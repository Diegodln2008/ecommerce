<?php

function dataProtectionKey(): string
{
    $configuredKey = getenv('DATA_ENCRYPTION_KEY') ?: ($_ENV['DATA_ENCRYPTION_KEY'] ?? '');
    if ($configuredKey === '') {
        throw new RuntimeException('DATA_ENCRYPTION_KEY no está configurada.');
    }

    return hash('sha256', $configuredKey, true);
}

function encryptPersonalData(?string $value): ?string
{
    if ($value === null || $value === '' || str_starts_with($value, 'enc:v1:')) {
        return $value;
    }

    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($value, 'aes-256-gcm', dataProtectionKey(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('No se pudo cifrar el dato personal.');
    }

    return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);
}

function decryptPersonalData(?string $value): ?string
{
    if ($value === null || $value === '' || !str_starts_with($value, 'enc:v1:')) {
        return $value;
    }

    $payload = base64_decode(substr($value, 7), true);
    if ($payload === false || strlen($payload) < 29) {
        throw new RuntimeException('El dato cifrado está dañado.');
    }

    $plaintext = openssl_decrypt(
        substr($payload, 28),
        'aes-256-gcm',
        dataProtectionKey(),
        OPENSSL_RAW_DATA,
        substr($payload, 0, 12),
        substr($payload, 12, 16)
    );
    if ($plaintext === false) {
        throw new RuntimeException('No se pudo descifrar el dato personal. Verifica DATA_ENCRYPTION_KEY.');
    }

    return $plaintext;
}

function decryptOrderPersonalData(array $order): array
{
    foreach (['telefono', 'calle', 'exterior', 'interior', 'colonia', 'ciudad', 'estado', 'postal', 'pais'] as $field) {
        if (array_key_exists($field, $order)) {
            $order[$field] = decryptPersonalData($order[$field]);
        }
    }

    return $order;
}

function assertOrderPersonalDataReady(PDO $pdo): void
{
    dataProtectionKey();

    $fields = ['telefono', 'calle', 'exterior', 'interior', 'colonia', 'ciudad', 'estado', 'postal', 'pais'];
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $statement = $pdo->prepare("SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME IN ($placeholders)");
    $statement->execute($fields);
    $capacities = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $capacities[$column['COLUMN_NAME']] = (int)$column['CHARACTER_MAXIMUM_LENGTH'];
    }

    foreach ($fields as $field) {
        if (($capacities[$field] ?? 0) < 512) {
            throw new RuntimeException('La columna pedidos.' . $field . ' no está preparada para guardar datos cifrados.');
        }
    }
}