<?php
require_once '../../config/config.php';
require_once '../../includes/Auth.php';
require_once '../../models/Carrito.php';

header('Content-Type: application/json');

// Verificar autenticación y rol
if (!Auth::isLoggedIn() || Auth::getUser()['rol'] !== 'cliente') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener datos del JSON
$input = json_decode(file_get_contents('php://input'), true);
$producto_id = intval($input['producto_id'] ?? 0);
$cantidad = intval($input['cantidad'] ?? 1);

if (!$producto_id || $cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$user = Auth::getUser();
$carrito = new Carrito();

try {
    $resultado = $carrito->actualizarCantidad($user['id'], $producto_id, $cantidad);

    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Cantidad actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar cantidad']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>