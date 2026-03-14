<?php
/**
 * api/admin/monitoreo_stats.php
 * Endpoint — Estadisticas de monitoreo vehicular para consumo AJAX
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'msg'=>'No autorizado']); exit; }
require_once __DIR__ . '/../../api/utils.php';

try {
    $pdo = getPDO();
    $periodo = $_GET['periodo'] ?? '7d';
    $where = match($periodo) {
        '24h' => "WHERE v.creado_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
        '3d'  => "WHERE v.creado_en >= DATE_SUB(NOW(), INTERVAL 3 DAY)",
        '7d'  => "WHERE v.creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        '30d' => "WHERE v.creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        default => ""
    };

    $capturas = $pdo->query("SELECT id,placa,tipo_vehiculo,ubicacion,probabilidad,latitud,longitud,creado_en FROM vehiculos_registrados $where ORDER BY creado_en DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    $rojoMap  = [];
    foreach($pdo->query("SELECT vehiculo_id FROM capturas_semaforo_rojo")->fetchAll(PDO::FETCH_ASSOC) as $r){
        $rojoMap[intval($r['vehiculo_id'])]=true;
    }

    $tipoCount=[];
    foreach($capturas as $c){ $t=$c['tipo_vehiculo']??'Desconocido'; $tipoCount[$t]=($tipoCount[$t]??0)+1; }

    echo json_encode([
        'success'   => true,
        'periodo'   => $periodo,
        'total'     => count($capturas),
        'rojo'      => count(array_filter($capturas,fn($c)=>isset($rojoMap[intval($c['id'])]))),
        'high_conf' => count(array_filter($capturas,fn($c)=>floatval($c['probabilidad'])>=0.8)),
        'tipos'     => $tipoCount,
        'capturas'  => $capturas,
        'rojo_ids'  => array_map('intval',array_keys($rojoMap)),
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
