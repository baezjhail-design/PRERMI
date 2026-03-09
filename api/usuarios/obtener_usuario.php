<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$pdo = getPDO();
if ($id) {
    $stmt = $pdo->prepare("SELECT id,nombre,apellido,usuario,email,telefono,cedula,token,token_activo,creado_en FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
} elseif ($token) {
    $stmt = $pdo->prepare("SELECT id,nombre,apellido,usuario,email,telefono,cedula,token,token_activo,creado_en FROM usuarios WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
} else { jsonErr('id o token requerido'); }
$user = $stmt->fetch();
if (!$user) jsonErr('No encontrado',404);
jsonOk(['user'=>$user]);
