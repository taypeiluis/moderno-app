<?php include "inc/header.php"; ?>
<?php include "inc/db.php"; ?>

<?php
$id = $_GET['id'];
$productos = $conexion->query("SELECT * FROM productos WHERE categoria_id=$id");
?>

<h2 class="title">Productos</h2>

<div class="grid">
<?php while($p = $productos->fetch_assoc()): ?>
    <a class="card" href="producto.php?id=<?= $p['id'] ?>">
        <h3><?= $p['nombre'] ?></h3>
        <p><?= $p['descripcion'] ?></p>
        <span class="price">$<?= $p['precio_publico'] ?></span>
    </a>
<?php endwhile; ?>
</div>

<?php include "inc/footer.php"; ?>
