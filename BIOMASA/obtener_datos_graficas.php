<?php
/**
 * API para obtener datos de gráficas del sistema BIOMASA
 * Maneja requestss GET/POST y retorna datos en JSON
 * También guarda los datos en la BD y opcionalmente genera archivos locales
 */

header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('America/Santo_Domingo');
require_once __DIR__ . '/../config/db_config.php';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Asegura consistencia de hora/fecha con RD en las consultas SQL.
    $pdo->exec("SET time_zone = '-04:00'");
} catch(PDOException $e) {
    die(json_encode(['status' => 'error', 'msg' => 'Error de conexión BD: ' . $e->getMessage()]));
}

// Crear tabla de datos de gráficas si no existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS datos_graficas (
        id int(11) NOT NULL AUTO_INCREMENT,
        periodo varchar(20) NOT NULL COMMENT 'dia, mes, anual',
        fecha_inicio date NOT NULL,
        fecha_fin date NOT NULL,
        total_temp_promedio decimal(5,2) DEFAULT 0,
        total_temp_max decimal(5,2) DEFAULT 0,
        total_temp_min decimal(5,2) DEFAULT 0,
        total_energia_generada decimal(10,4) DEFAULT 0,
        total_energia_consumida decimal(10,4) DEFAULT 0,
        total_energia_neta decimal(10,4) DEFAULT 0,
        ganancia_monetaria decimal(10,2) DEFAULT 0,
        datos_json longtext,
        fecha_creacion timestamp DEFAULT current_timestamp(),
        fecha_actualizacion timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY(id),
        UNIQUE KEY unique_periodo (periodo, fecha_inicio, fecha_fin)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(PDOException $e) {
    // Tabla ya existe
}

// Obtener datos según el período
$periodo = $_GET['periodo'] ?? 'dia';
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Validar fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
    echo json_encode(['status' => 'error', 'msg' => 'Fechas inválidas']);
    exit;
}

// Para período día, poner la misma fecha
if ($periodo === 'dia') {
    $fechaFin = $fechaInicio;
}

try {
    // Obtener datos de mediciones_biomasa
    $sql = "
        SELECT 
            temperatura, 
            energia, 
            relay, 
            ventilador, 
            peltier1, 
            peltier2, 
            gases,
            fecha,
            DATE_FORMAT(fecha, '%d/%m/%Y %H:%i:%s') as fecha_hora
        FROM mediciones_biomasa
        WHERE DATE(fecha) BETWEEN :fecha_inicio AND :fecha_fin
        ORDER BY fecha ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]);
    $mediciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar datos
    $labels = [];
    $tempData = [];
    $energiaData = [];
    $consumoData = [];
    $gananciasData = [];
    
    $totalTemp = 0;
    $maxTemp = -999;
    $minTemp = 999;
    $totalEnergia = 0;
    $totalConsumo = 0;
    
    foreach($mediciones as $row) {
        // Siempre usar fecha + hora exacta (segundos incluidos).
        $labels[] = $row['fecha_hora'];
        
        $temp = (float)$row['temperatura'];
        $energia = (float)$row['energia'] / 1000; // Convertir Wh a kWh
        $consumo = ((float)$row['relay'] + (float)$row['ventilador'] + 
                    (float)$row['peltier1'] + (float)$row['peltier2'] + (float)$row['gases']) / 1000;
        
        $tempData[] = $temp;
        $energiaData[] = $energia;
        $consumoData[] = $consumo;
        $gananciasData[] = $energia - $consumo;
        
        $totalTemp += $temp;
        $maxTemp = max($maxTemp, $temp);
        $minTemp = min($minTemp, $temp);
        $totalEnergia += $energia;
        $totalConsumo += $consumo;
    }
    
    $count = count($mediciones);
    $promTemp = $count > 0 ? $totalTemp / $count : 0;
    $totalNeto = $totalEnergia - $totalConsumo;
    
    // Precio DOP por kWh (aproximadamente 60-70 DOP)
    $PRECIO_KWH_DOP = 65.00;
    $gananciaMonetaria = $totalNeto * $PRECIO_KWH_DOP;
    
    // Guardar/actualizar datos en la tabla
    $datosJson = json_encode([
        'labels' => $labels,
        'tempData' => $tempData,
        'energiaData' => $energiaData,
        'consumoData' => $consumoData,
        'gananciasData' => $gananciasData
    ]);
    
    $sqlGuardar = "
        INSERT INTO datos_graficas (
            periodo, fecha_inicio, fecha_fin, 
            total_temp_promedio, total_temp_max, total_temp_min,
            total_energia_generada, total_energia_consumida, total_energia_neta,
            ganancia_monetaria, datos_json
        ) VALUES (
            :periodo, :fecha_inicio, :fecha_fin,
            :temp_prom, :temp_max, :temp_min,
            :energia_gen, :energia_cons, :energia_neta,
            :ganancia, :datos_json
        )
        ON DUPLICATE KEY UPDATE
            total_temp_promedio = :temp_prom,
            total_temp_max = :temp_max,
            total_temp_min = :temp_min,
            total_energia_generada = :energia_gen,
            total_energia_consumida = :energia_cons,
            total_energia_neta = :energia_neta,
            ganancia_monetaria = :ganancia,
            datos_json = :datos_json,
            fecha_actualizacion = NOW()
    ";
    
    $stmtGuardar = $pdo->prepare($sqlGuardar);
    $stmtGuardar->execute([
        ':periodo' => $periodo,
        ':fecha_inicio' => $fechaInicio,
        ':fecha_fin' => $fechaFin,
        ':temp_prom' => $promTemp,
        ':temp_max' => $maxTemp === -999 ? 0 : $maxTemp,
        ':temp_min' => $minTemp === 999 ? 0 : $minTemp,
        ':energia_gen' => $totalEnergia,
        ':energia_cons' => $totalConsumo,
        ':energia_neta' => $totalNeto,
        ':ganancia' => $gananciaMonetaria,
        ':datos_json' => $datosJson
    ]);
    
    // Si se solicita guardar localmente
    if (isset($_GET['guardar_local']) && $_GET['guardar_local'] === '1') {
        guardarDatosLocales($periodo, $fechaInicio, $fechaFin, [
            'temp_prom' => $promTemp,
            'temp_max' => $maxTemp,
            'temp_min' => $minTemp,
            'energia_gen' => $totalEnergia,
            'energia_cons' => $totalConsumo,
            'energia_neta' => $totalNeto,
            'ganancia' => $gananciaMonetaria,
            'mediciones' => $mediciones
        ]);
    }
    
    // Retornar respuesta
    echo json_encode([
        'status' => 'ok',
        'periodo' => $periodo,
        'fechaInicio' => $fechaInicio,
        'fechaFin' => $fechaFin,
        'datos' => [
            'labels' => $labels,
            'tempData' => $tempData,
            'energiaData' => $energiaData,
            'consumoData' => $consumoData,
            'gananciasData' => $gananciasData
        ],
        'resumen' => [
            'totalTemp' => round($totalTemp, 2),
            'promTemp' => round($promTemp, 2),
            'maxTemp' => $maxTemp === -999 ? 0 : round($maxTemp, 2),
            'minTemp' => $minTemp === 999 ? 0 : round($minTemp, 2),
            'totalEnergia' => round($totalEnergia, 3),
            'totalConsumo' => round($totalConsumo, 3),
            'totalNeto' => round($totalNeto, 3),
            'gananciaMonetaria' => round($gananciaMonetaria, 2),
            'precioDOP' => $PRECIO_KWH_DOP,
            'registros' => $count
        ]
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error en BD: ' . $e->getMessage()
    ]);
}

