<?php
/**
 * security.example.php — Plantilla de claves de seguridad PRERMI
 *
 * INSTRUCCIONES PARA COLABORADORES:
 *   1. Copia este archivo: cp api/security.example.php api/security.php
 *   2. Genera claves únicas para tu entorno (no reutilices las de producción).
 *   3. Las mismas claves MCU deben configurarse en el firmware de cada placa.
 *   4. El archivo api/security.php está en .gitignore por seguridad.
 *
 * Para generar una clave segura en PHP:
 *   php -r "echo bin2hex(random_bytes(24));"
 */

// ============================================================
// CLAVES VÁLIDAS PARA DISPOSITIVOS MCU
// ============================================================
define('MCU_API_KEYS', [
    'ESP32-S3-CAM'  => 'CAMBIA-ESTA-CLAVE-ESP32S3',    // Generar con bin2hex(random_bytes(24))
    'ESP8266-OLED'  => 'CAMBIA-ESTA-CLAVE-ESP8266',    // Generar con bin2hex(random_bytes(24))
]);

// ============================================================
// USUARIOS AUTORIZADOS PARA ACCESO DE DESARROLLO / ADMIN API
// ============================================================
define('DEV_USERS', [
    'tu_usuario' => [
        'nombre'   => 'Tu Nombre',
        'password' => 'contraseña_segura_aqui',  // min 20 chars, símbolos, mayúsculas
    ],
]);

// ============================================================
// FUNCIÓN: requireMCUAccess
// ============================================================
function requireMCUAccess(): void
{
    $key = $_SERVER['HTTP_X_MCU_KEY']  ?? '';
    $id  = $_SERVER['HTTP_X_MCU_ID']   ?? '';

    if (!$key || !$id || !array_key_exists($id, MCU_API_KEYS)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'msg' => 'Acceso MCU no autorizado']);
        exit;
    }

    if (!hash_equals(MCU_API_KEYS[$id], $key)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'msg' => 'Clave MCU inválida']);
        exit;
    }
}

// ============================================================
// FUNCIÓN: requireDevAccess
// ============================================================
function requireDevAccess(): void
{
    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $pass = $_SERVER['PHP_AUTH_PW']   ?? '';

    if (!$user || !array_key_exists($user, DEV_USERS) ||
        !hash_equals(DEV_USERS[$user]['password'], $pass))
    {
        header('WWW-Authenticate: Basic realm="PRERMI Dev"');
        http_response_code(401);
        echo json_encode(['success' => false, 'msg' => 'Credenciales de desarrollo incorrectas']);
        exit;
    }
}
