<?php
/**
 * ============================================================================
 * CONTROL_API.php
 * ============================================================================
 * API para control remoto del camión de recolección de residuos
 * Recibe comandos de la interfaz web y los envía al ESP8266 vía HTTP
 * 
 * Comandos soportados:
 *   - FORWARD: Movimiento hacia adelante
 *   - BACKWARD: Movimiento hacia atrás
 *   - LEFT: Giro a la izquierda
 *   - RIGHT: Giro a la derecha
 *   - STOP: Detener todos los motores
 *   - SERVO1_MOVE: Mover servomotor 1
 *   - SERVO2_MOVE: Mover servomotor 2
 *   - EMERGENCY_STOP: Parada de emergencia
 * ============================================================================
 */

// Habilitar CORS para solicitudes desde la interfaz
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// CONFIGURACIÓN
// ============================================================================

// IP y puerto del ESP8266 - MODIFICAR SEGÚN TU CONFIGURACIÓN
const ESP8266_HOST = '10.0.0.1';  // IP del ESP8266 en tu red WiFi
const ESP8266_PORT = 8080;         // Puerto del servidor en el ESP8266
const TIMEOUT = 5;                 // Timeout en segundos para la conexión

// Archivo de log para monitoreo
const LOG_FILE = __DIR__ . '/control_log.txt';

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

/**
 * Registra un evento en el archivo de log
 * @param string $message Mensaje a registrar
 * @param string $type Tipo de mensaje: 'INFO', 'SUCCESS', 'ERROR'
 */
function logEvent($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

/**
 * Envía un comando HTTP al ESP8266
 * @param string $command Comando a ejecutar
 * @param array $data Datos adicionales del comando
 * @return bool True si fue exitoso, false si falló
 */
function sendToESP8266($command, $data = array()) {
    // Construir la URL del endpoint en el ESP8266
    $url = "http://" . ESP8266_HOST . ":" . ESP8266_PORT . "/control";
    
    // Preparar los datos a enviar
    $payload = array(
        'command' => $command,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    );
    
    // Opciones para la solicitud cURL
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => TIMEOUT,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Connection: close'
        )
    );
    
    // Ejecutar la solicitud
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    // Registrar el resultado
    if ($httpCode === 200 && !$curlError) {
        logEvent("Comando enviado: $command - Respuesta HTTP: $httpCode", 'SUCCESS');
        return true;
    } else {
        $errorMsg = !empty($curlError) ? $curlError : "HTTP $httpCode";
        logEvent("Error al enviar comando: $command - Error: $errorMsg", 'ERROR');
        return false;
    }
}

/**
 * Responde al cliente con un JSON
 * @param bool $success Si la operación fue exitosa
 * @param string $message Mensaje de respuesta
 * @param array $data Datos adicionales a incluir
 */
function respondJSON($success, $message, $data = array()) {
    $response = array(
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}

// ============================================================================
// PROCESAMIENTO DE SOLICITUDES
// ============================================================================

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJSON(false, 'Solo se aceptan solicitudes POST');
}

// Obtener los datos JSON del cuerpo de la solicitud
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Validar que se recibieron datos JSON válidos
if ($input === null) {
    respondJSON(false, 'Error al decodificar JSON');
}

// Extraer el comando y los parámetros
$command = isset($input['command']) ? trim($input['command']) : '';
$in1Speed = isset($input['in1']) ? intval($input['in1']) : 255;
$in2Speed = isset($input['in2']) ? intval($input['in2']) : 255;
$in3Speed = isset($input['in3']) ? intval($input['in3']) : 255;
$in4Speed = isset($input['in4']) ? intval($input['in4']) : 255;
$servo1Angle = isset($input['servo1']) ? intval($input['servo1']) : 90;
$servo2Angle = isset($input['servo2']) ? intval($input['servo2']) : 90;

// Validar que se proporcionó un comando
if (empty($command)) {
    respondJSON(false, 'Comando no especificado');
}

