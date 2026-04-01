<?php
/**
 * API Documentation - Semáforo Inteligente
 * 
 * Base URL: https://prermi.duckdns.org/PRERMI/api
 */

require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'info';

$config = [
    'semaforo_id' => 'SEMAFORO-001',
    'ubicacion' => 'Santiago - Interseccion Demo',
    'latitud' => 19.4517,
    'longitud' => -70.6970,
    'ciclos' => [
        'verde_ms' => 12000,
        'amarillo_ms' => 2500,
        'rojo_ms' => 10000,
    ],
    'sensores' => [
        'presencia_vehicular' => [
            'pin' => 40,
            'tipo' => 'INPUT_PULLDOWN',
            'descripcion' => 'Sensor ultrasónico o de presencia',
        ],
        'linea_roja_ir' => [
            'pin' => 39,
            'tipo' => 'INPUT_PULLDOWN',
            'descripcion' => 'Sensor IR para detectar paso en rojo',
        ],
    ],
    'leds' => [
        'rojo' => 47,
        'amarillo' => 14,
        'verde' => 48,
    ],
];

$endpoints = [
    'POST /api/vehiculos/report_event.php' => [
        'descripcion' => 'Registrar evento de semáforo (normal o rojo)',
        'autenticacion' => 'X-MCU-ID, X-MCU-KEY',
        'body' => [
            'evento' => 'vehiculo_detectado|semaforo_rojo (normal=compatibilidad)',
            'placa' => 'DESCONOCIDA',
            'tipo_vehiculo' => 'Desconocido',
            'modelo_ml' => 'VPS-Classifier',
            'probabilidad' => 0.0,
            'ubicacion' => 'string',
            'latitud' => 'float',
            'longitud' => 'float',
            'imagen_base64' => 'JPEG base64 encoded',
            'nota' => 'Detalle del evento',
        ],
    ],
    'GET /api/vehiculos/semaforo_status.php' => [
        'descripcion' => 'Obtener estado actual del semáforo y estadísticas',
        'parametros' => [],
        'respuesta' => [
            'semaforo' => 'estado, tiempo restante, configuración',
            'estadisticas_24h' => 'eventos normales, violaciones, sanciones',
            'ultimo_evento' => 'último evento registrado',
        ],
    ],
    'GET /api/vehiculos/obtener_capturas.php' => [
        'descripcion' => 'Obtener lista de capturas con filtros',
        'parametros' => [
            'limit' => 'number (1-500, default 100)',
            'evento' => 'all|vehiculo_detectado|semaforo_rojo (normal=compatibilidad)',
        ],
    ],
];

switch ($action) {
    case 'config':
        jsonOk(['config' => $config]);
        break;
    case 'endpoints':
        jsonOk(['endpoints' => $endpoints]);
        break;
    case 'info':
    default:
        jsonOk([
            'sistema' => 'Semáforo Inteligente ESP32-S3 CAM',
            'version' => '1.0.0',
            'fecha' => date('Y-m-d H:i:s'),
            'documentacion' => 'Agregar ?action=config para config, ?action=endpoints para API',
            'config' => $config,
            'endpoints_principales' => array_keys($endpoints),
        ]);
        break;
}
