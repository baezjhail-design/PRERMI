<?php
require_once __DIR__ . '/../utils.php';
header('Content-Type: application/json; charset=utf-8');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

if (!$user_id) {
    jsonErr('user_id requerido', 400);
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT id, user_id, peso, sensor_metal, estado, fecha FROM mediciones WHERE user_id = ? ORDER BY fecha DESC LIMIT ?'
    );
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonOk(['mediciones' => $records]);
} catch (Exception $e) {
    jsonErr('Error obteniendo mediciones', 500);
}
?>
