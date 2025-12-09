<?php
session_start();
require '../inc/db.php';
if(!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST') {
    $id = (int)$_POST['id'];
    $img = $conn->query("SELECT imagen FROM productos WHERE id = $id")->fetch_assoc()['imagen'];
    if($img && file_exists("../uploads/productos/$img")) @unlink("../uploads/productos/$img");
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}
header('Location: productos.php');
