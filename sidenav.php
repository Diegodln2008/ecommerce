<?php
require 'dbcon.php';
$username = $_SESSION['username'];
$userRole = (int)($_SESSION['user_role'] ?? 0);
?>
<link rel="stylesheet" href="css/sidenav.css">
<script src="https://use.fontawesome.com/releases/v6.1.0/js/all.js" crossorigin="anonymous"></script>

    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Navbar Brand-->
        <a class="navbar-brand ps-3" href="<?php echo htmlspecialchars(authenticatedHomePath(), ENT_QUOTES, 'UTF-8'); ?>"><img style="width: 180px;" src="images/logo.png" alt=""></a>
        <!-- Sidebar Toggle-->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>

        <!-- Espacio entre el logo y el botón de salir -->
        <div class="d-flex justify-content-end w-100">
            <a style="margin-right: 15px;" class="btn btn-warning" href="logout.php">Salir <i class="bi bi-box-arrow-right"></i></a>
        </div>
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Principal</div>
                        <a class="nav-link" href="<?php echo htmlspecialchars(authenticatedHomePath(), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Inicio
                        </a>
                        <?php if ($userRole === 1): ?>
                            <a class="nav-link" href="usuarios.php">
                                <div class="sb-nav-link-icon"><i class="bi bi-person-fill"></i></div>
                                Usuarios
                            </a>
                            <span class="sb-sidenav-menu-heading">Administración</span>
                            <a class="nav-link" href="admin-modulos.php?section=settings">
                                <span class="sb-nav-link-icon"><i class="bi bi-gear-wide-connected"></i></span>
                                Configuraciones
                            </a>
                            <a class="nav-link" href="admin-modulos.php?section=marketing">
                                <span class="sb-nav-link-icon"><i class="bi bi-send-fill"></i></span>
                                Marketing
                            </a>
                            <a class="nav-link" href="admin-modulos.php?section=statistics">
                                <span class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></span>
                                Estadísticas
                            </a>
                            <span class="sb-sidenav-menu-heading">Catálogos</span>
                            <a class="nav-link" href="admin-modulos.php?section=inactive-products">Productos inactivos</a>
                            <a class="nav-link" href="admin-modulos.php?section=videos">Videos</a>
                            <a class="nav-link" href="admin-modulos.php?section=catalogs">Catálogos</a>
                            <span class="sb-sidenav-menu-heading">Panel de control</span>
                            <a class="nav-link" href="admin-modulos.php?section=categories">Categorías</a>
                            <a class="nav-link" href="admin-modulos.php?section=subcategories">Subcategorías</a>
                            <a class="nav-link" href="admin-modulos.php?section=industries">Industrias</a>
                        <?php endif; ?>
                        <?php if (in_array($userRole, [1, 2], true)): ?>
                            <div class="sb-sidenav-menu-heading">Tienda</div>
                            <a class="nav-link" href="compras-aprobadas.php">
                                <div class="sb-nav-link-icon"><i class="bi bi-cart-check"></i></div>
                                Compras
                            </a>
                            <a class="nav-link" href="carga-tienda-en-linea.php">
                                <div class="sb-nav-link-icon"><i class="bi bi-box-seam"></i></div>
                                Productos
                            </a>
                            <?php if ($userRole === 1): ?>
                                <a class="nav-link" href="admin-modulos.php?section=finished-orders">Compras finalizadas</a>
                                <a class="nav-link" href="admin-modulos.php?section=coupons">Cupones</a>
                                <a class="nav-link" href="admin-modulos.php?section=promotions">Promociones</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a class="nav-link" href="tienda-en-linea.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-shop"></i></div>
                            Ver tienda
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Usuario:</div>
                    <?php
                    if (isset($_SESSION['username'])) {
                        $registro_id = mysqli_real_escape_string($con, $_SESSION['username']);
                        $query = "SELECT * FROM usuarios WHERE username='$registro_id' ";
                        $query_run = mysqli_query($con, $query);

                        if (mysqli_num_rows($query_run) > 0) {
                            $registro = mysqli_fetch_array($query_run);
                    ?>
                            <p><?= $registro['nombre']; ?> <?= $registro['apellidopaterno']; ?> <?= $registro['apellidomaterno']; ?></p>

                    <?php
                        } else {
                            echo "<p>Error contacte a soporte</p>";
                        }
                    }
                    ?>
                </div>
            </nav>
        </div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/sidenav.js"></script>