<?php
// Iniciar sesión antes que cualquier output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../includes/Auth.php';

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
        SELECT t.*, v.cliente_id, v.fecha as fecha_venta, v.estado as estado_venta,
               u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono,
               v.direccion_entrega, v.comentarios
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

    // Obtener detalles de la venta
    $stmt = $conn->prepare("
        SELECT dv.*, p.nombre as producto_nombre, p.descripcion as producto_descripcion,
               p.precio as precio_actual, p.imagen as producto_imagen
        FROM detalle_venta dv
        JOIN productos p ON dv.producto_id = p.id
        WHERE dv.venta_id = :venta_id
        ORDER BY p.nombre
    ");
    $stmt->execute([':venta_id' => $ticket['venta_id']]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo htmlspecialchars($numero_ticket); ?> - SweetPot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- SweetPot theme overrides -->
    <link rel="stylesheet" href="../assets/css/sweetpot-theme.css">
    <style>
        .ticket-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .ticket-header {
            background: linear-gradient(135deg, var(--sp-pink, #d36b7f) 0%, var(--sp-brown, #b86b46) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .ticket-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .ticket-date {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .ticket-body {
            padding: 2rem;
        }

        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }

        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-completado {
            background-color: #d1edff;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .product-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--sp-pink, #d36b7f);
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }

        .qr-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }

        .qr-code {
            max-width: 200px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .total-section {
            background: linear-gradient(135deg, var(--sp-pink, #d36b7f) 0%, var(--sp-brown, #b86b46) 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }

        .print-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .ticket-container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>

<body class="bg-light">
    <?php if (isset($error)): ?>
        <div class="container mt-5">
            <div class="alert alert-danger">
                <h4><i class="bi bi-exclamation-triangle"></i> Error</h4>
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="index.php" class="btn btn-primary">Volver al inicio</a>
            </div>
        </div>
    <?php else: ?>
        <div class="ticket-container">
            <!-- Header del ticket -->
            <div class="ticket-header">
                <div class="ticket-number">#<?php echo htmlspecialchars($ticket['numero_ticket']); ?></div>
                <div class="ticket-date"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha'])); ?></div>
                <div class="mt-3">
                    <span class="status-badge status-<?php echo $ticket['estado_venta']; ?>">
                        <?php echo ucfirst($ticket['estado_venta']); ?>
                    </span>
                </div>
            </div>

            <div class="ticket-body">
                <!-- Información del cliente -->
                <div class="info-section">
                    <h5><i class="bi bi-person-circle"></i> Información del Cliente</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($ticket['cliente_nombre']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['cliente_email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Teléfono:</strong>
                                <?php echo htmlspecialchars($ticket['cliente_telefono'] ?? 'No especificado'); ?></p>
                            <p><strong>Método de pago:</strong> <?php echo ucfirst($ticket['metodo_pago']); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($ticket['direccion_entrega'])): ?>
                        <p><strong>Dirección de entrega:</strong> <?php echo htmlspecialchars($ticket['direccion_entrega']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($ticket['comentarios'])): ?>
                        <p><strong>Comentarios:</strong> <?php echo htmlspecialchars($ticket['comentarios']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Productos -->
                <h5><i class="bi bi-bag-check"></i> Productos Pedidos</h5>
                <?php foreach ($detalles as $detalle): ?>
                    <div class="product-item">
                        <div class="row align-items-center">
                            <div class="col-2">
                                <?php if (!empty($detalle['producto_imagen'])): ?>
                                    <?php
                                    // Si la imagen es una URL completa, usarla directamente; si no, usar la ruta local
                                    $imagen_src = (strpos($detalle['producto_imagen'], 'http') === 0)
                                        ? $detalle['producto_imagen']
                                        : BASE_URL . '/assets/images/productos/' . $detalle['producto_imagen'];
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imagen_src); ?>"
                                        alt="<?php echo htmlspecialchars($detalle['producto_nombre']); ?>" class="product-image"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="product-image bg-secondary d-flex align-items-center justify-content-center"
                                        style="display: none;">
                                        <i class="bi bi-image text-white"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="product-image bg-secondary d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-6">
                                <h6 class="mb-1"><?php echo htmlspecialchars($detalle['producto_nombre']); ?></h6>
                                <small
                                    class="text-muted"><?php echo htmlspecialchars($detalle['producto_descripcion']); ?></small>
                            </div>
                            <div class="col-2 text-center">
                                <span class="badge bg-primary">x<?php echo $detalle['cantidad']; ?></span>
                            </div>
                            <div class="col-2 text-end">
                                <strong>$<?php echo number_format($detalle['subtotal'], 2); ?></strong>
                                <br>
                                <small class="text-muted">$<?php echo number_format($detalle['precio_unitario'], 2); ?>
                                    c/u</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Totales -->
                <div class="total-section">
                    <div class="row">
                        <div class="col-6 text-start">
                            <p class="mb-1">Subtotal: <strong>$<?php echo number_format($ticket['subtotal'], 2); ?></strong>
                            </p>
                            <?php if ($ticket['descuento'] > 0): ?>
                                <p class="mb-1">Descuento:
                                    <strong>-$<?php echo number_format($ticket['descuento'], 2); ?></strong>
                                </p>
                            <?php endif; ?>
                            <p class="mb-0">Impuestos:
                                <strong>$<?php echo number_format($ticket['impuestos'], 2); ?></strong>
                            </p>
                        </div>
                        <div class="col-6 text-end">
                            <h3>Total: <strong>$<?php echo number_format($ticket['total'], 2); ?></strong></h3>
                        </div>
                    </div>
                </div>

                <!-- Código QR -->
                <div class="qr-section">
                    <h5><i class="bi bi-qr-code"></i> Código QR del Ticket</h5>
                    <p class="text-muted mb-3">Escanea este código para acceder rápidamente a tu ticket</p>
                    <?php
                    // URL del ticket para el QR
                    $ticket_url = BASE_URL . "/cliente/ticket.php?numero=" . urlencode($ticket['numero_ticket']);
                    // URL del generador dinámico de QR
                    $qr_url = BASE_URL . "/generate-qr.php?data=" . urlencode($ticket_url) . "&size=200";
                    ?>
                    <img src="<?php echo $qr_url; ?>" alt="QR del ticket" class="qr-code"
                        onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM2Yzc1N2QiPlFSIE5vIERpc3BvbmlibGU8L3RleHQ+PC9zdmc+'">

                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>URL del ticket:</strong><br>
                            <code
                                style="font-size: 0.8em; word-break: break-all;"><?php echo htmlspecialchars($ticket_url); ?></code>
                        </small>
                    </div>
                </div> <!-- Botones de acción -->
                <div class="text-center mt-4 no-print">
                    <button onclick="window.print()" class="btn print-button me-3">
                        <i class="bi bi-printer"></i> Imprimir Ticket
                    </button>

                    <?php if (Auth::isLoggedIn() && Auth::getUser()['id'] == $ticket['cliente_id']): ?>
                        <a href="mis-pedidos.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Mis Pedidos
                        </a>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="bi bi-house"></i> Inicio
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Información adicional -->
                <div class="mt-4 text-center no-print">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Guarda este ticket como comprobante de tu pedido. Para cualquier consulta, contacta a SweetPot.
                    </small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>