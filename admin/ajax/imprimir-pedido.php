<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../models/Venta.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

// Verificar parámetro ID
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de pedido inválido');
}

$ventaModel = new Venta();
$pedido = $ventaModel->obtenerPorId($_GET['id']);

if (!$pedido) {
    die('Pedido no encontrado');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido <?php echo htmlspecialchars($pedido['numero_pedido']); ?> - SweetPot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #8b4513;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #8b4513;
            margin-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-section h3 {
            color: #8b4513;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 40%;
        }

        .info-value {
            width: 60%;
        }

        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .productos-table th,
        .productos-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .productos-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .productos-table .text-center {
            text-align: center;
        }

        .productos-table .text-right {
            text-align: right;
        }

        .totales {
            float: right;
            width: 300px;
            margin-top: 20px;
        }

        .totales table {
            width: 100%;
            border-collapse: collapse;
        }

        .totales td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        .totales .total-final {
            background-color: #8b4513;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pendiente {
            background-color: #ffc107;
            color: #000;
        }

        .status-en_proceso {
            background-color: #17a2b8;
            color: #fff;
        }

        .status-enviado {
            background-color: #6c757d;
            color: #fff;
        }

        .status-entregado {
            background-color: #28a745;
            color: #fff;
        }

        .status-cancelado {
            background-color: #dc3545;
            color: #fff;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .direccion-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #8b4513;
            margin: 15px 0;
        }

        .comentarios-box {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }

        @media print {
            body {
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()"
            style="background: #8b4513; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
            🖨️ Imprimir
        </button>
        <button onclick="window.close()"
            style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-left: 10px;">
            ✖️ Cerrar
        </button>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="logo">🧁 SweetPot - Repostería Artesanal</div>
        <div>Teléfono: (555) 123-4567 | Email: info@sweetpot.com</div>
        <div>Dirección: Calle Principal 123, Ciudad</div>
    </div>

    <!-- Información del Pedido -->
    <div class="info-grid">
        <div class="info-section">
            <h3>📋 Información del Pedido</h3>
            <div class="info-row">
                <span class="info-label">Número de Pedido:</span>
                <span
                    class="info-value"><strong><?php echo htmlspecialchars($pedido['numero_pedido']); ?></strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha del Pedido:</span>
                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Estado:</span>
                <span class="info-value">
                    <span class="status-badge status-<?php echo $pedido['estado']; ?>">
                        <?php echo strtoupper(str_replace('_', ' ', $pedido['estado'])); ?>
                    </span>
                </span>
            </div>
            <?php if (!empty($pedido['vendedor_nombre'])): ?>
                <div class="info-row">
                    <span class="info-label">Vendedor:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['vendedor_nombre']); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="info-section">
            <h3>👤 Información del Cliente</h3>
            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['cliente_email']); ?></span>
            </div>
            <?php if (!empty($pedido['cliente_telefono'])): ?>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['cliente_telefono']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dirección de Entrega -->
    <?php if (!empty($pedido['direccion_entrega'])): ?>
        <div class="direccion-box">
            <strong>📍 Dirección de Entrega:</strong><br>
            <?php echo nl2br(htmlspecialchars($pedido['direccion_entrega'])); ?>
        </div>
    <?php endif; ?>

    <!-- Comentarios -->
    <?php if (!empty($pedido['comentarios'])): ?>
        <div class="comentarios-box">
            <strong>💬 Comentarios:</strong><br>
            <?php echo nl2br(htmlspecialchars($pedido['comentarios'])); ?>
        </div>
    <?php endif; ?>

    <!-- Productos -->
    <h3 style="color: #8b4513; border-bottom: 2px solid #8b4513; padding-bottom: 5px;">🛒 Productos del Pedido</h3>

    <table class="productos-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Precio Unitario</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalProductos = 0;
            foreach ($pedido['productos'] as $producto):
                $totalProductos += $producto['cantidad'];
                ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($producto['producto_nombre']); ?></strong>
                        <br><small
                            style="color: #666;"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></small>
                    </td>
                    <td class="text-center"><?php echo $producto['cantidad']; ?></td>
                    <td class="text-right">$<?php echo number_format($producto['precio_unitario'], 2); ?></td>
                    <td class="text-right"><strong>$<?php echo number_format($producto['subtotal'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot style="background-color: #f8f9fa;">
            <tr>
                <td colspan="3"><strong>Total de Productos: <?php echo $totalProductos; ?></strong></td>
                <td class="text-right"><strong>$<?php echo number_format($pedido['subtotal'], 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Totales -->
    <div class="totales">
        <table>
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">$<?php echo number_format($pedido['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td><strong>Impuestos (16%):</strong></td>
                <td class="text-right">$<?php echo number_format($pedido['impuestos'], 2); ?></td>
            </tr>
            <?php if ($pedido['descuento'] > 0): ?>
                <tr>
                    <td><strong>Descuento:</strong></td>
                    <td class="text-right" style="color: red;">-$<?php echo number_format($pedido['descuento'], 2); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="total-final">
                <td><strong>TOTAL A PAGAR:</strong></td>
                <td class="text-right"><strong>$<?php echo number_format($pedido['total'], 2); ?></strong></td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>¡Gracias por elegir SweetPot!</strong></p>
        <p>Este documento fue generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>Para cualquier consulta sobre su pedido, contáctenos al (555) 123-4567</p>
        <p style="margin-top: 20px; font-style: italic;">
            "Endulzando tus momentos especiales con amor y dedicación"
        </p>
    </div>

    <script>
        // Auto-imprimir si se desea
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>