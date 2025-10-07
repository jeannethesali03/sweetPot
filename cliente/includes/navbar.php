<?php
// Obtener información del carrito si está disponible
$carritoInfo = null;
if (isset($carritoModel) && isset($_SESSION['user_id'])) {
    try {
        $carritoInfo = $carritoModel->obtenerResumen($_SESSION['user_id']);
    } catch (Exception $e) {
        $carritoInfo = ['cantidad' => 0, 'total' => 0];
    }
} else {
    $carritoInfo = ['cantidad' => 0, 'total' => 0];
}

// Obtener página actual para navegación activa
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Navbar para clientes -->
<nav class="navbar navbar-expand-lg navbar-sweetpot sticky-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <img src="https://i.ibb.co/pjxGjNSR/Sweet-Pot-1.png" alt="Logo" width="30" height="30" class="d-inline-block align-text-top">
            SweetPot
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a style="color: white"
                        class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>"
                        href="dashboard.php">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a style="color: white"
                        class="nav-link <?php echo ($currentPage == 'productos.php') ? 'active' : ''; ?>"
                        href="productos.php">
                        <i class="fas fa-birthday-cake"></i> Productos
                    </a>
                </li>
                <li style="color: white" class="nav-item">
                    <a style="color: white"
                        class="nav-link <?php echo ($currentPage == 'mis-pedidos.php' || $currentPage == 'pedidos.php') ? 'active' : ''; ?>"
                        href="mis-pedidos.php">
                        <i class="fas fa-shopping-bag"></i> Mis Pedidos
                    </a>
                </li>
                <li class="nav-item">
                    <a style="color: white"
                        class="nav-link <?php echo ($currentPage == 'perfil.php') ? 'active' : ''; ?>"
                        href="perfil.php">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a style="color: white"
                        class="nav-link position-relative <?php echo ($currentPage == 'carrito.php') ? 'active' : ''; ?>"
                        href="carrito.php">
                        <i class="fas fa-shopping-cart"></i> Carrito
                        <?php if (isset($carritoInfo['cantidad']) && $carritoInfo['cantidad'] > 0): ?>
                            <span class="carrito-badge"><?php echo $carritoInfo['cantidad']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a style="color: white" class="nav-link dropdown-toggle" href="#" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a>
                        </li>
                        <li><a class="dropdown-item" href="mis-pedidos.php"><i class="fas fa-list me-2"></i>Mis
                                Pedidos</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../logout.php"
                                onclick="confirmarCerrarSesion(); return false;">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>