<?php
// categoria.php
// Redirige al catálogo principal usando el filtro de categoría.

// Tomamos el id de categoría si viene en la URL: categoria.php?id=3
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    // Si hay una categoría, mandamos al index con ese filtro
    header('Location: index.php?cat=' . $id);
} else {
    // Si no viene nada, mostramos todo el catálogo
    header('Location: index.php');
}
exit;
