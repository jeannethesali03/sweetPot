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

    $ventaModel = new Venta();
    $pedido = $ventaModel->obtenerPorId($data['id']);

    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }

    echo json_encode([
        'success' => true,
        'pedido' => $pedido
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>