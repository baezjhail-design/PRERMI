<?php
require_once "../utils.php";
require_once __DIR__ . '/../../config/mailer.php';

requireAdminSession();
$pdo = getPDO();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['contenedor_id'])) {
    jsonErr("Datos incompletos");
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO sanciones 
        (user_id, contenedor_id, descripcion, peso)
        VALUES (?, ?, ?, ?)
    ");

    $desc = limpiar($data['descripcion'] ?? "Sanción administrativa");
    $pesoVal = $data['peso'] ?? null;

    $stmt->execute([
        limpiar($data['user_id']),
        limpiar($data['contenedor_id']),
        $desc,
        $pesoVal
    ]);

    $sancion_id = $pdo->lastInsertId();

    registrarLog("Admin creó sanción a usuario ID: " . $data['user_id'], "warning");

    // Enviar reporte de sancion al usuario por email
    try {
        $stmtU = $pdo->prepare("SELECT email, nombre, apellido FROM usuarios WHERE id = ? LIMIT 1");
        $stmtU->execute([intval($data['user_id'])]);
        $u = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($u && !empty($u['email'])) {
            $fullName = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')) ?: $u['email'];
            sendSanctionReportEmail($u['email'], $fullName, $sancion_id, $desc, $pesoVal, 'Contenedor #' . $data['contenedor_id'], date('Y-m-d H:i:s'));
        }
    } catch (Exception $emailEx) {
        error_log("[EMAIL] Error enviando reporte sancion: " . $emailEx->getMessage());
    }

    jsonOk(["msg" => "Sanción creada correctamente", "sancion_id" => $sancion_id]);

} catch (Exception $e) {
    jsonErr("Error creando sanción", 500);
}