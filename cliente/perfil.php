<?php
// Iniciar sesión antes que cualquier output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/Auth.php';

// Verificar que el usuario esté logueado y sea cliente
if (!Auth::isLoggedIn() || !Auth::isCliente()) {
    header('Location: ../login.php');
    exit;
}

$user = Auth::getUser();
$mensaje = '';
$error = '';

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Validar datos
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);

        // Validaciones básicas
        if (empty($nombre) || empty($email)) {
            throw new Exception('El nombre y email son obligatorios');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El formato del email no es válido');
        }

        // Verificar si el email ya existe (pero no es el actual)
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :user_id");
        $stmt->execute([
            ':email' => $email,
            ':user_id' => $user['id']
        ]);

        if ($stmt->fetch()) {
            throw new Exception('El email ya está registrado por otro usuario');
        }

        // Actualizar datos del usuario
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET nombre = :nombre, email = :email, telefono = :telefono, direccion = :direccion
            WHERE id = :user_id
        ");

        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':telefono' => $telefono,
            ':direccion' => $direccion,
            ':user_id' => $user['id']
        ]);

        // Actualizar variables de sesión
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email'] = $email;
        $_SESSION['telefono'] = $telefono;
        $_SESSION['direccion'] = $direccion;

        $mensaje = 'Perfil actualizado correctamente';

        // Actualizar datos locales
        $user['nombre'] = $nombre;
        $user['email'] = $email;
        $user['telefono'] = $telefono;
        $user['direccion'] = $direccion;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Procesar cambio de contraseña
