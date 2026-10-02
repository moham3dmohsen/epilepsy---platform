<?php
$host = 'mysql.railway.internal';
$user = 'root';
$pass = 'sIIBeBetQvCraDNB1Xl1bITnzJhyivrs';
$name = 'railway';
$port = 3306;

try {
    $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $name . ";charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}
?>
