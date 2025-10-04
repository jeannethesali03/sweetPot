<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Categoria
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Crear nueva categoría
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO categorias (nombre, descripcion, estado, fecha_creacion) VALUES (:nombre, :descripcion, :estado, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':descripcion', $datos['descripcion']);
            $estado = ($datos['activo'] == 1) ? 'activo' : 'inactivo';
            $stmt->bindParam(':estado', $estado);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error al crear categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener categoría por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $query = "SELECT * FROM categorias WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar categoría
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE categorias SET nombre = :nombre, descripcion = :descripcion, estado = :estado WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':descripcion', $datos['descripcion']);
            $estado = ($datos['activo'] == 1) ? 'activo' : 'inactivo';
            $stmt->bindParam(':estado', $estado);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado de la categoría
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $query = "UPDATE categorias SET estado = :estado WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':estado', $estado);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al cambiar estado de categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar categoría
     */
    public function eliminar($id)
    {
        try {
            // Verificar si tiene productos asociados
            if ($this->tieneProductos($id)) {
                return ['error' => 'No se puede eliminar la categoría porque tiene productos asociados'];
            }

            $query = "DELETE FROM categorias WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al eliminar categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar categorías
     */
    public function listar($filtros = [])
    {
        try {
            // Si solo se necesita datos simples sin paginación
            if (empty($filtros['page']) && empty($filtros['limit'])) {
                $query = "SELECT c.*, 
                         (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id AND p.estado = 'activo') as total_productos
                         FROM categorias c WHERE 1=1";

                // Aplicar filtros
                if (!empty($filtros['estado'])) {
                    $query .= " AND c.estado = :estado";
                }

                // Soportar tanto 'buscar' como 'search'
                if (!empty($filtros['buscar'])) {
                    $query .= " AND (c.nombre LIKE :buscar1 OR c.descripcion LIKE :buscar2)";
                }
                if (!empty($filtros['search'])) {
                    $query .= " AND (c.nombre LIKE :search1 OR c.descripcion LIKE :search2)";
                }

                $query .= " ORDER BY c.nombre ASC";

                $stmt = $this->conn->prepare($query);

                // Bind parameters
                if (!empty($filtros['estado'])) {
                    $stmt->bindParam(':estado', $filtros['estado']);
                }

                if (!empty($filtros['buscar'])) {
                    $buscar = '%' . $filtros['buscar'] . '%';
                    $stmt->bindParam(':buscar1', $buscar);
                    $stmt->bindParam(':buscar2', $buscar);
                }

                if (!empty($filtros['search'])) {
                    $search = '%' . $filtros['search'] . '%';
                    $stmt->bindParam(':search1', $search);
                    $stmt->bindParam(':search2', $search);
                }

                $stmt->execute();
                return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            }

            // Configurar paginación
            $limit = $filtros['limit'] ?? 10;
            $page = $filtros['page'] ?? 1;
            $offset = ($page - 1) * $limit;

            // Query base para contar total
            $countQuery = "SELECT COUNT(*) as total FROM categorias c WHERE 1=1";

            // Query principal
            $query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id AND p.estado = 'activo') as total_productos
                     FROM categorias c WHERE 1=1";

            $whereConditions = "";
            $params = [];

            // Aplicar filtros
            if (isset($filtros['activo'])) {
                $estado_filtro = ($filtros['activo'] == 1) ? 'activo' : 'inactivo';
                $whereConditions .= " AND c.estado = :estado";
                $params[':estado'] = $estado_filtro;
            }

            if (!empty($filtros['search'])) {
                $whereConditions .= " AND (c.nombre LIKE :search1 OR c.descripcion LIKE :search2)";
                $params[':search1'] = '%' . $filtros['search'] . '%';
                $params[':search2'] = '%' . $filtros['search'] . '%';
            }

            // Soportar también 'buscar'
            if (!empty($filtros['buscar'])) {
                $whereConditions .= " AND (c.nombre LIKE :buscar1 OR c.descripcion LIKE :buscar2)";
                $params[':buscar1'] = '%' . $filtros['buscar'] . '%';
                $params[':buscar2'] = '%' . $filtros['buscar'] . '%';
            }

            // Agregar condiciones WHERE a ambas queries
            $countQuery .= $whereConditions;
            $query .= $whereConditions;

            // Ordenamiento
            $query .= " ORDER BY c.nombre ASC";

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
            error_log("Error al listar categorías: " . $e->getMessage());
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
     * Obtener categorías activas para selector
     */
    public function obtenerActivas()
    {
        try {
            $query = "SELECT id, nombre FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener categorías activas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar categorías
     */
    public function contar($filtros = [])
    {
        try {
            $query = "SELECT COUNT(*) as total FROM categorias WHERE 1=1";

            // Aplicar filtros
            if (!empty($filtros['estado'])) {
                $query .= " AND estado = :estado";
            }

            if (!empty($filtros['buscar'])) {
                $query .= " AND (nombre LIKE :buscar OR descripcion LIKE :buscar)";
            }

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            if (!empty($filtros['estado'])) {
                $stmt->bindParam(':estado', $filtros['estado']);
            }

            if (!empty($filtros['buscar'])) {
                $buscar = '%' . $filtros['buscar'] . '%';
                $stmt->bindParam(':buscar', $buscar);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'];

        } catch (PDOException $e) {
            error_log("Error al contar categorías: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar si existe una categoría con el mismo nombre
     */
    public function existeNombre($nombre, $excluir_id = null)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM categorias WHERE nombre = :nombre";

            if ($excluir_id) {
                $query .= " AND id != :excluir_id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nombre', $nombre);

            if ($excluir_id) {
                $stmt->bindParam(':excluir_id', $excluir_id);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'] > 0;

        } catch (PDOException $e) {
            error_log("Error al verificar nombre de categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si la categoría tiene productos asociados
     */
    public function tieneProductos($id)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;

        } catch (PDOException $e) {
            error_log("Error al verificar productos de categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de categorías
     */
    public function obtenerEstadisticas()
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activas,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivas
                      FROM categorias";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de categorías: " . $e->getMessage());
            return [
                'total' => 0,
                'activas' => 0,
                'inactivas' => 0
            ];
        }
    }

    /**
     * Obtener categorías más populares (con más productos)
     */
    public function obtenerMasPopulares($limite = 5)
    {
        try {
            $query = "SELECT c.*, 
                     COUNT(p.id) as total_productos,
                     SUM(p.stock) as stock_total
                     FROM categorias c
                     LEFT JOIN productos p ON c.id = p.categoria_id AND p.estado = 'activo'
                     WHERE c.estado = 'activo'
                     GROUP BY c.id
                     ORDER BY total_productos DESC
                     LIMIT :limite";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener categorías populares: " . $e->getMessage());
            return false;
        }
    }


}
?>