<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../models/Usuario.php';
require_once '../models/Producto.php';
require_once '../models/Carrito.php';

// Verificar autenticación y rol de cliente
Auth::requireRole('cliente');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$carritoModel = new Carrito();

try {
    // Obtener resumen del carrito
    $resumen = $carritoModel->obtenerResumen($_SESSION['user_id']);
    $items = $carritoModel->obtenerItems($_SESSION['user_id']);

    // Verificar que hay productos en el carrito
    if (empty($items)) {
        setAlert('warning', 'Carrito vacío', 'No tienes productos en tu carrito');
        header('Location: carrito.php');
        exit;
    }

    // Verificar stock disponible
    $verificacion = $carritoModel->verificarStock($_SESSION['user_id']);
    if (!$verificacion['tiene_stock']) {
        $error_stock = "Algunos productos no tienen stock suficiente";
    }

} catch (Exception $e) {
    $error = "Error al cargar información: " . $e->getMessage();
    $resumen = ['total_items' => 0, 'subtotal' => 0, 'total' => 0];
    $items = [];
}

$pageTitle = "Procesar Pago - SweetPot";
include '../includes/header.php';
include 'includes/navbar.php';
?>

<div class="container my-4">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_stock)): ?>
        <div class="alert alert-warning" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error_stock; ?>
            <div class="mt-2">
                <?php foreach ($verificacion['items_sin_stock'] as $item): ?>
                    <small class="d-block">• <?php echo $item['nombre']; ?>: Solicitaste
                        <?php echo $item['cantidad_solicitada']; ?>, disponible:
                        <?php echo $item['stock_disponible']; ?></small>
                <?php endforeach; ?>
            </div>
            <a href="carrito.php" class="btn btn-warning btn-sm mt-2">Revisar Carrito</a>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="text-gradient-sweetpot mb-2">
                <i class="fas fa-credit-card me-2"></i>
                Procesar Pago
            </h1>
            <p class="text-muted">Confirma tu pedido y procede con el pago</p>
        </div>
    </div>

    <div class="row">
        <!-- Formulario de pago -->
        <div class="col-lg-8">
            <form id="pagoForm">
                <!-- Información de contacto -->
                <div class="card card-sweetpot mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            Información de Contacto
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre completo *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                    value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono"
                                    value="<?php echo htmlspecialchars($_SESSION['telefono'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dirección de entrega -->
                <div class="card card-sweetpot mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Dirección de Entrega
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección completa *</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="3"
                                placeholder="Calle, número, colonia, código postal, ciudad"
                                required><?php echo htmlspecialchars($_SESSION['direccion'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="comentarios" class="form-label">Comentarios adicionales</label>
                            <textarea class="form-control" id="comentarios" name="comentarios" rows="2"
                                placeholder="Referencias, instrucciones especiales, etc."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Método de pago -->
                <div class="card card-sweetpot mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            Método de Pago
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="efectivo"
                                        value="efectivo" checked>
                                    <label class="form-check-label" for="efectivo">
                                        <i class="fas fa-money-bill-wave me-2 text-success"></i>
                                        Efectivo (Pago contra entrega)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="tarjeta"
                                        value="tarjeta">
                                    <label class="form-check-label" for="tarjeta">
                                        <i class="fas fa-credit-card me-2 text-primary"></i>
                                        Tarjeta de Crédito/Débito
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="transferencia"
                                        value="transferencia">
                                    <label class="form-check-label" for="transferencia">
                                        <i class="fas fa-university me-2 text-info"></i>
                                        Transferencia Bancaria
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Campos de tarjeta (se muestran cuando se selecciona tarjeta) -->
                        <div id="campos-tarjeta" style="display: none;" class="mt-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="numero-tarjeta" class="form-label">Número de tarjeta</label>
                                    <input type="text" class="form-control" id="numero-tarjeta"
                                        placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="expiracion" class="form-label">Expiración</label>
                                    <input type="text" class="form-control" id="expiracion" placeholder="MM/AA"
                                        maxlength="5">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" placeholder="123" maxlength="4">
                                </div>
                            </div>
                        </div>

                        <!-- Información de transferencia (se muestra cuando se selecciona transferencia) -->
                        <div id="info-transferencia" style="display: none;" class="mt-3">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Datos para Transferencia:</h6>
                                <p class="mb-1"><strong>Banco:</strong> Banco Ejemplo</p>
                                <p class="mb-1"><strong>Cuenta:</strong> 1234567890</p>
                                <p class="mb-1"><strong>CLABE:</strong> 012345678901234567</p>
                                <p class="mb-0"><strong>Beneficiario:</strong> SweetPot S.A. de C.V.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Resumen del pedido -->
        <div class="col-lg-4">
            <div class="card card-sweetpot sticky-top" style="top: 100px;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-shopping-bag me-2"></i>
                        Resumen del Pedido
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Productos -->
                    <div class="mb-3">
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <small class="fw-bold"><?php echo htmlspecialchars($item['nombre']); ?></small>
                                    <br>
                                    <small class="text-muted">Cantidad: <?php echo $item['cantidad']; ?> ×
                                        $<?php echo number_format($item['precio'], 2); ?></small>
                                </div>
                                <small class="fw-bold">$<?php echo number_format($item['subtotal'], 2); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <!-- Totales -->
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($resumen['subtotal'], 2); ?></span>
                    </div>
                    <?php if ($resumen['impuestos'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Impuestos:</span>
                            <span>$<?php echo number_format($resumen['impuestos'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Envío:</span>
                        <span class="text-success">Gratis</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong
                            class="text-sweetpot-pink fs-5">$<?php echo number_format($resumen['total'], 2); ?></strong>
                    </div>

                    <!-- Botón de pago -->
                    <div class="d-grid">
                        <?php if (!isset($error_stock)): ?>
                            <button type="button" class="btn btn-sweetpot-primary btn-lg" onclick="procesarPago()">
                                <i class="fas fa-lock me-2"></i>
                                Confirmar Pedido
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-lg" disabled>
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Revisar Stock
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Transacción 100% segura
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/cliente-sweetalert.js"></script>

<script>
    // Mostrar/ocultar campos según método de pago
    document.querySelectorAll('input[name="metodo_pago"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const camposTarjeta = document.getElementById('campos-tarjeta');
            const infoTransferencia = document.getElementById('info-transferencia');

            // Ocultar todos los campos
            camposTarjeta.style.display = 'none';
            infoTransferencia.style.display = 'none';

            // Mostrar campos según selección
            if (this.value === 'tarjeta') {
                camposTarjeta.style.display = 'block';
            } else if (this.value === 'transferencia') {
                infoTransferencia.style.display = 'block';
            }
        });
    });

    // Formatear número de tarjeta
    document.getElementById('numero-tarjeta')?.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
    });

    // Formatear fecha de expiración
    document.getElementById('expiracion')?.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });

    // Procesar pago
    function procesarPago() {
        const form = document.getElementById('pagoForm');
        const formData = new FormData(form);

        // Validar campos requeridos
        const nombre = document.getElementById('nombre').value.trim();
        const email = document.getElementById('email').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const direccion = document.getElementById('direccion').value.trim();

        if (!nombre || !email || !telefono || !direccion) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Por favor completa todos los campos obligatorios'
            });
            return;
        }

        // Validar método de pago específico
        const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;

        if (metodoPago === 'tarjeta') {
            const numeroTarjeta = document.getElementById('numero-tarjeta').value.trim();
            const expiracion = document.getElementById('expiracion').value.trim();
            const cvv = document.getElementById('cvv').value.trim();

            if (!numeroTarjeta || !expiracion || !cvv) {
                Swal.fire({
                    icon: 'error',
                    title: 'Datos de tarjeta incompletos',
                    text: 'Por favor completa todos los datos de la tarjeta'
                });
                return;
            }
        }

        // Mostrar confirmación
        Swal.fire({
            title: '¿Confirmar pedido?',
            text: '¿Estás seguro de que quieres procesar este pedido?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ff6b9d',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Procesando pago...',
                    text: 'Por favor espera mientras procesamos tu pedido',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Preparar datos para enviar
                const datosCompra = {
                    nombre: nombre,
                    email: email,
                    telefono: telefono,
                    direccion: direccion,
                    comentarios: document.getElementById('comentarios').value.trim(),
                    metodo_pago: metodoPago
                };

                // Si es tarjeta, agregar datos de tarjeta (simulados)
                if (metodoPago === 'tarjeta') {
                    datosCompra.numero_tarjeta = document.getElementById('numero-tarjeta').value.replace(/\s/g, '');
                    datosCompra.expiracion = document.getElementById('expiracion').value;
                    datosCompra.cvv = document.getElementById('cvv').value;
                }

                // Enviar al servidor
                console.log('Enviando datos:', datosCompra);

                fetch('ajax/procesar-pago.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(datosCompra)
                })
                    .then(response => {
                        console.log('Response recibida:', response);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Datos recibidos:', data);

                        // Cerrar el modal de loading
                        Swal.close();

                        if (data.success) {
                            console.log('Pago exitoso, mostrando confirmación...');

                            Swal.fire({
                                icon: 'success',
                                title: '¡Pedido realizado exitosamente!',
                                html: `
                            <div class="text-start">
                                <p><strong>Número de pedido:</strong> ${data.numero_pedido}</p>
                                <p><strong>Número de ticket:</strong> ${data.numero_ticket}</p>
                                <p><strong>Total:</strong> $${parseFloat(data.total).toFixed(2)}</p>
                                <hr>
                                <p class="text-muted small">Se ha generado tu ticket con código QR. Podrás seguir el estado de tu pedido desde tu área de cliente.</p>
                            </div>
                        `,
                                showCancelButton: true,
                                confirmButtonText: '<i class="bi bi-receipt"></i> Ver Ticket',
                                cancelButtonText: '<i class="bi bi-list-ul"></i> Mis Pedidos',
                                confirmButtonColor: '#28a745',
                                cancelButtonColor: '#007bff'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    console.log('Redirigiendo a ticket simple...');
                                    window.location.href = `ticket-simple.php?numero=${data.numero_ticket}`;
                                } else if (result.dismiss === Swal.DismissReason.cancel) {
                                    console.log('Redirigiendo a mis pedidos...');
                                    window.location.href = 'mis-pedidos.php';
                                }
                            });
                        } else {
                            console.log('Error en respuesta:', data);

                            Swal.fire({
                                icon: 'error',
                                title: 'Error al procesar',
                                text: data.message || 'Ocurrió un error al procesar tu pedido'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error completo:', error);

                        // Cerrar loading
                        Swal.close();

                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: `Error: ${error.message}`
                        });
                    })
                    .finally(() => {
                        // Restaurar botón si existe
                        const btnElement = document.getElementById('btn-procesar-pago');
                        if (btnElement) {
                            btnElement.disabled = false;
                            btnElement.innerHTML = '<i class="bi bi-credit-card"></i> Confirmar Pedido';
                        }
                    });
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>