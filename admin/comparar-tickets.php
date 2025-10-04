<?php
/**
 * Página de comparación de tickets - SweetPot
 */

require_once '../config/config.php';
require_once '../config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener un ticket de ejemplo
    $stmt = $conn->prepare("SELECT numero_ticket, total FROM tickets ORDER BY fecha DESC LIMIT 1");
    $stmt->execute();
    $ticket_ejemplo = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparación de Tickets - SweetPot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>
                            Comparación de Estilos de Ticket
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php elseif (!$ticket_ejemplo): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                No se encontraron tickets para mostrar.
                                <a href="../cliente/pago.php">Crear una compra primero</a>.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <!-- Ticket Simple (Para Imprimir) -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <i class="fas fa-print me-2"></i>
                                                Ticket Simple (Para Imprimir)
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-3">
                                                <strong>Características:</strong>
                                            </p>
                                            <ul class="list-unstyled">
                                                <li>✅ Diseño minimalista</li>
                                                <li>✅ Solo información esencial</li>
                                                <li>✅ Optimizado para impresión</li>
                                                <li>✅ QR pequeño incluido</li>
                                                <li>✅ Sin imágenes de productos</li>
                                                <li>✅ Cliente solo nombre</li>
                                            </ul>

                                            <div class="d-grid gap-2 mt-4">
                                                <a href="ticket-simple.php?numero=<?php echo urlencode($ticket_ejemplo['numero_ticket']); ?>"
                                                    target="_blank" class="btn btn-success">
                                                    <i class="fas fa-eye me-2"></i>
                                                    Ver Ticket Simple
                                                </a>

                                                <a href="ticket-simple.php?numero=<?php echo urlencode($ticket_ejemplo['numero_ticket']); ?>"
                                                    target="_blank" onclick="setTimeout(() => window.print(), 500)"
                                                    class="btn btn-outline-success">
                                                    <i class="fas fa-print me-2"></i>
                                                    Abrir e Imprimir
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ticket Detallado -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">
                                                <i class="fas fa-desktop me-2"></i>
                                                Ticket Detallado (Para QR)
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-3">
                                                <strong>Características:</strong>
                                            </p>
                                            <ul class="list-unstyled">
                                                <li>✅ Diseño completo con estilos</li>
                                                <li>✅ Información detallada</li>
                                                <li>✅ Imágenes de productos</li>
                                                <li>✅ QR grande</li>
                                                <li>✅ Datos completos del cliente</li>
                                                <li>✅ Método de pago y dirección</li>
                                            </ul>

                                            <div class="d-grid gap-2 mt-4">
                                                <a href="ticket.php?numero=<?php echo urlencode($ticket_ejemplo['numero_ticket']); ?>"
                                                    target="_blank" class="btn btn-info">
                                                    <i class="fas fa-eye me-2"></i>
                                                    Ver Ticket Detallado
                                                </a>

                                                <button type="button" class="btn btn-outline-info"
                                                    onclick="mostrarQR('<?php echo $ticket_ejemplo['numero_ticket']; ?>')">
                                                    <i class="fas fa-qrcode me-2"></i>
                                                    Mostrar QR
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del ticket de prueba -->
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6><i class="fas fa-info-circle text-info me-2"></i>Ticket de Prueba:</h6>
                                <p class="mb-2">
                                    <strong>Número:</strong>
                                    <?php echo htmlspecialchars($ticket_ejemplo['numero_ticket']); ?><br>
                                    <strong>Total:</strong> $<?php echo number_format($ticket_ejemplo['total'], 2); ?>
                                </p>

                                <h6 class="mt-3"><i class="fas fa-flow-chart text-primary me-2"></i>Flujo del Sistema:</h6>
                                <ol class="mb-0">
                                    <li><strong>Compra completada</strong> → Usuario ve <em>ticket simple</em> (para
                                        imprimir)</li>
                                    <li><strong>QR escaneado</strong> → Lleva al <em>ticket detallado</em> (con todos los
                                        datos)</li>
                                    <li><strong>Lista de pedidos</strong> → Enlaces a <em>ticket simple</em> por defecto
                                    </li>
                                </ol>
                            </div>

                            <!-- QR Preview (oculto inicialmente) -->
                            <div id="qr-preview" class="mt-4 text-center" style="display: none;">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0">
                                            <i class="fas fa-qrcode me-2"></i>
                                            QR del Ticket (escanea para ver detalles)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <img id="qr-image" src="" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                                        <p class="mt-3 small text-muted">
                                            Este QR lleva al ticket detallado, no al simple
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function mostrarQR(numeroTicket) {
            const baseURL = '<?php echo BASE_URL; ?>';
            const ticketURL = baseURL + '/cliente/ticket.php?numero=' + encodeURIComponent(numeroTicket);
            const qrURL = baseURL + '/generate-qr.php?data=' + encodeURIComponent(ticketURL) + '&size=200';

            document.getElementById('qr-image').src = qrURL;
            document.getElementById('qr-preview').style.display = 'block';

            // Scroll suave al QR
            document.getElementById('qr-preview').scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>