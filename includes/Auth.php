<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../config/config.php';

class Auth
{

    // Iniciar sesión
    public static function login($email, $password)
    {
        $usuario = new Usuario();
        $userData = $usuario->login($email, $password);

        if ($userData) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['nombre'] = $userData['nombre'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['telefono'] = $userData['telefono'] ?? '';
            $_SESSION['direccion'] = $userData['direccion'] ?? '';
            $_SESSION['rol'] = $userData['rol'];
            $_SESSION['estado'] = $userData['estado'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();

            // Actualizar última conexión
            $usuario->actualizarUltimaConexion($userData['id']);

            return true;
        }

        return false;
    }

    // Cerrar sesión
    public static function logout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        return true;
    }

    // Verificar si el usuario está logueado
    public static function isLoggedIn()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }

        // Verificar si la sesión ha expirado
        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
        }

        return true;
    }

    // Obtener datos del usuario actual
    public static function getUser()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'nombre' => $_SESSION['nombre'],
                'email' => $_SESSION['email'],
                'telefono' => $_SESSION['telefono'],
                'direccion' => $_SESSION['direccion'],
                'rol' => $_SESSION['rol'],
                'estado' => $_SESSION['estado']
            ];
        }

        return false;
    }

    // Verificar si el usuario es administrador
    public static function isAdmin()
    {
        $user = self::getUser();
        return $user && $user['rol'] === 'administrador';
    }

    // Verificar si el usuario es cliente
    public static function isCliente()
    {
        $user = self::getUser();
        return $user && $user['rol'] === 'cliente';
    }

    // Verificar si el usuario es vendedor
    public static function isVendedor()
    {
        $user = self::getUser();
        return $user && $user['rol'] === 'vendedor';
    }

    // Requerir autenticación
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . URL . 'login.php');
            exit();
        }
    }

    // Requerir rol de administrador
    public static function requireAdmin()
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            header('Location: ' . URL . 'acceso_denegado.php');
            exit();
        }
    }

    // Requerir rol específico
    public static function requireRole($rol)
    {
        self::requireLogin();

        $user = self::getUser();
        if (!$user || $user['rol'] !== $rol) {
            header('Location: ' . URL . 'acceso_denegado.php');
            exit();
        }
    }

    // Generar token CSRF
    public static function generateCSRFToken()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    // Verificar token CSRF
    public static function verifyCSRFToken($token)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>