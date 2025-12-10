<?php
require_once __DIR__ . '/admin-config.php';

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

// ----------------------
// ACCIÓN: EXPORTAR CSV PLANTILLA
// ----------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'export') {
    // Consultar todos los productos
    $sqlExport = "
        SELECT id, nombre, descripcion, precio_unitario, precio_mayor, imagen, categoria_id
        FROM productos
        ORDER BY id ASC
    ";
    $resExport = $conn->query($sqlExport);

    // Encabezados HTTP para forzar descarga
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="productos_plantilla_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');

    // Fila de cabecera
    fputcsv($output, [
        'id',
        'nombre',
        'descripcion',
        'precio_unitario',
        'precio_mayor',
        'imagen',
        'categoria_id'
    ]);

    if ($resExport) {
        while ($row = $resExport->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['nombre'],
                $row['descripcion'],
                $row['precio_unitario'],
                $row['precio_mayor'],
                $row['imagen'],
                $row['categoria_id']
            ]);
        }
    }

    fclose($output);
    exit;
}

$alertMsg  = '';
$alertType = 'danger';

// ----------------------
// MENSAJES DESDE OTRAS PÁGINAS (?msg=...)
// ----------------------
if (empty($_POST) && !empty($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'eliminado':
            $alertMsg  = 'Producto eliminado correctamente.';
            $alertType = 'success';
            break;
        case 'error_id':
            $alertMsg  = 'ID de producto no válido.';
            $alertType = 'danger';
            break;
        case 'no_encontrado':
            $alertMsg  = 'El producto seleccionado no existe.';
            $alertType = 'danger';
            break;
    }
}

