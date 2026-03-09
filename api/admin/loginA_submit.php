<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");
session_start();

$usuario = trim($_POST['usuario'] ?? '');
$clave   = trim($_POST['clave'] ?? '');

if ($usuario === '' || $clave === '') {
    jsonErr("Faltan datos");
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, clave, verified, active, rol FROM usuarios_admin WHERE usuario=? LIMIT 1");
    $stmt->execute([$usuario]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonErr("Usuario no encontrado");
    }

    if (!password_verify($clave, $row['clave'])) {
        jsonErr("Clave incorrecta");
    }

    if ($row['verified'] == 0) {
        jsonErr("Debe verificar su correo antes de acceder");
    }
} catch (PDOException $e) {
    jsonErr("Error en la base de datos", 500);
}

if ($row['active'] == 0) {
    jsonErr("Su cuenta aún no ha sido aprobada por un administrador");
}

$_SESSION['admin_id'] = $row['id'];
$_SESSION['rol']      = $row['rol'];

jsonOk(["msg" => "Login exitoso"]);

