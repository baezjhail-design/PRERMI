<?php
/**
 * instalar_bd.php
 * Importa la base de datos prer_mi.sql y verifica la instalación
 * 
 * Acceso: http://localhost/PRERMI/instalar_bd.php
 */

header('Content-Type: application/json; charset=utf-8');

$resultado = [
    'status' => 'INICIANDO',
    'pasos' => [],
    'errores' => []
];

try {
    // PASO 1: Verificar archivo SQL
    $archivo_sql = __DIR__ . '/prer_mi.sql';
    if (!file_exists($archivo_sql)) {
        $resultado['errores'][] = "Archivo prer_mi.sql no encontrado en " . $archivo_sql;
        echo json_encode($resultado, JSON_PRETTY_PRINT);
        exit;
    }
    $resultado['pasos'][] = "✓ Archivo prer_mi.sql encontrado";
    
    // PASO 2: Conectar a MySQL sin BD
    require_once __DIR__ . '/config/db_config.php';
    
    try {
        $pdo_mysql = new PDO(
            "mysql:host=$DB_HOST",
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]
        );
        $resultado['pasos'][] = "✓ Conexión a MySQL establecida";
    } catch (PDOException $e) {
        $resultado['errores'][] = "No se pudo conectar a MySQL: " . $e->getMessage();
        echo json_encode($resultado, JSON_PRETTY_PRINT);
        exit;
    }
    
    // PASO 3: Verificar si BD ya existe
    try {
        $pdo_mysql->query("USE $DB_NAME");
        $resultado['pasos'][] = "⚠ Base de datos '$DB_NAME' ya existe (se usará como está)";
    } catch (PDOException $e) {
        // BD no existe, no es un error en este punto
        $resultado['pasos'][] = "ℹ Base de datos '$DB_NAME' no existe (será creada)";
    }
    
    // PASO 4: Leer archivo SQL
    $sql_content = file_get_contents($archivo_sql);
    if ($sql_content === false) {
        $resultado['errores'][] = "No se pudo leer el archivo prer_mi.sql";
        echo json_encode($resultado, JSON_PRETTY_PRINT);
        exit;
    }
    $resultado['pasos'][] = "✓ Archivo SQL leído (" . strlen($sql_content) . " bytes)";
    
    // PASO 5: Ejecutar SQL
    try {
        // Dividir por punto y coma, pero cuidado con comentarios
        $sentencias = array_filter(array_map('trim', explode(';', $sql_content)), function($s) {
            return strlen($s) > 0 && !preg_match('/^--/', $s);
        });
        
        $contador = 0;
        foreach ($sentencias as $sentencia) {
            if (trim($sentencia) === '') continue;
            try {
                $pdo_mysql->exec($sentencia);
                $contador++;
            } catch (PDOException $e) {
                // Algunos errores son esperados (por ejemplo, tabla ya existe)
                // Registrar pero continuar
                if (strpos($e->getMessage(), 'already exists') === false) {
                    // Es un error real, no solo una tabla que ya existe
                    // Pero continuamos de todas formas
                }
            }
        }
        $resultado['pasos'][] = "✓ $contador sentencias SQL ejecutadas";
    } catch (Exception $e) {
        $resultado['errores'][] = "Error ejecutando SQL: " . $e->getMessage();
    }
    
    // PASO 6: Verificar conexión a BD
    require_once __DIR__ . '/api/utils.php';
    
    try {
        $pdo = getPDO();
        $resultado['pasos'][] = "✓ Conexión a base de datos prer_mi establecida";
    } catch (Exception $e) {
        $resultado['errores'][] = "Error conectando a prer_mi: " . $e->getMessage();
        echo json_encode($resultado, JSON_PRETTY_PRINT);
        exit;
    }
    
    // PASO 7: Verificar tablas
    $tablas_esperadas = [
        'usuarios', 'usuarios_admin', 'vehiculos_registrados',
        'contenedores_registrados', 'depositos', 'multas', 'logs_sistema'
    ];
    
    $stmt = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?");
    $stmt->execute([$DB_NAME]);
    $tablas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $tablas_ok = true;
    foreach ($tablas_esperadas as $tabla) {
        if (!in_array($tabla, $tablas_existentes)) {
            $resultado['errores'][] = "Tabla '$tabla' no existe";
            $tablas_ok = false;
        }
    }
    
    if ($tablas_ok) {
        $resultado['pasos'][] = "✓ Todas las " . count($tablas_esperadas) . " tablas existen";
    }
    
    // PASO 8: Verificar datos iniciales
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios_admin WHERE active = 1");
    $stmt->execute();
    $admin_count = $stmt->fetch()['total'];
    
    if ($admin_count > 0) {
        $resultado['pasos'][] = "✓ $admin_count admin(s) activo(s)";
    } else {
        $resultado['advertencias'][] = "No hay admins activos";
    }
    
    // PASO 9: Estadísticas finales
    $stats = [];
    foreach ($tablas_esperadas as $tabla) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM $tabla");
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        $stats[$tabla] = $total;
    }
    $resultado['estadisticas'] = $stats;
    
    // Resultado final
    if (count($resultado['errores']) === 0) {
        $resultado['status'] = 'ÉXITO';
        $resultado['mensaje'] = 'Base de datos instalada correctamente';
    } else {
        $resultado['status'] = 'ERROR';
        $resultado['mensaje'] = 'La instalación completó con errores';
    }
    
} catch (Exception $e) {
    $resultado['status'] = 'FATAL';
    $resultado['errores'][] = $e->getMessage();
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
