<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../models/Venta.php';

// Verificar autenticación y rol de vendedor
Auth::requireRole('vendedor');

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
    exit;
}

$pedidoId = (int) $_GET['id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener datos del pedido con método de pago
    $pedidoQuery = "SELECT v.*, u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono,
                           p.metodo as metodo_pago
                    FROM ventas v 
                    INNER JOIN usuarios u ON v.cliente_id = u.id 
                    LEFT JOIN pagos p ON v.id = p.venta_id
                    WHERE v.id = :id";
    $pedidoStmt = $conn->prepare($pedidoQuery);
    $pedidoStmt->bindValue(':id', $pedidoId, PDO::PARAM_INT);
    $pedidoStmt->execute();

    $pedido = $pedidoStmt->fetch(PDO::FETCH_ASSOC);
    if (!$pedido) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }

    // Obtener productos del pedido
    $productosQuery = "SELECT dv.*, p.nombre as nombre_producto 
                       FROM detalle_venta dv 
                       INNER JOIN productos p ON dv.producto_id = p.id 
                       WHERE dv.venta_id = :venta_id";
    $productosStmt = $conn->prepare($productosQuery);
    $productosStmt->bindValue(':venta_id', $pedidoId, PDO::PARAM_INT);
    $productosStmt->execute();

    $productos = $productosStmt->fetchAll(PDO::FETCH_ASSOC);

    // Construir HTML
    $html = '
    <div class="row">
        <div class="col-md-6">
            <h6>Información del Cliente</h6>
            <table class="table table-sm">
                <tr><td><strong>Nombre:</strong></td><td>' . htmlspecialchars($pedido['cliente_nombre']) . '</td></tr>
                <tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($pedido['cliente_email']) . '</td></tr>
                <tr><td><strong>Teléfono:</strong></td><td>' . htmlspecialchars($pedido['cliente_telefono'] ?? 'No especificado') . '</td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6>Información del Pedido</h6>
            <table class="table table-sm">
                <tr><td><strong>Número:</strong></td><td>#' . $pedido['id'] . '</td></tr>
                <tr><td><strong>Fecha:</strong></td><td>' . ($pedido['fecha'] ? date('d/m/Y H:i', strtotime($pedido['fecha'])) : 'N/A') . '</td></tr>
                <tr><td><strong>Estado:</strong></td><td>
                    <span class="badge ' . match ($pedido['estado']) {
        'pendiente' => 'bg-warning text-dark',
        'en_proceso' => 'bg-info',
        'enviado' => 'bg-primary',
        'entregado' => 'bg-success',
        'cancelado' => 'bg-danger',
        default => 'bg-secondary'
    } . '">' . ucfirst($pedido['estado']) . '</span>
                </td></tr>
                <tr><td><strong>Método de Pago:</strong></td><td>' . ($pedido['metodo_pago'] ? ucfirst($pedido['metodo_pago']) : 'No especificado') . '</td></tr>
            </table>
        </div>
    </div>
    
    <hr>
    
    <h6>Productos</h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead class="table-light">
                <tr>
                    <th>Producto</th>
                    <th>Precio Unit.</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>';

    $subtotal = 0;
    foreach ($productos as $producto) {
        $productoSubtotal = $producto['precio_unitario'] * $producto['cantidad'];
        $subtotal += $productoSubtotal;

        $html .= '
                <tr>
                    <td>' . htmlspecialchars($producto['nombre_producto']) . '</td>
                    <td>$' . number_format($producto['precio_unitario'], 2) . '</td>
                    <td>' . $producto['cantidad'] . '</td>
                    <td>$' . number_format($productoSubtotal, 2) . '</td>
                </tr>';
    }

    $html .= '
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3">Total:</th>
                    <th>$' . number_format($pedido['total'], 2) . '</th>
                </tr>
            </tfoot>
        </table>
    </div>';

    if (!empty($pedido['comentarios'])) {
        $html .= '
        <hr>
        <h6>Comentarios</h6>
        <p class="text-muted">' . htmlspecialchars($pedido['comentarios']) . '</p>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener detalles: ' . $e->getMessage()
    ]);
}
?>