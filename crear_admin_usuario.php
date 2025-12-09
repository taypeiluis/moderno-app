<?php
// crear_admin_usuario.php
require __DIR__ . '/inc/db.php'; // tu conexión mysqli $conn

// 👉 Datos del nuevo usuario
$usuario   = 'admin2';              // NOMBRE DE USUARIO
$clave     = 'ClaveSegura2025';     // CLAVE EN TEXTO PLANO (CÁMBIALA)
$rol       = 'admin';               // admin / lo que quieras manejar

// Hashear con SHA-256 (igual formato que el que ya tienes)
$hash = hash('sha256', $clave);

// Preparar INSERT
$stmt = $conn->prepare("
    INSERT INTO admin_usuarios (usuario, password, rol)
    VALUES (?, ?, ?)
");
$stmt->bind_param("sss", $usuario, $hash, $rol);

if ($stmt->execute()) {
    echo "Usuario admin creado correctamente.<br>";
    echo "Usuario: " . htmlspecialchars($usuario) . "<br>";
    echo "Clave: "   . htmlspecialchars($clave)   . "<br>";
} else {
    echo "Error al crear usuario: " . $stmt->error;
}

$stmt->close();
