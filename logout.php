<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';
require_once 'includes/helpers.php';

// Cerrar sesión
if (Auth::logout()) {
    setAlert('success', '¡Hasta pronto!', 'Has cerrado sesión correctamente');
}

// Redirigir al login
redirect(URL . 'login.php');
?>