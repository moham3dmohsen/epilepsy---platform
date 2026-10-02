<?php
require_once '../config/db.php';
require_once '../config/session.php';
requireLogin('admin');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: users.php'); exit; }

$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id=? AND role != 'admin'");
$stmt->bind_param("i", $id);
$stmt->execute();
$target = $stmt->get_result()->fetch_assoc();

if (!$target) { header('Location: users.php'); exit; }

$_SESSION['view_as'] = [
    'id'    => $target['id'],
    'name'  => $target['name'],
    'email' => $target['email'],
    'role'  => $target['role'],
];

if ($target['role'] === 'doctor') {
    header('Location: ../doctor/dashboard.php');
} else {
    header('Location: ../user/dashboard.php');
}
exit;
