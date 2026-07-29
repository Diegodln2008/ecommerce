<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbName = $_ENV['DB_NAME'] ?? 'ecommerce';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

$dsn = "mysql:host=$dbHost;port=3306;dbname=$dbName;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Error de conexión (PDO): ' . $e->getMessage());
}

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, 3306);
if ($mysqli->connect_error) {
    die('Error de conexión (MySQLi): ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
$con = $mysqli;
?>