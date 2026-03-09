<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM vehiculos_registrados ORDER BY id DESC");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    jsonOk(['vehiculos' => $rows]);
} catch (PDOException $e) {
    jsonErr("Error en la base de datos", 500);
}
