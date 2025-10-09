<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../models/Usuario.php';
require_once '../models/Producto.php';
require_once '../models/Pedido.php';
require_once '../models/Categoria.php';

// Verificar autenticación y rol de administrador
$user = Auth::getUser();
if (!$user || ($user['rol'] !== 'admin' && $user['rol'] !== 'administrador')) {
    header('Location: ../acceso_denegado.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$usuarioModel = new Usuario();
$productoModel = new Producto();
$pedidoModel = new Pedido();
$categoriaModel = new Categoria();

// Obtener estadísticas del dashboard
try {
    // Estadísticas generales
    $totalUsuarios = $usuarioModel->contarPorRol();
    $totalProductos = $productoModel->contar(['estado' => 'activo']);
    $totalCategorias = $categoriaModel->contar(['estado' => 'activo']);

    // Estadísticas de pedidos
    $estadisticasPedidos = $pedidoModel->obtenerEstadisticas();

    // Debug: Solo para desarrollo - remover en producción
    if (!$estadisticasPedidos) {
        error_log("Error: No se pudieron obtener estadísticas de pedidos");
        $estadisticasPedidos = [
            'total' => 0,
            'pendiente' => 0,
            'en_proceso' => 0,
            'enviado' => 0,
            'entregado' => 0,
            'cancelado' => 0
        ];
    }

    // Productos con stock bajo (menos de 10)
    $productosStockBajo = $productoModel->obtenerConStockBajo(10);

    // Últimos pedidos
    $ultimosPedidos = $pedidoModel->obtenerUltimosPedidos(5);

    // Productos más vendidos
    $productosMasVendidos = $productoModel->obtenerMasVendidos(5);

} catch (Exception $e) {
    $error = "Error al cargar datos del dashboard: " . $e->getMessage();
}

$pageTitle = "Dashboard Administrativo - SweetPot";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom mt-5">
                <h1 class="h2 text-gradient-sweetpot">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard Administrativo
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

            <!-- Tarjetas de estadísticas principales -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-sweetpot bg-sweetpot-gradient text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $totalUsuarios['cliente'] ?? 0; ?></div>
                                    <div>Clientes</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center justify-content-between bg-transparent border-0">
                            <a class="small text-white stretched-link text-decoration-none"
                                href="usuarios.php?rol=cliente">Ver detalles</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-sweetpot bg-sweetpot-gradient-dark text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $totalProductos ?? 0; ?></div>
                                    <div>Productos</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-birthday-cake fa-2x"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center justify-content-between bg-transparent border-0">
                            <a class="small text-white stretched-link text-decoration-none" href="productos.php">Ver
                                detalles</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-sweetpot bg-sweetpot-gradient text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $estadisticasPedidos['total'] ?? 0; ?></div>
                                    <div>Pedidos</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-shopping-bag fa-2x"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center justify-content-between bg-transparent border-0">
                            <a class="small text-white stretched-link text-decoration-none" href="pedidos.php">Ver
                                detalles</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-sweetpot bg-sweetpot-gradient-dark text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold"><?php echo $totalUsuarios['vendedor'] ?? 0; ?></div>
                                    <div>Vendedores</div>
                                </div>
                                <div class="opacity-75">
                                    <i class="fas fa-user-tie fa-2x"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center justify-content-between bg-transparent border-0">
                            <a class="small text-white stretched-link text-decoration-none"
                                href="usuarios.php?rol=vendedor">Ver detalles</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos y estadísticas -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Estados de Pedidos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="fs-3 text-warning">
                                            <?php echo $estadisticasPedidos['pendiente'] ?? 0; ?>
                                        </div>
                                        <div class="badge badge-estado-enviado">Pendientes</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="fs-3 text-info">
                                            <?php echo $estadisticasPedidos['en_proceso'] ?? 0; ?>
                                        </div>
                                        <div class="badge badge-estado-enviado">En Proceso</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="fs-3 text-sweetpot-pink">
                                            <?php echo $estadisticasPedidos['enviado'] ?? 0; ?>
                                        </div>
                                        <div class="badge badge-estado-entregado">Enviados</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="fs-3 text-success">
                                            <?php echo $estadisticasPedidos['entregado'] ?? 0; ?>
                                        </div>
                                        <div class="badge badge-estado-entregado">Entregados</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                Stock Bajo
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($productosStockBajo)): ?>
                                <div style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($productosStockBajo as $producto): ?>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><br>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($producto['categoria']); ?></small>
                                            </div>
                                            <span class="badge bg-warning text-dark"><?php echo $producto['stock']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <p>Todos los productos tienen stock suficiente</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimos pedidos y productos más vendidos -->
            <div class="row">
                <div class="col-lg-7">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Últimos Pedidos
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($ultimosPedidos['data'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Pedido</th>
                                                <th>Cliente</th>
                                                <th>Estado</th>
                                                <th>Total</th>
                                                <th>Fecha</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimosPedidos['data'] as $pedido): ?>
                                                <tr>
                                                    <td><strong>#<?php echo $pedido['numero_pedido']; ?></strong></td>
                                                    <td><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></td>
                                                    <td>
                                                        <span
                                                            class="badge badge-estado-<?php echo strtolower($pedido['estado']); ?>">
                                                            <?php echo ucfirst($pedido['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sweetpot-secondary btn-sm"
                                                            onclick="verDetallePedido(<?php echo $pedido['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center">
                                    <a href="pedidos.php" class="btn btn-sweetpot-primary">
                                        <i class="fas fa-list"></i> Ver Todos los Pedidos
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-shopping-bag fa-2x mb-2 opacity-50"></i>
                                    <p>No hay pedidos recientes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-star me-2"></i>
                                Productos Más Vendidos
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($productosMasVendidos)): ?>
                                <?php foreach ($productosMasVendidos as $index => $producto): ?>
                                    <div
                                        class="d-flex justify-content-between align-items-center py-2 <?php echo $index !== count($productosMasVendidos) - 1 ? 'border-bottom' : ''; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <span
                                                    class="badge bg-sweetpot-pink rounded-pill"><?php echo $index + 1; ?></span>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><br>
                                                <small
                                                    class="text-muted">$<?php echo number_format($producto['precio'], 2); ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success"><?php echo $producto['total_vendidos']; ?></span><br>
                                            <small class="text-muted">vendidos</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-bar fa-2x mb-2 opacity-50"></i>
                                    <p>No hay datos de ventas aún</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/admin-sweetalert.js"></script>

<script>
    // Datos para gráficos (si se implementan charts.js más adelante)
    const estadisticasPedidos = <?php echo json_encode($estadisticasPedidos ?? []); ?>;
    const productosStockBajo = <?php echo json_encode($productosStockBajo ?? []); ?>;

    // Función para mostrar alerta de stock bajo
    function alertaStockBajo() {
        if (productosStockBajo.length > 0) {
            const productos = productosStockBajo.map(p => ({
                nombre: p.nombre,
                categoria: p.categoria || 'Sin categoría',
                stock: p.stock
            }));
            mostrarAlertaStockBajo(productos);
        }
    }

    // Función para ver detalle de pedido (abre modal con detalles vía AJAX)
    function verDetallePedido(pedidoId) {
        // guardar id en variable global para impresión
        window.pedidoActual = pedidoId;
        // Asegurarse de que exista el modal en la página
        if (!document.getElementById('modalDetallePedido')) {
            const modalHtml = `
            <div class="modal fade" id="modalDetallePedido" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-clipboard-list me-2"></i> Detalle del Pedido</h5>
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
                            <button type="button" class="btn btn-primary" onclick="imprimirPedido()"><i class="fas fa-print"></i> Imprimir</button>
                        </div>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        const modalEl = document.getElementById('modalDetallePedido');
        const modal = new bootstrap.Modal(modalEl);
        const modalBody = document.getElementById('contenidoDetallePedido');

        // Mostrar loading
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;

        modal.show();

        // Cargar detalles via AJAX
        fetch('ajax/obtener-detalle-pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: pedidoId })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Reusar la función de mostrar detalle si existe, sino render simple
                    if (typeof mostrarDetallePedido === 'function') {
                        mostrarDetallePedido(data.pedido);
                    } else {
                        // fallback: render basic HTML
                        let html = '<h5>Pedido #' + (data.pedido.numero_pedido || data.pedido.id) + '</h5>';
                        html += '<p>Total: $' + (data.pedido.total || '') + '</p>';
                        html += '<pre>' + JSON.stringify(data.pedido, null, 2) + '</pre>';
                        modalBody.innerHTML = html;
                    }
                } else {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error: ${data.message || 'No se pudo cargar el pedido'}</div>`;
                }
            })
            .catch(err => {
                modalBody.innerHTML = `<div class="alert alert-danger">Error de conexión al cargar el pedido</div>`;
            });
    }

    // Renderizar el detalle del pedido dentro del modal (misma estructura que admin/pedidos.php)
    function mostrarDetallePedido(pedido) {
        const modalBody = document.getElementById('contenidoDetallePedido');
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
                        : '<div class="bg-light d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;"><i class="fas fa-image text-muted"></i></div>'}
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

        modalBody.innerHTML = html;
    }

    function imprimirPedido() {
        if (window.pedidoActual) {
            window.open(`ajax/imprimir-pedido.php?id=${window.pedidoActual}`, '_blank');
        }
    }

    // Auto-actualizar estadísticas cada 5 minutos
    setInterval(() => {
        // Actualizar solo las estadísticas numéricas sin recargar la página completa
        fetch('ajax/obtener-estadisticas-dashboard.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar los números en las tarjetas
                    document.querySelector('.fs-4.fw-bold').textContent = data.totalClientes;
                    // ... actualizar otros elementos
                }
            })
            .catch(error => console.error('Error actualizando estadísticas:', error));
    }, 300000); // 5 minutos

    // Mostrar notificación de bienvenida
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (isset($_GET['welcome']) && $_GET['welcome'] === '1'): ?>
            mostrarBienvenidaAdmin('<?php echo htmlspecialchars($_SESSION['nombre']); ?>');
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>