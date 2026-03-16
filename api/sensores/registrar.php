<?php
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../security.php';
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-MCU-KEY, X-MCU-ID, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
requireMCUAccess();

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}
if (!is_array($data)) {
    jsonErr('JSON inválido', 400);
}

$user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
$sensor_ir = isset($data['sensor_ir']) ? (int)$data['sensor_ir'] : 0;
$ruta_imagen = isset($data['ruta_imagen']) ? limpiar($data['ruta_imagen']) : null;

if (!$user_id) {
    jsonErr('user_id requerido', 400);
}

try {
    $pdo = getPDO();
    
    // Validar usuario
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        jsonErr('Usuario no encontrado', 404);
    }
    
    // Insertar sensor
    $stmt = $pdo->prepare(
        'INSERT INTO sensores (user_id, sensor_ir, ruta_imagen) VALUES (?, ?, ?)'
    );
    $stmt->execute([$user_id, $sensor_ir, $ruta_imagen]);
    
    $insertId = $pdo->lastInsertId();
    registrarLog("Registro sensor ID={$insertId} usuario={$user_id} ir={$sensor_ir}", 'info');
    
    jsonOk(['sensor_id' => $insertId, 'sensor_ir' => $sensor_ir, 'ruta_imagen' => $ruta_imagen]);
} catch (Exception $e) {
    registrarLog('Error registrando sensor: ' . $e->getMessage(), 'error');
    jsonErr('Error registrando sensor', 500);
}
?>
