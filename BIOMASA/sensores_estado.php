<?php
// sensores_estado.php — endpoint para BIOMASA que recibe/devuelve datos en tiempo real
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Optional token validation
function expected_token() {
    $tfile = __DIR__ . '/../config/token.txt';
    if (file_exists($tfile)) return trim(file_get_contents($tfile));
    return null;
}

$apiStatus = __DIR__ . '/../api/status.json';
$apiControl = __DIR__ . '/../api/control.json';
$medicionesFile = __DIR__ . '/../api/mediciones_biomasa.json';

// Inicializar status
$status = [
    'temperatura' => 0,
    'corriente' => 0,
    'ventilador' => 0,
    'calentador' => 0,
    'energia_generada' => 0,
    'sistema_activo' => 0,
    'updated_at' => null
];

if (file_exists($apiStatus)) {
    $data = json_decode(file_get_contents($apiStatus), true);
    if (is_array($data)) {
        $status = array_merge($status, $data);
    }
}

$control = [];
if (file_exists($apiControl)) {
    $control = json_decode(file_get_contents($apiControl), true);
    if (!is_array($control)) $control = [];
}

if (!isset($control['kill_temp'])) $control['kill_temp'] = false;
if (!isset($control['kill_fan'])) $control['kill_fan'] = false;
if (!isset($control['kill_heater'])) $control['kill_heater'] = false;
if (!isset($control['kill_current'])) $control['kill_current'] = false;

