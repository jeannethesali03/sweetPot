<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../models/Producto.php';
require_once '../includes/qr_utils.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

$pageTitle = "Regenerar Códigos QR - Admin SweetPot";
include '../includes/header.php';

// Procesar regeneración si se envió el formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerar'])) {
    try {
        $productoModel = new Producto();
        $qrUtils = new QRUtils();

        // Obtener todos los productos activos
        $productos = $productoModel->listar(['limit' => 1000]); // Límite alto para obtener todos
        $productos_data = $productos['data'] ?? [];

        $regenerados = 0;
        $errores = 0;
        $detalles = [];

        foreach ($productos_data as $producto) {
            try {
                // Eliminar QR anterior si existe
                if (!empty($producto['codigo_qr'])) {
                    $qrUtils->eliminarQR($producto['codigo_qr']);
                }

                // Generar nuevo QR
                $qr_result = $qrUtils->generarQRProducto($producto['id'], $producto['nombre']);

                if ($qr_result['success']) {
                    // Actualizar base de datos
                    $productoModel->actualizarQR($producto['id'], $qr_result['archivo']);
                    $regenerados++;
                    $detalles[] = "✅ {$producto['nombre']} - QR regenerado";
                } else {
                    $errores++;
                    $detalles[] = "❌ {$producto['nombre']} - Error: " . $qr_result['error'];
                }

            } catch (Exception $e) {
                $errores++;
                $detalles[] = "❌ {$producto['nombre']} - Error: " . $e->getMessage();
            }
        }

        $mensaje = "Regeneración completada: {$regenerados} códigos QR regenerados, {$errores} errores.";
        $tipo_mensaje = ($errores == 0) ? 'success' : 'warning';

    } catch (Exception $e) {
        $mensaje = "Error durante la regeneración: " . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 text-gradient-sweetpot">
                    <i class="fas fa-qrcode me-2"></i>
                    Regenerar Códigos QR
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="productos.php" class="btn btn-sweetpot-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver a Productos
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo $mensaje; ?>
                </div>

                <?php if (isset($detalles) && !empty($detalles)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Detalles de la regeneración</h5>
                        </div>
                        <div class="card-body">
                            <div style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($detalles as $detalle): ?>
                                    <div class="mb-1"><?php echo $detalle; ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-sync-alt me-2"></i>
                                Regenerar todos los códigos QR
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>¿Cuándo regenerar los códigos QR?</h6>
                                <ul class="mb-0">
                                    <li>Cuando hayas cambiado la <strong>URL base</strong> en la configuración</li>
                                    <li>Cuando hayas cambiado de <strong>red o IP</strong></li>
                                    <li>Cuando quieras <strong>actualizar todos los QR</strong> con la URL actual</li>
                                    <li>Cuando hayas migrado el sitio a <strong>otro servidor</strong></li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h6>Configuración actual:</h6>
                                <div class="bg-light p-3 rounded">
                                    <strong>URL base:</strong> <?php echo URL; ?><br>
                                    <strong>Ejemplo de URL de producto:</strong> <?php echo URL; ?>producto.php?id=1
                                </div>
                            </div>

                            <form method="POST" onsubmit="return confirmarRegeneracion()">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Importante:</h6>
                                    <p class="mb-0">Esta acción regenerará <strong>todos</strong> los códigos QR de
                                        productos usando la URL actual. Los códigos QR anteriores serán reemplazados.
                                    </p>
                                </div>

                                <div class="text-center">
                                    <button type="submit" name="regenerar" class="btn btn-warning btn-lg">
                                        <i class="fas fa-sync-alt me-2"></i>
                                        Regenerar Todos los Códigos QR
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-lightbulb me-2"></i>Consejos</h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <p><strong>Regeneración individual:</strong><br>
                                    También puedes regenerar códigos QR individuales desde la página de productos usando
                                    el botón QR.</p>

                                <p><strong>Uso de QR:</strong><br>
                                    Los códigos QR permiten a los clientes escanear y ver el producto directamente en su
                                    teléfono.</p>

                                <p><strong>Formatos disponibles:</strong><br>
                                    • Imagen PNG (para imprimir)<br>
                                    • PDF con información completa</p>

                                <p><strong>Compatibilidad:</strong><br>
                                    Los QR funcionan con cualquier app de cámara moderna.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/admin-sweetalert.js"></script>

<script>
    function confirmarRegeneracion() {
        return confirm('¿Estás seguro de que quieres regenerar TODOS los códigos QR?\n\nEsta acción reemplazará todos los códigos QR existentes con nuevos códigos que usan la URL actual.');
    }

    // Mostrar loading durante la regeneración
    document.querySelector('form').addEventListener('submit', function (e) {
        if (confirmarRegeneracion()) {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Regenerando...';
            button.disabled = true;
        } else {
            e.preventDefault();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>