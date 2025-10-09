<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/qr_utils.php';

class Producto
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
     * Crear nuevo producto
     */
    public function crear($datos)
    {
        try {
            // Generar código único del producto
            if (empty($datos['codigo_producto'])) {
                $datos['codigo_producto'] = generateProductCode($datos['categoria_id'], $datos['nombre']);
            }

            $query = "INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, stock_minimo, 
                     imagen, codigo_producto, estado) 
                     VALUES (:categoria_id, :nombre, :descripcion, :precio, :stock, :stock_minimo, 
                     :imagen, :codigo_producto, :estado)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':categoria_id', $datos['categoria_id']);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':descripcion', $datos['descripcion']);
            $stmt->bindParam(':precio', $datos['precio']);
            $stmt->bindParam(':stock', $datos['stock']);
            $stmt->bindParam(':stock_minimo', $datos['stock_minimo']);
            $stmt->bindParam(':imagen', $datos['imagen']);
            $stmt->bindParam(':codigo_producto', $datos['codigo_producto']);
            $stmt->bindParam(':estado', $datos['estado']);

            if ($stmt->execute()) {
                $producto_id = $this->conn->lastInsertId();

                // Generar código QR automáticamente
                $qr_result = $this->qr_utils->generarQRProducto($producto_id, $datos['nombre']);
                if ($qr_result['success']) {
                    // Actualizar el producto con la ruta del QR
                    $this->actualizarQR($producto_id, $qr_result['archivo']);
                }

                return $producto_id;
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error al crear producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener producto por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                     FROM productos p 
                     INNER JOIN categorias c ON p.categoria_id = c.id 
                     WHERE p.id = :id LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener producto por código
     */
    public function obtenerPorCodigo($codigo)
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                     FROM productos p 
                     INNER JOIN categorias c ON p.categoria_id = c.id 
                     WHERE p.codigo_producto = :codigo AND p.estado = 'activo' LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener producto por código: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar producto
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE productos SET categoria_id = :categoria_id, nombre = :nombre, 
                     descripcion = :descripcion, precio = :precio, stock = :stock, 
                     stock_minimo = :stock_minimo, imagen = :imagen, estado = :estado 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':categoria_id', $datos['categoria_id']);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':descripcion', $datos['descripcion']);
            $stmt->bindParam(':precio', $datos['precio']);
            $stmt->bindParam(':stock', $datos['stock']);
            $stmt->bindParam(':stock_minimo', $datos['stock_minimo']);
            $stmt->bindParam(':imagen', $datos['imagen']);
            $stmt->bindParam(':estado', $datos['estado']);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado del producto
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $query = "UPDATE productos SET estado = :estado WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':estado', $estado);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al cambiar estado del producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar producto
     */
    public function eliminar($id)
    {
        try {
            // Soft-delete: marcar producto como inactivo
            $query = "UPDATE productos SET estado = 'inactivo' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al eliminar producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar productos con filtros
     */
    public function listar($filtros = [])
    {
        try {
            // Configurar paginación
            $limit = $filtros['limit'] ?? 10;
            $page = $filtros['page'] ?? 1;
            $offset = ($page - 1) * $limit;

            // Query base para contar total
            $countQuery = "SELECT COUNT(*) as total 
                          FROM productos p 
                          INNER JOIN categorias c ON p.categoria_id = c.id 
                          WHERE 1=1";

            // Query principal
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                     FROM productos p 
                     INNER JOIN categorias c ON p.categoria_id = c.id 
                     WHERE 1=1";

            $whereConditions = "";
            $params = [];

            // Aplicar filtros
            if (!empty($filtros['categoria'])) {
                $whereConditions .= " AND p.categoria_id = :categoria_id";
                $params[':categoria_id'] = $filtros['categoria'];
            }

            // Soporte para filtro categoria_id también
            if (!empty($filtros['categoria_id'])) {
                $whereConditions .= " AND p.categoria_id = :categoria_id";
                $params[':categoria_id'] = $filtros['categoria_id'];
            }

            if (!empty($filtros['estado'])) {
                $whereConditions .= " AND p.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }

            if (!empty($filtros['stock_bajo'])) {
                $whereConditions .= " AND p.stock <= p.stock_minimo";
            }

            if (!empty($filtros['search'])) {
                $whereConditions .= " AND (p.nombre LIKE :search1 OR p.descripcion LIKE :search2 OR p.codigo_producto LIKE :search3)";
                $params[':search1'] = '%' . $filtros['search'] . '%';
                $params[':search2'] = '%' . $filtros['search'] . '%';
                $params[':search3'] = '%' . $filtros['search'] . '%';
            }

            if (!empty($filtros['precio_min'])) {
                $whereConditions .= " AND p.precio >= :precio_min";
                $params[':precio_min'] = $filtros['precio_min'];
            }

            if (!empty($filtros['precio_max'])) {
                $whereConditions .= " AND p.precio <= :precio_max";
                $params[':precio_max'] = $filtros['precio_max'];
            }

            if (!empty($filtros['stock_min'])) {
                $whereConditions .= " AND p.stock >= :stock_min";
                $params[':stock_min'] = $filtros['stock_min'];
            }

            // Agregar condiciones WHERE a ambas queries
            $countQuery .= $whereConditions;
            $query .= $whereConditions;

            // Ordenamiento
            if (!empty($filtros['order'])) {
                $query .= " ORDER BY " . $filtros['order'];
            } else {
                $query .= " ORDER BY p.nombre ASC";
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
            error_log("Error al listar productos: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => 1,
                'limit' => $limit ?? 10,
                'offset' => 0
            ];
        }
    }

    /**
     * Contar productos
     */
    public function contar($filtros = [])
    {
        try {
            $query = "SELECT COUNT(*) as total FROM productos p 
                     INNER JOIN categorias c ON p.categoria_id = c.id 
                     WHERE 1=1";

            // Aplicar filtros
            if (!empty($filtros['categoria_id'])) {
                $query .= " AND p.categoria_id = :categoria_id";
            }

            if (!empty($filtros['estado'])) {
                $query .= " AND p.estado = :estado";
            }

            if (!empty($filtros['stock_bajo'])) {
                $query .= " AND p.stock <= p.stock_minimo";
            }

            if (!empty($filtros['buscar'])) {
                $query .= " AND (p.nombre LIKE :buscar OR p.descripcion LIKE :buscar OR p.codigo_producto LIKE :buscar)";
            }

            if (!empty($filtros['precio_min'])) {
                $query .= " AND p.precio >= :precio_min";
            }

            if (!empty($filtros['precio_max'])) {
                $query .= " AND p.precio <= :precio_max";
            }

            $stmt = $this->conn->prepare($query);

            // Bind parameters (mismo código que listar)
            if (!empty($filtros['categoria_id'])) {
                $stmt->bindParam(':categoria_id', $filtros['categoria_id']);
            }

            if (!empty($filtros['estado'])) {
                $stmt->bindParam(':estado', $filtros['estado']);
            }

            if (!empty($filtros['buscar'])) {
                $buscar = '%' . $filtros['buscar'] . '%';
                $stmt->bindParam(':buscar', $buscar);
            }

            if (!empty($filtros['precio_min'])) {
                $stmt->bindParam(':precio_min', $filtros['precio_min']);
            }

            if (!empty($filtros['precio_max'])) {
                $stmt->bindParam(':precio_max', $filtros['precio_max']);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'];

        } catch (PDOException $e) {
            error_log("Error al contar productos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar stock del producto
     */
    public function actualizarStock($id, $nuevo_stock)
    {
        try {
            // Si el stock es 0, cambiar estado a inactivo automáticamente
            $estado = ($nuevo_stock <= 0) ? 'inactivo' : null;

            if ($estado) {
                $query = "UPDATE productos SET stock = :stock, estado = :estado WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':stock', $nuevo_stock);
                $stmt->bindParam(':estado', $estado);
            } else {
                $query = "UPDATE productos SET stock = :stock WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':stock', $nuevo_stock);
            }

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reducir stock del producto
     */
    public function reducirStock($id, $cantidad)
    {
        try {
            // Primero verificar stock actual
            $checkQuery = "SELECT stock FROM productos WHERE id = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $producto = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$producto || $producto['stock'] < $cantidad) {
                return false;
            }

            $nuevo_stock = $producto['stock'] - $cantidad;

            // Si el nuevo stock es 0, cambiar estado a inactivo
            if ($nuevo_stock <= 0) {
                $query = "UPDATE productos SET stock = 0, estado = 'inactivo' WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $id);
            } else {
                $query = "UPDATE productos SET stock = :nuevo_stock WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':nuevo_stock', $nuevo_stock);
            }

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al reducir stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aumentar stock del producto
     */
    public function aumentarStock($id, $cantidad)
    {
        try {
            $query = "UPDATE productos SET stock = stock + :cantidad WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':cantidad', $cantidad);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al aumentar stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener productos con stock bajo
     */
    public function obtenerStockBajo()
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                     FROM productos p 
                     INNER JOIN categorias c ON p.categoria_id = c.id 
                     WHERE p.stock <= p.stock_minimo AND p.estado = 'activo'
                     ORDER BY p.stock ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener productos con stock bajo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener productos más vendidos
     */
    public function obtenerMasVendidos($limite = 10)
    {
        try {
            // Primero verificar si existen las tablas de ventas
            $checkTables = "SHOW TABLES LIKE 'detalle_venta'";
            $stmt = $this->conn->prepare($checkTables);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                // Si no existen las tablas de ventas, devolver productos aleatorios
                $query = "SELECT p.*, c.nombre as categoria_nombre, 0 as total_vendidos
                         FROM productos p 
                         INNER JOIN categorias c ON p.categoria_id = c.id
                         WHERE p.estado = 'activo'
                         ORDER BY RAND()
                         LIMIT :limite";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Si existen las tablas, obtener productos más vendidos
            $query = "SELECT p.*, c.nombre as categoria_nombre, 
                            COALESCE(SUM(dv.cantidad), 0) as total_vendidos
                     FROM productos p 
                     INNER JOIN categorias c ON p.categoria_id = c.id
                     LEFT JOIN detalle_venta dv ON p.id = dv.producto_id
                     LEFT JOIN ventas v ON dv.venta_id = v.id 
                                      AND v.estado IN ('entregado', 'enviado', 'en_proceso')
                     WHERE p.estado = 'activo'
                     GROUP BY p.id
                     ORDER BY total_vendidos DESC, p.fecha_creacion DESC
                     LIMIT :limite";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener productos más vendidos: " . $e->getMessage());
            // En caso de error, devolver array vacío
            return [];
        }
    }

    /**
     * Verificar si existe código de producto
     */
    public function existeCodigo($codigo, $excluir_id = null)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM productos WHERE codigo_producto = :codigo";

            if ($excluir_id) {
                $query .= " AND id != :excluir_id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':codigo', $codigo);

            if ($excluir_id) {
                $stmt->bindParam(':excluir_id', $excluir_id);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'] > 0;

        } catch (PDOException $e) {
            error_log("Error al verificar código de producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar código QR del producto
     */
    public function actualizarQR($id, $codigo_qr)
    {
        try {
            $query = "UPDATE productos SET codigo_qr = :codigo_qr WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':codigo_qr', $codigo_qr);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar QR del producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar nuevo código QR para producto
     */
    public function generarNuevoQR($id)
    {
        try {
            $producto = $this->obtenerPorId($id);
            if (!$producto) {
                return false;
            }

            // Eliminar QR anterior si existe
            if (!empty($producto['codigo_qr'])) {
                $this->qr_utils->eliminarQR($producto['codigo_qr']);
            }

            // Generar nuevo QR
            $qr_result = $this->qr_utils->generarQRProducto($id, $producto['nombre']);

            if ($qr_result['success']) {
                // Actualizar el producto con la nueva ruta del QR
                $this->actualizarQR($id, $qr_result['archivo']);
                return $qr_result;
            }

            return false;

        } catch (Exception $e) {
            error_log("Error al generar nuevo QR: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de productos
     */
    public function obtenerEstadisticas()
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_productos,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                        SUM(stock) as stock_total,
                        AVG(precio) as precio_promedio,
                        SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) as stock_bajo
                      FROM productos";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de productos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener productos con stock bajo
     */
    public function obtenerConStockBajo($limite_stock = 10)
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria 
                    FROM productos p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.stock <= :limite_stock AND p.estado = 'activo'
                    ORDER BY p.stock ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limite_stock', $limite_stock, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en Producto::obtenerConStockBajo(): " . $e->getMessage());
            throw new Exception("Error al obtener productos con stock bajo");
        }
    }


}
?>