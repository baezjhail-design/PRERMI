<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../security.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-MCU-KEY, X-MCU-ID, Authorization');

// Limites ampliados para payloads con imagenes base64 grandes.
@ini_set('post_max_size', '20M');
@ini_set('upload_max_filesize', '20M');
@ini_set('memory_limit', '256M');

$maxImageBytes = 12 * 1024 * 1024; // 12MB binarios

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErr('Method not allowed', 405);
}

if (function_exists('requireMCUAccess')) {
    requireMCUAccess();
}

function ensureSemaforosRojosTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS semaforos_rojos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehiculo_id INT NOT NULL,
        placa VARCHAR(32) NOT NULL,
        tipo_vehiculo VARCHAR(80) NULL,
        imagen VARCHAR(255) NOT NULL,
        ubicacion VARCHAR(255) NULL,
        nota VARCHAR(255) NULL,
        estado VARCHAR(30) NOT NULL DEFAULT 'semaforo_rojo',
        creado_en DATETIME NOT NULL,
        INDEX idx_vehiculo_id (vehiculo_id),
        INDEX idx_estado (estado),
        INDEX idx_creado_en (creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function extractLastJsonFromOutput(string $text): ?array {
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

function runVehicleVerification(string $binary, string $evento): array {
    $tmpDir = __DIR__ . '/../../uploads/tmp_verify';
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0755, true);
    }

    if (!is_dir($tmpDir)) {
        return [
            'ok' => false,
            'error' => 'No se pudo preparar directorio temporal de verificacion'
        ];
    }

    $tmpFile = $tmpDir . '/verify_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.jpg';
    if (file_put_contents($tmpFile, $binary) === false) {
        return [
            'ok' => false,
            'error' => 'No se pudo crear imagen temporal para verificacion'
        ];
    }

    $pythonScript = realpath(__DIR__ . '/../../python/vehicle_verify.py');
    if (!$pythonScript || !file_exists($pythonScript)) {
        @unlink($tmpFile);
        return [
            'ok' => false,
            'error' => 'Script Python de verificacion no encontrado'
        ];
    }

    $output = null;
    $pythonBins = ['python3', 'python'];
    foreach ($pythonBins as $pythonBin) {
        $cmd = escapeshellcmd($pythonBin) . ' ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($tmpFile) . ' 2>&1';
        $out = shell_exec($cmd);
        if ($out !== null && trim((string)$out) !== '') {
            $output = $out;
            break;
        }
    }
    @unlink($tmpFile);

    if ($output === null) {
        return [
            'ok' => false,
            'error' => 'No se pudo ejecutar el verificador Python'
        ];
    }

    $result = extractLastJsonFromOutput($output);
    if (!is_array($result)) {
        registrarLog('Salida invalida de vehicle_verify.py: ' . substr((string)$output, 0, 1200), 'error');
        return [
            'ok' => false,
            'error' => 'Respuesta invalida del verificador Python'
        ];
    }

    $isRegistered = !empty($result['success'])
        && !empty($result['match_id'])
        && (($result['categoria'] ?? 'sin_match') !== 'sin_match');

    return [
        'ok' => true,
        'is_registered' => $isRegistered,
        'result' => $result,
        'evento' => $evento
    ];
}

function saveNoReconocidoCapture(string $binary, string $evento): ?array {
    $capturasDir = __DIR__ . '/../../uploads/capturas_cam/';
    if (!is_dir($capturasDir) && !@mkdir($capturasDir, 0755, true) && !is_dir($capturasDir)) {
        return null;
    }

    $fileName = 'veh_no_reconocido_' . preg_replace('/[^a-z0-9_\-]/i', '', $evento) . '_' . date('Ymd_His') . '_' . rand(100, 999) . '.jpg';
    $fullPath = $capturasDir . $fileName;

    if (@file_put_contents($fullPath, $binary) === false) {
        return null;
    }

    return [
        'file' => $fileName,
        'url' => '/PRERMI/uploads/capturas_cam/' . rawurlencode($fileName),
    ];
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    jsonErr('JSON invalido');
}

$imagenB64 = trim((string)($data['imagen_base64'] ?? ''));
if ($imagenB64 === '') {
    jsonErr('imagen_base64 es requerido');
}

if (strpos($imagenB64, 'base64,') !== false) {
    $parts = explode('base64,', $imagenB64, 2);
    $imagenB64 = $parts[1] ?? '';
}

$bin = base64_decode($imagenB64, true);
if ($bin === false || !isValidJpegBinary($bin)) {
    jsonErr('Imagen base64 invalida o no es JPEG');
}

if (strlen($bin) > $maxImageBytes) {
    jsonErr('Imagen demasiado grande (máx 12MB)', 413);
}

$evento = strtolower(trim((string)($data['evento'] ?? 'normal')));
if ($evento === 'normal') {
    $evento = 'vehiculo_detectado';
}
if ($evento !== 'vehiculo_detectado' && $evento !== 'semaforo_rojo') {
    $evento = 'vehiculo_detectado';
}

$placa = trim((string)($data['placa'] ?? 'DESCONOCIDA'));
$tipoVehiculo = trim((string)($data['tipo_vehiculo'] ?? 'Desconocido'));
$ubicacion = trim((string)($data['ubicacion'] ?? 'Semaforo inteligente'));
$modeloMl = trim((string)($data['modelo_ml'] ?? 'VPS-Classifier'));
$probabilidad = isset($data['probabilidad']) ? floatval($data['probabilidad']) : 0.0;
$lat = isset($data['latitud']) ? floatval($data['latitud']) : null;
$lon = isset($data['longitud']) ? floatval($data['longitud']) : null;

