<?php
session_start();
require_once 'includes/Auth.php';

// Cerrar sesión
Auth::logout();

// Redirigir al login
header('Location: login.php?logout=1');
exit();
?>