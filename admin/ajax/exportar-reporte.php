<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';

// Verificar autenticación y rol de administrador
Auth::requireRole('admin');

if (!isset($_GET['accion'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener parámetros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo'] ?? 'ventas';
$accion = $_GET['accion'];

try {
    // Obtener datos según el tipo de reporte
    if ($tipo_reporte === 'ventas') {
        $query = "SELECT 
                    DATE(v.fecha) as fecha,
                    COUNT(DISTINCT v.id) as pedidos,
                    COALESCE(SUM(p.monto), 0) as ventas
                  FROM pagos p
                  INNER JOIN ventas v ON p.venta_id = v.id
                  WHERE p.estado = 'completado' 
                  AND DATE(v.fecha) BETWEEN ? AND ?
                  GROUP BY DATE(v.fecha)
                  ORDER BY fecha DESC";

    } elseif ($tipo_reporte === 'productos') {
        $query = "SELECT 
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
                  ORDER BY cantidad_vendida DESC";

    } elseif ($tipo_reporte === 'clientes') {
        $query = "SELECT 
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
                  ORDER BY total_gastado DESC";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute([$fecha_desde, $fecha_hasta]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($accion === 'excel') {
        // Crear archivo Excel con estilos usando HTML
        $filename = "reporte_" . $tipo_reporte . "_" . date('Y-m-d_H-i-s') . ".xls";
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Escribir BOM para UTF-8
        echo "\xEF\xBB\xBF";

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th { background-color: #8b4513; color: white; font-weight: bold; padding: 8px; border: 1px solid #333; text-align: center; }';
        echo 'td { padding: 6px; border: 1px solid #ccc; }';
        echo '.numero { text-align: right; }';
        echo '.titulo { background-color: #ff6b9d; color: white; font-size: 18px; font-weight: bold; text-align: center; padding: 10px; }';
        echo '.subtitulo { background-color: #f8f9fa; font-weight: bold; padding: 5px; text-align: center; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        echo '<table>';
        echo '<tr><td colspan="10" class="titulo">🧁 SweetPot Admin - Reporte de ' . ucfirst($tipo_reporte) . '</td></tr>';
        echo '<tr><td colspan="10" class="subtitulo">Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta)) . '</td></tr>';
        echo '<tr><td colspan="10" class="subtitulo">Generado: ' . date('d/m/Y H:i:s') . '</td></tr>';
        echo '<tr><td colspan="10">&nbsp;</td></tr>';

        if ($tipo_reporte === 'ventas') {
            echo '<tr>';
            echo '<th>Fecha</th>';
            echo '<th>Pedidos</th>';
            echo '<th>Ventas</th>';
            echo '</tr>';
            foreach ($datos as $fila) {
                echo '<tr>';
                echo '<td>' . date('d/m/Y', strtotime($fila['fecha'])) . '</td>';
                echo '<td class="numero">' . $fila['pedidos'] . '</td>';
                echo '<td class="numero">$' . number_format($fila['ventas'], 2) . '</td>';
                echo '</tr>';
            }
        } elseif ($tipo_reporte === 'productos') {
            echo '<tr>';
            echo '<th>Producto</th>';
            echo '<th>Cantidad Vendida</th>';
            echo '<th>Ingresos</th>';
            echo '</tr>';
            foreach ($datos as $fila) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($fila['nombre']) . '</td>';
                echo '<td class="numero">' . $fila['cantidad_vendida'] . '</td>';
                echo '<td class="numero">$' . number_format($fila['ingresos'], 2) . '</td>';
                echo '</tr>';
            }
        } elseif ($tipo_reporte === 'clientes') {
            echo '<tr>';
            echo '<th>Cliente</th>';
            echo '<th>Email</th>';
            echo '<th>Total Pedidos</th>';
            echo '<th>Total Gastado</th>';
            echo '</tr>';
            foreach ($datos as $fila) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($fila['nombre']) . '</td>';
                echo '<td>' . htmlspecialchars($fila['email']) . '</td>';
                echo '<td class="numero">' . $fila['total_pedidos'] . '</td>';
                echo '<td class="numero">$' . number_format($fila['total_gastado'], 2) . '</td>';
                echo '</tr>';
            }
        }

        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;

    } elseif ($accion === 'csv') {
        // Exportar CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="reporte_' . $tipo_reporte . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM para Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Encabezados según el tipo de reporte
        if ($tipo_reporte === 'ventas') {
            fputcsv($output, ['Fecha', 'Pedidos', 'Ventas'], ';');
            foreach ($datos as $fila) {
                fputcsv($output, [
                    date('d/m/Y', strtotime($fila['fecha'])),
                    $fila['pedidos'],
                    number_format($fila['ventas'], 2)
                ], ';');
            }
        } elseif ($tipo_reporte === 'productos') {
            fputcsv($output, ['Producto', 'Cantidad Vendida', 'Ingresos'], ';');
            foreach ($datos as $fila) {
                fputcsv($output, [
                    $fila['nombre'],
                    $fila['cantidad_vendida'],
                    number_format($fila['ingresos'], 2)
                ], ';');
            }
        } elseif ($tipo_reporte === 'clientes') {
            fputcsv($output, ['Cliente', 'Email', 'Total Pedidos', 'Total Gastado'], ';');
            foreach ($datos as $fila) {
                fputcsv($output, [
                    $fila['nombre'],
                    $fila['email'],
                    $fila['total_pedidos'],
                    number_format($fila['total_gastado'], 2)
                ], ';');
            }
        }

        fclose($output);
        exit;

    } elseif ($accion === 'pdf') {
        // Redirigir al archivo de impresión para PDF
        $params = http_build_query([
            'print' => '1',
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'tipo' => $tipo_reporte
        ]);

        header('Location: ../reporte-impresion.php?' . $params);
        exit;

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al exportar: ' . $e->getMessage()]);
}
?>