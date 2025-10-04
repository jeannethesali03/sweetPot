<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../models/Producto.php';
require_once '../../includes/qr_utils.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

header('Content-Type: application/json');

try {
    // Verificar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }

    // Validar datos requeridos
    if (empty($data['id']) || !is_numeric($data['id'])) {
        throw new Exception('ID de producto inválido');
    }

    $formato = isset($data['formato']) ? $data['formato'] : 'imagen';

    if (!in_array($formato, ['imagen', 'pdf'])) {
        throw new Exception('Formato inválido');
    }

    // Obtener información del producto
    $productoModel = new Producto();
    $producto = $productoModel->obtenerPorId($data['id']);

    if (!$producto) {
        throw new Exception('Producto no encontrado');
    }

    // Generar código QR
    $qrUtils = new QRUtils();
    $qr_result = $qrUtils->generarQRProducto($data['id'], $producto['nombre']);

    if (!$qr_result['success']) {
        throw new Exception('Error al generar código QR: ' . $qr_result['error']);
    }

    // Actualizar la base de datos con la nueva ruta del QR
    $productoModel->actualizarQR($data['id'], $qr_result['archivo']);

    $response = [
        'success' => true,
        'message' => 'Código QR generado exitosamente',
        'qr_url' => $qr_result['url'],
        'producto_url' => $qr_result['contenido'],
        'filename' => $qr_result['archivo']
    ];

    if ($formato === 'pdf') {
        // Generar PDF con información del producto y QR
        $pdf_result = generarPDFConQR($producto, $qr_result);
        if ($pdf_result['success']) {
            $response['download_url'] = $pdf_result['url'];
            $response['filename'] = $pdf_result['filename'];
        } else {
            // Si falla el PDF, usar la imagen como fallback
            $response['download_url'] = $qr_result['url'];
        }
    } else {
        // Formato imagen
        $response['download_url'] = $qr_result['url'];
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Generar PDF con código QR e información del producto
 */
function generarPDFConQR($producto, $qr_result)
{
    try {
        // Para simplicidad, crear un HTML que se puede "imprimir como PDF"
        $pdf_content = generarHTMLParaPDF($producto, $qr_result);

        // Crear archivo HTML temporal
        $pdf_filename = 'qr_producto_' . $producto['id'] . '_' . time() . '.html';
        $pdf_path = __DIR__ . '/../../assets/qr/' . $pdf_filename;

        if (file_put_contents($pdf_path, $pdf_content) === false) {
            throw new Exception("No se pudo crear el archivo PDF");
        }

        return [
            'success' => true,
            'filename' => $pdf_filename,
            'path' => $pdf_path,
            'url' => URL . 'assets/qr/' . $pdf_filename
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generar HTML para PDF con diseño profesional
 */
function generarHTMLParaPDF($producto, $qr_result)
{
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código QR - ' . htmlspecialchars($producto['nombre']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #8b4513;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 2.5rem;
            color: #8b4513;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .qr-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .qr-code {
            text-align: center;
            flex: 0 0 250px;
        }
        .qr-code img {
            max-width: 200px;
            border: 3px solid #8b4513;
            border-radius: 10px;
        }
        .product-info {
            flex: 1;
            margin-left: 30px;
        }
        .product-name {
            font-size: 1.8rem;
            color: #8b4513;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .product-details {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
        }
        .detail-value {
            color: #666;
        }
        .price {
            font-size: 1.5rem;
            color: #28a745;
            font-weight: bold;
        }
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
        }
        .instructions h3 {
            color: #856404;
            margin-top: 0;
        }
        .url-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            margin: 15px 0;
            border-left: 4px solid #8b4513;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #8b4513;
            color: #666;
        }
        @media print {
            body { margin: 0; padding: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🧁 SweetPot</div>
        <p>Repostería Artesanal - Código QR del Producto</p>
        <p style="color: #666; margin: 0;">Generado el ' . date('d/m/Y H:i:s') . '</p>
    </div>

    <div class="qr-section">
        <div class="qr-code">
            <img src="' . $qr_result['url'] . '" alt="Código QR">
            <p style="margin: 10px 0 0 0; font-weight: bold; color: #8b4513;">Escanea para ver el producto</p>
        </div>
        
        <div class="product-info">
            <div class="product-name">' . htmlspecialchars($producto['nombre']) . '</div>
            
            <div class="product-details">
                <div class="detail-row">
                    <span class="detail-label">Categoría:</span>
                    <span class="detail-value">' . htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría') . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Código:</span>
                    <span class="detail-value">' . htmlspecialchars($producto['codigo_producto'] ?? 'N/A') . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Stock disponible:</span>
                    <span class="detail-value">' . $producto['stock'] . ' unidades</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value">' . ucfirst($producto['estado']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Precio:</span>
                    <span class="detail-value price">$' . number_format($producto['precio'], 2) . '</span>
                </div>
            </div>
            
            ' . (!empty($producto['descripcion']) ? '
            <div style="margin-top: 15px;">
                <strong>Descripción:</strong>
                <p style="margin: 5px 0; color: #666; font-style: italic;">' . nl2br(htmlspecialchars($producto['descripcion'])) . '</p>
            </div>
            ' : '') . '
        </div>
    </div>

    <div class="instructions">
        <h3>📱 Instrucciones de uso:</h3>
        <ol>
            <li><strong>Escanea el código QR</strong> con cualquier aplicación de cámara o lector QR</li>
            <li><strong>Se abrirá automáticamente</strong> la página del producto en el navegador</li>
            <li><strong>Los clientes podrán ver</strong> toda la información y agregar el producto al carrito</li>
            <li><strong>Funciona desde cualquier dispositivo</strong> con cámara e internet</li>
        </ol>
    </div>

    <div class="url-box">
        <strong>URL del producto:</strong><br>
        ' . $qr_result['contenido'] . '
    </div>

    <div class="footer">
        <p><strong>SweetPot - Repostería Artesanal</strong></p>
        <p>Este código QR te llevará directamente al producto en nuestro catálogo online</p>
        <p style="margin-top: 15px; font-size: 0.9rem;">
            🏠 Dirección: Calle Principal 123, Ciudad<br>
            📞 Teléfono: (555) 123-4567<br>
            📧 Email: info@sweetpot.com
        </p>
    </div>

    <div class="no-print" style="text-align: center; margin: 30px 0;">
        <button onclick="window.print()" style="background: #8b4513; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 16px;">
            🖨️ Imprimir / Guardar como PDF
        </button>
    </div>
</body>
</html>';

    return $html;
}
?>