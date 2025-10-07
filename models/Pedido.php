<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Pedido
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Crear nuevo pedido
     */
    public function crear($datos)
    {
        try {
            $this->conn->beginTransaction();

            // Insertar venta principal
            $query = "INSERT INTO ventas (cliente_id, vendedor_id, subtotal, impuestos, descuento, total, 
                     estado, direccion_entrega, comentarios) 
                     VALUES (:cliente_id, :vendedor_id, :subtotal, :impuestos, :descuento, :total, 
                     :estado, :direccion_entrega, :comentarios)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $datos['cliente_id']);
            $stmt->bindParam(':vendedor_id', $datos['vendedor_id']);
            $stmt->bindParam(':subtotal', $datos['subtotal']);
            $stmt->bindParam(':impuestos', $datos['impuestos']);
            $stmt->bindParam(':descuento', $datos['descuento']);
            $stmt->bindParam(':total', $datos['total']);
            $stmt->bindParam(':estado', $datos['estado']);
            $stmt->bindParam(':direccion_entrega', $datos['direccion_entrega']);
            $stmt->bindParam(':comentarios', $datos['comentarios']);

            if (!$stmt->execute()) {
                throw new Exception("Error al crear la venta principal");
            }

            $venta_id = $this->conn->lastInsertId();

            // Insertar detalles de la venta
            if (!empty($datos['productos'])) {
                $query_detalle = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                 VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
                $stmt_detalle = $this->conn->prepare($query_detalle);

                foreach ($datos['productos'] as $producto) {
                    $subtotal_producto = $producto['cantidad'] * $producto['precio_unitario'];

                    $stmt_detalle->bindParam(':venta_id', $venta_id);
                    $stmt_detalle->bindParam(':producto_id', $producto['producto_id']);
                    $stmt_detalle->bindParam(':cantidad', $producto['cantidad']);
                    $stmt_detalle->bindParam(':precio_unitario', $producto['precio_unitario']);
                    $stmt_detalle->bindParam(':subtotal', $subtotal_producto);

                    if (!$stmt_detalle->execute()) {
                        throw new Exception("Error al insertar detalle del producto ID: " . $producto['producto_id']);
                    }
                }
            }

            $this->conn->commit();
            return $venta_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al crear pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener pedido por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $query = "SELECT v.*, 
                     u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email, 
                     u_cliente.telefono as cliente_telefono, u_cliente.direccion as cliente_direccion,
                     u_vendedor.nombre as vendedor_nombre,
                     t.numero_ticket, t.qr_code as ticket_qr
                     FROM ventas v
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     LEFT JOIN tickets t ON v.id = t.venta_id
                     WHERE v.id = :id LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pedido) {
                // Obtener detalles del pedido
                $pedido['productos'] = $this->obtenerDetallesPedido($id);
            }

            return $pedido;

        } catch (PDOException $e) {
            error_log("Error al obtener pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener pedido por número
     */
    public function obtenerPorNumero($numero_pedido)
    {
        try {
            $query = "SELECT v.*, 
                     u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email,
                     u_vendedor.nombre as vendedor_nombre
                     FROM ventas v
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     WHERE v.numero_pedido = :numero_pedido LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':numero_pedido', $numero_pedido);
            $stmt->execute();

            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pedido) {
                $pedido['productos'] = $this->obtenerDetallesPedido($pedido['id']);
            }

            return $pedido;

        } catch (PDOException $e) {
            error_log("Error al obtener pedido por número: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener detalles de productos del pedido
     */
    public function obtenerDetallesPedido($venta_id)
    {
        try {
            $query = "SELECT dv.*, p.nombre as producto_nombre, p.imagen as producto_imagen,
                     c.nombre as categoria_nombre
                     FROM detalle_venta dv
                     INNER JOIN productos p ON dv.producto_id = p.id
                     INNER JOIN categorias c ON p.categoria_id = c.id
                     WHERE dv.venta_id = :venta_id
                     ORDER BY dv.id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':venta_id', $venta_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener detalles del pedido: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar estado del pedido
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $query = "UPDATE ventas SET estado = :estado, fecha_actualizacion = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':estado', $estado);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar estado del pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar pedido
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE ventas SET direccion_entrega = :direccion_entrega, 
                     comentarios = :comentarios, fecha_actualizacion = NOW() 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':direccion_entrega', $datos['direccion_entrega']);
            $stmt->bindParam(':comentarios', $datos['comentarios']);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancelar pedido
     */
    public function cancelar($id)
    {
        try {
            $this->conn->beginTransaction();

            // Obtener detalles del pedido para restaurar el stock
            $detalles = $this->obtenerDetallesPedido($id);

            // Restaurar stock de productos
            $query_stock = "UPDATE productos SET stock = stock + :cantidad WHERE id = :producto_id";
            $stmt_stock = $this->conn->prepare($query_stock);

            foreach ($detalles as $detalle) {
                $stmt_stock->bindParam(':cantidad', $detalle['cantidad']);
                $stmt_stock->bindParam(':producto_id', $detalle['producto_id']);

                if (!$stmt_stock->execute()) {
                    throw new Exception("Error al restaurar stock del producto ID: " . $detalle['producto_id']);
                }
            }

            // Actualizar estado del pedido
            if (!$this->actualizarEstado($id, 'cancelado')) {
                throw new Exception("Error al cancelar el pedido");
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al cancelar pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar pedidos con filtros
     */
    public function listar($filtros = [])
    {
        try {
            $query = "SELECT v.*, 
                     u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email,
                     u_vendedor.nombre as vendedor_nombre,
                     t.numero_ticket
                     FROM ventas v
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     LEFT JOIN tickets t ON v.id = t.venta_id
                     WHERE 1=1";

            // Aplicar filtros
            if (!empty($filtros['cliente_id'])) {
                $query .= " AND v.cliente_id = :cliente_id";
            }

            if (!empty($filtros['vendedor_id'])) {
                $query .= " AND v.vendedor_id = :vendedor_id";
            }

            if (!empty($filtros['estado'])) {
                $query .= " AND v.estado = :estado";
            }

            if (!empty($filtros['fecha_desde'])) {
                $query .= " AND DATE(v.fecha) >= :fecha_desde";
            }

            if (!empty($filtros['fecha_hasta'])) {
                $query .= " AND DATE(v.fecha) <= :fecha_hasta";
            }

            if (!empty($filtros['buscar'])) {
                $query .= " AND (v.numero_pedido LIKE :buscar OR u_cliente.nombre LIKE :buscar OR u_cliente.email LIKE :buscar)";
            }

            // Ordenamiento
            $order_by = $filtros['order_by'] ?? 'v.fecha';
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
            if (!empty($filtros['cliente_id'])) {
                $stmt->bindParam(':cliente_id', $filtros['cliente_id']);
            }

            if (!empty($filtros['vendedor_id'])) {
                $stmt->bindParam(':vendedor_id', $filtros['vendedor_id']);
            }

            if (!empty($filtros['estado'])) {
                $stmt->bindParam(':estado', $filtros['estado']);
            }

            if (!empty($filtros['fecha_desde'])) {
                $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
            }

            if (!empty($filtros['fecha_hasta'])) {
                $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
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
            error_log("Error al listar pedidos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar pedidos
     */
    public function contar($filtros = [])
    {
        try {
            $query = "SELECT COUNT(*) as total FROM ventas v
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     WHERE 1=1";

            // Aplicar mismos filtros que listar
            if (!empty($filtros['cliente_id'])) {
                $query .= " AND v.cliente_id = :cliente_id";
            }

            if (!empty($filtros['vendedor_id'])) {
                $query .= " AND v.vendedor_id = :vendedor_id";
            }

            if (!empty($filtros['estado'])) {
                $query .= " AND v.estado = :estado";
            }

            if (!empty($filtros['fecha_desde'])) {
                $query .= " AND DATE(v.fecha) >= :fecha_desde";
            }

            if (!empty($filtros['fecha_hasta'])) {
                $query .= " AND DATE(v.fecha) <= :fecha_hasta";
            }

            if (!empty($filtros['buscar'])) {
                $query .= " AND (v.numero_pedido LIKE :buscar OR u_cliente.nombre LIKE :buscar OR u_cliente.email LIKE :buscar)";
            }

            $stmt = $this->conn->prepare($query);

            // Bind parameters (mismo código que listar)
            if (!empty($filtros['cliente_id'])) {
                $stmt->bindParam(':cliente_id', $filtros['cliente_id']);
            }

            if (!empty($filtros['vendedor_id'])) {
                $stmt->bindParam(':vendedor_id', $filtros['vendedor_id']);
            }

            if (!empty($filtros['estado'])) {
                $stmt->bindParam(':estado', $filtros['estado']);
            }

            if (!empty($filtros['fecha_desde'])) {
                $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
            }

            if (!empty($filtros['fecha_hasta'])) {
                $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
            }

            if (!empty($filtros['buscar'])) {
                $buscar = '%' . $filtros['buscar'] . '%';
                $stmt->bindParam(':buscar', $buscar);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'];

        } catch (PDOException $e) {
            error_log("Error al contar pedidos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener estadísticas de pedidos
     */
    public function obtenerEstadisticas($filtros = [])
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(total) as ventas_totales,
                        AVG(total) as venta_promedio,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendiente,
                        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                        SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviado,
                        SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregado,
                        SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelado
                      FROM ventas WHERE 1=1";

            // Aplicar filtros de fecha si existen
            if (!empty($filtros['fecha_desde'])) {
                $query .= " AND DATE(fecha) >= :fecha_desde";
            }

            if (!empty($filtros['fecha_hasta'])) {
                $query .= " AND DATE(fecha) <= :fecha_hasta";
            }

            if (!empty($filtros['vendedor_id'])) {
                $query .= " AND vendedor_id = :vendedor_id";
            }

            $stmt = $this->conn->prepare($query);

            if (!empty($filtros['fecha_desde'])) {
                $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
            }

            if (!empty($filtros['fecha_hasta'])) {
                $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
            }

            if (!empty($filtros['vendedor_id'])) {
                $stmt->bindParam(':vendedor_id', $filtros['vendedor_id']);
            }

            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            // Asegurar que todos los valores sean números, no null
            if ($resultado) {
                foreach ($resultado as $key => $value) {
                    if (is_null($value)) {
                        $resultado[$key] = 0;
                    }
                }
            }

            return $resultado;

        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de pedidos: " . $e->getMessage());
            return [
                'total' => 0,
                'ventas_totales' => 0,
                'venta_promedio' => 0,
                'pendiente' => 0,
                'en_proceso' => 0,
                'enviado' => 0,
                'entregado' => 0,
                'cancelado' => 0
            ];
        }
    }

    /**
     * Obtener ventas por día/mes para gráficos
     */
    public function obtenerVentasPorPeriodo($periodo = 'dia', $limite = 30)
    {
        try {
            $format = $periodo === 'mes' ? '%Y-%m' : '%Y-%m-%d';

            $query = "SELECT 
                        DATE_FORMAT(fecha, '{$format}') as periodo,
                        COUNT(*) as total_pedidos,
                        SUM(total) as total_ventas
                      FROM ventas 
                      WHERE estado != 'cancelado'
                      GROUP BY DATE_FORMAT(fecha, '{$format}')
                      ORDER BY periodo DESC
                      LIMIT :limite";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener ventas por período: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener últimos pedidos para dashboard
     */
    public function obtenerUltimosPedidos($limite = 10)
    {
        try {
            $query = "SELECT v.id, v.numero_pedido, v.fecha as fecha_pedido, v.total, v.estado,
                     u_cliente.nombre as nombre_cliente, u_cliente.email as email_cliente
                     FROM ventas v
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     ORDER BY v.fecha DESC
                     LIMIT :limite";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $pedidos,
                'total' => count($pedidos)
            ];

        } catch (PDOException $e) {
            error_log("Error al obtener últimos pedidos: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0
            ];
        }
    }
}
?>