<?php
require_once __DIR__ . '/includes/security.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/dbcon.php';
requireUserRole([1]);

function adminModuleEscape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$sectionDefinitions = [
    'settings' => ['title' => 'Configuraciones', 'table' => 'configuraciones', 'fields' => []],
    'marketing' => ['title' => 'Marketing y promociones', 'table' => 'promociones', 'fields' => [
        'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
        'medio' => ['label' => 'Ruta de imagen o medio', 'type' => 'text', 'required' => true],
        'url' => ['label' => 'Enlace de destino', 'type' => 'text', 'required' => false],
        'estatus' => ['label' => 'Estado', 'type' => 'select', 'options' => ['1' => 'Activo', '0' => 'Inactivo']],
    ]],
    'statistics' => ['title' => 'Estadísticas', 'table' => '', 'fields' => []],
    'inactive-products' => ['title' => 'Productos inactivos', 'table' => 'productosventa', 'fields' => []],
    'videos' => ['title' => 'Videos', 'table' => 'videos', 'fields' => [
        'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
        'path' => ['label' => 'Ruta o URL del video', 'type' => 'text', 'required' => true],
        'estatus' => ['label' => 'Estado', 'type' => 'select', 'options' => ['1' => 'Activo', '0' => 'Inactivo']],
    ]],
    'catalogs' => ['title' => 'Catálogos', 'table' => 'catalogos', 'fields' => [
        'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
        'path' => ['label' => 'Ruta o URL del catálogo', 'type' => 'text', 'required' => true],
        'estatus' => ['label' => 'Estado', 'type' => 'select', 'options' => ['1' => 'Activo', '0' => 'Inactivo']],
    ]],
    'finished-orders' => ['title' => 'Compras finalizadas', 'table' => 'pedidos', 'fields' => []],
    'coupons' => ['title' => 'Cupones', 'table' => 'cupones', 'fields' => [
        'cupon' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
        'codigo' => ['label' => 'Código', 'type' => 'text', 'required' => true],
        'porcentaje' => ['label' => 'Descuento (%)', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 100],
        'minimo' => ['label' => 'Compra mínima', 'type' => 'number', 'required' => true, 'min' => 0],
        'maximo' => ['label' => 'Descuento máximo', 'type' => 'number', 'required' => true, 'min' => 0],
        'canjes' => ['label' => 'Usos permitidos', 'type' => 'number', 'required' => true, 'min' => 1],
        'estatus' => ['label' => 'Estado', 'type' => 'select', 'options' => ['1' => 'Activo', '0' => 'Inactivo']],
    ]],
    'promotions' => ['title' => 'Promociones', 'table' => 'promociones', 'fields' => [
        'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
        'medio' => ['label' => 'Ruta de imagen o medio', 'type' => 'text', 'required' => true],
        'url' => ['label' => 'Enlace de destino', 'type' => 'text', 'required' => false],
        'estatus' => ['label' => 'Estado', 'type' => 'select', 'options' => ['1' => 'Activo', '0' => 'Inactivo']],
    ]],
    'categories' => ['title' => 'Categorías', 'table' => 'categorias', 'fields' => [
        'categoria' => ['label' => 'Categoría', 'type' => 'text', 'required' => true],
    ]],
    'subcategories' => ['title' => 'Subcategorías', 'table' => 'subcategorias', 'fields' => [
        'subcategoria' => ['label' => 'Subcategoría', 'type' => 'text', 'required' => true],
        'medio' => ['label' => 'Ruta de imagen (opcional)', 'type' => 'text', 'required' => false],
    ]],
    'industries' => ['title' => 'Industrias', 'table' => 'industrias', 'fields' => [
        'industria' => ['label' => 'Industria', 'type' => 'text', 'required' => true],
    ]],
];

$section = (string)($_GET['section'] ?? 'settings');
if (!isset($sectionDefinitions[$section])) {
    http_response_code(404);
    exit('Sección no encontrada.');
}

