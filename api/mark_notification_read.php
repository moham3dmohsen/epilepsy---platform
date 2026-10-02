<?php
require_once __DIR__ . '/../config/json_api.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = refreshCurrentUser($conn);
$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    jsonResponse(['error' => 'Invalid id'], 400);
}

$stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?');
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();

jsonResponse(['success' => true, 'count' => getUnreadNotificationCount($conn, (int)$user['id'])]);
