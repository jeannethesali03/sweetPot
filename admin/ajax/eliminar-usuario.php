<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../models/Usuario.php';

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
        'message' => 'ID de usuario requerido'
    ]);
    exit();
}

$userId = (int) $input['id'];

// Verificar que no se está intentando eliminar a sí mismo
if ($userId == $_SESSION['user_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'No puedes eliminarte a ti mismo'
    ]);
    exit();
}

try {
    $usuarioModel = new Usuario();

    // Verificar que el usuario existe
    $usuario = $usuarioModel->obtenerPorId($userId);
    if (!$usuario) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
        exit();
    }

    // Verificar si es el último administrador
    if ($usuario['rol'] === 'admin') {
        $stats = $usuarioModel->obtenerEstadisticas();
        if (($stats['admin'] ?? 0) <= 1) {
            echo json_encode([
                'success' => false,
                'message' => 'No puedes eliminar el último administrador del sistema'
            ]);
            exit();
        }
    }

    // Eliminar usuario
    $result = $usuarioModel->eliminar($userId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar el usuario'
        ]);
    }

} catch (Exception $e) {
    error_log("Error al eliminar usuario: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>