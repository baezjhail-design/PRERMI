<?php
session_start();
require_once "../includes/db_connect.php";

$email      = $_POST['email'];
$clave      = $_POST['clave'];
$contenedor = $_POST['contenedor'];
$token      = $_POST['token'];

// 1. Validar usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuario no encontrado.");
}

// 2. Verificar clave
if (!password_verify($clave, $user['clave'])) {
    die("Clave incorrecta.");
}

// 3. Validar token
$stmt2 = $conn->prepare("SELECT * FROM contenedores_registrados 
    WHERE id = ? 
      AND ultimo_token = ? 
      AND token_expira_en > NOW()");
$stmt2->execute([$contenedor, $token]);
$tokenData = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    die("Token inválido o expirado.");
}

// 4. Guardar sesión
$_SESSION['user_id'] = $user['id'];
$_SESSION['contenedor'] = $contenedor;
$_SESSION['token'] = $token;

// 5. Redirigir a abrir
header("Location: ../frontend/abrir_CQR.php");
exit;
?>
