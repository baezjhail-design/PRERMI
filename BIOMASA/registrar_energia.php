<?php
/**
 * registrar_energia.php — Endpoint BIOMASA para registro de ciclos de energía
 *
 * Uso desde ESP (GET con ?esp=1):
 *   Registrar ciclo finalizado:
 *     ?esp=1&accion=registrar&energia_wh=0.25&duracion_seg=3600
 *       &temp_promedio=47.5&corriente_promedio=2.3&timestamp_inicio=1710000000
 *
 *   Obtener últimos registros:
 *     ?esp=1&accion=obtener&limit=10
 *
 * Uso desde la web / UI (GET sin ?esp=1):
 *     ?limit=50                — últimos 50 ciclos (default 100)
 *     ?desde=2026-03-01       — filtrar desde fecha
 *     ?resumen=1              — solo estadísticas totales
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ============================================================
// ARCHIVOS DE DATOS
// ============================================================
$energiaFile   = __DIR__ . '/../api/energia_biomasa.json';    // Historial de ciclos
$energiaDir    = dirname($energiaFile);

// Crear directorio api/ si no existe
if (!is_dir($energiaDir)) {
    mkdir($energiaDir, 0775, true);
}

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================
function leerCiclos(string $file): array {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function guardarCiclos(string $file, array $ciclos): bool {
    return file_put_contents($file, json_encode($ciclos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function calcularResumen(array $ciclos): array {
    $total_wh       = 0.0;
    $total_seg      = 0;
    $temp_sum       = 0.0;
    $temp_count     = 0;
    $corriente_sum  = 0.0;
    $corriente_count = 0;

    foreach ($ciclos as $c) {
        $total_wh      += floatval($c['energia_wh'] ?? 0);
        $total_seg     += intval($c['duracion_seg'] ?? 0);
        if (isset($c['temp_promedio']) && floatval($c['temp_promedio']) > 0) {
            $temp_sum   += floatval($c['temp_promedio']);
            $temp_count++;
        }
        if (isset($c['corriente_promedio']) && floatval($c['corriente_promedio']) > 0) {
            $corriente_sum   += floatval($c['corriente_promedio']);
            $corriente_count++;
        }
    }

    $horas = $total_seg / 3600;

    return [
        'total_ciclos'       => count($ciclos),
        'energia_total_wh'   => round($total_wh, 4),
        'energia_total_kwh'  => round($total_wh / 1000, 6),
        'duracion_total_seg' => $total_seg,
        'duracion_total_h'   => round($horas, 2),
        'temp_promedio_global' => $temp_count > 0 ? round($temp_sum / $temp_count, 2) : null,
        'corriente_promedio_global' => $corriente_count > 0 ? round($corriente_sum / $corriente_count, 3) : null,
        'potencia_media_w'   => ($horas > 0) ? round($total_wh / $horas, 3) : 0,
    ];
}

// ============================================================
// INTENTAR TAMBIÉN GUARDAR EN BD (no bloquea si falla)
// ============================================================
function intentarGuardarEnBD(array $ciclo): void {
    $dbConfig = __DIR__ . '/../config/db_config.php';
    if (!file_exists($dbConfig)) return;

    try {
        require_once $dbConfig;
        if (empty($DB_HOST) || empty($DB_NAME)) return;

        $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);

        // Crear tabla si no existe (primera vez)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `energia_biomasa_ciclos` (
                `id`                  INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `energia_wh`          DECIMAL(10,4) NOT NULL DEFAULT 0,
                `duracion_seg`        INT(11) UNSIGNED NOT NULL DEFAULT 0,
                `temp_promedio`       DECIMAL(6,2) DEFAULT NULL,
                `corriente_promedio`  DECIMAL(6,3) DEFAULT NULL,
                `timestamp_inicio`    BIGINT UNSIGNED DEFAULT NULL,
                `registrado_en`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_registrado_en` (`registrado_en`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $stmt = $pdo->prepare("
            INSERT INTO `energia_biomasa_ciclos`
                (energia_wh, duracion_seg, temp_promedio, corriente_promedio, timestamp_inicio)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            round(floatval($ciclo['energia_wh']), 4),
            intval($ciclo['duracion_seg']),
            isset($ciclo['temp_promedio'])      ? round(floatval($ciclo['temp_promedio']), 2)      : null,
            isset($ciclo['corriente_promedio'])  ? round(floatval($ciclo['corriente_promedio']), 3) : null,
            isset($ciclo['timestamp_inicio'])    ? intval($ciclo['timestamp_inicio'])               : null,
        ]);
    } catch (Exception $e) {
        // Silencioso: la BD es opcional; el JSON es la fuente primaria
        error_log('[registrar_energia] BD error: ' . $e->getMessage());
    }
}

// ============================================================
// SOLICITUD GET
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'msg' => 'Solo GET soportado']);
    exit;
}

$esEsp   = isset($_GET['esp']);
$accion  = strtolower(trim($_GET['accion'] ?? ($esEsp ? 'registrar' : 'obtener')));
$limit   = max(1, min(1000, intval($_GET['limit'] ?? 100)));
$desde   = $_GET['desde'] ?? null;
$resumen = isset($_GET['resumen']) && intval($_GET['resumen']) === 1;

// ============================================================
// ACCION: REGISTRAR CICLO
// ============================================================
if ($accion === 'registrar') {

    $energia_wh = floatval($_GET['energia_wh'] ?? 0);
    $duracion_seg = intval($_GET['duracion_seg'] ?? 0);

    // Validación mínima
    if ($energia_wh < 0 || $duracion_seg < 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Valores negativos no permitidos']);
        exit;
    }

    $ciclo = [
        'energia_wh'         => round($energia_wh, 4),
        'duracion_seg'       => $duracion_seg,
        'temp_promedio'      => isset($_GET['temp_promedio'])      ? round(floatval($_GET['temp_promedio']), 2)      : null,
        'corriente_promedio' => isset($_GET['corriente_promedio']) ? round(floatval($_GET['corriente_promedio']), 3) : null,
        'timestamp_inicio'   => isset($_GET['timestamp_inicio'])   ? intval($_GET['timestamp_inicio'])              : null,
        'registrado_en'      => date(DATE_ATOM),
    ];

    // Calcular potencia promedio del ciclo
    if ($duracion_seg > 0) {
        $ciclo['potencia_media_w'] = round($energia_wh / ($duracion_seg / 3600), 3);
    }

    // Leer historial, agregar y persistir
    $ciclos = leerCiclos($energiaFile);
    array_unshift($ciclos, $ciclo);             // El más reciente al inicio
    if (count($ciclos) > 500) {
        $ciclos = array_slice($ciclos, 0, 500); // Máximo 500 ciclos en JSON
    }

    if (!guardarCiclos($energiaFile, $ciclos)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => 'No se pudo escribir energia_biomasa.json']);
        exit;
    }

    // Intentar también en BD (si está disponible)
    intentarGuardarEnBD($ciclo);

    // Respuesta compacta (el ESP no necesita más)
    $respuesta = [
        'status'         => 'ok',
        'ciclo_guardado' => $ciclo,
        'total_ciclos'   => count($ciclos),
        'energia_acumulada_wh' => round(array_sum(array_column($ciclos, 'energia_wh')), 4),
    ];

    echo json_encode($respuesta);
    exit;
}

// ============================================================
// ACCION: OBTENER HISTORIAL
// ============================================================
if ($accion === 'obtener' || $accion === 'historial') {

    $ciclos = leerCiclos($energiaFile);

    // Filtrar por fecha si se proporcionó
    if ($desde !== null) {
        $desdeTs = strtotime($desde);
        if ($desdeTs !== false) {
            $ciclos = array_filter($ciclos, function ($c) use ($desdeTs) {
                $t = isset($c['registrado_en']) ? strtotime($c['registrado_en']) : 0;
                return $t >= $desdeTs;
            });
            $ciclos = array_values($ciclos);
        }
    }

    $total = count($ciclos);
    $ciclos = array_slice($ciclos, 0, $limit);

    $respuesta = [
        'status'        => 'ok',
        'total'         => $total,
        'mostrando'     => count($ciclos),
        'ciclos'        => $ciclos,
        'resumen'       => calcularResumen($ciclos),
    ];

    if ($resumen) {
        // Solo devolver el resumen (más liviano para la UI)
        unset($respuesta['ciclos']);
    }

    echo json_encode($respuesta);
    exit;
}

// Accion desconocida
http_response_code(400);
echo json_encode(['status' => 'error', 'msg' => "Accion desconocida: {$accion}. Usar: registrar|obtener"]);
