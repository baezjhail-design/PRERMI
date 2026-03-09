<?php
require_once __DIR__ . '/../utils.php';
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

if (!$user_id) {
    jsonErr('user_id requerido', 400);
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT id, user_id, sensor_ir, ruta_imagen, fecha FROM sensores WHERE user_id = ? ORDER BY fecha DESC LIMIT ?'
    );
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonOk(['sensores' => $records]);
} catch (Exception $e) {
    jsonErr('Error obteniendo sensores', 500);
}
?>
