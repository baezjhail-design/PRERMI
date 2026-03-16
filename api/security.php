<?php
/**
 * security.php — Capa de seguridad central PRERMI
 *
 * CAPA 1: requireMCUAccess()   → Solo ESP32-S3 CAM y ESP8266MOD pueden llamar APIs IoT
 * CAPA 2: requireDevAccess()   → Solo Jhail Baez y Adrian Espinal acceden vía HTTP Basic Auth
 */


// ============================================================
// CLAVES VÁLIDAS PARA DISPOSITIVOS MCU
// Estas mismas claves deben estar en el firmware de cada placa
// ============================================================
define('MCU_API_KEYS', [
    'ESP32-S3-CAM'  => 'PRERMI-ESP32S3-CAM-2026-$ecure!',
    'ESP8266-OLED'  => 'PRERMI-ESP8266-MOD-2026-$ecure!',
]);

// ============================================================
// USUARIOS AUTORIZADOS PARA ACCESO DE DESARROLLO / ADMIN API
// ============================================================
define('DEV_USERS', [
    'jhail'  => [
        'nombre'   => 'Jhail Baez',
        'password' => 'PRERMI.jhailBaez&adrianEspinal',
    ],
    'adrian' => [
        'nombre'   => 'Adrian Espinal',
        'password' => 'PRERMI.jhailBaez&adrianEspinal',
    ],
]);


// ============================================================
// CAPA 1 — ACCESO EXCLUSIVO PARA MCU (ESP32 / ESP8266)
// Uso: llamar requireMCUAccess() al inicio de cada endpoint IoT
// El MCU debe enviar: Header  X-MCU-KEY: <clave>
//                     Header  X-MCU-ID:  ESP32-S3-CAM | ESP8266-OLED
// ============================================================
function requireMCUAccess(): void {
    $headers = getallheaders() ?: [];

    // Normalizar claves del array (algunos servidores las envían en minúsculas)
    $normalized = [];
    foreach ($headers as $k => $v) {
        $normalized[strtolower($k)] = $v;
    }

    $mcuId  = trim($normalized['x-mcu-id']  ?? '');
    $mcuKey = trim($normalized['x-mcu-key'] ?? '');

    // Validar que el ID sea un dispositivo conocido
    $validKeys = MCU_API_KEYS;
    if (!$mcuId || !isset($validKeys[$mcuId])) {
        _denyMCU("Dispositivo desconocido: '$mcuId'");
    }

    // Validar la clave con comparación de tiempo constante (anti timing-attack)
    if (!hash_equals($validKeys[$mcuId], $mcuKey)) {
        _denyMCU("API Key inválida para $mcuId");
    }
}

function _denyMCU(string $motivo): void {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Acceso denegado',
        'hint'    => 'Solo dispositivos MCU autorizados pueden usar esta API',
    ]);
    // Registrar intento en log sin romper si la BD no está disponible
    _logIntento("MCU_DENY", $motivo);
    exit;
}


// ============================================================
// CAPA 2 — ACCESO DE DESARROLLADORES (HTTP Basic Auth)
// Uso: llamar requireDevAccess() en endpoints de gestión/debug
// El cliente envía: Authorization: Basic base64(usuario:password)
// ============================================================
function requireDevAccess(): void {
    // Leer credenciales Basic Auth
    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $pass = $_SERVER['PHP_AUTH_PW']   ?? '';

    $devUsers = DEV_USERS;

    if (!$user || !isset($devUsers[$user])) {
        _denyDev();
    }

    if (!hash_equals($devUsers[$user]['password'], $pass)) {
        _denyDev();
    }

    // Credenciales válidas — guardar nombre en variable global accesible
    $GLOBALS['dev_nombre'] = $devUsers[$user]['nombre'];
}

function _denyDev(): void {
    header('WWW-Authenticate: Basic realm="PRERMI Dev Access"');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Credenciales de desarrollador requeridas',
    ]);
    _logIntento("DEV_DENY", "Intento fallido desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    exit;
}


// ============================================================
// HELPER INTERNO — Log de intentos no autorizados
// ============================================================
function _logIntento(string $tipo, string $detalle): void {
    try {
        $logFile = __DIR__ . '/../logs/security.log';
        $dir     = dirname($logFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $ip   = $_SERVER['REMOTE_ADDR']    ?? 'unknown';
        $ua   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $uri  = $_SERVER['REQUEST_URI']     ?? 'unknown';
        $line = "[" . date('Y-m-d H:i:s') . "] [$tipo] IP=$ip | URI=$uri | UA=$ua | $detalle\n";

        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {
        // Silencioso — no romper la respuesta principal
    }
}
