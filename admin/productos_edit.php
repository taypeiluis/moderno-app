<?php
session_start();
require '../inc/db.php';
require '../inc/functions.php';
if(!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
if($id<=0) { header('Location: productos.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST') {
    $nombre = trim($_POST['nombre']);
    $categoria = (int)$_POST['categoria'];
    $desc = $_POST['descripcion'] ?? '';
    $pu = (float)$_POST['precio_unitario'];
    $pm = (float)$_POST['precio_mayor'];
    $imagen = null;
    if(isset($_FILES['imagen']) && $_FILES['imagen']['error']===0) {
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $imagen = uniqid().'_'.time().'.'.$ext;
        move_uploaded_file($_FILES['imagen']['tmp_name'], '../uploads/productos/'.$imagen);
        $old = $conn->query("SELECT imagen FROM productos WHERE id = $id")->fetch_assoc()['imagen'];
        if($old && file_exists("../uploads/productos/$old")) @unlink("../uploads/productos/$old");
    }
    if($imagen) {
        $stmt = $conn->prepare("UPDATE productos SET categoria_id=?, nombre=?, descripcion=?, precio_unitario=?, precio_mayor=?, imagen=? WHERE id=?");
        $stmt->bind_param("issddsi",$categoria,$nombre,$desc,$pu,$pm,$imagen,$id);
    } else {
        $stmt = $conn->prepare("UPDATE productos SET categoria_id=?, nombre=?, descripcion=?, precio_unitario=?, precio_mayor=? WHERE id=?");
        $stmt->bind_param("issddi",$categoria,$nombre,$desc,$pu,$pm,$id);
    }
    $stmt->execute();
    header('Location: productos.php'); exit;
}
$p = $conn->query("SELECT * FROM productos WHERE id = $id")->fetch_assoc();
$cats = $conn->query("SELECT id,nombre FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><link href="../assets/css/style_moderno.css" rel="stylesheet"></head><body>
<div class="container py-4">
  <a href="productos.php" class="btn btn-link">Volver</a>
  <h4>Editar producto</h4>
  <form method="post" enctype="multipart/form-data">
    <div class="mb-2"><input name="nombre" class="form-control" value="<?= e($p['nombre']) ?>" required></div>
    <div class="mb-2"><select name="categoria" class="form-select"><?php foreach($cats as $c) echo '<option value="'.$c['id'].'" '.($c['id']==$p['categoria_id']?'selected':'').'>'.e($c['nombre']).'</option>'; ?></select></div>
    <div class="mb-2"><textarea name="descripcion" class="form-control"><?= e($p['descripcion']) ?></textarea></div>
    <div class="mb-2"><input name="precio_unitario" type="number" step="0.01" class="form-control" value="<?= $p['precio_unitario'] ?>"></div>
    <div class="mb-2"><input name="precio_mayor" type="number" step="0.01" class="form-control" value="<?= $p['precio_mayor'] ?>"></div>
    <div class="mb-2"><input name="imagen" type="file" class="form-control"></div>
    <button class="btn btn-success">Actualizar</button>
  </form>
</div>
</body></html>
