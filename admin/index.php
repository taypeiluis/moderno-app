<?php
require_once __DIR__ . '/admin-config.php';

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

// Inicializar estadísticas
$stats = [
    'productos'   => 0,
    'categorias'  => 0,
    'banners'     => 0,
    'vendedores'  => 0,
    'admin_users' => 0,
];

// Contar productos
if ($res = $conn->query("SELECT COUNT(*) AS c FROM productos")) {
    $row = $res->fetch_assoc();
    $stats['productos'] = (int)$row['c'];
}

// Contar categorías
if ($res = $conn->query("SELECT COUNT(*) AS c FROM categorias")) {
    $row = $res->fetch_assoc();
    $stats['categorias'] = (int)$row['c'];
}

// Contar banners (si existe la tabla)
if ($res = @$conn->query("SELECT COUNT(*) AS c FROM banners")) {
    $row = $res->fetch_assoc();
    $stats['banners'] = (int)$row['c'];
}

// Contar vendedores (si existe la tabla vendedores)
if ($res = @$conn->query("SELECT COUNT(*) AS c FROM vendedores")) {
    $row = $res->fetch_assoc();
    $stats['vendedores'] = (int)$row['c'];
}

// Contar usuarios admin (admin_usuarios)
if ($res = @$conn->query("SELECT COUNT(*) AS c FROM admin_usuarios")) {
    $row = $res->fetch_assoc();
    $stats['admin_users'] = (int)$row['c'];
}

// Nombre del admin logueado
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel administrador | Streaming Market</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Icons -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- Estilos del proyecto -->
  <link rel="stylesheet" href="../assets/css/style_moderno.css">
</head>
<body class="bg-dark text-light">

<nav class="navbar navbar-dark bg-black navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      Admin | Streaming Market
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#adminNavbar" aria-controls="adminNavbar"
            aria-expanded="false" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="adminNavbar">
      <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item me-lg-3">
          <span class="nav-link disabled small">
            Hola, <?= e($adminName) ?>
          </span>
        </li>
        <li class="nav-item me-lg-2">
          <a href="../index.php" class="btn btn-sm btn-outline-light">
            <i class="bi bi-shop"></i> Ver tienda
          </a>
        </li>
        <li class="nav-item">
          <a href="logout.php" class="btn btn-sm btn-outline-danger ms-lg-2 mt-2 mt-lg-0">
            <i class="bi bi-box-arrow-right"></i> Salir
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-4">

  <div class="mb-4">
    <h3 class="mb-1">Panel administrador</h3>
    <p class="small text-muted mb-0">
      Gestiona productos, categorías, banners, vendedores y usuarios del sistema.
    </p>
  </div>

  <!-- Tarjetas de resumen -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card glass p-3 text-light h-100">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <h6 class="mb-0">Productos</h6>
          <i class="bi bi-box-seam"></i>
        </div>
        <p class="display-6 mb-1"><?= $stats['productos'] ?></p>
        <a href="productos.php" class="small text-info text-decoration-none">
          Gestionar productos →
        </a>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
      <div class="card glass p-3 text-light h-100">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <h6 class="mb-0">Categorías</h6>
          <i class="bi bi-tags"></i>
        </div>
        <p class="display-6 mb-1"><?= $stats['categorias'] ?></p>
        <a href="categorias.php" class="small text-info text-decoration-none">
          Gestionar categorías →
        </a>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
      <div class="card glass p-3 text-light h-100">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <h6 class="mb-0">Banners</h6>
          <i class="bi bi-images"></i>
        </div>
        <p class="display-6 mb-1"><?= $stats['banners'] ?></p>
        <a href="banners.php" class="small text-info text-decoration-none">
          Gestión de banners →
        </a>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
      <div class="card glass p-3 text-light h-100">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <h6 class="mb-0">Vendedores</h6>
          <i class="bi bi-people"></i>
        </div>
        <p class="display-6 mb-1"><?= $stats['vendedores'] ?></p>
        <a href="vendedores.php" class="small text-info text-decoration-none">
          Gestión de distribuidores →
        </a>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
      <div class="card glass p-3 text-light h-100">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <h6 class="mb-0">Usuarios admin</h6>
          <i class="bi bi-shield-lock"></i>
        </div>
        <p class="display-6 mb-1"><?= $stats['admin_users'] ?></p>
        <a href="usuarios_admin.php" class="small text-info text-decoration-none">
          Gestión de admins →
        </a>
      </div>
    </div>
  </div>

  <!-- Enlaces rápidos / ayuda -->
  <div class="row g-3">
    <div class="col-md-6">
      <div class="card glass p-3 text-light h-100">
        <h5 class="mb-3">Accesos rápidos</h5>
        <ul class="small mb-0">
          <li><a href="productos.php" class="text-info text-decoration-none">Ver / editar productos</a></li>
          <li><a href="productos_edit.php?id=0" class="text-info text-decoration-none">Crear nuevo producto</a></li>
          <li><a href="categorias.php" class="text-info text-decoration-none">Gestionar categorías</a></li>
          <li><a href="banners.php" class="text-info text-decoration-none">Configurar carrusel / banners</a></li>
          <li><a href="vendedores.php" class="text-info text-decoration-none">Agregar / editar distribuidores</a></li>
        </ul>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card glass p-3 text-light h-100">
        <h5 class="mb-3">Resumen técnico</h5>
        <p class="small text-muted mb-2">
          Este panel controla el catálogo que se muestra en la tienda pública:
        </p>
        <ul class="small text-muted mb-0">
          <li><strong>Productos</strong>: aparecen en el catálogo y en la búsqueda.</li>
          <li><strong>Precios mayoristas</strong>: visibles solo para distribuidores con código válido.</li>
          <li><strong>Banners</strong>: imágenes del carrusel principal.</li>
          <li><strong>Vendedores</strong>: códigos de acceso para el modo distribuidor.</li>
          <li><strong>Usuarios admin</strong>: controlan el acceso a este panel.</li>
        </ul>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
