<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$usuario = $_POST['usuario'] ?? '';
$clave = $_POST['clave'] ?? '';

$stmt = $conn->prepare("SELECT * FROM usuarios_admin WHERE usuario=? LIMIT 1");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    $_SESSION['msg'] = "Datos incorrectos.";
    header("Location: loginA.php"); exit;
}

$user = $res->fetch_assoc();

if (!password_verify($clave, $user['clave'])) {
    $_SESSION['msg'] = "Datos incorrectos.";
    header("Location: loginA.php"); exit;
}

if (!$user['verified']) {
    $_SESSION['msg'] = "Correo no verificado.";
    header("Location: loginA.php"); exit;
}

if (!$user['active']) {
    $_SESSION['msg'] = "Cuenta aún no aprobada.";
    header("Location: loginA.php"); exit;
}

$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_user'] = $user['usuario'];
$_SESSION['admin_rol'] = $user['rol'];

header("Location: admin/dashboard.php");
