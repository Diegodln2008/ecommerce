<?php

require_once __DIR__ . '/dbcon.php';

$stmt = $pdo->query("SELECT * FROM productos");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($productos);