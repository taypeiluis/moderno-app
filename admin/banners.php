<?php
session_start();
require '../inc/db.php';
require '../inc/functions.php';
if(!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    $titulo  = trim($_POST['titulo']);
    // el input del formulario puede seguir llamándose "link"
    $enlace  = trim($_POST['link']);
    $orden   = (int)$_POST['orden'];
    $imagen  = '';

    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $ext    = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $imagen = uniqid().'_'.time().'.'.$ext;
        move_uploaded_file($_FILES['imagen']['tmp_name'], '../uploads/banners/'.$imagen);
    }

    // OJO: la columna correcta en la BD es "enlace"
    $stmt = $conn->prepare("INSERT INTO banners (titulo,imagen,enlace,orden) VALUES (?,?,?,?)");
    $stmt->bind_param("sssi", $titulo, $imagen, $enlace, $orden);
    $stmt->execute();
    header('Location: banners.php'); exit;
}


$banners = $conn->query("SELECT * FROM banners ORDER BY orden ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><link href="../assets/css/style_moderno.css" rel="stylesheet"></head><body>
<div class="container py-4">
  <a href="dashboard.php" class="btn btn-link">Volver</a>
  <h4>Agregar banner</h4>
  <form method="post" enctype="multipart/form-data" class="mb-3">
    <div class="row g-2">
      <div class="col-md-6"><input name="titulo" class="form-control" placeholder="Título"></div>
      <div class="col-md-3"><input name="link" class="form-control" placeholder="Link (opcional)"></div>
      <div class="col-md-1"><input name="orden" class="form-control" value="0" /></div>
      <div class="col-md-2"><input name="imagen" type="file" class="form-control"></div>
    </div>
    <div class="mt-2"><button class="btn btn-primary">Agregar</button></div>
  </form>

  <h4>Listado</h4>
  <table class="table table-striped"><thead><tr><th>ID</th><th>Título</th><th>Imagen</th><th>Orden</th><th>Acción</th></tr></thead><tbody>
    <?php foreach($banners as $bn): ?>
      <tr>
        <td><?= $bn['id'] ?></td>
        <td><?= e($bn['titulo']) ?></td>
        <td><?= $bn['imagen'] ? '<img src="../uploads/banners/'.e($bn['imagen']).'" style="height:40px">' : '' ?></td>
        <td><?= $bn['orden'] ?></td>
        <td>
          <form method="post" action="banners_delete.php" style="display:inline">
            <input type="hidden" name="id" value="<?= $bn['id'] ?>">
            <button class="btn btn-sm btn-danger" onclick="return confirm('Eliminar?')">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody></table>
</div>
</body></html>
