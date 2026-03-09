<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

$limit = intval($_GET['limit'] ?? 100);

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM vehiculos_registrados ORDER BY id DESC LIMIT ?");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    jsonOk(['capturas' => $rows]);
} catch (PDOException $e) {
    jsonErr("Error en la base de datos", 500);
}
