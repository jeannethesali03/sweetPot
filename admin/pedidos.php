<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../models/Usuario.php';
require_once '../models/Venta.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$ventaModel = new Venta();

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 15;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

try {
    // Construir filtros
    $filtros = ['limit' => $limit, 'page' => $page];
    if (!empty($search)) {
        $filtros['search'] = $search;
    }
    if (!empty($estado)) {
        $filtros['estado'] = $estado;
    }
    if (!empty($fecha_desde)) {
        $filtros['fecha_desde'] = $fecha_desde;
    }
    if (!empty($fecha_hasta)) {
        $filtros['fecha_hasta'] = $fecha_hasta;
    }

    // Obtener pedidos
    $result = $ventaModel->listar($filtros);
    $pedidos = $result['data'] ?? [];
    $totalPages = $result['totalPages'] ?? 1;
    $currentPage = $result['currentPage'] ?? 1;
    $totalPedidos = $result['total'] ?? 0;

    // Obtener estadísticas
    $stats = $ventaModel->obtenerEstadisticas();

    // Obtener estados disponibles
    $estados = $ventaModel->obtenerEstados();

} catch (Exception $e) {
    $error = "Error al cargar pedidos: " . $e->getMessage();
    $pedidos = [];
    $totalPages = 1;
    $currentPage = 1;
    $totalPedidos = 0;
    $stats = ['total_pedidos' => 0, 'pendientes' => 0, 'en_proceso' => 0, 'enviados' => 0, 'entregados' => 0, 'cancelados' => 0];
    $estados = [];
}

