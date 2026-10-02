<?php
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/notifications_ui.php';
require_once __DIR__ . '/chat.php';
require_once __DIR__ . '/platform_settings.php';

if (function_exists('bootstrapPlatformGuards')) {
    bootstrapPlatformGuards();
}


function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function flashMessage($message, $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'message' => $message,
        'type'    => $type,
    ];
}

function getFlashMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function uploadImage($file, $subfolder = 'uploads') {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        return '';
    }
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    $dir      = __DIR__ . '/../uploads/' . $subfolder . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $dest = $dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'uploads/' . $subfolder . '/' . $filename;
    }
    return '';
}

function calculateEpilepsyRisk(string $symptoms): array {
    $symptomsList = explode(',', strtolower($symptoms));
    $highRisk = ['تشنجات', 'فقدان الوعي', 'نوبات مفاجئة'];
    $medRisk  = ['حركات لا إرادية', 'شرود الذهن', 'صعوبة في الكلام'];
    $lowRisk  = ['مشاكل في النوم', 'صعوبة في التركيز'];

    $score = 0;
    foreach ($symptomsList as $s) {
        $s = trim($s);
        if (in_array($s, $highRisk)) $score += 30;
        elseif (in_array($s, $medRisk)) $score += 15;
        elseif (in_array($s, $lowRisk)) $score += 8;
    }

    $score += rand(-10, 10);
    $score = max(5, min(95, $score));

    if ($score >= 66) $result = 'positive';
    elseif ($score >= 35) $result = 'suspected';
    else $result = 'negative';

    return ['percentage' => $score, 'result' => $result];
}

function addNotification($conn, int $user_id, string $type, string $title, string $body = '', string $link = ''): void {
    if ($user_id <= 0) {
        return;
    }
    if (!notificationsTableExists($conn)) {
        return;
    }
    $allowed = ['report', 'message', 'assessment', 'system'];
    if (!in_array($type, $allowed, true)) {
        $type = 'system';
    }
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (?,?,?,?,?)');
    $stmt->bind_param('issss', $user_id, $type, $title, $body, $link);
    $stmt->execute();
}