// ----------------------
// CARGA MASIVA DESDE EXCEL/CSV
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accion'])
    && $_POST['accion'] === 'bulk_upload') {

    if (empty($_FILES['archivo_excel']['name']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
        $alertMsg  = 'Debes seleccionar un archivo CSV válido.';
        $alertType = 'danger';
    } else {
        $tmpName    = $_FILES['archivo_excel']['tmp_name'];
        $origName   = $_FILES['archivo_excel']['name'];
        $extension  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Solo permitimos CSV
        if ($extension !== 'csv') {
            $alertMsg  = 'Por seguridad, solo se permite subir archivos CSV. En Excel, guarda como: "CSV (delimitado por comas)".';
            $alertType = 'danger';
        } else {
            $insertados   = 0;
            $actualizados = 0;
            $saltados     = 0;
            $errores      = 0;
            $primerError  = '';

            $fh = fopen($tmpName, 'r');
            if ($fh === false) {
                $alertMsg  = 'No se pudo leer el archivo subido.';
                $alertType = 'danger';
            } else {
                $headerLine = fgets($fh);
                if ($headerLine === false) {
                    $alertMsg  = 'El archivo CSV está vacío.';
                    $alertType = 'danger';
                } else {
                    // Detectar delimitador
                    $delimiter = ',';
                    if (substr_count($headerLine, ';') > substr_count($headerLine, ',')) {
                        $delimiter = ';';
                    }

                    $headers = str_getcsv($headerLine, $delimiter);
                    $map = [];

                    foreach ($headers as $idx => $col) {
                        $key = strtolower(trim($col));
                        if (in_array($key, [
                            'id',
                            'nombre',
                            'descripcion',
                            'precio_unitario',
                            'precio_mayor',
                            'imagen',
                            'categoria_id'
                        ], true)) {
                            $map[$key] = $idx;
                        }
                    }

                    if (!isset($map['nombre']) || !isset($map['precio_unitario'])) {
                        $alertMsg  = 'El CSV debe tener al menos las columnas: "nombre" y "precio_unitario". Opcionales: id, descripcion, precio_mayor, imagen, categoria_id.';
                        $alertType = 'danger';
                    } else {
                        // Recorrer filas
                        while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
                            // Saltar filas totalmente vacías
                            $emptyRow = true;
                            foreach ($row as $val) {
                                if (trim($val) !== '') {
                                    $emptyRow = false;
                                    break;
                                }
                            }
                            if ($emptyRow) {
                                continue;
                            }

                            $id          = isset($map['id']) ? trim($row[$map['id']] ?? '') : '';
                            $nombre      = trim($row[$map['nombre']] ?? '');
                            $descripcion = isset($map['descripcion']) ? trim($row[$map['descripcion']] ?? '') : '';
                            $precioU     = trim($row[$map['precio_unitario']] ?? '');
                            $precioM     = isset($map['precio_mayor']) ? trim($row[$map['precio_mayor']] ?? '') : '';
                            $imagen      = isset($map['imagen']) ? trim($row[$map['imagen']] ?? '') : '';
                            $categoriaId = isset($map['categoria_id']) ? trim($row[$map['categoria_id']] ?? '') : '';

                            // Validación mínima
                            if ($nombre === '' || $precioU === '') {
                                $saltados++;
                                continue;
                            }

                            $precioU = (float)str_replace(',', '.', $precioU);
                            $precioM = $precioM !== '' ? (float)str_replace(',', '.', $precioM) : 0.0;
                            $categoriaId = $categoriaId !== '' ? (int)$categoriaId : null;
                            $categoriaParam = $categoriaId; // puede ser null o int

                            // ¿hay ID y es > 0?
                            if ($id !== '' && (int)$id > 0) {
                                $idInt = (int)$id;

                                // Verificar si existe en BD
                                $stmtChk = $conn->prepare("SELECT id FROM productos WHERE id = ? LIMIT 1");
                                $stmtChk->bind_param("i", $idInt);
                                $stmtChk->execute();
                                $resChk = $stmtChk->get_result();
                                $existe = $resChk && $resChk->num_rows === 1;
                                $stmtChk->close();

                                if ($existe) {
                                    // UPDATE
                                    $sqlUpd = "
                                        UPDATE productos
                                        SET nombre = ?, descripcion = ?, precio_unitario = ?, precio_mayor = ?, imagen = ?, categoria_id = ?
                                        WHERE id = ?
                                    ";
                                    $stmtUpd = $conn->prepare($sqlUpd);
                                    $stmtUpd->bind_param(
                                        "ssddsii",
                                        $nombre,
                                        $descripcion,
                                        $precioU,
                                        $precioM,
                                        $imagen,
                                        $categoriaParam,
                                        $idInt
                                    );

                                    if ($stmtUpd->execute()) {
                                        $actualizados++;
                                    } else {
                                        $errores++;
                                        if ($primerError === '') {
                                            $primerError = $stmtUpd->error;
                                        }
                                    }
                                    $stmtUpd->close();
                                } else {
                                    // ID viene en el archivo pero no existe en BD → lo tratamos como INSERT (sin forzar ese ID)
                                    $sqlIns = "
                                        INSERT INTO productos (nombre, descripcion, precio_unitario, precio_mayor, imagen, categoria_id)
                                        VALUES (?, ?, ?, ?, ?, ?)
                                    ";
                                    $stmtIns = $conn->prepare($sqlIns);
                                    $stmtIns->bind_param(
                                        "ssddsi",
                                        $nombre,
                                        $descripcion,
                                        $precioU,
                                        $precioM,
                                        $imagen,
                                        $categoriaParam
                                    );

                                    if ($stmtIns->execute()) {
                                        $insertados++;
                                    } else {
                                        $errores++;
                                        if ($primerError === '') {
                                            $primerError = $stmtIns->error;
                                        }
                                    }
                                    $stmtIns->close();
                                }
                            } else {
                                // Sin ID → INSERT
                                $sqlIns = "
                                    INSERT INTO productos (nombre, descripcion, precio_unitario, precio_mayor, imagen, categoria_id)
                                    VALUES (?, ?, ?, ?, ?, ?)
                                ";
                                $stmtIns = $conn->prepare($sqlIns);
                                $stmtIns->bind_param(
                                    "ssddsi",
                                    $nombre,
                                    $descripcion,
                                    $precioU,
                                    $precioM,
                                    $imagen,
                                    $categoriaParam
                                );

                                if ($stmtIns->execute()) {
                                    $insertados++;
                                } else {
                                    $errores++;
                                    if ($primerError === '') {
                                        $primerError = $stmtIns->error;
                                    }
                                }
                                $stmtIns->close();
                            }
                        }

                        fclose($fh);

                        $alertType = $errores > 0 ? 'warning' : 'success';
                        $alertMsg  = "Carga masiva completada. "
                                   . "Nuevos: {$insertados}, Actualizados: {$actualizados}, "
                                   . "Saltados: {$saltados}, Errores: {$errores}.";
                        if ($primerError !== '') {
                            $alertMsg .= ' Primer error: ' . $primerError;
                        }
                    }
                }
            }
        }
    }
}

