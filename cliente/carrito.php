<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../models/Usuario.php';
require_once '../models/Producto.php';
require_once '../models/Carrito.php';

// Verificar autenticación y rol de cliente
Auth::requireRole('cliente');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$carritoModel = new Carrito();

try {
    // Obtener items del carrito
    $items = $carritoModel->obtenerItems($_SESSION['user_id']);
    $resumen = $carritoModel->obtenerResumen($_SESSION['user_id']);

} catch (Exception $e) {
    $error = "Error al cargar carrito: " . $e->getMessage();
    $items = [];
    $resumen = ['total_items' => 0, 'subtotal' => 0, 'total' => 0];
}

$pageTitle = "Mi Carrito - SweetPot";
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

    <!-- Header del carrito -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="text-gradient-sweetpot mb-2">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Mi Carrito
                    </h1>
                    <p class="text-muted">Revisa y confirma tus productos</p>
                </div>
                <div class="text-end">
                    <small class="text-muted">
                        <?php echo $resumen['total_items']; ?>
                        producto<?php echo $resumen['total_items'] != 1 ? 's' : ''; ?>
                        en el carrito
                    </small>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($items)): ?>
        <div class="row">
            <!-- Items del carrito -->
            <div class="col-lg-8">
                <div class="card card-sweetpot">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Productos en tu carrito
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex align-items-center p-3 border-bottom"
                                id="item-<?php echo $item['producto_id']; ?>">
                                <!-- Imagen del producto -->
                                <div class="me-3">
                                    <?php if (!empty($item['imagen'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['imagen']); ?>"
                                            alt="<?php echo htmlspecialchars($item['nombre']); ?>"
                                            style="width: 80px; height: 80px; object-fit: cover;" class="rounded">
                                    <?php else: ?>
                                        <div class="bg-sweetpot-gradient d-flex align-items-center justify-content-center rounded"
                                            style="width: 80px; height: 80px;">
                                            <i class="fas fa-birthday-cake fa-2x text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Información del producto -->
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-sweetpot-brown"><?php echo htmlspecialchars($item['nombre']); ?></h6>
                                    <p class="text-muted small mb-1"><?php echo htmlspecialchars($item['categoria_nombre']); ?>
                                    </p>
                                    <div class="d-flex align-items-center">
                                        <span
                                            class="text-sweetpot-pink fw-bold">$<?php echo number_format($item['precio'], 2); ?></span>
                                        <span class="text-muted small ms-2">por unidad</span>
                                    </div>
                                </div>

                                <!-- Controles de cantidad -->
                                <div class="d-flex align-items-center me-3">
                                    <button class="btn btn-outline-secondary btn-sm"
                                        onclick="cambiarCantidad(<?php echo $item['producto_id']; ?>, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="mx-3 fw-bold" id="cantidad-<?php echo $item['producto_id']; ?>">
                                        <?php echo $item['cantidad']; ?>
                                    </span>
                                    <button class="btn btn-outline-secondary btn-sm"
                                        onclick="cambiarCantidad(<?php echo $item['producto_id']; ?>, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>

                                <!-- Subtotal y eliminar -->
                                <div class="text-end">
                                    <div class="fw-bold text-sweetpot-brown mb-2"
                                        id="subtotal-<?php echo $item['producto_id']; ?>">
                                        $<?php echo number_format($item['subtotal'], 2); ?>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm"
                                        onclick="eliminarDelCarrito(<?php echo $item['producto_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="mt-3 d-flex gap-2">
                    <a href="productos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Seguir Comprando
                    </a>
                    <button class="btn btn-outline-warning" onclick="vaciarCarrito()">
                        <i class="fas fa-trash me-2"></i>
                        Vaciar Carrito
                    </button>
                </div>
            </div>

            <!-- Resumen del carrito -->
            <div class="col-lg-4">
                <div class="card card-sweetpot sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calculator me-2"></i>
                            Resumen del Pedido
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="resumen-subtotal">$<?php echo number_format($resumen['subtotal'], 2); ?></span>
                        </div>
                        <?php if ($resumen['impuestos'] > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Impuestos:</span>
                                <span id="resumen-impuestos">$<?php echo number_format($resumen['impuestos'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($resumen['descuento'] > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Descuento:</span>
                                <span id="resumen-descuento">-$<?php echo number_format($resumen['descuento'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-sweetpot-pink fs-5" id="resumen-total">
                                $<?php echo number_format($resumen['total'], 2); ?>
                            </strong>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-sweetpot-primary btn-lg" onclick="procederPago()">
                                <i class="fas fa-credit-card me-2"></i>
                                Proceder al Pago
                            </button>
                        </div>

                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Compra 100% segura
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Carrito vacío -->
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
            <h3 class="text-muted mb-3">Tu carrito está vacío</h3>
            <p class="text-muted mb-4">¡Explora nuestros deliciosos productos y agrega algunos a tu carrito!</p>
            <a href="productos.php" class="btn btn-sweetpot-primary">
                <i class="fas fa-birthday-cake me-2"></i>
                Ver Productos
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/cliente-sweetalert.js"></script>

<script>
    // Función para cambiar cantidad
    function cambiarCantidad(productoId, cambio) {
        const cantidadElement = document.getElementById(`cantidad-${productoId}`);
        const cantidadActual = parseInt(cantidadElement.textContent);
        const nuevaCantidad = cantidadActual + cambio;

        if (nuevaCantidad <= 0) {
            eliminarDelCarrito(productoId);
            return;
        }

        fetch('ajax/actualizar-carrito.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                producto_id: productoId,
                cantidad: nuevaCantidad
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Recargar para actualizar totales
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo actualizar la cantidad'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor'
                });
            });
    }

    // Función para eliminar del carrito
    function eliminarDelCarrito(productoId) {
        Swal.fire({
            title: '¿Eliminar producto?',
            text: '¿Estás seguro de que quieres eliminar este producto del carrito?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/eliminar-carrito.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        producto_id: productoId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo eliminar el producto'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor'
                        });
                    });
            }
        });
    }

    // Función para vaciar carrito
    function vaciarCarrito() {
        Swal.fire({
            title: '¿Vaciar carrito?',
            text: '¿Estás seguro de que quieres eliminar todos los productos del carrito?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, vaciar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/vaciar-carrito.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo vaciar el carrito'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor'
                        });
                    });
            }
        });
    }

    // Función para proceder al pago
    function procederPago() {
        window.location.href = 'pago.php';
    }
</script>

<style>
    .producto-imagen {
        transition: transform 0.3s ease;
    }

    .producto-imagen:hover {
        transform: scale(1.05);
    }
</style>

<?php include '../includes/footer.php'; ?>