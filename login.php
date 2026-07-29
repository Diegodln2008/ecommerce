<?php
$pageTitle = 'Login | Ecommerce';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['username'])) {
    header('Location: usuarios.php');
    exit();
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
require __DIR__ . '/templates/header.php';
?>
    <div class="auth-card">
        <div class="auth-logo">
            <h1>Bienvenido</h1>
            <p class="text-muted">Inicia sesión para continuar</p>
        </div>

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <form action="authenticate.php?action=login" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
        </form>

        <div class="mt-4 text-center">
            <span class="text-muted">¿No tienes cuenta?</span>
            <a href="register.php">Regístrate</a>
        </div>
    </div>

<?php require __DIR__ . '/templates/footer.php'; ?>
