<?php
require_once __DIR__ . '/../config/json_api.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    jsonResponse(['count' => 0]);
}

$user = refreshCurrentUser($conn);
jsonResponse(['count' => getUnreadNotificationCount($conn, (int)$user['id'])]);
