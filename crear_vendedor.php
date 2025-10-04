<?php
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Crear usuario vendedor
    $passwordHash = password_hash('vendedor123', PASSWORD_DEFAULT);

    $query = "INSERT INTO usuarios (nombre, email, password, rol, activo, fecha_registro) 
              VALUES (?, ?, ?, ?, ?, NOW())
              ON DUPLICATE KEY UPDATE password = ?, rol = ?, activo = ?";

    $stmt = $conn->prepare($query);
    $nombre = "Vendedor SweetPot";
    $email = "vendedor@sweetpot.com";
    $rol = "vendedor";
    $activo = 1;

    if (
        $stmt->execute([
            $nombre,
            $email,
            $passwordHash,
            $rol,
            $activo,
            $passwordHash,
            $rol,
            $activo
        ])
    ) {
        echo "<h2>✅ Usuario Vendedor Creado</h2>";
        echo "<p><strong>Email:</strong> vendedor@sweetpot.com</p>";
        echo "<p><strong>Password:</strong> vendedor123</p>";
        echo "<p><strong>Rol:</strong> vendedor</p>";
        echo "<hr>";
        echo "<a href='login.php' class='btn btn-primary'>Ir al Login</a>";
        echo "<br><br>";
        echo "<a href='logout_quick.php'>Cerrar sesión actual</a> (si hay alguna activa)";
    } else {
        echo "<h2>❌ Error</h2>";
        echo "<p>Error al crear usuario: " . print_r($stmt->errorInfo(), true) . "</p>";
    }

} catch (Exception $e) {
    echo "<h2>❌ Error de conexión</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Crear Usuario Vendedor - SweetPot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background: #ffeaa7;
        }

        .btn {
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <!-- Contenido generado arriba -->
</body>

</html>