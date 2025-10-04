<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';

// Verificar autenticación y rol de vendedor
Auth::requireRole('vendedor');

if (!isset($_GET['export']) || $_GET['export'] !== 'csv') {
    header('Location: ../pedidos.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener filtros de la URL
    $search = $_GET['search'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';

    // Construir consulta
    $whereConditions = ['1=1'];
    $params = [];

    if (!empty($search)) {
        $whereConditions[] = "(v.id LIKE :search OR u.nombre LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($estado)) {
        $whereConditions[] = "v.estado = :estado";
        $params[':estado'] = $estado;
    }

    if (!empty($fecha_desde)) {
        $whereConditions[] = "DATE(v.fecha) >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    }

    if (!empty($fecha_hasta)) {
        $whereConditions[] = "DATE(v.fecha) <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    }

    $whereClause = implode(' AND ', $whereConditions);

    $query = "SELECT v.id, v.fecha, v.total, v.estado, v.comentarios,
                     u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono,
                     p.metodo as metodo_pago
              FROM ventas v 
              INNER JOIN usuarios u ON v.cliente_id = u.id 
              LEFT JOIN pagos p ON v.id = p.venta_id
              WHERE $whereClause 
              ORDER BY v.fecha DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Configurar headers para descarga CSV
    $filename = 'pedidos_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    // Crear archivo CSV
    $output = fopen('php://output', 'w');

    // BOM para UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeceras del CSV
    fputcsv($output, [
        'ID Pedido',
        'Fecha',
        'Cliente',
        'Email',
        'Teléfono',
        'Total',
        'Estado',
        'Método Pago',
        'Comentarios'
    ]);

    // Datos
    foreach ($pedidos as $pedido) {
        fputcsv($output, [
            $pedido['id'],
            $pedido['fecha'] ? date('d/m/Y H:i', strtotime($pedido['fecha'])) : 'N/A',
            $pedido['cliente_nombre'],
            $pedido['cliente_email'],
            $pedido['cliente_telefono'] ?? '',
            '$' . number_format($pedido['total'], 2),
            ucfirst($pedido['estado']),
            $pedido['metodo_pago'] ? ucfirst($pedido['metodo_pago']) : 'No especificado',
            $pedido['comentarios'] ?? ''
        ]);
    }

    fclose($output);

} catch (Exception $e) {
    header('Location: ../pedidos.php?error=' . urlencode('Error al exportar: ' . $e->getMessage()));
    exit;
}
?>