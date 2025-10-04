<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../models/Producto.php';

// Verificar autenticación y rol de admin
try {
    Auth::requireRole('admin');
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta acción'
    ]);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || empty($input['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de producto requerido'
    ]);
    exit();
}

$productoId = (int) $input['id'];

try {
    $productoModel = new Producto();

    // Verificar que el producto existe
    $producto = $productoModel->obtenerPorId($productoId);
    if (!$producto) {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
        exit();
    }

    // Nota: Como trabajamos con URLs, no necesitamos eliminar archivos físicos

    // Eliminar producto
    $result = $productoModel->eliminar($productoId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar el producto'
        ]);
    }

} catch (Exception $e) {
    error_log("Error al eliminar producto: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>