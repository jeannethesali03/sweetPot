<?php
/**
 * Funciones de ayuda para SweetPot
 */

/**
 * Función para generar contraseñas hash
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Función para verificar contraseñas
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Función para limpiar y sanitizar entrada de datos
 */
function cleanInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Función para validar email
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Función para generar números aleatorios
 */
function generateRandomNumber($length = 6)
{
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Función para formatear precios
 */
function formatPrice($price)
{
    return '$' . number_format($price, 2);
}

/**
 * Función para formatear fechas
 */
function formatDate($date, $format = 'd/m/Y H:i')
{
    return date($format, strtotime($date));
}

/**
 * Función para crear URLs amigables
 */
function createSlug($string)
{
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Función para redireccionar
 */
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

/**
 * Función para mostrar alertas con SweetAlert2
 */
function setAlert($type, $title, $message)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message
    ];
}

/**
 * Función para mostrar y limpiar alertas
 */
function showAlert()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);

        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '{$alert['type']}',
                title: '{$alert['title']}',
                text: '{$alert['message']}',
                confirmButtonColor: '#ff6b9d'
            });
        });
        </script>";
    }
}

/**
 * Función para calcular el total de un carrito
 */
function calculateCartTotal($cartItems)
{
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    return $total;
}

/**
 * Función para generar código de producto único
 */
function generateProductCode($categoria_id, $nombre)
{
    $prefijo = strtoupper(substr($nombre, 0, 3));
    $codigo = $prefijo . str_pad($categoria_id, 2, '0', STR_PAD_LEFT) . generateRandomNumber(3);
    return $codigo;
}

/**
 * Función para validar stock disponible
 */
function validateStock($producto_id, $cantidad_solicitada)
{
    require_once __DIR__ . '/../models/Producto.php';
    $producto = new Producto();
    $data = $producto->obtenerPorId($producto_id);

    if (!$data) {
        return false;
    }

    return $data['stock'] >= $cantidad_solicitada;
}

/**
 * Función para generar breadcrumbs
 */
function generateBreadcrumb($items)
{
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';

    foreach ($items as $key => $item) {
        if ($key === array_key_last($items)) {
            $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . $item['text'] . '</li>';
        } else {
            $breadcrumb .= '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . $item['text'] . '</a></li>';
        }
    }

    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}

/**
 * Función para paginar resultados
 */
function paginate($total_records, $records_per_page, $current_page, $base_url)
{
    $total_pages = ceil($total_records / $records_per_page);

    if ($total_pages <= 1) {
        return '';
    }

    $pagination = '<nav aria-label="Paginación"><ul class="pagination justify-content-center">';

    // Botón anterior
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $prev_page . '">Anterior</a></li>';
    }

    // Números de página
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }

    // Botón siguiente
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $next_page . '">Siguiente</a></li>';
    }

    $pagination .= '</ul></nav>';
    return $pagination;
}

/**
 * Función para obtener estados de pedido con colores
 */
function getEstadoPedido($estado)
{
    $estados = [
        'pendiente' => ['texto' => 'Pendiente', 'color' => 'warning'],
        'en_proceso' => ['texto' => 'En Proceso', 'color' => 'info'],
        'enviado' => ['texto' => 'Enviado', 'color' => 'primary'],
        'entregado' => ['texto' => 'Entregado', 'color' => 'success'],
        'cancelado' => ['texto' => 'Cancelado', 'color' => 'danger']
    ];

    return $estados[$estado] ?? ['texto' => 'Desconocido', 'color' => 'secondary'];
}

/**
 * Función para comprimir imágenes (si se necesitara en el futuro)
 */
function resizeImage($source, $destination, $width, $height, $quality = 80)
{
    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false;
    }

    $thumb = imagecreatetruecolor($width, $height);
    imagecopyresized($thumb, $image, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);

    if ($info['mime'] == 'image/jpeg') {
        imagejpeg($thumb, $destination, $quality);
    } elseif ($info['mime'] == 'image/gif') {
        imagegif($thumb, $destination);
    } elseif ($info['mime'] == 'image/png') {
        imagepng($thumb, $destination);
    }

    imagedestroy($image);
    imagedestroy($thumb);

    return true;
}

/**
 * Función para obtener la URL correcta de imagen de producto
 */
function getProductImageUrl($imagePath)
{
    if (empty($imagePath)) {
        return null;
    }

    // Si ya es una URL completa (empieza con http), devolverla tal como está
    if (strpos($imagePath, 'http') === 0) {
        return $imagePath;
    }

    // Si empieza con 'uploads/', asumir que es relativa desde la raíz
    if (strpos($imagePath, 'uploads/') === 0) {
        return URL . $imagePath;
    }

    // Si es solo el nombre del archivo, agregar la ruta completa
    if (strpos($imagePath, '/') === false) {
        return URL . 'uploads/productos/' . $imagePath;
    }

    // En otros casos, asumir que es relativa desde la raíz
    return URL . ltrim($imagePath, '/');
}

/**
 * Función para mostrar imagen de producto con fallback
 */
function displayProductImage($imagePath, $productName, $cssClass = '', $style = '')
{
    $imageUrl = getProductImageUrl($imagePath);

    if ($imageUrl) {
        return '<img src="' . htmlspecialchars($imageUrl) . '" 
                     alt="' . htmlspecialchars($productName) . '" 
                     class="' . $cssClass . '" 
                     style="' . $style . '" 
                     onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    }

    return '';
}
?>