<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../models/Usuario.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$usuarioModel = new Usuario();

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$rol = isset($_GET['rol']) ? $_GET['rol'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

try {
    // Construir filtros
    $filtros = ['limit' => $limit, 'page' => $page];
    if (!empty($search)) {
        $filtros['search'] = $search;
    }
    if (!empty($rol)) {
        $filtros['rol'] = $rol;
    }
    if (!empty($estado)) {
        $filtros['activo'] = ($estado == 'activo') ? 1 : 0;
    }

    // Obtener usuarios
    $result = $usuarioModel->listar($filtros);
    $usuarios = $result['data'] ?? [];
    $totalPages = $result['totalPages'] ?? 1;
    $currentPage = $result['currentPage'] ?? 1;
    $totalUsuarios = $result['total'] ?? 0;

    // Obtener estadísticas
    $stats = $usuarioModel->obtenerEstadisticas();

} catch (Exception $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
    $usuarios = [];
    $totalPages = 1;
    $currentPage = 1;
    $totalUsuarios = 0;
    $stats = ['admin' => 0, 'vendedor' => 0, 'cliente' => 0, 'activos' => 0];
}

$pageTitle = "Gestión de Usuarios - Admin SweetPot";
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
                    <i class="fas fa-users me-2"></i>
                    Gestión de Usuarios
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sweetpot-secondary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        <button type="button" class="btn btn-sweetpot-primary btn-sm"
                            onclick="window.location.href='usuario-form.php'">
                            <i class="fas fa-plus"></i> Nuevo Usuario
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

            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $totalUsuarios; ?></div>
                                    <div>Total Usuarios</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-users fa-2x"></i>
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
                                    <div class="fs-4 fw-bold"><?php echo $stats['cliente'] ?? 0; ?></div>
                                    <div>Clientes</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card bg-info text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $stats['vendedor'] ?? 0; ?></div>
                                    <div>Vendedores</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-user-tie fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $stats['admin'] ?? 0; ?></div>
                                    <div>Administradores</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-user-shield fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card card-sweetpot mb-4">
                <div class="card-body">
                    <form method="GET" action="usuarios.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="<?php echo htmlspecialchars($search); ?>" placeholder="Nombre, email...">
                        </div>
                        <div class="col-md-3">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" id="rol" name="rol">
                                <option value="">Todos los roles</option>
                                <option value="admin" <?php echo ($rol == 'admin') ? 'selected' : ''; ?>>Administrador
                                </option>
                                <option value="vendedor" <?php echo ($rol == 'vendedor') ? 'selected' : ''; ?>>Vendedor
                                </option>
                                <option value="cliente" <?php echo ($rol == 'cliente') ? 'selected' : ''; ?>>Cliente
                                </option>
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

            <!-- Tabla de usuarios -->
            <div class="card card-sweetpot">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Usuarios
                        <?php if (!empty($search) || !empty($rol) || !empty($estado)): ?>
                            <small class="text-muted">(Filtrado)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($usuarios)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Avatar</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Registro</th>
                                        <th>Último Acceso</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo $usuario['id']; ?></td>
                                            <td>
                                                <?php if (!empty($usuario['avatar'])): ?>
                                                    <img src="../uploads/avatars/<?php echo $usuario['avatar']; ?>"
                                                        alt="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                                        class="rounded-circle"
                                                        style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-sweetpot-gradient rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                                        style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                                <?php if ($usuario['id'] == $_SESSION['user_id']): ?>
                                                    <small class="badge bg-info ms-1">Tú</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td>
                                                <?php
                                                $rolClasses = [
                                                    'admin' => 'bg-danger',
                                                    'vendedor' => 'bg-warning text-dark',
                                                    'cliente' => 'bg-success'
                                                ];
                                                $rolIcons = [
                                                    'admin' => 'fa-user-shield',
                                                    'vendedor' => 'fa-user-tie',
                                                    'cliente' => 'fa-user'
                                                ];
                                                ?>
                                                <span
                                                    class="badge <?php echo $rolClasses[$usuario['rol']] ?? 'bg-secondary'; ?>">
                                                    <i
                                                        class="fas <?php echo $rolIcons[$usuario['rol']] ?? 'fa-user'; ?> me-1"></i>
                                                    <?php echo ucfirst($usuario['rol']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($usuario['activo']): ?>
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
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php
                                                    if (!empty($usuario['ultimo_acceso'])) {
                                                        echo date('d/m/Y H:i', strtotime($usuario['ultimo_acceso']));
                                                    } else {
                                                        echo 'Nunca';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        onclick="window.location.href='usuario-form.php?id=<?php echo $usuario['id']; ?>'"
                                                        title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                        <button type="button" class="btn btn-outline-danger"
                                                            onclick="eliminarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre']); ?>')"
                                                            title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay usuarios</h5>
                            <p class="text-muted">No se encontraron usuarios con los filtros aplicados.</p>
                            <button type="button" class="btn btn-sweetpot-primary"
                                onclick="window.location.href='usuario-form.php'">
                                <i class="fas fa-plus"></i> Agregar Primer Usuario
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginación de usuarios" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage - 1); ?>&search=<?php echo urlencode($search); ?>&rol=<?php echo urlencode($rol); ?>&estado=<?php echo urlencode($estado); ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&rol=<?php echo urlencode($rol); ?>&estado=<?php echo urlencode($estado); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage + 1); ?>&search=<?php echo urlencode($search); ?>&rol=<?php echo urlencode($rol); ?>&estado=<?php echo urlencode($estado); ?>">
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
    function eliminarUsuario(id, nombre) {
        confirmarEliminarUsuario(id, nombre).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                mostrarCargando('Eliminando usuario...');

                // Aquí iría la llamada AJAX para eliminar
                fetch('ajax/eliminar-usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            mostrarExito('Usuario desactivado', 'El usuario se desactivó correctamente');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            mostrarError('Error', data.message || 'No se pudo eliminar el usuario');
                        }
                    })
                    .catch(error => {
                        mostrarError('Error', 'Error de conexión al eliminar el usuario');
                    });
            }
        });
    }

    // Auto-refresh cada 30 segundos para ver usuarios activos
    setInterval(function () {
        // Actualizar indicadores de usuarios online si es necesario
    }, 30000);
</script>

<?php include '../includes/footer.php'; ?>