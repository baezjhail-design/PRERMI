<?php
require_once __DIR__ . '/../utils.php';
header('Content-Type: application/json; charset=utf-8');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
if ($limit < 1 || $limit > 1000) $limit = 100;

if (!$user_id) {
    jsonErr('user_id requerido', 400);
}

try {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare(
        'SELECT id, user_id, relay, ventilador, peltier1, peltier2, gases, fecha FROM mediciones_biomasa WHERE user_id = ? ORDER BY fecha DESC LIMIT ?'
    );
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonOk(['registros' => $registros]);
} catch (Exception $e) {
    registrarLog('Error recuperando biomasa: ' . $e->getMessage(), 'error');
    jsonErr('Error recuperando datos de biomasa', 500);
}
?>
