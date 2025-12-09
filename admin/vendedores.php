<?php
session_start();
require '../inc/db.php';
require '../inc/functions.php';
if(!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'])) {
    $nombre = trim($_POST['nombre']);
    $codigo = trim($_POST['codigo']);
    if($codigo!=='') {
        $stmt = $conn->prepare("INSERT INTO vendedores (nombre,codigo) VALUES (?,?)");
        $stmt->bind_param("ss",$nombre,$codigo);
        $stmt->execute();
    }
    header('Location: vendedores.php'); exit;
}
$rows = $conn->query("SELECT * FROM vendedores ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><link href="../assets/css/style_moderno.css" rel="stylesheet"></head><body>
<div class="container py-4">
  <a href="dashboard.php" class="btn btn-link">Volver</a>
  <h4>Agregar vendedor / código</h4>
  <form method="post" class="row g-2 mb-3">
    <div class="col-md-5"><input name="nombre" class="form-control" placeholder="Nombre"></div>
    <div class="col-md-5"><input name="codigo" class="form-control" placeholder="Código (secreto)" required></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Agregar</button></div>
  </form>
  <table class="table table-striped"><thead><tr><th>ID</th><th>Nombre</th><th>Código</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr><td><?= $r['id'] ?></td><td><?= e($r['nombre']) ?></td><td><?= e($r['codigo']) ?></td></tr><?php endforeach; ?>
  </tbody></table>
</div>
</body></html>
