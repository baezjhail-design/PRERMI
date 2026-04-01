<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json; charset=utf-8');

// Obtener estadísticas de eventos de semáforo en últimas 24 horas
try {
    $pdo = getPDO();
    
    // Total de eventos normales (últimas 24h)
    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM vehiculos_registrados WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $totalNormal = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Total de eventos en rojo (últimas 24h)
    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM capturas_semaforo_rojo WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $totalRojo = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Últimas sanciones por semáforo rojo (últimas 24h)
    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM sanciones 
         WHERE descripcion LIKE '%semaforo%' AND creado_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $totalSanciones = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Evento más reciente
    $stmt = $pdo->query(
        "SELECT v.id, v.placa, v.tipo_vehiculo, v.imagen, v.ubicacion, v.fecha, v.hora,
                CASE WHEN csr.vehiculo_id IS NULL THEN 'vehiculo_detectado' ELSE 'semaforo_rojo' END AS evento,
                v.creado_en
         FROM vehiculos_registrados v
         LEFT JOIN capturas_semaforo_rojo csr ON csr.vehiculo_id = v.id
         ORDER BY v.creado_en DESC
         LIMIT 1"
    );
    $lastEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estado del semáforo (simulado basado en hora del día)
    $hora = intval(date('H'));
    $minuto = intval(date('i'));
    $segundo = intval(date('s'));
    
    // Ciclo de 24.5 segundos: 12s verde, 2.5s amarillo, 10s rojo
    $cicloTotal = 24.5;
    $tiempoEnCiclo = fmod($segundo + ($minuto * 60), $cicloTotal);
    
    if ($tiempoEnCiclo < 12) {
        $estado = 'verde';
        $tiempoRestante = ceil(12 - $tiempoEnCiclo);
    } elseif ($tiempoEnCiclo < 14.5) {
        $estado = 'amarillo';
        $tiempoRestante = ceil(14.5 - $tiempoEnCiclo);
    } else {
        $estado = 'rojo';
        $tiempoRestante = ceil(24.5 - $tiempoEnCiclo);
    }
    
    jsonOk([
        'success' => true,
        'semaforo' => [
            'id' => 'SEMAFORO-001',
            'ubicacion' => 'Santiago - Interseccion Demo',
            'latitud' => 19.4517,
            'longitud' => -70.6970,
            'estado_actual' => $estado,
            'tiempo_restante_segundos' => $tiempoRestante,
            'ciclo_verde_ms' => 12000,
            'ciclo_amarillo_ms' => 2500,
            'ciclo_rojo_ms' => 10000,
        ],
        'estadisticas_24h' => [
            'eventos_normales' => intval($totalNormal),
            'violaciones_rojo' => intval($totalRojo),
            'sanciones_aplicadas' => intval($totalSanciones),
            'total_eventos' => intval($totalNormal + $totalRojo),
        ],
        'ultimo_evento' => $lastEvent ? [
            'id' => intval($lastEvent['id']),
            'tipo' => $lastEvent['tipo_vehiculo'],
            'evento' => $lastEvent['evento'],
            'hora' => $lastEvent['creado_en'],
            'imagen' => $lastEvent['imagen'],
        ] : null,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
} catch (PDOException $e) {
    jsonErr("Error consultando estado: " . $e->getMessage(), 500);
}
