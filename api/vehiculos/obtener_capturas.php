<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

$limit = intval($_GET['limit'] ?? 100);
$limit = max(1, min($limit, 500));
$evento = strtolower(trim((string)($_GET['evento'] ?? 'all')));

try {
    $pdo = getPDO();

    $where = '';
    if ($evento === 'semaforo_rojo') {
        $where = 'WHERE csr.vehiculo_id IS NOT NULL';
    } elseif ($evento === 'normal' || $evento === 'vehiculo_detectado') {
        $where = 'WHERE csr.vehiculo_id IS NULL';
    }

    $sql = "SELECT v.*, CASE WHEN csr.vehiculo_id IS NULL THEN 'vehiculo_detectado' ELSE 'semaforo_rojo' END AS evento
            FROM vehiculos_registrados v
            LEFT JOIN capturas_semaforo_rojo csr ON csr.vehiculo_id = v.id
            $where
            ORDER BY v.id DESC
            LIMIT ?";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    jsonOk(['capturas' => $rows, 'filtro_evento' => $evento]);
} catch (PDOException $e) {
    jsonErr("Error en la base de datos", 500);
}
