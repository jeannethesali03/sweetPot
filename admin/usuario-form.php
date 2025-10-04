<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';
require_once '../models/Usuario.php';

// Verificar autenticación y rol de admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

// Instanciar modelos
$usuarioModel = new Usuario();

// Variables
$usuario = null;
$isEdit = false;
$errors = [];
$success = false;

// Verificar si es edición
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $userId = (int) $_GET['id'];
    $usuario = $usuarioModel->obtenerPorId($userId);

    if (!$usuario) {
        header('Location: usuarios.php?error=Usuario no encontrado');
        exit();
    }

    $isEdit = true;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validaciones
    if (empty($nombre)) {
        $errors[] = "El nombre es obligatorio";
    }

    if (empty($email)) {
        $errors[] = "El email es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no es válido";
    }

    if (!$isEdit && empty($password)) {
        $errors[] = "La contraseña es obligatoria";
    }

    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden";
    }

    if (!empty($password) && strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }

    if (empty($rol) || !in_array($rol, ['admin', 'vendedor', 'cliente'])) {
        $errors[] = "Debe seleccionar un rol válido";
    }

    // Verificar email único
    if ($isEdit) {
        $emailExists = $usuarioModel->existeEmail($email, $userId);
    } else {
        $emailExists = $usuarioModel->existeEmail($email);
    }

    if ($emailExists) {
        $errors[] = "El email ya está registrado";
    }

    // Si no hay errores, procesar
    if (empty($errors)) {
        $datos = [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'rol' => $rol,
            'estado' => $estado
        ];

        if (!empty($password)) {
            $datos['password'] = $password;
        }

        try {
            if ($isEdit) {
                $result = $usuarioModel->actualizar($userId, $datos);
                if ($result) {
                    $success = true;
                    $successMessage = "Usuario actualizado correctamente";
                    // Recargar datos del usuario
                    $usuario = $usuarioModel->obtenerPorId($userId);
                } else {
                    $errors[] = "Error al actualizar el usuario";
                }
            } else {
                $result = $usuarioModel->crear($datos);
                if ($result) {
                    header('Location: usuarios.php?success=Usuario creado correctamente');
                    exit();
                } else {
                    $errors[] = "Error al crear el usuario";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

$pageTitle = ($isEdit ? "Editar Usuario" : "Nuevo Usuario") . " - Admin SweetPot";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 text-gradient-sweetpot">
                    <i class="fas <?php echo $isEdit ? 'fa-edit' : 'fa-plus'; ?> me-2"></i>
                    <?php echo $isEdit ? 'Editar Usuario' : 'Nuevo Usuario'; ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="usuarios.php" class="btn btn-sweetpot-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver a Usuarios
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

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-sweetpot">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>
                                Información del Usuario
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" novalidate>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">
                                                <i class="fas fa-user me-1"></i>
                                                Nombre Completo <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="nombre" name="nombre"
                                                value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope me-1"></i>
                                                Email <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">
                                                <i class="fas fa-phone me-1"></i>
                                                Teléfono
                                            </label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                                value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="rol" class="form-label">
                                                <i class="fas fa-user-tag me-1"></i>
                                                Rol <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="rol" name="rol" required>
                                                <option value="">Seleccionar rol...</option>
                                                <option value="admin" <?php echo ($usuario['rol'] ?? '') === 'admin' ? 'selected' : ''; ?>>
                                                    <i class="fas fa-user-shield"></i> Administrador
                                                </option>
                                                <option value="vendedor" <?php echo ($usuario['rol'] ?? '') === 'vendedor' ? 'selected' : ''; ?>>
                                                    <i class="fas fa-user-tie"></i> Vendedor
                                                </option>
                                                <option value="cliente" <?php echo ($usuario['rol'] ?? '') === 'cliente' ? 'selected' : ''; ?>>
                                                    <i class="fas fa-user"></i> Cliente
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Dirección
                                    </label>
                                    <textarea class="form-control" id="direccion" name="direccion"
                                        rows="2"><?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?></textarea>
                                </div>

                                <?php if ($isEdit): ?>
                                    <div class="mb-3">
                                        <label for="estado" class="form-label">
                                            <i class="fas fa-toggle-on me-1"></i>
                                            Estado
                                        </label>
                                        <select class="form-select" id="estado" name="estado">
                                            <option value="activo" <?php echo ($usuario['estado'] ?? '') === 'activo' ? 'selected' : ''; ?>>
                                                <i class="fas fa-check"></i> Activo
                                            </option>
                                            <option value="inactivo" <?php echo ($usuario['estado'] ?? '') === 'inactivo' ? 'selected' : ''; ?>>
                                                <i class="fas fa-times"></i> Inactivo
                                            </option>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <hr class="my-4">

                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-lock me-2"></i>
                                    <?php echo $isEdit ? 'Cambiar Contraseña (opcional)' : 'Contraseña'; ?>
                                </h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">
                                                Nueva Contraseña
                                                <?php echo !$isEdit ? '<span class="text-danger">*</span>' : ''; ?>
                                            </label>
                                            <input type="password" class="form-control" id="password" name="password"
                                                <?php echo !$isEdit ? 'required' : ''; ?>>
                                            <div class="form-text">
                                                <?php echo $isEdit ? 'Déjalo vacío si no quieres cambiarla' : 'Mínimo 6 caracteres'; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">
                                                Confirmar Contraseña
                                                <?php echo !$isEdit ? '<span class="text-danger">*</span>' : ''; ?>
                                            </label>
                                            <input type="password" class="form-control" id="confirm_password"
                                                name="confirm_password" <?php echo !$isEdit ? 'required' : ''; ?>>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="usuarios.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-sweetpot-primary">
                                        <i class="fas fa-save"></i>
                                        <?php echo $isEdit ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Información adicional (solo en edición) -->
                <?php if ($isEdit): ?>
                    <div class="col-md-4">
                        <div class="card card-sweetpot">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Información Adicional
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">ID del Usuario</small>
                                    <div class="fw-bold"><?php echo $usuario['id']; ?></div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">Fecha de Registro</small>
                                    <div class="fw-bold">
                                        <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?>
                                    </div>
                                </div>

                                <?php if (!empty($usuario['ultima_conexion'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Última Conexión</small>
                                        <div class="fw-bold">
                                            <?php echo date('d/m/Y H:i', strtotime($usuario['ultima_conexion'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <small class="text-muted">Estado Actual</small>
                                    <div>
                                        <?php if ($usuario['estado'] === 'activo'): ?>
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

                                <?php if ($usuario['id'] == $_SESSION['user_id']): ?>
                                    <div class="alert alert-info p-2">
                                        <small>
                                            <i class="fas fa-info-circle me-1"></i>
                                            Este es tu usuario actual
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/admin-sweetalert.js"></script>

<script>
    // Validación en tiempo real
    document.addEventListener('DOMContentLoaded', function () {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const form = document.querySelector('form');

        function validatePasswords() {
            if (password.value && confirmPassword.value) {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                    confirmPassword.classList.add('is-invalid');
                } else {
                    confirmPassword.setCustomValidity('');
                    confirmPassword.classList.remove('is-invalid');
                    confirmPassword.classList.add('is-valid');
                }
            }
        }

        password.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);

        // Validar email en tiempo real
        const email = document.getElementById('email');
        email.addEventListener('blur', function () {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.setCustomValidity('Ingresa un email válido');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>