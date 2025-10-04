<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';

// Verificar autenticación y rol de vendedor
Auth::requireRole('vendedor');

if (!isset($_GET['print'])) {
    header('Location: reportes.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener parámetros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
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
    if ($tipo_reporte === 'ventas') {
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

    } elseif ($tipo_reporte === 'productos') {
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
                           LIMIT 20";

        $productosStmt = $conn->prepare($productosQuery);
        $productosStmt->execute([$fecha_desde, $fecha_hasta]);
        $datos = $productosStmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tipo_reporte === 'clientes') {
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
                          LIMIT 20";

        $clientesStmt = $conn->prepare($clientesQuery);
        $clientesStmt->execute([$fecha_desde, $fecha_hasta]);
        $datos = $clientesStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $error = "Error al generar reporte: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de <?php echo ucfirst($tipo_reporte); ?> - SweetPot</title>
    <!-- Chart.js para gráficos en impresión -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #8b4513;
            padding-bottom: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #8b4513;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #8b4513;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .controls {
            margin-bottom: 20px;
            text-align: center;
        }

        .btn {
            background-color: #8b4513;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background-color: #654321;
        }

        .chart-container {
            width: 100%;
            height: 400px;
            margin: 20px 0;
            page-break-inside: avoid;
        }

        .chart-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <div class="controls no-print">
        <button class="btn" onclick="window.print()">🖨️ Imprimir</button>
        <a href="reportes.php" class="btn">← Volver a Reportes</a>
    </div>

    <div class="header">
        <div class="logo">🧁 SweetPot</div>
        <h2>Reporte de <?php echo ucfirst($tipo_reporte); ?></h2>
        <p>Periodo: <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> -
            <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?>
        </p>
        <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <?php if (isset($error)): ?>
        <div style="color: red; text-align: center; margin: 20px;">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php else: ?>
        <!-- Estadísticas generales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_pedidos'] ?? 0); ?></div>
                <div class="stat-label">Total Pedidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($stats['ventas_totales'] ?? 0, 2); ?></div>
                <div class="stat-label">Ventas Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($stats['ticket_promedio'] ?? 0, 2); ?></div>
                <div class="stat-label">Ticket Promedio</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['entregados'] ?? 0); ?></div>
                <div class="stat-label">Pedidos Entregados</div>
            </div>
        </div>

        <!-- Gráfico principal -->
        <div class="chart-section">
            <h3>
                <?php if ($tipo_reporte === 'ventas'): ?>
                    Gráfico de Ventas por Día
                <?php elseif ($tipo_reporte === 'productos'): ?>
                    Top 5 Productos Más Vendidos
                <?php elseif ($tipo_reporte === 'clientes'): ?>
                    Top 5 Clientes Más Activos
                <?php endif; ?>
            </h3>
            <div class="chart-container">
                <canvas id="mainChart"></canvas>
            </div>
        </div>

        <!-- Gráfico de Estados de Pedidos -->
        <div class="chart-section">
            <h3>Estados de Pedidos</h3>
            <div class="chart-container">
                <canvas id="estadosChart"></canvas>
            </div>
        </div>

        <!-- Datos específicos del reporte -->
        <?php if ($tipo_reporte === 'ventas' && !empty($datos)): ?>
            <h3>Detalle de Ventas por Día</h3>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Pedidos</th>
                        <th class="text-right">Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $fila): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></td>
                            <td><?php echo $fila['pedidos']; ?></td>
                            <td class="text-right">$<?php echo number_format($fila['ventas'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($tipo_reporte === 'productos' && !empty($datos)): ?>
            <h3>Productos Más Vendidos</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $index => $fila): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                            <td class="text-right"><?php echo $fila['cantidad_vendida']; ?></td>
                            <td class="text-right">$<?php echo number_format($fila['ingresos'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($tipo_reporte === 'clientes' && !empty($datos)): ?>
            <h3>Clientes Más Activos</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th class="text-right">Pedidos</th>
                        <th class="text-right">Total Gastado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $index => $fila): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($fila['email']); ?></td>
                            <td class="text-right"><?php echo $fila['total_pedidos']; ?></td>
                            <td class="text-right">$<?php echo number_format($fila['total_gastado'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <div class="footer">
        <p>SweetPot - Sistema de Gestión de Ventas</p>
        <p>Reporte generado automáticamente el <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <script>
        // Datos para gráficos
        const datos = <?php echo json_encode($datos ?? []); ?>;
        const stats = <?php echo json_encode($stats ?? []); ?>;
        const tipoReporte = '<?php echo $tipo_reporte; ?>';

        // Generar gráfico principal
        const ctx = document.getElementById('mainChart').getContext('2d');

        if (tipoReporte === 'ventas' && datos.length > 0) {
            // Gráfico de ventas por día
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: datos.slice().reverse().map(item => new Date(item.fecha).toLocaleDateString('es-ES')),
                    datasets: [{
                        label: 'Ventas ($)',
                        data: datos.slice().reverse().map(item => parseFloat(item.ventas || 0)),
                        borderColor: '#8b4513',
                        backgroundColor: 'rgba(139, 69, 19, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Evolución de Ventas'
                        }
                    },
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
        } else if (tipoReporte === 'productos' && datos.length > 0) {
            // Gráfico de productos más vendidos
            const top5 = datos.slice(0, 5);
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top5.map(item => item.nombre),
                    datasets: [{
                        label: 'Cantidad Vendida',
                        data: top5.map(item => parseInt(item.cantidad_vendida || 0)),
                        backgroundColor: '#8b4513',
                        borderColor: '#6f42c1',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Top 5 Productos Más Vendidos'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } else if (tipoReporte === 'clientes' && datos.length > 0) {
            // Gráfico de clientes más activos
            const top5 = datos.slice(0, 5);
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top5.map(item => item.nombre),
                    datasets: [{
                        label: 'Total Gastado ($)',
                        data: top5.map(item => parseFloat(item.total_gastado || 0)),
                        backgroundColor: '#28a745',
                        borderColor: '#20c997',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Top 5 Clientes Más Activos'
                        }
                    },
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

        // Gráfico de Estados de Pedidos (siempre se muestra)
        const ctxEstados = document.getElementById('estadosChart').getContext('2d');
        new Chart(ctxEstados, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'En Proceso', 'Enviados', 'Entregados', 'Cancelados'],
                datasets: [{
                    data: [
                        parseInt(stats.pendientes || 0),
                        parseInt(stats.en_proceso || 0),
                        parseInt(stats.enviados || 0),
                        parseInt(stats.entregados || 0),
                        parseInt(stats.cancelados || 0)
                    ],
                    backgroundColor: [
                        '#ffc107', // Amarillo para pendientes
                        '#17a2b8', // Azul para en proceso
                        '#6f42c1', // Púrpura para enviados
                        '#28a745', // Verde para entregados
                        '#dc3545'  // Rojo para cancelados
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    title: {
                        display: true,
                        text: 'Distribución de Estados de Pedidos'
                    }
                }
            }
        });

        // Auto-imprimir cuando se carga la página (opcional)
        // setTimeout(() => { window.print(); }, 1000);
    </script>
</body>

</html>