$definition = $sectionDefinitions[$section];
$notice = $_SESSION['admin_module_notice'] ?? null;
unset($_SESSION['admin_module_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($section === 'settings' && $action === 'update-settings') {
            $settingId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$settingId || !isset($_POST['valoruno'], $_POST['valordos'])) {
                throw new InvalidArgumentException('Revisa los valores de configuración.');
            }
            $statement = $pdo->prepare('UPDATE configuraciones SET valoruno = ?, valordos = ? WHERE id = ?');
            $statement->execute([trim((string)$_POST['valoruno']), trim((string)$_POST['valordos']), $settingId]);
            $_SESSION['admin_module_notice'] = 'Configuración actualizada.';
        } elseif ($section === 'inactive-products' && $action === 'reactivate-product') {
            $productId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$productId) {
                throw new InvalidArgumentException('Producto no válido.');
            }
            $statement = $pdo->prepare('UPDATE productosventa SET estatus = 1 WHERE id = ? AND estatus = 0');
            $statement->execute([$productId]);
            $_SESSION['admin_module_notice'] = 'Producto reactivado.';
        } elseif ($action === 'save' && !empty($definition['fields'])) {
            $fields = array_keys($definition['fields']);
            $values = [];
            foreach ($definition['fields'] as $field => $options) {
                $value = trim((string)($_POST[$field] ?? ''));
                if (($options['required'] ?? false) && $value === '') {
                    throw new InvalidArgumentException('Completa el campo: ' . $options['label'] . '.');
                }
                if (($options['type'] ?? '') === 'select' && !array_key_exists($value, $options['options'])) {
                    throw new InvalidArgumentException('Selecciona un estado válido.');
                }
                if (($options['type'] ?? '') === 'number' && ($value !== '' && !is_numeric($value))) {
                    throw new InvalidArgumentException('El campo ' . $options['label'] . ' debe ser numérico.');
                }
                if (($options['type'] ?? '') === 'number' && $value !== '') {
                    $numericValue = (float)$value;
                    if (isset($options['min']) && $numericValue < $options['min']) {
                        throw new InvalidArgumentException('El campo ' . $options['label'] . ' está fuera de rango.');
                    }
                    if (isset($options['max']) && $numericValue > $options['max']) {
                        throw new InvalidArgumentException('El campo ' . $options['label'] . ' está fuera de rango.');
                    }
                }
                $values[$field] = $value;
            }

            if (in_array($section, ['marketing', 'promotions'], true) && !empty($values['url'])) {
                $scheme = parse_url($values['url'], PHP_URL_SCHEME);
                if ($scheme !== null && !in_array(strtolower($scheme), ['http', 'https'], true)) {
                    throw new InvalidArgumentException('El enlace debe usar HTTP o HTTPS.');
                }
            }

            if (isset($_POST['id']) && ctype_digit((string)$_POST['id'])) {
                $set = implode(', ', array_map(static fn(string $field): string => '`' . $field . '` = ?', $fields));
                $statement = $pdo->prepare('UPDATE `' . $definition['table'] . '` SET ' . $set . ' WHERE id = ?');
                $statement->execute([...array_values($values), (int)$_POST['id']]);
                $_SESSION['admin_module_notice'] = $definition['title'] . ' actualizado.';
            } else {
                $columns = implode(', ', array_map(static fn(string $field): string => '`' . $field . '`', $fields));
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                $statement = $pdo->prepare('INSERT INTO `' . $definition['table'] . '` (' . $columns . ') VALUES (' . $placeholders . ')');
                $statement->execute(array_values($values));
                $_SESSION['admin_module_notice'] = $definition['title'] . ' creado.';
            }
        } elseif ($action === 'delete' && in_array($section, ['categories', 'subcategories', 'industries'], true)) {
            $recordId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$recordId) {
                throw new InvalidArgumentException('Registro no válido.');
            }
            $pdo->beginTransaction();
            if ($section === 'categories') {
                $pdo->prepare('DELETE FROM categoriasasociadasventa WHERE categoria = (SELECT categoria FROM categorias WHERE id = ?)')->execute([$recordId]);
                $pdo->prepare('DELETE FROM categoriasasociadas WHERE categoria = (SELECT categoria FROM categorias WHERE id = ?)')->execute([$recordId]);
            } elseif ($section === 'subcategories') {
                $pdo->prepare('DELETE FROM subcategoriasasociadasventa WHERE subcategoria = (SELECT subcategoria FROM subcategorias WHERE id = ?)')->execute([$recordId]);
            } else {
                $pdo->prepare('DELETE FROM industriaasociadaventa WHERE industria = (SELECT industria FROM industrias WHERE id = ?)')->execute([$recordId]);
                $pdo->prepare('DELETE FROM industriaasociada WHERE industria = (SELECT industria FROM industrias WHERE id = ?)')->execute([$recordId]);
            }
            $statement = $pdo->prepare('DELETE FROM `' . $definition['table'] . '` WHERE id = ?');
            $statement->execute([$recordId]);
            $pdo->commit();
            $_SESSION['admin_module_notice'] = 'Registro eliminado.';
        } else {
            throw new InvalidArgumentException('Acción no válida.');
        }
    } catch (InvalidArgumentException $exception) {
        $_SESSION['admin_module_notice'] = $exception->getMessage();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Admin module operation failed: ' . $exception->getMessage());
        $_SESSION['admin_module_notice'] = 'No se pudo guardar el cambio. Revisa los datos e intenta nuevamente.';
    }

    header('Location: admin-modulos.php?section=' . rawurlencode($section));
    exit;
}

