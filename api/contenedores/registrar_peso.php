<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';
// Cargar configuración de aplicación (p.ej. tasa kWh/kg)
$appConfig = require __DIR__ . '/../../config/app_config.php';

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
$user_id = intval($input['user_id'] ?? 0);
$contenedor_id = intval($input['contenedor_id'] ?? 0);
$peso = floatval($input['peso'] ?? 0.0);

if (!$user_id || !$contenedor_id) jsonErr('missing');

$credito_por_kg = isset($appConfig['credito_kwh_por_kg']) ? floatval($appConfig['credito_kwh_por_kg']) : 0.5;
$credito_kwh = round($peso * $credito_por_kg, 5);

$pdo = getPDO();
try {
    $stmt = $pdo->prepare("INSERT INTO depositos (user_id, contenedor_id, peso, credito_kwh, creado_en) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $contenedor_id, $peso, $credito_kwh]);
    $deposit_id = $pdo->lastInsertId();

    // Obtener datos del usuario para enviar email
    $stmtU = $pdo->prepare("SELECT email, nombre, apellido FROM usuarios WHERE id = ? LIMIT 1");
    $stmtU->execute([$user_id]);
    $u = $stmtU->fetch(PDO::FETCH_ASSOC);

    // Fecha aproximada del depósito
    $deposit_date = date('Y-m-d H:i:s');

    // Enviar notificación al usuario si tiene email
    if (!empty($u['email'])) {
        $fullName = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')) ?: $u['email'];
        // Usamos la función más completa creada en config/mailer.php
        sendDepositNotificationEmail($u['email'], $fullName, $peso, $credito_kwh, $deposit_date, $deposit_id);
    }

    jsonOk(['deposit_id' => $deposit_id, 'credito_kwh' => $credito_kwh]);
} catch (PDOException $e) {
    jsonErr('db error',500);
}
