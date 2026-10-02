<?php

function platformSettingsDefaults(): array {
    return [
        'notif_new_user'       => '1',
        'notif_new_report'     => '1',
        'notif_critical'       => '1',
        'notif_contact'        => '1',
        'notif_system'         => '0',
        'allow_registration'   => '1',
        'maintenance_mode'     => '0',
        'content_review'       => '0',
        'admin_2fa'            => '0',
        'session_timeout'      => '60',
        'maintenance_message'  => 'المنصة قيد الصيانة حالياً. نعود قريباً.',
    ];
}

function ensurePlatformSettingsSchema(mysqli $conn): void {
    static $done = false;
    if ($done) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS platform_settings (
        setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $defaults = platformSettingsDefaults();
    foreach ($defaults as $key => $value) {
        $stmt = $conn->prepare('INSERT IGNORE INTO platform_settings (setting_key, setting_value) VALUES (?, ?)');
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
    }

    $ac = $conn->query("SHOW COLUMNS FROM articles LIKE 'status'");
    if ($ac && $ac->num_rows === 0) {
        $conn->query("ALTER TABLE articles ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER read_time");
    }
    $vc = $conn->query("SHOW COLUMNS FROM videos LIKE 'status'");
    if ($vc && $vc->num_rows === 0) {
        $conn->query("ALTER TABLE videos ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER category");
    }

    $done = true;
}

function getPlatformSettings(mysqli $conn): array {
    ensurePlatformSettingsSchema($conn);
    $settings = platformSettingsDefaults();
    $result = $conn->query('SELECT setting_key, setting_value FROM platform_settings');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

function getSetting(mysqli $conn, string $key, ?string $default = null): string {
    $defaults = platformSettingsDefaults();
    $fallback = $default ?? ($defaults[$key] ?? '');
    ensurePlatformSettingsSchema($conn);
    $stmt = $conn->prepare('SELECT setting_value FROM platform_settings WHERE setting_key=? LIMIT 1');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (string)$row['setting_value'] : $fallback;
}

function setSetting(mysqli $conn, string $key, string $value): void {
    ensurePlatformSettingsSchema($conn);
    $stmt = $conn->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
}

function savePlatformSettings(mysqli $conn, array $data): void {
    $allowed = array_keys(platformSettingsDefaults());
    $toggles = [
        'notif_new_user', 'notif_new_report', 'notif_critical', 'notif_contact', 'notif_system',
        'allow_registration', 'maintenance_mode', 'content_review', 'admin_2fa',
    ];
    foreach ($allowed as $key) {
        if (in_array($key, $toggles, true)) {
            setSetting($conn, $key, !empty($data[$key]) ? '1' : '0');
            continue;
        }
        if (!array_key_exists($key, $data)) {
            continue;
        }
        $value = $data[$key];
        if ($key === 'session_timeout') {
            $value = (string)max(15, min(480, (int)$value));
        }
        if ($key === 'maintenance_message') {
            $value = trim(strip_tags((string)$value));
        }
        setSetting($conn, $key, (string)$value);
    }
}

function settingIsOn(mysqli $conn, string $key): bool {
    return getSetting($conn, $key, '0') === '1';
}

function adminNotificationAllowed(mysqli $conn, string $event): bool {
    $map = [
        'new_user'    => 'notif_new_user',
        'new_report'  => 'notif_new_report',
        'critical'    => 'notif_critical',
        'contact'     => 'notif_contact',
        'system'      => 'notif_system',
    ];
    $settingKey = $map[$event] ?? 'notif_system';
    return settingIsOn($conn, $settingKey);
}

function notifyAdminsIfEnabled(mysqli $conn, string $event, string $type, string $title, string $body, string $link): void {
    if (!adminNotificationAllowed($conn, $event)) {
        return;
    }
    notifyUsersByRole($conn, 'admin', $type, $title, $body, $link);
}

function isRegistrationOpen(mysqli $conn): bool {
    return settingIsOn($conn, 'allow_registration') && !settingIsOn($conn, 'maintenance_mode');
}

function isMaintenanceMode(mysqli $conn): bool {
    return settingIsOn($conn, 'maintenance_mode');
}

function requiresContentReview(mysqli $conn): bool {
    return settingIsOn($conn, 'content_review');
}

function newContentStatus(mysqli $conn): string {
    return requiresContentReview($conn) ? 'draft' : 'published';
}

function enforceSessionTimeout(mysqli $conn): void {
    if (!isLoggedIn()) {
        return;
    }
    $timeoutSec = max(15, min(480, (int)getSetting($conn, 'session_timeout', '60'))) * 60;
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > $timeoutSec) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $login = '/login.php';
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (preg_match('#/(admin|user|doctor)(/|$)#', $dir)) {
            $login = '../login.php';
        }
        header('Location: ' . $login . '?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function enforceMaintenanceMode(mysqli $conn): void {
    if (!isMaintenanceMode($conn)) {
        return;
    }
    $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
    if (strpos($script, '/admin/') !== false) {
        return;
    }
    if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin') {
        return;
    }
    if (isLoggedIn()) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
    require __DIR__ . '/../maintenance.php';
    exit;
}
