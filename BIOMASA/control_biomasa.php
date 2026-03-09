<?php
// control_biomasa.php — wrapper simple para la UI antigua.
// Acepta POST (form-urlencoded o JSON) con campo `accion` = START|STOP|EMERGENCY
// Guarda el comando en api/control.json para que el ESP lo recoja.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Helper: read token if exists
function expected_token() {
    $tfile = __DIR__ . '/../config/token.txt';
    if (file_exists($tfile)) return trim(file_get_contents($tfile));
    return null;
}

// Read action from form or JSON body
$rawAction = null;
if (!empty($_POST['accion'])) {
    $rawAction = $_POST['accion'];
} else {
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    if (is_array($json) && isset($json['accion'])) $rawAction = $json['accion'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$rawAction) {
    http_response_code(400);
    echo json_encode(['status'=>'error','msg'=>'invalid_request']);
    exit;
}

$accion = strtoupper(trim($rawAction));

// Actions supported: START, STOP, EMERGENCY, BYPASS_TEMP_ON/OFF, BYPASS_FAN_ON/OFF,
// BYPASS_HEATER_ON/OFF, BYPASS_CURRENT_ON/OFF, SYSTEM_OFF/SYSTEM_ON,
// TEMP_OFF, VENTILADOR_OFF, CORRIENTE_OFF, CALENTADOR_OFF

$map = [
    'START' => ['command' => 'start_generacion'],
    'STOP'  => ['command' => 'stop_generacion'],
    'EMERGENCY' => ['command' => 'emergency_off'],
    'BYPASS_TEMP_ON' => ['bypass_temp' => true],
    'BYPASS_TEMP_OFF' => ['bypass_temp' => false],
    'BYPASS_FAN_ON' => ['bypass_fan' => true],
    'BYPASS_FAN_OFF' => ['bypass_fan' => false],
    'BYPASS_HEATER_ON' => ['bypass_heater' => true],
    'BYPASS_HEATER_OFF' => ['bypass_heater' => false],
    'BYPASS_CURRENT_ON' => ['bypass_current' => true],
    'BYPASS_CURRENT_OFF' => ['bypass_current' => false],
    'SYSTEM_OFF' => ['system_off' => true],
    'SYSTEM_ON' => ['system_off' => false],
    'TEMP_OFF' => ['kill_temp' => true, 'bypass_temp' => true],
    'VENTILADOR_OFF' => ['kill_fan' => true, 'bypass_fan' => true],
    'CORRIENTE_OFF' => ['kill_current' => true, 'bypass_current' => true],
    'CALENTADOR_OFF' => ['kill_heater' => true, 'bypass_heater' => true]
];

if (!isset($map[$accion])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','msg'=>'invalid_action']);
    exit;
}

$actionChanges = $map[$accion];

// Optional token check (GET param or JSON/post field)
$expected = expected_token();
if ($expected !== null) {
    $provided = $_GET['token'] ?? ($_POST['token'] ?? null);
    if (!$provided || $provided !== $expected) {
        http_response_code(401);
        echo json_encode(['status'=>'error','msg'=>'invalid_token']);
        exit;
    }
}

$apiControl = __DIR__ . '/../api/control.json';

// Read existing control file
$control = [
    'command'=>'none',
    'raw'=>'none',
    'created_at'=>null,
    'sent_at'=>null,
    'command_id'=>0,
    'bypass_temp'=>false,
    'bypass_fan'=>false,
    'bypass_heater'=>false,
    'bypass_current'=>false,
    'system_off'=>false,
    'kill_temp'=>false,
    'kill_fan'=>false,
    'kill_heater'=>false,
    'kill_current'=>false
];
if (file_exists($apiControl)) {
    $c = json_decode(file_get_contents($apiControl), true);
    if (is_array($c)) $control = array_merge($control, $c);
}

// Apply changes
if (isset($actionChanges['command'])) {
    $control['command'] = $actionChanges['command'];
    $control['raw'] = $actionChanges['command'];
    $control['created_at'] = date(DATE_ATOM);
    $control['sent_at'] = null;
    
    // Si es START, resetear system_off para que el sistema inicie
    if ($actionChanges['command'] === 'start_generacion') {
        $control['system_off'] = false;
        $control['bypass_temp'] = false;
        $control['bypass_fan'] = false;
        $control['bypass_heater'] = false;
        $control['bypass_current'] = false;
        $control['kill_temp'] = false;
        $control['kill_fan'] = false;
        $control['kill_heater'] = false;
        $control['kill_current'] = false;
    }

    if ($actionChanges['command'] === 'stop_generacion') {
        $control['system_off'] = false;
    }

    if ($actionChanges['command'] === 'emergency_off') {
        $control['system_off'] = true;
        $control['kill_temp'] = true;
        $control['kill_fan'] = true;
        $control['kill_heater'] = true;
        $control['kill_current'] = true;
        $control['bypass_temp'] = true;
        $control['bypass_fan'] = true;
        $control['bypass_heater'] = true;
        $control['bypass_current'] = true;
    }
}
if (isset($actionChanges['bypass_temp'])) $control['bypass_temp'] = (bool)$actionChanges['bypass_temp'];
if (isset($actionChanges['bypass_fan'])) $control['bypass_fan'] = (bool)$actionChanges['bypass_fan'];
if (isset($actionChanges['bypass_heater'])) $control['bypass_heater'] = (bool)$actionChanges['bypass_heater'];
if (isset($actionChanges['bypass_current'])) $control['bypass_current'] = (bool)$actionChanges['bypass_current'];
if (isset($actionChanges['system_off'])) $control['system_off'] = (bool)$actionChanges['system_off'];
if (isset($actionChanges['kill_temp'])) $control['kill_temp'] = (bool)$actionChanges['kill_temp'];
if (isset($actionChanges['kill_fan'])) $control['kill_fan'] = (bool)$actionChanges['kill_fan'];
if (isset($actionChanges['kill_heater'])) $control['kill_heater'] = (bool)$actionChanges['kill_heater'];
if (isset($actionChanges['kill_current'])) $control['kill_current'] = (bool)$actionChanges['kill_current'];

if ($accion === 'SYSTEM_OFF') {
    $control['command'] = 'emergency_off';
    $control['raw'] = 'system_off';
    $control['system_off'] = true;
    $control['kill_temp'] = true;
    $control['kill_fan'] = true;
    $control['kill_heater'] = true;
    $control['kill_current'] = true;
    $control['bypass_temp'] = true;
    $control['bypass_fan'] = true;
    $control['bypass_heater'] = true;
    $control['bypass_current'] = true;
    $control['created_at'] = date(DATE_ATOM);
    $control['sent_at'] = null;
}

if ($accion === 'SYSTEM_ON') {
    $control['system_off'] = false;
    $control['kill_temp'] = false;
    $control['kill_fan'] = false;
    $control['kill_heater'] = false;
    $control['kill_current'] = false;
    $control['bypass_temp'] = false;
    $control['bypass_fan'] = false;
    $control['bypass_heater'] = false;
    $control['bypass_current'] = false;
}

$control['command_id'] = intval($control['command_id'] ?? 0) + 1;

// persist
if (file_put_contents($apiControl, json_encode($control, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    echo json_encode(['status'=>'error','msg'=>'write_failed']);
    exit;
}

echo json_encode(['status'=>'ok','stored'=>$control]);

?>
