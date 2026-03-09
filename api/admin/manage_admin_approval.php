<?php
// api/admin/manage_admin_approval.php - Aprobar o rechazar administradores

require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");

// Verificar autenticación
session_start();
if (empty($_SESSION['admin_id'])) {
    jsonErr('No autorizado', 401);
}

// Validar que solo superadmins puedan aprobar
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT rol FROM usuarios_admin WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin || $admin['rol'] !== 'superadmin') {
    jsonErr('Solo superadministradores pueden aprobar usuarios', 403);
}

// Leer entrada
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonErr('Datos inválidos', 400);
}

$admin_id = isset($input['admin_id']) ? intval($input['admin_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : ''; // 'approve' 'reject' or 'force_activate'

if (!$admin_id || !in_array($action, ['approve', 'reject', 'force_activate'])) {
    jsonErr('Parámetros inválidos', 400);
}

try {
    // Obtener datos del admin a procesar
    $stmt = $pdo->prepare("SELECT usuario, email FROM usuarios_admin WHERE id = ?");
    $stmt->execute([$admin_id]);
    $target_admin = $stmt->fetch();
    
    if (!$target_admin) {
        jsonErr('Administrador no encontrado', 404);
    }
    
    if ($action === 'approve') {
        // Activar admin (usuario ya verificado)
        $stmt = $pdo->prepare("UPDATE usuarios_admin SET active = 1 WHERE id = ?");
        $stmt->execute([$admin_id]);
        
        registrarLog("Administrador aprobado: {$target_admin['usuario']}", "info");
        jsonOk(['message' => "Administrador {$target_admin['usuario']} aprobado exitosamente"]);
        
    } elseif ($action === 'reject') {
        // Eliminar admin rechazado
        $stmt = $pdo->prepare("DELETE FROM usuarios_admin WHERE id = ?");
        $stmt->execute([$admin_id]);
        
        registrarLog("Administrador rechazado: {$target_admin['usuario']}", "info");
        jsonOk(['message' => "Administrador {$target_admin['usuario']} rechazado y eliminado"]);
    } elseif ($action === 'force_activate') {
        // Forzar activación: marcar verified=1 y active=1
        $stmt = $pdo->prepare("UPDATE usuarios_admin SET verified = 1, active = 1 WHERE id = ?");
        $stmt->execute([$admin_id]);

        registrarLog("Administrador forzado activado: {$target_admin['usuario']}", "info");
        jsonOk(['message' => "Administrador {$target_admin['usuario']} ahora está verificado y activo"]);
    }
    
} catch (PDOException $e) {
    error_log('Admin approval error: ' . $e->getMessage());
    jsonErr('Error procesando solicitud', 500);
}
