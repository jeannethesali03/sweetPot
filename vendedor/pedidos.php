<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../models/Usuario.php';
require_once '../models/Venta.php';

// Verificar autenticación y rol de vendedor
Auth::requireRole('vendedor');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$ventaModel = new Venta();

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10; // Reducido temporalmente para probar paginación
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$owner = isset($_GET['owner']) ? $_GET['owner'] : 'all';

try {
    // Construir consulta directamente sin usar el modelo
    $whereConditions = ['1=1'];
    $params = [];

    // Agregar filtro de búsqueda
    if (!empty($search)) {
        $whereConditions[] = "(v.id LIKE :search OR v.numero_pedido LIKE :search OR u.nombre LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Agregar filtro de estado
    if (!empty($estado)) {
        $whereConditions[] = "v.estado = :estado";
        $params[':estado'] = $estado;
    }

    // Filtro por propietario/vendedor
    if ($owner === 'mine') {
        // Mostrar solo pedidos asignados a este vendedor
        $whereConditions[] = "v.vendedor_id = :vendedor_id";
        $params[':vendedor_id'] = $_SESSION['user_id'];
    } elseif ($owner === 'unassigned') {
        // Mostrar pedidos sin vendedor asignado
        $whereConditions[] = "v.vendedor_id IS NULL";
    }

    // Agregar filtros de fecha
    if (!empty($fecha_desde)) {
        $whereConditions[] = "DATE(v.fecha) >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    }

    if (!empty($fecha_hasta)) {
        $whereConditions[] = "DATE(v.fecha) <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Contar total de pedidos
    $countQuery = "SELECT COUNT(*) FROM ventas v 
                   INNER JOIN usuarios u ON v.cliente_id = u.id 
                   WHERE $whereClause";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $totalPedidos = $countStmt->fetchColumn();

    // Calcular paginación
    $totalPages = ceil($totalPedidos / $limit);
    $currentPage = $page;
    $offset = ($page - 1) * $limit;

    // Obtener pedidos con método de pago
    $query = "SELECT v.*, u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono,
                     p.metodo as metodo_pago
              FROM ventas v 
              INNER JOIN usuarios u ON v.cliente_id = u.id 
              LEFT JOIN pagos p ON v.id = p.venta_id
              WHERE $whereClause 
              ORDER BY v.fecha DESC, v.id DESC 
              LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener estadísticas
    $statsQuery = "SELECT 
                    COUNT(*) as total_pedidos,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
                    SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
                    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
                   FROM ventas";
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Estados disponibles
    $estados = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En Proceso',
        'enviado' => 'Enviado',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado'
    ];

} catch (Exception $e) {
    $error = "Error al cargar pedidos: " . $e->getMessage();
    $pedidos = [];
    $totalPages = 1;
    $currentPage = 1;
    $totalPedidos = 0;
    $stats = ['total_pedidos' => 0, 'pendientes' => 0, 'en_proceso' => 0, 'enviados' => 0, 'entregados' => 0, 'cancelados' => 0];
    $estados = [];
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - SweetPot Vendedor</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- SweetPot CSS -->
    <link href="../assets/css/sweetpot-theme.css" rel="stylesheet">

    <style>
        .stats-card {
            background: linear-gradient(135deg, var(--sweetpot-cream), #fff);
            border-left: 4px solid var(--sweetpot-brown);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .estado-badge {
            font-size: 0.85em;
            font-weight: 500;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(139, 69, 19, 0.05);
        }

        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Gestión de Pedidos
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>



                <!-- Estadísticas rápidas -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card bg-sweetpot-gradient text-center">
                            <div class="card-body">
                                <i class="fas fa-clipboard-list fa-2x mb-2" style="color: white"></i>
                                <h4 style="color: white; font-weight: bold;"><?php echo $stats['total_pedidos'] ?? 0; ?>
                                </h4>
                                <p class="mb-0 small" style="color: white; font-weight: bold;">Total Pedidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-sweetpot-gradient text-center">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h4 style="color: white; font-weight: bold;"><?php echo $stats['pendientes'] ?? 0; ?>
                                </h4>
                                <p class="mb-0 small" style="color: white; font-weight: bold;">Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-sweetpot-gradient text-center">
                            <div class="card-body">
                                <i class="fas fa-cogs fa-2x mb-2" style="color: white; font-weight: bold;"></i>
                                <h4 style="color: white; font-weight: bold;"><?php echo $stats['en_proceso'] ?? 0; ?>
                                </h4>
                                <p class="mb-0 small" style="color: white; font-weight: bold;">En Proceso</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-sweetpot-gradient text-center">
                            <div class="card-body">
                                <i class="fas fa-shipping-fast fa-2x mb-2" style="color: white; font-weight: bold;"></i>
                                <h4 style="color: white; font-weight: bold;"><?php echo $stats['enviados'] ?? 0; ?>
                                </h4>
                                <p class="mb-0 small" style="color: white; font-weight: bold;">Enviados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-sweetpot-gradient text-center">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x mb-2" style="color: white; font-weight: bold;"></i>
                                <h4 style="color: white; font-weight: bold;"><?php echo $stats['entregados'] ?? 0; ?>
                                </h4>
                                <p class="mb-0 small" style="color: white; font-weight: bold;">Entregados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-sweetpot-gradient text-center">
                            <div class="card-body">
                                <i class="fas fa-times-circle fa-2x mb-2" style="color: white; font-weight: bold;"></i>
                                <h4 style="color: white; font-weight: bold;"><?php echo $stats['cancelados'] ?? 0; ?>
                                </h4>
                                <p class="mb-0 small" style="color: white; font-weight: bold;">Cancelados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="pedidos.php" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Nº pedido, cliente, email...">
                            </div>
                            <div class="col-md-2">
                                <label for="owner_filter" class="form-label">Propietario</label>
                                <select id="owner_filter" name="owner" class="form-select">
                                    <option value="all" <?php echo (isset($_GET['owner']) && $_GET['owner'] === 'all') ? 'selected' : ''; ?>>Todos</option>
                                    <option value="mine" <?php echo (isset($_GET['owner']) && $_GET['owner'] === 'mine') ? 'selected' : ''; ?>>Mis pedidos</option>
                                    <option value="unassigned" <?php echo (isset($_GET['owner']) && $_GET['owner'] === 'unassigned') ? 'selected' : ''; ?>>Sin vendedor</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <?php foreach ($estados as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($estado == $key) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                                    value="<?php echo htmlspecialchars($fecha_desde); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                                    value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <a href="pedidos.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de pedidos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Lista de Pedidos
                            <span class="badge bg-secondary ms-2"><?php echo $totalPedidos; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($pedidos)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Cliente</th>
                                            <th>Fecha</th>
                                            <th>Total</th>
                                            <th>Estado</th>
                                            <th>Método Pago</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pedidos as $pedido): ?>
                                            <tr>
                                                <td class="fw-bold">#<?php echo $pedido['id']; ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></strong>
                                                        <br>
                                                        <small
                                                            class="text-muted"><?php echo htmlspecialchars($pedido['cliente_email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo $pedido['fecha'] ? date('d/m/Y', strtotime($pedido['fecha'])) : 'N/A'; ?>
                                                        <br>
                                                        <small
                                                            class="text-muted"><?php echo $pedido['fecha'] ? date('H:i', strtotime($pedido['fecha'])) : ''; ?></small>
                                                    </div>
                                                </td>
                                                <td class="fw-bold">
                                                    $<?php echo number_format($pedido['total'], 2); ?>
                                                </td>
                                                <td>
                                                    <span class="badge estado-badge <?php
                                                    echo match ($pedido['estado']) {
                                                        'pendiente' => 'bg-warning text-dark',
                                                        'en_proceso' => 'bg-info',
                                                        'enviado' => 'bg-primary',
                                                        'entregado' => 'bg-success',
                                                        'cancelado' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>">
                                                        <?php echo ucfirst($pedido['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo $pedido['metodo_pago'] ? ucfirst($pedido['metodo_pago']) : 'No especificado'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-outline-primary btn-action"
                                                            onclick="verDetallePedido(<?php echo $pedido['id']; ?>)"
                                                            title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning btn-action"
                                                            onclick="cambiarEstadoPedido(<?php echo $pedido['id']; ?>, '<?php echo $pedido['estado']; ?>')"
                                                            title="Cambiar estado">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Debug temporal de paginación -->
                            <div class="alert alert-info mt-3">
                                <small>
                                    <strong>Debug Paginación:</strong>
                                    Total pedidos: <?php echo $totalPedidos; ?> |
                                    Total páginas: <?php echo $totalPages; ?> |
                                    Página actual: <?php echo $currentPage; ?> |
                                    Límite: <?php echo $limit; ?>
                                </small>
                            </div>

                            <!-- Paginación -->
                            <?php if ($totalPages > 1): ?>
                                <div class="d-flex justify-content-center mt-4">
                                    <nav aria-label="Paginación de pedidos">
                                        <ul class="pagination">
                                            <?php if ($currentPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>">
                                                        <i class="fas fa-chevron-left"></i> Anterior
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php
                                            $start = max(1, $currentPage - 2);
                                            $end = min($totalPages, $currentPage + 2);

                                            for ($i = $start; $i <= $end; $i++):
                                                ?>
                                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                                    <a class="page-link"
                                                        href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($currentPage < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>">
                                                        Siguiente <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h4>No se encontraron pedidos</h4>
                                <p class="text-muted">
                                    <?php if (!empty($search) || !empty($estado) || !empty($fecha_desde) || !empty($fecha_hasta)): ?>
                                        Intenta ajustar los filtros de búsqueda
                                    <?php else: ?>
                                        Aún no hay pedidos registrados
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para cambiar estado -->
    <div class="modal fade" id="modalCambiarEstado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Estado del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCambiarEstado">
                        <input type="hidden" id="pedidoId" name="pedido_id">
                        <div class="mb-3">
                            <label for="nuevoEstado" class="form-label">Nuevo Estado:</label>
                            <select class="form-select" id="nuevoEstado" name="nuevo_estado" required>
                                <option value="">Seleccionar estado...</option>
                                <?php foreach ($estados as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comentario" class="form-label">Comentario (opcional):</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="3"
                                placeholder="Agregar un comentario sobre el cambio de estado..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarCambioEstado()">Confirmar
                        Cambio</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del pedido -->
    <div class="modal fade" id="modalDetallePedido" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Pedido</h5>
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
        function verDetallePedido(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetallePedido'));
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
            fetch(`ajax/obtener-detalle-pedido.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = data.html;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Error al cargar los detalles del pedido
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

        function cambiarEstadoPedido(id, estadoActual) {
            document.getElementById('pedidoId').value = id;
            document.getElementById('nuevoEstado').value = estadoActual;

            const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
            modal.show();
        }

        function confirmarCambioEstado() {
            const form = document.getElementById('formCambiarEstado');
            const formData = new FormData(form);
            // Helper to actually post (allow force flag)
            function postCambio(force = false) {
                if (force) formData.set('force', '1');
                fetch('ajax/cambiar-estado-pedido.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Estado actualizado!',
                                text: 'El estado del pedido ha sido actualizado correctamente',
                                timer: 1600,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else if (data.conflict) {
                            // Pedido ya tiene vendedor asignado
                            Swal.fire({
                                icon: 'warning',
                                title: 'Pedido asignado',
                                html: `Este pedido ya está asignado a otro vendedor (ID: <strong>${data.assigned_vendedor_id}</strong>). ¿Deseas forzar el cambio y asignarlo a ti?`,
                                showCancelButton: true,
                                confirmButtonText: 'Forzar y asignar',
                                cancelButtonText: 'Cancelar'
                            }).then((res) => {
                                if (res.isConfirmed) {
                                    postCambio(true);
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Error al actualizar el estado'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error de conexión'
                        });
                    });
            }

            postCambio(false);

            bootstrap.Modal.getInstance(document.getElementById('modalCambiarEstado')).hide();
        }

        function imprimirTicket(id) {
            // Obtener el número de ticket primero
            fetch(`ajax/obtener-numero-ticket.php?venta_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Abrir ticket con el número correcto
                        window.open(`../cliente/ticket.php?numero=${data.numero_ticket}`, '_blank');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo obtener el ticket'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión al obtener el ticket'
                    });
                });
        }

        function exportarPedidos() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = 'ajax/exportar-pedidos.php?' + params.toString();
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
    <?php include '../includes/footer.php'; ?>
</body>

</html>