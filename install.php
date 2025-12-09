<?php
require 'inc/db.php';

// SQL: crear tablas
$sqls = [];

$sqls[] = "CREATE TABLE IF NOT EXISTS usuarios_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(100) NOT NULL UNIQUE,
  clave VARCHAR(255) NOT NULL,
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sqls[] = "CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sqls[] = "CREATE TABLE IF NOT EXISTS productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT NOT NULL,
  nombre VARCHAR(200) NOT NULL,
  descripcion TEXT,
  precio_unitario DECIMAL(10,2) DEFAULT 0,
  precio_mayor DECIMAL(10,2) DEFAULT 0,
  imagen VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sqls[] = "CREATE TABLE IF NOT EXISTS banners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200),
  imagen VARCHAR(255),
  link VARCHAR(255),
  orden INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sqls[] = "CREATE TABLE IF NOT EXISTS vendedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200),
  codigo VARCHAR(200) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

foreach($sqls as $s) {
    if ($conn->query($s) === TRUE) echo "<p>OK</p>";
    else echo "<p>Error: ".$conn->error."</p>";
}

// crear admin por defecto (solo si no existe)
$admin = 'admin';
$pass = 'admin123';
$stmt = $conn->prepare("SELECT id FROM usuarios_admin WHERE usuario = ?");
$stmt->bind_param('s',$admin);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO usuarios_admin (usuario, clave) VALUES (?,?)");
    $ins->bind_param('ss', $admin, $hash);
    $ins->execute();
    echo "<p>Usuario admin creado: usuario='admin' pass='admin123' (cámbialo luego)</p>";
} else {
    echo "<p>Usuario admin ya existe. No se creó.</p>";
}

echo "<p>Instalación finalizada. BORRA o RENOMBRA install.php por seguridad.</p>";
?>
