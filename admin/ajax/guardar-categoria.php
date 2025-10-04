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

try {
    $categoriaModel = new Categoria();

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $activo = (int) ($_POST['activo'] ?? 1);
    $id = (int) ($_POST['id'] ?? 0);

    // Validaciones
    if (empty($nombre)) {
        echo json_encode([
            'success' => false,
            'message' => 'El nombre es obligatorio'
        ]);
        exit();
    }

    $datos = [
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'activo' => $activo
    ];

    if ($id > 0) {
        // Actualizar categoría existente
        $result = $categoriaModel->actualizar($id, $datos);
        $message = 'Categoría actualizada correctamente';
    } else {
        // Crear nueva categoría
        $result = $categoriaModel->crear($datos);
        $message = 'Categoría creada correctamente';
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la categoría'
        ]);
    }

} catch (Exception $e) {
    error_log("Error al guardar categoría: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>