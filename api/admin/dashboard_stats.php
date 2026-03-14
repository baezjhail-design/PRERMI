<?php
/**
 * api/admin/dashboard_stats.php
 * Endpoint — Estadisticas del dashboard (ultimas 24h) para consumo AJAX
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'msg'=>'No autorizado']); exit; }
require_once __DIR__ . '/../../api/utils.php';

try {
    $pdo  = getPDO();
    $h24  = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $sT   = function() use($pdo){ $s=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='sanciones'"); $s->execute(); return $s->fetchColumn()?'sanciones':'multas'; };
    $san  = $sT();
    $qi   = fn($sql,$p=[])=>intval($pdo->prepare($sql)->execute($p)? (($st=$pdo->prepare($sql))&&$st->execute($p) ? intval($st->fetchColumn()):0) : 0);

    // Query helper
    $q = function($sql,$p=[]) use($pdo){ $s=$pdo->prepare($sql); $s->execute($p); return intval($s->fetchColumn()); };
    $qf= function($sql,$p=[]) use($pdo){ $s=$pdo->prepare($sql); $s->execute($p); return floatval($s->fetchColumn()); };

    echo json_encode([
        'success'       => true,
        'ts'            => date('Y-m-d H:i:s'),
        'capturas_24h'  => $q("SELECT COUNT(*) FROM vehiculos_registrados WHERE creado_en>=?",[$h24]),
        'depositos_24h' => $q("SELECT COUNT(*) FROM depositos WHERE COALESCE(creado_en,fecha_hora)>=?",[$h24]),
        'sanciones_24h' => $q("SELECT COUNT(*) FROM {$san} WHERE creado_en>=?",[$h24]),
        'kwh_24h'       => round($qf("SELECT COALESCE(SUM(credito_kwh),0) FROM depositos WHERE COALESCE(creado_en,fecha_hora)>=?",[$h24]),3),
        'rojo_24h'      => $q("SELECT COUNT(*) FROM capturas_semaforo_rojo WHERE creado_en>=?",[$h24]),
        'no_vistas'     => $q("SELECT COUNT(*) FROM {$san} WHERE seen_by_admin=0"),
        'pend_admins'   => $q("SELECT COUNT(*) FROM usuarios_admin WHERE active=0"),
        'total_capturas'=> $q("SELECT COUNT(*) FROM vehiculos_registrados"),
        'total_depositos'=> $q("SELECT COUNT(*) FROM depositos"),
        'total_usuarios'=> $q("SELECT COUNT(*) FROM usuarios"),
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