$records = [];
if ($section === 'settings') {
    $records = $pdo->query('SELECT id, nombre, detalle, valoruno, valordos FROM configuraciones ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
} elseif ($section === 'inactive-products') {
    $records = $pdo->query('SELECT id, titulo, subtitulo, sku, stock FROM productosventa WHERE estatus = 0 ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
} elseif ($section === 'finished-orders') {
    $records = $pdo->query("SELECT identificador, fecha, total, status_pago, guia FROM pedidos WHERE estatus = 0 ORDER BY id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($section === 'statistics') {
    $statistics = [
        'Pedidos' => (int)$pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
        'Pedidos pagados' => (int)$pdo->query("SELECT COUNT(*) FROM pedidos WHERE LOWER(status_pago) = 'pagado'")->fetchColumn(),
        'Ventas pagadas' => (float)$pdo->query("SELECT COALESCE(SUM(CAST(total AS DECIMAL(12,2))), 0) FROM pedidos WHERE LOWER(status_pago) = 'pagado'")->fetchColumn(),
        'Productos activos' => (int)$pdo->query('SELECT COUNT(*) FROM productosventa WHERE estatus = 1')->fetchColumn(),
        'Productos inactivos' => (int)$pdo->query('SELECT COUNT(*) FROM productosventa WHERE estatus = 0')->fetchColumn(),
    ];
} else {
    $statement = $pdo->query('SELECT * FROM `' . $definition['table'] . '` ORDER BY id DESC LIMIT 200');
    $records = $statement->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = $definition['title'] . ' | Fastpack';
$bodyClass = 'sb-nav-fixed';
$additionalStyles = ['https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'];
require __DIR__ . '/templates/header.php';
include __DIR__ . '/sidenav.php';
?>
<div id="layoutSidenav_content">
    <main class="container-fluid p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <p class="text-uppercase text-muted small mb-1">Panel de administración</p>
                <h1 class="h3 mb-0"><?= adminModuleEscape($definition['title']); ?></h1>
            </div>
            <a class="btn btn-outline-secondary" href="<?= adminModuleEscape(authenticatedHomePath()); ?>">Volver al inicio</a>
        </div>

        <?php if ($notice): ?>
            <div class="alert alert-info" role="status"><?= adminModuleEscape($notice); ?></div>
        <?php endif; ?>

        <?php if ($section === 'statistics'): ?>
            <div class="row g-3">
                <?php foreach ($statistics as $label => $value): ?>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <section class="border rounded p-3 h-100 bg-white">
                            <p class="text-muted mb-2"><?= adminModuleEscape($label); ?></p>
                            <strong class="fs-4"><?= $label === 'Ventas pagadas' ? '$' . number_format((float)$value, 2) : number_format((int)$value); ?></strong>
                        </section>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($section === 'settings'): ?>
            <div class="table-responsive bg-white border rounded">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>Configuración</th><th>Detalle</th><th>Valor 1</th><th>Valor 2</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= adminModuleEscape($record['nombre']); ?></td>
                            <td><?= adminModuleEscape($record['detalle']); ?></td>
                            <td colspan="2">
                                <form method="post" class="d-flex flex-wrap gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= adminModuleEscape(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="update-settings">
                                    <input type="hidden" name="id" value="<?= (int)$record['id']; ?>">
                                    <input class="form-control" name="valoruno" aria-label="Valor 1" value="<?= adminModuleEscape($record['valoruno']); ?>">
                                    <input class="form-control" name="valordos" aria-label="Valor 2" value="<?= adminModuleEscape($record['valordos']); ?>">
                                    <button class="btn btn-primary" type="submit">Guardar</button>
                                </form>
                            </td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($section === 'inactive-products'): ?>
            <div class="table-responsive bg-white border rounded">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>ID</th><th>Producto</th><th>SKU</th><th>Existencia</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= (int)$record['id']; ?></td><td><?= adminModuleEscape($record['titulo'] . ' ' . $record['subtitulo']); ?></td>
                            <td><?= adminModuleEscape($record['sku']); ?></td><td><?= (int)$record['stock']; ?></td>
                            <td><form method="post"><input type="hidden" name="csrf_token" value="<?= adminModuleEscape(csrfToken()); ?>"><input type="hidden" name="action" value="reactivate-product"><input type="hidden" name="id" value="<?= (int)$record['id']; ?>"><button class="btn btn-sm btn-primary" type="submit">Reactivar</button></form></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$records): ?><tr><td colspan="5" class="text-muted p-3">No hay productos inactivos.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($section === 'finished-orders'): ?>
            <div class="table-responsive bg-white border rounded">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>Pedido</th><th>Fecha</th><th>Pago</th><th>Total</th><th>Guía</th></tr></thead>
                    <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr><td><?= adminModuleEscape($record['identificador']); ?></td><td><?= adminModuleEscape($record['fecha']); ?></td><td><?= adminModuleEscape($record['status_pago']); ?></td><td>$<?= number_format((float)$record['total'], 2); ?></td><td><?= adminModuleEscape($record['guia']); ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$records): ?><tr><td colspan="5" class="text-muted p-3">No hay compras finalizadas.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <?php if ($definition['fields']): ?>
                <form method="post" class="row g-3 border rounded p-3 mb-4 bg-white">
                    <input type="hidden" name="csrf_token" value="<?= adminModuleEscape(csrfToken()); ?>">
                    <input type="hidden" name="action" value="save">
                    <?php foreach ($definition['fields'] as $field => $options): ?>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new-<?= adminModuleEscape($field); ?>"><?= adminModuleEscape($options['label']); ?></label>
                            <?php if ($options['type'] === 'select'): ?>
                                <select class="form-select" id="new-<?= adminModuleEscape($field); ?>" name="<?= adminModuleEscape($field); ?>" required>
                                    <?php foreach ($options['options'] as $value => $label): ?><option value="<?= adminModuleEscape($value); ?>"><?= adminModuleEscape($label); ?></option><?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input class="form-control" id="new-<?= adminModuleEscape($field); ?>" name="<?= adminModuleEscape($field); ?>" type="<?= adminModuleEscape($options['type']); ?>" <?= !empty($options['required']) ? 'required' : ''; ?> <?= isset($options['min']) ? 'min="' . adminModuleEscape($options['min']) . '"' : ''; ?> <?= isset($options['max']) ? 'max="' . adminModuleEscape($options['max']) . '"' : ''; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Crear</button></div>
                </form>
            <?php endif; ?>

            <div class="table-responsive bg-white border rounded">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr>
                        <?php foreach (array_keys($definition['fields']) as $field): ?><th><?= adminModuleEscape($definition['fields'][$field]['label']); ?></th><?php endforeach; ?>
                        <?php if (in_array($section, ['categories', 'subcategories', 'industries'], true)): ?><th></th><?php endif; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <?php foreach (array_keys($definition['fields']) as $field): ?><td><?= adminModuleEscape($record[$field] ?? ''); ?></td><?php endforeach; ?>
                            <?php if (in_array($section, ['categories', 'subcategories', 'industries'], true)): ?>
                                <td><form method="post" onsubmit="return confirm('¿Eliminar este registro?');"><input type="hidden" name="csrf_token" value="<?= adminModuleEscape(csrfToken()); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$record['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button></form></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$records): ?><tr><td colspan="<?= max(1, count($definition['fields']) + 1); ?>" class="text-muted p-3">No hay registros.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>