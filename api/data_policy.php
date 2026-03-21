<?php
/**
 * api/data_policy.php — Política de acceso a datos PRERMI
 *
 * Centraliza qué campos de usuario/admin son visibles según el rol.
 * Los datos sensibles (email, teléfono, cédula) son exclusivos de SUPERADMIN.
 *
 * Uso:
 *   require_once __DIR__ . '/../api/data_policy.php';   // desde web/
 *   require_once __DIR__ . '/data_policy.php';          // desde api/admin/
 *
 *   $rol = getSessionRole();
 *   $safeUsers  = filterUsers($usuarios, $rol);
 *   $safeAdmins = filterAdmins($admins, $rol);
 *   requireSuperAdmin(); // aborta con 403 si no es superadmin
 */

// ============================================================
// CAMPOS PERMITIDOS POR NIVEL DE ROL
// ============================================================

/** Campos de la tabla `usuarios` visibles para admin regular. */
const USER_FIELDS_ADMIN = [
    'id', 'usuario', 'nombre', 'apellido',
    'verified', 'activo', 'creado_en',
];

/** Campos de la tabla `usuarios` visibles solo para superadmin. */
const USER_FIELDS_SUPERADMIN = [
    'id', 'usuario', 'nombre', 'apellido',
    'email', 'telefono', 'cedula',
    'verified', 'activo', 'creado_en',
];

/** Campos de la tabla `usuarios_admin` visibles para admin regular. */
const ADMIN_FIELDS_ADMIN = [
    'id', 'usuario', 'nombre', 'apellido',
    'rol', 'verified', 'active', 'creado_en',
];

/** Campos de la tabla `usuarios_admin` visibles solo para superadmin. */
const ADMIN_FIELDS_SUPERADMIN = [
    'id', 'usuario', 'nombre', 'apellido', 'email',
    'rol', 'verified', 'active', 'creado_en',
];

// ============================================================
// FUNCIONES DE SESIÓN / ROL
// ============================================================

/**
 * Devuelve el rol del admin autenticado en sesión ('admin' | 'superadmin').
 * Cachea el resultado en $_SESSION['admin_rol'] para evitar
 * múltiples consultas dentro del mismo request.
 *
 * @return string  'superadmin' | 'admin' | 'guest'
 */
function getSessionRole(): string
{
    if (empty($_SESSION['admin_id'])) {
        return 'guest';
    }

    // Caché dentro del mismo request
    if (!empty($_SESSION['admin_rol'])) {
        return $_SESSION['admin_rol'];
    }

    try {
        $pdo = getPDO();
        $st  = $pdo->prepare("SELECT rol FROM usuarios_admin WHERE id = ? LIMIT 1");
        $st->execute([$_SESSION['admin_id']]);
        $rol = $st->fetchColumn() ?: 'admin';
    } catch (Exception $e) {
        $rol = 'admin'; // degradar con seguridad ante fallo de BD
    }

    $_SESSION['admin_rol'] = $rol;
    return $rol;
}

/**
 * Aborta el request con HTTP 403 si el admin en sesión NO es superadmin.
 * Debe llamarse después de haber iniciado sesión (session_start ya ejecutado).
 */
function requireSuperAdmin(): void
{
    if (getSessionRole() !== 'superadmin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'msg'     => 'Acceso restringido a superadministradores',
        ]);
        exit;
    }
}

// ============================================================
// FUNCIONES DE FILTRADO
// ============================================================

/**
 * Filtra un array asociativo de usuario dejando solo los campos
 * permitidos para el rol dado.
 *
 * @param array  $user  Fila de la tabla `usuarios`
 * @param string $rol   'admin' | 'superadmin'
 * @return array
 */
function filterUser(array $user, string $rol): array
{
    $fields = ($rol === 'superadmin') ? USER_FIELDS_SUPERADMIN : USER_FIELDS_ADMIN;
    return array_intersect_key($user, array_flip($fields));
}

/**
 * Filtra un array asociativo de admin dejando solo los campos
 * permitidos para el rol dado.
 *
 * @param array  $admin  Fila de la tabla `usuarios_admin`
 * @param string $rol    'admin' | 'superadmin'
 * @return array
 */
function filterAdmin(array $admin, string $rol): array
{
    $fields = ($rol === 'superadmin') ? ADMIN_FIELDS_SUPERADMIN : ADMIN_FIELDS_ADMIN;
    return array_intersect_key($admin, array_flip($fields));
}

/**
 * Aplica filterUser() a toda una colección de usuarios.
 *
 * @param array  $users  Array de filas de `usuarios`
 * @param string $rol
 * @return array
 */
function filterUsers(array $users, string $rol): array
{
    return array_values(array_map(fn($u) => filterUser($u, $rol), $users));
}

/**
 * Aplica filterAdmin() a toda una colección de admins.
 *
 * @param array  $admins  Array de filas de `usuarios_admin`
 * @param string $rol
 * @return array
 */
function filterAdmins(array $admins, string $rol): array
{
    return array_values(array_map(fn($a) => filterAdmin($a, $rol), $admins));
}
