<?php
$pageTitle = 'Registro | Ecommerce';
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
            <h1>Crear cuenta</h1>
            <p class="text-muted">Regístrate para acceder a tu cuenta</p>
        </div>

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <form action="authenticate.php?action=register" method="POST" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" required>
                </div>
                <div class="col-md-6">
                    <label for="apellidopaterno" class="form-label">Apellido paterno</label>
                    <input type="text" class="form-control" id="apellidopaterno" name="apellidopaterno" placeholder="Apellido paterno" required>
                </div>
                <div class="col-md-6">
                    <label for="apellidomaterno" class="form-label">Apellido materno</label>
                    <input type="text" class="form-control" id="apellidomaterno" name="apellidomaterno" placeholder="Apellido materno" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="correo@ejemplo.com" required>
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" minlength="8" required>
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repite la contraseña" minlength="8" required>
                </div>
            </div>

            <div class="mt-4 d-grid gap-2">
                <button type="submit" class="btn btn-primary">Registrarme</button>
                <a href="login.php" class="btn btn-outline-secondary">Ya tengo cuenta</a>
            </div>
        </form>
    </div>

<?php require __DIR__ . '/templates/footer.php'; ?>
