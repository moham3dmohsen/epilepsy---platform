<?php
require_once __DIR__ . '/../config/json_api.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    jsonResponse([]);
}

$user = refreshCurrentUser($conn);
ensureChatSchema($conn);
$with_id = (int)($_GET['with'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

if (!$with_id) {
    jsonResponse([]);
}

$stmt = $conn->prepare("SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)) AND m.id > ? ORDER BY m.created_at ASC");
$stmt->bind_param("iiiii", $user['id'], $with_id, $with_id, $user['id'], $last_id);
$stmt->execute();
$msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->query("UPDATE messages SET is_read=1 WHERE sender_id=" . (int)$with_id . " AND receiver_id=" . (int)$user['id']);

jsonResponse($msgs);
