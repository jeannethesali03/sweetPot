<?php
require_once '../../config/config.php';
require_once '../../includes/Auth.php';
require_once '../../models/Carrito.php';
require_once '../../models/Producto.php';

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

$user = Auth::getUser();

if (!$producto_id || $cantidad <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos inválidos'
    ]);
    exit;
}

$carrito = new Carrito();
$productoModel = new Producto();

try {
    // Verificar que el producto existe y está activo
    $producto = $productoModel->obtenerPorId($producto_id);

    if (!$producto) {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
        exit;
    }

    // Verificar que el producto esté activo
    if ($producto['estado'] !== 'activo') {
        echo json_encode([
            'success' => false,
            'message' => 'Este producto no está disponible'
        ]);
        exit;
    }

    // Verificar stock suficiente
    if ($producto['stock'] < $cantidad) {
        echo json_encode([
            'success' => false,
            'message' => 'Stock insuficiente. Solo quedan ' . $producto['stock'] . ' unidades'
        ]);
        exit;
    }

    $resultado = $carrito->agregar($user['id'], $producto_id, $cantidad);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado al carrito correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al agregar producto al carrito'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>