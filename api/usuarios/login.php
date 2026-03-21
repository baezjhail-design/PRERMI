<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");
// CORS restringido a orígenes de confianza.
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

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// Aceptar tanto JSON como form-data (InfinityFree bloquea application/json)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    $input = $_POST;
}
if (!$input || empty($input['usuario']) || empty($input['clave'])) jsonErr('Credenciales faltan');

$usuario = trim($input['usuario']);
$clave = $input['clave'];

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id,nombre,apellido,usuario,email,telefono,cedula,token,token_activo,clave,verified,activo FROM usuarios WHERE usuario = ? LIMIT 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();
    
    if (!$user) jsonErr('Usuario no existe',404);
    if (!password_verify($clave, $user['clave'])) jsonErr('Clave incorrecta',401);

    // Verificar que no esté baneado
    if (intval($user['activo']) === 0) {
        jsonErr('Tu cuenta ha sido suspendida. Contacta al administrador para más información.', 403);
    }
    
    // Verificar que el email ha sido confirmado
    if (isset($user['verified']) && intval($user['verified']) === 0) {
        jsonErr('Debes verificar tu correo electrónico antes de iniciar sesión. Revisa tu bandeja de entrada.', 403);
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['usuario'] = $user['usuario'];
    
    unset($user['clave']);
    unset($user['verified']);
    jsonOk(['user'=>$user, 'message' => 'Login exitoso']);
} catch (PDOException $e) {
    jsonErr('Error en la base de datos', 500);
}

