<?php
require_once __DIR__ . '/../utils.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    jsonErr('JSON inválido', 400);
}

$user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
$peso = isset($data['peso']) ? floatval($data['peso']) : null;
$sensor_metal = isset($data['sensor_metal']) ? (int)$data['sensor_metal'] : 0;
$estado = isset($data['estado']) ? limpiar($data['estado']) : 'disponible';

if (!$user_id || $peso === null) {
    jsonErr('user_id y peso requeridos', 400);
}

try {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        jsonErr('Usuario no encontrado', 404);
    }
    
    $stmt = $pdo->prepare(
        'INSERT INTO mediciones (user_id, peso, sensor_metal, estado) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$user_id, $peso, $sensor_metal, $estado]);
    
    $insertId = $pdo->lastInsertId();
    registrarLog("Medición registrada ID={$insertId} user={$user_id} peso={$peso}", 'info');
    
    jsonOk(['medicion_id' => $insertId, 'peso' => $peso, 'estado' => $estado]);
} catch (Exception $e) {
    registrarLog('Error registrando medición: ' . $e->getMessage(), 'error');
    jsonErr('Error registrando medición', 500);
}
?>
