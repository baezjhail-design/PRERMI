<?php
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../api/data_policy.php';

header("Content-Type: application/json; charset=UTF-8");

session_start();
if (empty($_SESSION['admin_id'])) {
    jsonErr('No autorizado', 401);
}

try {
    $pdo  = getPDO();
    $rol  = getSessionRole();

    $stmt = $pdo->prepare(
        "SELECT id, usuario, nombre, apellido, email, rol, verified, active, creado_en
         FROM usuarios_admin ORDER BY id DESC"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonOk(['admins' => filterAdmins($rows, $rol)]);
} catch (PDOException $e) {
    jsonErr('Error en la base de datos', 500);
}