/**
 * Guarda los datos en archivos locales dentro de PRERMI
 */
function guardarDatosLocales($periodo, $fechaInicio, $fechaFin, $datos) {
    $carpetaBase = dirname(__DIR__) . '/BIOMASA/reportes';
    
    // Crear carpeta si no existe
    if (!is_dir($carpetaBase)) {
        mkdir($carpetaBase, 0755, true);
    }
    
    // Generar nombre del archivo
    $timestamp = date('YmdHis');
    $nombreArchivo = "datos_graficas_{$periodo}_{$timestamp}.json";
    $rutaArchivo = $carpetaBase . '/' . $nombreArchivo;
    
    // Preparar datos para guardar
    $datosGuardar = [
        'periodo' => $periodo,
        'fechaInicio' => $fechaInicio,
        'fechaFin' => $fechaFin,
        'generado' => date('Y-m-d H:i:s'),
        'resumen' => [
            'temperaturaPromedio' => $datos['temp_prom'],
            'temperaturaMax' => $datos['temp_max'],
            'temperaturaMin' => $datos['temp_min'],
            'energiaGenerada_kWh' => $datos['energia_gen'],
            'energiaConsumida_kWh' => $datos['energia_cons'],
            'energiaNeta_kWh' => $datos['energia_neta'],
            'gananciaMonetaria_DOP' => $datos['ganancia']
        ],
        'totalRegistros' => count($datos['mediciones'])
    ];
    
    // Guardar JSON
    file_put_contents($rutaArchivo, json_encode($datosGuardar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // También generar CSV
    $rutaCSV = str_replace('.json', '.csv', $rutaArchivo);
    generarCSV($rutaCSV, $datos['mediciones'], $periodo);
    
    return true;
}

/**
 * Genera archivo CSV con los datos
 */
function generarCSV($rutaCSV, $mediciones, $periodo) {
    $csv = fopen($rutaCSV, 'w');
    
    // Encabezados
    $encabezados = ['Fecha', 'Hora', 'Temperatura (°C)', 'Energía (kWh)', 'Relay', 'Ventilador', 'Peltier1', 'Peltier2', 'Gases'];
    fputcsv($csv, $encabezados);
    
    // Datos
    foreach($mediciones as $row) {
        $fechaRow = strtotime((string)$row['fecha']);
        $fila = [
            date('Y-m-d', $fechaRow),
            date('H:i:s', $fechaRow),
            $row['temperatura'],
            (float)$row['energia'] / 1000,
            $row['relay'],
            $row['ventilador'],
            $row['peltier1'],
            $row['peltier2'],
            $row['gases']
        ];
        fputcsv($csv, $fila);
    }
    
    fclose($csv);
}

?>
