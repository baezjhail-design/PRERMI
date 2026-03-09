<?php
require_once "../utils.php";
require_once __DIR__ . '/../../config/mailer.php';
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$pdo = getPDO();

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

if (!isset($data['user_id'], $data['contenedor_id'])) {
    jsonErr("Datos incompletos");
}

$user_id = intval($data['user_id']);
$contenedor_id = intval($data['contenedor_id']);
$descripcion = $data['descripcion'] ?? "Metal detectado";
$peso = isset($data['peso']) ? floatval($data['peso']) : null;

try {
    $stmt = $pdo->prepare("INSERT INTO sanciones (user_id, contenedor_id, descripcion, peso) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $contenedor_id, $descripcion, $peso]);
    $sancion_id = $pdo->lastInsertId();

    // Enviar reporte de sancion al usuario por email
    try {
        $stmtU = $pdo->prepare("SELECT email, nombre, apellido FROM usuarios WHERE id = ? LIMIT 1");
        $stmtU->execute([$user_id]);
        $u = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($u && !empty($u['email'])) {
            $fullName = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')) ?: $u['email'];
            sendSanctionReportEmail($u['email'], $fullName, $sancion_id, $descripcion, $peso, 'Contenedor #' . $contenedor_id, date('Y-m-d H:i:s'));
        }
    } catch (Exception $emailEx) {
        error_log("[EMAIL] Error enviando reporte sancion auto: " . $emailEx->getMessage());
    }

    // === REPLICAR A INFINITYFREE ===
    // Solo intentar si estamos en InfinityFree (sql208 no es accesible desde localhost).
    // El ESP32 ya envía a ambos servidores directamente.
    $remote_id = null;
    global $isInfinityFree;
    if ($isInfinityFree) {
        $pdo_remote = getPDORemote();
        if ($pdo_remote) {
            try {
                $stmt_r = $pdo_remote->prepare("INSERT INTO sanciones (user_id, contenedor_id, descripcion, peso) VALUES (?, ?, ?, ?)");
                $stmt_r->execute([$user_id, $contenedor_id, $descripcion, $peso]);
                $remote_id = $pdo_remote->lastInsertId();
            } catch (Exception $re) {
                error_log("[REMOTE DB] Error replicando sancion: " . $re->getMessage());
            }
        }
    }

    jsonOk(["msg" => "Sanción automática creada", "sancion_id" => $sancion_id, "remote_id" => $remote_id]);
} catch (Exception $e) {
    jsonErr("Error al crear sanción: " . $e->getMessage(), 500);
}