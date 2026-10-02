<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        $base = dirname($_SERVER['PHP_SELF']);
        $base = ($base === '/' || $base === '\\') ? '' : $base;
        header('Location: ' . $base . '/../login.php');
        exit;
    }
    if (isset($_SESSION['view_as'])) {
        $effective_role = $_SESSION['view_as']['role'];
    } else {
        $effective_role = $_SESSION['role'];
    }
    if ($role && $effective_role !== $role && $_SESSION['role'] !== 'admin') {
        $base = dirname($_SERVER['PHP_SELF']);
        $base = ($base === '/' || $base === '\\') ? '' : $base;
        header('Location: ' . $base . '/../login.php');
        exit;
    }
}

function getCurrentUser() {
    if (isset($_SESSION['view_as'])) {
        return $_SESSION['view_as'];
    }
    return [
        'id'              => $_SESSION['user_id']         ?? null,
        'name'            => $_SESSION['name']            ?? null,
        'email'           => $_SESSION['email']           ?? null,
        'role'            => $_SESSION['role']            ?? null,
        'doctor_status'   => $_SESSION['doctor_status']   ?? null,
        'syndicate_card'  => $_SESSION['syndicate_card']  ?? null,
        'profile_picture' => $_SESSION['profile_picture'] ?? null,
    ];
}

function refreshCurrentUser($conn) {
    if (isset($_SESSION['view_as'])) {
        return $_SESSION['view_as'];
    }
    $id = (int)($_SESSION['user_id'] ?? 0);
    if (!$id) return getCurrentUser();

    if ($conn) {
        $result = $conn->query("SELECT id, name, email, role, profile_picture, doctor_status, syndicate_card FROM users WHERE id=$id LIMIT 1");
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row) {
                $_SESSION['name']            = $row['name'];
                $_SESSION['email']           = $row['email'];
                $_SESSION['profile_picture'] = $row['profile_picture'];
                return [
                    'id'              => (int)$row['id'],
                    'name'            => $row['name'],
                    'email'           => $row['email'],
                    'role'            => $row['role'],
                    'doctor_status'   => $row['doctor_status'],
                    'syndicate_card'  => $row['syndicate_card'],
                    'profile_picture' => $row['profile_picture'],
                ];
            }
        }
    }
    return getCurrentUser();
}

function isViewingAs() {
    return isset($_SESSION['view_as']);
}

function avatarHtml(array $user, string $size = 'md', string $base = '../'): string {
    $sizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-20 h-20 text-3xl',
    ];
    $cls = $sizes[$size] ?? $sizes['md'];
    $initial = htmlspecialchars(mb_substr($user['name'] ?? '?', 0, 1, 'UTF-8'));

    if (!empty($user['profile_picture'])) {
        $src = htmlspecialchars($base . $user['profile_picture']);
        return "<img src=\"{$src}\" alt=\"صورة البروفايل\" class=\"{$cls} rounded-full object-cover border-2 border-white/30\">";
    }
    return "<div class=\"{$cls} rounded-full gradient-primary flex items-center justify-center text-white font-bold flex-shrink-0\">{$initial}</div>";
}

function getAdminUser() {
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['name']    ?? null,
        'email' => $_SESSION['email']   ?? null,
        'role'  => $_SESSION['role']    ?? null,
    ];
}

function bootstrapPlatformGuards(): void {
    static $booted = false;
    if ($booted || !file_exists(__DIR__ . '/db.php')) {
        return;
    }
    $booted = true;
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/platform_settings.php';
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return;
    }
    ensurePlatformSettingsSchema($conn);
    enforceMaintenanceMode($conn);
    if (isset($_SESSION['user_id'])) {
        enforceSessionTimeout($conn);
    }
}
