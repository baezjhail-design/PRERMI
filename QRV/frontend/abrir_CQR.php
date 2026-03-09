<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("No autorizado.");
}

$contenedor = $_SESSION['contenedor'] ?? '';
$token      = $_SESSION['token'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Abrir Contenedor</title>
</head>
<body>
    <h2>Procesando apertura...</h2>
    <?php require_once "../backend/openContainer_CQR.php"; ?>
</body>
</html>
