<?php
require_once __DIR__ . '/../dbcon.php';
require_once __DIR__ . '/../includes/data-protection.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$apply = in_array('--apply', $argv, true);
$fields = ['telefono', 'calle', 'exterior', 'interior', 'colonia', 'ciudad', 'estado', 'postal', 'pais'];
$fieldList = implode(', ', array_map(static fn(string $field): string => '`' . $field . '`', $fields));
$metadata = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME IN (" . implode(',', array_fill(0, count($fields), '?')) . ')');
$metadata->execute($fields);
$columns = [];
foreach ($metadata->fetchAll() as $column) {
    $columns[$column['COLUMN_NAME']] = $column;
}

foreach ($fields as $field) {
    if (!isset($columns[$field])) {
        fwrite(STDERR, "Falta la columna pedidos.$field; no se modificó nada.\n");
        exit(1);
    }
}

if (!$apply) {
    fwrite(STDOUT, "Simulación: se ampliarán las columnas personales de pedidos y se cifrarán los registros existentes.\n");
    foreach ($fields as $field) {
        $column = $columns[$field];
        fwrite(STDOUT, $field . ': ' . $column['DATA_TYPE'] . ($column['DATA_TYPE'] === 'varchar' ? '(' . $column['CHARACTER_MAXIMUM_LENGTH'] . ')' : '') . "\n");
    }
    fwrite(STDOUT, "No se realizaron cambios. Haz respaldo de la base de datos y ejecuta con --apply para aplicar.\n");
    exit(0);
}

dataProtectionKey();

foreach ($fields as $field) {
    $column = $columns[$field];
    if (!in_array(strtolower($column['DATA_TYPE']), ['text', 'mediumtext', 'longtext'], true)) {
        $nullable = $column['IS_NULLABLE'] === 'YES' ? ' NULL' : ' NOT NULL';
        $charset = $column['CHARACTER_SET_NAME'] ? ' CHARACTER SET ' . $column['CHARACTER_SET_NAME'] : '';
        $collation = $column['COLLATION_NAME'] ? ' COLLATE ' . $column['COLLATION_NAME'] : '';
        $default = $column['COLUMN_DEFAULT'] === null
            ? ($column['IS_NULLABLE'] === 'YES' ? ' DEFAULT NULL' : '')
            : ' DEFAULT ' . $pdo->quote((string)$column['COLUMN_DEFAULT']);
        $pdo->exec('ALTER TABLE `pedidos` MODIFY COLUMN `' . $field . '` VARCHAR(2048)' . $charset . $collation . $nullable . $default);
    }
}

$select = $pdo->prepare('SELECT `id`, ' . $fieldList . ' FROM `pedidos` WHERE `id` > ? ORDER BY `id` LIMIT 200');
$setClause = implode(', ', array_map(static fn(string $field): string => '`' . $field . '` = ?', $fields));
$update = $pdo->prepare('UPDATE `pedidos` SET ' . $setClause . ' WHERE `id` = ?');
$lastId = 0;
$encryptedRows = 0;

while (true) {
    $select->execute([$lastId]);
    $rows = $select->fetchAll();
    if (!$rows) {
        break;
    }

    foreach ($rows as $row) {
        $values = [];
        $changed = false;
        foreach ($fields as $field) {
            $original = $row[$field];
            $encrypted = encryptPersonalData($original);
            $values[] = $encrypted;
            $changed = $changed || $encrypted !== $original;
        }

        if ($changed) {
            $values[] = $row['id'];
            $update->execute($values);
            $encryptedRows++;
        }
        $lastId = (int)$row['id'];
    }
}

fwrite(STDOUT, "Migración completada. Pedidos cifrados: $encryptedRows.\n");
