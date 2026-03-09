<?php
// api/admin/get_pending_admins.php - Obtener admins pendientes de aprobación

require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");

// Verificar que sea sesión de admin
session_start();
if (empty($_SESSION['admin_id'])) {
    jsonErr('No autorizado', 401);
}

try {
    $pdo = getPDO();
    
    // Obtener admins pendientes: todos los no verificados OR verificados pero no activos
    $stmt = $pdo->prepare(
        "SELECT id, usuario, nombre, apellido, email, verified, active, rol, creado_en 
         FROM usuarios_admin 
         WHERE (verified = 1 AND active = 0) OR (verified = 0)
         ORDER BY creado_en DESC"
    );
    $stmt->execute();
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonOk(['pending_admins' => $pending, 'count' => count($pending)]);
    
} catch (PDOException $e) {
    error_log('Get pending admins error: ' . $e->getMessage());
    jsonErr('Error al obtener datos', 500);
}
