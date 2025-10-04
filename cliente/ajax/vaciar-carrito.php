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

$user = Auth::getUser();
$carrito = new Carrito();

try {
    $resultado = $carrito->vaciar($user['id']);

    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Carrito vaciado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al vaciar carrito']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>