logEvent("Comando recibido: $command | IN1: $in1Speed | IN2: $in2Speed | IN3: $in3Speed | IN4: $in4Speed | Servo1: $servo1Angle | Servo2: $servo2Angle");

// ============================================================================
// PROCESAMIENTO DE COMANDOS
// ============================================================================

// Datos a enviar al ESP8266
$commandData = array(
    'in1' => $in1Speed,
    'in2' => $in2Speed,
    'in3' => $in3Speed,
    'in4' => $in4Speed,
    'servo1' => $servo1Angle,
    'servo2' => $servo2Angle
);

switch ($command) {
    // Movimiento hacia adelante (todas las ruedas en la misma dirección)
    case 'FORWARD':
        $commandData['direction'] = 'FORWARD';
        if (sendToESP8266('FORWARD', $commandData)) {
            respondJSON(true, 'Camión moviéndose hacia adelante', $commandData);
        } else {
            respondJSON(false, 'No se pudo contactar al ESP8266');
        }
        break;

    // Movimiento hacia atrás
    case 'BACKWARD':
        $commandData['direction'] = 'BACKWARD';
        if (sendToESP8266('BACKWARD', $commandData)) {
            respondJSON(true, 'Camión moviéndose hacia atrás', $commandData);
        } else {
            respondJSON(false, 'No se pudo contactar al ESP8266');
        }
        break;

    // Giro a la izquierda (ruedas izquierdas inviersa, derechas adelante)
    case 'LEFT':
        $commandData['direction'] = 'LEFT';
        if (sendToESP8266('LEFT', $commandData)) {
            respondJSON(true, 'Camión girando a la izquierda', $commandData);
        } else {
            respondJSON(false, 'No se pudo contactar al ESP8266');
        }
        break;

    // Giro a la derecha (ruedas derechas inversa, izquierdas adelante)
    case 'RIGHT':
        $commandData['direction'] = 'RIGHT';
        if (sendToESP8266('RIGHT', $commandData)) {
            respondJSON(true, 'Camión girando a la derecha', $commandData);
        } else {
            respondJSON(false, 'No se pudo contactar al ESP8266');
        }
        break;

    // Detener todos los motores
    case 'STOP':
        $commandData['direction'] = 'STOP';
        if (sendToESP8266('STOP', $commandData)) {
            respondJSON(true, 'Camión detenido', $commandData);
        } else {
            respondJSON(false, 'No se pudo contactar al ESP8266');
        }
        break;

    // Mover servomotor 1
    case 'SERVO1_MOVE':
        $commandData['servo'] = 1;
        $commandData['angle'] = $servo1Angle;
        if (sendToESP8266('SERVO1_MOVE', $commandData)) {
            respondJSON(true, "Servomotor 1 movido a $servo1Angle°", $commandData);
        } else {
            respondJSON(false, 'No se pudo contactar al ESP8266');
        }
        break;

    // Mover servomotor 2
    case 'SERVO2_MOVE':
        $commandData['servo'] = 2;
        $commandData['angle'] = $servo2Angle;
        if (sendToESP8266('SERVO2_MOVE', $commandData)) {
            respondJSON(true, "Servomotor 2 movido a $servo2Angle°", $commandData);
        } else {
            respondJSON(false, 'No se pudo contactar al ESP8266');
        }
        break;

    // Parada de emergencia - Detiene TODOS los motores inmediatamente
    case 'EMERGENCY_STOP':
        logEvent('PARADA DE EMERGENCIA ACTIVADA', 'ERROR');
        if (sendToESP8266('EMERGENCY_STOP', $commandData)) {
            respondJSON(true, 'PARADA DE EMERGENCIA ACTIVADA - Todos los motores detenidos', $commandData);
        } else {
            respondJSON(false, 'No se pudo contactar al ESP8266');
        }
        break;

    // Comando desconocido
    default:
        respondJSON(false, "Comando desconocido: $command");
}

?>
