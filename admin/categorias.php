<?php
require_once __DIR__ . '/admin-config.php';

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

$alertMsg  = '';
$alertType = 'danger';

// Mensajes que vienen por GET (por ejemplo, después de borrar)
if (empty($_POST) && !empty($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'eliminada':
            $alertMsg  = 'Categoría eliminada correctamente.';
            $alertType = 'success';
            break;
        case 'error_id':
            $alertMsg  = 'ID de categoría no válido.';
            $alertType = 'danger';
            break;
        case 'no_encontrada':
            $alertMsg  = 'La categoría seleccionada no existe.';
            $alertType = 'danger';
            break;
    }
}

// ----------------------
// CREAR / ACTUALIZAR CATEGORÍA
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = trim($_POST['nombre'] ?? '');

    if ($nombre === '') {
        $alertMsg  = 'El nombre de la categoría es obligatorio.';
        $alertType = 'danger';
    } else {
        if ($id > 0) {
            // Actualizar
            $stmt = $conn->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
            $stmt->bind_param("si", $nombre, $id);
            if ($stmt->execute()) {
                $alertMsg  = 'Categoría actualizada correctamente.';
                $alertType = 'success';
            } else {
                $alertMsg  = 'Error al actualizar la categoría: ' . $stmt->error;
                $alertType = 'danger';
            }
            $stmt->close();
        } else {
            // Crear nueva
            $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmt->bind_param("s", $nombre);
            if ($stmt->execute()) {
                $alertMsg  = 'Categoría creada correctamente.';
                $alertType = 'success';
            } else {
                $alertMsg  = 'Error al crear la categoría: ' . $stmt->error;
                $alertType = 'danger';
            }
            $stmt->close();
        }
    }
}

// ----------------------
// CARGAR DATOS PARA FORMULARIO (EDICIÓN)
// ----------------------
$editId   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = [
    'id'     => 0,
    'nombre' => ''
];

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT id, nombre FROM categorias WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $editData = $res->fetch_assoc();
    } else {
        // Si no existe, solo mostramos mensaje luego si quieres
        // Pero no redirigimos para permitir ver la lista igual.
        $alertMsg  = 'La categoría que intentas editar no existe.';
        $alertType = 'danger';
    }
    $stmt->close();
}

// ----------------------
// LISTA DE CATEGORÍAS
// ----------------------
$categorias = [];
$resCat = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
if ($resCat) {
    $categorias = $resCat->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Categorías | Admin</title>
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
    <h3 class="mb-0">Categorías</h3>
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
    <!-- Formulario nueva/editar categoría -->
    <div class="col-md-4">
      <div class="card glass p-3 text-light">
        <h5 class="mb-2">
          <?= $editData['id'] ? 'Editar categoría' : 'Nueva categoría' ?>
        </h5>
        <p class="small text-muted mb-3">
          Las categorías se usan para agrupar productos en el catálogo.
        </p>

        <form method="post" autocomplete="off">
          <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">

          <div class="mb-3">
            <label class="form-label small">Nombre de la categoría</label>
            <input type="text"
                   name="nombre"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= e($editData['nombre']) ?>"
                   required>
          </div>

          <button class="btn btn-primary w-100">
            <i class="bi bi-save"></i>
            <?= $editData['id'] ? 'Guardar cambios' : 'Crear categoría' ?>
          </button>

          <?php if ($editData['id']): ?>
            <a href="categorias.php" class="btn btn-outline-light btn-sm w-100 mt-2">
              Cancelar edición
            </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Listado de categorías -->
    <div class="col-md-8">
      <div class="card glass p-3 text-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Listado de categorías</h5>
        </div>

        <?php if (empty($categorias)): ?>
          <p class="small text-muted mb-0">
            Todavía no hay categorías registradas.
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-dark table-striped table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:60px;">ID</th>
                  <th>Nombre</th>
                  <th style="width:160px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($categorias as $c): ?>
                  <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td><?= e($c['nombre']) ?></td>
                    <td class="text-end">
                      <a href="categorias.php?edit=<?= (int)$c['id'] ?>"
                         class="btn btn-sm btn-outline-info">
                        <i class="bi bi-pencil"></i> Editar
                      </a>
                      <a href="categorias_delete.php?id=<?= (int)$c['id'] ?>"
                         class="btn btn-sm btn-outline-danger ms-1"
                         onclick="return confirm('¿Seguro que deseas eliminar esta categoría? Los productos seguirán existiendo, pero sin esta categoría.');">
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
