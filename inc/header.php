<?php
// inc/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a BD y funciones (del proyecto original)
require_once __DIR__ . '/db.php';        // aquí se define $conn
require_once __DIR__ . '/functions.php'; // aquí se define e()

$esMayorista = isset($_SESSION['mayorista']) && $_SESSION['mayorista'] === true;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Streaming Market</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons (para .bi) -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Estilo moderno (OJO con la ruta: proyecto en /moderno) -->
    <link rel="stylesheet"
          href="/moderno/assets/css/style_moderno.css?v=1">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="/moderno/index.php">Streaming Market</a>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>"
             href="/moderno/index.php">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link"
             href="/moderno/categoria.php">Categorías</a>
        </li>
        <li class="nav-item">
          <a class="nav-link"
             href="/moderno/producto.php">Productos</a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-light rounded-pill" type="button">
          <i class="bi bi-search"></i>
        </button>

        <?php if ($esMayorista): ?>
          <span class="text-warning small me-1">
            Distribuidor: <?= e($_SESSION['vendedor'] ?? '') ?>
          </span>
          <a class="btn btn-outline-light btn-sm rounded-pill"
             href="/moderno/mayorista-logout.php">
            Salir
          </a>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm rounded-pill"
             href="/moderno/mayorista-login.php">
            Acceso Distribuidor
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Contenedor principal de la página -->
<div class="container py-4">
