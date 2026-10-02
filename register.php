<?php
$pageTitle = 'Registro | Ecommerce';
require_once __DIR__ . '/includes/security.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['username'])) {
    header('Location: ' . authenticatedHomePath());
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
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
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}" title="Mínimo 8 caracteres, con minúscula, mayúscula y número" required>
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repite la contraseña" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}" title="Las contraseñas deben cumplir la política indicada" required>
                </div>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" value="1" id="accept_policy" name="accept_policy" required>
                <label class="form-check-label" for="accept_policy">He leído el <a href="aviso-privacidad.php" target="_blank" rel="noopener">Aviso de privacidad</a> y acepto los <a href="terminos-condiciones.php" target="_blank" rel="noopener">Términos y condiciones</a>.</label>
            </div>

            <div class="mt-4 d-grid gap-2">
                <button type="submit" class="btn btn-primary">Registrarme</button>
                <a href="login.php" class="btn btn-outline-secondary">Ya tengo cuenta</a>
            </div>
        </form>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmationInput = document.getElementById('confirm_password');
        const validatePasswords = () => {
            const meetsPolicy = value => value.length >= 8 && /[a-z]/.test(value) && /[A-Z]/.test(value) && /[0-9]/.test(value);
            passwordInput.setCustomValidity(meetsPolicy(passwordInput.value) ? '' : 'Usa al menos 8 caracteres, una minúscula, una mayúscula y un número.');
            confirmationInput.setCustomValidity(confirmationInput.value === passwordInput.value ? '' : 'Las contraseñas no coinciden.');
        };
        passwordInput.addEventListener('input', validatePasswords);
        confirmationInput.addEventListener('input', validatePasswords);
    </script>

<?php require __DIR__ . '/templates/footer.php'; ?>
