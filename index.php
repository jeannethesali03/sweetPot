<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';

// Verificar si el usuario ya está logueado
if (Auth::isLoggedIn()) {
    $user = Auth::getUser();
    if ($user['rol'] === 'administrador') {
        header('Location: ' . URL . 'admin/dashboard.php');
    } else if ($user['rol'] === 'cliente') {
        header('Location: ' . URL . 'cliente/dashboard.php');
    } else if ($user['rol'] === 'vendedor') {
        header('Location: ' . URL . 'vendedor/dashboard.php');

    }
    exit();
}

// Si no está logueado, redirigir al login
header('Location: ' . URL . 'login.php');
exit();
?>