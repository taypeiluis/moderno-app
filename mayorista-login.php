<?php
require 'inc/db.php';
session_start();
$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $stmt = $conn->prepare("SELECT id,nombre FROM vendedores WHERE codigo = ? LIMIT 1");
    $stmt->bind_param("s",$codigo);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $_SESSION['mayorista'] = true;
        $_SESSION['vendedor'] = $row['nombre'];
        header('Location: index.php');
        exit;
    } else $msg = 'Código inválido';
}
?>
<!doctype html><html><head><meta charset="utf-8"><link href="assets/css/style_moderno.css" rel="stylesheet"></head><body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card p-3">
        <h5>Acceso Distribuidor</h5>
        <?php if($msg) echo '<div class="alert alert-danger">'.$msg.'</div>'; ?>
        <form method="post">
          <div class="mb-3"><input class="form-control" name="codigo" placeholder="Código vendedor" required></div>
          <button class="btn btn-primary">Entrar</button>
          <a class="btn btn-link" href="index.php">Volver</a>
        </form>
      </div>
    </div>
  </div>
</div>
</body></html>
