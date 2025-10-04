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

if (!$producto_id) {
    echo json_encode(['success' => false, 'message' => 'Producto inválido']);
    exit;
}

$user = Auth::getUser();
$carrito = new Carrito();

try {
    $resultado = $carrito->eliminar($user['id'], $producto_id);

    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Producto eliminado del carrito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar producto']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>