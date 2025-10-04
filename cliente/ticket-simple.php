<?php
// Iniciar sesión antes que cualquier output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/Database.php';

// Verificar parámetros
if (!isset($_GET['numero']) || empty($_GET['numero'])) {
    header('Location: index.php');
    exit;
}

$numero_ticket = $_GET['numero'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener información del ticket
    $stmt = $conn->prepare("
        SELECT t.*, v.cliente_id, v.fecha as fecha_venta, v.total as total_venta,
               u.nombre as cliente_nombre
        FROM tickets t
        JOIN ventas v ON t.venta_id = v.id
        JOIN usuarios u ON v.cliente_id = u.id
        WHERE t.numero_ticket = :numero_ticket
    ");
    $stmt->execute([':numero_ticket' => $numero_ticket]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        throw new Exception('Ticket no encontrado');
    }

    // Obtener productos (solo nombres y cantidades)
    $stmt = $conn->prepare("
        SELECT dv.cantidad, dv.precio_unitario, p.nombre as producto_nombre
        FROM detalle_venta dv
        JOIN productos p ON dv.producto_id = p.id
        WHERE dv.venta_id = :venta_id
        ORDER BY p.nombre
    ");
    $stmt->execute([':venta_id' => $ticket['venta_id']]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_mensaje = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket <?php echo htmlspecialchars($numero_ticket); ?> - SweetPot</title>
    <style>
        /* Estilos minimalistas para impresión */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            max-width: 300px;
            margin: 0 auto;
            padding: 10px;
        }

        .ticket-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .ticket-header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .ticket-info {
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }

        .ticket-info div {
            margin-bottom: 2px;
        }

        .productos {
            margin-bottom: 15px;
        }

        .producto-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }

        .producto-nombre {
            flex: 1;
            margin-right: 10px;
        }

        .producto-precio {
            white-space: nowrap;
        }

        .total-section {
            border-top: 2px solid #000;
            padding-top: 8px;
            text-align: right;
            font-weight: bold;
            font-size: 14px;
        }

        .qr-section {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }

        .qr-code {
            width: 80px;
            height: 80px;
            margin: 5px 0;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
            color: #666;
        }

        /* Ocultar botones en impresión */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                max-width: none;
                margin: 0;
            }
        }

        .no-print {
            text-align: center;
            margin-top: 15px;
        }

        .btn {
            background: #8b4513;
            color: white;
            border: none;
            padding: 8px 16px;
            margin: 5px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            cursor: pointer;
        }

        .btn:hover {
            background: #654321;
        }

        .btn-outline {
            background: white;
            color: #8b4513;
            border: 1px solid #8b4513;
        }

        .btn-outline:hover {
            background: #8b4513;
            color: white;
        }
    </style>
</head>

<body>
    <?php if (isset($error_mensaje)): ?>
        <div style="text-align: center; padding: 20px; color: #dc3545;">
            <strong>Error:</strong> <?php echo htmlspecialchars($error_mensaje); ?>
        </div>
        <div class="no-print">
            <a href="../index.php" class="btn btn-outline">Volver al Inicio</a>
        </div>
    <?php else: ?>

        <!-- Header del ticket -->
        <div class="ticket-header">
            <h1>SWEETPOT</h1>
            <div>Ticket de Compra</div>
        </div>

        <!-- Información del ticket -->
        <div class="ticket-info">
            <div><strong>Ticket:</strong> <?php echo htmlspecialchars($ticket['numero_ticket']); ?></div>
            <div><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_venta'])); ?></div>
            <div><strong>Cliente:</strong> <?php echo htmlspecialchars($ticket['cliente_nombre']); ?></div>
        </div>

        <!-- Productos -->
        <div class="productos">
            <div style="font-weight: bold; margin-bottom: 5px; border-bottom: 1px solid #000; padding-bottom: 3px;">
                PRODUCTOS
            </div>
            <?php foreach ($productos as $producto): ?>
                <div class="producto-item">
                    <div class="producto-nombre">
                        <?php echo htmlspecialchars($producto['producto_nombre']); ?>
                        <br><small><?php echo $producto['cantidad']; ?> x
                            $<?php echo number_format($producto['precio_unitario'], 2); ?></small>
                    </div>
                    <div class="producto-precio">
                        $<?php echo number_format($producto['cantidad'] * $producto['precio_unitario'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Total -->
        <div class="total-section">
            TOTAL: $<?php echo number_format($ticket['total_venta'], 2); ?>
        </div>

        <!-- QR Code -->
        <div class="qr-section">
            <div style="font-size: 10px; margin-bottom: 5px;">Escanea para ver detalles:</div>
            <?php
            // URL que apunta al ticket detallado
            $ticket_detalle_url = BASE_URL . "/cliente/ticket.php?numero=" . urlencode($ticket['numero_ticket']);
            $qr_url = BASE_URL . "/generate-qr.php?data=" . urlencode($ticket_detalle_url) . "&size=80";
            ?>
            <img src="<?php echo $qr_url; ?>" alt="QR Ticket" class="qr-code">
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>¡Gracias por tu compra!</div>
            <div>www.sweetpot.com</div>
        </div>

        <!-- Botones de acción (no se imprimen) -->
        <div class="no-print">
            <button onclick="window.print()" class="btn">
                🖨️ Imprimir
            </button>
            <a href="ticket.php?numero=<?php echo urlencode($numero_ticket); ?>" class="btn btn-outline">
                📄 Ver Detalles
            </a>
        </div>

    <?php endif; ?>
</body>

</html>