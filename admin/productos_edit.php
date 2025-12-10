<?php
require_once __DIR__ . '/admin-config.php';

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esURL')) {
    function esURL($cadena) {
        return filter_var($cadena, FILTER_VALIDATE_URL) !== false;
    }
}

$alertMsg  = '';
$alertType = 'danger';

// ID desde GET (para mostrar el formulario)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ----------------------
// CARGAR CATEGORÍAS
// ----------------------
$categorias = [];
$resCat = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
if ($resCat) {
    $categorias = $resCat->fetch_all(MYSQLI_ASSOC);
}

// ----------------------
// DATOS BASE DEL PRODUCTO (por defecto = nuevo)
// ----------------------
$producto = [
    'id'             => 0,
    'nombre'         => '',
    'descripcion'    => '',
    'precio_unitario'=> '',
    'precio_mayor'   => '',
    'imagen'         => '',
    'categoria_id'   => 0,
];

// ----------------------
// SI ES EDICIÓN (GET con id>0), CARGAR DATOS
// ----------------------
if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT id, nombre, descripcion, precio_unitario, precio_mayor, imagen, categoria_id
        FROM productos
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $producto = $res->fetch_assoc();
        // Formato amigable para inputs
        $producto['precio_unitario'] = (float)$producto['precio_unitario'];
        $producto['precio_mayor']    = (float)$producto['precio_mayor'];
    } else {
        // No existe el producto → volver a la lista
        $stmt->close();
        header('Location: productos.php?msg=no_encontrado');
        exit;
    }
    $stmt->close();
}

