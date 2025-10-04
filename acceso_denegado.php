<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - SweetPot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .error-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            margin: 0 auto;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .error-icon {
            font-size: 5rem;
            color: var(--sweetpot-pink);
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-10px);
            }

            60% {
                transform: translateY(-5px);
            }
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--sweetpot-pink), var(--sweetpot-brown));
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 157, 0.3);
            background: linear-gradient(135deg, var(--sweetpot-brown), var(--sweetpot-pink));
        }

        .brand {
            color: var(--sweetpot-brown);
            font-weight: bold;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="error-container">
            <div class="brand">
                <i class="fas fa-birthday-cake me-2"></i>SweetPot
            </div>
            <i class="fas fa-lock error-icon"></i>
            <h1 class="display-6 mb-3">Acceso Denegado</h1>
            <p class="lead mb-4">
                🍰 ¡Ups! No tienes permisos para acceder a esta sección dulce.
            </p>
            <p class="text-muted mb-4">
                Si crees que esto es un error, contacta con el administrador del sistema.
            </p>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="javascript:history.back()" class="btn btn-outline-secondary me-md-2">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
                <a href="<?= URL ?>login.php" class="btn btn-custom text-white">
                    <i class="fas fa-home me-2"></i>Ir al Inicio
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.querySelector('.error-container');
            container.style.opacity = '0';
            container.style.transform = 'scale(0.8)';

            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'scale(1)';
            }, 100);
        });
    </script>
</body>

</html>