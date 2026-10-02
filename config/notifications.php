<?php

function notificationsTableExists(mysqli $conn): bool {
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    $r = $conn->query("SHOW TABLES LIKE 'notifications'");
    $exists = $r && $r->num_rows > 0;
    return $exists;
}

function getUnreadNotificationCount(mysqli $conn, int $user_id): int {
    if (!notificationsTableExists($conn) || $user_id <= 0) {
        return 0;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
}

function notifyUsersByRole(mysqli $conn, string $role, string $type, string $title, string $body, string $link, string $extraWhere = ''): void {
    $sql = "SELECT id FROM users WHERE role=? AND status='active'" . $extraWhere;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        addNotification($conn, (int)$row['id'], $type, $title, $body, $link);
    }
}

function notifyAdmins(mysqli $conn, string $type, string $title, string $body, string $link, string $event = 'system'): void {
    if (function_exists('adminNotificationAllowed') && !adminNotificationAllowed($conn, $event)) {
        return;
    }
    notifyUsersByRole($conn, 'admin', $type, $title, $body, $link);
}

function notifyParents(mysqli $conn, string $type, string $title, string $body, string $link): void {
    notifyUsersByRole($conn, 'parent', $type, $title, $body, $link);
}

function notifyDoctors(mysqli $conn, string $type, string $title, string $body, string $link): void {
    notifyUsersByRole($conn, 'doctor', $type, $title, $body, $link, " AND (doctor_status='approved' OR doctor_status IS NULL)");
}

function notifyUser(mysqli $conn, int $user_id, string $type, string $title, string $body, string $link): void {
    if ($user_id > 0) {
        addNotification($conn, $user_id, $type, $title, $body, $link);
    }
}

function getUserById(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT id, name, email, role FROM users WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}
