<?php
/**
 * Script de limpieza para pruebas:
 * Elimina 1 admin y 1 usuario común de la BD.
 *
 * Uso por CLI (recomendado):
 *   php scripts/eliminar_usuarios_prueba.php --confirm=SI
 *   php scripts/eliminar_usuarios_prueba.php --confirm=SI --admin_id=12 --user_id=25
 *   php scripts/eliminar_usuarios_prueba.php --confirm=SI --admin_email=admin@test.com --user_email=user@test.com
 *
 * Uso vía web (solo si realmente lo necesitas):
 *   /PRERMI/scripts/eliminar_usuarios_prueba.php?confirm=SI
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json; charset=UTF-8');

function out(array $payload, int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getParam(string $key): ?string {
    if (PHP_SAPI === 'cli') {
        $opts = getopt('', [
            'confirm::',
            'admin_id::',
            'user_id::',
            'admin_email::',
            'user_email::'
        ]);
        if (isset($opts[$key])) {
            $value = is_array($opts[$key]) ? ($opts[$key][0] ?? null) : $opts[$key];
            return $value !== false ? trim((string)$value) : null;
        }
        return null;
    }

    return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : null;
}

$confirm = strtoupper((string)(getParam('confirm') ?? 'NO'));
if ($confirm !== 'SI') {
    out([
        'success' => false,
        'msg' => 'Operación cancelada. Debes confirmar con confirm=SI para ejecutar borrado.'
    ], 400);
}

$adminId = getParam('admin_id');
$userId = getParam('user_id');
$adminEmail = getParam('admin_email');
$userEmail = getParam('user_email');

try {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->beginTransaction();

    // ===== Resolver ADMIN a eliminar (1 registro) =====
    if (!empty($adminId)) {
        $stmtAdmin = $pdo->prepare("SELECT id, usuario, email, rol FROM usuarios_admin WHERE id = ? LIMIT 1");
        $stmtAdmin->execute([(int)$adminId]);
    } elseif (!empty($adminEmail)) {
        $stmtAdmin = $pdo->prepare("SELECT id, usuario, email, rol FROM usuarios_admin WHERE LOWER(email)=LOWER(?) LIMIT 1");
        $stmtAdmin->execute([$adminEmail]);
    } else {
        $stmtAdmin = $pdo->query("SELECT id, usuario, email, rol FROM usuarios_admin WHERE rol='admin' ORDER BY id DESC LIMIT 1");
    }

    $admin = $stmtAdmin->fetch();
    if (!$admin) {
        $pdo->rollBack();
        out([
            'success' => false,
            'msg' => 'No se encontró administrador elegible para eliminar (rol=admin).',
        ], 404);
    }

    if (($admin['rol'] ?? '') === 'superadmin') {
        $pdo->rollBack();
        out([
            'success' => false,
            'msg' => 'Seguridad: no se permite borrar superadmin con este script.'
        ], 403);
    }

    // ===== Resolver USUARIO común a eliminar (1 registro) =====
    if (!empty($userId)) {
        $stmtUser = $pdo->prepare("SELECT id, nombre, apellido, usuario, email FROM usuarios WHERE id = ? LIMIT 1");
        $stmtUser->execute([(int)$userId]);
    } elseif (!empty($userEmail)) {
        $stmtUser = $pdo->prepare("SELECT id, nombre, apellido, usuario, email FROM usuarios WHERE LOWER(email)=LOWER(?) LIMIT 1");
        $stmtUser->execute([$userEmail]);
    } else {
        $stmtUser = $pdo->query("SELECT id, nombre, apellido, usuario, email FROM usuarios ORDER BY id DESC LIMIT 1");
    }

    $user = $stmtUser->fetch();
    if (!$user) {
        $pdo->rollBack();
        out([
            'success' => false,
            'msg' => 'No se encontró usuario común para eliminar.',
        ], 404);
    }

    // ===== Ejecutar borrado =====
    $delAdmin = $pdo->prepare("DELETE FROM usuarios_admin WHERE id = ? LIMIT 1");
    $delAdmin->execute([(int)$admin['id']]);

    $delUser = $pdo->prepare("DELETE FROM usuarios WHERE id = ? LIMIT 1");
    $delUser->execute([(int)$user['id']]);

    $pdo->commit();

    out([
        'success' => true,
        'msg' => 'Se eliminaron 1 admin y 1 usuario común para pruebas.',
        'deleted_admin' => [
            'id' => (int)$admin['id'],
            'usuario' => $admin['usuario'],
            'email' => $admin['email'],
            'rol' => $admin['rol'],
        ],
        'deleted_user' => [
            'id' => (int)$user['id'],
            'nombre' => trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')),
            'usuario' => $user['usuario'],
            'email' => $user['email'],
        ],
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    out([
        'success' => false,
        'msg' => 'Error ejecutando limpieza de usuarios.',
        'error' => $e->getMessage(),
    ], 500);
}
