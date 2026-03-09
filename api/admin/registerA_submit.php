<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';

header("Content-Type: application/json; charset=UTF-8");

// Disable error display to avoid HTML mixed with JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Validar datos
$usuario = trim($_POST['usuario'] ?? '');
$email   = trim($_POST['email'] ?? '');
$clave   = trim($_POST['clave'] ?? '');
$nombre  = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');

if ($usuario === '' || $email === '' || $clave === '') {
    jsonErr("Faltan datos (usuario, email, clave requeridos)");
}

// Validar si existe email o usuario
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario=? OR email=? LIMIT 1");
    $stmt->execute([$usuario, $email]);

    if ($stmt->fetch()) {
        jsonErr("El usuario o correo ya existe");
    }
} catch (PDOException $e) {
    jsonErr("Error en la base de datos", 500);
}

// Crear token y hash
$hash  = password_hash($clave, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32)); // Token de 64 caracteres

$rol = "admin";
$verified = 0;
$active = 0;

// Insertar en DB
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO usuarios_admin
        (usuario, email, clave, verification_token, verified, active, rol, creado_en)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->execute([
        $usuario,
        $email,
        $hash,
        $token,
        $verified,
        $active,
        $rol
    ]);
} catch (PDOException $e) {
    error_log('RegisterA_submit DB error: ' . $e->getMessage());
    jsonErr("Error al registrar: " . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('RegisterA_submit error: ' . $e->getMessage());
    jsonErr("Error al registrar: " . $e->getMessage(), 500);
}

// Enviar email de confirmación con función centralizada
try {
    $fullName = !empty($nombre) && !empty($apellido) ? "$nombre $apellido" : $usuario;
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
    $base_url = $protocol . '://' . $host . '/PRERMI';
    $verificationLink = $base_url . "/api/admin/verifyEmailTokenA.php?token=" . urlencode($token);

    if (sendRegistrationConfirmationEmail($email, $fullName, $verificationLink, 'admin')) {
        jsonOk(["msg" => "Registro completado. Se envió un email de verificación a $email. Después de confirmar, un administrador aprobará tu acceso."]);
    } else {
        jsonOk(["msg" => "Registro completado, pero hubo error al enviar el email. Contacta al administrador.", "warning" => true]);
    }
} catch (Exception $e) {
    error_log('RegisterA_submit email error: ' . $e->getMessage());
    jsonErr("Error al enviar email: " . $e->getMessage(), 500);
}

