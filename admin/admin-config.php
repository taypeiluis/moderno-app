<?php
// admin/admin-config.php
// Configuración y protección básica para todas las páginas del admin

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos del proyecto
require_once __DIR__ . '/../inc/db.php';

// Función para escapar salida
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// Si no está logueado, redirigir a login
if (empty($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}
