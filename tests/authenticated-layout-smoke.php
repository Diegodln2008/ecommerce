<?php
require_once __DIR__ . '/../dbcon.php';

$testPdo = $pdo;
$tag = bin2hex(random_bytes(8));
$accounts = [];
$insert = $testPdo->prepare('INSERT INTO usuarios (nombre, apellidopaterno, apellidomaterno, username, password, rol, estatus) VALUES (?, ?, ?, ?, ?, ?, 1)');

try {
    foreach ([1, 2, 3] as $role) {
        $username = '__layout_smoke_' . $tag . '_' . $role . '@invalid.local';
        $insert->execute([
            'Layout',
            'Smoke',
            'Test',
            $username,
            password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
            $role,
        ]);
        $accounts[] = ['id' => (int)$testPdo->lastInsertId(), 'username' => $username, 'role' => $role];
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    foreach ($accounts as $account) {
        $_SESSION = [
            'username' => $account['username'],
            'user_id' => $account['id'],
            'user_name' => 'Layout Smoke',
            'user_role' => $account['role'],
        ];

        $page = match ($account['role']) {
            1 => 'usuarios.php',
            2 => 'carga-tienda-en-linea.php',
            3 => 'menu.php',
        };
        ob_start();
        include __DIR__ . '/../' . $page;
        $html = ob_get_clean();

        $dom = new DOMDocument();
        $previousErrorMode = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);

        $xpath = new DOMXPath($dom);
        if ($account['role'] === 3) {
            $publicNavCount = $xpath->query('//nav[contains(concat(" ", normalize-space(@class), " "), " navbar ")]')->length;
            $logoutLinkCount = $xpath->query('//a[@href="logout.php"]')->length;
            $menuText = $xpath->query('//body')->length === 0 ? $html : $xpath->query('//nav')->item(0)->textContent;
            if ($publicNavCount !== 1 || $logoutLinkCount !== 1 || !str_contains($menuText, 'Cliente')) {
                throw new RuntimeException('La navegación pública del cliente está incompleta.');
            }

            fwrite(STDOUT, 'Rol 3: navbar pública y cierre de sesión correctos.' . PHP_EOL);
            continue;
        }

        $layoutCount = $xpath->query('//*[@id="layoutSidenav"]')->length;
        $sidebarCount = $xpath->query('//*[@id="layoutSidenav_nav"]')->length;
        $contentCount = $xpath->query('//*[@id="layoutSidenav_content"]')->length;
        $usersLinkCount = $xpath->query('//*[@id="sidenavAccordion"]//a[normalize-space(.)="Usuarios"]')->length;
        $expectedUsersLinkCount = $account['role'] === 1 ? 1 : 0;
        $settingsLinkCount = $xpath->query('//*[@id="sidenavAccordion"]//a[@href="admin-modulos.php?section=settings"]')->length;
        $expectedSettingsLinkCount = $account['role'] === 1 ? 1 : 0;
        $adminModuleLinkCount = $xpath->query('//*[@id="sidenavAccordion"]//a[starts-with(@href, "admin-modulos.php?section=")]')->length;
        $expectedAdminModuleLinkCount = $account['role'] === 1 ? 12 : 0;

        if ($layoutCount !== 1 || $sidebarCount !== 1 || $contentCount !== 1 || $usersLinkCount !== $expectedUsersLinkCount || $settingsLinkCount !== $expectedSettingsLinkCount || $adminModuleLinkCount !== $expectedAdminModuleLinkCount) {
            throw new RuntimeException('La estructura de navegación no coincide para el rol ' . $account['role'] . '.');
        }

        ob_start();
        include __DIR__ . '/../menu.php';
        $storeMenu = ob_get_clean();
        $menuDom = new DOMDocument();
        $previousErrorMode = libxml_use_internal_errors(true);
        $menuDom->loadHTML('<!doctype html><html><body>' . $storeMenu . '</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);
        $menuXpath = new DOMXPath($menuDom);
        $panelLink = $menuXpath->query('//a[normalize-space(.)="Volver al panel"]')->item(0);
        $expectedPanel = $account['role'] === 1 ? 'usuarios.php' : 'carga-tienda-en-linea.php';

        if (!$panelLink || $panelLink->getAttribute('href') !== $expectedPanel) {
            throw new RuntimeException('Falta el enlace de regreso al panel para el rol ' . $account['role'] . '.');
        }

        fwrite(STDOUT, 'Rol ' . $account['role'] . ': sidebar y regreso desde tienda correctos.' . PHP_EOL);
    }
} finally {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    foreach ($accounts as $account) {
        $delete = $testPdo->prepare('DELETE FROM usuarios WHERE username = ?');
        $delete->execute([$account['username']]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}