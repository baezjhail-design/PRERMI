<?php
ob_start();

require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

function writeAdminSubmitLog(string $message): void {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logDir . '/admin_register_errors.log', '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

$mailerLoaded = false;
try {
    if (file_exists(__DIR__ . '/../../config/mailer.php')) {
        require_once __DIR__ . '/../../config/mailer.php';
        $mailerLoaded = true;
    }
} catch (Throwable $e) {
    writeAdminSubmitLog('Mailer load warning: ' . $e->getMessage());
}

$input = $_POST;
if (empty($input)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) {
        $input = $json;
    }
}

$usuario = mb_strtolower(trim((string)($input['usuario'] ?? '')));
$email   = mb_strtolower(trim((string)($input['email'] ?? '')));
$clave   = (string)($input['clave'] ?? '');
$nombre  = trim((string)($input['nombre'] ?? ''));
$apellido = trim((string)($input['apellido'] ?? ''));

if ($usuario === '' || $email === '' || $clave === '') {
    jsonErr('Faltan datos (usuario, email, clave requeridos)', 400);
}
if (!preg_match('/^[a-z0-9._-]{4,30}$/', $usuario)) {
    jsonErr('Usuario inválido', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonErr('Email inválido', 422);
}
if (mb_strlen($clave) < 8 || mb_strlen($clave) > 100) {
    jsonErr('La contraseña debe tener entre 8 y 100 caracteres', 422);
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id FROM usuarios_admin WHERE LOWER(usuario)=LOWER(?) OR LOWER(email)=LOWER(?) LIMIT 1");
    $stmt->execute([$usuario, $email]);
    if ($stmt->fetch()) {
        jsonErr('El usuario o correo ya existe', 409);
    }

    $hash  = password_hash($clave, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("INSERT INTO usuarios_admin
        (usuario, email, nombre, apellido, clave, verification_token, verified, active, rol, creado_en)
        VALUES (?, ?, ?, ?, ?, ?, 0, 0, 'admin', NOW())");

    $stmt->execute([$usuario, $email, $nombre, $apellido, $hash, $token]);

    $emailSent = false;
    if ($mailerLoaded && function_exists('sendRegistrationConfirmationEmail')) {
        try {
            $fullName = !empty($nombre) && !empty($apellido) ? "$nombre $apellido" : $usuario;
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'prermi.duckdns.org';
            $base_url = $protocol . '://' . $host . '/PRERMI';
            $verificationLink = $base_url . "/api/admin/verifyEmailTokenA.php?token=" . urlencode($token);
            $emailSent = sendRegistrationConfirmationEmail($email, $fullName, $verificationLink, 'admin');
            if (!$emailSent) {
                writeAdminSubmitLog('sendRegistrationConfirmationEmail returned false for ' . $email);
            }
        } catch (Throwable $e) {
            writeAdminSubmitLog('Admin submit email error: ' . $e->getMessage());
        }
    }

    jsonOk([
        'msg' => $emailSent
            ? "Registro completado. Se envió un email de verificación a $email."
            : 'Registro completado, pero hubo error al enviar el email. Contacta al administrador.',
        'warning' => !$emailSent,
        'email_sent' => $emailSent,
    ]);
} catch (PDOException $e) {
    writeAdminSubmitLog('PDO error: ' . $e->getMessage());
    if (strpos($e->getMessage(), '1062') !== false) {
        jsonErr('El usuario o correo ya existe', 409);
    }
    jsonErr('Error al registrar administrador', 500);
} catch (Throwable $e) {
    writeAdminSubmitLog('General error: ' . $e->getMessage());
    jsonErr('Error al registrar administrador', 500);
}

