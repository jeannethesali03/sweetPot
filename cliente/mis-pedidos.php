<?php
// Iniciar sesión antes que cualquier output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../includes/Auth.php';

// Verificar que el usuario esté logueado y sea cliente
if (!Auth::isLoggedIn() || Auth::getUser()['rol'] !== 'cliente') {
    header('Location: ../acceso_denegado.php');
    exit;
}

$user = Auth::getUser();

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener pedidos del cliente
    $stmt = $conn->prepare("
        SELECT v.*, t.numero_ticket,
               COUNT(dv.id) as total_productos,
               SUM(dv.cantidad) as total_items
        FROM ventas v
        LEFT JOIN tickets t ON v.id = t.venta_id
        LEFT JOIN detalle_venta dv ON v.id = dv.venta_id
        WHERE v.cliente_id = :cliente_id
        GROUP BY v.id
        ORDER BY v.fecha DESC
    ");
    $stmt->execute([':cliente_id' => $user['id']]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

// Función para obtener clase CSS del estado
function getEstadoClass($estado)
{
    switch ($estado) {
        case 'pendiente':
            return 'warning';
        case 'confirmado':
            return 'info';
        case 'en_preparacion':
            return 'primary';
        case 'listo':
            return 'success';
        case 'entregado':
            return 'success';
        case 'cancelado':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Función para obtener icono del estado
function getEstadoIcon($estado)
{
    switch ($estado) {
        case 'pendiente':
            return 'clock';
        case 'confirmado':
            return 'check-circle';
        case 'en_preparacion':
            return 'gear';
        case 'listo':
            return 'check2-circle';
        case 'entregado':
            return 'truck';
        case 'cancelado':
            return 'x-circle';
        default:
            return 'question-circle';
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - SweetPot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../assets/css/cliente.css">
    <style>
        .pedido-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .pedido-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .pedido-header {
            background: linear-gradient(135deg, var(--sweetpot-pink) 0%, var(--sweetpot-brown) 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }

        .pedido-numero {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .pedido-fecha {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .estado-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            border: none;
        }

        .pedido-body {
            padding: 1.5rem;
            background-color: var(--sweetpot-white);
        }

        .pedido-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--sweetpot-pink);
        }

        .pedido-items {
            color: var(--sweetpot-gray);
            font-size: 0.9rem;
        }

        .btn-accion {
            border-radius: 20px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid var(--sweetpot-pink);
            color: var(--sweetpot-pink);
            background: transparent;
        }

        .btn-accion:hover {
            transform: translateY(-2px);
            background: var(--sweetpot-pink);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 157, 0.3);
        }

        .btn-accion-primary {
            background: linear-gradient(135deg, var(--sweetpot-pink), var(--sweetpot-brown));
            color: white;
            border: none;
        }

        .btn-accion-primary:hover {
            background: linear-gradient(135deg, var(--sweetpot-brown), var(--sweetpot-pink));
            color: white;
        }

        .qr-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--sweetpot-light-pink);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--sweetpot-gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--sweetpot-light-pink);
            opacity: 0.7;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--sweetpot-pink) 0%, var(--sweetpot-brown) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }

        .stats-card {
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            border: 3px solid rgba(255, 255, 255, 1);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: white;
        }

        .stats-label {
            opacity: 0.9;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Estados de pedidos adaptados a SweetPot */
        .estado-pendiente {
            background-color: var(--sweetpot-warning);
            color: var(--sweetpot-brown);
        }

        .estado-en-proceso {
            background-color: var(--sweetpot-info);
            color: white;
        }

        .estado-enviado {
            background-color: var(--sweetpot-pink);
            color: white;
        }

        .estado-entregado {
            background-color: var(--sweetpot-success);
            color: white;
        }

        .estado-cancelado {
            background-color: var(--sweetpot-danger);
            color: white;
        }

        /* Items del pedido */
        .pedido-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background-color: rgba(255, 234, 167, 0.1);
            border-radius: 10px;
            border-left: 4px solid var(--sweetpot-pink);
        }

        .pedido-item:last-child {
            margin-bottom: 0;
        }

        .item-nombre {
            font-weight: 600;
            color: var(--sweetpot-brown);
        }

        .item-cantidad {
            color: var(--sweetpot-gray);
            font-size: 0.9rem;
        }

        .item-precio {
            font-weight: bold;
            color: var(--sweetpot-pink);
        }

        /* Información del cliente */
        .cliente-info {
            background: linear-gradient(135deg, var(--sweetpot-cream) 0%, var(--sweetpot-light-pink) 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .cliente-nombre {
            font-weight: bold;
            color: var(--sweetpot-brown);
            margin-bottom: 0.5rem;
        }

        .cliente-contacto {
            color: var(--sweetpot-gray);
            font-size: 0.9rem;
        }

        /* Filtros */
        .filtros-pedidos {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .filtro-label {
            font-weight: 600;
            color: var(--sweetpot-brown);
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .pedido-header {
                padding: 1rem;
            }

            .pedido-body {
                padding: 1rem;
            }

            .pedido-total {
                font-size: 1.25rem;
            }

            .btn-accion {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
            }

            .hero-section {
                padding: 2rem 0;
            }

            .stats-number {
                font-size: 2rem;
            }
        }

        /* Animaciones */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pedido-card {
            animation: slideInUp 0.5s ease forwards;
        }

        .pedido-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .pedido-card:nth-child(odd) {
            animation-delay: 0.2s;
        }
    </style>
</head>

<body class="bg-light">
    <?php include '../includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="bi bi-list-ul"></i> Mis Pedidos</h1>
                    <p class="lead mb-0">Aquí puedes ver el historial y estado de todos tus pedidos</p>
                </div>
                <div class="col-md-4">
                    <?php if (!empty($pedidos)): ?>
                        <div class="stats-card">
                            <div class="stats-number"><?php echo count($pedidos); ?></div>
                            <div class="stats-label">Pedidos realizados</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <h5><i class="bi bi-exclamation-triangle"></i> Error</h5>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php elseif (empty($pedidos)): ?>
            <div class="empty-state">
                <i class="bi bi-cart-x"></i>
                <h3>No tienes pedidos aún</h3>
                <p>Cuando realices tu primer pedido, aparecerá aquí con toda la información y su código QR.</p>
                <a href="productos.php" class="btn btn-primary btn-lg mt-3">
                    <i class="bi bi-shop"></i> Explorar Productos
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card pedido-card">
                            <div class="pedido-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="pedido-numero">#<?php echo htmlspecialchars($pedido['numero_pedido']); ?>
                                        </div>
                                        <div class="pedido-fecha">
                                            <i class="bi bi-calendar"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($pedido['numero_ticket'])): ?>
                                        <?php
                                        $ticket_url = BASE_URL . "/cliente/ticket.php?numero=" . urlencode($pedido['numero_ticket']);
                                        $qr_url = BASE_URL . "/generate-qr.php?data=" . urlencode($ticket_url) . "&size=60";
                                        ?>
                                        <img src="<?php echo $qr_url; ?>" alt="QR Ticket" class="qr-thumbnail"
                                            title="QR del ticket <?php echo htmlspecialchars($pedido['numero_ticket']); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="pedido-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-<?php echo getEstadoClass($pedido['estado']); ?> estado-badge">
                                        <i class="bi bi-<?php echo getEstadoIcon($pedido['estado']); ?>"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
                                    </span>
                                    <div class="pedido-total">$<?php echo number_format($pedido['total'], 2); ?></div>
                                </div>

                                <div class="pedido-items mb-3">
                                    <i class="bi bi-bag"></i>
                                    <?php echo $pedido['total_items']; ?> productos (<?php echo $pedido['total_productos']; ?>
                                    diferentes)
                                </div>

                                <?php if (!empty($pedido['direccion_entrega'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt"></i>
                                            <?php echo htmlspecialchars($pedido['direccion_entrega']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <?php if (!empty($pedido['numero_ticket'])): ?>
                                        <a href="ticket-simple.php?numero=<?php echo htmlspecialchars($pedido['numero_ticket']); ?>"
                                            class="btn btn-accion" style="font-size: 1.5rem; color: pink; border: 3px solid pink;">
                                            <i class="bi bi-receipt"></i> Ver Ticket
                                        </a>
                                    <?php endif; ?>

                                    <button class="btn btn-outline-secondary btn-sm"
                                        onclick="verDetalles(<?php echo $pedido['id']; ?>)">
                                        <i class="bi bi-eye"></i> Ver Detalles
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Botones de acción -->
            <div class="text-center mt-4">
                <a href="productos.php" class="btn btn-success btn-lg me-3">
                    <i class="bi bi-plus-circle"></i> Realizar Nuevo Pedido
                </a>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="bi bi-house"></i> Volver al Inicio
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para detalles del pedido -->
    <div class="modal fade" id="detallesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detallesContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function verDetalles(ventaId) {
            const modal = new bootstrap.Modal(document.getElementById('detallesModal'));
            const content = document.getElementById('detallesContent');

            // Mostrar loading
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles del pedido...</p>
                </div>
            `;

            modal.show();

            // Cargar detalles
            fetch(`ajax/obtener-detalle-pedido.php?id=${ventaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarDetalles(data.pedido, data.productos);
                    } else {
                        content.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                Error al cargar los detalles: ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            Error de conexión: ${error.message}
                        </div>
                    `;
                });
        }

        function mostrarDetalles(pedido, productos) {
            const content = document.getElementById('detallesContent');

            let productosHtml = '';
            productos.forEach(producto => {
                productosHtml += `
                    <div class="row align-items-center mb-3 border-bottom pb-3">
                        <div class="col-2">
                            ${producto.imagen ?
                        `<img src="<?php echo BASE_URL; ?>assets/images/productos/${producto.imagen}" class="img-fluid rounded" style="max-height: 60px;">` :
                        `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 60px;"><i class="bi bi-image text-muted"></i></div>`
                    }
                        </div>
                        <div class="col-6">
                            <h6 class="mb-1">${producto.nombre}</h6>
                            <small class="text-muted">$${parseFloat(producto.precio_unitario).toFixed(2)} c/u</small>
                        </div>
                        <div class="col-2 text-center">
                            <span class="badge bg-primary">x${producto.cantidad}</span>
                        </div>
                        <div class="col-2 text-end">
                            <strong>$${parseFloat(producto.subtotal).toFixed(2)}</strong>
                        </div>
                    </div>
                `;
            });

            content.innerHTML = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Información del Pedido</h6>
                        <p><strong>Número:</strong> #${pedido.numero_pedido}</p>
                        <p><strong>Fecha:</strong> ${new Date(pedido.fecha).toLocaleString('es-ES')}</p>
                        <p><strong>Estado:</strong> 
                            <span class="badge bg-<?php echo "'+getEstadoClass('"; ?>${pedido.estado}<?php echo "')+'"; ?>">
                                ${pedido.estado.replace('_', ' ').toUpperCase()}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Entrega</h6>
                        <p><strong>Dirección:</strong><br>${pedido.direccion_entrega || 'No especificada'}</p>
                        ${pedido.comentarios ? `<p><strong>Comentarios:</strong><br>${pedido.comentarios}</p>` : ''}
                    </div>
                </div>
                
                <h6>Productos Pedidos</h6>
                <div class="mb-4">
                    ${productosHtml}
                </div>
                
                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-end">$${parseFloat(pedido.subtotal).toFixed(2)}</td>
                            </tr>
                            ${pedido.descuento > 0 ? `
                            <tr>
                                <td>Descuento:</td>
                                <td class="text-end text-success">-$${parseFloat(pedido.descuento).toFixed(2)}</td>
                            </tr>
                            ` : ''}
                            <tr>
                                <td>Impuestos:</td>
                                <td class="text-end">$${parseFloat(pedido.impuestos).toFixed(2)}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Total:</td>
                                <td class="text-end">$${parseFloat(pedido.total).toFixed(2)}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            `;
        }
    </script>
</body>

</html>