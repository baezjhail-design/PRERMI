<?php
require_once "../utils.php";

requireAdminSession();
$pdo = getPDO();

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonErr("ID requerido");
}

try {

    $stmt = $pdo->prepare("
        SELECT * FROM sanciones WHERE id = ?
    ");

    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        jsonErr("Sanción no encontrada", 404);
    }

    jsonOk(["data" => $data]);

} catch (Exception $e) {
    jsonErr("Error interno", 500);
}