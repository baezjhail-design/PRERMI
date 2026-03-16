<?php
ob_start();

header("Content-Type: application/json; charset=UTF-8");

$allowedOrigins = [
    'https://prermi.duckdns.org',
    'http://prermi.duckdns.org',
    'http://localhost',
    'http://127.0.0.1'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

function writeRegisterLog(string $message): void {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $line = '[' . date('c') . '] ' . $message . PHP_EOL;
    @file_put_contents($logDir . '/register_errors.log', $line, FILE_APPEND);
}

function normalizeDigits(string $value): string {
    return preg_replace('/\D+/', '', $value) ?? '';
}

try {
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../config/db_config.php';
} catch (Throwable $e) {
    writeRegisterLog('Dependency error (utils/db): ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'msg' => 'Error crítico de configuración del servidor (dependencias).'
    ]);
    exit;
}

$mailerLoaded = false;
try {
    if (file_exists(__DIR__ . '/../../config/mailer.php')) {
        require_once __DIR__ . '/../../config/mailer.php';
        $mailerLoaded = true;
    }
} catch (Throwable $e) {
    writeRegisterLog('Mailer load warning: ' . $e->getMessage());
}

ob_clean();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        $input = $_POST;
    }
    if (!$input || !is_array($input)) {
        jsonErr('Entrada inválida', 400);
    }

    $required = ['nombre', 'apellido', 'usuario', 'cedula', 'clave', 'email'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
            jsonErr("Falta el campo: $field", 400);
        }
    }

    $nombre = trim((string)$input['nombre']);
    $apellido = trim((string)$input['apellido']);
    $usuarioRaw = trim((string)$input['usuario']);
    $emailRaw = trim((string)$input['email']);
    $cedulaRaw = trim((string)$input['cedula']);
    $telefonoRaw = isset($input['telefono']) ? trim((string)$input['telefono']) : '';
    $clave = (string)$input['clave'];

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 80) {
        jsonErr('El nombre debe tener entre 2 y 80 caracteres', 422);
    }
    if (mb_strlen($apellido) < 2 || mb_strlen($apellido) > 80) {
        jsonErr('El apellido debe tener entre 2 y 80 caracteres', 422);
    }

    $usuario = mb_strtolower($usuarioRaw);
    if (!preg_match('/^[a-z0-9._-]{4,30}$/', $usuario)) {
        jsonErr('El nombre de usuario debe tener 4-30 caracteres (a-z, 0-9, ., _, -)', 422);
    }

    $email = mb_strtolower($emailRaw);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
        jsonErr('El correo electrónico no es válido', 422);
    }

    $cedulaDigits = normalizeDigits($cedulaRaw);
    if (!preg_match('/^\d{11}$/', $cedulaDigits)) {
        jsonErr('La cédula debe tener exactamente 11 dígitos', 422);
    }
    $cedula = substr($cedulaDigits, 0, 3) . '-' . substr($cedulaDigits, 3, 7) . '-' . substr($cedulaDigits, 10, 1);

    $telefono = null;
    if ($telefonoRaw !== '') {
        $telefonoDigits = normalizeDigits($telefonoRaw);
        if (!preg_match('/^\d{10}$/', $telefonoDigits)) {
            jsonErr('El teléfono debe tener exactamente 10 dígitos', 422);
        }
        $telefono = substr($telefonoDigits, 0, 3) . '-' . substr($telefonoDigits, 3, 3) . '-' . substr($telefonoDigits, 6, 4);
    }

    if (mb_strlen($clave) < 8 || mb_strlen($clave) > 100) {
        jsonErr('La contraseña debe tener entre 8 y 100 caracteres', 422);
    }

    $pdo = getPDO();

    $dupSql = "SELECT
                EXISTS(SELECT 1 FROM usuarios WHERE LOWER(usuario) = LOWER(?)) AS u_usuario,
                EXISTS(SELECT 1 FROM usuarios WHERE LOWER(email) = LOWER(?)) AS u_email,
                EXISTS(SELECT 1 FROM usuarios WHERE cedula = ?) AS u_cedula,
                EXISTS(SELECT 1 FROM usuarios WHERE telefono = ? AND ? IS NOT NULL) AS u_telefono";
    $dupStmt = $pdo->prepare($dupSql);
    $dupStmt->execute([$usuario, $email, $cedula, $telefono, $telefono]);
    $dup = $dupStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!empty($dup['u_usuario'])) {
        jsonErr('El nombre de usuario ya está registrado', 409);
    }
    if (!empty($dup['u_email'])) {
        jsonErr('El correo electrónico ya está registrado', 409);
    }
    if (!empty($dup['u_cedula'])) {
        jsonErr('La cédula ya está registrada', 409);
    }
    if (!empty($dup['u_telefono'])) {
        jsonErr('El teléfono ya está registrado', 409);
    }

    if (!function_exists('generarToken')) {
        jsonErr('Error interno: token no disponible', 500);
    }

    $token = generarToken();
    $claveHash = password_hash($clave, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios
            (nombre, apellido, usuario, email, telefono, cedula, token, clave, verified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nombre,
        $apellido,
        $usuario,
        $email,
        $telefono,
        $cedula,
        $token,
        $claveHash
    ]);

    $emailSent = false;
    if ($mailerLoaded && function_exists('sendRegistrationConfirmationEmail')) {
        try {
            $fullName = $nombre . ' ' . $apellido;
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'prermi.duckdns.org';
            $baseUrl = $protocol . '://' . $host . '/PRERMI';
            $link = $baseUrl . '/api/usuarios/verifyEmail.php?token=' . urlencode($token);
            $emailSent = sendRegistrationConfirmationEmail($email, $fullName, $link, 'usuario');
        } catch (Throwable $e) {
            writeRegisterLog('Email send error: ' . $e->getMessage());
        }
    }

    $message = $emailSent
        ? 'Usuario registrado. Revisa tu correo para verificar tu cuenta antes de iniciar sesión.'
        : 'Usuario registrado, pero no se pudo enviar el correo de verificación en este momento.';

    jsonOk([
        'message' => $message,
        'usuario' => $usuario,
        'email_sent' => $emailSent,
        'require_verification' => true
    ]);

} catch (PDOException $e) {
    if (strpos($e->getMessage(), '1062') !== false) {
        jsonErr('Registro duplicado: usuario, cédula, correo o teléfono ya existe', 409);
    }
    writeRegisterLog('PDO error: ' . $e->getMessage());
    jsonErr('Error al registrar usuario', 500);
} catch (Throwable $e) {
    writeRegisterLog('General error: ' . $e->getMessage());
    jsonErr('Error interno del sistema', 500);
}
