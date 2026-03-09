<?php
// similar a registrar_basura.php pero crea una fila en sanciones y notifica admins
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['user_id'] ?? 0);
$contenedor_id = intval($input['contenedor_id'] ?? 0);
$peso = floatval($input['peso'] ?? 0.0);
$desc = isset($input['descripcion']) ? trim($input['descripcion']) : 'Multa generada';

if (!$user_id || !$contenedor_id) jsonErr('missing');

$pdo = getPDO();
try {
    // registrar en nueva tabla sanciones en lugar de multas
    $stmt = $pdo->prepare("INSERT INTO sanciones (user_id, contenedor_id, descripcion, peso) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $contenedor_id, $desc, $peso]);

    // notify admins
    $stmtU = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id = ? LIMIT 1");
    $stmtU->execute([$user_id]);
    $u = $stmtU->fetch();
    $stmtA = $pdo->prepare("SELECT usuario AS name, email FROM usuarios_admin WHERE active = 1");
    $stmtA->execute();
    $admins = $stmtA->fetchAll(PDO::FETCH_ASSOC);
    if (empty($admins)) $admins = [['name'=>'Admin','email'=>'admin@example.com']];

    sendAdminFineEmail($admins, $u['email'], $u['nombre'], $user_id, $contenedor_id, $peso);

    jsonOk();
} catch (PDOException $e) {
    jsonErr('db error',500);
}
