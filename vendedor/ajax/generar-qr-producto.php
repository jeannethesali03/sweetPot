<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../models/Producto.php';
require_once '../../includes/qr_utils.php';

// Requerir que el usuario sea vendedor o admin
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$user = Auth::getUser();
if (!in_array($user['rol'], ['vendedor', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permisos insuficientes']);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }

    if (empty($data['id']) || !is_numeric($data['id'])) {
        throw new Exception('ID de producto inválido');
    }

    $formato = isset($data['formato']) ? $data['formato'] : 'imagen';
    if (!in_array($formato, ['imagen', 'pdf'])) {
        throw new Exception('Formato inválido');
    }

    $productoModel = new Producto();
    $producto = $productoModel->obtenerPorId($data['id']);

    if (!$producto) {
        throw new Exception('Producto no encontrado');
    }

    $qrUtils = new QRUtils();
    $qr_result = $qrUtils->generarQRProducto($data['id'], $producto['nombre']);

    if (!$qr_result['success']) {
        throw new Exception('Error al generar código QR: ' . $qr_result['error']);
    }

    // Actualizar base de datos con ruta del QR si existe la función
    if (method_exists($productoModel, 'actualizarQR')) {
        $productoModel->actualizarQR($data['id'], $qr_result['archivo']);
    }

    $response = [
        'success' => true,
        'message' => 'Código QR generado exitosamente',
        'qr_url' => $qr_result['url'],
        'producto_url' => $qr_result['contenido'],
        'filename' => $qr_result['archivo']
    ];

    if ($formato === 'pdf') {
        $pdf_result = generarPDFConQR($producto, $qr_result);
        if ($pdf_result['success']) {
            $response['download_url'] = $pdf_result['url'];
            $response['filename'] = $pdf_result['filename'];
        } else {
            $response['download_url'] = $qr_result['url'];
        }
    } else {
        $response['download_url'] = $qr_result['url'];
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Copiar funciones auxiliares para generar PDF (idénticas a admin/ajax/generar-qr-producto.php)
 */
function generarPDFConQR($producto, $qr_result)
{
    try {
        $pdf_content = generarHTMLParaPDF($producto, $qr_result);
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
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function generarHTMLParaPDF($producto, $qr_result)
{
    // Reusar la misma plantilla que en admin; para brevedad, incluir una versión compacta
    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>QR ' . htmlspecialchars($producto['nombre']) . '</title></head><body>';
    $html .= '<h2>' . htmlspecialchars($producto['nombre']) . '</h2>';
    $html .= '<img src="' . $qr_result['url'] . '" alt="QR">';
    $html .= '<p>URL: ' . $qr_result['contenido'] . '</p>';
    $html .= '</body></html>';
    return $html;
}
