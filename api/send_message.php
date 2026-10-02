<?php
require_once __DIR__ . '/../config/json_api.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$user = refreshCurrentUser($conn);
ensureChatSchema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$content     = sanitizeChatText($_POST['content'] ?? '');
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$attachment  = uploadChatAttachment($_FILES['attachment'] ?? []);

if (!$receiver_id) {
    jsonResponse(['error' => 'Invalid data'], 400);
}

if ($content === '' && empty($attachment)) {
    jsonResponse(['error' => 'أدخل رسالة أو أرفق ملفاً'], 400);
}

$msg_type  = $attachment['msg_type'] ?? 'text';
$file_path = $attachment['file_path'] ?? null;
$file_name = $attachment['file_name'] ?? null;

$stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, content, msg_type, file_path, file_name) VALUES (?,?,?,?,?,?)');
$stmt->bind_param('iissss', $user['id'], $receiver_id, $content, $msg_type, $file_path, $file_name);
$stmt->execute();
$msg_id = $conn->insert_id;

$stmt2 = $conn->prepare('SELECT name FROM users WHERE id=?');
$stmt2->bind_param('i', $user['id']);
$stmt2->execute();
$sender = $stmt2->get_result()->fetch_assoc();

$preview = chatMessagePreview([
    'msg_type'  => $msg_type,
    'content'   => $content,
    'file_name' => $file_name,
]);
$link = $user['role'] === 'doctor' ? "../user/messages.php?with={$user['id']}" : "../doctor/messages.php?with={$user['id']}";
addNotification($conn, $receiver_id, 'message', 'رسالة جديدة', "رسالة من {$sender['name']}: $preview", $link);

jsonResponse([
    'success'   => true,
    'id'        => $msg_id,
    'content'   => $content,
    'msg_type'  => $msg_type,
    'file_path' => $file_path,
    'file_name' => $file_name,
    'time'      => date('h:i A'),
]);
