<?php
// Obtener el nombre del archivo actual para marcar como activo
$currentFile = basename($_SERVER['PHP_SELF']);

// Función para determinar si un enlace está activo
function isActive($page, $currentFile)
{
    return $page === $currentFile ? 'active' : '';
}
?>

<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block sidebar-sweetpot sidebar-sticky collapse" id="sidebar">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <img src="../assets/images/logo-sweetpot.png" alt="SweetPot" class="img-fluid mb-2"
                style="max-width: 80px;">
            <h6 class="text-sweetpot-brown">Panel Vendedor</h6>
            <small class="text-muted">Bienvenido/a <?php echo htmlspecialchars($_SESSION['nombre']); ?></small>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('dashboard.php', $currentFile); ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('productos.php', $currentFile); ?>" href="productos.php">
                    <i class="fas fa-birthday-cake me-2"></i>
                    Productos
                </a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link <?php echo isActive('inventario.php', $currentFile); ?>" href="inventario.php">
                    <i class="fas fa-boxes me-2"></i>
                    Inventario
                </a>
            </li> -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('pedidos.php', $currentFile); ?>" href="pedidos.php">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Pedidos
                </a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link <?php echo isActive('ventas.php', $currentPage); ?>" href="ventas.php">
                    <i class="fas fa-cash-register me-2"></i>
                    Ventas
                </a>
            </li> -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('reportes.php', $currentFile); ?>" href="reportes.php">
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
                <a class="nav-link" href="../logout.php" onclick="confirmarCerrarSesion(); return false;">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Navbar móvil para mostrar/ocultar sidebar -->
<nav class="navbar navbar-dark bg-dark d-md-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand mx-auto" href="dashboard.php">
            <i class="fas fa-birthday-cake me-2"></i>
            SweetPot - Vendedor
        </a>
    </div>
</nav>