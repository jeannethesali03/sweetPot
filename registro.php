<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';
require_once 'includes/helpers.php';
require_once 'models/Usuario.php';

// Verificar si ya está logueado
if (Auth::isLoggedIn()) {
    $user = Auth::getUser();
    switch ($user['rol']) {
        case 'administrador':
            redirect(URL . 'admin/dashboard.php');
            break;
        case 'vendedor':
            redirect(URL . 'vendedor/dashboard.php');
            break;
        case 'cliente':
            redirect(URL . 'cliente/dashboard.php');
            break;
    }
    exit();
}

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = cleanInput($_POST['nombre'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $telefono = cleanInput($_POST['telefono'] ?? '');
    $direccion = cleanInput($_POST['direccion'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errores = [];

    // Validaciones
    if (empty($nombre)) {
        $errores[] = 'El nombre es requerido';
    }

    if (empty($email)) {
        $errores[] = 'El email es requerido';
    } elseif (!validateEmail($email)) {
        $errores[] = 'El email no es válido';
    }

    if (empty($password)) {
        $errores[] = 'La contraseña es requerida';
    } elseif (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres';
    }

    if ($password !== $confirm_password) {
        $errores[] = 'Las contraseñas no coinciden';
    }

    // Verificar si el email ya existe
    if (empty($errores)) {
        $usuario = new Usuario();
        if ($usuario->existeEmail($email)) {
            $errores[] = 'Este email ya está registrado';
        }
    }

    if (empty($errores)) {
        // Crear usuario
        $datos_usuario = [
            'nombre' => $nombre,
            'email' => $email,
            'password' => $password,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'rol' => 'cliente',
            'estado' => 'activo'
        ];

        $usuario_id = $usuario->crear($datos_usuario);

        if ($usuario_id) {
            setAlert('success', '¡Registro exitoso!', 'Tu cuenta ha sido creada. Ahora puedes iniciar sesión.');
            redirect(URL . 'login.php');
        } else {
            setAlert('error', 'Error', 'Hubo un problema al crear tu cuenta. Inténtalo de nuevo.');
        }
    } else {
        setAlert('error', 'Errores en el formulario', implode('<br>', $errores));
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - SweetPot</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        :root {
            --sweetpot-pink: #ff6b9d;
            --sweetpot-cream: #ffeaa7;
            --sweetpot-brown: #8b4513;
            --sweetpot-light-pink: #ffb3d1;
        }

        body {
            background: linear-gradient(135deg, var(--sweetpot-cream) 0%, var(--sweetpot-light-pink) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }

        .register-header {
            background: linear-gradient(135deg, var(--sweetpot-pink), var(--sweetpot-brown));
            color: white;
            text-align: center;
            padding: 2rem;
        }

        .register-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .register-header p {
            margin: 0;
            opacity: 0.9;
        }

        .register-body {
            padding: 2rem;
        }

        .form-floating label {
            color: var(--sweetpot-brown);
        }

        .form-control:focus {
            border-color: var(--sweetpot-pink);
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 157, 0.25);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--sweetpot-pink), var(--sweetpot-brown));
            border: none;
            border-radius: 25px;
            padding: 0.8rem 2rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 157, 0.3);
            background: linear-gradient(135deg, var(--sweetpot-brown), var(--sweetpot-pink));
        }

        .login-link {
            color: var(--sweetpot-pink);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link:hover {
            color: var(--sweetpot-brown);
        }

        .password-strength {
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .strength-weak {
            color: #dc3545;
        }

        .strength-medium {
            color: #ffc107;
        }

        .strength-strong {
            color: #198754;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1><i class="fas fa-birthday-cake"></i> SweetPot</h1>
                <p>Únete a nuestra familia</p>
            </div>

            <div class="register-body">
                <form method="POST" action="" id="registerForm">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                    placeholder="Nombre completo" required
                                    value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
                                <label for="nombre"><i class="fas fa-user me-2"></i>Nombre completo</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="nombre@ejemplo.com" required
                                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                <label for="email"><i class="fas fa-envelope me-2"></i>Email</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="telefono" name="telefono"
                                    placeholder="Teléfono"
                                    value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>">
                                <label for="telefono"><i class="fas fa-phone me-2"></i>Teléfono</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="direccion" name="direccion" placeholder="Dirección"
                            style="height: 80px"><?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?></textarea>
                        <label for="direccion"><i class="fas fa-map-marker-alt me-2"></i>Dirección</label>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Contraseña" required>
                                <label for="password"><i class="fas fa-lock me-2"></i>Contraseña</label>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" placeholder="Confirmar contraseña" required>
                                <label for="confirm_password"><i class="fas fa-lock me-2"></i>Confirmar
                                    contraseña</label>
                            </div>
                            <div id="passwordMatch"></div>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-register">
                            <i class="fas fa-user-plus me-2"></i>Crear Cuenta
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="mb-0">¿Ya tienes cuenta?
                            <a href="<?= URL ?>login.php" class="login-link">
                                <i class="fas fa-sign-in-alt me-1"></i>Iniciar sesión
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

    <?php showAlert(); ?>

    <script>
        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function () {
            const card = document.querySelector('.register-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });

        // Validación de fortaleza de contraseña
        document.getElementById('password').addEventListener('input', function () {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');

            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            let strengthText = '';
            let strengthClass = '';

            if (strength < 2) {
                strengthText = 'Débil';
                strengthClass = 'strength-weak';
            } else if (strength < 4) {
                strengthText = 'Media';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Fuerte';
                strengthClass = 'strength-strong';
            }

            strengthDiv.innerHTML = `<i class="fas fa-shield-alt me-1"></i>Fortaleza: <span class="${strengthClass}">${strengthText}</span>`;
        });

        // Validación de confirmación de contraseña
        document.getElementById('confirm_password').addEventListener('input', function () {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }

            if (password === confirmPassword) {
                matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Las contraseñas coinciden</small>';
            } else {
                matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Las contraseñas no coinciden</small>';
            }
        });

        // Validación del formulario antes del envío
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Las contraseñas no coinciden',
                    confirmButtonColor: '#ff6b9d'
                });
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'La contraseña debe tener al menos 6 caracteres',
                    confirmButtonColor: '#ff6b9d'
                });
                return false;
            }
        });
    </script>
</body>

</html>