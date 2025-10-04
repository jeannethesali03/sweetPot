<?php
// Herramienta para generar QR manualmente para tickets existentes
require_once '../config/config.php';

$numero_ticket = $_GET['numero'] ?? '';

if (!$numero_ticket) {
    die('Especifica el número de ticket: ?numero=TK-00000005');
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener ticket
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE numero_ticket = :numero_ticket");
    $stmt->execute([':numero_ticket' => $numero_ticket]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die('Ticket no encontrado');
    }

    echo "<h2>Generando QR para ticket: $numero_ticket</h2>";

    // Generar QR
    require_once '../includes/qr_utils.php';
    $qr_utils = new QRUtils();
    $result = $qr_utils->generarQRTicket($ticket['id'], $ticket['numero_ticket']);

    echo "<h3>Resultado:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";

    if ($result['success']) {
        // Actualizar en BD
        $stmt = $conn->prepare("UPDATE tickets SET qr_code = :qr_code WHERE id = :id");
        $stmt->execute([
            ':qr_code' => $result['archivo'],
            ':id' => $ticket['id']
        ]);

        echo "<h3>✅ QR generado y guardado exitosamente!</h3>";
        echo "<p>Archivo: " . $result['archivo'] . "</p>";
        echo "<p>URL: " . $result['url'] . "</p>";

        // Mostrar imagen
        $img_path = '../assets/qr/' . $result['archivo'];
        if (file_exists($img_path)) {
            echo "<h3>Imagen generada:</h3>";
            echo "<img src='../assets/qr/{$result['archivo']}' style='max-width: 300px; border: 1px solid #ccc;'>";
            echo "<p>Tamaño del archivo: " . filesize($img_path) . " bytes</p>";
        } else {
            echo "<p>❌ El archivo no se creó correctamente</p>";
        }

    } else {
        echo "<h3>❌ Error generando QR:</h3>";
        echo "<p>" . $result['error'] . "</p>";
    }

} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<br><br><a href='ticket.php?numero=$numero_ticket'>Ver Ticket</a>";
?>