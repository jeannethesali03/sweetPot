<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../models/Categoria.php';

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
        'message' => 'ID de categoría requerido'
    ]);
    exit();
}

$categoriaId = (int) $input['id'];

try {
    $categoriaModel = new Categoria();

    // Verificar que la categoría existe
    $categoria = $categoriaModel->obtenerPorId($categoriaId);
    if (!$categoria) {
        echo json_encode([
            'success' => false,
            'message' => 'Categoría no encontrada'
        ]);
        exit();
    }

    // Verificar si tiene productos asociados
    $tieneProductos = $categoriaModel->tieneProductos($categoriaId);
    if ($tieneProductos) {
        echo json_encode([
            'success' => false,
            'message' => "No se puede eliminar la categoría porque tiene productos asociados"
        ]);
        exit();
    }

    // Eliminar categoría
    $result = $categoriaModel->eliminar($categoriaId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Categoría eliminada correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar la categoría'
        ]);
    }

} catch (Exception $e) {
    error_log("Error al eliminar categoría: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>