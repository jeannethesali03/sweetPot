<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../models/Usuario.php';
require_once '../models/Producto.php';
require_once '../models/Categoria.php';
require_once '../models/Carrito.php';

// Verificar autenticación y rol de cliente
Auth::requireRole('cliente');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$productoModel = new Producto();
$categoriaModel = new Categoria();
$carritoModel = new Carrito();

try {
    // Obtener productos destacados (más vendidos o aleatorios)
    $productosDestacados = $productoModel->obtenerMasVendidos(6);

    // Si no hay productos vendidos, obtener productos aleatorios
    if (empty($productosDestacados)) {
        $productosDestacados = $productoModel->listar(['limit' => 6])['data'] ?? [];
    }

    // Obtener categorías activas (normalizar estructura)
    $categorias = $categoriaModel->listar(['estado' => 'activo', 'limit' => 8]);
    if (is_array($categorias) && isset($categorias['data'])) {
        $categorias = $categorias['data'];
    }
    if (!is_array($categorias))
        $categorias = [];
    $categorias = array_values(array_filter($categorias, function ($c) {
        return is_array($c) && isset($c['id']) && isset($c['nombre']); }));

    // Obtener información del carrito del usuario
    $carritoInfo = $carritoModel->obtenerResumen($_SESSION['user_id']);

} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

$pageTitle = "Mi Tienda - SweetPot";
include '../includes/header.php';
include 'includes/navbar.php';
?>



<div class="container my-4">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card card-sweetpot bg-sweetpot-gradient text-white text-center py-5">
                <div class="card-body">
                    <h1 class="display-4 mb-3">
                        ¡Bienvenido/a <?php echo htmlspecialchars($_SESSION['nombre']); ?>!
                    </h1>
                    <p class="lead mb-4">
                        Descubre nuestros deliciosos productos de repostería artesanal
                    </p>
                    <a href="productos.php" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>
                        Explorar Productos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Categorías -->
    <?php if (!empty($categorias)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-gradient-sweetpot mb-4">
                    <i class="fas fa-tags me-2"></i>
                    Nuestras Categorías
                </h2>
            </div>
            <?php foreach ($categorias as $categoria): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                    <div class="card card-sweetpot hover-sweetpot h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-birthday-cake text-sweetpot-pink fa-3x"></i>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($categoria['nombre']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars($categoria['descripcion'] ?? 'Deliciosos productos'); ?>
                            </p>
                            <a href="productos.php?categoria=<?php echo $categoria['id']; ?>"
                                class="btn btn-sweetpot-secondary btn-sm">
                                <i class="fas fa-eye"></i> Ver Productos
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Productos Destacados -->
    <?php if (!empty($productosDestacados)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-gradient-sweetpot mb-4">
                    <i class="fas fa-star me-2"></i>
                    Productos Destacados
                </h2>
            </div>
            <?php foreach ($productosDestacados as $producto): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card producto-card h-100">
                        <div class="position-relative">
                            <?php
                            $imagenUrl = getProductImageUrl($producto['imagen']);
                            echo "<img src=\"$imagenUrl\" class=\"card-img-top\" alt=\"" . htmlspecialchars($producto['nombre']) . "\" onclick=\"mostrarImagenProducto('$imagenUrl', '" . htmlspecialchars($producto['nombre']) . "')\">";
                            ?>

                            <?php if (isset($producto['total_vendidos']) && $producto['total_vendidos'] > 0): ?>
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-fire"></i> Más Vendido
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo htmlspecialchars(substr($producto['descripcion'] ?? '', 0, 100)); ?>
                                <?php if (strlen($producto['descripcion'] ?? '') > 100): ?>...<?php endif; ?>
                            </p>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="producto-precio">$<?php echo number_format($producto['precio'], 2); ?></span>
                                    <small class="text-muted">
                                        <i class="fas fa-boxes"></i>
                                        <?php echo $producto['stock']; ?> disponibles
                                    </small>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <?php if ($producto['stock'] > 0): ?>
                                    <button class="btn btn-sweetpot-primary w-100"
                                        onclick="agregarAlCarrito(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo $producto['precio']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Agregar al Carrito
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-times"></i> Sin Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Sección de información adicional -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card card-sweetpot text-center h-100">
                <div class="card-body">
                    <i class="fas fa-truck text-sweetpot-pink fa-3x mb-3"></i>
                    <h5>Entrega Rápida</h5>
                    <p class="text-muted">Recibe tus productos frescos en tiempo récord</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card card-sweetpot text-center h-100">
                <div class="card-body">
                    <i class="fas fa-heart text-sweetpot-pink fa-3x mb-3"></i>
                    <h5>Hechos con Amor</h5>
                    <p class="text-muted">Cada producto es elaborado con ingredientes de calidad</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card card-sweetpot text-center h-100">
                <div class="card-body">
                    <i class="fas fa-shield-alt text-sweetpot-pink fa-3x mb-3"></i>
                    <h5>Calidad Garantizada</h5>
                    <p class="text-muted">Productos frescos y de la más alta calidad</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/cliente-sweetalert.js"></script>

<script>
    // Función para agregar al carrito
    function agregarAlCarrito(productoId, nombreProducto, precio) {
        confirmarAgregarAlCarrito(nombreProducto, precio).then((result) => {
            if (result.isConfirmed) {
                mostrarCargando('Agregando al carrito...');

                // Hacer petición AJAX
                fetch('ajax/agregar-carrito.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        producto_id: productoId,
                        cantidad: 1
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            mostrarProductoAgregado(nombreProducto);
                            // Actualizar contador del carrito si existe
                            actualizarContadorCarrito();
                        } else {
                            mostrarError('Error', data.message || 'No se pudo agregar el producto al carrito');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        mostrarErrorConexion();
                    });
            }
        });
    }

    // Función para actualizar contador del carrito
    function actualizarContadorCarrito() {
        fetch('../cliente/ajax/obtener-carrito.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cantidad > 0) {
                    const badge = document.querySelector('.carrito-badge');
                    if (badge) {
                        badge.textContent = data.cantidad;
                    } else {
                        // Crear badge si no existe
                        const carritoLink = document.querySelector('.nav-link[href="carrito.php"]');
                        if (carritoLink) {
                            carritoLink.insertAdjacentHTML('beforeend', `<span class="carrito-badge">${data.cantidad}</span>`);
                        }
                    }
                }
            })
            .catch(error => console.error('Error actualizando carrito:', error));
    }

    // Mostrar bienvenida si es la primera visita
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (isset($_GET['welcome']) && $_GET['welcome'] === '1'): ?>
            mostrarBienvenida('<?php echo htmlspecialchars($_SESSION['nombre']); ?>');
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>