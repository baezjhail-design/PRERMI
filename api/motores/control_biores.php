<?php
session_start();
header('Content-Type: application/json');

// 🔐 Validación de sesión (solo admins)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "msg" => "No autorizado"]);
    exit;
}

if (!isset($_POST['accion'])) {
    echo json_encode(["status" => "error", "msg" => "Acción no recibida"]);
    exit;
}

$accion = strtoupper(trim($_POST['accion']));

// IP por defecto del ESP32-S3 — cambiar por la IP real en la red si es necesario
$esp_ip = isset($_POST['esp_ip']) && filter_var($_POST['esp_ip'], FILTER_VALIDATE_IP) ? $_POST['esp_ip'] : "192.168.1.102";
$timeout = 5;

// Mapear acciones de frontend a comandos/endpoints del ESP32
$map = [
    'START' => ['endpoint' => '/control', 'cmd' => 'HEATER_ON'],
    'STOP' => ['endpoint' => '/control', 'cmd' => 'HEATER_OFF'],
    'START_GENERACION' => ['endpoint' => '/control', 'cmd' => 'HEATER_ON'],
    'STOP_GENERACION' => ['endpoint' => '/control', 'cmd' => 'HEATER_OFF'],
    'EMERGENCY_STOP' => ['endpoint' => '/control', 'cmd' => 'EMERGENCY_OFF'],
    'GET_STATUS' => ['endpoint' => '/status', 'cmd' => null]
];

if (!array_key_exists($accion, $map)) {
    echo json_encode(["status" => "error", "msg" => "Acción inválida"]);
    exit;
}

// Helper: realizar petición HTTP GET con timeout
function http_get($url, $timeout = 5) {
    $context = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'method' => 'GET',
            'header' => "Accept: application/json\r\n"
        ]
    ]);

    $res = @file_get_contents($url, false, $context);
    return $res === FALSE ? null : $res;
}

$entry = $map[$accion];
$url = "http://$esp_ip" . $entry['endpoint'];

if ($entry['cmd'] !== null) {
    // Enviar comando, por ejemplo: http://IP/control?cmd=HEATER_ON
    $url .= '?cmd=' . urlencode($entry['cmd']);
}

error_log("Control BIORES: Enviando acción $accion => $url");

$respuesta = http_get($url, $timeout);

if ($respuesta === null) {
    error_log("Control BIORES: No se pudo conectar al ESP32 en $esp_ip para acción $accion");
    echo json_encode([
        'status' => 'error',
        'msg' => 'No se pudo conectar al dispositivo en ' . $esp_ip,
        'accion' => $accion,
        'esp_ip' => $esp_ip,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Si la acción fue GET_STATUS esperamos JSON con campos: heater, temp, fan, current
if ($accion === 'GET_STATUS') {
    $data = json_decode($respuesta, true);
    if ($data === null) {
        // Respuesta no JSON — devolver tal cual
        echo json_encode([
            'status' => 'ok',
            'msg' => 'Respuesta recibida del ESP32 (no JSON)',
            'raw' => trim($respuesta),
            'esp_ip' => $esp_ip,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        // Normalizar campos esperados
        $out = [
            'status' => 'ok',
            'msg' => 'Estado del equipo',
            'heater' => isset($data['heater']) ? $data['heater'] : null,
            'temperature' => isset($data['temperature']) ? $data['temperature'] : null,
            'fan' => isset($data['fan']) ? $data['fan'] : null,
            'current' => isset($data['current']) ? $data['current'] : null,
            'raw' => $data,
            'esp_ip' => $esp_ip,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($out);
    }
    exit;
}

// Para comandos de control devolver la respuesta raw del ESP32
echo json_encode([
    'status' => 'ok',
    'msg' => 'Comando enviado al ESP32',
    'accion' => $accion,
    'cmd' => $entry['cmd'],
    'respuesta_esp' => trim($respuesta),
    'esp_ip' => $esp_ip,
    'timestamp' => date('Y-m-d H:i:s')
]);

?>

