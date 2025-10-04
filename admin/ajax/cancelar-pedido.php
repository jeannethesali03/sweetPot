<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../models/Venta.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

header('Content-Type: application/json');

try {
    // Verificar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }

    // Validar ID
    if (empty($data['id']) || !is_numeric($data['id'])) {
        throw new Exception('ID de pedido inválido');
    }

    $motivo = isset($data['motivo']) ? trim($data['motivo']) : 'Cancelado por el administrador';

    $ventaModel = new Venta();

    // Verificar que el pedido existe y se puede cancelar
    $pedido = $ventaModel->obtenerPorId($data['id']);
    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }

    if ($pedido['estado'] == 'cancelado') {
        throw new Exception('El pedido ya está cancelado');
    }

    if ($pedido['estado'] == 'entregado') {
        throw new Exception('No se puede cancelar un pedido ya entregado');
    }

    // Cancelar pedido (esto restaurará el stock automáticamente)
    $resultado = $ventaModel->cancelar($data['id'], $motivo);

    if (!$resultado) {
        throw new Exception('No se pudo cancelar el pedido');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Pedido cancelado correctamente. El stock se restauró automáticamente.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>