// ----------------------
// LISTA DE PRODUCTOS
// ----------------------
$productos = [];
$sql = "
    SELECT p.*, c.nombre AS categoria
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    ORDER BY p.id DESC
";
$res = $conn->query($sql);
if ($res) {
    $productos = $res->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Productos | Admin</title>
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
    <h3 class="mb-0">Productos</h3>
    <div class="d-flex gap-2 flex-wrap">
      <a href="index.php" class="btn btn-sm btn-outline-light">
        ← Volver al panel
      </a>
      <a href="productos.php?accion=export" class="btn btn-sm btn-outline-info">
        <i class="bi bi-download"></i> Exportar plantilla CSV
      </a>
      <a href="productos_edit.php?id=0" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle"></i> Nuevo producto
      </a>
    </div>
  </div>

  <?php if (!empty($alertMsg)): ?>
    <div class="alert alert-<?= e($alertType) ?> py-2">
      <?= e($alertMsg) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Carga masiva desde Excel/CSV -->
    <div class="col-md-4">
      <div class="card glass p-3 text-light h-100">
        <h5 class="mb-2">Carga masiva desde Excel/CSV</h5>
        <p class="small text-muted mb-2">
          1. Primero puedes exportar la plantilla con los productos actuales.<br>
          2. Edita en Excel y guarda como CSV.<br>
          3. Luego súbelo aquí para actualizar o agregar productos.
        </p>
        <ul class="small text-muted mb-2">
          <li>Extensión permitida: <strong>.csv</strong></li>
          <li>Cabeceras esperadas:
            <code>id, nombre, descripcion, precio_unitario, precio_mayor, imagen, categoria_id</code>
          </li>
          <li>Si <strong>id existe en BD</strong> → se <strong>actualiza</strong>.</li>
          <li>Si <strong>id va vacío o no existe</strong> → se <strong>crea producto nuevo</strong>.</li>
        </ul>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
          <input type="hidden" name="accion" value="bulk_upload">

          <div class="mb-3">
            <label class="form-label small">Archivo CSV</label>
            <input type="file"
                   name="archivo_excel"
                   class="form-control bg-dark text-light border-secondary"
                   accept=".csv"
                   required>
            <div class="form-text text-muted small">
              En Excel: <em>Archivo → Guardar como → CSV (delimitado por comas)</em>.
            </div>
          </div>

          <button class="btn btn-primary w-100">
            <i class="bi bi-upload"></i> Procesar archivo
          </button>
        </form>
      </div>
    </div>

    <!-- Listado de productos -->
    <div class="col-md-8">
      <div class="card glass p-3 text-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Listado de productos</h5>
          <span class="small text-muted">
            <?= count($productos) ?> producto(s)
          </span>
        </div>

        <?php if (empty($productos)): ?>
          <p class="small text-muted mb-0">
            No hay productos registrados todavía.
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-dark table-striped table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:60px;">ID</th>
                  <th>Nombre</th>
                  <th>Categoría</th>
                  <th>Precio público</th>
                  <th>Precio mayorista</th>
                  <th style="width:160px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($productos as $p): ?>
                  <tr>
                    <td><?= (int)$p['id'] ?></td>
                    <td><?= e($p['nombre']) ?></td>
                    <td><?= e($p['categoria'] ?? '') ?></td>
                    <td>S/ <?= number_format((float)$p['precio_unitario'], 2) ?></td>
                    <td>
                      <?php if (!empty($p['precio_mayor'])): ?>
                        S/ <?= number_format((float)$p['precio_mayor'], 2) ?>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <a href="productos_edit.php?id=<?= (int)$p['id'] ?>"
                         class="btn btn-sm btn-outline-info">
                        <i class="bi bi-pencil"></i> Editar
                      </a>
                      <a href="productos_delete.php?id=<?= (int)$p['id'] ?>"
                         class="btn btn-sm btn-outline-danger ms-1"
                         onclick="return confirm('¿Seguro que deseas eliminar este producto?');">
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