// If GET: return combined status for UI
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // =============================================
    // ACCESO DIRECTO POR URL DESDE ESP (?esp=1)
    // =============================================
    if (isset($_GET['esp'])) {
        // Si vienen datos de sensores en la URL, guardarlos
        if (isset($_GET['temperatura']) || isset($_GET['corriente'])) {
            if (isset($_GET['temperatura'])) $status['temperatura'] = floatval($_GET['temperatura']);
            if (isset($_GET['corriente'])) $status['corriente'] = floatval($_GET['corriente']);
            if (isset($_GET['ventilador'])) $status['ventilador'] = intval($_GET['ventilador']);
            if (isset($_GET['calentador'])) $status['calentador'] = intval($_GET['calentador']);
            if (isset($_GET['energia_generada'])) $status['energia_generada'] = floatval($_GET['energia_generada']);
            if (isset($_GET['sistema_activo'])) $status['sistema_activo'] = intval($_GET['sistema_activo']);
            $status['updated_at'] = date(DATE_ATOM);
            file_put_contents($apiStatus, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Guardar en historial de mediciones
            $mediciones = [];
            if (file_exists($medicionesFile)) {
                $mediciones = json_decode(file_get_contents($medicionesFile), true);
                if (!is_array($mediciones)) $mediciones = [];
            }
            $mediciones[] = [
                'temperatura' => $status['temperatura'],
                'corriente' => $status['corriente'],
                'ventilador' => $status['ventilador'],
                'calentador' => $status['calentador'],
                'energia_generada' => $status['energia_generada'],
                'timestamp' => $status['updated_at']
            ];
            if (count($mediciones) > 1000) $mediciones = array_slice($mediciones, -1000);
            file_put_contents($medicionesFile, json_encode($mediciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        // Responder con comandos en formato ESP-friendly
        $respuesta = [
            'status' => 'ok',
            'accion' => $control['command'] ?? 'none',
            'raw' => $control['raw'] ?? 'none',
            'command_id' => intval($control['command_id'] ?? 0),
            'bypass_temp' => (bool)($control['bypass_temp'] ?? $control['kill_temp'] ?? false),
            'bypass_fan' => (bool)($control['bypass_fan'] ?? $control['kill_fan'] ?? false),
            'bypass_heater' => (bool)($control['bypass_heater'] ?? $control['kill_heater'] ?? false),
            'bypass_current' => (bool)($control['bypass_current'] ?? $control['kill_current'] ?? false),
            'system_off' => (bool)($control['system_off'] ?? false)
        ];
        echo json_encode($respuesta);
        exit;
    }

    $sistema_activo = (isset($status['sistema_activo']) && intval($status['sistema_activo']) === 1);
    $ts = isset($status['updated_at']) ? $status['updated_at'] : null;

    $resp = [
        'status' => 'ok',
        'data' => [
            'sistema_activo' => $sistema_activo,
            'temperatura' => [
                'estado' => $sistema_activo ? 'activo' : 'apagado',
                'valor' => $status['temperatura'] ?? 0,
                'timestamp' => $ts
            ],
            'ventilador' => [
                'estado' => (isset($status['ventilador']) && intval($status['ventilador'])===1) ? 'activo' : 'apagado',
                'valor' => $status['ventilador'] ?? 0,
                'timestamp' => $ts
            ],
            'corriente' => [
                'estado' => (isset($status['corriente']) && floatval($status['corriente'])>0.1) ? 'sensando' : 'apagado',
                'valor' => $status['corriente'] ?? 0,
                'timestamp' => $ts
            ],
            'energia_generada' => $status['energia_generada'] ?? 0,
            'control' => [
                'last_command' => $control['command'] ?? 'none',
                'command_id' => intval($control['command_id'] ?? 0),
                'system_off' => (bool)($control['system_off'] ?? false),
                'kill_temp' => (bool)($control['kill_temp'] ?? false),
                'kill_fan' => (bool)($control['kill_fan'] ?? false),
                'kill_heater' => (bool)($control['kill_heater'] ?? false),
                'kill_current' => (bool)($control['kill_current'] ?? false)
            ]
        ]
    ];

    echo json_encode($resp);
    exit;
}

// If POST: Recibir datos del ESP8266
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'invalid_json']);
        exit;
    }

    // Compatibilidad: si llega una acción de emergencia desde frontend, encolar en control.json
    if (isset($data['accion'])) {
        $accion = strtoupper(trim((string)$data['accion']));
        $map = [
            'TEMP_OFF' => ['kill_temp' => true, 'bypass_temp' => true],
            'VENTILADOR_OFF' => ['kill_fan' => true, 'bypass_fan' => true],
            'CORRIENTE_OFF' => ['kill_current' => true, 'bypass_current' => true],
            'CALENTADOR_OFF' => ['kill_heater' => true, 'bypass_heater' => true],
            'SYSTEM_OFF' => [
                'command' => 'emergency_off',
                'system_off' => true,
                'kill_temp' => true,
                'kill_fan' => true,
                'kill_heater' => true,
                'kill_current' => true,
                'bypass_temp' => true,
                'bypass_fan' => true,
                'bypass_heater' => true,
                'bypass_current' => true
            ]
        ];

        if (isset($map[$accion])) {
            $changes = $map[$accion];
            foreach ($changes as $k => $v) {
                $control[$k] = $v;
            }
            $control['raw'] = strtolower($accion);
            $control['created_at'] = date(DATE_ATOM);
            $control['sent_at'] = null;
            $control['command_id'] = intval($control['command_id'] ?? 0) + 1;
            file_put_contents($apiControl, json_encode($control, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            echo json_encode(['status' => 'ok', 'queued' => $control]);
            exit;
        }
    }
    
    // Actualizar status con datos del ESP8266
    if (isset($data['temperatura'])) $status['temperatura'] = floatval($data['temperatura']);
    if (isset($data['corriente'])) $status['corriente'] = floatval($data['corriente']);
    if (isset($data['ventilador'])) $status['ventilador'] = intval($data['ventilador']);
    if (isset($data['calentador'])) $status['calentador'] = intval($data['calentador']);
    if (isset($data['energia_generada'])) $status['energia_generada'] = floatval($data['energia_generada']);
    if (isset($data['sistema_activo'])) $status['sistema_activo'] = intval($data['sistema_activo']);
    
    $status['updated_at'] = date(DATE_ATOM);
    
    // Guardar status
    if (file_put_contents($apiStatus, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => 'write_failed']);
        exit;
    }
    
    // Guardar medicion en historial
    $mediciones = [];
    if (file_exists($medicionesFile)) {
        $mediciones = json_decode(file_get_contents($medicionesFile), true);
        if (!is_array($mediciones)) $mediciones = [];
    }
    
    $mediciones[] = [
        'temperatura' => $status['temperatura'],
        'corriente' => $status['corriente'],
        'ventilador' => $status['ventilador'],
        'calentador' => $status['calentador'],
        'energia_generada' => $status['energia_generada'],
        'timestamp' => $status['updated_at']
    ];
    
    // Mantener solo últimas 1000 mediciones
    if (count($mediciones) > 1000) {
        $mediciones = array_slice($mediciones, -1000);
    }
    
    file_put_contents($medicionesFile, json_encode($mediciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
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
        'system_off' => (bool)($control['system_off'] ?? false),
        'kill_temp' => (bool)($control['kill_temp'] ?? false),
        'kill_fan' => (bool)($control['kill_fan'] ?? false),
        'kill_heater' => (bool)($control['kill_heater'] ?? false),
        'kill_current' => (bool)($control['kill_current'] ?? false)
    ];
    
    echo json_encode($respuesta);
    exit;
}

http_response_code(405);
echo json_encode(['status'=>'error','msg'=>'method_not_allowed']);
?>
