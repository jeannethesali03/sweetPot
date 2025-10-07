<?php
// Determinar si el usuario está en admin o vendedor basado en la sesión
$userRole = $_SESSION['rol'] ?? 'admin';
?>

<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block sidebar-sweetpot sidebar-sticky collapse" id="sidebar">
    <div class="pt-3">
        <div class="text-center mb-4">
            <img src="../assets/images/logo-sweetpot.png" alt="SweetPot" class="img-fluid mb-2"
                style="max-width: 80px;">
            <h6 class="text-sweetpot-brown">Panel <?php echo ucfirst($userRole); ?></h6>
            <small class="text-muted">Bienvenido/a <?php echo htmlspecialchars($_SESSION['nombre']); ?></small>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : ''; ?>"
                    href="productos.php">
                    <i class="fas fa-birthday-cake me-2"></i>
                    Productos
                </a>
            </li>

            <?php if ($userRole === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>"
                        href="categorias.php">
                        <i class="fas fa-tags me-2"></i>
                        Categorías
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>"
                        href="usuarios.php">
                        <i class="fas fa-users me-2"></i>
                        Usuarios
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>"
                    href="pedidos.php">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>"
                    href="reportes.php">
                    <i class="fas fa-chart-line me-2"></i>
                    Reportes
                </a>
            </li>

            <hr>
            <!-- <li class="nav-item">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-2"></i>
                    Ir al Sitio
                </a>
            </li> -->
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="confirmarCerrarSesion(); return false;">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Top navbar para móviles -->
<nav class="navbar navbar-dark bg-dark sticky-top d-md-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand mx-auto" href="dashboard.php">
            <i class="fas fa-birthday-cake me-2"></i>
            SweetPot
        </a>
    </div>
</nav>