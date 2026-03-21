<?php
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// =============================
// LEER JSON
// =============================
$raw = file_get_contents('php://input');

if (!$raw) {
    jsonErr('No se recibió payload', 400);
}

$data = json_decode($raw, true);

if (!is_array($data)) {
    jsonErr('JSON inválido', 400);
}

// =============================
// COMPATIBILIDAD ESP32
// =============================
$usuario_id = isset($data['usuario_id']) ? (int)$data['usuario_id'] :
              (isset($data['user_id']) ? (int)$data['user_id'] : null);

$peso = isset($data['peso']) ? (float)$data['peso'] : null;
$id_contenedor = isset($data['id_contenedor']) ? (int)$data['id_contenedor'] : 0;
$token_usado = isset($data['token_usado']) ? limpiar($data['token_usado']) : '';
$metal_detectado = isset($data['inductivo']) ? (int)$data['inductivo'] :
                   (isset($data['metal_detectado']) ? (int)$data['metal_detectado'] : 0);

// tipo_residuo: 'metal' si se detectó metal, 'organico' en caso contrario (a menos que el ESP32 envíe otro valor)
$tipo_residuo = isset($data['tipo_residuo']) ? trim($data['tipo_residuo']) : ($metal_detectado ? 'metal' : 'organico');
if (!in_array($tipo_residuo, ['organico', 'metal', 'electronico', 'plastico', 'vidrio', 'papel'], true)) {
    $tipo_residuo = $metal_detectado ? 'metal' : 'organico';
}

// credito_kwh: usar valor enviado por el ESP32, o calcular a 0.0011 kWh/kg
const KG_TO_KWH = 0.0011;
$credito_kwh = isset($data['credito_kwh']) ? round((float)$data['credito_kwh'], 5) : round($peso * KG_TO_KWH, 5);

// =============================
// VALIDACIONES
// =============================
if (!$usuario_id || $peso === null) {
    jsonErr('Faltan campos requeridos: usuario_id y peso', 400);
}

if ($peso <= 0) {
    jsonErr('Peso inválido', 400);
}

try {

    $pdo = getPDO();

    // =============================
    // VALIDAR USUARIO
    // =============================
    $checkUser = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $checkUser->execute([$usuario_id]);

    if (!$checkUser->fetch()) {
        jsonErr('Usuario no encontrado', 404);
    }

    // =============================
    // VALIDAR CONTENEDOR (si aplica)
    // =============================
    // La tabla `contenedores_registrados` fue eliminada, por lo que no
    // validamos la existencia del contenedor en la BD. Si se envía
    // un `id_contenedor` lo guardamos tal cual en `depositos`.

    // =============================
    // INSERTAR DEPÓSITO
    // =============================
    $stmt = $pdo->prepare("
        INSERT INTO depositos 
        (id_usuario, id_contenedor, token_usado, peso, tipo_residuo, credito_kwh, metal_detectado)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $usuario_id,
        $id_contenedor,
        $token_usado,
        $peso,
        $tipo_residuo,
        $credito_kwh,
        $metal_detectado
    ]);

    $insertId = $pdo->lastInsertId();

    registrarLog(
        "Depósito registrado ID={$insertId} usuario={$usuario_id} peso={$peso}",
        'info'
    );

    // Enviar factura de deposito por email al usuario
    try {
        $stmtU = $pdo->prepare("SELECT email, nombre, apellido FROM usuarios WHERE id = ? LIMIT 1");
        $stmtU->execute([$usuario_id]);
        $u = $stmtU->fetch(PDO::FETCH_ASSOC);
        if (!empty($u['email'])) {
            $fullName = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')) ?: $u['email'];
            sendDepositNotificationEmail($u['email'], $fullName, $peso, $credito_kwh, date('Y-m-d H:i:s'), $insertId);
        }

        // Si se detecto metal, enviar reporte de sancion
        if ($metal_detectado) {
            $desc = 'Metal detectado en deposito';
            $stmtS = $pdo->prepare("INSERT INTO sanciones (user_id, contenedor_id, descripcion, peso) VALUES (?, ?, ?, ?)");
            $stmtS->execute([$usuario_id, $id_contenedor, $desc, $peso]);
            $sancion_id = $pdo->lastInsertId();

            if (!empty($u['email'])) {
                sendSanctionReportEmail($u['email'], $fullName, $sancion_id, $desc, $peso, 'Contenedor #' . $id_contenedor, date('Y-m-d H:i:s'));
            }
        }
    } catch (Exception $emailEx) {
        registrarLog('Email notification failed: ' . $emailEx->getMessage(), 'warning');
    }

    jsonOk([
        'deposito_id' => $insertId,
        'peso' => $peso
    ]);

} catch (Exception $e) {

    registrarLog(
        'Error insertando depósito: ' . $e->getMessage(),
        'error'
    );

    jsonErr('Error interno del servidor', 500);
}