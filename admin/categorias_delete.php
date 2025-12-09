<?php
require_once __DIR__ . '/admin-config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: categorias.php?msg=error_id');
    exit;
}

// Verificar que exista la categoría
$stmt = $conn->prepare("SELECT id FROM categorias WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$categoria = $res->fetch_assoc();
$stmt->close();

if (!$categoria) {
    header('Location: categorias.php?msg=no_encontrada');
    exit;
}

// Opcional: podrías primero “desasignar” esta categoría de productos, por ejemplo:
// UPDATE productos SET categoria_id = 0 WHERE categoria_id = ?
// Lo dejo comentado por si lo quieres usar:

/*
$stmtProd = $conn->prepare("UPDATE productos SET categoria_id = 0 WHERE categoria_id = ?");
$stmtProd->bind_param("i", $id);
$stmtProd->execute();
$stmtProd->close();
*/

// Eliminar la categoría
$stmtDel = $conn->prepare("DELETE FROM categorias WHERE id = ? LIMIT 1");
$stmtDel->bind_param("i", $id);
$stmtDel->execute();
$stmtDel->close();

header('Location: categorias.php?msg=eliminada');
exit;
