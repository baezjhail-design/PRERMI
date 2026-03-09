<?php
require_once __DIR__ . '/../utils.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    jsonErr('JSON inválido', 400);
}

$user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
$relay = isset($data['relay']) ? (int)$data['relay'] : 0;
$ventilador = isset($data['ventilador']) ? (int)$data['ventilador'] : 0;
$peltier1 = isset($data['peltier1']) ? floatval($data['peltier1']) : 0.0;
$peltier2 = isset($data['peltier2']) ? floatval($data['peltier2']) : 0.0;
$gases = isset($data['gases']) ? floatval($data['gases']) : 0.0;

if (!$user_id) {
    jsonErr('user_id requerido', 400);
}

try {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        jsonErr('Usuario no encontrado', 404);
    }
    
    $stmt = $pdo->prepare(
        'INSERT INTO mediciones_biomasa (user_id, relay, ventilador, peltier1, peltier2, gases) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$user_id, $relay, $ventilador, $peltier1, $peltier2, $gases]);
    
    $insertId = $pdo->lastInsertId();
    registrarLog("Medición biomasa ID={$insertId} user={$user_id}", 'info');
    
    jsonOk(['biomasa_id' => $insertId, 'relay' => $relay, 'ventilador' => $ventilador]);
} catch (Exception $e) {
    registrarLog('Error registrando biomasa: ' . $e->getMessage(), 'error');
    jsonErr('Error registrando medición biomasa', 500);
}
?>