$pageTitle = "Gestión de Pedidos - Admin SweetPot";
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
                    <i class="fas fa-clipboard-list me-2"></i>
                    Gestión de Pedidos
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sweetpot-secondary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualizar
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
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5><?php echo $stats['total_pedidos'] ?? 0; ?></h5>
                                    <p class="mb-0 small">Total Pedidos</p>
                                </div>
                                <div>
                                    <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5><?php echo $stats['pendientes'] ?? 0; ?></h5>
                                    <p class="mb-0 small">Pendientes</p>
                                </div>
                                <div>
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5><?php echo $stats['en_proceso'] ?? 0; ?></h5>
                                    <p class="mb-0 small">En Proceso</p>
                                </div>
                                <div>
                                    <i class="fas fa-cogs fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5><?php echo $stats['enviados'] ?? 0; ?></h5>
                                    <p class="mb-0 small">Enviados</p>
                                </div>
                                <div>
                                    <i class="fas fa-shipping-fast fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5><?php echo $stats['entregados'] ?? 0; ?></h5>
                                    <p class="mb-0 small">Entregados</p>
                                </div>
                                <div>
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5><?php echo $stats['cancelados'] ?? 0; ?></h5>
                                    <p class="mb-0 small">Cancelados</p>
                                </div>
                                <div>
                                    <i class="fas fa-times-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card card-sweetpot mb-4">
                <div class="card-body">
                    <form method="GET" action="pedidos.php" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="<?php echo htmlspecialchars($search); ?>" placeholder="Nº pedido, cliente, email...">
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
                                <button type="submit" class="btn btn-sweetpot-primary">
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
            <div class="card card-sweetpot">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Pedidos
                        <?php if (!empty($search) || !empty($estado) || !empty($fecha_desde) || !empty($fecha_hasta)): ?>
                            <small class="text-muted">(Filtrado - <?php echo $totalPedidos; ?> resultados)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($pedidos)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nº Pedido</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th width="180">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($pedido['numero_pedido']); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($pedido['cliente_email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?>
                                            </td>
                                            <td class=" fw-bold">
                                                $<?php echo number_format($pedido['total'], 2); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = '';
                                                switch ($pedido['estado']) {
                                                    case 'pendiente':
                                                        $badgeClass = 'bg-warning';
                                                        break;
                                                    case 'en_proceso':
                                                        $badgeClass = 'bg-info';
                                                        break;
                                                    case 'enviado':
                                                        $badgeClass = 'bg-secondary';
                                                        break;
                                                    case 'entregado':
                                                        $badgeClass = 'bg-success';
                                                        break;
                                                    case 'cancelado':
                                                        $badgeClass = 'bg-danger';
                                                        break;
                                                    default:
                                                        $badgeClass = 'bg-light text-dark';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        onclick="verDetallePedido(<?php echo $pedido['id']; ?>)"
                                                        title="Ver detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($pedido['estado'] != 'cancelado' && $pedido['estado'] != 'entregado'): ?>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-success dropdown-toggle"
                                                                data-bs-toggle="dropdown" title="Cambiar estado">
                                                                <i class="fas fa-exchange-alt"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <?php foreach ($estados as $key => $value): ?>
                                                                    <?php if ($key != $pedido['estado'] && $key != 'cancelado'): ?>
                                                                        <li>
                                                                            <a class="dropdown-item" href="#"
                                                                                onclick="cambiarEstado(<?php echo $pedido['id']; ?>, '<?php echo $key; ?>', '<?php echo $value; ?>')">
                                                                                <?php echo $value; ?>
                                                                            </a>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#"
                                                                        onclick="cancelarPedido(<?php echo $pedido['id']; ?>, '<?php echo htmlspecialchars($pedido['numero_pedido']); ?>')">
                                                                        Cancelar
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
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
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay pedidos</h5>
                            <p class="text-muted">No se encontraron pedidos con los filtros aplicados.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginación de pedidos" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage - 1); ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?php echo ($currentPage + 1); ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>">
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

<!-- Modal para ver detalle del pedido -->
<div class="modal fade" id="modalDetallePedido" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Detalle del Pedido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDetallePedido">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="imprimirPedido()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/admin-sweetalert.js"></script>

<script>
    let pedidoActual = null;

    function verDetallePedido(id) {
        pedidoActual = id;
        const modal = new bootstrap.Modal(document.getElementById('modalDetallePedido'));
        
        // Resetear contenido
        document.getElementById('contenidoDetallePedido').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;
        
        modal.show();
        
        // Cargar datos del pedido
        fetch('ajax/obtener-detalle-pedido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDetallePedido(data.pedido);
            } else {
                document.getElementById('contenidoDetallePedido').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar el pedido: ' + (data.message || 'Error desconocido') + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('contenidoDetallePedido').innerHTML = 
                '<div class="alert alert-danger">Error de conexión al cargar el pedido</div>';
        });
    }
    
    function mostrarDetallePedido(pedido) {
        let productosHtml = '';
        let totalProductos = 0;
        
        if (pedido.productos && pedido.productos.length > 0) {
            pedido.productos.forEach(producto => {
                totalProductos += parseInt(producto.cantidad);
                productosHtml += `
                    <tr>
                        <td>
                            ${producto.producto_imagen ? 
                                `<img src="${producto.producto_imagen}" alt="${producto.producto_nombre}" class="img-thumbnail me-2" style="width: 40px; height: 40px; object-fit: cover;">` 
                                : '<div class="bg-light d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;"><i class="fas fa-image text-muted"></i></div>'
                            }
                            <strong>${producto.producto_nombre}</strong>
                            <br><small class="text-muted">${producto.categoria_nombre}</small>
                        </td>
                        <td class="text-center">${producto.cantidad}</td>
                        <td class="text-end">$${parseFloat(producto.precio_unitario).toFixed(2)}</td>
                        <td class="text-end fw-bold">$${parseFloat(producto.subtotal).toFixed(2)}</td>
                    </tr>
                `;
            });
        }
        
        const html = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle me-1"></i> Información del Pedido</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Número:</strong></td><td>${pedido.numero_pedido}</td></tr>
                        <tr><td><strong>Fecha:</strong></td><td>${new Date(pedido.fecha).toLocaleString('es-ES')}</td></tr>
                        <tr><td><strong>Estado:</strong></td><td><span class="badge bg-primary">${pedido.estado.replace('_', ' ').toUpperCase()}</span></td></tr>
                        <tr><td><strong>Total Productos:</strong></td><td>${totalProductos}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-user me-1"></i> Información del Cliente</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Nombre:</strong></td><td>${pedido.cliente_nombre}</td></tr>
                        <tr><td><strong>Email:</strong></td><td>${pedido.cliente_email}</td></tr>
                        <tr><td><strong>Teléfono:</strong></td><td>${pedido.cliente_telefono || 'No especificado'}</td></tr>
                        <tr><td><strong>Vendedor:</strong></td><td>${pedido.vendedor_nombre || 'Venta Online'}</td></tr>
                    </table>
                </div>
            </div>
            
            ${pedido.direccion_entrega ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-map-marker-alt me-1"></i> Dirección de Entrega</h6>
                        <p class="bg-light p-2 rounded">${pedido.direccion_entrega}</p>
                    </div>
                </div>
            ` : ''}
            
            ${pedido.comentarios ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-comment me-1"></i> Comentarios</h6>
                        <p class="bg-light p-2 rounded">${pedido.comentarios}</p>
                    </div>
                </div>
            ` : ''}
            
            <div class="row mt-3">
                <div class="col-12">
                    <h6><i class="fas fa-shopping-cart me-1"></i> Productos del Pedido</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${productosHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <table class="table table-sm">
                        <tr><td><strong>Subtotal:</strong></td><td class="text-end">$${parseFloat(pedido.subtotal).toFixed(2)}</td></tr>
                        <tr><td><strong>Impuestos:</strong></td><td class="text-end">$${parseFloat(pedido.impuestos).toFixed(2)}</td></tr>
                        <tr><td><strong>Descuento:</strong></td><td class="text-end">-$${parseFloat(pedido.descuento).toFixed(2)}</td></tr>
                        <tr class="table-success"><td><strong>TOTAL:</strong></td><td class="text-end fw-bold">$${parseFloat(pedido.total).toFixed(2)}</td></tr>
                    </table>
                </div>
            </div>
        `;
        
        document.getElementById('contenidoDetallePedido').innerHTML = html;
    }
    
    function cambiarEstado(id, nuevoEstado, nombreEstado) {
        Swal.fire({
            title: '¿Cambiar estado del pedido?',
            text: `El pedido cambiará a: ${nombreEstado}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                mostrarCargando('Cambiando estado...');
                
                fetch('ajax/cambiar-estado-pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        id: id, 
                        estado: nuevoEstado 
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarExito('Estado actualizado', `El pedido cambió a: ${nombreEstado}`);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarError('Error', data.message || 'No se pudo cambiar el estado');
                    }
                })
                .catch(error => {
                    mostrarError('Error', 'Error de conexión al cambiar el estado');
                });
            }
        });
    }
    
    function cancelarPedido(id, numeroPedido) {
        Swal.fire({
            title: '¿Cancelar pedido?',
            text: `Se cancelará el pedido: ${numeroPedido}`,
            input: 'textarea',
            inputLabel: 'Motivo de cancelación (opcional)',
            inputPlaceholder: 'Escribe el motivo...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                mostrarCargando('Cancelando pedido...');
                
                fetch('ajax/cancelar-pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        id: id, 
                        motivo: result.value || ''
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarExito('Pedido cancelado', 'El pedido se canceló correctamente');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarError('Error', data.message || 'No se pudo cancelar el pedido');
                    }
                })
                .catch(error => {
                    mostrarError('Error', 'Error de conexión al cancelar el pedido');
                });
            }
        });
    }
    
    function imprimirPedido() {
        if (pedidoActual) {
            window.open(`ajax/imprimir-pedido.php?id=${pedidoActual}`, '_blank');
        }
    }
    
    function exportarPedidos() {
        // Implementar exportación (CSV/PDF)
        mostrarInfo('Funcionalidad en desarrollo', 'La exportación estará disponible próximamente');
    }
    
    // Confirmar antes de salir si hay cambios sin guardar
    window.addEventListener('beforeunload', function (e) {
        // Aquí se puede agregar lógica para detectar cambios sin guardar
    });
</script>

<?php include '../includes/footer.php'; ?>