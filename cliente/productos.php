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

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 12;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';

try {
    // Construir filtros - usar la misma lógica que en admin
    $filtros = ['limit' => $limit, 'page' => $page];

    if (!empty($search)) {
        $filtros['search'] = $search;
    }
    if (!empty($categoria)) {
        $filtros['categoria'] = $categoria;
    }

    // Solo mostrar productos activos para clientes
    $filtros['estado'] = 'activo';

    // Obtener productos
    $result = $productoModel->listar($filtros);
    $productos = $result['data'] ?? [];
    $totalPages = $result['totalPages'] ?? 1;
    $currentPage = $result['currentPage'] ?? 1;
    $totalProductos = $result['total'] ?? 0;

    // Obtener categorías para el filtro
    $categorias = $categoriaModel->listar(['estado' => 'activo'])['data'] ?? [];

    // Obtener información del carrito
    $carritoInfo = $carritoModel->obtenerResumen($_SESSION['user_id']);

} catch (Exception $e) {
    $error = "Error al cargar productos: " . $e->getMessage();
    $productos = [];
    $totalPages = 1;
    $currentPage = 1;
    $totalProductos = 0;
    $carritoInfo = ['total_items' => 0, 'total' => 0];
}

$pageTitle = "Productos - SweetPot";
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

    <!-- Header de productos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="text-gradient-sweetpot mb-2">
                        <i class="fas fa-birthday-cake me-2"></i>
                        Nuestros Productos
                    </h1>
                    <p class="text-muted">Descubre nuestra deliciosa repostería artesanal</p>
                </div>
                <div class="text-end">
                    <small class="text-muted">
                        <?php echo $totalProductos; ?> producto<?php echo $totalProductos != 1 ? 's' : ''; ?>
                        disponible<?php echo $totalProductos != 1 ? 's' : ''; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar de filtros -->
        <div class="col-lg-3 mb-4">
            <div class="card card-sweetpot sticky-top" style="top: 100px;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="productos.php" id="filtrosForm">
                        <!-- Búsqueda -->
                        <div class="mb-3">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="<?php echo htmlspecialchars($search); ?>" placeholder="Nombre del producto...">
                        </div>

                        <!-- Categorías -->
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($categoria == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sweetpot-primary">
                                <i class="fas fa-search"></i> Aplicar Filtros
                            </button>
                            <a href="productos.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Grid de productos -->
        <div class="col-lg-9">
            <?php if (!empty($productos)): ?>
                <div class="row">
                    <?php foreach ($productos as $producto): ?>
                        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                            <div class="card card-sweetpot h-100 producto-card">
                                <!-- Imagen -->
                                <div class="position-relative">
                                    <?php if (!empty($producto['imagen'])): ?>
                                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" class="card-img-top"
                                            alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                            style="height: 250px; object-fit: cover;"
                                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjUwIiBoZWlnaHQ9IjI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImEiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiNmZjZiOWQiLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNmZmVhYTciLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iMjUwIiBoZWlnaHQ9IjI1MCIgZmlsbD0idXJsKCNhKSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iNDAiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+🧁</dGV4dD48L3N2Zz4=';">
                                    <?php else: ?>
                                        <div class="card-img-top bg-sweetpot-gradient d-flex align-items-center justify-content-center"
                                            style="height: 250px;">
                                            <i class="fas fa-birthday-cake fa-4x text-white opacity-75"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Stock bajo -->
                                    <?php if ($producto['stock'] <= 5 && $producto['stock'] > 0): ?>
                                        <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">
                                            ¡Últimas <?php echo $producto['stock']; ?>!
                                        </span>
                                    <?php elseif ($producto['stock'] == 0): ?>
                                        <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                                            Agotado
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Contenido -->
                                <div class="card-body d-flex flex-column">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title text-sweetpot-brown mb-2">
                                            <?php echo htmlspecialchars($producto['nombre']); ?>
                                        </h5>

                                        <?php if (!empty($producto['descripcion'])): ?>
                                            <p class="card-text text-muted small mb-3">
                                                <?php echo htmlspecialchars(substr($producto['descripcion'], 0, 100)) . '...'; ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?>
                                            </span>
                                            <?php if ($producto['stock'] > 0): ?>
                                                <span class="badge bg-success ms-1">Disponible</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger ms-1">Agotado</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Precio y acciones -->
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="price-section">
                                                <div class="h4 text-sweetpot-pink mb-0">
                                                    $<?php echo number_format($producto['precio'], 2); ?>
                                                </div>
                                                <small class="text-muted">Precio por unidad</small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    <i class="fas fa-boxes me-1"></i>
                                                    Stock: <?php echo $producto['stock']; ?>
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Botones de acción -->
                                        <div class="d-grid gap-2">
                                            <?php if ($producto['stock'] > 0): ?>
                                                <button type="button" class="btn btn-sweetpot-primary"
                                                    onclick="agregarAlCarrito(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo $producto['precio']; ?>)">
                                                    <i class="fas fa-cart-plus me-2"></i>
                                                    Agregar al Carrito
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-secondary" disabled>
                                                    <i class="fas fa-times me-2"></i>
                                                    No Disponible
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-4"></i>
                    <h3 class="text-muted mb-3">No encontramos productos</h3>
                    <p class="text-muted mb-4">
                        <?php if (!empty($search) || !empty($categoria)): ?>
                            No hay productos que coincidan con tus filtros de búsqueda.
                        <?php else: ?>
                            Actualmente no hay productos disponibles.
                        <?php endif; ?>
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <?php if (!empty($search) || !empty($categoria)): ?>
                            <a href="productos.php" class="btn btn-sweetpot-secondary">
                                <i class="fas fa-times"></i> Limpiar Filtros
                            </a>
                        <?php endif; ?>
                        <a href="dashboard.php" class="btn btn-sweetpot-primary">
                            <i class="fas fa-home"></i> Volver al Inicio
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginación de productos" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage - 1); ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage + 1); ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/cliente-sweetalert.js"></script>

<script>
    // Función para agregar al carrito
    function agregarAlCarrito(id, nombre, precio) {
        Swal.fire({
            title: 'Agregar al Carrito',
            html: `
        <div class="text-center">
            <h5>${nombre}</h5>
            <p class="text-sweetpot-pink fs-4">$${precio.toFixed(2)}</p>
            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad:</label>
                <input type="number" id="cantidad" class="form-control text-center" value="1" min="1" max="10">
            </div>
        </div>
    `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-cart-plus"></i> Agregar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ff6b9d',
            preConfirm: () => {
                const cantidad = document.getElementById('cantidad').value;
                if (!cantidad || cantidad < 1) {
                    Swal.showValidationMessage('Por favor ingresa una cantidad válida');
                    return false;
                }
                return cantidad;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const cantidad = result.value;

                fetch('ajax/agregar-carrito.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        producto_id: id,
                        cantidad: cantidad
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Agregado al carrito!',
                                text: `${cantidad} ${nombre} agregado${cantidad > 1 ? 's' : ''} al carrito`,
                                confirmButtonColor: '#ff6b9d',
                                timer: 2000
                            });
                            actualizarContadorCarrito();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo agregar el producto al carrito',
                                confirmButtonColor: '#ff6b9d'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor',
                            confirmButtonColor: '#ff6b9d'
                        });
                    });
            }
        });
    }

    // Función para actualizar contador del carrito
    function actualizarContadorCarrito() {
        fetch('ajax/obtener-carrito-resumen.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('.carrito-badge');
                    if (data.data.cantidad > 0) {
                        if (badge) {
                            badge.textContent = data.data.cantidad;
                        } else {
                            const cartLink = document.querySelector('a[href="carrito.php"]');
                            if (cartLink) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'carrito-badge';
                                newBadge.textContent = data.data.cantidad;
                                cartLink.appendChild(newBadge);
                            }
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }
            })
            .catch(error => console.error('Error al actualizar carrito:', error));
    }
</script>

<style>
    .producto-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .producto-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .carrito-badge {
        position: absolute;
        top: -5px;
        right: -10px;
        background: #ff6b9d;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        min-width: 20px;
        text-align: center;
    }
</style>

<?php include '../includes/footer.php'; ?>