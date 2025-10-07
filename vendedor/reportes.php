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

// Obtener parámetros de fechas
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d'); // Hoy
$tipo_reporte = $_GET['tipo'] ?? 'ventas';

try {
    // Estadísticas generales - CORREGIDO: separar consultas para evitar conflictos de parámetros

    // Estadísticas de pedidos
    $pedidosQuery = "SELECT 
                        COUNT(*) as total_pedidos,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                        SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
                        SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
                        SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
                    FROM ventas 
                    WHERE DATE(fecha) BETWEEN ? AND ?";

    $pedidosStmt = $conn->prepare($pedidosQuery);
    $pedidosStmt->execute([$fecha_desde, $fecha_hasta]);
    $pedidosStats = $pedidosStmt->fetch(PDO::FETCH_ASSOC);

    // Estadísticas de pagos
    $pagosQuery = "SELECT 
                      COALESCE(SUM(p.monto), 0) as ventas_totales,
                      COALESCE(AVG(p.monto), 0) as ticket_promedio
                   FROM pagos p 
                   INNER JOIN ventas v ON p.venta_id = v.id 
                   WHERE p.estado = 'completado' 
                   AND DATE(v.fecha) BETWEEN ? AND ?";

    $pagosStmt = $conn->prepare($pagosQuery);
    $pagosStmt->execute([$fecha_desde, $fecha_hasta]);
    $pagosStats = $pagosStmt->fetch(PDO::FETCH_ASSOC);

    // Combinar estadísticas
    $stats = array_merge($pedidosStats, $pagosStats);

    // Datos específicos según el tipo de reporte
    $datos = [];
    $chartData = [];

    if ($tipo_reporte === 'ventas') {
        // Ventas por día - CORREGIDO: usar tabla pagos como principal
        $ventasDiariasQuery = "SELECT 
                                DATE(v.fecha) as fecha,
                                COUNT(DISTINCT v.id) as pedidos,
                                COALESCE(SUM(p.monto), 0) as ventas
                               FROM pagos p
                               INNER JOIN ventas v ON p.venta_id = v.id
                               WHERE p.estado = 'completado' 
                               AND DATE(v.fecha) BETWEEN ? AND ?
                               GROUP BY DATE(v.fecha)
                               ORDER BY fecha DESC";

        $ventasDiariasStmt = $conn->prepare($ventasDiariasQuery);
        $ventasDiariasStmt->execute([$fecha_desde, $fecha_hasta]);
        $datos = $ventasDiariasStmt->fetchAll(PDO::FETCH_ASSOC);

        // Datos para gráfica
        $chartData = array_reverse($datos); // Para mostrar cronológicamente

    } elseif ($tipo_reporte === 'productos') {
        // Productos más vendidos - CORREGIDO: usar pagos completados y incluir todos los productos
        $productosQuery = "SELECT 
                            p.nombre,
                            SUM(dv.cantidad) as cantidad_vendida,
                            SUM(dv.cantidad * dv.precio_unitario) as ingresos
                           FROM detalle_venta dv
                           INNER JOIN productos p ON dv.producto_id = p.id
                           INNER JOIN ventas v ON dv.venta_id = v.id
                           INNER JOIN pagos pg ON v.id = pg.venta_id
                           WHERE DATE(v.fecha) BETWEEN ? AND ?
                           AND pg.estado = 'completado'
                           GROUP BY p.id, p.nombre
                           ORDER BY cantidad_vendida DESC
                           LIMIT 10";

        $productosStmt = $conn->prepare($productosQuery);
        $productosStmt->execute([$fecha_desde, $fecha_hasta]);
        $datos = $productosStmt->fetchAll(PDO::FETCH_ASSOC);

        // Datos para gráfica
        $chartData = $datos;

    } elseif ($tipo_reporte === 'clientes') {
        // Clientes más activos - CORREGIDO: usar tabla pagos para totales reales
        $clientesQuery = "SELECT 
                            u.nombre,
                            u.email,
                            COUNT(DISTINCT v.id) as total_pedidos,
                            COALESCE(SUM(CASE WHEN pg.estado = 'completado' THEN pg.monto ELSE 0 END), 0) as total_gastado_periodo,
                            (SELECT COALESCE(SUM(CASE WHEN pg2.estado = 'completado' THEN pg2.monto ELSE 0 END), 0)
                             FROM ventas v2 
                             LEFT JOIN pagos pg2 ON v2.id = pg2.venta_id
                             WHERE v2.cliente_id = u.id) as total_gastado
                          FROM usuarios u
                          INNER JOIN ventas v ON u.id = v.cliente_id
                          LEFT JOIN pagos pg ON v.id = pg.venta_id
                          WHERE DATE(v.fecha) BETWEEN ? AND ?
                          GROUP BY u.id, u.nombre, u.email
                          ORDER BY total_gastado DESC
                          LIMIT 10";

        $clientesStmt = $conn->prepare($clientesQuery);
        $clientesStmt->execute([$fecha_desde, $fecha_hasta]);
        $datos = $clientesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Datos para gráfica
        $chartData = $datos;
    }

    // Métodos de pago - CORREGIDO: solo pagos completados
    $metodosQuery = "SELECT 
                        COALESCE(p.metodo, 'No especificado') as metodo_pago,
                        COUNT(*) as cantidad,
                        SUM(p.monto) as total
                     FROM pagos p
                     INNER JOIN ventas v ON p.venta_id = v.id
                     WHERE p.estado = 'completado'
                     AND DATE(v.fecha) BETWEEN ? AND ?
                     GROUP BY p.metodo
                     ORDER BY cantidad DESC";

    $metodosStmt = $conn->prepare($metodosQuery);
    $metodosStmt->execute([$fecha_desde, $fecha_hasta]);
    $metodos = $metodosStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Error al generar reporte: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - SweetPot Vendedor</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
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
                        <i class="fas fa-chart-bar me-2"></i>
                        Reportes de Ventas
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="imprimirReporte()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="exportarReporte()">
                                <i class="fas fa-download"></i> Exportar
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

                <!-- Filtros de fecha -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Fecha desde:</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                                    value="<?php echo htmlspecialchars($fecha_desde); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Fecha hasta:</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                                    value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo de reporte:</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="ventas" <?php echo $tipo_reporte == 'ventas' ? 'selected' : ''; ?>>
                                        Ventas</option>
                                    <option value="productos" <?php echo $tipo_reporte == 'productos' ? 'selected' : ''; ?>>Productos</option>
                                    <option value="clientes" <?php echo $tipo_reporte == 'clientes' ? 'selected' : ''; ?>>
                                        Clientes</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Generar Reporte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas generales -->
                <?php if (isset($stats)): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-shopping-cart fa-2x text-primary mb-2 p-1 py-2"
                                        style="border-radius: 5px;"></i>
                                    <h3><?php echo number_format($stats['total_pedidos'] ?? 0); ?></h3>
                                    <p class="text-muted mb-0">Total Pedidos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-dollar-sign fa-2x p-2 text-success mb-2"
                                        style="border-radius: 5px;"></i>
                                    <h3>$<?php echo number_format($stats['ventas_totales'] ?? 0, 2); ?></h3>
                                    <p class="text-muted mb-0">Ventas Totales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-receipt fa-2x text-info mb-2"></i>
                                    <h3>$<?php echo number_format($stats['ticket_promedio'] ?? 0, 2); ?></h3>
                                    <p class="text-muted mb-0">Ticket Promedio</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-check-circle fa-2x p-2 text-success mb-2"
                                        style="border-radius: 5px;"></i>
                                    <h3><?php echo number_format($stats['entregados'] ?? 0); ?></h3>
                                    <p class="text-muted mb-0">Entregados</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Gráfico de ventas diarias -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line me-2"></i>Ventas por Día</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="ventasDiariasChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estados de pedidos -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie me-2"></i>Estados de Pedidos</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="estadosChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenido dinámico según el tipo de reporte -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>
                                    <?php if ($tipo_reporte === 'ventas'): ?>
                                        <i class="fas fa-chart-line me-2"></i>Ventas por Día
                                    <?php elseif ($tipo_reporte === 'productos'): ?>
                                        <i class="fas fa-trophy me-2"></i>Productos Más Vendidos
                                    <?php elseif ($tipo_reporte === 'clientes'): ?>
                                        <i class="fas fa-star me-2"></i>Clientes Más Activos
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <?php if ($tipo_reporte === 'ventas'): ?>
                                                    <th>Fecha</th>
                                                    <th>Pedidos</th>
                                                    <th>Ventas</th>
                                                <?php elseif ($tipo_reporte === 'productos'): ?>
                                                    <th>#</th>
                                                    <th>Producto</th>
                                                    <th>Cantidad Vendida</th>
                                                    <th>Ingresos</th>
                                                <?php elseif ($tipo_reporte === 'clientes'): ?>
                                                    <th>#</th>
                                                    <th>Cliente</th>
                                                    <th>Total Pedidos</th>
                                                    <th>Total Gastado</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($datos)): ?>
                                                <?php foreach ($datos as $index => $fila): ?>
                                                    <tr>
                                                        <?php if ($tipo_reporte === 'ventas'): ?>
                                                            <td><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></td>
                                                            <td><span
                                                                    class="badge bg-primary"><?php echo $fila['pedidos']; ?></span>
                                                            </td>
                                                            <td class="fw-bold">
                                                                $<?php echo number_format($fila['ventas'] ?? 0, 2); ?></td>
                                                        <?php elseif ($tipo_reporte === 'productos'): ?>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                                                            <td><span
                                                                    class="badge bg-primary"><?php echo $fila['cantidad_vendida']; ?></span>
                                                            </td>
                                                            <td class="text-success fw-bold">
                                                                $<?php echo number_format($fila['ingresos'] ?? 0, 2); ?></td>
                                                        <?php elseif ($tipo_reporte === 'clientes'): ?>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($fila['nombre']); ?></strong>
                                                                    <br>
                                                                    <small
                                                                        class="text-muted"><?php echo htmlspecialchars($fila['email']); ?></small>
                                                                </div>
                                                            </td>
                                                            <td><span
                                                                    class="badge bg-info"><?php echo $fila['total_pedidos']; ?></span>
                                                            </td>
                                                            <td class="text-success fw-bold">
                                                                $<?php echo number_format($fila['total_gastado'] ?? 0, 2); ?></td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="<?php echo $tipo_reporte === 'ventas' ? '3' : '4'; ?>"
                                                        class="text-center text-muted">
                                                        No hay datos para mostrar en el período seleccionado
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Métodos de pago -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-credit-card me-2"></i>Métodos de Pago</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="metodosChart"></canvas>
                                </div>
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

    <script>
        // Datos para gráficos
        const datos = <?php echo json_encode($chartData); ?>;
        const stats = <?php echo json_encode($stats); ?>;
        const metodos = <?php echo json_encode($metodos); ?>;
        const tipoReporte = '<?php echo $tipo_reporte; ?>';

        // Gráfico principal dinámico
        const ctxVentas = document.getElementById('ventasDiariasChart').getContext('2d');

        if (tipoReporte === 'ventas') {
            // Gráfico de ventas diarias
            new Chart(ctxVentas, {
                type: 'line',
                data: {
                    labels: datos.map(item => new Date(item.fecha).toLocaleDateString('es-ES')),
                    datasets: [{
                        label: 'Ventas ($)',
                        data: datos.map(item => parseFloat(item.ventas || 0)),
                        borderColor: '#8b4513',
                        backgroundColor: 'rgba(139, 69, 19, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        } else if (tipoReporte === 'productos') {
            // Gráfico de productos más vendidos
            new Chart(ctxVentas, {
                type: 'bar',
                data: {
                    labels: datos.slice(0, 5).map(item => item.nombre),
                    datasets: [{
                        label: 'Cantidad Vendida',
                        data: datos.slice(0, 5).map(item => parseInt(item.cantidad_vendida || 0)),
                        backgroundColor: '#8b4513',
                        borderColor: '#6f42c1',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } else if (tipoReporte === 'clientes') {
            // Gráfico de clientes más activos
            new Chart(ctxVentas, {
                type: 'bar',
                data: {
                    labels: datos.slice(0, 5).map(item => item.nombre),
                    datasets: [{
                        label: 'Total Gastado ($)',
                        data: datos.slice(0, 5).map(item => parseFloat(item.total_gastado || 0)),
                        backgroundColor: '#28a745',
                        borderColor: '#20c997',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de estados
        const ctxEstados = document.getElementById('estadosChart').getContext('2d');
        new Chart(ctxEstados, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'En Proceso', 'Enviados', 'Entregados', 'Cancelados'],
                datasets: [{
                    data: [
                        stats.pendientes,
                        stats.en_proceso,
                        stats.enviados,
                        stats.entregados,
                        stats.cancelados
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#17a2b8',
                        '#6f42c1',
                        '#28a745',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de métodos de pago
        const ctxMetodos = document.getElementById('metodosChart').getContext('2d');
        new Chart(ctxMetodos, {
            type: 'bar',
            data: {
                labels: metodos.map(item => item.metodo_pago.charAt(0).toUpperCase() + item.metodo_pago.slice(1)),
                datasets: [{
                    label: 'Cantidad',
                    data: metodos.map(item => parseInt(item.cantidad)),
                    backgroundColor: '#8b4513',
                    borderColor: '#654321',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function imprimirReporte() {
            // Abrir página de impresión en nueva ventana
            const params = new URLSearchParams(window.location.search);
            params.set('print', '1');
            window.open('reporte-impresion.php?' + params.toString(), '_blank');
        }

        function exportarReporte() {
            // Mostrar opciones de exportación
            Swal.fire({
                title: 'Exportar Reporte',
                text: '¿En qué formato deseas exportar el reporte?',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                showConfirmButton: true,
                confirmButtonText: '�️ PDF (Imprimir)',
                denyButtonText: '📊 Excel (con estilos)',
                cancelButtonText: '📋 CSV (simple)',
                confirmButtonColor: '#8b4513',
                denyButtonColor: '#28a745',
                cancelButtonColor: '#17a2b8'
            }).then((result) => {
                const params = new URLSearchParams(window.location.search);

                if (result.isConfirmed) {
                    // PDF (página de impresión)
                    params.set('accion', 'pdf');
                    window.location.href = 'ajax/exportar-reporte.php?' + params.toString();
                } else if (result.isDenied) {
                    // Excel con estilos
                    params.set('accion', 'excel');
                    window.location.href = 'ajax/exportar-reporte.php?' + params.toString();
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // CSV simple
                    Swal.fire({
                        title: '¿Exportar como CSV?',
                        text: 'Se descargará un archivo CSV simple compatible con Excel',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '📋 Descargar CSV',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#17a2b8'
                    }).then((csvResult) => {
                        if (csvResult.isConfirmed) {
                            params.set('accion', 'csv');
                            window.location.href = 'ajax/exportar-reporte.php?' + params.toString();
                        }
                    });
                }
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