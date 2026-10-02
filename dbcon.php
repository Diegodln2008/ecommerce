<?php
require_once __DIR__ . '/includes/security.php';
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
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    header('Retry-After: 60');
    exit('Servicio temporalmente no disponible. Intenta nuevamente más tarde.');
}

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, 3306);
if ($mysqli->connect_error) {
    error_log('MySQLi connection failed: ' . $mysqli->connect_error);
    http_response_code(503);
    header('Retry-After: 60');
    exit('Servicio temporalmente no disponible. Intenta nuevamente más tarde.');
}
$mysqli->set_charset('utf8mb4');
$con = $mysqli;
?>