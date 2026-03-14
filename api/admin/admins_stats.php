<?php
/**
 * api/admin/admins_stats.php
 * Endpoint — Estadisticas de admins y usuarios para consumo AJAX
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'msg'=>'No autorizado']); exit; }
require_once __DIR__ . '/../../api/utils.php';

try {
    $pdo = getPDO();
    $stA=$pdo->prepare("SELECT rol FROM usuarios_admin WHERE id=? LIMIT 1"); $stA->execute([$_SESSION['admin_id']]); $myRol=$stA->fetchColumn()||'admin';

    $admins=$pdo->query("SELECT id,usuario,email,rol,verified,active,creado_en FROM usuarios_admin ORDER BY creado_en DESC")->fetchAll(PDO::FETCH_ASSOC);
    $usuarios=$pdo->query("SELECT id,usuario,nombre,apellido,email,verified,COALESCE(activo,1) activo,creado_en FROM usuarios ORDER BY creado_en DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'       => true,
        'rol'           => $myRol,
        'total_admins'  => count($admins),
        'pend_admins'   => count(array_filter($admins,fn($a)=>!intval($a['active']))),
        'total_usuarios'=> count($usuarios),
        'baneados'      => count(array_filter($usuarios,fn($u)=>!intval($u['activo']))),
        'verificados'   => count(array_filter($usuarios,fn($u)=>intval($u['verified']))),
        'admins'        => $admins,
        'usuarios'      => $usuarios,
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
