<?php session_start(); unset($_SESSION['mayorista'],$_SESSION['vendedor']); header('Location: index.php'); exit; ?>
<?php
session_start();
unset($_SESSION['mayorista'], $_SESSION['vendedor']);
header('Location: index.php'); exit;
