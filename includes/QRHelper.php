<?php
/**
 * Helper para generar URLs de QR - SweetPot
 */

class QRHelper
{
    private static function getBaseURL()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);
        return $protocol . $host . $path;
    }

    /**
     * Genera URL para QR de producto
     */
    public static function getProductoQR($producto_id, $size = 200)
    {
        $base = self::getBaseURL();
        return $base . "/generate-qr.php?type=producto&id=" . $producto_id . "&size=" . $size;
    }

    /**
     * Genera URL para QR de ticket
     */
    public static function getTicketQR($ticket_id, $size = 200)
    {
        $base = self::getBaseURL();
        return $base . "/generate-qr.php?type=ticket&id=" . $ticket_id . "&size=" . $size;
    }

    /**
     * Genera QR personalizado con data específica
     */
    public static function getCustomQR($data, $size = 200)
    {
        $base = self::getBaseURL();
        return $base . "/generate-qr.php?data=" . urlencode($data) . "&size=" . $size;
    }

    /**
     * Obtiene la URL directa del producto
     */
    public static function getProductoURL($producto_id)
    {
        $base = self::getBaseURL();
        return $base . "/producto.php?id=" . $producto_id;
    }

    /**
     * Obtiene la URL directa del ticket
     */
    public static function getTicketURL($ticket_id)
    {
        $base = self::getBaseURL();
        return $base . "/cliente/ticket.php?id=" . $ticket_id;
    }
}

// Ejemplo de uso:
if (basename($_SERVER['PHP_SELF']) === 'qr-helper.php') {
    header('Content-Type: application/json');

    $producto_id = $_GET['producto_id'] ?? null;
    $ticket_id = $_GET['ticket_id'] ?? null;

    if ($producto_id) {
        echo json_encode([
            'qr_url' => QRHelper::getProductoQR($producto_id),
            'producto_url' => QRHelper::getProductoURL($producto_id)
        ]);
    } elseif ($ticket_id) {
        echo json_encode([
            'qr_url' => QRHelper::getTicketQR($ticket_id),
            'ticket_url' => QRHelper::getTicketURL($ticket_id)
        ]);
    } else {
        echo json_encode(['error' => 'Parámetros requeridos: producto_id o ticket_id']);
    }
}
?>