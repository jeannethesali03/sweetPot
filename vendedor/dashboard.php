<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../models/Usuario.php';
require_once '../models/Producto.php';
require_once '../models/Pedido.php';
require_once '../models/Categoria.php';

// Verificar autenticación y rol de vendedor
$user = Auth::getUser();
if (!$user || $user['rol'] !== 'vendedor') {
    header('Location: ../acceso_denegado.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$productoModel = new Producto();
$pedidoModel = new Pedido();
$categoriaModel = new Categoria();

try {
    // Estadísticas básicas
    $totalProductos = $productoModel->contar();
    $totalCategorias = $categoriaModel->contar();

    // Productos con stock bajo
    $productosStockBajo = $productoModel->obtenerConStockBajo(10);

    // Productos más vendidos
    $productosMasVendidos = $productoModel->obtenerMasVendidos(5);

} catch (Exception $e) {
    $error = "Error al cargar datos del dashboard: " . $e->getMessage();
}

$pageTitle = "Dashboard Vendedor - SweetPot";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 mt-5 border-bottom">
                <h1 class="h2 text-gradient-sweetpot">
                    <i class="fas fa-user-tie me-2"></i>
                    Dashboard Vendedor
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sweetpot-secondary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        <button type="button" class="btn btn-sweetpot-primary btn-sm"
                            onclick="window.location.href='productos.php'">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </button>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Tarjetas de estadísticas principales -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-sweetpot bg-sweetpot-gradient text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $totalProductos ?? 0; ?></div>
                                    <div>Productos</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-birthday-cake fa-2x"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center justify-content-between bg-transparent border-0">
                            <a class="small text-white stretched-link text-decoration-none"
                                href="productos.php">Gestionar productos</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-sweetpot bg-sweetpot-gradient-dark text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $totalCategorias ?? 0; ?></div>
                                    <div>Categorías</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-tags fa-2x"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center justify-content-between bg-transparent border-0">
                            <a class="small text-white stretched-link text-decoration-none" href="">Ver
                                categorías</a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-sweetpot bg-sweetpot-gradient text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo count($productosStockBajo); ?></div>
                                    <div>Stock Bajo</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center justify-content-between bg-transparent border-0">
                            <a class="small text-white stretched-link text-decoration-none" href="productos.php">Ver
                                inventario</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-sweetpot bg-sweetpot-gradient-dark text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">Hoy</div>
                                    <div>Ventas</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-cash-register fa-2x"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center justify-content-between bg-transparent border-0">
                            <a class="small text-white stretched-link text-decoration-none" href="pedidos.php">Ver
                                ventas</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas de stock bajo -->
            <?php if (!empty($productosStockBajo)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ¡Atención! Productos con stock bajo
                            </h5>
                            <p>Los siguientes productos necesitan reposición urgente:</p>
                            <ul class="mb-0">
                                <?php foreach (array_slice($productosStockBajo, 0, 5) as $producto): ?>
                                    <li><strong><?php echo htmlspecialchars($producto['nombre']); ?></strong> - Stock:
                                        <?php echo $producto['stock']; ?>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (count($productosStockBajo) > 5): ?>
                                    <li><em>Y <?php echo count($productosStockBajo) - 5; ?> producto(s) más...</em></li>
                                <?php endif; ?>
                            </ul>
                            <hr>
                            <button class="btn btn-warning btn-sm" onclick="alertaStockBajo()">
                                <i class="fas fa-bell"></i> Ver todos
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Productos más vendidos -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-star me-2"></i>
                                Productos Más Vendidos
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($productosMasVendidos)): ?>
                                <?php foreach ($productosMasVendidos as $index => $producto): ?>
                                    <div
                                        class="d-flex justify-content-between align-items-center py-2 <?php echo $index !== count($productosMasVendidos) - 1 ? 'border-bottom' : ''; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <span
                                                    class="badge bg-sweetpot-pink rounded-pill"><?php echo $index + 1; ?></span>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><br>
                                                <small class="text-muted">$<?php echo number_format($producto['precio'], 2); ?>
                                                    | Stock: <?php echo $producto['stock']; ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span
                                                class="badge bg-success"><?php echo $producto['total_vendidos'] ?? 0; ?></span><br>
                                            <small class="text-muted">vendidos</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-bar fa-2x mb-2 opacity-50"></i>
                                    <p>No hay datos de ventas aún</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>
                                Acciones Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-sweetpot-primary" onclick="window.location.href='productos.php'">
                                    <i class="fas fa-plus me-2"></i>
                                    Agregar Nuevo Producto
                                </button>
                                <button class="btn btn-sweetpot-secondary"
                                    onclick="window.location.href='inventario.php'">
                                    <i class="fas fa-boxes me-2"></i>
                                    Actualizar Inventario
                                </button>
                                <button class="btn btn-outline-primary" onclick="window.location.href='pedidos.php'">
                                    <i class="fas fa-shopping-bag me-2"></i>
                                    Gestionar Pedidos
                                </button>
                                <button class="btn btn-outline-success" onclick="window.location.href='reportes.php'">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Ver Reportes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card card-sweetpot">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <i class="fas fa-clock text-sweetpot-pink fa-2x mb-2"></i>
                                    <h6>Horario de Atención</h6>
                                    <p class="text-muted small">Lun-Vie: 8:00-18:00<br>Sáb: 9:00-15:00</p>
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-phone text-sweetpot-pink fa-2x mb-2"></i>
                                    <h6>Contacto</h6>
                                    <p class="text-muted small">+52 123 456 7890<br>ventas@sweetpot.com</p>
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-truck text-sweetpot-pink fa-2x mb-2"></i>
                                    <h6>Entregas</h6>
                                    <p class="text-muted small">Dentro de la ciudad<br>24-48 horas</p>
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-heart text-sweetpot-pink fa-2x mb-2"></i>
                                    <h6>Compromiso</h6>
                                    <p class="text-muted small">Calidad artesanal<br>Ingredientes frescos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/vendedor-sweetalert.js"></script>

<script>
    // Datos para el dashboard
    const productosStockBajo = <?php echo json_encode($productosStockBajo ?? []); ?>;

    // Función para mostrar alerta de stock bajo
    function alertaStockBajo() {
        if (productosStockBajo.length > 0) {
            const productos = productosStockBajo.map(p => ({
                nombre: p.nombre,
                categoria: p.categoria || 'Sin categoría',
                stock: p.stock
            }));
            mostrarAlertaStockBajo(productos);
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Stock Suficiente',
                text: 'Todos los productos tienen stock adecuado',
                confirmButtonColor: '#ff6b9d'
            });
        }
    }

    // Mostrar bienvenida si es la primera visita
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (isset($_GET['welcome']) && $_GET['welcome'] === '1'): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Bienvenido/a <?php echo htmlspecialchars($_SESSION['nombre']); ?>!',
                text: 'Panel de vendedor cargado correctamente',
                confirmButtonColor: '#ff6b9d',
                timer: 3000
            });
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>