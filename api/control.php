<?php
// PRERMI/api/control.php
// Endpoint para la web: setear comandos que el ESP32 recogerá

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$base = __DIR__;
$controlFile = $base . '/control.json';

function get_expected_token() {
    $tfile = __DIR__ . '/../config/token.txt';
    if (file_exists($tfile)) return trim(file_get_contents($tfile));
    return null;
}

function check_token($inputToken) {
    $expected = get_expected_token();
    if ($expected === null) return true;
    return ($inputToken !== null && $inputToken === $expected);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!file_exists($controlFile)) {
        copy(__DIR__ . '/control.json', $controlFile);
    }
    $control = json_decode(file_get_contents($controlFile), true);
    echo json_encode($control);
    exit;
}

if ($method === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_array($data)) { http_response_code(400); echo json_encode(['error'=>'invalid_json']); exit; }

    $token = isset($data['token']) ? $data['token'] : (isset($_GET['token'])?$_GET['token']:null);
    if (!check_token($token)) { http_response_code(401); echo json_encode(['error'=>'invalid_token']); exit; }

    $allowed = ['start_generacion','stop_generacion','emergency_off','none'];
    $cmd = isset($data['command']) ? $data['command'] : null;
    if ($cmd === null || !in_array($cmd, $allowed)) { http_response_code(400); echo json_encode(['error'=>'invalid_command']); exit; }

    $control = [
        'command' => $cmd,
        'raw' => $cmd,
        'created_at' => date(DATE_ATOM),
        'sent_at' => null,
        'command_id' => isset($data['command_id']) ? intval($data['command_id']) : 0
    ];
    file_put_contents($controlFile, json_encode($control, JSON_PRETTY_PRINT));
    echo json_encode(['ok'=>true,'stored'=>$control]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'method_not_allowed']);
