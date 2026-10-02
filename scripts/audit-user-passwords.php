<?php
require_once __DIR__ . '/../dbcon.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$passwords = $pdo->query('SELECT password FROM usuarios');
$counts = ['total' => 0, 'bcrypt' => 0, 'md5' => 0, 'plaintext_or_other' => 0];

while (($stored = $passwords->fetchColumn()) !== false) {
    $counts['total']++;
    $prefix = substr((string)$stored, 0, 4);
    if (in_array($prefix, [chr(36) . '2a' . chr(36), chr(36) . '2b' . chr(36), chr(36) . '2y' . chr(36)], true)) {
        $counts['bcrypt']++;
    } elseif (preg_match('/^[a-fA-F0-9]{32}$/', (string)$stored)) {
        $counts['md5']++;
    } else {
        $counts['plaintext_or_other']++;
    }
}

fwrite(STDOUT, json_encode($counts, JSON_PRETTY_PRINT) . PHP_EOL);
