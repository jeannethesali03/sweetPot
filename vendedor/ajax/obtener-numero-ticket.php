<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';

// Verificar autenticación y rol de vendedor
Auth::requireRole('vendedor');

header('Content-Type: application/json');

if (!isset($_GET['venta_id']) || !is_numeric($_GET['venta_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de venta inválido']);
    exit;
}

$ventaId = (int) $_GET['venta_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener el número de ticket basado en la venta
    $query = "SELECT numero_ticket FROM tickets WHERE venta_id = :venta_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':venta_id', $ventaId, PDO::PARAM_INT);
    $stmt->execute();

    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ticket) {
        echo json_encode([
            'success' => true,
            'numero_ticket' => $ticket['numero_ticket']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró ticket para este pedido'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener ticket: ' . $e->getMessage()
    ]);
}
?>