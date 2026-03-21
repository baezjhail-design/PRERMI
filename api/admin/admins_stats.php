<?php
/**
 * api/admin/admins_stats.php
 * Endpoint — Estadisticas de admins y usuarios para consumo AJAX
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'msg'=>'No autorizado']); exit; }
require_once __DIR__ . '/../../api/utils.php';
require_once __DIR__ . '/../../api/data_policy.php';

try {
    $pdo  = getPDO();
    $rol  = getSessionRole(); // 'admin' | 'superadmin'

    // Fetch máximo de campos; data_policy filtrará según rol
    $admins  = $pdo->query("SELECT id,usuario,nombre,apellido,email,rol,verified,active,creado_en FROM usuarios_admin ORDER BY creado_en DESC")->fetchAll(PDO::FETCH_ASSOC);
    $usuarios = $pdo->query("SELECT id,usuario,nombre,apellido,email,telefono,cedula,verified,COALESCE(activo,1) activo,creado_en FROM usuarios ORDER BY creado_en DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'        => true,
        'rol'            => $rol,
        'total_admins'   => count($admins),
        'pend_admins'    => count(array_filter($admins, fn($a) => !intval($a['active']))),
        'total_usuarios' => count($usuarios),
        'baneados'       => count(array_filter($usuarios, fn($u) => !intval($u['activo']))),
        'verificados'    => count(array_filter($usuarios, fn($u) => intval($u['verified']))),
        'admins'         => filterAdmins($admins, $rol),
        'usuarios'       => filterUsers($usuarios, $rol),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Error interno']);
}