// ----------------------
// PROCESAR FORMULARIO (CREAR / EDITAR)
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre        = trim($_POST['nombre'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $precioU       = trim($_POST['precio_unitario'] ?? '');
    $precioM       = trim($_POST['precio_mayor'] ?? '');
    $categoriaId   = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    $imagenUrlForm = trim($_POST['imagen_url'] ?? '');

    // Validaciones básicas
    if ($nombre === '' || $precioU === '') {
        $alertMsg  = 'El nombre y el precio público son obligatorios.';
        $alertType = 'danger';
    } else {
        // Normalizar precios (permitir coma o punto)
        $precioU = (float)str_replace(',', '.', $precioU);
        $precioM = $precioM !== '' ? (float)str_replace(',', '.', $precioM) : 0.0;

        // Si categoría es 0, la dejamos como NULL
        $categoriaParam = $categoriaId > 0 ? $categoriaId : null;

        // Si estamos editando, cargamos el producto actual (para recuperar imagen previa)
        $imagenActual = '';
        if ($idPost > 0) {
            $stmt = $conn->prepare("
                SELECT imagen
                FROM productos
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $idPost);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $fila = $res->fetch_assoc();
                $imagenActual = $fila['imagen'];
            }
            $stmt->close();
        }

        $imagenFinal = $imagenActual;

        // ----------------------
        // MANEJO DE IMAGEN (ARCHIVO Y/O URL)
        // ----------------------
        $hayArchivo = !empty($_FILES['imagen_file']['name']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK;
        $hayUrl     = ($imagenUrlForm !== '');

        if ($hayArchivo) {
            $tmpName  = $_FILES['imagen_file']['tmp_name'];
            $origName = basename($_FILES['imagen_file']['name']);
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            // Validar tipo básico (jpg, jpeg, png, webp)
            $allowedExt = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowedExt, true)) {
                $alertMsg  = 'Formato de imagen no permitido. Usa JPG, JPEG, PNG o WEBP.';
                $alertType = 'danger';
            } else {
                $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($origName, PATHINFO_FILENAME));
                $fileName = time() . '_' . $safeName . '.' . $ext;

                $destDir = __DIR__ . '/../uploads/productos/';
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0777, true);
                }

                $destPath = $destDir . $fileName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    // Borrar imagen anterior si era local
                    if (!empty($imagenActual) && !esURL($imagenActual)) {
                        $old1 = __DIR__ . '/../uploads/productos/' . $imagenActual;
                        $old2 = __DIR__ . '/../uploads/' . $imagenActual;
                        if (is_file($old1)) @unlink($old1);
                        elseif (is_file($old2)) @unlink($old2);
                    }
                    $imagenFinal = $fileName;
                } else {
                    $alertMsg  = 'No se pudo guardar la imagen subida.';
                    $alertType = 'danger';
                }
            }
        } elseif ($hayUrl) {
            // No hay archivo, pero sí URL escrita → usar URL tal cual
            $imagenFinal = $imagenUrlForm;
        }
        // Si no hay ni archivo ni URL y $imagenActual tenía algo, se conserva.

        if ($alertMsg === '') {
            // ----------------------
            // INSERT (nuevo) o UPDATE (edición)
            // ----------------------
            if ($idPost > 0) {
                // UPDATE
                $sql = "
                    UPDATE productos
                    SET nombre = ?, descripcion = ?, precio_unitario = ?, precio_mayor = ?, imagen = ?, categoria_id = ?
                    WHERE id = ?
                ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssddsii",
                    $nombre,
                    $descripcion,
                    $precioU,
                    $precioM,
                    $imagenFinal,
                    $categoriaParam,
                    $idPost
                );
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: productos.php?msg=actualizado');
                    exit;
                } else {
                    $alertMsg  = 'Error al actualizar el producto: ' . $stmt->error;
                    $alertType = 'danger';
                    $stmt->close();
                }
            } else {
                // INSERT NUEVO
                $sql = "
                    INSERT INTO productos (nombre, descripcion, precio_unitario, precio_mayor, imagen, categoria_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssddsi",
                    $nombre,
                    $descripcion,
                    $precioU,
                    $precioM,
                    $imagenFinal,
                    $categoriaParam
                );
                if ($stmt->execute()) {
                    $nuevoId = $stmt->insert_id;
                    $stmt->close();
                    header('Location: productos.php?msg=creado');
                    exit;
                } else {
                    $alertMsg  = 'Error al crear el producto: ' . $stmt->error;
                    $alertType = 'danger';
                    $stmt->close();
                }
            }
        }

        // Si hubo error, recargamos $producto con lo que el usuario envió
        $producto = [
            'id'             => $idPost,
            'nombre'         => $nombre,
            'descripcion'    => $descripcion,
            'precio_unitario'=> $precioU,
            'precio_mayor'   => $precioM,
            'imagen'         => $imagenFinal,
            'categoria_id'   => $categoriaId,
        ];
        $id = $idPost; // para el HTML
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= $producto['id'] ? 'Editar producto' : 'Nuevo producto' ?> | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/style_moderno.css">
</head>
<body class="bg-dark text-light">

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 class="mb-0">
      <?= $producto['id'] ? 'Editar producto' : 'Nuevo producto' ?>
    </h3>
    <div class="d-flex gap-2">
      <a href="productos.php" class="btn btn-sm btn-outline-light">
        ← Volver a productos
      </a>
    </div>
  </div>

  <?php if (!empty($alertMsg)): ?>
    <div class="alert alert-<?= e($alertType) ?> py-2">
      <?= e($alertMsg) ?>
    </div>
  <?php endif; ?>

  <div class="card glass p-3 text-light">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
      <input type="hidden" name="id" value="<?= (int)$producto['id'] ?>">

      <div class="row g-3">
        <div class="col-md-8">
          <div class="mb-3">
            <label class="form-label small">Nombre del producto</label>
            <input type="text"
                   name="nombre"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= e($producto['nombre']) ?>"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label small">Descripción</label>
            <textarea name="descripcion"
                      rows="4"
                      class="form-control bg-dark text-light border-secondary"><?= e($producto['descripcion']) ?></textarea>
          </div>

          <div class="row g-2">
            <div class="col-sm-6">
              <label class="form-label small">Precio público (S/)</label>
              <input type="text"
                     name="precio_unitario"
                     class="form-control bg-dark text-light border-secondary"
                     value="<?= e($producto['precio_unitario']) ?>"
                     required>
            </div>
            <div class="col-sm-6">
              <label class="form-label small">Precio mayorista (S/)</label>
              <input type="text"
                     name="precio_mayor"
                     class="form-control bg-dark text-light border-secondary"
                     value="<?= e($producto['precio_mayor']) ?>">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label small">Categoría</label>
            <select name="categoria_id"
                    class="form-select bg-dark text-light border-secondary">
              <option value="0">Sin categoría</option>
              <?php foreach ($categorias as $c): ?>
                <option value="<?= (int)$c['id'] ?>"
                  <?= ((int)$producto['categoria_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                  <?= e($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col-md-4">
          <!-- Preview de imagen -->
          <div class="mb-3">
            <label class="form-label small">Imagen actual</label>
            <div class="border rounded-3 bg-black p-2 d-flex align-items-center justify-content-center" style="min-height:150px;">
              <?php
              $imgPreview = '';
              if (!empty($producto['imagen'])) {
                  if (esURL($producto['imagen'])) {
                      $imgPreview = $producto['imagen'];
                  } elseif (is_file(__DIR__ . '/../uploads/productos/' . $producto['imagen'])) {
                      $imgPreview = '../uploads/productos/' . $producto['imagen'];
                  } elseif (is_file(__DIR__ . '/../uploads/' . $producto['imagen'])) {
                      $imgPreview = '../uploads/' . $producto['imagen'];
                  }
              }
              ?>
              <?php if ($imgPreview): ?>
                <img src="<?= e($imgPreview) ?>"
                     alt="Imagen producto"
                     style="max-width:100%;max-height:180px;object-fit:contain;">
              <?php else: ?>
                <span class="small text-muted">
                  Sin imagen
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small">Subir nueva imagen (archivo)</label>
            <input type="file"
                   name="imagen_file"
                   class="form-control bg-dark text-light border-secondary"
                   accept="image/*">
            <div class="form-text text-muted small">
              Si subes una nueva imagen, reemplaza la anterior.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small">o URL de imagen (opcional)</label>
            <input type="text"
                   name="imagen_url"
                   class="form-control bg-dark text-light border-secondary"
                   placeholder="https://..."
                   value="<?= e(esURL($producto['imagen'] ?? '') ? $producto['imagen'] : '') ?>">
            <div class="form-text text-muted small">
              Si no subes un archivo pero pones una URL, se usará esa imagen externa.
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3 d-flex justify-content-end gap-2">
        <a href="productos.php" class="btn btn-outline-light">
          Cancelar
        </a>
        <button class="btn btn-primary">
          <i class="bi bi-save"></i>
          <?= $producto['id'] ? 'Guardar cambios' : 'Crear producto' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
