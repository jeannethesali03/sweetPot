<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../models/Usuario.php';
require_once '../models/Producto.php';
require_once '../models/Categoria.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$productoModel = new Producto();
$categoriaModel = new Categoria();

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

try {
    // Construir filtros
    $filtros = ['limit' => $limit, 'page' => $page];
    if (!empty($search)) {
        $filtros['search'] = $search;
    }
    if (!empty($categoria)) {
        $filtros['categoria'] = $categoria;
    }
    if (!empty($estado)) {
        $filtros['estado'] = $estado;
    }

    // Obtener productos
    $result = $productoModel->listar($filtros);
    $productos = $result['data'] ?? [];
    $totalPages = $result['totalPages'] ?? 1;
    $currentPage = $result['currentPage'] ?? 1;
    $totalProductos = $result['total'] ?? 0;

    // Obtener estadísticas
    $stats = $productoModel->obtenerEstadisticas();

    // Obtener categorías para el filtro
    $categorias = $categoriaModel->listar();

} catch (Exception $e) {
    $error = "Error al cargar productos: " . $e->getMessage();
    $productos = [];
    $totalPages = 1;
    $currentPage = 1;
    $totalProductos = 0;
    $stats = ['total' => 0, 'activos' => 0, 'sin_stock' => 0];
    $categorias = [];
}

$pageTitle = "Gestión de Productos - Admin SweetPot";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 text-gradient-sweetpot">
                    <i class="fas fa-birthday-cake me-2"></i>
                    Gestión de Productos
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sweetpot-secondary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        <button type="button" class="btn btn-sweetpot-primary btn-sm"
                            onclick="window.location.href='producto-form.php'">
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

            <!-- Filtros -->
            <div class="card card-sweetpot mb-4">
                <div class="card-body">
                    <form method="GET" action="productos.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="<?php echo htmlspecialchars($search); ?>" placeholder="Nombre, descripción...">
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="activo" <?php echo ($estado == 'activo') ? 'selected' : ''; ?>>Activo
                                </option>
                                <option value="inactivo" <?php echo ($estado == 'inactivo') ? 'selected' : ''; ?>>Inactivo
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-sweetpot-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5><?php echo $totalProductos; ?></h5>
                                    <p class="mb-0">Total Productos</p>
                                </div>
                                <div>
                                    <i class="fas fa-birthday-cake fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de productos -->
            <div class="card card-sweetpot">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Productos
                        <?php if (!empty($search) || !empty($categoria) || !empty($estado)): ?>
                            <small class="text-muted">(Filtrado)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($productos)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th width="200">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): ?>
                                        <tr>
                                            <td><?php echo $producto['id']; ?></td>
                                            <td>
                                                <?php if (!empty($producto['imagen'])): ?>
                                                    <img src="<?php echo htmlspecialchars($producto['imagen']); ?>"
                                                        alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                        class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <?php else: ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center img-thumbnail"
                                                        style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                                <?php if (!empty($producto['descripcion'])): ?>
                                                    <br><small
                                                        class="text-muted"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 50)) . '...'; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-info"><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></span>
                                            </td>
                                            <td class=" fw-bold">
                                                $<?php echo number_format($producto['precio'], 2); ?></td>
                                            <td>
                                                <?php
                                                $stockClass = 'text-success-stock';
                                                if ($producto['stock'] <= 5)
                                                    $stockClass = 'text-danger-stock';
                                                elseif ($producto['stock'] <= 10)
                                                    $stockClass = 'text-warning';
                                                ?>
                                                <span
                                                    class="<?php echo $stockClass; ?> fw-bold"><?php echo $producto['stock']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($producto['estado'] == 'activo'): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        onclick="window.location.href='producto-form.php?id=<?php echo $producto['id']; ?>'"
                                                        title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success"
                                                        onclick="generarQR(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')"
                                                        title="Generar código QR">
                                                        <i class="fas fa-qrcode"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        onclick="eliminarProducto(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')"
                                                        title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-birthday-cake fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay productos</h5>
                            <p class="text-muted">No se encontraron productos con los filtros aplicados.</p>
                            <button type="button" class="btn btn-sweetpot-primary"
                                onclick="window.location.href='producto-form.php'">
                                <i class="fas fa-plus"></i> Agregar Primer Producto
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginación de productos" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage - 1); ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>&estado=<?php echo urlencode($estado); ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>&estado=<?php echo urlencode($estado); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage + 1); ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo urlencode($categoria); ?>&estado=<?php echo urlencode($estado); ?>">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/admin-sweetalert.js"></script>

<script>
    function eliminarProducto(id, nombre) {
        confirmarEliminarProducto(id, nombre).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                mostrarCargando('Eliminando producto...');

                // Aquí iría la llamada AJAX para eliminar
                fetch('ajax/eliminar-producto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            mostrarExito('Producto eliminado', 'El producto se eliminó correctamente');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            mostrarError('Error', data.message || 'No se pudo eliminar el producto');
                        }
                    })
                    .catch(error => {
                        mostrarError('Error', 'Error de conexión al eliminar el producto');
                    });
            }
        });
    }

    function generarQR(id, nombre) {
        Swal.fire({
            title: '¿Generar código QR?',
            text: `Se generará un código QR para: ${nombre}`,
            html: `
                <p>Se generará un código QR para: <strong>${nombre}</strong></p>
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="formatoQr" id="formatoImagen" value="imagen" checked>
                        <label class="form-check-label" for="formatoImagen">
                            <i class="fas fa-image me-1"></i> Descargar imagen PNG
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="formatoQr" id="formatoPdf" value="pdf">
                        <label class="form-check-label" for="formatoPdf">
                            <i class="fas fa-file-pdf me-1"></i> Descargar PDF con información
                        </label>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-qrcode"></i> Generar QR',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const formato = document.querySelector('input[name="formatoQr"]:checked').value;
                return formato;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formato = result.value;

                // Mostrar loading
                mostrarCargando('Generando código QR...');

                fetch('ajax/generar-qr-producto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        formato: formato
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡QR generado exitosamente!',
                                html: `
                                <div class="mb-3">
                                    <img src="${data.qr_url}" alt="Código QR" style="max-width: 200px; border: 2px solid #ddd; border-radius: 10px;">
                                </div>
                                <p><strong>URL del producto:</strong><br>
                                <small class="text-muted">${data.producto_url}</small></p>
                            `,
                                showConfirmButton: true,
                                showCancelButton: true,
                                confirmButtonText: `<i class="fas fa-download"></i> Descargar ${formato.toUpperCase()}`,
                                cancelButtonText: 'Cerrar',
                                confirmButtonColor: '#007bff'
                            }).then((downloadResult) => {
                                if (downloadResult.isConfirmed) {
                                    // Descargar el archivo
                                    const link = document.createElement('a');
                                    link.href = data.download_url;
                                    link.download = data.filename;
                                    document.body.appendChild(link);
                                    link.click();
                                    document.body.removeChild(link);
                                }
                            });
                        } else {
                            mostrarError('Error', data.message || 'No se pudo generar el código QR');
                        }
                    })
                    .catch(error => {
                        mostrarError('Error', 'Error de conexión al generar el código QR');
                    });
            }
        });
    }

    // Confirmar antes de salir si hay cambios sin guardar
    window.addEventListener('beforeunload', function (e) {
        // Aquí se puede agregar lógica para detectar cambios sin guardar
    });
</script>

<?php include '../includes/footer.php'; ?>