<?php
require_once __DIR__ . '/../config/json_api.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    jsonResponse([]);
}

$user = refreshCurrentUser($conn);

$tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    jsonResponse([]);
}

$all = isset($_GET['all']) && $_GET['all'] === '1';
$limit = min(100, max(1, (int)($_GET['limit'] ?? ($all ? 50 : 10))));

if ($all) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT $limit");
    $stmt->bind_param('i', $user['id']);
} else {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT $limit");
    $stmt->bind_param('i', $user['id']);
}

if (!$stmt) {
    jsonResponse([]);
}
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

jsonResponse($notifs);
