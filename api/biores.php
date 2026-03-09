<?php
// PRERMI/api/biores.php
// Controlador bidireccional para ESP32: GET => entregar comando; POST => recibir estado

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$baseDir = __DIR__;
$controlFile = $baseDir . '/control.json';
$statusFile  = $baseDir . '/status.json';

// Simple token validation: optional. If file config/token.txt exists, require matching token param
function get_expected_token() {
    $tfile = __DIR__ . '/../config/token.txt';
    if (file_exists($tfile)) return trim(file_get_contents($tfile));
    return null;
}

function check_token($inputToken) {
    $expected = get_expected_token();
    if ($expected === null) return true; // no token configured => allow
    return ($inputToken !== null && $inputToken === $expected);
}

// Read raw body for POST
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $token = isset($_GET['token']) ? $_GET['token'] : null;
    if (!check_token($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_token']);
        exit;
    }

    if (!file_exists($controlFile)) {
        file_put_contents($controlFile, json_encode(["command"=>"none","raw"=>"none","created_at"=>null,"sent_at"=>null,"command_id"=>0], JSON_PRETTY_PRINT));
    }
    $control = json_decode(file_get_contents($controlFile), true);

    // On GET, return the current command for the ESP32
    $actionMap = [
        'start_generacion' => 'start',
        'stop_generacion'  => 'stop',
        'emergency_off'    => 'emergency'
    ];

    $raw = isset($control['command']) ? $control['command'] : 'none';
    $action = isset($actionMap[$raw]) ? $actionMap[$raw] : 'none';

    // update sent_at and increment command_id when ESP32 polls
    $control['sent_at'] = date(DATE_ATOM);
    if (!isset($control['command_id'])) $control['command_id'] = 0;
    $control['command_id'] = intval($control['command_id']) + 1;
    file_put_contents($controlFile, json_encode($control, JSON_PRETTY_PRINT));

    // include bypass flags so device can act immediately
    $bypass_temp = isset($control['bypass_temp']) ? (bool)$control['bypass_temp'] : false;
    $bypass_fan = isset($control['bypass_fan']) ? (bool)$control['bypass_fan'] : false;
    $bypass_heater = isset($control['bypass_heater']) ? (bool)$control['bypass_heater'] : false;
    $bypass_current = isset($control['bypass_current']) ? (bool)$control['bypass_current'] : false;
    $system_off = isset($control['system_off']) ? (bool)$control['system_off'] : false;

    echo json_encode([
        'accion' => $action,
        'raw'    => $raw,
        'command_id' => $control['command_id'],
        'sent_at' => $control['sent_at'],
        'bypass_temp' => $bypass_temp,
        'bypass_fan' => $bypass_fan,
        'bypass_heater' => $bypass_heater,
        'bypass_current' => $bypass_current,
        'system_off' => $system_off
    ]);
    exit;
}

if ($method === 'POST') {
    // ESP32 posts JSON with sensores y estado de relés
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    $token = null;
    if (isset($data['token'])) $token = $data['token'];

    if (!check_token($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_token']);
        exit;
    }

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_json']);
        exit;
    }

    // Only keep known fields
    $status = [];
    if (isset($data['temperatura'])) $status['temperatura'] = floatval($data['temperatura']);
    if (isset($data['corriente'])) $status['corriente'] = floatval($data['corriente']);
    if (isset($data['calentador'])) $status['calentador'] = intval($data['calentador']);
    if (isset($data['ventilador'])) $status['ventilador'] = intval($data['ventilador']);
    $status['updated_at'] = date(DATE_ATOM);

    // merge with existing status file
    $existing = [];
    if (file_exists($statusFile)) {
        $existing = json_decode(file_get_contents($statusFile), true);
        if (!is_array($existing)) $existing = [];
    }
    $merged = array_merge($existing, $status);
    file_put_contents($statusFile, json_encode($merged, JSON_PRETTY_PRINT));

    echo json_encode(['ok' => true, 'stored' => $merged]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);
