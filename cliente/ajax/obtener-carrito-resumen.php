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
    $resumen = $carrito->obtenerResumen($user['id']);

    echo json_encode([
        'success' => true,
        'data' => [
            'cantidad' => $resumen['total_items'],
            'total' => $resumen['total']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener resumen del carrito'
    ]);
}
?>