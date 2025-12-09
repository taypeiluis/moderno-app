<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Eliminar datos de sesión del admin
unset($_SESSION['admin_logged'], $_SESSION['admin_name']);

// Opcional: destruir toda la sesión
// session_destroy();

header('Location: login.php');
exit;
