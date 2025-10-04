<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';
require_once 'includes/helpers.php';

// Verificar si ya está logueado
if (Auth::isLoggedIn()) {
    $user = Auth::getUser();
    switch ($user['rol']) {
        case 'admin':
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

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setAlert('error', 'Error', 'Por favor complete todos los campos');
    } elseif (!validateEmail($email)) {
        setAlert('error', 'Error', 'Por favor ingrese un email válido');
    } else {
        if (Auth::login($email, $password)) {
            $user = Auth::getUser();
            setAlert('success', '¡Bienvenido!', 'Has iniciado sesión correctamente');

            // Redirigir según el rol
            switch ($user['rol']) {
                case 'admin':
                case 'administrador':
                    redirect(URL . 'admin/dashboard.php');
                    break;
                case 'vendedor':
                    redirect(URL . 'vendedor/dashboard.php');
                    break;
                case 'cliente':
                    redirect(URL . 'cliente/dashboard.php');
                    break;
                default:
                    redirect(URL . 'cliente/dashboard.php');
                    break;
            }
        } else {
            setAlert('error', 'Error de acceso', 'Email o contraseña incorrectos');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SweetPot</title>

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

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(135deg, var(--sweetpot-pink), var(--sweetpot-brown));
            color: white;
            text-align: center;
            padding: 2rem;
        }

        .login-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .login-header p {
            margin: 0;
            opacity: 0.9;
        }

        .login-body {
            padding: 2rem;
        }

        .form-floating label {
            color: var(--sweetpot-brown);
        }

        .form-control:focus {
            border-color: var(--sweetpot-pink);
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 157, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--sweetpot-pink), var(--sweetpot-brown));
            border: none;
            border-radius: 25px;
            padding: 0.8rem 2rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 157, 0.3);
            background: linear-gradient(135deg, var(--sweetpot-brown), var(--sweetpot-pink));
        }

        .register-link {
            color: var(--sweetpot-pink);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .register-link:hover {
            color: var(--sweetpot-brown);
        }

        .demo-accounts {
            background: rgba(255, 107, 157, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .demo-accounts h6 {
            color: var(--sweetpot-brown);
            margin-bottom: 0.5rem;
        }

        .demo-accounts small {
            display: block;
            margin-bottom: 0.2rem;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-birthday-cake"></i> SweetPot</h1>
                <p>Repostería Artesanal</p>
            </div>

            <div class="login-body">
                <form method="POST" action="">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email"
                            placeholder="nombre@ejemplo.com" required>
                        <label for="email"><i class="fas fa-envelope me-2"></i>Email</label>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Contraseña" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Contraseña</label>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="mb-0">¿No tienes cuenta?
                            <a href="<?= URL ?>registro.php" class="register-link">
                                <i class="fas fa-user-plus me-1"></i>Regístrate aquí
                            </a>
                        </p>
                    </div>
                </form>

                <!-- Cuentas de demostración -->
                <div class="demo-accounts">
                    <h6><i class="fas fa-info-circle me-2"></i>Cuentas de Prueba:</h6>
                    <small><strong>Admin:</strong> admin@sweetpot.com</small>
                    <small><strong>Vendedor:</strong> vendedor@sweetpot.com</small>
                    <small><strong>Cliente:</strong> cliente@sweetpot.com</small>
                    <small><strong>Contraseña para todas:</strong> password123</small>
                </div>
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
            const card = document.querySelector('.login-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });

        // Auto-completar con cuentas demo (solo para desarrollo)
        document.addEventListener('DOMContentLoaded', function () {
            const demoAccounts = document.querySelectorAll('.demo-accounts small');
            demoAccounts.forEach(account => {
                if (account.innerHTML.includes('@')) {
                    account.style.cursor = 'pointer';
                    account.addEventListener('click', function () {
                        const email = this.innerHTML.match(/[\w.-]+@[\w.-]+\.\w+/)[0];
                        document.getElementById('email').value = email;
                        document.getElementById('password').value = 'password123';
                    });
                }
            });
        });
    </script>
</body>

</html>