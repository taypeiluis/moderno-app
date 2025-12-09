<?php
require_once __DIR__ . '/admin-config.php';

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('esURL')) {
    function esURL($cadena) {
        return filter_var($cadena, FILTER_VALIDATE_URL) !== false;
    }
}

// ID del producto
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: productos.php?msg=error_id');
    exit;
}

$msg = '';
$tipoMsg = 'danger';

// Cargar categorías
$categorias = [];
$resCat = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
if ($resCat) {
    $categorias = $resCat->fetch_all(MYSQLI_ASSOC);
}

// Cargar producto actual
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$producto = $res->fetch_assoc();
$stmt->close();

if (!$producto) {
    header('Location: productos.php?msg=no_encontrado');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre        = trim($_POST['nombre'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $categoria_id  = (int)($_POST['categoria_id'] ?? 0);
    $precio_unit   = str_replace(',', '.', $_POST['precio_unitario'] ?? '0');
    $precio_mayor  = str_replace(',', '.', $_POST['precio_mayor'] ?? '0');

    $precio_unit   = (float)$precio_unit;
    $precio_mayor  = (float)$precio_mayor;

    // Validaciones básicas
    if ($nombre === '' || $precio_unit <= 0) {
        $msg = 'Nombre y precio público son obligatorios.';
    } else {
        // Manejo de imagen
        $nuevaImagen = null;

        if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $tmpName  = $_FILES['imagen']['tmp_name'];
            $origName = basename($_FILES['imagen']['name']);
            $ext      = pathinfo($origName, PATHINFO_EXTENSION);

            $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $fileName = time() . '_' . $safeName . ($ext ? '.' . $ext : '');

            $destDir  = __DIR__ . '/../uploads/productos/';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0777, true);
            }

            $destPath = $destDir . $fileName;

            if (move_uploaded_file($tmpName, $destPath)) {
                $nuevaImagen = $fileName;

                // Opcional: borrar imagen anterior si era archivo local
                if (!empty($producto['imagen']) && !esURL($producto['imagen'])) {
                    $oldPath = __DIR__ . '/../uploads/productos/' . $producto['imagen'];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            } else {
                $msg = 'No se pudo subir la nueva imagen.';
            }
        }

        if ($msg === '') {
            if ($nuevaImagen) {
                $sql = "UPDATE productos 
                        SET nombre = ?, descripcion = ?, categoria_id = ?, 
                            precio_unitario = ?, precio_mayor = ?, imagen = ?
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssiddsi",
                    $nombre,
                    $descripcion,
                    $categoria_id,
                    $precio_unit,
                    $precio_mayor,
                    $nuevaImagen,
                    $id
                );
            } else {
                $sql = "UPDATE productos 
                        SET nombre = ?, descripcion = ?, categoria_id = ?, 
                            precio_unitario = ?, precio_mayor = ?
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssiddi",
                    $nombre,
                    $descripcion,
                    $categoria_id,
                    $precio_unit,
                    $precio_mayor,
                    $id
                );
            }

            if ($stmt->execute()) {
                $tipoMsg = 'success';
                $msg = 'Producto actualizado correctamente.';

                // Recargar datos
                $stmtProd = $conn->prepare("SELECT * FROM productos WHERE id = ? LIMIT 1");
                $stmtProd->bind_param("i", $id);
                $stmtProd->execute();
                $resProd = $stmtProd->get_result();
                $producto = $resProd->fetch_assoc();
                $stmtProd->close();
            } else {
                $msg = 'Error al actualizar el producto: ' . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// Resolver imagen actual
$imgSrc = null;
if (!empty($producto['imagen'])) {
    if (esURL($producto['imagen'])) {
        $imgSrc = $producto['imagen'];
    } elseif (is_file(__DIR__ . '/../uploads/productos/' . $producto['imagen'])) {
        $imgSrc = '../uploads/productos/' . $producto['imagen'];
    } elseif (is_file(__DIR__ . '/../uploads/' . $producto['imagen'])) {
        $imgSrc = '../uploads/' . $producto['imagen'];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar producto | Admin</title>
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
    <h3 class="mb-0">Editar producto</h3>
    <div>
      <a href="productos.php" class="btn btn-sm btn-outline-light">
        ← Volver a productos
      </a>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $tipoMsg ?> py-2">
      <?= e($msg) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card glass p-3 text-light">
        <h6 class="mb-3">Imagen actual</h6>
        <?php if ($imgSrc): ?>
          <div class="product-thumb-wrapper mb-3">
            <img src="<?= e($imgSrc) ?>" alt="Imagen actual" class="product-thumb-img">
          </div>
        <?php else: ?>
          <div class="border rounded p-3 text-center small text-muted">
            Sin imagen cargada.
          </div>
        <?php endif; ?>

        <p class="small text-muted mb-1">Subir nueva imagen (opcional):</p>
        <p class="small text-muted mb-0">
          La imagen se guardará en <code>uploads/productos/</code>.
        </p>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card glass p-3 text-light">
        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text"
                   name="nombre"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= e($producto['nombre']) ?>"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Categoría</label>
            <select name="categoria_id"
                    class="form-select bg-dark text-light border-secondary">
              <option value="0">Sin categoría</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                  <?php if ((int)$producto['categoria_id'] === (int)$cat['id']) echo 'selected'; ?>>
                  <?= e($cat['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Precio público (S/.)</label>
              <input type="number"
                     step="0.01"
                     min="0"
                     name="precio_unitario"
                     class="form-control bg-dark text-light border-secondary"
                     value="<?= e($producto['precio_unitario']) ?>"
                     required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Precio distribuidor (S/.)</label>
              <input type="number"
                     step="0.01"
                     min="0"
                     name="precio_mayor"
                     class="form-control bg-dark text-light border-secondary"
                     value="<?= e($producto['precio_mayor']) ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion"
                      rows="4"
                      class="form-control bg-dark text-light border-secondary"><?= e($producto['descripcion']) ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Nueva imagen (opcional)</label>
            <input type="file"
                   name="imagen"
                   class="form-control bg-dark text-light border-secondary"
                   accept="image/*">
          </div>

          <button class="btn btn-primary">
            <i class="bi bi-save"></i> Guardar cambios
          </button>
          <a href="productos_delete.php?id=<?= $producto['id'] ?>"
             class="btn btn-outline-danger ms-2"
             onclick="return confirm('¿Seguro que deseas eliminar este producto?');">
            <i class="bi bi-trash"></i> Eliminar
          </a>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
