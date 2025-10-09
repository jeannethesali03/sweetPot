<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Usuario
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Crear nuevo usuario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO usuarios (nombre, email, password, telefono, direccion, rol, estado) 
                     VALUES (:nombre, :email, :password, :telefono, :direccion, :rol, :estado)";

            $stmt = $this->conn->prepare($query);

            // Hash de la contraseña
            $password_hash = hashPassword($datos['password']);

            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':email', $datos['email']);
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':telefono', $datos['telefono']);
            $stmt->bindParam(':direccion', $datos['direccion']);
            $stmt->bindParam(':rol', $datos['rol']);
            $stmt->bindParam(':estado', $datos['estado']);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Login de usuario
     */
    public function login($email, $password)
    {
        try {
            // Buscar el usuario por email (sin filtrar por estado) para poder detectar cuentas inactivas
            $query = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si no existe el usuario
            if (!$usuario) {
                return false;
            }

            // Verificar contraseña
            if (!verifyPassword($password, $usuario['password'])) {
                return false;
            }

            // Si la contraseña es correcta pero el usuario está inactivo, devolver señal específica
            if (!empty($usuario['estado']) && $usuario['estado'] !== 'activo') {
                return ['inactive' => true, 'user' => $usuario];
            }

            // Login exitoso: remover el hash antes de retornar
            unset($usuario['password']);
            return $usuario;

        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $query = "SELECT id, nombre, email, telefono, direccion, rol, estado, fecha_registro, ultima_conexion 
                     FROM usuarios WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener usuario por email
     */
    public function obtenerPorEmail($email)
    {
        try {
            $query = "SELECT id, nombre, email, telefono, direccion, rol, estado, fecha_registro 
                     FROM usuarios WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener usuario por email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar usuario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE usuarios SET nombre = :nombre, email = :email, telefono = :telefono, 
                     direccion = :direccion, rol = :rol, estado = :estado";

            // Si se proporciona nueva contraseña
            if (!empty($datos['password'])) {
                $query .= ", password = :password";
            }

            $query .= " WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':email', $datos['email']);
            $stmt->bindParam(':telefono', $datos['telefono']);
            $stmt->bindParam(':direccion', $datos['direccion']);
            $stmt->bindParam(':rol', $datos['rol']);
            $stmt->bindParam(':estado', $datos['estado']);

            if (!empty($datos['password'])) {
                $password_hash = hashPassword($datos['password']);
                $stmt->bindParam(':password', $password_hash);
            }

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado del usuario (activar/desactivar)
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $query = "UPDATE usuarios SET estado = :estado WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':estado', $estado);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al cambiar estado del usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar rol del usuario
     */
    public function cambiarRol($id, $rol)
    {
        try {
            $query = "UPDATE usuarios SET rol = :rol WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':rol', $rol);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al cambiar rol del usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar usuario
     */
    public function eliminar($id)
    {
        try {
            // Soft-delete: marcar el usuario como inactivo en lugar de eliminarlo físicamente
            $query = "UPDATE usuarios SET estado = 'inactivo' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar usuarios con filtros
     */
    public function listar($filtros = [])
    {
        try {
            // Configurar paginación
            $limit = $filtros['limit'] ?? 10;
            $page = $filtros['page'] ?? 1;
            $offset = ($page - 1) * $limit;

            // Query base para contar total
            $countQuery = "SELECT COUNT(*) as total FROM usuarios WHERE 1=1";

            // Query principal - usar los nombres de campo reales
            $query = "SELECT id, nombre, email, telefono, rol, estado as activo, fecha_registro, ultima_conexion as ultimo_acceso 
                     FROM usuarios WHERE 1=1";

            $whereConditions = "";
            $params = [];

            // Aplicar filtros
            if (!empty($filtros['rol'])) {
                $whereConditions .= " AND rol = :rol";
                $params[':rol'] = $filtros['rol'];
            }

            if (isset($filtros['activo'])) {
                // Convertir el filtro activo a estado
                $estado = $filtros['activo'] ? 'activo' : 'inactivo';
                $whereConditions .= " AND estado = :estado";
                $params[':estado'] = $estado;
            }

            if (!empty($filtros['search'])) {
                $whereConditions .= " AND (nombre LIKE :search1 OR email LIKE :search2)";
                $params[':search1'] = '%' . $filtros['search'] . '%';
                $params[':search2'] = '%' . $filtros['search'] . '%';
            }

            // Agregar condiciones WHERE a ambas queries
            $countQuery .= $whereConditions;
            $query .= $whereConditions;

            // Ordenamiento
            $query .= " ORDER BY fecha_registro DESC";

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

            // Convertir estado a boolean para activo
            foreach ($data as &$usuario) {
                $usuario['activo'] = ($usuario['activo'] === 'activo') ? 1 : 0;
            }

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
            error_log("Error al listar usuarios: " . $e->getMessage());
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
     * Contar usuarios
     */
    public function contar($filtros = [])
    {
        try {
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE 1=1";

            // Aplicar filtros
            if (!empty($filtros['rol'])) {
                $query .= " AND rol = :rol";
            }

            if (!empty($filtros['estado'])) {
                $query .= " AND estado = :estado";
            }

            if (!empty($filtros['buscar'])) {
                $query .= " AND (nombre LIKE :buscar OR email LIKE :buscar)";
            }

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            if (!empty($filtros['rol'])) {
                $stmt->bindParam(':rol', $filtros['rol']);
            }

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
            error_log("Error al contar usuarios: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar si existe un email
     */
    public function existeEmail($email, $excluir_id = null)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email";

            if ($excluir_id) {
                $query .= " AND id != :excluir_id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);

            if ($excluir_id) {
                $stmt->bindParam(':excluir_id', $excluir_id);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'] > 0;

        } catch (PDOException $e) {
            error_log("Error al verificar email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar última conexión
     */
    public function actualizarUltimaConexion($id)
    {
        try {
            $query = "UPDATE usuarios SET ultima_conexion = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al actualizar última conexión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function obtenerEstadisticas()
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN rol = 'cliente' THEN 1 ELSE 0 END) as cliente,
                        SUM(CASE WHEN rol = 'vendedor' THEN 1 ELSE 0 END) as vendedor,
                        SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as admin,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
                      FROM usuarios";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de usuarios: " . $e->getMessage());
            return [
                'total' => 0,
                'cliente' => 0,
                'vendedor' => 0,
                'admin' => 0,
                'activos' => 0,
                'inactivos' => 0
            ];
        }
    }

    /**
     * Contar usuarios por rol
     */
    public function contarPorRol()
    {
        try {
            $sql = "SELECT 
                        SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as admin,
                        SUM(CASE WHEN rol = 'vendedor' THEN 1 ELSE 0 END) as vendedor,
                        SUM(CASE WHEN rol = 'cliente' THEN 1 ELSE 0 END) as cliente,
                        COUNT(*) as total
                    FROM usuarios 
                    WHERE estado = 'activo'";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en Usuario::contarPorRol(): " . $e->getMessage());
            throw new Exception("Error al contar usuarios por rol");
        }
    }
}
?>