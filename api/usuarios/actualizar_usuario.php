<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['id'])) jsonErr('id requerido');

$id = intval($input['id']);
$fields = [];
$params = [];
if (isset($input['telefono'])) { $fields[] = "telefono = ?"; $params[] = trim($input['telefono']); }
if (isset($input['token_activo'])) { $fields[] = "token_activo = ?"; $params[] = intval($input['token_activo']); }

if (empty($fields)) jsonErr('Nada que actualizar');
$params[] = $id;

$pdo = getPDO();
$stmt = $pdo->prepare("UPDATE usuarios SET " . implode(", ", $fields) . " WHERE id = ?");
try {
    $stmt->execute($params);
    jsonOk();
} catch (PDOException $e) {
    jsonErr('db error',500);
}
