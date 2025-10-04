<?php
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "<h2>🍰 Creando datos de prueba para SweetPot</h2>";

    // 1. Crear categorías
    echo "<h3>📂 Creando categorías...</h3>";
    $categorias = [
        ['nombre' => 'Pasteles', 'descripcion' => 'Deliciosos pasteles artesanales', 'estado' => 'activo'],
        ['nombre' => 'Cupcakes', 'descripcion' => 'Cupcakes decorados con amor', 'estado' => 'activo'],
        ['nombre' => 'Galletas', 'descripcion' => 'Galletas crujientes y sabrosas', 'estado' => 'activo'],
        ['nombre' => 'Postres', 'descripcion' => 'Postres fríos y calientes', 'estado' => 'activo'],
        ['nombre' => 'Bebidas', 'descripcion' => 'Bebidas para acompañar', 'estado' => 'activo']
    ];

    foreach ($categorias as $categoria) {
        $query = "INSERT INTO categorias (nombre, descripcion, estado, fecha_creacion) 
                  VALUES (?, ?, ?, NOW()) 
                  ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$categoria['nombre'], $categoria['descripcion'], $categoria['estado']]);
        echo "✅ Categoría: {$categoria['nombre']}<br>";
    }

    // 2. Obtener IDs de categorías
    $query = "SELECT id, nombre FROM categorias";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $categoriasDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoriaIds = [];
    foreach ($categoriasDb as $cat) {
        $categoriaIds[$cat['nombre']] = $cat['id'];
    }

    // 3. Crear productos
    echo "<h3>🧁 Creando productos...</h3>";
    $productos = [
        [
            'categoria' => 'Pasteles',
            'nombre' => 'Pastel de Chocolate',
            'descripcion' => 'Delicioso pastel de chocolate con frosting de vainilla',
            'precio' => 350.00,
            'stock' => 15,
            'codigo_producto' => 'PAS-CHOC-001'
        ],
        [
            'categoria' => 'Pasteles',
            'nombre' => 'Pastel Red Velvet',
            'descripcion' => 'Clásico pastel red velvet con queso crema',
            'precio' => 420.00,
            'stock' => 8,
            'codigo_producto' => 'PAS-REDV-002'
        ],
        [
            'categoria' => 'Cupcakes',
            'nombre' => 'Cupcakes de Vainilla',
            'descripcion' => 'Pack de 6 cupcakes de vainilla con decoración',
            'precio' => 180.00,
            'stock' => 25,
            'codigo_producto' => 'CUP-VAIN-003'
        ],
        [
            'categoria' => 'Cupcakes',
            'nombre' => 'Cupcakes de Fresa',
            'descripcion' => 'Pack de 6 cupcakes de fresa con betún rosa',
            'precio' => 190.00,
            'stock' => 12,
            'codigo_producto' => 'CUP-FRES-004'
        ],
        [
            'categoria' => 'Galletas',
            'nombre' => 'Galletas de Chispas de Chocolate',
            'descripcion' => 'Docena de galletas crujientes con chispas',
            'precio' => 120.00,
            'stock' => 30,
            'codigo_producto' => 'GAL-CHIP-005'
        ],
        [
            'categoria' => 'Galletas',
            'nombre' => 'Galletas Decoradas',
            'descripcion' => 'Galletas personalizadas con royal icing',
            'precio' => 250.00,
            'stock' => 5, // Stock bajo para probar alertas
            'codigo_producto' => 'GAL-DECO-006'
        ],
        [
            'categoria' => 'Postres',
            'nombre' => 'Cheesecake de Fresa',
            'descripcion' => 'Cheesecake cremoso con topping de fresa',
            'precio' => 280.00,
            'stock' => 10,
            'codigo_producto' => 'POS-CHEE-007'
        ],
        [
            'categoria' => 'Postres',
            'nombre' => 'Tiramisú',
            'descripcion' => 'Clásico tiramisú italiano individual',
            'precio' => 85.00,
            'stock' => 20,
            'codigo_producto' => 'POS-TIRA-008'
        ],
        [
            'categoria' => 'Bebidas',
            'nombre' => 'Café Americano',
            'descripcion' => 'Café americano recién preparado',
            'precio' => 35.00,
            'stock' => 50,
            'codigo_producto' => 'BEB-CAFE-009'
        ],
        [
            'categoria' => 'Bebidas',
            'nombre' => 'Chocolate Caliente',
            'descripcion' => 'Chocolate caliente con marshmallows',
            'precio' => 45.00,
            'stock' => 3, // Stock bajo
            'codigo_producto' => 'BEB-CHOC-010'
        ]
    ];

    foreach ($productos as $producto) {
        $categoriaId = $categoriaIds[$producto['categoria']] ?? null;
        if (!$categoriaId) {
            echo "❌ No se encontró categoría: {$producto['categoria']}<br>";
            continue;
        }

        $query = "INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, stock_minimo, codigo_producto, estado, fecha_creacion) 
                  VALUES (?, ?, ?, ?, ?, 10, ?, 'activo', NOW()) 
                  ON DUPLICATE KEY UPDATE 
                  precio = VALUES(precio), 
                  stock = VALUES(stock),
                  descripcion = VALUES(descripcion)";

        $stmt = $conn->prepare($query);
        $stmt->execute([
            $categoriaId,
            $producto['nombre'],
            $producto['descripcion'],
            $producto['precio'],
            $producto['stock'],
            $producto['codigo_producto']
        ]);
        echo "✅ Producto: {$producto['nombre']} - \${$producto['precio']} (Stock: {$producto['stock']})<br>";
    }

    // 4. Verificar datos creados
    echo "<h3>📊 Resumen de datos creados:</h3>";

    $query = "SELECT COUNT(*) as total FROM categorias WHERE estado = 'activo'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalCategorias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $query = "SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalProductos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "✅ Categorías activas: <strong>$totalCategorias</strong><br>";
    echo "✅ Productos activos: <strong>$totalProductos</strong><br>";

    // Productos con stock bajo
    $query = "SELECT COUNT(*) as total FROM productos WHERE stock <= 10 AND estado = 'activo'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stockBajo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "⚠️ Productos con stock bajo: <strong>$stockBajo</strong><br>";

    echo "<hr>";
    echo "<h3>🎉 ¡Datos de prueba creados exitosamente!</h3>";
    echo "<p><a href='admin/productos.php' class='btn btn-primary'>Ver Productos en Admin</a></p>";
    echo "<p><a href='vendedor/productos.php' class='btn btn-success'>Ver Catálogo de Vendedor</a></p>";
    echo "<p><a href='cliente/productos.php' class='btn btn-info'>Ver Catálogo de Cliente</a></p>";

} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Crear Datos de Prueba - SweetPot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .btn {
            margin: 10px 5px;
            border-radius: 25px;
            padding: 10px 20px;
        }

        h2,
        h3 {
            color: #8b4513;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Contenido generado arriba -->
    </div>
</body>

</html>