<?php
// Configuración de la aplicación
define("URL", "http://192.168.0.7:80/SweetPot/");
define("BASE_URL", "http://192.168.0.2:8888/SweetPot");
define("HOST", "localhost");
define("DB", "sweetpot_db");
define("USER", "root");
define("PASSWORD", "");
define("CHARSET", "utf8mb4");

// Configuración de sesión
define("SESSION_LIFETIME", 3600); // 1 hora en segundos
define("SECRET_KEY", "SweetPot_2025_SecretKey_!@#$%"); // Clave secreta para tokens

// Configuración de archivos
define("UPLOAD_PATH", "assets/uploads/");
define("MAX_FILE_SIZE", 5242880); // 5MB en bytes

// Configuración de la aplicación
define("APP_NAME", "SweetPot");
define("APP_VERSION", "1.0.0");

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de errores (cambiar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>