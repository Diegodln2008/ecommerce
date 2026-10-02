<?php
require_once __DIR__ . '/../includes/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$section = (string)($argv[1] ?? '');
$expectedTitles = [
    'settings' => 'Configuraciones',
    'marketing' => 'Marketing y promociones',
    'statistics' => 'Estadísticas',
    'inactive-products' => 'Productos inactivos',
    'videos' => 'Videos',
    'catalogs' => 'Catálogos',
    'finished-orders' => 'Compras finalizadas',
    'coupons' => 'Cupones',
    'promotions' => 'Promociones',
    'categories' => 'Categorías',
    'subcategories' => 'Subcategorías',
    'industries' => 'Industrias',
];

if (!isset($expectedTitles[$section])) {
    fwrite(STDERR, "Sección de prueba inválida.\n");
    exit(2);
}

$_SESSION = [
    'username' => '__admin_module_smoke__',
    'user_name' => 'Admin smoke',
    'user_role' => 1,
];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['section'] = $section;

ob_start();
include __DIR__ . '/../admin-modulos.php';
$html = ob_get_clean();

$dom = new DOMDocument();
$previousErrorMode = libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();
libxml_use_internal_errors($previousErrorMode);
$xpath = new DOMXPath($dom);
$heading = trim($xpath->query('//h1')->item(0)?->textContent ?? '');
$layoutCount = $xpath->query('//*[@id="layoutSidenav"]')->length;
$contentCount = $xpath->query('//*[@id="layoutSidenav_content"]')->length;

if ($heading !== $expectedTitles[$section] || $layoutCount !== 1 || $contentCount !== 1) {
    fwrite(STDERR, 'Falló el render de la sección ' . $section . ".\n");
    exit(1);
}

fwrite(STDOUT, 'OK ' . $section . PHP_EOL);