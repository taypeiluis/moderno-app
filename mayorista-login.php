<?php
// mayorista-login.php
require_once __DIR__ . '/inc/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado como mayorista, lo mandamos al inicio
if (!empty($_SESSION['mayorista']) && $_SESSION['mayorista'] === true) {
    header('Location: index.php');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');

    if ($codigo === '') {
        $msg = 'Por favor ingresa tu código de distribuidor.';
    } else {
        $stmt = $conn->prepare("SELECT id, nombre FROM vendedores WHERE codigo = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                $row = $res->fetch_assoc();

                // Activamos modo mayorista
                $_SESSION['mayorista'] = true;
                $_SESSION['vendedor']  = $row['nombre'];

                header('Location: index.php');
                exit;
            } else {
                $msg = 'Código inválido. Verifica con tu ejecutivo comercial.';
            }

            $stmt->close();
        } else {
            $msg = 'Error interno al validar el código.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <title>Acceso Distribuidor | Streaming Market</title>

  <!-- Bootstrap CSS -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- Estilos del proyecto -->
  <link rel="stylesheet" href="assets/css/style_moderno.css">
</head>
<body class="text-light">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
      <div class="card glass p-4">
        <div class="mb-3 text-center">
          <h4 class="mb-1">Acceso Distribuidor</h4>
          <p class="text-muted small mb-0">
            Ingresa tu código para ver precios especiales.
          </p>
        </div>

        <?php if ($msg): ?>
          <div class="alert alert-danger py-2">
            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
          <div class="mb-3">
            <label class="form-label small">Código distribuidor</label>
            <input type="password"
                   name="codigo"
                   class="form-control"
                   placeholder="Ej: DIST2025"
                   required>
          </div>

          <button class="btn btn-primary w-100 mb-2">
            <i class="bi bi-unlock"></i> Entrar
          </button>

          <a href="index.php" class="btn btn-outline-light w-100 btn-sm">
            Volver al catálogo
          </a>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS (opcional, por si luego usas componentes) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
