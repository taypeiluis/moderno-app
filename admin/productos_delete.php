<?php
require_once __DIR__ . '/admin-config.php';

if (!function_exists('esURL')) {
    function esURL($cadena) {
        return filter_var($cadena, FILTER_VALIDATE_URL) !== false;
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: productos.php?msg=error_id');
    exit;
}

// Buscar producto para saber si tiene imagen
$stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$producto = $res->fetch_assoc();
$stmt->close();

if (!$producto) {
    header('Location: productos.php?msg=no_encontrado');
    exit;
}

// Borrar imagen física si es archivo local
if (!empty($producto['imagen']) && !esURL($producto['imagen'])) {
    $ruta1 = __DIR__ . '/../uploads/productos/' . $producto['imagen'];
    $ruta2 = __DIR__ . '/../uploads/' . $producto['imagen'];

    if (is_file($ruta1)) {
        @unlink($ruta1);
    } elseif (is_file($ruta2)) {
        @unlink($ruta2);
    }
}

// Borrar producto de la BD
$stmtDel = $conn->prepare("DELETE FROM productos WHERE id = ? LIMIT 1");
$stmtDel->bind_param("i", $id);
$stmtDel->execute();
$stmtDel->close();

header('Location: productos.php?msg=eliminado');
exit;
