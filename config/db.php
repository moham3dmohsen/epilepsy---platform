<?php
$host = getenv('MYSQLHOST') ?: getenv('DB_HOST');
$user = getenv('MYSQLUSER') ?: getenv('DB_USER');
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS');
$name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME');
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT');
if (!$host || !$user || !$name) {
    die(json_encode(['error' => 'Database environment variables are missing on Railway!']));
}

$conn = new mysqli($host, $user, $pass, $name, $port ? (int)$port : 3306);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}
?>