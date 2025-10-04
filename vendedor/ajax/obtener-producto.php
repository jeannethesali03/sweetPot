<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';

// Verificar autenticación y rol de vendedor
Auth::requireRole('vendedor');

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
    exit;
}

$productId = (int) $_GET['id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p 
              INNER JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.id = :id AND p.estado = 'activo'";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto) {
        echo json_encode([
            'success' => true,
            'producto' => $producto
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener producto: ' . $e->getMessage()
    ]);
}
?>