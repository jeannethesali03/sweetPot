<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuarios'])) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        echo "<h2>🎯 Creando usuarios de prueba...</h2>";

        // Verificar si ya existen usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $total = $stmt->fetch()['total'];

        if ($total > 0) {
            echo "<p style='color: orange;'>⚠️ Ya existen $total usuarios en la base de datos</p>";
            echo "<p>¿Quieres continuar y agregar más usuarios de prueba?</p>";
            if (!isset($_POST['forzar'])) {
                echo "<form method='POST'>";
                echo "<input type='hidden' name='crear_usuarios' value='1'>";
                echo "<input type='hidden' name='forzar' value='1'>";
                echo "<button type='submit' style='background: #007bff; color: white; border: none; padding: 10px; border-radius: 5px;'>Sí, crear más usuarios</button>";
                echo " <a href='admin/usuarios.php' style='background: #6c757d; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>No, ir a usuarios</a>";
                echo "</form>";
                exit();
            }
        }

        // Usuarios de prueba
        $usuarios = [
            [
                'nombre' => 'Super Admin',
                'email' => 'admin@sweetpot.com',
                'password' => hashPassword('admin123'),
                'telefono' => '1234567890',
                'direccion' => 'Oficina Central SweetPot',
                'rol' => 'admin',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Carlos Vendedor',
                'email' => 'vendedor@sweetpot.com',
                'password' => hashPassword('vendedor123'),
                'telefono' => '1234567891',
                'direccion' => 'Sucursal Norte',
                'rol' => 'vendedor',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'María Cliente',
                'email' => 'cliente@sweetpot.com',
                'password' => hashPassword('cliente123'),
                'telefono' => '1234567892',
                'direccion' => 'Casa del Cliente #1',
                'rol' => 'cliente',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Ana Pérez',
                'email' => 'ana@example.com',
                'password' => hashPassword('123456'),
                'telefono' => '5551234567',
                'direccion' => 'Calle Ejemplo 123',
                'rol' => 'cliente',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Roberto Vendedor',
                'email' => 'roberto@sweetpot.com',
                'password' => hashPassword('123456'),
                'telefono' => '5559876543',
                'direccion' => 'Sucursal Sur',
                'rol' => 'vendedor',
                'estado' => 'inactivo'
            ]
        ];

        $sql = "INSERT INTO usuarios (nombre, email, password, telefono, direccion, rol, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);

        $creados = 0;
        foreach ($usuarios as $usuario) {
            try {
                // Verificar si el email ya existe
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
                $checkStmt->execute([$usuario['email']]);

                if ($checkStmt->fetchColumn() == 0) {
                    $stmt->execute([
                        $usuario['nombre'],
                        $usuario['email'],
                        $usuario['password'],
                        $usuario['telefono'],
                        $usuario['direccion'],
                        $usuario['rol'],
                        $usuario['estado']
                    ]);
                    echo "<p style='color: green;'>✅ Usuario creado: " . $usuario['nombre'] . " (" . $usuario['email'] . ")</p>";
                    $creados++;
                } else {
                    echo "<p style='color: orange;'>⚠️ Ya existe: " . $usuario['email'] . "</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error creando " . $usuario['email'] . ": " . $e->getMessage() . "</p>";
            }
        }

        echo "<hr>";
        echo "<h3>🎉 Proceso completado</h3>";
        echo "<p><strong>$creados usuarios nuevos creados</strong></p>";

        echo "<h4>📝 Credenciales de acceso:</h4>";
        echo "<ul>";
        echo "<li><strong>Admin:</strong> admin@sweetpot.com / admin123</li>";
        echo "<li><strong>Vendedor:</strong> vendedor@sweetpot.com / vendedor123</li>";
        echo "<li><strong>Cliente:</strong> cliente@sweetpot.com / cliente123</li>";
        echo "</ul>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }

    echo "<br><br>";
    echo "<a href='admin/usuarios.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Ver Usuarios</a>";
    echo "<a href='debug_db.php' style='background: #17a2b8; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Debug Base de Datos</a>";

} else {
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Crear Usuarios - SweetPot</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
            }

            .btn {
                background: #ff6b9d;
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                margin: 10px 5px;
            }

            .btn:hover {
                background: #e55a8a;
            }

            .btn-secondary {
                background: #6c757d;
            }

            .btn-secondary:hover {
                background: #545b62;
            }

            h1 {
                color: #ff6b9d;
            }

            .info {
                background: #e3f2fd;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
        </style>
    </head>

    <body>
        <h1>🍭 Crear Usuarios de Prueba - SweetPot</h1>

        <div class="info">
            <h3>ℹ️ ¿Qué hace este script?</h3>
            <p>Este script creará usuarios de prueba para que puedas testear el sistema:</p>
            <ul>
                <li><strong>1 Administrador:</strong> Con acceso completo al sistema</li>
                <li><strong>2 Vendedores:</strong> Para gestión de productos y pedidos</li>
                <li><strong>2 Clientes:</strong> Para realizar compras</li>
            </ul>
            <p><strong>Nota:</strong> Las contraseñas serán simples para pruebas (123456, admin123, etc.)</p>
        </div>

        <form method="POST">
            <input type="hidden" name="crear_usuarios" value="1">
            <button type="submit" class="btn">🚀 Crear Usuarios de Prueba</button>
            <a href="admin/usuarios.php" class="btn btn-secondary">🔙 Cancelar</a>
        </form>

        <hr>
        <p><a href="debug_db.php">🔍 Ver Debug de Base de Datos</a></p>
    </body>

    </html>
<?php } ?>