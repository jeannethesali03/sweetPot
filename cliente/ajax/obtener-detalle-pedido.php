<?php
require_once '../../config/config.php';
require_once '../../includes/Auth.php';
require_once '../../models/Pedido.php';

// Verificar autenticación
if (!Auth::isLoggedIn()) {
    exit('<div class="alert alert-danger">Acceso denegado</div>');
}

$user = Auth::getUser();
$id_pedido = intval($_GET['id'] ?? 0);

if (!$id_pedido) {
    exit('<div class="alert alert-danger">ID de pedido inválido</div>');
}

$pedidoModel = new Pedido();
$pedido_data = $pedidoModel->obtenerPorId($id_pedido);

// Verificar que el pedido pertenece al usuario (si es cliente)
if (!$pedido_data || ($user['rol'] === 'cliente' && $pedido_data['cliente_id'] != $user['id'])) {
    exit('<div class="alert alert-danger">Pedido no encontrado</div>');
}

// Obtener detalle del pedido (ya incluido por obtenerPorId)
$items = $pedido_data['productos'] ?? [];
?>

<div class="container-fluid">
    <!-- Cabecera del pedido -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-1">Pedido #<?php echo str_pad($pedido_data['id'], 6, '0', STR_PAD_LEFT); ?>
                        </h4>
                        <p class="mb-0">
                            <i class="fas fa-calendar me-1"></i>
                            Realizado el
                            <?php echo date('d/m/Y \a \las H:i', strtotime($pedido_data['fecha'])); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-light text-dark fs-6 px-3 py-2">
                            <?php echo ucfirst(str_replace('_', ' ', $pedido_data['estado'])); ?>
                        </span>
                        <div class="mt-2">
                            <strong
                                class="fs-4">$<?php echo number_format($pedido_data['total'], 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información del pedido -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información del Cliente</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Nombre:</strong>
                        <?php echo htmlspecialchars($pedido_data['cliente_nombre'] ?? $pedido_data['nombre'] ?? ''); ?>
                    </p>
                    <p class="mb-2"><strong>Email:</strong>
                        <?php echo htmlspecialchars($pedido_data['cliente_email'] ?? $pedido_data['email'] ?? ''); ?>
                    </p>
                    <p class="mb-3"><strong>Teléfono:</strong>
                        <?php echo htmlspecialchars($pedido_data['cliente_telefono'] ?? $pedido_data['telefono_contacto'] ?? ''); ?>
                    </p>

                    <h6><i class="fas fa-map-marker-alt me-2"></i>Dirección de Envío</h6>
                    <p class="mb-0">
                        <?php echo nl2br(htmlspecialchars($pedido_data['direccion_entrega'] ?? $pedido_data['cliente_direccion'] ?? '')); ?>
                    </p>

                    <?php if (!empty($pedido_data['comentarios']) || !empty($pedido_data['notas'])): ?>
                        <hr>
                        <h6><i class="fas fa-comment me-2"></i>Notas del Pedido</h6>
                        <p class="mb-0 text-muted">
                            <?php echo nl2br(htmlspecialchars($pedido_data['comentarios'] ?? $pedido_data['notas'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Estado del pedido -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-truck me-2"></i>Estado del Pedido</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php
                        $estados = [
                            'pendiente' => ['icon' => 'clock', 'text' => 'Pedido Recibido', 'desc' => 'Tu pedido ha sido registrado'],
                            'confirmado' => ['icon' => 'check-circle', 'text' => 'Confirmado', 'desc' => 'Preparando tu pedido'],
                            'enviado' => ['icon' => 'truck', 'text' => 'Enviado', 'desc' => 'En camino a tu dirección'],
                            'entregado' => ['icon' => 'check-double', 'text' => 'Entregado', 'desc' => 'Pedido completado'],
                            'cancelado' => ['icon' => 'times-circle', 'text' => 'Cancelado', 'desc' => 'Pedido cancelado']
                        ];

                        $estado_actual = $pedido_data['estado'];
                        $estados_orden = ['pendiente', 'confirmado', 'enviado', 'entregado'];
                        $indice_actual = array_search($estado_actual, $estados_orden);
                        ?>

                        <?php if ($estado_actual !== 'cancelado'): ?>
                            <?php foreach ($estados_orden as $index => $estado): ?>
                                <div class="timeline-item <?php echo $index <= $indice_actual ? 'active' : ''; ?>">
                                    <div class="timeline-marker">
                                        <i class="fas fa-<?php echo $estados[$estado]['icon']; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?php echo $estados[$estado]['text']; ?></h6>
                                        <small class="text-muted"><?php echo $estados[$estado]['desc']; ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="timeline-item active">
                                <div class="timeline-marker text-danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1 text-danger">Pedido Cancelado</h6>
                                    <small class="text-muted">Este pedido ha sido cancelado</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items del pedido -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-images me-2"></i>Obras Compradas</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($items)): ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No se encontraron items en este pedido</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex align-items-center p-3 border-bottom">
                                <div class="imagen-wrapper">
                                    <?php $img = $item['producto_imagen'] ?? $item['imagen'] ?? ''; ?>
                                    <?php if ($img): ?>
                                        <?php if (filter_var($img, FILTER_VALIDATE_URL)): ?>
                                            <img src="<?php echo htmlspecialchars($img); ?>"
                                                alt="<?php echo htmlspecialchars($item['producto_nombre'] ?? $item['titulo'] ?? ''); ?>"
                                                class="rounded" style="width: 80px; height: 80px; object-fit: cover;"
                                                onerror="this.parentElement.innerHTML='<div class=\'bg-light rounded d-flex align-items-center justify-content-center\' style=\'width: 80px; height: 80px;\'><i class=\'fas fa-image fa-2x text-muted\'></i></div>'">
                                        <?php else: ?>
                                            <img src="../../assets/uploads/obras/<?php echo htmlspecialchars($img); ?>"
                                                alt="<?php echo htmlspecialchars($item['producto_nombre'] ?? $item['titulo'] ?? ''); ?>"
                                                class="rounded" style="width: 80px; height: 80px; object-fit: cover;"
                                                onerror="this.parentElement.innerHTML='<div class=\'bg-light rounded d-flex align-items-center justify-content-center\' style=\'width: 80px; height: 80px;\'><i class=\'fas fa-image fa-2x text-muted\'></i></div>'">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                            style="width: 80px; height: 80px;">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($item['producto_nombre'] ?? $item['titulo'] ?? 'Producto'); ?>
                                    </h6>
                                    <p class="text-muted mb-1">
                                        <small
                                            class="text-muted"><?php echo htmlspecialchars($item['categoria_nombre'] ?? ''); ?></small>
                                    </p>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <small class="text-muted">Cantidad: <?php echo $item['cantidad']; ?></small>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted">Precio unitario:
                                                $<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?></small>
                                        </div>
                                        <div class="col-sm-4">
                                            <strong>Subtotal:
                                                $<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Total -->
                        <div class="bg-primary text-white p-3 text-center">
                            <h5 class="mb-0">
                                <i class="fas fa-dollar-sign me-2"></i>
                                Total del Pedido: $<?php echo number_format($pedido_data['total'], 0, ',', '.'); ?>
                            </h5>
                            <small>Envío gratuito incluido</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 30px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: -40px;
        top: 0;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #dee2e6;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .timeline-item.active .timeline-marker {
        background: #28a745;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -26px;
        top: 30px;
        width: 2px;
        height: calc(100% - 20px);
        background: #dee2e6;
    }

    .timeline-item:last-child::before {
        display: none;
    }

    .timeline-item.active::before {
        background: #28a745;
    }
</style>