$nota = trim((string)($data['nota'] ?? 'Evento detectado por ESP32-S3 CAM'));
$userId = isset($data['user_id']) ? intval($data['user_id']) : 0;
$contenedorId = isset($data['contenedor_id']) ? intval($data['contenedor_id']) : 0;
$multaAutomatica = !empty($data['multa_automatica']);

$verificationData = null;
if ($evento === 'vehiculo_detectado') {
    $verificationData = runVehicleVerification($bin, $evento);
    if (empty($verificationData['ok'])) {
        jsonErr('Error de verificacion Python: ' . ($verificationData['error'] ?? 'desconocido'), 502);
    }

    if (empty($verificationData['is_registered'])) {
        $capturaNoReconocida = saveNoReconocidoCapture($bin, $evento);
        jsonOk([
            'msg' => 'Sin vehiculo registrado en la imagen. Evento descartado.',
            'guardado' => false,
            'evento' => $evento,
            'verificacion' => $verificationData['result'] ?? null,
            'captura_cam' => $capturaNoReconocida,
        ]);
    }

    $vr = $verificationData['result'] ?? [];
    if (!empty($vr['categoria'])) {
        $tipoVehiculo = trim((string)$vr['categoria']);
    }
    if (isset($vr['confianza'])) {
        $probabilidad = floatval($vr['confianza']);
    }
    if (!empty($vr['match_id'])) {
        $placa = 'REG-' . intval($vr['match_id']);
    }
    if (!empty($vr['detalle'])) {
        $nota = trim($nota . ' | verificado=' . (string)$vr['detalle']);
    }
}

$uploadDir = __DIR__ . '/../../uploads/vehiculos/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    jsonErr('No se pudo crear directorio de uploads', 500);
}

$safePlaca = preg_replace('/[^0-9A-Za-z_-]/', '', $placa);
if ($safePlaca === '') {
    $safePlaca = 'DESCONOCIDA';
}

$fileName = 'veh_' . $safePlaca . '_' . date('Ymd_His') . '_' . rand(100, 999) . '.jpg';
$fullPath = $uploadDir . $fileName;
if (file_put_contents($fullPath, $bin) === false) {
    jsonErr('No se pudo guardar imagen', 500);
}

$fecha = date('Y-m-d');
$hora = date('H:i:s');

try {
    $pdo = getPDO();
    ensureSemaforosRojosTable($pdo);
    $transactionStarted = $pdo->beginTransaction();

    $stmtVeh = $pdo->prepare(
        'INSERT INTO vehiculos_registrados (placa, tipo_vehiculo, imagen, ubicacion, fecha, hora, modelo_ml, probabilidad, latitud, longitud, creado_en)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmtVeh->execute([$placa, $tipoVehiculo, $fileName, $ubicacion, $fecha, $hora, $modeloMl, $probabilidad, $lat, $lon]);
    $vehiculoId = intval($pdo->lastInsertId());

    $capturaRojoId = null;
    if ($evento === 'semaforo_rojo') {
        $stmtRojo = $pdo->prepare(
            'INSERT INTO capturas_semaforo_rojo (vehiculo_id, marcado_por_admin_id, nota, imagen, creado_en) VALUES (?, NULL, ?, ?, NOW())'
        );
        $stmtRojo->execute([$vehiculoId, $nota, $fileName]);
        $capturaRojoId = intval($pdo->lastInsertId());

        $stmtSemRojo = $pdo->prepare(
            'INSERT INTO semaforos_rojos (vehiculo_id, placa, tipo_vehiculo, imagen, ubicacion, nota, estado, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, "semaforo_rojo", NOW())'
        );
        $stmtSemRojo->execute([$vehiculoId, $placa, $tipoVehiculo, $fileName, $ubicacion, $nota]);
    }

    $sancionId = null;
    if ($evento === 'semaforo_rojo' && $multaAutomatica && $userId > 0 && $contenedorId > 0) {
        $descripcion = trim((string)($data['descripcion'] ?? 'Violacion de semaforo en rojo'));
        $peso = isset($data['peso']) ? floatval($data['peso']) : null;

        $stmtSan = $pdo->prepare(
            'INSERT INTO sanciones (user_id, contenedor_id, descripcion, peso) VALUES (?, ?, ?, ?)'
        );
        $stmtSan->execute([$userId, $contenedorId, $descripcion, $peso]);
        $sancionId = intval($pdo->lastInsertId());
    }

    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->commit();
    }

    jsonOk([
        'msg' => 'Evento registrado',
        'vehiculo_id' => $vehiculoId,
        'captura_rojo_id' => $capturaRojoId,
        'sancion_id' => $sancionId,
        'evento' => $evento,
        'marcado_servidor' => ($evento === 'semaforo_rojo' ? 'semaforo_rojo' : 'vehiculo_detectado'),
        'tabla_destino' => ($evento === 'semaforo_rojo' ? 'semaforos_rojos' : 'vehiculos_registrados'),
        'file' => $fileName,
        'multa_automatica_aplicada' => ($sancionId !== null),
        'verificacion' => $verificationData['result'] ?? null,
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonErr('Error al registrar evento: ' . $e->getMessage(), 500);
}
