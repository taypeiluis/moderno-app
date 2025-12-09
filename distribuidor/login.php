<?php
include "../inc/config.php";

if ($_POST) {
    if (in_array($_POST['codigo'], $codigos_distribuidores)) {
        setcookie("dist", "1", time() + 86400 * 7);
        header("Location: /");
        exit;
    }
    $error = "Código incorrecto";
}
?>

<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="/assets/css/style.css"></head>
<body>
<div class="login-box">
    <h2>Distribuidor</h2>

    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="post">
        <input type="text" name="codigo" placeholder="Código distribuidor">
        <button>Ingresar</button>
    </form>
</div>
</body>
</html>
