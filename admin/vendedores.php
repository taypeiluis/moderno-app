<?php
require_once __DIR__ . '/admin-config.php';

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

$alertMsg  = '';
$alertType = 'danger';

// ----------------------
// ACCIONES: CREAR / ACTUALIZAR / ELIMINAR
// ----------------------

// Eliminar vendedor
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId > 0) {
        // Opcional: evitar borrar algún ID especial
        $stmtDel = $conn->prepare("DELETE FROM vendedores WHERE id = ? LIMIT 1");
        $stmtDel->bind_param("i", $delId);
        if ($stmtDel->execute()) {
            $alertMsg  = 'Vendedor eliminado correctamente.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Error al eliminar vendedor: ' . $stmtDel->error;
            $alertType = 'danger';
        }
        $stmtDel->close();
    }
}

// Crear / actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre    = trim($_POST['nombre'] ?? '');
    $codigo    = trim($_POST['codigo'] ?? '');

    if ($nombre === '' || $codigo === '') {
        $alertMsg  = 'Nombre y código son obligatorios.';
        $alertType = 'danger';
    } else {
        // ¿Estamos editando o creando?
        if ($id > 0) {
            // Actualizar
            $stmt = $conn->prepare("UPDATE vendedores SET nombre = ?, codigo = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nombre, $codigo, $id);

            if ($stmt->execute()) {
                $alertMsg  = 'Vendedor actualizado correctamente.';
                $alertType = 'success';
            } else {
                $alertMsg  = 'Error al actualizar vendedor: ' . $stmt->error;
                $alertType = 'danger';
            }
            $stmt->close();
        } else {
            // Crear nuevo
            $stmt = $conn->prepare("INSERT INTO vendedores (nombre, codigo) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $codigo);

            if ($stmt->execute()) {
                $alertMsg  = 'Vendedor creado correctamente.';
                $alertType = 'success';
            } else {
                $alertMsg  = 'Error al crear vendedor: ' . $stmt->error;
                $alertType = 'danger';
            }
            $stmt->close();
        }
    }
}

// ----------------------
// CARGAR DATOS PARA FORM (EDICIÓN)
// ----------------------
$editId   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = [
    'id'     => 0,
    'nombre' => '',
    'codigo' => ''
];

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT id, nombre, codigo FROM vendedores WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $editData = $res->fetch_assoc();
    }
    $stmt->close();
}

// ----------------------
// LISTA DE VENDEDORES
// ----------------------
$vendedores = [];
$resVend = $conn->query("SELECT id, nombre, codigo FROM vendedores ORDER BY id DESC");
if ($resVend) {
    $vendedores = $resVend->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Vendedores / Distribuidores | Admin</title>
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
    <h3 class="mb-0">Vendedores / Distribuidores</h3>
    <div class="d-flex gap-2">
      <a href="index.php" class="btn btn-sm btn-outline-light">
        ← Volver al panel
      </a>
    </div>
  </div>

  <?php if (!empty($alertMsg)): ?>
    <div class="alert alert-<?= e($alertType) ?> py-2">
      <?= e($alertMsg) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Formulario de creación / edición -->
    <div class="col-md-4">
      <div class="card glass p-3 text-light">
        <h5 class="mb-2">
          <?= $editData['id'] ? 'Editar vendedor' : 'Nuevo vendedor' ?>
        </h5>
        <p class="small text-muted mb-3">
          El código se usa como clave de acceso en el modo distribuidor (mayorista).
        </p>

        <form method="post" autocomplete="off">
          <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">

          <div class="mb-3">
            <label class="form-label small">Nombre</label>
            <input type="text"
                   name="nombre"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= e($editData['nombre']) ?>"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label small">Código distribuidor</label>
            <input type="text"
                   name="codigo"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= e($editData['codigo']) ?>"
                   required>
            <div class="form-text text-muted small">
              Este código es el que el distribuidor ingresa en <strong>Acceso distribuidor</strong>.
            </div>
          </div>

          <button class="btn btn-primary w-100">
            <i class="bi bi-save"></i>
            <?= $editData['id'] ? 'Guardar cambios' : 'Crear vendedor' ?>
          </button>

          <?php if ($editData['id']): ?>
            <a href="vendedores.php" class="btn btn-outline-light btn-sm w-100 mt-2">
              Cancelar edición
            </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Tabla de vendedores -->
    <div class="col-md-8">
      <div class="card glass p-3 text-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Listado de vendedores</h5>
        </div>

        <?php if (empty($vendedores)): ?>
          <p class="small text-muted mb-0">
            No hay vendedores registrados todavía.
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-dark table-striped table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:60px;">ID</th>
                  <th>Nombre</th>
                  <th>Código distribuidor</th>
                  <th style="width:150px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($vendedores as $v): ?>
                  <tr>
                    <td><?= (int)$v['id'] ?></td>
                    <td><?= e($v['nombre']) ?></td>
                    <td>
                      <code><?= e($v['codigo']) ?></code>
                    </td>
                    <td class="text-end">
                      <a href="vendedores.php?edit=<?= (int)$v['id'] ?>"
                         class="btn btn-sm btn-outline-info">
                        <i class="bi bi-pencil"></i> Editar
                      </a>
                      <a href="vendedores.php?delete=<?= (int)$v['id'] ?>"
                         class="btn btn-sm btn-outline-danger ms-1"
                         onclick="return confirm('¿Seguro que deseas eliminar este vendedor?');">
                        <i class="bi bi-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
