<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../models/Producto.php';
require_once '../models/Categoria.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$productoModel = new Producto();
$categoriaModel = new Categoria();

// Variables
$producto = null;
$isEdit = false;
$errors = [];

// Verificar si es edición
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $productoId = (int)$_GET['id'];
    $producto = $productoModel->obtenerPorId($productoId);
    
    if (!$producto) {
        header('Location: productos.php?error=Producto no encontrado');
        exit();
    }
    
    $isEdit = true;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $activo = (int)($_POST['activo'] ?? 1);
    
    // Validaciones
    if (empty($nombre)) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if ($precio <= 0) {
        $errors[] = "El precio debe ser mayor a 0";
    }
    
    if ($stock < 0) {
        $errors[] = "El stock no puede ser negativo";
    }
    
    if ($categoria_id <= 0) {
        $errors[] = "Debe seleccionar una categoría";
    }
    
    // Manejo de imagen URL
    $imagen_url = trim($_POST['imagen_url'] ?? '');
    
    // Validar URL de imagen si se proporciona
    if (!empty($imagen_url)) {
        if (!filter_var($imagen_url, FILTER_VALIDATE_URL)) {
            $errors[] = "La URL de la imagen no es válida";
        }
    }
    
    // Si no hay errores, procesar
    if (empty($errors)) {
        $datos = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'stock' => $stock,
            'categoria_id' => $categoria_id,
            'estado' => ($activo == 1) ? 'activo' : 'inactivo',
            'imagen' => $imagen_url
        ];
        
        try {
            if ($isEdit) {
                $result = $productoModel->actualizar($productoId, $datos);
                if ($result) {
                    header('Location: productos.php?success=Producto actualizado correctamente');
                    exit();
                } else {
                    $errors[] = "Error al actualizar el producto";
                }
            } else {
                $result = $productoModel->crear($datos);
                if ($result) {
                    header('Location: productos.php?success=Producto creado correctamente');
                    exit();
                } else {
                    $errors[] = "Error al crear el producto";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Obtener categorías activas
$categorias = $categoriaModel->obtenerActivas();

$pageTitle = ($isEdit ? "Editar Producto" : "Nuevo Producto") . " - Admin SweetPot";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 text-gradient-sweetpot">
                    <i class="fas <?php echo $isEdit ? 'fa-edit' : 'fa-plus'; ?> me-2"></i>
                    <?php echo $isEdit ? 'Editar Producto' : 'Nuevo Producto'; ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="productos.php" class="btn btn-sweetpot-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver a Productos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>



            <!-- Formulario -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-box me-2"></i>
                                Información del Producto
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" novalidate>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">
                                                <i class="fas fa-tag me-1"></i>
                                                Nombre del Producto <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                                   value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="categoria_id" class="form-label">
                                                <i class="fas fa-folder me-1"></i>
                                                Categoría <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                                <option value="">Seleccionar categoría...</option>
                                                <?php foreach ($categorias as $categoria): ?>
                                                    <option value="<?php echo $categoria['id']; ?>" 
                                                        <?php echo ($producto['categoria_id'] ?? '') == $categoria['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>
                                        Descripción
                                    </label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="precio" class="form-label">
                                                <i class="fas fa-dollar-sign me-1"></i>
                                                Precio <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control" id="precio" name="precio" 
                                                   value="<?php echo $producto['precio'] ?? ''; ?>" 
                                                   step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">
                                                <i class="fas fa-boxes me-1"></i>
                                                Stock
                                            </label>
                                            <input type="number" class="form-control" id="stock" name="stock" 
                                                   value="<?php echo $producto['stock'] ?? '0'; ?>" 
                                                   min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="activo" class="form-label">
                                                <i class="fas fa-toggle-on me-1"></i>
                                                Estado
                                            </label>
                                            <select class="form-select" id="activo" name="activo">
                                                <option value="1" <?php echo (!isset($producto['estado']) || $producto['estado'] === 'activo') ? 'selected' : ''; ?>>
                                                    Activo
                                                </option>
                                                <option value="0" <?php echo (isset($producto['estado']) && $producto['estado'] === 'inactivo') ? 'selected' : ''; ?>>
                                                    Inactivo
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="imagen_url" class="form-label">
                                        <i class="fas fa-image me-1"></i>
                                        URL de la Imagen
                                    </label>
                                    <input type="url" class="form-control" id="imagen_url" name="imagen_url" 
                                           value="<?php echo htmlspecialchars($producto['imagen'] ?? ''); ?>"
                                           placeholder="https://ejemplo.com/imagen.jpg">
                                    <div class="form-text">
                                        Ingresa la URL completa de la imagen del producto.
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="mostrarEjemplosUrl()">
                                            Ver ejemplos
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="productos.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-sweetpot-primary">
                                        <i class="fas fa-save"></i> 
                                        <?php echo $isEdit ? 'Actualizar Producto' : 'Crear Producto'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Vista previa / Información adicional -->
                <div class="col-md-4">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-eye me-2"></i>
                                <?php echo $isEdit ? 'Vista Previa' : 'Vista Previa'; ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Vista previa de imagen -->
                            <div id="image-preview" class="mb-3">
                                <small class="text-muted">Vista Previa de la Imagen</small>
                                <div class="mt-1">
                                    <img id="preview-img" class="img-fluid rounded" 
                                         src="<?php echo htmlspecialchars($producto['imagen'] ?? ''); ?>"
                                         alt="Vista previa"
                                         style="max-height: 200px; width: 100%; object-fit: cover; <?php echo empty($producto['imagen']) ? 'display: none;' : ''; ?>">
                                    <div id="no-image" class="bg-light d-flex align-items-center justify-content-center rounded" 
                                         style="height: 200px; <?php echo !empty($producto['imagen']) ? 'display: none;' : ''; ?>">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-image fa-3x mb-2"></i>
                                            <p>Sin imagen</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($isEdit): ?>
                                <div class="mb-3">
                                    <small class="text-muted">ID del Producto</small>
                                    <div class="fw-bold"><?php echo $producto['id']; ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Fecha de Creación</small>
                                    <div class="fw-bold">
                                        <?php echo date('d/m/Y H:i', strtotime($producto['fecha_creacion'])); ?>
                                    </div>
                                </div>

                                <?php if (!empty($producto['fecha_actualizacion'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Última Actualización</small>
                                        <div class="fw-bold">
                                            <?php echo date('d/m/Y H:i', strtotime($producto['fecha_actualizacion'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <small class="text-muted">Estado Actual</small>
                                    <div>
                                        <?php if (isset($producto['estado']) && $producto['estado'] === 'activo'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times me-1"></i>Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="alert alert-info p-2">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Usa URLs de imágenes públicas. Recomendado: servicios como Cloudinary, ImgBB o enlaces directos.
                                </small>
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
// Vista previa de imagen URL
document.getElementById('imagen_url').addEventListener('input', function(e) {
    const url = e.target.value.trim();
    const previewImg = document.getElementById('preview-img');
    const noImage = document.getElementById('no-image');
    
    if (url && isValidUrl(url)) {
        previewImg.src = url;
        previewImg.style.display = 'block';
        noImage.style.display = 'none';
        
        // Validar si la imagen carga correctamente
        previewImg.onerror = function() {
            previewImg.style.display = 'none';
            noImage.style.display = 'flex';
        };
    } else {
        previewImg.style.display = 'none';
        noImage.style.display = 'flex';
    }
});

// Función para validar URL
function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

// Mostrar ejemplos de URLs
function mostrarEjemplosUrl() {
    Swal.fire({
        title: 'Ejemplos de URLs de Imágenes',
        html: `
            <div class="text-start">
                <h6>URLs de ejemplo para productos de repostería:</h6>
                <div class="mb-2">
                    <small class="text-muted">Pastel de chocolate:</small><br>
                    <code>https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400</code>
                    <button class="btn btn-sm btn-outline-primary ms-1" onclick="usarUrl('https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400')">Usar</button>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Cupcakes:</small><br>
                    <code>https://images.unsplash.com/photo-1614707267537-b85aaf00c4b7?w=400</code>
                    <button class="btn btn-sm btn-outline-primary ms-1" onclick="usarUrl('https://images.unsplash.com/photo-1614707267537-b85aaf00c4b7?w=400')">Usar</button>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Galletas:</small><br>
                    <code>https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=400</code>
                    <button class="btn btn-sm btn-outline-primary ms-1" onclick="usarUrl('https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=400')">Usar</button>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Donas:</small><br>
                    <code>https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400</code>
                    <button class="btn btn-sm btn-outline-primary ms-1" onclick="usarUrl('https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400')">Usar</button>
                </div>
                <hr>
                <small class="text-info">
                    <i class="fas fa-info-circle"></i> 
                    Estos son ejemplos de Unsplash. Puedes usar cualquier URL pública de imagen.
                </small>
            </div>
        `,
        width: 600,
        showConfirmButton: false,
        showCloseButton: true
    });
}

// Usar URL de ejemplo
function usarUrl(url) {
    document.getElementById('imagen_url').value = url;
    document.getElementById('imagen_url').dispatchEvent(new Event('input'));
    Swal.close();
}

// Validación en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const precio = document.getElementById('precio');
    const stock = document.getElementById('stock');
    const form = document.querySelector('form');

    precio.addEventListener('input', function() {
        if (this.value <= 0) {
            this.setCustomValidity('El precio debe ser mayor a 0');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });

    stock.addEventListener('input', function() {
        if (this.value < 0) {
            this.setCustomValidity('El stock no puede ser negativo');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>