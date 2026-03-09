<?php
// ============================================
// PRERMI - Registro de Usuarios (REPARADO)
// ============================================

// 1️⃣ Output buffering para evitar HTML accidental
ob_start();

// 2️⃣ Headers JSON + CORS desde el inicio
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 3️⃣ Configuración de errores (NO HTML)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// 4️⃣ Carga segura de dependencias
try {
    require_once __DIR__ . '/../utils.php';
    require_once __DIR__ . '/../../config/db_config.php';
    require_once __DIR__ . '/../../config/mailer.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'msg' => 'Error crítico cargando dependencias'
    ]);
    exit;
}

// 5️⃣ Limpiar cualquier salida previa
ob_clean();

// 6️⃣ Ejecutar lógica principal
try {

    // === Leer entrada: JSON o form-data (InfinityFree bloquea application/json) ===
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        $input = $_POST;
    }
    if (!$input || !is_array($input)) {
        jsonErr('Entrada JSON inválida', 400);
    }

    // === Validación de campos obligatorios ===
    $required = ['nombre', 'apellido', 'usuario', 'cedula', 'clave', 'email'];
    foreach ($required as $campo) {
        if (empty($input[$campo])) {
            jsonErr("Falta el campo: $campo", 400);
        }
    }

    // === Validar formato de email ===
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        jsonErr('El correo electr\u00f3nico no es v\u00e1lido', 400);
    }

    // === Sanitizar datos ===
    $nombre   = trim($input['nombre']);
    $apellido = trim($input['apellido']);
    $usuario  = trim($input['usuario']);
    $cedula   = trim($input['cedula']);
    $email    = trim($input['email']);
    $telefono = !empty($input['telefono']) ? trim($input['telefono']) : null;

    // === Password seguro ===
    $claveHash = password_hash($input['clave'], PASSWORD_DEFAULT);

    // === Token de verificación ===
    if (!function_exists('generarToken')) {
        jsonErr('Error interno: token no disponible', 500);
    }
    $token = generarToken();

    // === Insertar usuario ===
    $pdo = getPDO();

    $sql = "INSERT INTO usuarios 
            (nombre, apellido, usuario, email, telefono, cedula, token, clave, verified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";

    $stmt = $pdo->prepare($sql);
    if (!$stmt) {
        jsonErr('Error preparando consulta SQL', 500);
    }

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

    // === Envío de correo de verificación (OBLIGATORIO) ===
    $emailSent = false;
    if (function_exists('sendRegistrationConfirmationEmail')) {
        try {
            $nombreCompleto = "$nombre $apellido";
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
            $base_url = $protocol . '://' . $host . '/PRERMI';
            $link = $base_url . "/api/usuarios/verifyEmail.php?token=" . urlencode($token);
            $emailSent = sendRegistrationConfirmationEmail($email, $nombreCompleto, $link, 'usuario');
        } catch (Throwable $e) {
            error_log('Email error registro usuario: ' . $e->getMessage());
        }
    }

    // === Respuesta exitosa ===
    jsonOk([
        'message' => 'Usuario registrado. Revisa tu correo para verificar tu cuenta antes de iniciar sesi\u00f3n.',
        'usuario' => $usuario,
        'email_sent' => $emailSent,
        'require_verification' => true
    ]);

} catch (PDOException $e) {

    // === Duplicados ===
    if (strpos($e->getMessage(), '1062') !== false) {
        jsonErr('Usuario, cédula o correo ya registrado', 409);
    }

    error_log('PDO Register Error: ' . $e->getMessage());
    jsonErr('Error al registrar usuario', 500);

} catch (Throwable $e) {

    error_log('Register General Error: ' . $e->getMessage());
    jsonErr('Error interno del sistema', 500);
}
