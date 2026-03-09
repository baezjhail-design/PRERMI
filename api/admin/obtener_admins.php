<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, nombre, correo, verificado, created_at FROM usuarios_admin ORDER BY id DESC");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    jsonOk(['admins' => $rows]);
} catch (PDOException $e) {
    jsonErr("Error en la base de datos", 500);
}
