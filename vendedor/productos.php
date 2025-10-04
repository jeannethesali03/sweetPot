<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';

// Verificar autenticación y rol de vendedor
Auth::requireRole('vendedor');

$db = new Database();
$conn = $db->getConnection();

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 12;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'nombre_asc';

try {
    // Construir consulta base
    $whereConditions = ["p.estado = 'activo'"];
    $params = [];
    
    // Agregar filtro de búsqueda
    if (!empty($search)) {
        $whereConditions[] = "(p.nombre LIKE :search OR p.descripcion LIKE :search OR p.codigo_producto LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Agregar filtro de categoría
    if (!empty($categoria)) {
        $whereConditions[] = "p.categoria_id = :categoria";
        $params[':categoria'] = $categoria;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Determinar orden
    $orderBy = match($orden) {
        'nombre_desc' => 'p.nombre DESC',
        'precio_asc' => 'p.precio ASC',
        'precio_desc' => 'p.precio DESC',
        'stock_asc' => 'p.stock ASC',
        'stock_desc' => 'p.stock DESC',
        'fecha_desc' => 'p.fecha_creacion DESC',
        default => 'p.nombre ASC'
    };
    
    // Contar total de productos
    $countQuery = "SELECT COUNT(*) FROM productos p 
                   INNER JOIN categorias c ON p.categoria_id = c.id 
                   WHERE $whereClause";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $totalProductos = $countStmt->fetchColumn();
    
    // Calcular paginación
    $totalPages = ceil($totalProductos / $limit);
    $offset = ($page - 1) * $limit;
    
    // Obtener productos
    $query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p 
              INNER JOIN categorias c ON p.categoria_id = c.id 
              WHERE $whereClause 
              ORDER BY $orderBy 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener categorías para el filtro
    $categoriasQuery = "SELECT id, nombre FROM categorias WHERE estado = 'activo' ORDER BY nombre";
    $categoriasStmt = $conn->prepare($categoriasQuery);
    $categoriasStmt->execute();
    $categorias = $categoriasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas rápidas
    $statsQuery = "SELECT 
                    COUNT(*) as total_productos,
                    SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) as productos_bajo_stock,
                    AVG(precio) as precio_promedio
                   FROM productos WHERE estado = 'activo'";
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al cargar productos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - SweetPot Vendedor</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- SweetPot CSS -->
    <link href="../assets/css/sweetpot.css" rel="stylesheet">
    
    <style>
        .product-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 200px;
            overflow: hidden;
            border-radius: 10px 10px 0 0;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .price-tag {
            background: var(--sweetpot-gold);
            color: var(--sweetpot-brown);
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 20px;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--sweetpot-cream), #fff);
            border-left: 4px solid var(--sweetpot-brown);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-birthday-cake me-2"></i>
                        Catálogo de Productos
                    </h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas rápidas -->
                <?php if (isset($stats)): ?>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-cubes fa-2x text-primary mb-2"></i>
                                    <h3><?php echo number_format($stats['total_productos']); ?></h3>
                                    <p class="text-muted mb-0">Total Productos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                                    <h3><?php echo number_format($stats['productos_bajo_stock']); ?></h3>
                                    <p class="text-muted mb-0">Bajo Stock</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                                    <h3>$<?php echo number_format($stats['precio_promedio'], 2); ?></h3>
                                    <p class="text-muted mb-0">Precio Promedio</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filtros y búsqueda -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Buscar productos:</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Nombre, descripción o código...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Categoría:</label>
                                <select class="form-select" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ordenar por:</label>
                                <select class="form-select" name="orden">
                                    <option value="nombre_asc" <?php echo $orden == 'nombre_asc' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                    <option value="nombre_desc" <?php echo $orden == 'nombre_desc' ? 'selected' : ''; ?>>Nombre Z-A</option>
                                    <option value="precio_asc" <?php echo $orden == 'precio_asc' ? 'selected' : ''; ?>>Precio menor</option>
                                    <option value="precio_desc" <?php echo $orden == 'precio_desc' ? 'selected' : ''; ?>>Precio mayor</option>
                                    <option value="stock_asc" <?php echo $orden == 'stock_asc' ? 'selected' : ''; ?>>Stock menor</option>
                                    <option value="stock_desc" <?php echo $orden == 'stock_desc' ? 'selected' : ''; ?>>Stock mayor</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resultados -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">
                        Mostrando <?php echo count($productos); ?> de <?php echo $totalProductos; ?> productos
                    </p>
                    <?php if (!empty($search) || !empty($categoria)): ?>
                        <a href="productos.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpiar filtros
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Grid de productos -->
                <?php if (!empty($productos)): ?>
                    <div class="row">
                        <?php foreach ($productos as $producto): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card product-card h-100">
                                    <div class="position-relative">
                                        <div class="product-image">
                                            <?php
                                            $imagenUrl = !empty($producto['imagen']) 
                                                ? htmlspecialchars($producto['imagen']) 
                                                : 'https://via.placeholder.com/300x200/8b4513/ffffff?text=SweetPot';
                                            ?>
                                            <img src="<?php echo $imagenUrl; ?>" 
                                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                 onerror="this.src='https://via.placeholder.com/300x200/8b4513/ffffff?text=Sin+Imagen'">
                                        </div>
                                        
                                        <!-- Badge de stock -->
                                        <span class="badge stock-badge <?php 
                                            echo $producto['stock'] <= $producto['stock_minimo'] 
                                                ? 'bg-danger' 
                                                : ($producto['stock'] <= ($producto['stock_minimo'] * 2) 
                                                    ? 'bg-warning' 
                                                    : 'bg-success'); 
                                        ?>">
                                            <?php echo $producto['stock']; ?> unidades
                                        </span>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-tag"></i>
                                                <?php echo htmlspecialchars($producto['categoria_nombre']); ?>
                                            </small>
                                        </div>
                                        
                                        <h6 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                        
                                        <?php if (!empty($producto['descripcion'])): ?>
                                            <p class="card-text text-muted small flex-grow-1">
                                                <?php echo htmlspecialchars(substr($producto['descripcion'], 0, 80)); ?>
                                                <?php if (strlen($producto['descripcion']) > 80): ?>...<?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="price-tag">
                                                    $<?php echo number_format($producto['precio'], 2); ?>
                                                </span>
                                                <?php if (!empty($producto['codigo_producto'])): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-barcode"></i>
                                                        <?php echo htmlspecialchars($producto['codigo_producto']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm w-100"
                                                    onclick="verDetalleProducto(<?php echo $producto['id']; ?>)">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginación -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Paginación de productos">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>&orden=<?php echo urlencode($orden); ?>">
                                            <i class="fas fa-chevron-left"></i> Anterior
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>&orden=<?php echo urlencode($orden); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>&orden=<?php echo urlencode($orden); ?>">
                                            Siguiente <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No se encontraron productos</h4>
                        <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                        <?php if (!empty($search) || !empty($categoria)): ?>
                            <a href="productos.php" class="btn btn-primary">
                                <i class="fas fa-refresh"></i> Ver todos los productos
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal de detalles del producto -->
    <div class="modal fade" id="modalDetalleProducto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalDetalleContenido">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function verDetalleProducto(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleProducto'));
            const modalBody = document.getElementById('modalDetalleContenido');
            
            // Mostrar loading
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Cargar detalles
            fetch(`ajax/obtener-producto.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const producto = data.producto;
                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <img src="${producto.imagen || 'https://via.placeholder.com/400x300/8b4513/ffffff?text=Sin+Imagen'}" 
                                         class="img-fluid rounded" 
                                         alt="${producto.nombre}"
                                         onerror="this.src='https://via.placeholder.com/400x300/8b4513/ffffff?text=Sin+Imagen'">
                                </div>
                                <div class="col-md-6">
                                    <h4>${producto.nombre}</h4>
                                    <p class="text-muted"><i class="fas fa-tag"></i> ${producto.categoria_nombre}</p>
                                    ${producto.descripcion ? `<p>${producto.descripcion}</p>` : ''}
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <strong>Precio:</strong><br>
                                            <span class="h5 text-success">$${parseFloat(producto.precio).toFixed(2)}</span>
                                        </div>
                                        <div class="col-6">
                                            <strong>Stock:</strong><br>
                                            <span class="badge ${producto.stock <= producto.stock_minimo ? 'bg-danger' : 'bg-success'}">
                                                ${producto.stock} unidades
                                            </span>
                                        </div>
                                    </div>
                                    
                                    ${producto.codigo_producto ? `
                                        <p><strong>Código:</strong> ${producto.codigo_producto}</p>
                                    ` : ''}
                                    
                                    ${producto.fecha_creacion ? `<p><strong>Fecha de creación:</strong> ${new Date(producto.fecha_creacion).toLocaleDateString('es-ES')}</p>` : ''}
                                </div>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Error al cargar los detalles del producto
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error de conexión
                        </div>
                    `;
                });
        }

        // Función para confirmar cerrar sesión
        function confirmarCerrarSesion() {
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: "¿Estás seguro que deseas cerrar sesión?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#8b4513',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        }
    </script>
</body>
</html>