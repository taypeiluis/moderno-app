<?php
// inc/db.php - edita con tus credenciales MySQL
$DB_HOST = 'localhost';
$DB_NAME = 'sistema_membresias';
$DB_USER = 'root';
$DB_PASS = '';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Error conexión MySQL: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
?>
