<?php
session_start();
header('Content-Type: application/json');

// 🔐 Validación de sesión (solo admins y ESP32/ESP8266)
$tokens_validos = [
    'esp8266_sensor_token',      // ESP8266 antiguo
    'esp32_devkit1_biomasa',     // ESP32 DEVKIT1 nuevo
    'esp8266_sensor_token'       // ESP32-S3 CAM (usa token esp8266)
];

if (!isset($_SESSION['user_id']) && (!isset($_GET['token']) || !in_array($_GET['token'], $tokens_validos))) {
    echo json_encode(["status" => "error", "msg" => "No autorizado"]);
    exit;
}

// Directorio para almacenar comandos
$cmd_dir = __DIR__ . '/../../data/comandos';
if (!is_dir($cmd_dir)) {
    mkdir($cmd_dir, 0777, true);
}

$cmd_file = $cmd_dir . '/comandos_pendientes.json';

// Si es GET: verificar comandos pendientes (desde Arduino)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($cmd_file)) {
        $comandos = json_decode(file_get_contents($cmd_file), true);
        
        // Si hay comandos pendientes
        if (!empty($comandos) && is_array($comandos)) {
            $comando = array_shift($comandos);  // Sacar el primer comando
            
            // Guardar los comandos restantes
            file_put_contents($cmd_file, json_encode($comandos, JSON_PRETTY_PRINT));
            
            // Retornar el comando al Arduino
            echo json_encode([
                "status" => "ok",
                "comando" => $comando['tipo'],
                "descripcion" => $comando['descripcion'],
                "timestamp" => $comando['timestamp']
            ]);
        } else {
            // Sin comandos pendientes
            echo json_encode([
                "status" => "sin_comandos",
                "msg" => "No hay comandos pendientes"
            ]);
        }
    } else {
        // Archivo no existe, sin comandos
        echo json_encode([
            "status" => "sin_comandos",
            "msg" => "No hay comandos pendientes"
        ]);
    }
    exit;
}

// Si es POST: agregar nuevo comando (desde PRERMI web)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar autenticación: sesión o token
    $input = json_decode(file_get_contents('php://input'), true);
    $token_post = $input['token'] ?? '';
    
    $autenticado = isset($_SESSION['user_id']) || in_array($token_post, $tokens_validos);
    
    if (!$autenticado) {
        echo json_encode(["status" => "error", "msg" => "No autorizado. Token o sesión requerido"]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['comando'])) {
        echo json_encode(["status" => "error", "msg" => "Comando no especificado"]);
        exit;
    }
    
    // Cargar comandos existentes
    $comandos = [];
    if (file_exists($cmd_file)) {
        $comandos = json_decode(file_get_contents($cmd_file), true);
        if (!is_array($comandos)) {
            $comandos = [];
        }
    }
    
    // Agregar nuevo comando
    $nuevo_comando = [
        "tipo" => $input['comando'],
        "descripcion" => $input['descripcion'] ?? "Comando: " . $input['comando'],
        "timestamp" => date('Y-m-d H:i:s'),
        "usuario" => $_SESSION['user_id'] ?? $token_post ?? "web"
    ];
    
    $comandos[] = $nuevo_comando;
    
    // Guardar comandos
    file_put_contents($cmd_file, json_encode($comandos, JSON_PRETTY_PRINT));
    
    // Log del comando
    error_log("BIOMASA: Comando '{$input['comando']}' enviado por usuario {$_SESSION['user_id']} a las " . date('Y-m-d H:i:s'));
    
    echo json_encode([
        "status" => "ok",
        "msg" => "Comando enviado al Arduino: " . $input['comando'],
        "comando" => $nuevo_comando
    ]);
    exit;
}

// Método no permitido
http_response_code(405);
echo json_encode(["status" => "error", "msg" => "Método no permitido"]);
?>
