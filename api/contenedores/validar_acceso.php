<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$headers = getallheaders();
$provided_key = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : null;
// En producción: comparar $provided_key con contenedores_registrados.api_key

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
$token = isset($input['token']) ? trim($input['token']) : '';
if (!$token) jsonErr('token missing');

$pdo = getPDO();
$stmt = $pdo->prepare("SELECT id, nombre, apellido, token_activo FROM usuarios WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) jsonErr('no_registered',404);
if (!$user['token_activo']) jsonErr('token_disabled',403);

jsonOk(['user_id'=>$user['id'],'nombre'=>$user['nombre'].' '.$user['apellido']]);
