<?php
ob_start();

require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

function writeAdminRegisterLog(string $message): void {
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
    writeAdminRegisterLog('Mailer load warning: ' . $e->getMessage());
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    $input = $_POST;
}
if (!$input || !is_array($input)) {
    jsonErr('Entrada inválida', 400);
}

$required = ['usuario', 'email', 'clave'];
foreach ($required as $field) {
    if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
        jsonErr("Campo obligatorio: $field", 400);
    }
}

$usuario = mb_strtolower(trim((string)$input['usuario']));
$email = mb_strtolower(trim((string)$input['email']));
$nombre = trim((string)($input['nombre'] ?? ''));
$apellido = trim((string)($input['apellido'] ?? ''));
$clave = (string)$input['clave'];

if (!preg_match('/^[a-z0-9._-]{4,30}$/', $usuario)) {
    jsonErr('El usuario debe tener 4-30 caracteres válidos', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
    jsonErr('Email inválido', 422);
}
if (mb_strlen($clave) < 8 || mb_strlen($clave) > 100) {
    jsonErr('La contraseña debe tener entre 8 y 100 caracteres', 422);
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id FROM usuarios_admin WHERE LOWER(usuario) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
    $stmt->execute([$usuario, $email]);
    if ($stmt->fetch()) {
        jsonErr('Usuario o email ya registrado', 409);
    }

    $verification_token = bin2hex(random_bytes(32));
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO usuarios_admin (usuario, email, nombre, apellido, clave, verification_token, verified, active, rol)
         VALUES (?, ?, ?, ?, ?, ?, 0, 0, 'admin')"
    );
    $stmt->execute([$usuario, $email, $nombre, $apellido, $clave_hash, $verification_token]);
    $admin_id = $pdo->lastInsertId();

    $fullName = trim((string)($nombre . ' ' . $apellido));
    if ($fullName === '') {
        $fullName = $usuario;
    }

    $emailSent = false;
    if ($mailerLoaded && function_exists('sendRegistrationConfirmationEmail')) {
        try {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'prermi.duckdns.org';
            $base_url = $protocol . '://' . $host . '/PRERMI';
            $verifyLink = $base_url . "/api/admin/verifyEmailTokenA.php?token=" . urlencode($verification_token);
            $emailSent = sendRegistrationConfirmationEmail($email, $fullName, $verifyLink, 'admin');
            if (!$emailSent) {
                writeAdminRegisterLog('sendRegistrationConfirmationEmail returned false for ' . $email);
            }
        } catch (Throwable $e) {
            writeAdminRegisterLog('Admin email send error: ' . $e->getMessage());
        }
    }

    registrarLog("Nuevo administrador registrado: $usuario ($email)", "info");
    $message = $emailSent
        ? 'Registro exitoso. Revisa tu correo para verificar tu cuenta.'
        : 'Registro exitoso, pero no se pudo enviar el correo de verificación en este momento.';

    jsonOk([
        'message' => $message,
        'admin_id' => $admin_id,
        'email_sent' => $emailSent
    ]);

} catch (PDOException $e) {
    writeAdminRegisterLog('PDO error: ' . $e->getMessage());
    if (strpos($e->getMessage(), '1062') !== false) {
        jsonErr('Usuario o email ya registrado', 409);
    }
    jsonErr('Error en la base de datos', 500);
} catch (Throwable $e) {
    writeAdminRegisterLog('General error: ' . $e->getMessage());
    jsonErr('Error en el registro', 500);
}

