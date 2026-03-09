<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_GET['token'])) {
    jsonErr("Token no recibido", 400);
}

$token = trim($_GET['token']);

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, verified FROM usuarios_admin WHERE verification_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $data = $stmt->fetch();

    if (!$data) {
        jsonErr("Token inválido", 404);
    }

    if ($data['verified'] == 1) {
        jsonOk(["msg" => "La cuenta ya estaba verificada"]);
    }

    // Marcar como verificado
    $upd = $pdo->prepare("UPDATE usuarios_admin SET verified = 1 WHERE id = ?");
    $upd->execute([$data['id']]);

    jsonOk(["msg" => "Cuenta verificada con éxito"]);
} catch (PDOException $e) {
    jsonErr("Error en la base de datos", 500);
}
