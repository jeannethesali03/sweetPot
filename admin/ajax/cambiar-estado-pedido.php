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

    // Validar datos requeridos
    if (empty($data['id']) || !is_numeric($data['id'])) {
        throw new Exception('ID de pedido inválido');
    }

    if (empty($data['estado'])) {
        throw new Exception('Estado requerido');
    }

    // Validar estado
    $estadosValidos = ['pendiente', 'en_proceso', 'enviado', 'entregado', 'cancelado'];
    if (!in_array($data['estado'], $estadosValidos)) {
        throw new Exception('Estado inválido');
    }

    $ventaModel = new Venta();

    // Verificar que el pedido existe
    $pedido = $ventaModel->obtenerPorId($data['id']);
    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }

    // Cambiar estado
    $resultado = $ventaModel->cambiarEstado($data['id'], $data['estado']);

    if (!$resultado) {
        throw new Exception('No se pudo cambiar el estado del pedido');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Estado del pedido actualizado correctamente',
        'nuevo_estado' => $data['estado']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>