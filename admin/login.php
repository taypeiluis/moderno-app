<?php
// admin/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
require_once __DIR__ . '/../inc/db.php';

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['clave'] ?? '');

    if ($user === '' || $pass === '') {
        $msg = 'Ingresa usuario y contraseña.';
    } else {
        // Buscar usuario en la tabla admin_usuarios
        $stmt = $conn->prepare("
            SELECT id, usuario, password, rol
            FROM admin_usuarios
            WHERE usuario = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();

            // Tu tabla guarda SHA-256 en la columna password
            $hashIngresado = hash('sha256', $pass);

            if ($hashIngresado === $row['password']) {
                // ✅ Credenciales correctas
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_id']    = $row['id'];
                $_SESSION['admin_name']  = $row['usuario'];
                $_SESSION['admin_rol']   = $row['rol'];

                header('Location: index.php');
                exit;
            } else {
                // Usuario existe pero clave no coincide
                $msg = 'Credenciales incorrectas.';
            }
        } else {
            // Usuario no existe
            $msg = 'Credenciales incorrectas.';
        }

        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Acceso administrador | Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- Estilos del proyecto -->
  <link rel="stylesheet" href="../assets/css/style_moderno.css">
</head>
<body class="bg-dark text-light">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card glass p-4 text-light">
        <h4 class="mb-3 text-center">Panel administrador</h4>
        <p class="small text-muted text-center mb-4">
          Inicia sesión para gestionar productos y catálogos.
        </p>

        <?php if ($msg): ?>
          <div class="alert alert-danger py-2">
            <?= e($msg) ?>
          </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
          <div class="mb-3">
            <label class="form-label small">Usuario</label>
            <input type="text"
                   name="usuario"
                   class="form-control bg-dark text-light border-secondary"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label small">Contraseña</label>
            <input type="password"
                   name="clave"
                   class="form-control bg-dark text-light border-secondary"
                   required>
          </div>

          <button class="btn btn-primary w-100">
            <i class="bi bi-door-open"></i> Entrar
          </button>

          <a href="../index.php" class="btn btn-outline-light btn-sm w-100 mt-2">
            Volver a la tienda
          </a>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
