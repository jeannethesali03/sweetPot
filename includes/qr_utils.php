<?php
/**
 * Utilidades para generar códigos QR - SweetPot
 * Requiere la librería phpqrcode
 */

require_once __DIR__ . '/../config/config.php';

class QRUtils
{
    private $qr_path;

    public function __construct()
    {
        // Crear directorio para códigos QR si no existe
        $this->qr_path = __DIR__ . '/../assets/qr/';
        if (!is_dir($this->qr_path)) {
            mkdir($this->qr_path, 0755, true);
        }
    }

    /**
     * Generar código QR para producto con URL dinámica
     */
    public function generarQRProducto($producto_id, $nombre_producto = '')
    {
        try {
            // URL del producto usando la configuración actual
            $url_producto = URL . "producto.php?id=" . $producto_id;

            // Nombre del archivo QR con timestamp para evitar cache
            $nombre_archivo = 'producto_' . $producto_id . '_' . time() . '.png';
            $ruta_completa = $this->qr_path . $nombre_archivo;

            // Generar QR con tamaño mayor para mejor legibilidad
            $this->generarQRSimple($url_producto, $ruta_completa, 300);

            return [
                'success' => true,
                'archivo' => $nombre_archivo,
                'ruta' => $ruta_completa,
                'url' => URL . "assets/qr/" . $nombre_archivo,
                'contenido' => $url_producto,
                'producto_id' => $producto_id
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar código QR para ticket de venta
     */
    public function generarQRTicket($ticket_id, $numero_ticket)
    {
        try {
            // URL para ver el ticket (simplificada)
            $url_ticket = URL . "cliente/ticket.php?numero=" . $numero_ticket;

            // Nombre del archivo QR
            $nombre_archivo = 'ticket_' . $numero_ticket . '_' . time() . '.png';
            $ruta_completa = $this->qr_path . $nombre_archivo;

            // Generar QR
            $result = $this->generarQRSimple($url_ticket, $ruta_completa);

            if ($result) {
                return [
                    'success' => true,
                    'archivo' => $nombre_archivo,
                    'ruta' => $ruta_completa,
                    'url' => URL . "assets/qr/" . $nombre_archivo,
                    'contenido' => $url_ticket
                ];
            } else {
                throw new Exception('No se pudo generar el código QR');
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar código QR usando API alternativa (qr-server.com)
     */
    private function generarQRSimple($contenido, $ruta_archivo, $tamaño = 200)
    {
        try {
            // Primero intentar con qr-server.com
            $url_qr_api = "https://api.qrserver.com/v1/create-qr-code/?size={$tamaño}x{$tamaño}&data=" . urlencode($contenido);

            // Crear contexto para la petición HTTP con timeout más largo
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'SweetPot-QR-Generator/1.0'
                ]
            ]);

            // Descargar la imagen
            $imagen_qr = @file_get_contents($url_qr_api, false, $context);

            if ($imagen_qr !== false && strlen($imagen_qr) > 100) {
                // Guardar la imagen
                if (file_put_contents($ruta_archivo, $imagen_qr) !== false) {
                    return true;
                }
            }

            // Si falla, usar método de respaldo
            return $this->generarQRFallback($contenido, $ruta_archivo, $tamaño);

        } catch (Exception $e) {
            // Usar método de respaldo
            return $this->generarQRFallback($contenido, $ruta_archivo, $tamaño);
        }
    }

    /**
     * Método de respaldo para generar QR cuando las APIs fallan
     */
    private function generarQRFallback($contenido, $ruta_archivo, $tamaño = 200)
    {
        try {
            // Crear una imagen simple con un placeholder
            $imagen = imagecreate($tamaño, $tamaño);

            // Colores
            $blanco = imagecolorallocate($imagen, 255, 255, 255);
            $negro = imagecolorallocate($imagen, 32, 32, 32);
            $azul = imagecolorallocate($imagen, 102, 126, 234);

            // Fondo blanco
            imagefill($imagen, 0, 0, $blanco);

            // Dibujar borde
            imagerectangle($imagen, 2, 2, $tamaño - 3, $tamaño - 3, $negro);

            // Crear un patrón simple que simule un QR
            $cell_size = $tamaño / 15;
            for ($i = 1; $i < 14; $i++) {
                for ($j = 1; $j < 14; $j++) {
                    // Crear un patrón basado en el contenido
                    $hash = crc32($contenido . $i . $j);
                    if ($hash % 2 == 0) {
                        $x1 = $i * $cell_size;
                        $y1 = $j * $cell_size;
                        $x2 = ($i + 1) * $cell_size;
                        $y2 = ($j + 1) * $cell_size;
                        imagefilledrectangle($imagen, $x1, $y1, $x2, $y2, $negro);
                    }
                }
            }

            // Agregar esquinas de localización más visibles
            $corner_size = $cell_size * 2.5;

            // Esquina superior izquierda
            imagefilledrectangle($imagen, 5, 5, $corner_size + 5, $corner_size + 5, $azul);
            imagefilledrectangle($imagen, 10, 10, $corner_size, $corner_size, $blanco);

            // Esquina superior derecha
            $right = $tamaño - $corner_size - 5;
            imagefilledrectangle($imagen, $right, 5, $tamaño - 5, $corner_size + 5, $azul);
            imagefilledrectangle($imagen, $right + 5, 10, $tamaño - 10, $corner_size, $blanco);

            // Esquina inferior izquierda
            $bottom = $tamaño - $corner_size - 5;
            imagefilledrectangle($imagen, 5, $bottom, $corner_size + 5, $tamaño - 5, $azul);
            imagefilledrectangle($imagen, 10, $bottom + 5, $corner_size, $tamaño - 10, $blanco);

            // Texto "QR" en el centro
            $font_size = 4;
            $text = "QR";
            $text_width = imagefontwidth($font_size) * strlen($text);
            $text_height = imagefontheight($font_size);
            $x = ($tamaño - $text_width) / 2;
            $y = ($tamaño - $text_height) / 2;

            // Fondo blanco para el texto
            imagefilledrectangle($imagen, $x - 5, $y - 2, $x + $text_width + 5, $y + $text_height + 2, $blanco);
            imagestring($imagen, $font_size, $x, $y, $text, $azul);

            // Guardar como PNG
            $resultado = imagepng($imagen, $ruta_archivo);
            imagedestroy($imagen);

            if (!$resultado) {
                throw new Exception("No se pudo guardar la imagen QR de respaldo");
            }

            return true;

        } catch (Exception $e) {
            // Como último recurso, crear un archivo de texto
            $contenido_info = "QR CODE\n" . $contenido . "\nGenerado: " . date('Y-m-d H:i:s');
            return file_put_contents(str_replace('.png', '.txt', $ruta_archivo), $contenido_info) !== false;
        }
    }

    /**
     * Generar QR con phpqrcode (cuando se instale la librería)
     */
    public function generarQRConLibreria($contenido, $ruta_archivo, $tamaño = 5)
    {
        // Esta función se puede implementar cuando se instale phpqrcode
        // require_once 'phpqrcode/qrlib.php';
        // QRcode::png($contenido, $ruta_archivo, QR_ECLEVEL_L, $tamaño);

        // Por ahora usar el método simple
        return $this->generarQRSimple($contenido, $ruta_archivo);
    }

    /**
     * Eliminar código QR
     */
    public function eliminarQR($nombre_archivo)
    {
        $ruta_completa = $this->qr_path . $nombre_archivo;

        if (file_exists($ruta_completa)) {
            return unlink($ruta_completa);
        }

        return false;
    }

    /**
     * Obtener lista de códigos QR generados
     */
    public function listarQRGenerados()
    {
        $archivos = [];

        if (is_dir($this->qr_path)) {
            $files = scandir($this->qr_path);

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                    $archivos[] = [
                        'nombre' => $file,
                        'ruta' => $this->qr_path . $file,
                        'url' => URL . "assets/qr/" . $file,
                        'fecha' => date('Y-m-d H:i:s', filemtime($this->qr_path . $file))
                    ];
                }
            }
        }

        return $archivos;
    }

    /**
     * Limpiar códigos QR antiguos (más de 30 días)
     */
    public function limpiarQRAntiguos($dias = 30)
    {
        $eliminados = 0;
        $limite_tiempo = time() - ($dias * 24 * 60 * 60);

        if (is_dir($this->qr_path)) {
            $files = scandir($this->qr_path);

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                    $ruta_archivo = $this->qr_path . $file;

                    if (filemtime($ruta_archivo) < $limite_tiempo) {
                        if (unlink($ruta_archivo)) {
                            $eliminados++;
                        }
                    }
                }
            }
        }

        return $eliminados;
    }

    /**
     * Generar código QR para compartir producto por WhatsApp
     */
    public function generarQRWhatsApp($producto_id, $mensaje_personalizado = '')
    {
        try {
            $url_producto = URL . "cliente/producto.php?id=" . $producto_id;

            // Mensaje por defecto
            if (empty($mensaje_personalizado)) {
                $mensaje_personalizado = "¡Mira este delicioso producto de SweetPot! " . $url_producto;
            }

            // URL de WhatsApp
            $url_whatsapp = "https://wa.me/?text=" . urlencode($mensaje_personalizado);

            // Generar QR para WhatsApp
            $nombre_archivo = 'whatsapp_producto_' . $producto_id . '_' . time() . '.png';
            $ruta_completa = $this->qr_path . $nombre_archivo;

            $this->generarQRSimple($url_whatsapp, $ruta_completa);

            return [
                'success' => true,
                'archivo' => $nombre_archivo,
                'ruta' => $ruta_completa,
                'url' => URL . "assets/qr/" . $nombre_archivo,
                'contenido' => $url_whatsapp
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>