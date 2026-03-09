<?php
/**
 * verificar_bd_integridad.php
 * Verifica que todos los APIs estén alineados con la estructura real de prer_mi.sql
 * 
 * Acceso: http://localhost/PRERMI/verificar_bd_integridad.php
 */

require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/api/utils.php';

header('Content-Type: application/json; charset=utf-8');

$resultado = [
    'status' => 'OK',
    'fecha' => date('Y-m-d H:i:s'),
    'verificaciones' => [],
    'errores' => [],
    'advertencias' => []
];

try {
    $pdo = getPDO();
    
    // 1. Verificar tablas
    $tablas_esperadas = [
        'usuarios',
        'usuarios_admin',
        'vehiculos_registrados',
        'contenedores_registrados',
        'depositos',
        'multas',
        'logs_sistema',
        'configuracion'
    ];
    
    $stmt = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?");
    $stmt->execute([$DB_NAME]);
    $tablas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $resultado['verificaciones']['tablas_existentes'] = [];
    foreach ($tablas_esperadas as $tabla) {
        $existe = in_array($tabla, $tablas_existentes);
        $resultado['verificaciones']['tablas_existentes'][$tabla] = $existe ? '✓' : '✗';
        if (!$existe) {
            $resultado['errores'][] = "Tabla '$tabla' no existe en la BD";
            $resultado['status'] = 'ERROR';
        }
    }
    
    // 2. Verificar estructura de tabla usuarios
    $stmt = $pdo->prepare("DESC usuarios");
    $stmt->execute();
    $campos_usuarios = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $campos_usuarios_esperados = [
        'id', 'nombre', 'apellido', 'usuario', 'email', 
        'telefono', 'cedula', 'token', 'token_activo', 'clave', 'creado_en'
    ];
    
    $resultado['verificaciones']['estructura_usuarios'] = [];
    foreach ($campos_usuarios_esperados as $campo) {
        $existe = in_array($campo, $campos_usuarios);
        $resultado['verificaciones']['estructura_usuarios'][$campo] = $existe ? '✓' : '✗';
        if (!$existe) {
            $resultado['errores'][] = "Campo '$campo' no existe en tabla usuarios";
            $resultado['status'] = 'ERROR';
        }
    }
    
    // 3. Verificar estructura de tabla usuarios_admin
    $stmt = $pdo->prepare("DESC usuarios_admin");
    $stmt->execute();
    $campos_admin = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $campos_admin_esperados = [
        'id', 'usuario', 'email', 'clave', 'verification_token',
        'verified', 'active', 'rol', 'creado_en'
    ];
    
    $resultado['verificaciones']['estructura_usuarios_admin'] = [];
    foreach ($campos_admin_esperados as $campo) {
        $existe = in_array($campo, $campos_admin);
        $resultado['verificaciones']['estructura_usuarios_admin'][$campo] = $existe ? '✓' : '✗';
        if (!$existe) {
            $resultado['advertencias'][] = "Campo '$campo' no existe en tabla usuarios_admin";
        }
    }
    
    // 4. Verificar estructura de vehiculos_registrados
    $stmt = $pdo->prepare("DESC vehiculos_registrados");
    $stmt->execute();
    $campos_vehiculos = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $campos_vehiculos_esperados = [
        'id', 'placa', 'tipo_vehiculo', 'imagen', 'ubicacion',
        'fecha', 'hora', 'modelo_ml', 'probabilidad', 'latitud', 'longitud', 'creado_en'
    ];
    
    $resultado['verificaciones']['estructura_vehiculos_registrados'] = [];
    foreach ($campos_vehiculos_esperados as $campo) {
        $existe = in_array($campo, $campos_vehiculos);
        $resultado['verificaciones']['estructura_vehiculos_registrados'][$campo] = $existe ? '✓' : '✗';
        if (!$existe) {
            $resultado['errores'][] = "Campo '$campo' no existe en tabla vehiculos_registrados";
            $resultado['status'] = 'ERROR';
        }
    }
    
    // 5. Verificar estructura de contenedores_registrados
    $stmt = $pdo->prepare("DESC contenedores_registrados");
    $stmt->execute();
    $campos_contenedores = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $campos_contenedores_esperados = [
        'id', 'id_contenedor', 'api_key', 'nivel_basura',
        'ubicacion', 'latitud', 'longitud', 'actualizado_en'
    ];
    
    $resultado['verificaciones']['estructura_contenedores_registrados'] = [];
    foreach ($campos_contenedores_esperados as $campo) {
        $existe = in_array($campo, $campos_contenedores);
        $resultado['verificaciones']['estructura_contenedores_registrados'][$campo] = $existe ? '✓' : '✗';
        if (!$existe) {
            $resultado['errores'][] = "Campo '$campo' no existe en tabla contenedores_registrados";
            $resultado['status'] = 'ERROR';
        }
    }
    
    // 6. Verificar estadísticas de datos
    $tablas_para_contar = ['usuarios', 'usuarios_admin', 'vehiculos_registrados', 'contenedores_registrados', 'depositos', 'multas', 'logs_sistema'];
    $resultado['estadisticas'] = [];
    
    foreach ($tablas_para_contar as $tabla) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM $tabla");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $resultado['estadisticas'][$tabla] = (int)$count['total'];
    }
    
    // 7. Verificar integridad de Foreign Keys
    $resultado['verificaciones']['foreign_keys'] = [];
    
    // Verificar depositos.user_id
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as invalidos FROM depositos d
        WHERE d.user_id NOT IN (SELECT id FROM usuarios)
    ");
    $stmt->execute();
    $invalidos = $stmt->fetch()['invalidos'];
    $resultado['verificaciones']['foreign_keys']['depositos.user_id'] = $invalidos == 0 ? '✓' : "✗ ($invalidos inválidos)";
    
    // Verificar multas.user_id
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as invalidos FROM multas m
        WHERE m.user_id NOT IN (SELECT id FROM usuarios)
    ");
    $stmt->execute();
    $invalidos = $stmt->fetch()['invalidos'];
    $resultado['verificaciones']['foreign_keys']['multas.user_id'] = $invalidos == 0 ? '✓' : "✗ ($invalidos inválidos)";
    
    // 8. Verificar admin activo
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios_admin WHERE active = 1 AND verified = 1");
    $stmt->execute();
    $admins_activos = $stmt->fetch()['total'];
    $resultado['verificaciones']['admin_activo'] = $admins_activos > 0 ? "✓ ($admins_activos)" : '✗ Sin admin activo';
    
} catch (PDOException $e) {
    $resultado['status'] = 'ERROR';
    $resultado['errores'][] = "Error PDO: " . $e->getMessage();
} catch (Exception $e) {
    $resultado['status'] = 'ERROR';
    $resultado['errores'][] = "Error: " . $e->getMessage();
}

// Determinar status final
if (count($resultado['errores']) > 0) {
    $resultado['status'] = 'ERROR';
} elseif (count($resultado['advertencias']) > 0) {
    $resultado['status'] = 'WARNING';
} else {
    $resultado['status'] = 'OK';
}

// Mostrar resultado
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
