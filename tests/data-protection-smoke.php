<?php
putenv('DATA_ENCRYPTION_KEY=local-smoke-test-key-not-for-production');
require_once __DIR__ . '/../includes/data-protection.php';

$plaintext = 'Calle 5, Ciudad de México';
$ciphertext = encryptPersonalData($plaintext);
if ($ciphertext === $plaintext || decryptPersonalData($ciphertext) !== $plaintext) {
    fwrite(STDERR, "Falló el ciclo de cifrado y descifrado.\n");
    exit(1);
}

if (decryptPersonalData('Pedido antiguo en texto claro') !== 'Pedido antiguo en texto claro') {
    fwrite(STDERR, "Falló la compatibilidad con datos anteriores a la migración.\n");
    exit(1);
}

try {
    decryptPersonalData(substr_replace($ciphertext, '!', 7, 1));
    fwrite(STDERR, "Se aceptó un valor cifrado manipulado.\n");
    exit(1);
} catch (RuntimeException $exception) {
}

fwrite(STDOUT, "Cifrado autenticado, compatibilidad heredada y detección de manipulación: OK.\n");
