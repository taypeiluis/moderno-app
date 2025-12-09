<?php
require_once __DIR__ . '/admin-config.php';

$res = $conn->query("SELECT p.*, c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC");
$productos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Productos | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/style_moderno.css">
</head>
<body class="bg-dark text-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Productos</h3>
    <a href="index.php" class="btn btn-sm btn-outline-light">← Volver al panel</a>
  </div>

  <table class="table table-dark table-striped table-sm align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>Precio público</th>
        <th>Precio distribuidor</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($productos as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= e($p['nombre']) ?></td>
          <td><?= e($p['categoria']) ?></td>
          <td>S/. <?= number_format($p['precio_unitario'], 2) ?></td>
          <td>S/. <?= number_format($p['precio_mayor'], 2) ?></td>
          <td>
            <a href="productos_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info">
              Editar
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
