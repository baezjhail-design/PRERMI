<?php
/**
 * verificar_vehiculo.php — Endpoint para validar detección de vehículo via Python
 * Recibe imagen base64 y devuelve categoría/confianza/match
 */

require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json; charset=utf-8');

function ensureVehiculosVerificacionesTable(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehiculos_verificaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        evento VARCHAR(80) NULL,
        origen VARCHAR(80) NULL,
        categoria_detectada VARCHAR(80) NULL,
        confianza DECIMAL(5,4) NULL,
        match_catalogo_id INT NULL,
        resultado_json LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_evento (evento),
        INDEX idx_origen (origen),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Este endpoint puede ser llamado por ESP32 (con API key) o admin en pruebas
$raw = file_get_contents('php://input');
if (!$raw) {
    jsonErr('No se recibió payload', 400);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    jsonErr('JSON inválido', 400);
}

$imagenBase64 = isset($data['imagen_base64']) ? $data['imagen_base64'] : null;
$evento = isset($data['evento']) ? limpiar($data['evento']) : 'deteccion_general';
$origen = isset($data['origen']) ? limpiar($data['origen']) : 'esp32_cam';

if (!$imagenBase64) {
    jsonErr('Campo imagen_base64 es requerido', 400);
}

// Validación rápida de base64
$binary = base64_decode($imagenBase64, true);
if ($binary === false || strlen($binary) === 0) {
    jsonErr('imagen_base64 inválida', 400);
}

if (strlen($binary) > 12 * 1024 * 1024) {
    jsonErr('Imagen demasiado grande (máx 12MB)', 413);
}

if (!isValidJpegBinary($binary)) {
    jsonErr('Formato no válido. Solo JPEG', 415);
}

// Guardar imagen temporal para procesar con Python
$tmpDir = __DIR__ . '/../../uploads/tmp_verify';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}

$tmpFile = $tmpDir . '/verify_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.jpg';
if (file_put_contents($tmpFile, $binary) === false) {
    jsonErr('No se pudo guardar imagen temporal', 500);
}

$pythonScript = realpath(__DIR__ . '/../../python/vehicle_verify.py');
if (!$pythonScript || !file_exists($pythonScript)) {
    @unlink($tmpFile);
    jsonErr('Script de verificación no encontrado', 500);
}

// Comando Python
$pythonBin = 'python';
$cmd = escapeshellcmd($pythonBin) . ' ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($tmpFile) . ' 2>&1';
$output = shell_exec($cmd);

if ($output === null) {
    @unlink($tmpFile);
    jsonErr('No se pudo ejecutar verificación Python', 500);
}

// Extraer último JSON válido de la salida
function extractLastJsonFromOutput($text) {
    $lines = preg_split('/\r\n|\r|\n/', trim($text));
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $line = trim($lines[$i]);
        if ($line === '' || $line[0] !== '{') {
            continue;
        }
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return null;
}

$result = extractLastJsonFromOutput($output);
if (!$result) {
    registrarLog("Salida inválida vehicle_verify.py: " . substr($output, 0, 1200), 'error');
    @unlink($tmpFile);
    jsonErr('Respuesta inválida del verificador', 500);
}

// Persistir resultado opcionalmente en BD
try {
    $pdo = getPDO();
    ensureVehiculosVerificacionesTable($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO vehiculos_verificaciones 
        (evento, origen, categoria_detectada, confianza, match_catalogo_id, resultado_json, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $evento,
        $origen,
        isset($result['categoria']) ? $result['categoria'] : null,
        isset($result['confianza']) ? floatval($result['confianza']) : null,
        isset($result['match_id']) ? intval($result['match_id']) : null,
        json_encode($result, JSON_UNESCAPED_UNICODE)
    ]);
    $verificacionId = $pdo->lastInsertId();
} catch (Exception $e) {
    registrarLog("Error guardando verificación: " . $e->getMessage(), 'error');
    $verificacionId = null;
}

@unlink($tmpFile);

jsonOk([
    'verificacion_id' => $verificacionId,
    'resultado' => $result,
    'msg' => 'Verificación completada'
]);
