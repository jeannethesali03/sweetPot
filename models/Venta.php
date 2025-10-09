<?php
require_once __DIR__ . '/../config/Database.php';

class Venta
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Crear nueva venta/pedido
     */
    public function crear($datos)
    {
        try {
            $this->conn->beginTransaction();

            // Insertar venta principal
            $query = "INSERT INTO ventas (cliente_id, vendedor_id, subtotal, impuestos, descuento, total, 
                     direccion_entrega, comentarios, estado) 
                     VALUES (:cliente_id, :vendedor_id, :subtotal, :impuestos, :descuento, :total, 
                     :direccion_entrega, :comentarios, :estado)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $datos['cliente_id']);
            $stmt->bindParam(':vendedor_id', $datos['vendedor_id']);
            $stmt->bindParam(':subtotal', $datos['subtotal']);
            $stmt->bindParam(':impuestos', $datos['impuestos']);
            $stmt->bindParam(':descuento', $datos['descuento']);
            $stmt->bindParam(':total', $datos['total']);
            $stmt->bindParam(':direccion_entrega', $datos['direccion_entrega']);
            $stmt->bindParam(':comentarios', $datos['comentarios']);
            $stmt->bindParam(':estado', $datos['estado']);

            if (!$stmt->execute()) {
                throw new Exception("Error al crear la venta");
            }

            $venta_id = $this->conn->lastInsertId();

            // Insertar detalles de la venta
            if (!empty($datos['productos'])) {
                $detalle_query = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                 VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
                $detalle_stmt = $this->conn->prepare($detalle_query);

                foreach ($datos['productos'] as $producto) {
                    $detalle_stmt->bindParam(':venta_id', $venta_id);
                    $detalle_stmt->bindParam(':producto_id', $producto['id']);
                    $detalle_stmt->bindParam(':cantidad', $producto['cantidad']);
                    $detalle_stmt->bindParam(':precio_unitario', $producto['precio']);
                    $subtotal_producto = $producto['cantidad'] * $producto['precio'];
                    $detalle_stmt->bindParam(':subtotal', $subtotal_producto);

                    if (!$detalle_stmt->execute()) {
                        throw new Exception("Error al insertar detalle del producto: " . $producto['nombre']);
                    }
                }
            }

            $this->conn->commit();
            return $venta_id;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error al crear venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener venta por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $query = "SELECT v.*, 
                     u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email, u_cliente.telefono as cliente_telefono,
                     u_vendedor.nombre as vendedor_nombre
                     FROM ventas v
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     WHERE v.id = :id LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($venta) {
                // Obtener productos de la venta
                $venta['productos'] = $this->obtenerProductosVenta($id);
            }

            return $venta;

        } catch (PDOException $e) {
            error_log("Error al obtener venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener productos de una venta
     */
    public function obtenerProductosVenta($venta_id)
    {
        try {
            $query = "SELECT dv.*, p.nombre as producto_nombre, p.imagen as producto_imagen,
                     c.nombre as categoria_nombre
                     FROM detalle_venta dv
                     INNER JOIN productos p ON dv.producto_id = p.id
                     INNER JOIN categorias c ON p.categoria_id = c.id
                     WHERE dv.venta_id = :venta_id
                     ORDER BY p.nombre";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':venta_id', $venta_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener productos de venta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Listar ventas con filtros
     */
    public function listar($filtros = [])
    {
        try {
            // Configurar paginación
            $limit = $filtros['limit'] ?? 20;
            $page = $filtros['page'] ?? 1;
            $offset = ($page - 1) * $limit;

            // Query base para contar total
            $countQuery = "SELECT COUNT(*) as total 
                          FROM ventas v 
                          INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id 
                          LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id 
                          WHERE 1=1";

            // Query principal
            $query = "SELECT v.*, 
                     u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email,
                     u_vendedor.nombre as vendedor_nombre
                     FROM ventas v
                     INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
                     LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
                     WHERE 1=1";

            $whereConditions = "";
            $params = [];

            // Aplicar filtros
            if (!empty($filtros['estado'])) {
                $whereConditions .= " AND v.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }

            if (!empty($filtros['cliente_id'])) {
                $whereConditions .= " AND v.cliente_id = :cliente_id";
                $params[':cliente_id'] = $filtros['cliente_id'];
            }

            if (!empty($filtros['vendedor_id'])) {
                $whereConditions .= " AND v.vendedor_id = :vendedor_id";
                $params[':vendedor_id'] = $filtros['vendedor_id'];
            }

            // Filtrar por existencia de vendedor (with / without)
            if (!empty($filtros['has_vendedor'])) {
                if ($filtros['has_vendedor'] === 'with') {
                    $whereConditions .= " AND v.vendedor_id IS NOT NULL";
                } elseif ($filtros['has_vendedor'] === 'without') {
                    $whereConditions .= " AND v.vendedor_id IS NULL";
                }
            }

            if (!empty($filtros['search'])) {
                $whereConditions .= " AND (v.numero_pedido LIKE :search1 OR u_cliente.nombre LIKE :search2 OR u_cliente.email LIKE :search3)";
                $params[':search1'] = '%' . $filtros['search'] . '%';
                $params[':search2'] = '%' . $filtros['search'] . '%';
                $params[':search3'] = '%' . $filtros['search'] . '%';
            }

            if (!empty($filtros['fecha_desde'])) {
                $whereConditions .= " AND DATE(v.fecha) >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $whereConditions .= " AND DATE(v.fecha) <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'];
            }

            if (!empty($filtros['total_min'])) {
                $whereConditions .= " AND v.total >= :total_min";
                $params[':total_min'] = $filtros['total_min'];
            }

            if (!empty($filtros['total_max'])) {
                $whereConditions .= " AND v.total <= :total_max";
                $params[':total_max'] = $filtros['total_max'];
            }

            // Agregar condiciones WHERE a ambas queries
            $countQuery .= $whereConditions;
            $query .= $whereConditions;

            // Ordenamiento
            if (!empty($filtros['order'])) {
                $query .= " ORDER BY " . $filtros['order'];
            } else {
                $query .= " ORDER BY v.fecha DESC";
            }

            // Obtener total de registros
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calcular paginación
            $totalPages = ceil($totalRecords / $limit);
            $currentPage = $page;

            // Agregar LIMIT y OFFSET
            $query .= " LIMIT :limit OFFSET :offset";

            // Preparar y ejecutar query principal
            $stmt = $this->conn->prepare($query);

            // Bind parámetros de filtros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            // Bind parámetros de paginación
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Retornar estructura completa
            return [
                'data' => $data,
                'total' => $totalRecords,
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'limit' => $limit,
                'offset' => $offset
            ];

        } catch (PDOException $e) {
            error_log("Error al listar ventas: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => 1,
                'limit' => $limit ?? 20,
                'offset' => 0
            ];
        }
    }

    /**
     * Cambiar estado de la venta
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $query = "UPDATE ventas SET estado = :estado, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':estado', $estado);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al cambiar estado de venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar venta
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE ventas SET 
                     subtotal = :subtotal, 
                     impuestos = :impuestos, 
                     descuento = :descuento, 
                     total = :total, 
                     direccion_entrega = :direccion_entrega, 
                     comentarios = :comentarios,
                     estado = :estado,
                     fecha_actualizacion = CURRENT_TIMESTAMP
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':subtotal', $datos['subtotal']);
            $stmt->bindParam(':impuestos', $datos['impuestos']);
            $stmt->bindParam(':descuento', $datos['descuento']);
            $stmt->bindParam(':total', $datos['total']);
            $stmt->bindParam(':direccion_entrega', $datos['direccion_entrega']);
            $stmt->bindParam(':comentarios', $datos['comentarios']);
            $stmt->bindParam(':estado', $datos['estado']);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar venta (cancelar)
     */
    public function cancelar($id, $motivo = '')
    {
        try {
            $this->conn->beginTransaction();

            // Actualizar estado a cancelado
            $query = "UPDATE ventas SET estado = 'cancelado', comentarios = CONCAT(COALESCE(comentarios, ''), '\nCANCELADO: ', :motivo) WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':motivo', $motivo);

            if (!$stmt->execute()) {
                throw new Exception("Error al cancelar venta");
            }

            // Restaurar stock de productos
            $detalle_query = "SELECT producto_id, cantidad FROM detalle_venta WHERE venta_id = :venta_id";
            $detalle_stmt = $this->conn->prepare($detalle_query);
            $detalle_stmt->bindParam(':venta_id', $id);
            $detalle_stmt->execute();

            $productos = $detalle_stmt->fetchAll(PDO::FETCH_ASSOC);

            $stock_query = "UPDATE productos SET stock = stock + :cantidad WHERE id = :producto_id";
            $stock_stmt = $this->conn->prepare($stock_query);

            foreach ($productos as $producto) {
                $stock_stmt->bindParam(':cantidad', $producto['cantidad']);
                $stock_stmt->bindParam(':producto_id', $producto['producto_id']);
                if (!$stock_stmt->execute()) {
                    throw new Exception("Error al restaurar stock del producto ID: " . $producto['producto_id']);
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error al cancelar venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de ventas
     */
    public function obtenerEstadisticas()
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_pedidos,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                        SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
                        SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
                        SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                        SUM(CASE WHEN estado != 'cancelado' THEN total ELSE 0 END) as total_ventas,
                        AVG(CASE WHEN estado != 'cancelado' THEN total ELSE NULL END) as promedio_venta,
                        SUM(CASE WHEN estado = 'entregado' AND DATE(fecha) = CURDATE() THEN total ELSE 0 END) as ventas_hoy
                      FROM ventas";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de ventas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estados disponibles
     */
    public function obtenerEstados()
    {
        return [
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En Proceso',
            'enviado' => 'Enviado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado'
        ];
    }

    /**
     * Obtener clientes para filtros
     */
    public function obtenerClientes()
    {
        try {
            $query = "SELECT DISTINCT u.id, u.nombre, u.email 
                     FROM usuarios u 
                     INNER JOIN ventas v ON u.id = v.cliente_id 
                     WHERE u.rol = 'cliente' AND u.estado = 'activo'
                     ORDER BY u.nombre";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener clientes: " . $e->getMessage());
            return [];
        }
    }
}
?>