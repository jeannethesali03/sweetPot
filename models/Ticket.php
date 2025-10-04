<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/qr_utils.php';

class Ticket
{
    private $db;
    private $conn;
    private $qr_utils;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->qr_utils = new QRUtils();
    }

    /**
     * Generar ticket para una venta
     */
    public function generar($venta_id, $metodo_pago = 'efectivo')
    {
        try {
            // Obtener datos de la venta
            require_once __DIR__ . '/Pedido.php';
            $pedido = new Pedido();
            $datos_venta = $pedido->obtenerPorId($venta_id);

            if (!$datos_venta) {
                return false;
            }

            // Crear el ticket
            $query = "INSERT INTO tickets (venta_id, subtotal, impuestos, descuento, total, metodo_pago) 
                     VALUES (:venta_id, :subtotal, :impuestos, :descuento, :total, :metodo_pago)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':venta_id', $venta_id);
            $stmt->bindParam(':subtotal', $datos_venta['subtotal']);
            $stmt->bindParam(':impuestos', $datos_venta['impuestos']);
            $stmt->bindParam(':descuento', $datos_venta['descuento']);
            $stmt->bindParam(':total', $datos_venta['total']);
            $stmt->bindParam(':metodo_pago', $metodo_pago);

            if ($stmt->execute()) {
                $ticket_id = $this->conn->lastInsertId();

                // Obtener el número de ticket generado por el trigger
                $ticket_data = $this->obtenerPorId($ticket_id);

                if ($ticket_data) {
                    // Generar QR para el ticket
                    $qr_result = $this->qr_utils->generarQRTicket($ticket_id, $ticket_data['numero_ticket']);

                    if ($qr_result['success']) {
                        // Actualizar ticket con QR
                        $this->actualizarQR($ticket_id, $qr_result['archivo']);
                    }

                    return $ticket_id;
                }
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error al generar ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ticket por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $query = "SELECT t.*, v.numero_pedido, v.fecha as fecha_venta, v.direccion_entrega, v.comentarios,
                     u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email, 
                     u_cliente.telefono as cliente_telefono, u_cliente.direccion as cliente_direccion,
                     u_vendedor.nombre as vendedor_nombre
                     FROM tickets t
                     INNER JOIN ventas v ON t.venta_id = v.id
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     WHERE t.id = :id LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ticket) {
                // Obtener productos del ticket
                require_once __DIR__ . '/Pedido.php';
                $pedido = new Pedido();
                $ticket['productos'] = $pedido->obtenerDetallesPedido($ticket['venta_id']);
            }

            return $ticket;

        } catch (PDOException $e) {
            error_log("Error al obtener ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ticket por número
     */
    public function obtenerPorNumero($numero_ticket)
    {
        try {
            $query = "SELECT t.*, v.numero_pedido, v.fecha as fecha_venta, v.direccion_entrega, v.comentarios,
                     u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email,
                     u_cliente.telefono as cliente_telefono, u_cliente.direccion as cliente_direccion,
                     u_vendedor.nombre as vendedor_nombre
                     FROM tickets t
                     INNER JOIN ventas v ON t.venta_id = v.id
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     WHERE t.numero_ticket = :numero_ticket LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':numero_ticket', $numero_ticket);
            $stmt->execute();

            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ticket) {
                // Obtener productos del ticket
                require_once __DIR__ . '/Pedido.php';
                $pedido = new Pedido();
                $ticket['productos'] = $pedido->obtenerDetallesPedido($ticket['venta_id']);
            }

            return $ticket;

        } catch (PDOException $e) {
            error_log("Error al obtener ticket por número: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ticket por venta ID
     */
    public function obtenerPorVenta($venta_id)
    {
        try {
            $query = "SELECT * FROM tickets WHERE venta_id = :venta_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':venta_id', $venta_id);
            $stmt->execute();

            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ticket) {
                return $this->obtenerPorId($ticket['id']);
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error al obtener ticket por venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar tickets con filtros
     */
    public function listar($filtros = [])
    {
        try {
            $query = "SELECT t.*, v.numero_pedido, v.fecha as fecha_venta, v.estado as estado_venta,
                     u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email,
                     u_vendedor.nombre as vendedor_nombre
                     FROM tickets t
                     INNER JOIN ventas v ON t.venta_id = v.id
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     WHERE 1=1";

            // Aplicar filtros
            if (!empty($filtros['fecha_desde'])) {
                $query .= " AND DATE(t.fecha) >= :fecha_desde";
            }

            if (!empty($filtros['fecha_hasta'])) {
                $query .= " AND DATE(t.fecha) <= :fecha_hasta";
            }

            if (!empty($filtros['metodo_pago'])) {
                $query .= " AND t.metodo_pago = :metodo_pago";
            }

            if (!empty($filtros['cliente_id'])) {
                $query .= " AND v.cliente_id = :cliente_id";
            }

            if (!empty($filtros['vendedor_id'])) {
                $query .= " AND v.vendedor_id = :vendedor_id";
            }

            if (!empty($filtros['buscar'])) {
                $query .= " AND (t.numero_ticket LIKE :buscar OR v.numero_pedido LIKE :buscar OR u_cliente.nombre LIKE :buscar)";
            }

            // Ordenamiento
            $order_by = $filtros['order_by'] ?? 't.fecha';
            $order_dir = $filtros['order_dir'] ?? 'DESC';
            $query .= " ORDER BY {$order_by} {$order_dir}";

            // Paginación
            if (!empty($filtros['limit'])) {
                $query .= " LIMIT :limit";
                if (!empty($filtros['offset'])) {
                    $query .= " OFFSET :offset";
                }
            }

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            if (!empty($filtros['fecha_desde'])) {
                $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
            }

            if (!empty($filtros['fecha_hasta'])) {
                $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
            }

            if (!empty($filtros['metodo_pago'])) {
                $stmt->bindParam(':metodo_pago', $filtros['metodo_pago']);
            }

            if (!empty($filtros['cliente_id'])) {
                $stmt->bindParam(':cliente_id', $filtros['cliente_id']);
            }

            if (!empty($filtros['vendedor_id'])) {
                $stmt->bindParam(':vendedor_id', $filtros['vendedor_id']);
            }

            if (!empty($filtros['buscar'])) {
                $buscar = '%' . $filtros['buscar'] . '%';
                $stmt->bindParam(':buscar', $buscar);
            }

            if (!empty($filtros['limit'])) {
                $stmt->bindParam(':limit', $filtros['limit'], PDO::PARAM_INT);
                if (!empty($filtros['offset'])) {
                    $stmt->bindParam(':offset', $filtros['offset'], PDO::PARAM_INT);
                }
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al listar tickets: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar tickets
     */
    public function contar($filtros = [])
    {
        try {
            $query = "SELECT COUNT(*) as total FROM tickets t
                     INNER JOIN ventas v ON t.venta_id = v.id
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     WHERE 1=1";

            // Aplicar mismos filtros que listar
            if (!empty($filtros['fecha_desde'])) {
                $query .= " AND DATE(t.fecha) >= :fecha_desde";
            }

            if (!empty($filtros['fecha_hasta'])) {
                $query .= " AND DATE(t.fecha) <= :fecha_hasta";
            }

            if (!empty($filtros['metodo_pago'])) {
                $query .= " AND t.metodo_pago = :metodo_pago";
            }

            if (!empty($filtros['cliente_id'])) {
                $query .= " AND v.cliente_id = :cliente_id";
            }

            if (!empty($filtros['vendedor_id'])) {
                $query .= " AND v.vendedor_id = :vendedor_id";
            }

            if (!empty($filtros['buscar'])) {
                $query .= " AND (t.numero_ticket LIKE :buscar OR v.numero_pedido LIKE :buscar OR u_cliente.nombre LIKE :buscar)";
            }

            $stmt = $this->conn->prepare($query);

            // Bind parameters (mismo código que listar)
            if (!empty($filtros['fecha_desde'])) {
                $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
            }

            if (!empty($filtros['fecha_hasta'])) {
                $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
            }

            if (!empty($filtros['metodo_pago'])) {
                $stmt->bindParam(':metodo_pago', $filtros['metodo_pago']);
            }

            if (!empty($filtros['cliente_id'])) {
                $stmt->bindParam(':cliente_id', $filtros['cliente_id']);
            }

            if (!empty($filtros['vendedor_id'])) {
                $stmt->bindParam(':vendedor_id', $filtros['vendedor_id']);
            }

            if (!empty($filtros['buscar'])) {
                $buscar = '%' . $filtros['buscar'] . '%';
                $stmt->bindParam(':buscar', $buscar);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'];

        } catch (PDOException $e) {
            error_log("Error al contar tickets: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar código QR del ticket
     */
    public function actualizarQR($id, $qr_code)
    {
        try {
            $query = "UPDATE tickets SET qr_code = :qr_code WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':qr_code', $qr_code);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar QR del ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar contenido HTML del ticket para impresión/PDF
     */
    public function generarHTML($ticket_id)
    {
        $ticket = $this->obtenerPorId($ticket_id);

        if (!$ticket) {
            return false;
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Ticket ' . $ticket['numero_ticket'] . '</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
                .ticket { max-width: 300px; margin: 0 auto; }
                .header { text-align: center; margin-bottom: 20px; }
                .logo { font-size: 18px; font-weight: bold; color: #ff6b9d; }
                .info { margin: 10px 0; }
                .products { margin: 20px 0; }
                .product { display: flex; justify-content: space-between; margin: 5px 0; }
                .totals { border-top: 1px solid #ccc; padding-top: 10px; margin-top: 10px; }
                .total-line { display: flex; justify-content: space-between; margin: 3px 0; }
                .total-final { font-weight: bold; font-size: 14px; border-top: 1px solid #000; padding-top: 5px; }
                .qr { text-align: center; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 10px; }
            </style>
        </head>
        <body>
            <div class="ticket">
                <div class="header">
                    <div class="logo">🍰 SweetPot</div>
                    <div>Repostería Artesanal</div>
                </div>
                
                <div class="info">
                    <strong>Ticket:</strong> ' . $ticket['numero_ticket'] . '<br>
                    <strong>Pedido:</strong> ' . $ticket['numero_pedido'] . '<br>
                    <strong>Fecha:</strong> ' . formatDate($ticket['fecha']) . '<br>
                    <strong>Cliente:</strong> ' . $ticket['cliente_nombre'] . '<br>';

        if (!empty($ticket['vendedor_nombre'])) {
            $html .= '<strong>Vendedor:</strong> ' . $ticket['vendedor_nombre'] . '<br>';
        }

        $html .= '<strong>Método de Pago:</strong> ' . ucfirst($ticket['metodo_pago']) . '
                </div>
                
                <div class="products">
                    <strong>Productos:</strong><br>';

        foreach ($ticket['productos'] as $producto) {
            $html .= '
                    <div class="product">
                        <span>' . $producto['producto_nombre'] . ' (x' . $producto['cantidad'] . ')</span>
                        <span>' . formatPrice($producto['subtotal']) . '</span>
                    </div>';
        }

        $html .= '
                </div>
                
                <div class="totals">
                    <div class="total-line">
                        <span>Subtotal:</span>
                        <span>' . formatPrice($ticket['subtotal']) . '</span>
                    </div>';

        if ($ticket['descuento'] > 0) {
            $html .= '
                    <div class="total-line">
                        <span>Descuento:</span>
                        <span>-' . formatPrice($ticket['descuento']) . '</span>
                    </div>';
        }

        if ($ticket['impuestos'] > 0) {
            $html .= '
                    <div class="total-line">
                        <span>Impuestos:</span>
                        <span>' . formatPrice($ticket['impuestos']) . '</span>
                    </div>';
        }

        $html .= '
                    <div class="total-line total-final">
                        <span>Total:</span>
                        <span>' . formatPrice($ticket['total']) . '</span>
                    </div>
                </div>';

        if (!empty($ticket['qr_code'])) {
            $qr_url = URL . "assets/qr/" . $ticket['qr_code'];
            $html .= '
                <div class="qr">
                    <img src="' . $qr_url . '" alt="Código QR" width="100" height="100">
                    <br><small>Escanea para ver tu pedido</small>
                </div>';
        }

        $html .= '
                <div class="footer">
                    ¡Gracias por tu compra!<br>
                    www.sweetpot.com
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Obtener estadísticas de tickets
     */
    public function obtenerEstadisticas($filtros = [])
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_tickets,
                        SUM(total) as ventas_totales,
                        AVG(total) as venta_promedio,
                        SUM(CASE WHEN metodo_pago = 'efectivo' THEN 1 ELSE 0 END) as efectivo,
                        SUM(CASE WHEN metodo_pago = 'tarjeta' THEN 1 ELSE 0 END) as tarjeta,
                        SUM(CASE WHEN metodo_pago = 'transferencia' THEN 1 ELSE 0 END) as transferencia
                      FROM tickets WHERE 1=1";

            // Aplicar filtros de fecha si existen
            if (!empty($filtros['fecha_desde'])) {
                $query .= " AND DATE(fecha) >= :fecha_desde";
            }

            if (!empty($filtros['fecha_hasta'])) {
                $query .= " AND DATE(fecha) <= :fecha_hasta";
            }

            $stmt = $this->conn->prepare($query);

            if (!empty($filtros['fecha_desde'])) {
                $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
            }

            if (!empty($filtros['fecha_hasta'])) {
                $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
            }

            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de tickets: " . $e->getMessage());
            return false;
        }
    }
}
?>