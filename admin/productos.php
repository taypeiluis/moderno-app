<?php
session_start();
require '../inc/db.php';
require '../inc/functions.php';
if(!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);
    $categoria = (int)$_POST['categoria'];
    $desc = $_POST['descripcion'] ?? '';
    $pu = (float)$_POST['precio_unitario'];
    $pm = (float)$_POST['precio_mayor'];
    $imagen = '';

    if(isset($_FILES['imagen']) && $_FILES['imagen']['error']===0) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed) && $_FILES['imagen']['size'] < 5*1024*1024) {
            $imagen = uniqid().'_'.time().'.'.$ext;
            move_uploaded_file($_FILES['imagen']['tmp_name'], '../uploads/productos/'.$imagen);
        }
    }

    $stmt = $conn->prepare("INSERT INTO productos (categoria_id,nombre,descripcion,precio_unitario,precio_mayor,imagen) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("issdds",$categoria,$nombre,$desc,$pu,$pm,$imagen);
    $stmt->execute();
    header('Location: productos.php'); exit;
}

$productos = $conn->query("SELECT p.*, c.nombre as categoria FROM productos p LEFT JOIN categorias c ON p.categoria_id=c.id ORDER BY p.id DESC")->fetch_all(MYSQLI_ASSOC);
$cats = $conn->query("SELECT id,nombre FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><link href="../assets/css/style_moderno.css" rel="stylesheet"></head><body>
<div class="container py-4">
  <a href="dashboard.php" class="btn btn-link">Volver</a>
  <h4>Agregar producto</h4>
  <form method="post" enctype="multipart/form-data" class="mb-4">
    <div class="row g-2">
      <div class="col-md-6"><input name="nombre" class="form-control" placeholder="Nombre" required></div>
      <div class="col-md-3"><select name="categoria" class="form-select" required><?php foreach($cats as $c) echo '<option value="'.$c['id'].'">'.e($c['nombre']).'</option>'; ?></select></div>
      <div class="col-md-3"><input name="imagen" type="file" class="form-control"></div>
      <div class="col-12"><textarea name="descripcion" class="form-control" placeholder="Descripción"></textarea></div>
      <div class="col-md-6"><input name="precio_unitario" type="number" step="0.01" class="form-control" placeholder="Precio unitario" required></div>
      <div class="col-md-6"><input name="precio_mayor" type="number" step="0.01" class="form-control" placeholder="Precio mayorista" required></div>
    </div>
    <div class="mt-2"><button class="btn btn-success">Guardar</button></div>
  </form>

  <h4>Listado de productos</h4>
  <table class="table table-hover"><thead><tr><th>ID</th><th>Nombre</th><th>Cat</th><th>Unit</th><th>Mayor</th><th>Acción</th></tr></thead><tbody>
    <?php foreach($productos as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= e($p['nombre']) ?></td>
        <td><?= e($p['categoria']) ?></td>
        <td><?= number_format($p['precio_unitario'],2) ?></td>
        <td><?= number_format($p['precio_mayor'],2) ?></td>
        <td>
          <a class="btn btn-sm btn-primary" href="productos_edit.php?id=<?= $p['id'] ?>">Editar</a>
          <form method="post" action="productos_delete.php" style="display:inline">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button class="btn btn-sm btn-danger" onclick="return confirm('Eliminar?')">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody></table>
</div>
</body></html>
