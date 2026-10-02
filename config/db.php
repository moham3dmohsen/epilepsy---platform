<?php
$host = 'mysql.railway.internal';
$user = 'root';
$pass = 'sIIBeBetQvCraDNB1Xl1bITnzJhyivrs';
$name = 'railway';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $name, $port);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}
?>
