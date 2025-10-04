<?php
ob_start();
ob_clean();

require_once '../../config/config.php';
require_once '../../includes/Auth.php';
require_once '../../models/Carrito.php';
require_once '../../models/Producto.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar autenticación
if (!Auth::isLoggedIn() || Auth::getUser()['rol'] !== 'cliente') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener datos del JSON
$input = json_decode(file_get_contents('php://input'), true);
$user = Auth::getUser();

// Validar datos básicos
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos']);
    exit;
}

// Validar campos requeridos
$required_fields = ['nombre', 'email', 'telefono', 'direccion', 'metodo_pago'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
        exit;
    }
}

try {
    $carritoModel = new Carrito();
    $productoModel = new Producto();

    // Verificar carrito
    $items = $carritoModel->obtenerItems($user['id']);
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
        exit;
    }

    // Obtener resumen del carrito
    $resumen = $carritoModel->obtenerResumen($user['id']);

    // Verificar stock básico
    $verificacion = $carritoModel->verificarStock($user['id']);
    if (!$verificacion['tiene_stock']) {
        echo json_encode(['success' => false, 'message' => 'Sin stock suficiente']);
        exit;
    }

    // Crear venta en base de datos
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    // Insertar venta
    $stmt = $conn->prepare("
        INSERT INTO ventas (cliente_id, fecha, subtotal, impuestos, descuento, total, 
                           estado, direccion_entrega, comentarios) 
        VALUES (:cliente_id, NOW(), :subtotal, :impuestos, :descuento, :total, 
                'pendiente', :direccion, :comentarios)
    ");

    $stmt->execute([
        ':cliente_id' => $user['id'],
        ':subtotal' => $resumen['subtotal'],
        ':impuestos' => $resumen['impuestos'],
        ':descuento' => $resumen['descuento'],
        ':total' => $resumen['total'],
        ':direccion' => $input['direccion'],
        ':comentarios' => $input['comentarios'] ?? ''
    ]);

    $venta_id = $conn->lastInsertId();

    // Obtener número de pedido
    $stmt = $conn->prepare("SELECT numero_pedido FROM ventas WHERE id = :id");
    $stmt->execute([':id' => $venta_id]);
    $numero_pedido = $stmt->fetch(PDO::FETCH_ASSOC)['numero_pedido'];

    // Insertar detalles
    foreach ($items as $item) {
        $stmt = $conn->prepare("
            INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
            VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)
        ");

        $stmt->execute([
            ':venta_id' => $venta_id,
            ':producto_id' => $item['producto_id'],
            ':cantidad' => $item['cantidad'],
            ':precio_unitario' => $item['precio'],
            ':subtotal' => $item['subtotal']
        ]);
    }

    // Crear pago
    $stmt = $conn->prepare("INSERT INTO pagos (venta_id, metodo, monto, fecha, estado) VALUES (:venta_id, :metodo, :monto, NOW(), 'completado')");
    $stmt->execute([
        ':venta_id' => $venta_id,
        ':metodo' => $input['metodo_pago'],
        ':monto' => $resumen['total']
    ]);

    // Crear ticket
    $stmt = $conn->prepare("INSERT INTO tickets (venta_id, fecha, subtotal, impuestos, descuento, total, metodo_pago) VALUES (:venta_id, NOW(), :subtotal, :impuestos, :descuento, :total, :metodo_pago)");
    $stmt->execute([
        ':venta_id' => $venta_id,
        ':subtotal' => $resumen['subtotal'],
        ':impuestos' => $resumen['impuestos'],
        ':descuento' => $resumen['descuento'],
        ':total' => $resumen['total'],
        ':metodo_pago' => $input['metodo_pago']
    ]);

    $ticket_id = $conn->lastInsertId();

    // Obtener número de ticket
    $stmt = $conn->prepare("SELECT numero_ticket FROM tickets WHERE id = :id");
    $stmt->execute([':id' => $ticket_id]);
    $numero_ticket = $stmt->fetch(PDO::FETCH_ASSOC)['numero_ticket'];

    // No necesitamos generar QR aquí - se hace dinámicamente    // Vaciar carrito
    $carritoModel->vaciar($user['id']);

    // Confirmar transacción
    $conn->commit();

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Pedido procesado exitosamente',
        'numero_pedido' => $numero_pedido,
        'numero_ticket' => $numero_ticket,
        'venta_id' => $venta_id,
        'total' => $resumen['total']
    ]);
    exit;

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}
?>