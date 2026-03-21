<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");
// CORS restringido para reducir exposición del endpoint.
$allowedOrigins = [
    'https://prermi.duckdns.org',
    'http://prermi.duckdns.org',
    'http://localhost',
    'http://127.0.0.1'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$pdo = getPDO();
if ($id) {
    // Respuesta mínima: no incluir token, cédula ni otros datos sensibles.
    $stmt = $pdo->prepare("SELECT id,nombre,apellido,usuario FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
} elseif ($token) {
    $stmt = $pdo->prepare("SELECT id,nombre,apellido,usuario FROM usuarios WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
} else { jsonErr('id o token requerido'); }
$user = $stmt->fetch();
if (!$user) jsonErr('No encontrado',404);
jsonOk(['user'=>$user]);
