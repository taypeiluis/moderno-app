<?php
require_once __DIR__ . '/admin-config.php';

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esURL')) {
    function esURL($cadena) {
        return filter_var($cadena, FILTER_VALIDATE_URL) !== false;
    }
}

$alertMsg  = '';
$alertType = 'danger';

// Mensajes que vienen por GET (?msg=...)
if (empty($_POST) && !empty($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'eliminado':
            $alertMsg  = 'Banner eliminado correctamente.';
            $alertType = 'success';
            break;
        case 'error_id':
            $alertMsg  = 'ID de banner no válido.';
            $alertType = 'danger';
            break;
        case 'no_encontrado':
            $alertMsg  = 'El banner seleccionado no existe.';
            $alertType = 'danger';
            break;
    }
}

// ----------------------
// CREAR / ACTUALIZAR BANNER
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $enlace      = trim($_POST['enlace'] ?? '');
    $orden       = (int)($_POST['orden'] ?? 1);
    $imagenUrl   = trim($_POST['imagen_url'] ?? '');

    if ($titulo === '') {
        $alertMsg  = 'El título del banner es obligatorio.';
        $alertType = 'danger';
    } else {
        // Cargar banner actual si estamos editando
        $bannerActual = null;
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $bannerActual = $res->fetch_assoc();
            $stmt->close();
        }

        $imagenFinal = $bannerActual['imagen'] ?? null;

        // 1) Si suben archivo, tiene prioridad
        if (!empty($_FILES['imagen_file']['name']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            $tmpName  = $_FILES['imagen_file']['tmp_name'];
            $origName = basename($_FILES['imagen_file']['name']);
            $ext      = pathinfo($origName, PATHINFO_EXTENSION);

            $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $fileName = time() . '_' . $safeName . ($ext ? '.' . $ext : '');

            $destDir = __DIR__ . '/../uploads/banners/';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0777, true);
            }

            $destPath = $destDir . $fileName;

            if (move_uploaded_file($tmpName, $destPath)) {
                // Borrar imagen anterior si era archivo local
                if (!empty($imagenFinal) && !esURL($imagenFinal)) {
                    $old1 = __DIR__ . '/../uploads/banners/' . $imagenFinal;
                    $old2 = __DIR__ . '/../uploads/' . $imagenFinal;
                    if (is_file($old1)) @unlink($old1);
                    elseif (is_file($old2)) @unlink($old2);
                }
                $imagenFinal = $fileName;
            } else {
                $alertMsg  = 'No se pudo subir la imagen del banner.';
                $alertType = 'danger';
            }

        // 2) Si NO suben archivo pero sí escriben URL, la usamos
        } elseif ($imagenUrl !== '') {
            $imagenFinal = $imagenUrl;
        }
        // 3) Si no hay ni archivo ni URL, se deja la que ya estaba

        if ($alertMsg === '') {
            if ($id > 0) {
                // UPDATE
                $stmt = $conn->prepare("
                    UPDATE banners
                    SET titulo = ?, descripcion = ?, imagen = ?, enlace = ?, orden = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "ssssii",
                    $titulo,
                    $descripcion,
                    $imagenFinal,
                    $enlace,
                    $orden,
                    $id
                );
                if ($stmt->execute()) {
                    $alertMsg  = 'Banner actualizado correctamente.';
                    $alertType = 'success';
                } else {
                    $alertMsg  = 'Error al actualizar el banner: ' . $stmt->error;
                    $alertType = 'danger';
                }
                $stmt->close();
            } else {
                // INSERT
                $stmt = $conn->prepare("
                    INSERT INTO banners (titulo, descripcion, imagen, enlace, orden)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "ssssi",
                    $titulo,
                    $descripcion,
                    $imagenFinal,
                    $enlace,
                    $orden
                );
                if ($stmt->execute()) {
                    $alertMsg  = 'Banner creado correctamente.';
                    $alertType = 'success';
                } else {
                    $alertMsg  = 'Error al crear el banner: ' . $stmt->error;
                    $alertType = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// ----------------------
// CARGAR DATOS PARA FORM (EDICIÓN)
// ----------------------
$editId   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = [
    'id'          => 0,
    'titulo'      => '',
    'descripcion' => '',
    'imagen'      => '',
    'enlace'      => '',
    'orden'       => 1,
];

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $editData = $res->fetch_assoc();
    } else {
        $alertMsg  = 'El banner que intentas editar no existe.';
        $alertType = 'danger';
    }
    $stmt->close();
}

// Resolver imagen para preview
$editImgSrc = null;
if (!empty($editData['imagen'])) {
    if (esURL($editData['imagen'])) {
        $editImgSrc = $editData['imagen'];
    } elseif (is_file(__DIR__ . '/../uploads/banners/' . $editData['imagen'])) {
        $editImgSrc = '../uploads/banners/' . $editData['imagen'];
    } elseif (is_file(__DIR__ . '/../uploads/' . $editData['imagen'])) {
        $editImgSrc = '../uploads/' . $editData['imagen'];
    }
}

// ----------------------
// LISTA DE BANNERS
// ----------------------
$banners = [];
$resBan = $conn->query("SELECT * FROM banners ORDER BY orden ASC, id DESC");
if ($resBan) {
    $banners = $resBan->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Banners / Carrusel | Admin</title>
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
    <h3 class="mb-0">Banners / Carrusel</h3>
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
    <!-- Formulario crear/editar -->
    <div class="col-md-4">
      <div class="card glass p-3 text-light">
        <h5 class="mb-2">
          <?= $editData['id'] ? 'Editar banner' : 'Nuevo banner' ?>
        </h5>
        <p class="small text-muted mb-3">
          Estos banners se muestran en el carrusel principal de la tienda.
        </p>

        <?php if ($editImgSrc): ?>
          <div class="mb-3">
            <div style="width:100%;aspect-ratio:16/9;overflow:hidden;border-radius:10px;">
              <img src="<?= e($editImgSrc) ?>"
                   alt="Banner actual"
                   style="width:100%;height:100%;object-fit:cover;">
            </div>
            <p class="small text-muted mt-1 mb-0">Imagen actual</p>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
          <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">

          <div class="mb-3">
            <label class="form-label small">Título</label>
            <input type="text"
                   name="titulo"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= e($editData['titulo']) ?>"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label small">Descripción</label>
            <textarea name="descripcion"
                      rows="2"
                      class="form-control bg-dark text-light border-secondary"><?= e($editData['descripcion']) ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label small">Enlace (opcional)</label>
            <input type="text"
                   name="enlace"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= e($editData['enlace']) ?>"
                   placeholder="https://tutienda.com/oferta">
            <div class="form-text text-muted small">
              Si lo completas, el banner será clickeable y llevará a esa URL.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small">Orden</label>
            <input type="number"
                   name="orden"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= (int)$editData['orden'] ?>">
            <div class="form-text text-muted small">
              Menor número = se muestra primero en el carrusel.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small">Imagen (archivo)</label>
            <input type="file"
                   name="imagen_file"
                   class="form-control bg-dark text-light border-secondary"
                   accept="image/*">
            <div class="form-text text-muted small">
              Si subes un archivo, reemplazará la imagen actual.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small">Imagen desde URL (opcional)</label>
            <input type="text"
                   name="imagen_url"
                   class="form-control bg-dark text-light border-secondary"
                   placeholder="https://images.pexels.com/....jpeg">
            <div class="form-text text-muted small">
              Si no subes archivo pero pones una URL, se usará esa imagen externa.
            </div>
          </div>

          <button class="btn btn-primary w-100">
            <i class="bi bi-save"></i>
            <?= $editData['id'] ? 'Guardar cambios' : 'Crear banner' ?>
          </button>

          <?php if ($editData['id']): ?>
            <a href="banners.php" class="btn btn-outline-light btn-sm w-100 mt-2">
              Cancelar edición
            </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Listado de banners -->
    <div class="col-md-8">
      <div class="card glass p-3 text-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Listado de banners</h5>
        </div>

        <?php if (empty($banners)): ?>
          <p class="small text-muted mb-0">
            No hay banners configurados todavía.
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-dark table-striped table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:60px;">ID</th>
                  <th>Título</th>
                  <th>Orden</th>
                  <th>Enlace</th>
                  <th>Preview</th>
                  <th style="width:180px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($banners as $b): ?>
                  <?php
                  $imgSrc = null;
                  if (!empty($b['imagen'])) {
                      if (esURL($b['imagen'])) {
                          $imgSrc = $b['imagen'];
                      } elseif (is_file(__DIR__ . '/../uploads/banners/' . $b['imagen'])) {
                          $imgSrc = '../uploads/banners/' . $b['imagen'];
                      } elseif (is_file(__DIR__ . '/../uploads/' . $b['imagen'])) {
                          $imgSrc = '../uploads/' . $b['imagen'];
                      }
                  }
                  ?>
                  <tr>
                    <td><?= (int)$b['id'] ?></td>
                    <td><?= e($b['titulo']) ?></td>
                    <td><?= (int)$b['orden'] ?></td>
                    <td class="small">
                      <?php if (!empty($b['enlace'])): ?>
                        <a href="<?= e($b['enlace']) ?>" target="_blank" class="text-info text-decoration-none">
                          Ver enlace
                        </a>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($imgSrc): ?>
                        <div style="width:120px;aspect-ratio:16/9;overflow:hidden;border-radius:6px;">
                          <img src="<?= e($imgSrc) ?>"
                               alt="Banner"
                               style="width:100%;height:100%;object-fit:cover;">
                        </div>
                      <?php else: ?>
                        <span class="small text-muted">Sin imagen</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <a href="banners.php?edit=<?= (int)$b['id'] ?>"
                         class="btn btn-sm btn-outline-info">
                        <i class="bi bi-pencil"></i> Editar
                      </a>
                      <a href="banners_delete.php?id=<?= (int)$b['id'] ?>"
                         class="btn btn-sm btn-outline-danger ms-1"
                         onclick="return confirm('¿Seguro que deseas eliminar este banner?');">
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
