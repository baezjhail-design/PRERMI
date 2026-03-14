<?php
/**
 * api/admin/depositos_stats.php
 * Endpoint — Estadisticas de depositos para consumo AJAX
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'msg'=>'No autorizado']); exit; }
require_once __DIR__ . '/../../api/utils.php';

try {
    $pdo    = getPDO();
    $TARIFA = 14.00;
    $periodo= $_GET['periodo'] ?? '30d';
    $where  = match($periodo){
        '24h' => "COALESCE(d.creado_en,d.fecha_hora)>=DATE_SUB(NOW(),INTERVAL 24 HOUR)",
        '7d'  => "COALESCE(d.creado_en,d.fecha_hora)>=DATE_SUB(NOW(),INTERVAL 7 DAY)",
        '30d' => "COALESCE(d.creado_en,d.fecha_hora)>=DATE_SUB(NOW(),INTERVAL 30 DAY)",
        '90d' => "COALESCE(d.creado_en,d.fecha_hora)>=DATE_SUB(NOW(),INTERVAL 90 DAY)",
        default => "1=1"
    };

    $s=$pdo->query("SELECT d.id,d.id_usuario,d.peso,d.credito_kwh,d.metal_detectado,d.tipo_residuo,COALESCE(d.creado_en,d.fecha_hora) ts,u.usuario,c.codigo_contenedor,c.latitud,c.longitud,c.ubicacion FROM depositos d LEFT JOIN usuarios u ON u.id=d.id_usuario LEFT JOIN contenedores_registrados c ON c.id=d.id_contenedor WHERE $where ORDER BY ts DESC LIMIT 300");
    $deps=$s->fetchAll(PDO::FETCH_ASSOC);

    $totalKwh=array_sum(array_map(fn($d)=>floatval($d['credito_kwh']),$deps));
    $totalPeso=array_sum(array_map(fn($d)=>floatval($d['peso']),$deps));
    $metal=count(array_filter($deps,fn($d)=>intval($d['metal_detectado'])===1));
    $tipoRes=[]; foreach($deps as $d){ $t=$d['tipo_residuo']??'N/A'; $tipoRes[$t]=($tipoRes[$t]??0)+1; }

    // 6-month history
    $savRows=$pdo->query("SELECT DATE_FORMAT(COALESCE(creado_en,fecha_hora),'%Y-%m') mes,SUM(COALESCE(credito_kwh,0)) kwh,COUNT(*) cnt FROM depositos WHERE COALESCE(creado_en,fecha_hora)>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes")->fetchAll(PDO::FETCH_ASSOC);
    $savMap=[]; foreach($savRows as $r) $savMap[$r['mes']]=['kwh'=>floatval($r['kwh']),'cnt'=>intval($r['cnt'])];
    $mLabels=$mKwh=$mCnt=[];
    for($i=5;$i>=0;$i--){ $ts=strtotime("-{$i} months"); $k=date('Y-m',$ts); $mLabels[]=date('M Y',$ts); $mKwh[]=$savMap[$k]['kwh']??0; $mCnt[]=$savMap[$k]['cnt']??0; }

    echo json_encode([
        'success'     => true,
        'periodo'     => $periodo,
        'total'       => count($deps),
        'kwh'         => round($totalKwh,4),
        'rd'          => round($totalKwh*$TARIFA,2),
        'peso_total'  => round($totalPeso,3),
        'con_metal'   => $metal,
        'tipo_residuo'=> $tipoRes,
        'mes_labels'  => $mLabels,
        'mes_kwh'     => $mKwh,
        'mes_cnt'     => $mCnt,
        'depositos'   => array_map(fn($d)=>['id'=>$d['id'],'usuario'=>$d['usuario'],'contenedor'=>$d['codigo_contenedor'],'lat'=>$d['latitud'],'lng'=>$d['longitud'],'peso'=>$d['peso'],'kwh'=>$d['credito_kwh'],'ts'=>$d['ts']],$deps),
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
