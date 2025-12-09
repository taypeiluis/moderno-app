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
// ACCIÓN: ELIMINAR USUARIO
// ----------------------
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];

    if ($delId <= 0) {
        $alertMsg  = 'ID de usuario no válido.';
        $alertType = 'danger';
    } else {
        // Evitar que el admin se borre a sí mismo
        if (!empty($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] === $delId) {
            $alertMsg  = 'No puedes eliminar la cuenta con la que estás logueado.';
            $alertType = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM admin_usuarios WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $delId);
            if ($stmt->execute()) {
                $alertMsg  = 'Usuario administrador eliminado correctamente.';
                $alertType = 'success';
            } else {
                $alertMsg  = 'Error al eliminar usuario: ' . $stmt->error;
                $alertType = 'danger';
            }
            $stmt->close();
        }
    }
}

// ----------------------
// ACCIÓN: CREAR / EDITAR USUARIO
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $usuario = trim($_POST['usuario'] ?? '');
    $rol     = trim($_POST['rol'] ?? 'admin');
    $clave   = trim($_POST['clave'] ?? '');

    if ($usuario === '') {
        $alertMsg  = 'El nombre de usuario es obligatorio.';
        $alertType = 'danger';
    } elseif ($id === 0 && $clave === '') {
        // Al crear nuevo, clave obligatoria
        $alertMsg  = 'La contraseña es obligatoria para un usuario nuevo.';
        $alertType = 'danger';
    } else {
        // Verificar que no se repita el usuario
        if ($id > 0) {
            // Editando → permitir el mismo usuario en su propio registro
            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM admin_usuarios WHERE usuario = ? AND id != ?");
            $stmt->bind_param("si", $usuario, $id);
        } else {
            // Creando
            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM admin_usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!empty($row['c']) && (int)$row['c'] > 0) {
            $alertMsg  = 'Ya existe un usuario con ese nombre.';
            $alertType = 'danger';
        } else {
            // Crear / actualizar en BD
            if ($id > 0) {
                // EDITAR
                // Obtener la contraseña actual si no se ingresó una nueva
                if ($clave === '') {
                    $stmt = $conn->prepare("SELECT password FROM admin_usuarios WHERE id = ? LIMIT 1");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $cur = $res->fetch_assoc();
                    $stmt->close();

                    if (!$cur) {
                        $alertMsg  = 'El usuario que intentas editar no existe.';
                        $alertType = 'danger';
                    } else {
                        $hash = $cur['password']; // dejamos la misma contraseña
                    }
                } else {
                    // Nueva contraseña → re-hashear
                    $hash = hash('sha256', $clave);
                }

                if ($alertMsg === '') {
                    $stmt = $conn->prepare("
                        UPDATE admin_usuarios
                        SET usuario = ?, password = ?, rol = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("sssi", $usuario, $hash, $rol, $id);

                    if ($stmt->execute()) {
                        $alertMsg  = 'Usuario administrador actualizado correctamente.';
                        $alertType = 'success';

                        // Si editó su propio usuario, actualizar nombre en sesión
                        if (!empty($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] === $id) {
                            $_SESSION['admin_name'] = $usuario;
                        }
                    } else {
                        $alertMsg  = 'Error al actualizar usuario: ' . $stmt->error;
                        $alertType = 'danger';
                    }
                    $stmt->close();
                }
            } else {
                // CREAR NUEVO
                $hash = hash('sha256', $clave);

                $stmt = $conn->prepare("
                    INSERT INTO admin_usuarios (usuario, password, rol)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("sss", $usuario, $hash, $rol);

                if ($stmt->execute()) {
                    $alertMsg  = 'Usuario administrador creado correctamente.';
                    $alertType = 'success';
                } else {
                    $alertMsg  = 'Error al crear usuario: ' . $stmt->error;
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
    'id'      => 0,
    'usuario' => '',
    'rol'     => 'admin'
];

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT id, usuario, rol FROM admin_usuarios WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $editData = $res->fetch_assoc();
    } else {
        $alertMsg  = 'El usuario que intentas editar no existe.';
        $alertType = 'danger';
    }
    $stmt->close();
}

// ----------------------
// LISTA DE USUARIOS ADMIN
// ----------------------
$usuarios = [];
$resUsers = $conn->query("
    SELECT id, usuario, rol, creado
    FROM admin_usuarios
    ORDER BY id DESC
");
if ($resUsers) {
    $usuarios = $resUsers->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Usuarios admin | Panel</title>
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
    <h3 class="mb-0">Usuarios administradores</h3>
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
          <?= $editData['id'] ? 'Editar usuario admin' : 'Nuevo usuario admin' ?>
        </h5>
        <p class="small text-muted mb-3">
          Los usuarios admin pueden acceder al panel de administración.
        </p>

        <form method="post" autocomplete="off">
          <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">

          <div class="mb-3">
            <label class="form-label small">Usuario</label>
            <input type="text"
                   name="usuario"
                   class="form-control bg-dark text-light border-secondary"
                   value="<?= e($editData['usuario']) ?>"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label small">
              Contraseña
              <?php if ($editData['id']): ?>
                <span class="text-muted">(deja en blanco para no cambiarla)</span>
              <?php endif; ?>
            </label>
            <input type="password"
                   name="clave"
                   class="form-control bg-dark text-light border-secondary">
          </div>

          <div class="mb-3">
            <label class="form-label small">Rol</label>
            <select name="rol"
                    class="form-select bg-dark text-light border-secondary">
              <option value="admin"  <?= $editData['rol'] === 'admin'  ? 'selected' : '' ?>>Admin</option>
              <option value="editor" <?= $editData['rol'] === 'editor' ? 'selected' : '' ?>>Editor</option>
            </select>
          </div>

          <button class="btn btn-primary w-100">
            <i class="bi bi-save"></i>
            <?= $editData['id'] ? 'Guardar cambios' : 'Crear usuario' ?>
          </button>

          <?php if ($editData['id']): ?>
            <a href="usuarios_admin.php" class="btn btn-outline-light btn-sm w-100 mt-2">
              Cancelar edición
            </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Listado de usuarios admin -->
    <div class="col-md-8">
      <div class="card glass p-3 text-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Listado de usuarios admin</h5>
        </div>

        <?php if (empty($usuarios)): ?>
          <p class="small text-muted mb-0">
            No hay usuarios administradores registrados.
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-dark table-striped table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:60px;">ID</th>
                  <th>Usuario</th>
                  <th>Rol</th>
                  <th>Creado</th>
                  <th style="width:170px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($usuarios as $u): ?>
                  <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= e($u['usuario']) ?></td>
                    <td><?= e($u['rol']) ?></td>
                    <td class="small text-muted">
                      <?= e($u['creado']) ?>
                    </td>
                    <td class="text-end">
                      <a href="usuarios_admin.php?edit=<?= (int)$u['id'] ?>"
                         class="btn btn-sm btn-outline-info">
                        <i class="bi bi-pencil"></i> Editar
                      </a>
                      <a href="usuarios_admin.php?delete=<?= (int)$u['id'] ?>"
                         class="btn btn-sm btn-outline-danger ms-1"
                         onclick="return confirm('¿Seguro que deseas eliminar este usuario administrador?');">
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
