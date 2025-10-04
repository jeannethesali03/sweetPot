<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Carrito
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Agregar producto al carrito
     */
    public function agregar($cliente_id, $producto_id, $cantidad = 1)
    {
        try {
            // Verificar si el producto ya está en el carrito
            $query_check = "SELECT id, cantidad FROM carrito WHERE cliente_id = :cliente_id AND producto_id = :producto_id";
            $stmt_check = $this->conn->prepare($query_check);
            $stmt_check->bindParam(':cliente_id', $cliente_id);
            $stmt_check->bindParam(':producto_id', $producto_id);
            $stmt_check->execute();

            $item_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($item_existente) {
                // Actualizar cantidad si ya existe
                $nueva_cantidad = $item_existente['cantidad'] + $cantidad;
                return $this->actualizarCantidad($cliente_id, $producto_id, $nueva_cantidad);
            } else {
                // Insertar nuevo item
                $query = "INSERT INTO carrito (cliente_id, producto_id, cantidad) VALUES (:cliente_id, :producto_id, :cantidad)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':cliente_id', $cliente_id);
                $stmt->bindParam(':producto_id', $producto_id);
                $stmt->bindParam(':cantidad', $cantidad);

                return $stmt->execute();
            }

        } catch (PDOException $e) {
            error_log("Error al agregar al carrito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar cantidad de producto en el carrito
     */
    public function actualizarCantidad($cliente_id, $producto_id, $cantidad)
    {
        try {
            if ($cantidad <= 0) {
                return $this->eliminar($cliente_id, $producto_id);
            }

            $query = "UPDATE carrito SET cantidad = :cantidad, fecha_agregado = NOW() 
                     WHERE cliente_id = :cliente_id AND producto_id = :producto_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->bindParam(':cantidad', $cantidad);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar cantidad en carrito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar producto del carrito
     */
    public function eliminar($cliente_id, $producto_id)
    {
        try {
            $query = "DELETE FROM carrito WHERE cliente_id = :cliente_id AND producto_id = :producto_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':producto_id', $producto_id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al eliminar del carrito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vaciar carrito completo del cliente
     */
    public function vaciar($cliente_id)
    {
        try {
            $query = "DELETE FROM carrito WHERE cliente_id = :cliente_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $cliente_id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al vaciar carrito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener items del carrito del cliente
     */
    public function obtenerItems($cliente_id)
    {
        try {
            $query = "SELECT c.*, p.nombre, p.descripcion, p.precio, p.imagen, p.stock, 
                     cat.nombre as categoria_nombre,
                     (c.cantidad * p.precio) as subtotal
                     FROM carrito c
                     INNER JOIN productos p ON c.producto_id = p.id
                     INNER JOIN categorias cat ON p.categoria_id = cat.id
                     WHERE c.cliente_id = :cliente_id AND p.estado = 'activo'
                     ORDER BY c.fecha_agregado DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener items del carrito: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar items en el carrito
     */
    public function contarItems($cliente_id)
    {
        try {
            $query = "SELECT SUM(cantidad) as total_items FROM carrito 
                     INNER JOIN productos p ON carrito.producto_id = p.id
                     WHERE carrito.cliente_id = :cliente_id AND p.estado = 'activo'";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_items'] ?? 0;

        } catch (PDOException $e) {
            error_log("Error al contar items del carrito: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular total del carrito
     */
    public function calcularTotal($cliente_id)
    {
        try {
            $query = "SELECT SUM(c.cantidad * p.precio) as total 
                     FROM carrito c
                     INNER JOIN productos p ON c.producto_id = p.id
                     WHERE c.cliente_id = :cliente_id AND p.estado = 'activo'";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;

        } catch (PDOException $e) {
            error_log("Error al calcular total del carrito: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar disponibilidad de stock para todos los items del carrito
     */
    public function verificarStock($cliente_id)
    {
        try {
            $items = $this->obtenerItems($cliente_id);
            $items_sin_stock = [];

            foreach ($items as $item) {
                if ($item['stock'] < $item['cantidad']) {
                    $items_sin_stock[] = [
                        'producto_id' => $item['producto_id'],
                        'nombre' => $item['nombre'],
                        'cantidad_solicitada' => $item['cantidad'],
                        'stock_disponible' => $item['stock']
                    ];
                }
            }

            return [
                'tiene_stock' => empty($items_sin_stock),
                'items_sin_stock' => $items_sin_stock
            ];

        } catch (Exception $e) {
            error_log("Error al verificar stock del carrito: " . $e->getMessage());
            return [
                'tiene_stock' => false,
                'items_sin_stock' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener resumen del carrito (total, items, etc.)
     */
    public function obtenerResumen($cliente_id)
    {
        try {
            $items = $this->obtenerItems($cliente_id);
            $total_items = $this->contarItems($cliente_id);
            $subtotal = $this->calcularTotal($cliente_id);

            // Calcular impuestos (ejemplo: 16%)
            $impuestos = $subtotal * 0.00; // Sin impuestos por ahora
            $descuento = 0; // Sin descuentos por ahora
            $total = $subtotal + $impuestos - $descuento;

            return [
                'items' => $items,
                'total_items' => $total_items,
                'subtotal' => $subtotal,
                'impuestos' => $impuestos,
                'descuento' => $descuento,
                'total' => $total
            ];

        } catch (Exception $e) {
            error_log("Error al obtener resumen del carrito: " . $e->getMessage());
            return [
                'items' => [],
                'total_items' => 0,
                'subtotal' => 0,
                'impuestos' => 0,
                'descuento' => 0,
                'total' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si un producto específico está en el carrito
     */
    public function estaEnCarrito($cliente_id, $producto_id)
    {
        try {
            $query = "SELECT cantidad FROM carrito WHERE cliente_id = :cliente_id AND producto_id = :producto_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['cantidad'] : 0;

        } catch (PDOException $e) {
            error_log("Error al verificar producto en carrito: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Preparar datos del carrito para crear pedido
     */
    public function prepararParaPedido($cliente_id)
    {
        try {
            $resumen = $this->obtenerResumen($cliente_id);
            $verificacion_stock = $this->verificarStock($cliente_id);

            if (!$verificacion_stock['tiene_stock']) {
                return [
                    'success' => false,
                    'error' => 'Algunos productos no tienen stock suficiente',
                    'items_sin_stock' => $verificacion_stock['items_sin_stock']
                ];
            }

            if (empty($resumen['items'])) {
                return [
                    'success' => false,
                    'error' => 'El carrito está vacío'
                ];
            }

            // Preparar productos para el pedido
            $productos = [];
            foreach ($resumen['items'] as $item) {
                $productos[] = [
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio']
                ];
            }

            return [
                'success' => true,
                'productos' => $productos,
                'subtotal' => $resumen['subtotal'],
                'impuestos' => $resumen['impuestos'],
                'descuento' => $resumen['descuento'],
                'total' => $resumen['total']
            ];

        } catch (Exception $e) {
            error_log("Error al preparar carrito para pedido: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Limpiar carritos antiguos (más de 30 días sin actividad)
     */
    public function limpiarCarritosAntiguos($dias = 30)
    {
        try {
            $query = "DELETE FROM carrito WHERE fecha_agregado < DATE_SUB(NOW(), INTERVAL :dias DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':dias', $dias);

            $stmt->execute();
            return $stmt->rowCount();

        } catch (PDOException $e) {
            error_log("Error al limpiar carritos antiguos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Migrar carrito de sesión a usuario (para cuando el usuario se registra/inicia sesión)
     */
    public function migrarCarritoSesion($carrito_sesion, $cliente_id)
    {
        try {
            $this->conn->beginTransaction();

            foreach ($carrito_sesion as $producto_id => $cantidad) {
                $this->agregar($cliente_id, $producto_id, $cantidad);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al migrar carrito de sesión: " . $e->getMessage());
            return false;
        }
    }
}
?>