<?php
// registrar_depositos.php (actualizado)
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Leer cuerpo JSON o form-data
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

if (!isset($data['id_usuario']) || !isset($data['id_contenedor']) || !isset($data['peso'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Parámetros incompletos"]);
    exit;
}

$id_usuario = intval($data['id_usuario']);
$id_contenedor = intval($data['id_contenedor']);
$peso = floatval($data['peso']);
$token_usado = isset($data['token_usado']) ? $conn->real_escape_string($data['token_usado']) : "";
$tipo_residuo = isset($data['tipo_residuo']) ? $conn->real_escape_string($data['tipo_residuo']) : null;
$credito_kwh = isset($data['credito_kwh']) ? floatval($data['credito_kwh']) : null;
$metal_detectado = isset($data['metal_detectado']) ? intval($data['metal_detectado']) : 0;
$fecha_hora = date('Y-m-d H:i:s');
$procesado_por = isset($data['procesado_por']) ? $conn->real_escape_string($data['procesado_por']) : null;
$observaciones = isset($data['observaciones']) ? $conn->real_escape_string($data['observaciones']) : null;
$contenedor_id = isset($data['contenedor_id']) ? intval($data['contenedor_id']) : $id_contenedor;
$creado_en = date('Y-m-d H:i:s');

$sql = "INSERT INTO depositos (id_usuario, id_contenedor, token_usado, peso, tipo_residuo, credito_kwh, metal_detectado, fecha_hora, procesado_por, observaciones, contenedor_id, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error preparando sentencia: " . $conn->error]);
    exit;
}

$types = "iisdsdisssis";
$stmt->bind_param($types,
    $id_usuario,
    $id_contenedor,
    $token_usado,
    $peso,
    $tipo_residuo,
    $credito_kwh,
    $metal_detectado,
    $fecha_hora,
    $procesado_por,
    $observaciones,
    $contenedor_id,
    $creado_en
);

if ($stmt->execute()) {
    $deposito_id = $stmt->insert_id;

    // Enviar factura de deposito por email
    try {
        $stmtU = $conn->prepare("SELECT email, nombre, apellido FROM usuarios WHERE id = ? LIMIT 1");
        $stmtU->bind_param("i", $id_usuario);
        $stmtU->execute();
        $resultU = $stmtU->get_result();
        $u = $resultU->fetch_assoc();
        if (!empty($u['email'])) {
            $fullName = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')) ?: $u['email'];
            $creditoVal = $credito_kwh ?? round($peso * 0.5, 4);
            sendDepositNotificationEmail($u['email'], $fullName, $peso, $creditoVal, $creado_en, $deposito_id);

            // Si se detecto metal, enviar reporte de sancion
            if ($metal_detectado) {
                $desc = 'Metal detectado en deposito';
                $stmtSanc = $conn->prepare("INSERT INTO sanciones (user_id, contenedor_id, descripcion, peso) VALUES (?, ?, ?, ?)");
                $stmtSanc->bind_param("iiss", $id_usuario, $id_contenedor, $desc, $peso);
                $stmtSanc->execute();
                $sancion_id_local = $stmtSanc->insert_id;
                $stmtSanc->close();
                sendSanctionReportEmail($u['email'], $fullName, $sancion_id_local, $desc, $peso, 'Contenedor #' . $id_contenedor, $creado_en);
            }
        }
        $stmtU->close();
    } catch (Exception $emailEx) {
        error_log("[EMAIL] Error enviando notificacion deposito: " . $emailEx->getMessage());
    }

    // === REPLICAR A INFINITYFREE ===
    // Nota: sql208.infinityfree.com solo es accesible desde la red de InfinityFree.
    // Desde localhost, el ESP32 ya envía datos directamente a ambos servidores,
    // por lo que la replicación PHP solo tiene sentido cuando estamos en InfinityFree
    // (y allí la DB remota ES la DB local, así que tampoco se necesita).
    $remote_id = null;
    if ($isInfinityFree) {
        try {
            $conn_remote = new mysqli($DB_HOST_REMOTE, $DB_USER_REMOTE, $DB_PASS_REMOTE, $DB_NAME_REMOTE);
            if (!$conn_remote->connect_error) {
                $stmt_r = $conn_remote->prepare($sql);
                if ($stmt_r) {
                    $stmt_r->bind_param($types,
                        $id_usuario, $id_contenedor, $token_usado, $peso,
                        $tipo_residuo, $credito_kwh, $metal_detectado, $fecha_hora,
                        $procesado_por, $observaciones, $contenedor_id, $creado_en
                    );
                    $stmt_r->execute();
                    $remote_id = $stmt_r->insert_id;
                    $stmt_r->close();
                }
                $conn_remote->close();
            }
        } catch (Exception $e) {
            error_log("[REMOTE DB] Error replicando deposito: " . $e->getMessage());
        }
    }

    echo json_encode([
        "success" => true,
        "deposito_id" => $deposito_id,
        "remote_id" => $remote_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al registrar depósito: " . $stmt->error]);
}

?>