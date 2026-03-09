<?php
require_once "../utils.php";

requireAdminSession();
$pdo = getPDO();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    jsonErr("ID requerido");
}

try {

    $stmt = $pdo->prepare("
        UPDATE sanciones 
        SET seen_by_admin = 1
        WHERE id = ?
    ");

    $stmt->execute([$data['id']]);

    jsonOk(["msg" => "Marcada como vista"]);

} catch (Exception $e) {
    jsonErr("Error actualizando", 500);
}