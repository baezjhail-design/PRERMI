<?php
require_once "../utils.php";

requireAdminSession();
$pdo = getPDO();

try {

    $total = $pdo->query("SELECT COUNT(*) FROM sanciones")->fetchColumn();

    $hoy = $pdo->query("
        SELECT COUNT(*) 
        FROM sanciones 
        WHERE DATE(creado_en) = CURDATE()
    ")->fetchColumn();

    $noVistas = $pdo->query("
        SELECT COUNT(*) 
        FROM sanciones 
        WHERE seen_by_admin = 0
    ")->fetchColumn();

    jsonOk([
        "total" => $total,
        "hoy" => $hoy,
        "pendientes" => $noVistas
    ]);

} catch (Exception $e) {
    jsonErr("Error obteniendo estadísticas", 500);
}