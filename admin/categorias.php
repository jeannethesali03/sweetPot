<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../models/Categoria.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$categoriaModel = new Categoria();

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

try {
    // Construir filtros
    $filtros = ['limit' => $limit, 'page' => $page];
    if (!empty($search)) {
        $filtros['search'] = $search;
    }
    if (!empty($estado)) {
        $filtros['activo'] = ($estado == 'activo') ? 1 : 0;
    }

    // Obtener categorías
    $result = $categoriaModel->listar($filtros);
    $categorias = $result['data'] ?? [];
    $totalPages = $result['totalPages'] ?? 1;
    $currentPage = $result['currentPage'] ?? 1;
    $totalCategorias = $result['total'] ?? 0;

    // Obtener estadísticas
    $stats = $categoriaModel->obtenerEstadisticas();

} catch (Exception $e) {
    $error = "Error al cargar categorías: " . $e->getMessage();
    $categorias = [];
    $totalPages = 1;
    $currentPage = 1;
    $totalCategorias = 0;
    $stats = ['total' => 0, 'activas' => 0];
}

$pageTitle = "Gestión de Categorías - Admin SweetPot";
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
                    <i class="fas fa-tags me-2"></i>
                    Gestión de Categorías
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sweetpot-secondary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        <button type="button" class="btn btn-sweetpot-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#categoriaModal">
                            <i class="fas fa-plus"></i> Nueva Categoría
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

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $totalCategorias; ?></div>
                                    <div>Total Categorías</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-tags fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $stats['activas'] ?? 0; ?></div>
                                    <div>Categorías Activas</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card card-sweetpot mb-4">
                <div class="card-body">
                    <form method="GET" action="categorias.php" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="<?php echo htmlspecialchars($search); ?>" placeholder="Nombre, descripción...">
                        </div>
                        <div class="col-md-4">
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

            <!-- Tabla de categorías -->
            <div class="card card-sweetpot">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Categorías
                        <?php if (!empty($search) || !empty($estado)): ?>
                            <small class="text-muted">(Filtrado)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($categorias)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Productos</th>
                                        <th>Fecha Creación</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <tr>
                                            <td><?php echo $categoria['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($categoria['descripcion'] ?? '', 0, 80)); ?>
                                                    <?php if (strlen($categoria['descripcion'] ?? '') > 80): ?>...<?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if (($categoria['estado'] ?? 'inactivo') == 'activo'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Activo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times me-1"></i>Inactivo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $categoria['total_productos'] ?? 0; ?> productos
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($categoria['fecha_creacion'] ?? '')); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($categoria)); ?>)"
                                                        title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        onclick="eliminarCategoria(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nombre']); ?>')"
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
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay categorías</h5>
                            <p class="text-muted">No se encontraron categorías con los filtros aplicados.</p>
                            <button type="button" class="btn btn-sweetpot-primary" data-bs-toggle="modal"
                                data-bs-target="#categoriaModal">
                                <i class="fas fa-plus"></i> Agregar Primera Categoría
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginación de categorías" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage - 1); ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage + 1); ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>">
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

<!-- Modal para crear/editar categoría -->
<div class="modal fade" id="categoriaModal" tabindex="-1" aria-labelledby="categoriaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoriaModalLabel">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoriaForm" method="POST" action="ajax/guardar-categoria.php">
                <div class="modal-body">
                    <input type="hidden" id="categoria_id" name="id">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">
                            <i class="fas fa-tag me-1"></i>
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-align-left me-1"></i>
                            Descripción
                        </label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="activo" class="form-label">
                            <i class="fas fa-toggle-on me-1"></i>
                            Estado
                        </label>
                        <select class="form-select" id="activo" name="activo">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sweetpot-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/admin-sweetalert.js"></script>

<script>
    function editarCategoria(categoria) {
        document.getElementById('categoriaModalLabel').textContent = 'Editar Categoría';
        document.getElementById('categoria_id').value = categoria.id;
        document.getElementById('nombre').value = categoria.nombre;
        document.getElementById('descripcion').value = categoria.descripcion || '';
        // Convertir estado a valor numérico para el select
        document.getElementById('activo').value = (categoria.estado === 'activo') ? 1 : 0;

        const modal = new bootstrap.Modal(document.getElementById('categoriaModal'));
        modal.show();
    } function eliminarCategoria(id, nombre) {
        confirmarEliminarCategoria(id, nombre).then((result) => {
            if (result.isConfirmed) {
                mostrarCargando('Eliminando categoría...');

                fetch('ajax/eliminar-categoria.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            mostrarExito('Categoría eliminada', 'La categoría se eliminó correctamente');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            mostrarError('Error', data.message || 'No se pudo eliminar la categoría');
                        }
                    })
                    .catch(error => {
                        mostrarError('Error', 'Error de conexión al eliminar la categoría');
                    });
            }
        });
    }

    // Manejar envío del formulario
    document.getElementById('categoriaForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        mostrarCargando('Guardando categoría...');

        fetch('ajax/guardar-categoria.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarExito('Éxito', data.message);
                    setTimeout(() => location.reload(), 1500);
                    bootstrap.Modal.getInstance(document.getElementById('categoriaModal')).hide();
                } else {
                    mostrarError('Error', data.message);
                }
            })
            .catch(error => {
                mostrarError('Error', 'Error de conexión');
            });
    });

    // Limpiar modal al cerrarse
    document.getElementById('categoriaModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('categoriaModalLabel').textContent = 'Nueva Categoría';
        document.getElementById('categoriaForm').reset();
        document.getElementById('categoria_id').value = '';
    });
</script>

<?php include '../includes/footer.php'; ?>