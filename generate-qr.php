<?php
/**
 * Generador dinámico de códigos QR - SweetPot
 * No guarda archivos, genera QR on-the-fly
 */

header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600'); // Cache por 1 hora

// Obtener parámetros
$data = $_GET['data'] ?? '';
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$size = (int) ($_GET['size'] ?? 200);

// Construir URL si se proporcionan type e id
if (!empty($type) && !empty($id)) {
    $base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

    switch ($type) {
        case 'producto':
            $data = $base_url . '/producto.php?id=' . $id;
            break;
        case 'ticket':
            $data = $base_url . '/cliente/ticket.php?id=' . $id;
            break;
        default:
            createErrorImage($size);
            exit;
    }
}

// Validar datos
if (empty($data)) {
    // Imagen de error
    createErrorImage($size);
    exit;
}

// Validar tamaño
$size = max(100, min(500, $size)); // Entre 100 y 500 px

try {
    // Intentar generar QR con API externa
    generateQRFromAPI($data, $size);
} catch (Exception $e) {
    // Fallback: crear imagen placeholder
    createPlaceholderQR($data, $size);
}

function generateQRFromAPI($data, $size)
{
    $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'SweetPot-QR/1.0'
        ]
    ]);

    $imageData = @file_get_contents($url, false, $context);

    if ($imageData && strlen($imageData) > 100) {
        echo $imageData;
        return;
    }

    throw new Exception('API failed');
}

function createPlaceholderQR($data, $size)
{
    $image = imagecreate($size, $size);

    // Colores
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 32, 32, 32);
    $blue = imagecolorallocate($image, 102, 126, 234);

    // Fondo blanco
    imagefill($image, 0, 0, $white);

    // Borde
    imagerectangle($image, 2, 2, $size - 3, $size - 3, $black);

    // Patrón simple
    $cellSize = $size / 12;
    for ($i = 1; $i < 11; $i++) {
        for ($j = 1; $j < 11; $j++) {
            if (crc32($data . $i . $j) % 2 == 0) {
                $x1 = $i * $cellSize;
                $y1 = $j * $cellSize;
                $x2 = ($i + 1) * $cellSize;
                $y2 = ($j + 1) * $cellSize;
                imagefilledrectangle($image, $x1, $y1, $x2, $y2, $black);
            }
        }
    }

    // Esquinas identificadoras
    $cornerSize = $cellSize * 2;

    // Superior izquierda
    imagefilledrectangle($image, 5, 5, $cornerSize + 5, $cornerSize + 5, $blue);
    imagefilledrectangle($image, 10, 10, $cornerSize, $cornerSize, $white);

    // Superior derecha
    $right = $size - $cornerSize - 5;
    imagefilledrectangle($image, $right, 5, $size - 5, $cornerSize + 5, $blue);

    // Inferior izquierda
    $bottom = $size - $cornerSize - 5;
    imagefilledrectangle($image, 5, $bottom, $cornerSize + 5, $size - 5, $blue);

    // Texto "QR"
    $fontSize = 3;
    $text = "QR";
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2;

    imagefilledrectangle($image, $x - 5, $y - 2, $x + $textWidth + 4, $y + $textHeight + 2, $white);
    imagestring($image, $fontSize, $x, $y, $text, $blue);

    imagepng($image);
    imagedestroy($image);
}

function createErrorImage($size)
{
    $image = imagecreate($size, $size);

    $white = imagecolorallocate($image, 255, 255, 255);
    $red = imagecolorallocate($image, 220, 53, 69);

    imagefill($image, 0, 0, $white);

    // X roja
    imageline($image, 10, 10, $size - 10, $size - 10, $red);
    imageline($image, 10, $size - 10, $size - 10, 10, $red);

    // Texto ERROR
    $text = "ERROR";
    $fontSize = 2;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $x = ($size - $textWidth) / 2;
    $y = $size / 2;

    imagestring($image, $fontSize, $x, $y, $text, $red);

    imagepng($image);
    imagedestroy($image);
}
?>