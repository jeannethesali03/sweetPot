<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';

// Verificar autenticación y rol de vendedor
Auth::requireRole('vendedor');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$pedidoId = $_POST['pedido_id'] ?? null;
$nuevoEstado = $_POST['nuevo_estado'] ?? null;
$comentario = $_POST['comentario'] ?? '';

if (!$pedidoId || !$nuevoEstado) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Verificar que el pedido existe
    $checkQuery = "SELECT id, estado FROM ventas WHERE id = :id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindValue(':id', $pedidoId, PDO::PARAM_INT);
    $checkStmt->execute();

    $pedido = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$pedido) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }

    // Actualizar estado
    $updateQuery = "UPDATE ventas SET estado = :estado, fecha_actualizacion = NOW() WHERE id = :id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindValue(':estado', $nuevoEstado);
    $updateStmt->bindValue(':id', $pedidoId, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        // Registrar el cambio en el historial si hay comentario
        if (!empty($comentario)) {
            $historialQuery = "INSERT INTO historial_pedidos (venta_id, estado_anterior, estado_nuevo, comentario, usuario_id, fecha_cambio) 
                              VALUES (:venta_id, :estado_anterior, :estado_nuevo, :comentario, :usuario_id, NOW())";
            $historialStmt = $conn->prepare($historialQuery);
            $historialStmt->bindValue(':venta_id', $pedidoId, PDO::PARAM_INT);
            $historialStmt->bindValue(':estado_anterior', $pedido['estado']);
            $historialStmt->bindValue(':estado_nuevo', $nuevoEstado);
            $historialStmt->bindValue(':comentario', $comentario);
            $historialStmt->bindValue(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $historialStmt->execute();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el estado'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>