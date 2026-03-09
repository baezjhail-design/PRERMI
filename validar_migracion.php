<?php
/**
 * VALIDACION_MIGRACION.php
 * Script para verificar que la migración del schema se realizó correctamente
 * y que todos los endpoints funcionan con el nuevo schema
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/api/utils.php';

$resultado = [
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'iniciado',
    'verificaciones' => [],
    'errores' => [],
    'advertencias' => []
];

try {
    $pdo = getPDO();
    
    // 1. Verificar tablas nuevas existen
    $resultado['verificaciones']['tablas_nuevas'] = [];
    $tablasNuevas = ['sensores', 'mediciones_biomasa'];
    
    foreach ($tablasNuevas as $tabla) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $tabla");
            $count = $stmt->fetchColumn();
            $resultado['verificaciones']['tablas_nuevas'][$tabla] = [
                'existe' => true,
                'registros' => (int)$count
            ];
        } catch (Exception $e) {
            $resultado['errores'][] = "Tabla $tabla no existe o no es accesible: " . $e->getMessage();
            $resultado['verificaciones']['tablas_nuevas'][$tabla] = ['existe' => false];
        }
    }
    
    // 2. Verificar relaciones de clave foránea
    $resultado['verificaciones']['foreign_keys'] = [];
    
    $fkQueries = [
        'sensores_usuario_fk' => 'SELECT TABLE_NAME, CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = "sensores" AND COLUMN_NAME = "user_id" AND REFERENCED_TABLE_NAME = "usuarios"',
'biomasa_usuario_fk' => 'SELECT TABLE_NAME, CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = "mediciones_biomasa" AND COLUMN_NAME = "user_id" AND REFERENCED_TABLE_NAME = "usuarios"'
    ];
    
    foreach ($fkQueries as $nombre => $query) {
        try {
            $stmt = $pdo->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resultado['verificaciones']['foreign_keys'][$nombre] = count($rows) > 0 ? 'válida' : 'no encontrada';
            if (count($rows) === 0) {
                $resultado['advertencias'][] = "FK $nombre no está definida";
            }
        } catch (Exception $e) {
            $resultado['advertencias'][] = "No se pudo verificar FK $nombre";
        }
    }
    
    // 3. Verificar estructura de columnas
    $resultado['verificaciones']['estructura'] = [];
    
    $columnsToCheck = [
        'sensores' => ['id', 'user_id', 'sensor_ir', 'ruta_imagen', 'fecha'],
        'mediciones_biomasa' => ['id', 'temperatura', 'energia', 'user_id', 'relay', 'ventilador', 'peltier1', 'peltier2', 'gases', 'fecha']
    ];
    
    foreach ($columnsToCheck as $tabla => $columnas) {
        $resultado['verificaciones']['estructura'][$tabla] = [];
        try {
            $stmt = $pdo->query("DESC $tabla");
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $fieldNames = array_map(fn($f) => $f['Field'], $fields);
            
            foreach ($columnas as $col) {
                $existe = in_array($col, $fieldNames);
                $resultado['verificaciones']['estructura'][$tabla][$col] = $existe ? '✓' : '✗';
                if (!$existe) {
                    $resultado['errores'][] = "Columna $col no existe en tabla $tabla";
                }
            }
        } catch (Exception $e) {
            $resultado['errores'][] = "No se pudo verificar estructura de $tabla";
        }
    }
    
    // 4. Verificar que depositos y sanciones ya no tienen FK obligatoriamente
    $resultado['verificaciones']['depositos_sanciones'] = [];
    
    try {
        $stmt = $pdo->query("DESC depositos");
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fields as $field) {
            if ($field['Field'] === 'id_contenedor') {
                $resultado['verificaciones']['depositos_sanciones']['id_contenedor_nullable'] = 
                    $field['Null'] === 'YES' ? 'puede ser NULL (✓)' : 'no puede ser NULL (⚠)';
            }
        }
    } catch (Exception $e) {
        $resultado['advertencias'][] = "No se pudo verificar estructura de depositos";
    }
    
    // 5. Contar registros en tablas principales
    $resultado['verificaciones']['conteos'] = [];
    foreach (['usuarios', 'depositos', 'sanciones', 'sensores', 'mediciones_biomasa'] as $tabla) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $tabla");
            $resultado['verificaciones']['conteos'][$tabla] = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            $resultado['verificaciones']['conteos'][$tabla] = 'error o tabla no existe';
        }
    }
    
    // 6. Verificar índices de performance
    $resultado['verificaciones']['indices'] = [];
    foreach (['sensores', 'mediciones_biomasa'] as $tabla) {
        try {
            $stmt = $pdo->query("SHOW INDEX FROM $tabla");
            $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resultado['verificaciones']['indices'][$tabla] = count($indices) . ' índices encontrados';
        } catch (Exception $e) {
            $resultado['advertencias'][] = "No se pudo verificar índices en $tabla";
        }
    }
    
    // 7. Test: Intentar crear un registro en cada tabla nueva
    $resultado['verificaciones']['test_inserts'] = [];
    
    // Primero, obtener un usuario válido
    $stmtUser = $pdo->query("SELECT id FROM usuarios LIMIT 1");
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $testUserId = $user['id'];
        
        // Test inserto en sensores
        try {
            $stmt = $pdo->prepare('INSERT INTO sensores (user_id, sensor_ir, ruta_imagen) VALUES (?, ?, ?)');
            $stmt->execute([$testUserId, 42, '/test/imagen.jpg']);
            $sensorId = $pdo->lastInsertId();
            $resultado['verificaciones']['test_inserts']['sensores'] = "✓ (ID: $sensorId)";
            
            // Limpiar registro de test
            $pdo->prepare('DELETE FROM sensores WHERE id = ?')->execute([$sensorId]);
        } catch (Exception $e) {
            $resultado['errores'][] = "No se pudo insertar en sensores: " . $e->getMessage();
        }
        
        // Test inserto en mediciones
        try {
            $stmt = $pdo->prepare('INSERT INTO mediciones (user_id, peso, sensor_metal, estado) VALUES (?, ?, ?, ?)');
            $stmt->execute([$testUserId, 45.50, 0, 'disponible']);
            $medId = $pdo->lastInsertId();
            $resultado['verificaciones']['test_inserts']['mediciones'] = "✓ (ID: $medId)";
            
            // Limpiar
            $pdo->prepare('DELETE FROM mediciones WHERE id = ?')->execute([$medId]);
        } catch (Exception $e) {
            $resultado['errores'][] = "No se pudo insertar en mediciones: " . $e->getMessage();
        }
        
        // Test inserto en mediciones_biomasa
        try {
            $stmt = $pdo->prepare('INSERT INTO mediciones_biomasa (user_id, relay, ventilador, peltier1, peltier2, gases) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$testUserId, 1.0, 75.5, 28.3, 27.9, 150.2]);
            $bioId = $pdo->lastInsertId();
            $resultado['verificaciones']['test_inserts']['mediciones_biomasa'] = "✓ (ID: $bioId)";
            
            // Limpiar
            $pdo->prepare('DELETE FROM mediciones_biomasa WHERE id = ?')->execute([$bioId]);
        } catch (Exception $e) {
            $resultado['errores'][] = "No se pudo insertar en mediciones_biomasa: " . $e->getMessage();
        }
    } else {
        $resultado['advertencias'][] = "No hay usuarios para realizar test de insertos";
    }
    
    // 8. Verificar APIs accesibles
    $resultado['verificaciones']['apis_activas'] = [];
    $apis = [
        'sensores/registrar.php',
        'sensores/obtener.php',
        'mediciones/registrar.php',
        'mediciones/obtener.php',
        'biomasa/registrar.php',
        'biomasa/obtener.php'
    ];
    
    foreach ($apis as $api) {
        $filepath = __DIR__ . '/api/' . $api;
        $resultado['verificaciones']['apis_activas'][$api] = file_exists($filepath) ? '✓ archivo existe' : '✗ no encontrado';
    }
    
    // 9. Status final
    if (empty($resultado['errores'])) {
        $resultado['status'] = 'ÉXITO: Migracion completada correctamente';
    } else {
        $resultado['status'] = 'ERROR: Hay ' . count($resultado['errores']) . ' error(es) que revisar';
    }
    
} catch (Exception $e) {
    $resultado['status'] = 'FALLO CRITICO';
    $resultado['errores'][] = 'Excepción general: ' . $e->getMessage();
}

http_response_code(!empty($resultado['errores']) ? 500 : 200);
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
