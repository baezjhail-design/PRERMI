<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$contenedor_id = isset($input['contenedor_id']) ? intval($input['contenedor_id']) : 0;
$peso = isset($input['peso']) ? floatval($input['peso']) : 0.0;
$metal = isset($input['metal']) ? intval($input['metal']) : 0;
$credito_kwh = isset($input['credito_kwh']) ? floatval($input['credito_kwh']) : 0.0;

if (!$user_id || !$contenedor_id) jsonErr('missing');

$pdo = getPDO();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO depositos (user_id, contenedor_id, peso, metal_detectado, credito_kwh) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $contenedor_id, $peso, $metal, $credito_kwh]);

    if ($metal) {
        $desc = 'Metal detectado en depósito';
        // registrar sanción en lugar de multa
        $stmt2 = $pdo->prepare("INSERT INTO sanciones (user_id, contenedor_id, descripcion, peso) VALUES (?, ?, ?, ?)");
        $stmt2->execute([$user_id, $contenedor_id, $desc, $peso]);
        $pdo->prepare("INSERT INTO logs_sistema (descripcion, tipo) VALUES (?, 'alert')")->execute(["Sanción: usuario_id=$user_id, contenedor_id=$contenedor_id, peso=$peso"]);
    }

    $stmtU = $pdo->prepare("SELECT email, nombre, apellido FROM usuarios WHERE id = ? LIMIT 1");
    $stmtU->execute([$user_id]);
    $u = $stmtU->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    $deposit_id = $pdo->lastInsertId();
    $deposit_date = date('Y-m-d H:i:s');
    $fullName = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')) ?: ($u['email'] ?? '');

    if ($metal) {
        // Obtener admins activos
        $stmtA = $pdo->prepare("SELECT usuario AS name, email FROM usuarios_admin WHERE active = 1");
        $stmtA->execute();
        $admins = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        if (empty($admins)) $admins = [['name'=>'Admin','email'=>'admin@example.com']];
        sendAdminFineEmail($admins, $u['email'] ?? '', $fullName, $user_id, $contenedor_id, $peso);

        // Enviar reporte de sancion al usuario
        if (!empty($u['email'])) {
            // Buscar la sancion recien creada
            $stmtLastS = $pdo->prepare("SELECT id FROM sanciones WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $stmtLastS->execute([$user_id]);
            $lastS = $stmtLastS->fetch(PDO::FETCH_ASSOC);
            $sancionId = $lastS ? $lastS['id'] : 0;
            sendSanctionReportEmail($u['email'], $fullName, $sancionId, 'Metal detectado en deposito', $peso, 'Contenedor #' . $contenedor_id, $deposit_date);
        }
    }

    // Enviar factura de deposito al usuario
    if (!empty($u['email'])) {
        sendDepositNotificationEmail($u['email'], $fullName, $peso, $credito_kwh, $deposit_date, $deposit_id);
    }

    jsonOk(['metal'=>$metal]);
} catch (PDOException $e) {
    $pdo->rollBack();
    jsonErr('db error',500);
}
