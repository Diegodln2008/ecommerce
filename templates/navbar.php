<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$username = $_SESSION['username'] ?? 'Invitado';
$userRole = $_SESSION['user_role'] ?? '3';
$roleLabel = $userRole === '1' ? 'Administrador/a' : ($userRole === '2' ? 'Colaborador/a' : 'Cliente/a');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="usuarios.php">Mi Empresa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <span class="nav-link text-white">Usuario: <?php echo htmlspecialchars($username); ?></span>
                </li>
                <li class="nav-item">
                    <span class="nav-link text-white">Rol: <?php echo htmlspecialchars($roleLabel); ?></span>
                </li>
            </ul>
            <div class="d-flex">
                <a class="btn btn-outline-light btn-sm" href="logout.php">Cerrar sesión</a>
            </div>
        </div>
    </div>
</nav>
