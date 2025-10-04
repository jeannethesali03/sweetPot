<?php
require_once '../../config/config.php';
require_once '../../includes/Auth.php';
require_once '../../models/Obra.php';
require_once '../../models/Exposicion.php';
require_once '../../models/Artista.php';
require_once '../../models/Carrito.php';

// Verificar autenticación
if (!Auth::isLoggedIn()) {
    exit('<div class="alert alert-danger">Acceso denegado</div>');
}

$user = Auth::getUser();
$id_exposicion = intval($_GET['id'] ?? 0);

if (!$id_exposicion) {
    exit('<div class="alert alert-danger">ID de exposición inválido</div>');
}

$obra = new Obra();
$exposicion = new Exposicion();
$artista = new Artista();
$carrito = new Carrito();

// Obtener información de la exposición
$exp_stmt = $exposicion->consultaPersonalizada(
    "SELECT * FROM exposiciones WHERE id_exposicion = ?",
    [$id_exposicion]
);
$exp_info = $exp_stmt->fetch(PDO::FETCH_ASSOC);

if (!$exp_info) {
    exit('<div class="alert alert-danger">Exposición no encontrada</div>');
}

// Obtener obras de la exposición
$obras_stmt = $obra->consultaPersonalizada("
    SELECT o.id_obra, o.id_artista, o.id_categoria, o.titulo, o.descripcion, 
           o.imagen, o.precio, o.disponible, o.dimensiones, o.tecnica,
           o.año_creacion as ano_creacion, o.created_at, o.updated_at,
           a.nombre as artista_nombre, a.apellidos as artista_apellidos
    FROM obras o 
    INNER JOIN exposicion_obras eo ON o.id_obra = eo.id_obra
    LEFT JOIN artistas a ON o.id_artista = a.id_artista 
    WHERE eo.id_exposicion = ? AND o.disponible = 1
    ORDER BY o.titulo ASC
", [$id_exposicion]);

$obras = $obras_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <!-- Info de la exposición -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-primary text-white p-4 rounded">
                <h4><?php echo htmlspecialchars($exp_info['nombre']); ?></h4>
                <?php if ($exp_info['descripcion']): ?>
                    <p class="mb-2"><?php echo htmlspecialchars($exp_info['descripcion']); ?></p>
                <?php endif; ?>
                <div class="d-flex gap-3 flex-wrap">
                    <span><i
                            class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($exp_info['fecha_inicio'])); ?></span>
                    <span><i
                            class="fas fa-calendar-check me-1"></i><?php echo date('d/m/Y', strtotime($exp_info['fecha_fin'])); ?></span>
                    <?php if ($exp_info['lugar']): ?>
                        <span><i
                                class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($exp_info['lugar']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Obras de la exposición -->
    <?php if (empty($obras)): ?>
        <div class="text-center py-4">
            <i class="fas fa-images fa-3x text-muted mb-3"></i>
            <h5>No hay obras disponibles en esta exposición</h5>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($obras as $obra_item): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if ($obra_item['imagen']): ?>
                            <?php if (filter_var($obra_item['imagen'], FILTER_VALIDATE_URL)): ?>
                                <!-- Es una URL externa -->
                                <img src="<?php echo htmlspecialchars($obra_item['imagen']); ?>" class="card-img-top"
                                    alt="<?php echo htmlspecialchars($obra_item['titulo']); ?>"
                                    style="height: 250px; object-fit: cover;"
                                    onerror="this.parentElement.innerHTML='<div class=\'card-img-top bg-light d-flex align-items-center justify-content-center\' style=\'height: 250px;\'><i class=\'fas fa-image fa-3x text-muted\'></i></div>'">
                            <?php else: ?>
                                <!-- Es un archivo local -->
                                <img src="../../assets/uploads/obras/<?php echo htmlspecialchars($obra_item['imagen']); ?>"
                                    class="card-img-top" alt="<?php echo htmlspecialchars($obra_item['titulo']); ?>"
                                    style="height: 250px; object-fit: cover;"
                                    onerror="this.parentElement.innerHTML='<div class=\'card-img-top bg-light d-flex align-items-center justify-content-center\' style=\'height: 250px;\'><i class=\'fas fa-image fa-3x text-muted\'></i></div>'">
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                style="height: 250px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title"><?php echo htmlspecialchars($obra_item['titulo']); ?></h6>

                            <?php if ($obra_item['artista_nombre']): ?>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-user me-1"></i>
                                    <?php
                                    echo htmlspecialchars($obra_item['artista_nombre']);
                                    if ($obra_item['artista_apellidos']) {
                                        echo ' ' . htmlspecialchars($obra_item['artista_apellidos']);
                                    }
                                    ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($obra_item['descripcion']): ?>
                                <p class="card-text small text-muted mb-2">
                                    <?php
                                    $desc = htmlspecialchars($obra_item['descripcion']);
                                    echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                    ?>
                                </p>
                            <?php endif; ?>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong
                                        class="text-primary">$<?php echo number_format($obra_item['precio'], 0, ',', '.'); ?></strong>
                                    <?php if ($obra_item['ano_creacion']): ?>
                                        <small class="text-muted"><?php echo $obra_item['ano_creacion']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <?php if ($user['rol'] === 'cliente'): ?>
                                    <button class="btn btn-primary btn-sm w-100"
                                        onclick="agregarAlCarrito(<?php echo $obra_item['id_obra']; ?>)">
                                        <i class="fas fa-cart-plus me-1"></i>Agregar al Carrito
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($user['rol'] === 'cliente'): ?>
    <script>
        function agregarAlCarrito(idObra) {
            fetch('../ajax/agregar-carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_obra=' + idObra + '&cantidad=1'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        const toast = document.createElement('div');
                        toast.className = 'toast position-fixed top-0 end-0 m-3';
                        toast.setAttribute('role', 'alert');
                        toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <strong class="me-auto">¡Éxito!</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    Obra agregada al carrito correctamente
                </div>
            `;
                        document.body.appendChild(toast);
                        const bsToast = new bootstrap.Toast(toast);
                        bsToast.show();

                        // Actualizar contador del carrito si existe
                        const badge = document.querySelector('.carrito-badge');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent) || 0;
                            badge.textContent = currentCount + 1;
                            badge.style.display = 'flex';
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al agregar al carrito');
                });
        }
    </script>
<?php endif; ?>