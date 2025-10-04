<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'SweetPot - Repostería Artesanal'; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap 5 JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Tus otros scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/cliente-sweetalert.js"></script>

    <!-- SweetPot Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?? ''; ?>/assets/css/sweetpot.css">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL ?? ''; ?>/assets/images/favicon.ico">

    <!-- Meta Tags -->
    <meta name="description" content="SweetPot - La mejor repostería artesanal con productos frescos y deliciosos">
    <meta name="keywords" content="repostería, pasteles, postres, tortas, dulces, artesanal">
    <meta name="author" content="SweetPot">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $pageTitle ?? 'SweetPot - Repostería Artesanal'; ?>">
    <meta property="og:description" content="Disfruta de los mejores productos de repostería artesanal en SweetPot">
    <meta property="og:image" content="<?php echo BASE_URL ?? ''; ?>/assets/images/og-image.jpg">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="website">

    <!-- Additional CSS for specific pages -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        /* Loading spinner para Ajax */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 234, 167, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        /* Estilos para notificaciones toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        /* Animaciones personalizadas */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translate3d(0, -100%, 0);
            }

            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        .fade-in-down {
            animation: fadeInDown 0.5s ease-out;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar-sweetpot {
                margin-left: -100%;
                transition: margin-left 0.3s;
            }

            .sidebar-sweetpot.show {
                margin-left: 0;
            }
        }
    </style>
</head>

<body class="bg-light">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-sweetpot mb-3"></div>
            <p class="text-sweetpot-brown">Cargando...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Skip to main content (for accessibility) -->
    <a class="visually-hidden-focusable" href="#main-content">Saltar al contenido principal</a>

    <?php
    // Mostrar alertas de sesión si existen
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "' . $alert['type'] . '",
                    title: "' . htmlspecialchars($alert['title']) . '",
                    text: "' . htmlspecialchars($alert['message']) . '",
                    confirmButtonColor: "#ff6b9d"
                });
            });
        </script>';
        unset($_SESSION['alert']);
    }
    ?>

    <!-- CSRF Token Meta Tag -->
    <?php if (isset($_SESSION['csrf_token'])): ?>
        <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <?php endif; ?>

    <!-- Main Content Start -->
    <div id="main-content">