if (isset($_POST['cambiar_password'])) {
    try {
        $password_actual = $_POST['password_actual'];
        $password_nueva = $_POST['password_nueva'];
        $password_confirmar = $_POST['password_confirmar'];

        if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
            throw new Exception('Todos los campos de contraseña son obligatorios');
        }

        if ($password_nueva !== $password_confirmar) {
            throw new Exception('Las contraseñas nuevas no coinciden');
        }

        if (strlen($password_nueva) < 6) {
            throw new Exception('La contraseña debe tener al menos 6 caracteres');
        }

        $db = new Database();
        $conn = $db->getConnection();

        // Verificar contraseña actual
        $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = :user_id");
        $stmt->execute([':user_id' => $user['id']]);
        $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($password_actual, $usuario_data['password'])) {
            throw new Exception('La contraseña actual es incorrecta');
        }

        // Actualizar contraseña
        $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
        $stmt->execute([
            ':password' => $password_hash,
            ':user_id' => $user['id']
        ]);

        $mensaje = 'Contraseña actualizada correctamente';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - SweetPot</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- SweetPot theme overrides -->
    <link rel="stylesheet" href="../assets/css/sweetpot-theme.css">

    <style>
        .navbar-sweetpot {
            background: linear-gradient(135deg, var(--sp-pink), var(--sp-brown));
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
        }

        .navbar-sweetpot .navbar-brand {
            color: white !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .navbar-sweetpot .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            margin: 0 0.2rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .navbar-sweetpot .nav-link:hover,
        .navbar-sweetpot .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white !important;
        }

        .navbar-sweetpot .navbar-toggler {
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.25rem 0.5rem;
        }

        .navbar-sweetpot .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .profile-header {
            background: linear-gradient(135deg, var(--sp-pink), var(--sp-brown));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--sp-cream), var(--sp-light-pink));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--sp-pink);
            margin: 0 auto 1.5rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--sp-pink), var(--sp-brown));
            color: white;
            border-radius: 20px 20px 0 0 !important;
            border: none;
            font-weight: bold;
            padding: 1.25rem 1.5rem;
        }

        .btn-sweetpot {
            background: linear-gradient(135deg, var(--sp-pink), var(--sp-brown));
            border: none;
            color: white;
            border-radius: 25px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-sweetpot:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(200, 120, 140, 0.18);
            background: linear-gradient(135deg, var(--sp-brown), var(--sp-pink));
            color: white;
        }

        .btn-sweetpot-outline {
            background: transparent;
            border: 2px solid var(--sp-pink);
            color: var(--sp-pink);
        }

        .btn-sweetpot-outline:hover {
            background: var(--sp-pink);
            color: white;
        }

        .form-control:focus {
            border-color: var(--sp-pink);
            box-shadow: 0 0 0 0.25rem rgba(211, 107, 127, 0.18);
        }

        .form-select:focus {
            border-color: var(--sp-pink);
            box-shadow: 0 0 0 0.25rem rgba(211, 107, 127, 0.18);
        }

        .carrito-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--sp-active);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Estilos específicos para el perfil */
        .profile-stats {
            background: linear-gradient(135deg, var(--sp-cream), var(--sp-light-pink));
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--sp-pink);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--sp-brown);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .profile-section {
            margin-bottom: 2rem;
        }

        .section-title {
            color: var(--sp-brown);
            font-weight: bold;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--sp-light-pink);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background-color: rgba(255, 234, 167, 0.06);
            border-radius: 10px;
            border-left: 4px solid var(--sp-pink);
        }

        .info-label {
            font-weight: 600;
            color: var(--sp-brown);
        }

        .info-value {
            color: var(--sp-muted-gray);
        }

        /* Avatar upload */
        .avatar-upload {
            position: relative;
            display: inline-block;
        }

        .avatar-upload-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--sp-pink);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .avatar-upload-btn:hover {
            background: var(--sp-brown);
            transform: scale(1.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                padding: 2rem 0;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }

            .btn-sweetpot {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .navbar-sweetpot .navbar-brand {
                font-size: 1.2rem;
            }

            .stat-number {
                font-size: 1.5rem;
            }
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-avatar {
            animation: fadeInUp 0.6s ease forwards;
        }

        .card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .card:nth-child(odd) {
            animation-delay: 0.2s;
        }

        /* Badges personalizados */
        .badge-sweetpot {
            background: linear-gradient(135deg, var(--sp-pink), var(--sp-brown));
            color: white;
            border-radius: 15px;
            padding: 0.4rem 0.8rem;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>

    <!-- Header del perfil -->
    <div class="profile-header">
        <div class="container text-center">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h2><?php echo htmlspecialchars($user['nombre']); ?></h2>
            <p class="mb-0">
                <i class="fas fa-envelope me-2"></i>
                <?php echo htmlspecialchars($user['email']); ?>
            </p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Mensajes -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Información Personal -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-edit me-2"></i>
                            Información Personal
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-user me-1"></i>
                                        Nombre Completo *
                                    </label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                        value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>
                                        Email *
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone me-1"></i>
                                        Teléfono
                                    </label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono"
                                        value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Dirección
                                    </label>
                                    <input type="text" class="form-control" id="direccion" name="direccion"
                                        value="<?php echo htmlspecialchars($user['direccion'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-sweetpot text-white">
                                    <i class="fas fa-save me-2"></i>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Panel Lateral -->
            <div class="col-md-4">
                <!-- Estadísticas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Mis Estadísticas
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $db = new Database();
                            $conn = $db->getConnection();

                            // Contar pedidos
                            $stmt = $conn->prepare("SELECT COUNT(*) as total_pedidos FROM ventas WHERE cliente_id = :user_id");
                            $stmt->execute([':user_id' => $user['id']]);
                            $total_pedidos = $stmt->fetchColumn();

                            // Total gastado (incluye pedidos entregados y enviados)
                            $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total_gastado FROM ventas WHERE cliente_id = :user_id AND estado IN ('entregado', 'enviado')");
                            $stmt->execute([':user_id' => $user['id']]);
                            $total_gastado = $stmt->fetchColumn();

                            // Si no hay ventas entregadas/enviadas, mostrar el total de todas las ventas (excepto canceladas)
                            if ($total_gastado == 0) {
                                $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total_gastado FROM ventas WHERE cliente_id = :user_id AND estado != 'cancelado'");
                                $stmt->execute([':user_id' => $user['id']]);
                                $total_gastado = $stmt->fetchColumn();
                            }

                            // Obtener último pedido
                            $stmt = $conn->prepare("SELECT fecha, estado, total FROM ventas WHERE cliente_id = :user_id ORDER BY fecha DESC LIMIT 1");
                            $stmt->execute([':user_id' => $user['id']]);
                            $ultimo_pedido = $stmt->fetch(PDO::FETCH_ASSOC);

                        } catch (Exception $e) {
                            $total_pedidos = 0;
                            $total_gastado = 0;
                            $ultimo_pedido = null;
                        }
                        ?>

                        <div class="text-center">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <h3 class="text-primary p-3 mb-0" style="border-radius: 5px;">
                                        <?php echo $total_pedidos; ?>
                                    </h3>
                                    <small class="text-muted">Pedidos Realizados</small>
                                </div>
                                <div class="col-6">
                                    <h3 class="text-success p-3 mb-0" style="border-radius: 5px;">
                                        $<?php echo number_format($total_gastado, 2); ?>
                                    </h3>
                                    <small class="text-muted">Total Gastado</small>
                                </div>
                            </div>

                            <?php if ($ultimo_pedido): ?>
                                <hr class="my-3">
                                <div class="text-start">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-clock me-1"></i>
                                        Último Pedido
                                    </h6>
                                    <p class="mb-1">
                                        <strong>Fecha:</strong>
                                        <?php echo date('d/m/Y', strtotime($ultimo_pedido['fecha'])); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Total:</strong> $<?php echo number_format($ultimo_pedido['total'], 2); ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Estado:</strong>
                                        <span class="badge bg-<?php
                                        echo match ($ultimo_pedido['estado']) {
                                            'pendiente' => 'warning',
                                            'en_proceso' => 'info',
                                            'enviado' => 'primary',
                                            'entregado' => 'success',
                                            'cancelado' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ultimo_pedido['estado'])); ?>
                                        </span>
                                    </p>
                                </div>
                            <?php else: ?>
                                <hr class="my-3">
                                <p class="text-muted mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Aún no has realizado pedidos
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Acciones Rápidas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="mis-pedidos.php" class="btn btn-outline-primary">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Ver Mis Pedidos
                            </a>
                            <a href="productos.php" class="btn btn-outline-success">
                                <i class="fas fa-birthday-cake me-2"></i>
                                Explorar Productos
                            </a>
                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal"
                                data-bs-target="#passwordModal">
                                <i class="fas fa-key me-2"></i>
                                Cambiar Contraseña
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>
                        Cambiar Contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="password_actual" class="form-label">Contraseña Actual *</label>
                            <input type="password" class="form-control" id="password_actual" name="password_actual"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="password_nueva" class="form-label">Nueva Contraseña *</label>
                            <input type="password" class="form-control" id="password_nueva" name="password_nueva"
                                required>
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña *</label>
                            <input type="password" class="form-control" id="password_confirmar"
                                name="password_confirmar" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cambiar_password" class="btn btn-sweetpot">
                            <i class="fas fa-save me-2"></i>
                            Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/cliente-sweetalert.js"></script>

    <script>
        // Validación en tiempo real de contraseñas
        document.addEventListener('DOMContentLoaded', function () {
            const passwordNueva = document.getElementById('password_nueva');
            const passwordConfirmar = document.getElementById('password_confirmar');

            function validarPasswords() {
                if (passwordNueva.value && passwordConfirmar.value) {
                    if (passwordNueva.value === passwordConfirmar.value) {
                        passwordConfirmar.classList.remove('is-invalid');
                        passwordConfirmar.classList.add('is-valid');
                    } else {
                        passwordConfirmar.classList.remove('is-valid');
                        passwordConfirmar.classList.add('is-invalid');
                    }
                }
            }

            passwordNueva.addEventListener('input', validarPasswords);
            passwordConfirmar.addEventListener('input', validarPasswords);
        });

        // Mostrar mensajes con SweetAlert si hay
        <?php if (!empty($mensaje)): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '<?php echo addslashes($mensaje); ?>',
                timer: 3000,
                showConfirmButton: false
            });
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo addslashes($error); ?>'
            });
        <?php endif; ?>
    </script>
</body>

</html>