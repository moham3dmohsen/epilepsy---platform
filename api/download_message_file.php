<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

ensureChatSchema($conn);
$user = refreshCurrentUser($conn);
$id   = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    exit('Invalid request');
}

$stmt = $conn->prepare('SELECT * FROM messages WHERE id=? AND (sender_id=? OR receiver_id=?) LIMIT 1');
$stmt->bind_param('iii', $id, $user['id'], $user['id']);
$stmt->execute();
$msg = $stmt->get_result()->fetch_assoc();

if (!$msg || empty($msg['file_path'])) {
    http_response_code(404);
    exit('Not found');
}

$fullPath = realpath(__DIR__ . '/../' . $msg['file_path']);
$baseDir  = realpath(__DIR__ . '/../uploads/chat');

if (!$fullPath || !$baseDir || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

$downloadName = $msg['file_name'] ?: basename($fullPath);
$mime         = mime_content_type($fullPath) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
