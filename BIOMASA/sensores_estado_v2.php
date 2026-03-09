<?php
// sensores_estado_v2.php — API mejorado para BIOMASA con soporte a energía
// Recibe datos del ESP8266 (temperatura, corriente, ventilador, etc.)
// Responde con estado formateado para la UI biores.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ============================================
// CONFIGURACION
// ============================================
$statusFile = __DIR__ . '/../api/status.json';
$controlFile = __DIR__ . '/../api/control.json';
$energyFile = __DIR__ . '/../api/mediciones_biomasa.json';

// ============================================
// LECTURA DE ESTADO ACTUAL
// ============================================
$status = [
    'temperatura' => 0,
    'corriente' => 0,
    'ventilador' => 0,
    'calentador' => 0,
    'energia_generada' => 0,
    'sistema_activo' => 0,
    'updated_at' => null
];

if (file_exists($statusFile)) {
    $data = json_decode(file_get_contents($statusFile), true);
    if (is_array($data)) {
        $status = array_merge($status, $data);
    }
}

$control = [
    'command' => 'none',
    'raw' => 'none',
    'command_id' => 0,
    'bypass_temp' => false,
    'bypass_fan' => false,
    'bypass_heater' => false,
    'bypass_current' => false,
    'system_off' => false
];

if (file_exists($controlFile)) {
    $data = json_decode(file_get_contents($controlFile), true);
    if (is_array($data)) {
        $control = array_merge($control, $data);
    }
}

// ============================================
// GET: Devolver estado actual + comando
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $respuesta = [
        'status' => 'ok',
        'data' => [
            'sistema_activo' => (bool)$status['sistema_activo'],
            'temperatura' => [
                'estado' => $status['sistema_activo'] ? 'activo' : 'apagado',
                'valor' => $status['temperatura'],
                'timestamp' => $status['updated_at']
            ],
            'ventilador' => [
                'estado' => (bool)$status['ventilador'] ? 'activo' : 'apagado',
                'valor' => $status['ventilador'],
                'timestamp' => $status['updated_at']
            ],
            'corriente' => [
                'estado' => (float)$status['corriente'] > 0.1 ? 'activo' : 'apagado',
                'valor' => $status['corriente'],
                'timestamp' => $status['updated_at']
            ],
            'energia_generada' => $status['energia_generada'],
            'tiempo_operacion' => $status['updated_at']
        ]
    ];
    
    echo json_encode($respuesta);
    exit;
}

// ============================================
// POST: Recibir datos del ESP8266
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'invalid_json']);
        exit;
    }
    
    // Actualizar status
    if (isset($data['temperatura'])) $status['temperatura'] = floatval($data['temperatura']);
    if (isset($data['corriente'])) $status['corriente'] = floatval($data['corriente']);
    if (isset($data['ventilador'])) $status['ventilador'] = intval($data['ventilador']);
    if (isset($data['calentador'])) $status['calentador'] = intval($data['calentador']);
    if (isset($data['energia_generada'])) $status['energia_generada'] = floatval($data['energia_generada']);
    if (isset($data['sistema_activo'])) $status['sistema_activo'] = intval($data['sistema_activo']);
    
    $status['updated_at'] = date(DATE_ATOM);
    
    // Guardar status
    if (file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => 'write_failed']);
        exit;
    }
    
    // Registrar medicion en archivo de mediciones
    $medicion = [
        'temperatura' => $status['temperatura'],
        'corriente' => $status['corriente'],
        'ventilador' => $status['ventilador'],
        'calentador' => $status['calentador'],
        'energia_generada' => $status['energia_generada'],
        'timestamp' => $status['updated_at']
    ];
    
    // Append a archivo de mediciones
    $mediciones = [];
    if (file_exists($energyFile)) {
        $mediciones = json_decode(file_get_contents($energyFile), true);
        if (!is_array($mediciones)) $mediciones = [];
    }
    
    $mediciones[] = $medicion;
    
    // Mantener solo últimas 1000 mediciones
    if (count($mediciones) > 1000) {
        $mediciones = array_slice($mediciones, -1000);
    }
    
    file_put_contents($energyFile, json_encode($mediciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // Responder con comando actual
    $respuesta = [
        'status' => 'ok',
        'accion' => $control['command'] ?? 'none',
        'raw' => $control['raw'] ?? 'none',
        'command_id' => $control['command_id'] ?? 0,
        'bypass_temp' => (bool)($control['bypass_temp'] ?? false),
        'bypass_fan' => (bool)($control['bypass_fan'] ?? false),
        'bypass_heater' => (bool)($control['bypass_heater'] ?? false),
        'bypass_current' => (bool)($control['bypass_current'] ?? false),
        'system_off' => (bool)($control['system_off'] ?? false)
    ];
    
    echo json_encode($respuesta);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'msg' => 'invalid_method']);
?>
