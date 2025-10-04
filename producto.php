<?php
// Iniciar sesión antes que cualquier output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/Auth.php';

// Verificar que se proporcionó un ID de producto
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: cliente/productos.php');
    exit;
}

// Inicializar variables
$producto = null;
$productos_relacionados = [];
$error_mensaje = null;

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener información del producto con categoría
    $stmt = $conn->prepare("
        SELECT p.*, c.nombre as categoria_nombre 
        FROM productos p 
        INNER JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.id = :id AND p.estado = 'activo'
    ");
    $stmt->execute([':id' => $_GET['id']]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new Exception('Producto no encontrado o no disponible');
    }

    // Obtener productos relacionados de la misma categoría
    $stmt = $conn->prepare("
        SELECT p.* FROM productos p 
        WHERE p.categoria_id = :categoria_id 
        AND p.id != :producto_id 
        AND p.estado = 'activo' 
        ORDER BY RAND() 
        LIMIT 4
    ");
    $stmt->execute([
        ':categoria_id' => $producto['categoria_id'],
        ':producto_id' => $_GET['id']
    ]);
    $productos_relacionados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_mensaje = $e->getMessage();
}

$user_logged_in = Auth::isLoggedIn();
$user_data = Auth::getUser();
$is_cliente = $user_logged_in && $user_data && $user_data['rol'] === 'cliente';

$pageTitle = ($producto ? $producto['nombre'] . " - SweetPot" : "Producto - SweetPot");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        :root {
            --sweetpot-brown: #8b4513;
            --sweetpot-light-brown: #deb887;
            --sweetpot-pink: #ffc0cb;
            --sweetpot-cream: #fff8dc;
        }

        body {
            background: linear-gradient(135deg, var(--sweetpot-cream) 0%, #fff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .product-hero {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.1);
            overflow: hidden;
            margin: 2rem 0;
        }

        .product-image {
            height: 400px;
            background: var(--sweetpot-cream);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-image .placeholder {
            font-size: 4rem;
            color: var(--sweetpot-brown);
            opacity: 0.3;
        }

        .btn-sweetpot {
            background: var(--sweetpot-brown);
            border-color: var(--sweetpot-brown);
            color: white;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-sweetpot:hover {
            background: #654321;
            border-color: #654321;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }

        .price-tag {
            background: var(--sweetpot-brown);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: bold;
            display: inline-block;
            margin: 1rem 0;
        }

        .stock-info {
            background: var(--sweetpot-cream);
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin: 0.5rem 0;
        }

        .category-badge {
            background: var(--sweetpot-pink);
            color: var(--sweetpot-brown);
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .navbar-brand {
            color: var(--sweetpot-brown) !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .alert-unavailable {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <?php if ($user_logged_in): ?>
        <?php include 'cliente/includes/navbar.php'; ?>
    <?php else: ?>
        <!-- Navbar para usuarios no logueados -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-birthday-cake me-2"></i>
                    SweetPot
                </a>
                <div class="navbar-nav ms-auto">
                    <a href="login.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </a>
                    <a href="registro.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </a>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <div class="container my-4">
        <?php if (isset($error_mensaje)): ?>
            <div class="alert alert-unavailable text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_mensaje; ?>
            </div>

            <!-- Botón para volver cuando hay error -->
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Catálogo
                </a>
            </div>
        <?php else: ?>

            <div class="product-hero">
                <div class="row g-0">
                    <!-- Imagen del producto -->
                    <div class="col-md-6">
                        <div class="product-image">
                            <?php if (!empty($producto['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($producto['imagen']); ?>"
                                    alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="placeholder" style="display: none;">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                            <?php else: ?>
                                <div class="placeholder">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Información del producto -->
                    <div class="col-md-6">
                        <div class="p-4">
                            <!-- Categoría -->
                            <div class="mb-3">
                                <span class="category-badge">
                                    <i class="fas fa-tag me-1"></i>
                                    <?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?>
                                </span>
                            </div>

                            <!-- Nombre del producto -->
                            <h1 class="mb-3" style="color: var(--sweetpot-brown);">
                                <?php echo htmlspecialchars($producto['nombre']); ?>
                            </h1>

                            <!-- Descripción -->
                            <?php if (!empty($producto['descripcion'])): ?>
                                <p class="text-muted mb-4" style="font-size: 1.1rem;">
                                    <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Precio -->
                            <div class="price-tag">
                                <i class="fas fa-dollar-sign"></i>
                                <?php echo number_format($producto['precio'], 2); ?>
                            </div>

                            <!-- Stock -->
                            <div class="stock-info">
                                <i class="fas fa-boxes me-2"></i>
                                <strong>Disponibles:</strong> <?php echo $producto['stock']; ?> unidades
                            </div>

                            <!-- Código del producto -->
                            <?php if (!empty($producto['codigo_producto'])): ?>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-barcode me-1"></i>
                                        Código: <?php echo htmlspecialchars($producto['codigo_producto']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <!-- Botones de acción -->
                            <div class="mt-4">
                                <?php if (isset($error_mensaje)): ?>
                                    <button class="btn btn-secondary btn-lg" disabled>
                                        <i class="fas fa-ban me-2"></i>
                                        No Disponible
                                    </button>
                                <?php elseif ($producto['stock'] <= 0): ?>
                                    <button class="btn btn-warning btn-lg" disabled>
                                        <i class="fas fa-times-circle me-2"></i>
                                        Sin Stock
                                    </button>
                                <?php else: ?>
                                    <?php if ($is_cliente): ?>
                                        <button class="btn btn-sweetpot btn-lg me-3"
                                            onclick="agregarAlCarrito(<?php echo $producto['id']; ?>)">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            Agregar al Carrito
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-sweetpot btn-lg me-3">
                                            <i class="fas fa-sign-in-alt me-2"></i>
                                            Iniciar Sesión para Comprar
                                        </a>
                                    <?php endif; ?>

                                    <button class="btn btn-outline-secondary btn-lg" onclick="compartirProducto()">
                                        <i class="fas fa-share-alt me-2"></i>
                                        Compartir
                                    </button>
                                <?php endif; ?>
                            </div>

                            <!-- Información adicional -->
                            <div class="mt-4 pt-4 border-top">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <i class="fas fa-truck text-success fs-3"></i>
                                        <div class="small mt-2">Entrega<br>Rápida</div>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-heart text-danger fs-3"></i>
                                        <div class="small mt-2">Hecho con<br>Amor</div>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-award text-warning fs-3"></i>
                                        <div class="small mt-2">Calidad<br>Premium</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón para volver -->
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Catálogo
                </a>
            </div>

        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function agregarAlCarrito(productoId) {
            // Mostrar loading
            Swal.fire({
                title: 'Agregando al carrito...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar petición AJAX
            fetch('cliente/ajax/agregar-carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    producto_id: productoId,
                    cantidad: 1
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Agregado al carrito!',
                            text: 'El producto se agregó correctamente a tu carrito',
                            showConfirmButton: true,
                            showCancelButton: true,
                            confirmButtonText: 'Ver Carrito',
                            cancelButtonText: 'Seguir Comprando',
                            confirmButtonColor: '#8b4513'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'cliente/carrito.php';
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo agregar el producto al carrito'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión. Inténtalo de nuevo.'
                    });
                });
        }

        function compartirProducto() {
            const url = window.location.href;
            const titulo = '<?php echo addslashes($producto['nombre']); ?>';
            const texto = `¡Mira este delicioso ${titulo} de SweetPot! ${url}`;

            if (navigator.share) {
                navigator.share({
                    title: titulo,
                    text: texto,
                    url: url
                });
            } else {
                // Fallback: copiar al portapapeles
                navigator.clipboard.writeText(url).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Enlace copiado!',
                        text: 'El enlace del producto se copió al portapapeles',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
            }
        }
    </script>
</body>

</html>