<?php
/**
 * api/admin/sanciones_stats.php
 * Endpoint — Estadisticas de sanciones para consumo AJAX
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'msg'=>'No autorizado']); exit; }
require_once __DIR__ . '/../../api/utils.php';

try {
    $pdo = getPDO();
    $st  = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='sanciones'");
    $st->execute(); $sT=$st->fetchColumn()?'sanciones':'multas';

    $q=function($sql,$p=[]) use($pdo){ $s=$pdo->prepare($sql); $s->execute($p); return intval($s->fetchColumn()); };

    $uid=intval($_GET['user_id']??0); $visto=$_GET['visto']??'all';
    $sql="SELECT s.id,s.user_id,s.descripcion,s.peso,s.creado_en,s.seen_by_admin,u.usuario FROM {$sT} s LEFT JOIN usuarios u ON u.id=s.user_id WHERE 1=1";
    $params=[];
    if($uid>0){$sql.=" AND s.user_id=?";$params[]=$uid;}
    if($visto==='0'||$visto==='1'){$sql.=" AND s.seen_by_admin=?";$params[]=intval($visto);}
    $sql.=" ORDER BY s.creado_en DESC LIMIT 200";
    $stS=$pdo->prepare($sql); $stS->execute($params);
    $sanciones=$stS->fetchAll(PDO::FETCH_ASSOC);

    $h24=date('Y-m-d H:i:s',strtotime('-24 hours'));
    $noVistas=count(array_filter($sanciones,fn($s)=>!intval($s['seen_by_admin'])));
    $hoy=count(array_filter($sanciones,fn($s)=>$s['creado_en']>=$h24));

    $porUser=[];
    foreach($sanciones as $s){$u=$s['usuario']??'#'.$s['user_id']; $porUser[$u]=($porUser[$u]??0)+1;}
    arsort($porUser); $porUser=array_slice($porUser,0,10,true);

    echo json_encode([
        'success'   => true,
        'total'     => count($sanciones),
        'no_vistas' => $noVistas,
        'hoy'       => $hoy,
        'por_usuario'=> $porUser,
        'sanciones' => $sanciones,
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
