<?php
session_start();
require '../inc/db.php';
require '../inc/functions.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['clave'] ?? '';
    $stmt = $conn->prepare("SELECT id,usuario,clave FROM usuarios_admin WHERE usuario = ? LIMIT 1");
    $stmt->bind_param("s",$user);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows===1) {
        $row = $res->fetch_assoc();
        if(password_verify($pass, $row['clave'])) {
            $_SESSION['admin'] = $row['usuario'];
            header('Location: dashboard.php'); exit;
        } else $msg='Credenciales inválidas';
    } else $msg='Usuario no encontrado';
}
?>
<!doctype html><html><head><meta charset="utf-8"><link href="../assets/css/style_moderno.css" rel="stylesheet"></head><body>
<div class="container py-5"><div class="row justify-content-center"><div class="col-md-5">
  <div class="card p-3">
    <h5>Panel Administrador</h5>
    <?php if($msg) echo '<div class="alert alert-danger">'.$msg.'</div>'; ?>
    <form method="post">
      <input class="form-control mb-2" name="usuario" placeholder="Usuario" required>
      <input class="form-control mb-2" name="clave" type="password" placeholder="Clave" required>
      <div class="d-flex justify-content-between">
        <button class="btn btn-primary">Entrar</button>
        <a class="btn btn-link" href="../index.php">Ver sitio</a>
      </div>
    </form>
  </div>
</div></div></div>
</body></html>
/admin/dashboard.php
php
Copiar código
<?php
session_start();
require '../inc/db.php';
require '../inc/functions.php';
if(!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
$totales = [];
$totales['productos'] = $conn->query("SELECT COUNT(*) as c FROM productos")->fetch_assoc()['c'];
$totales['categorias'] = $conn->query("SELECT COUNT(*) as c FROM categorias")->fetch_assoc()['c'];
$totales['vendedores'] = $conn->query("SELECT COUNT(*) as c FROM vendedores")->fetch_assoc()['c'];
?>
<!doctype html><html><head><meta charset="utf-8"><link href="../assets/css/style_moderno.css" rel="stylesheet"></head><body>
<nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="dashboard.php">Admin</a><div class="text-white"><?= e($_SESSION['admin']) ?> <a class="btn btn-sm btn-secondary ms-2" href="logout.php">Salir</a></div></div></nav>
<div class="container py-4">
  <div class="row g-3">
    <div class="col-md-4"><div class="card p-3"><h6>Productos</h6><h3><?= $totales['productos'] ?></h3><a href="productos.php">Gestionar</a></div></div>
    <div class="col-md-4"><div class="card p-3"><h6>Categorías</h6><h3><?= $totales['categorias'] ?></h3><a href="categorias.php">Gestionar</a></div></div>
    <div class="col-md-4"><div class="card p-3"><h6>Vendedores/Banners</h6><h3><?= $totales['vendedores'] ?></h3><a href="vendedores.php">Gestionar</a> <br><a href="banners.php">Banners</a></div></div>
  </div>
</div>
</body></html>