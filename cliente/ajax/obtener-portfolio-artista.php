<?php
require_once '../../config/config.php';
require_once '../../includes/Auth.php';
require_once '../../models/Obra.php';
require_once '../../models/Artista.php';
require_once '../../models/Carrito.php';

// Verificar autenticación
if (!Auth::isLoggedIn()) {
    exit('<div class="alert alert-danger">Acceso denegado</div>');
}

$user = Auth::getUser();
$id_artista = intval($_GET['id'] ?? 0);

if (!$id_artista) {
    exit('<div class="alert alert-danger">ID de artista inválido</div>');
}

$obra = new Obra();
$artista = new Artista();
$carrito = new Carrito();

// Obtener información del artista
$art_stmt = $artista->consultaPersonalizada(
    "SELECT * FROM artistas WHERE id_artista = ?",
    [$id_artista]
);
$art_info = $art_stmt->fetch(PDO::FETCH_ASSOC);

if (!$art_info) {
    exit('<div class="alert alert-danger">Artista no encontrado</div>');
}

// Obtener obras del artista
$obras_stmt = $obra->consultaPersonalizada("
    SELECT DISTINCT o.id_obra, o.id_artista, o.id_categoria, o.titulo, o.descripcion, 
           o.imagen, o.precio, o.disponible, o.dimensiones, o.tecnica,
           o.año_creacion as ano_creacion, o.created_at, o.updated_at,
           c.nombre as categoria_nombre, 
           GROUP_CONCAT(DISTINCT e.nombre SEPARATOR ', ') as exposicion_nombre
    FROM obras o 
    LEFT JOIN categorias c ON o.id_categoria = c.id_categoria 
    LEFT JOIN obras_exposiciones oe ON o.id_obra = oe.id_obra
    LEFT JOIN exposiciones e ON oe.id_exposicion = e.id_exposicion 
    WHERE o.id_artista = ? AND o.disponible = 1
    GROUP BY o.id_obra
    ORDER BY o.año_creacion DESC, o.titulo ASC
", [$id_artista]);

$obras = $obras_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <!-- Info del artista -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <?php if ($art_info['foto']): ?>
                            <img src="../../assets/uploads/artistas/<?php echo htmlspecialchars($art_info['foto']); ?>"
                                alt="<?php echo htmlspecialchars($art_info['nombre']); ?>" class="rounded-circle"
                                style="width: 80px; height: 80px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <h4 class="mb-1"><?php echo htmlspecialchars($art_info['nombre']); ?></h4>
                        <div class="d-flex gap-3 flex-wrap mb-2">
                            <?php if ($art_info['nacionalidad']): ?>
                                <span><i
                                        class="fas fa-globe me-1"></i><?php echo htmlspecialchars($art_info['nacionalidad']); ?></span>
                            <?php endif; ?>
                            <?php if ($art_info['fecha_nacimiento']): ?>
                                <span><i
                                        class="fas fa-birthday-cake me-1"></i><?php echo date('Y', strtotime($art_info['fecha_nacimiento'])); ?></span>
                            <?php endif; ?>
                            <?php if ($art_info['especialidad']): ?>
                                <span><i
                                        class="fas fa-palette me-1"></i><?php echo htmlspecialchars($art_info['especialidad']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($art_info['biografia']): ?>
                            <p class="mb-0 opacity-75"><?php echo htmlspecialchars($art_info['biografia']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Obras del artista -->
    <?php if (empty($obras)): ?>
        <div class="text-center py-4">
            <i class="fas fa-images fa-3x text-muted mb-3"></i>
            <h5>Este artista no tiene obras disponibles</h5>
        </div>
    <?php else: ?>
        <div class="mb-3">
            <h5><i class="fas fa-palette me-2"></i>Portfolio (<?php echo count($obras); ?>
                obra<?php echo count($obras) > 1 ? 's' : ''; ?>)</h5>
        </div>

        <div class="row">
            <?php foreach ($obras as $obra_item): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="imagen-wrapper">
                            <?php if ($obra_item['imagen']): ?>
                                <?php if (filter_var($obra_item['imagen'], FILTER_VALIDATE_URL)): ?>
                                    <img src="<?php echo htmlspecialchars($obra_item['imagen']); ?>" class="card-img-top"
                                        alt="<?php echo htmlspecialchars($obra_item['titulo']); ?>"
                                        style="height: 250px; object-fit: cover;"
                                        onerror="this.parentElement.innerHTML='<div class=\'card-img-top bg-light d-flex align-items-center justify-content-center\' style=\'height: 250px;\'><i class=\'fas fa-image fa-3x text-muted\'></i></div>'">
                                <?php else: ?>
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
                        </div>

                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title"><?php echo htmlspecialchars($obra_item['titulo']); ?></h6>

                            <div class="mb-2">
                                <?php if ($obra_item['categoria_nombre']): ?>
                                    <span class="badge bg-secondary me-1">
                                        <?php echo htmlspecialchars($obra_item['categoria_nombre']); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($obra_item['exposicion_nombre']): ?>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($obra_item['exposicion_nombre']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

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