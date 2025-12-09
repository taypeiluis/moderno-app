<?php
session_start();
require '../inc/db.php';
if(!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}
header('Location: categorias.php');
