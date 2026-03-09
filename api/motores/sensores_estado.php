<?php
session_start();
header('Content-Type: application/json');

// 🔐 Validación de sesión (solo admins y ESP8266)
if (!isset($_SESSION['user_id']) && $_GET['token'] !== 'esp8266_sensor_token') {
    echo json_encode(["status" => "error", "msg" => "No autorizado"]);
    exit;
}

// Directorio para almacenar estado de sensores
$sensor_dir = __DIR__ . '/../../data/sensores';
if (!is_dir($sensor_dir)) {
    mkdir($sensor_dir, 0777, true);
}

$sensor_file = $sensor_dir . '/estado.json';

// Si es GET: devolver estado actual
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($sensor_file)) {
        $estado = json_decode(file_get_contents($sensor_file), true);
    } else {
        // Estado inicial: todo apagado
        $estado = [
            "temperatura" => ["estado" => "apagado", "valor" => "N/A", "timestamp" => date('Y-m-d H:i:s')],
            "ventilador" => ["estado" => "apagado", "valor" => "N/A", "timestamp" => date('Y-m-d H:i:s')],
            "corriente" => ["estado" => "apagado", "valor" => "N/A", "timestamp" => date('Y-m-d H:i:s')]
        ];
    }
    
    echo json_encode([
        "status" => "ok",
        "data" => $estado
    ]);
    exit;
}

// Si es POST: actualizar estado (desde ESP8266 o administrador)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['accion'])) {
        echo json_encode(["status" => "error", "msg" => "Acción no especificada"]);
        exit;
    }
    
    $accion = $input['accion'];
    
    // Leer estado actual
    if (file_exists($sensor_file)) {
        $estado = json_decode(file_get_contents($sensor_file), true);
    } else {
        $estado = [
            "temperatura" => ["estado" => "apagado", "valor" => "N/A"],
            "ventilador" => ["estado" => "apagado", "valor" => "N/A"],
            "corriente" => ["estado" => "apagado", "valor" => "N/A"]
        ];
    }
    
    // Actualizar según acción
    switch ($accion) {
        case 'temp_on':
            $estado["temperatura"] = [
                "estado" => "activo",
                "valor" => $input['valor'] ?? "N/A",
                "timestamp" => date('Y-m-d H:i:s')
            ];
            break;
        
        case 'temp_off':
            $estado["temperatura"] = [
                "estado" => "apagado",
                "valor" => "N/A",
                "timestamp" => date('Y-m-d H:i:s')
            ];
            break;
        
        case 'ventilador_on':
            $estado["ventilador"] = [
                "estado" => "activo",
                "valor" => $input['valor'] ?? "N/A",
                "timestamp" => date('Y-m-d H:i:s')
            ];
            break;
        
        case 'ventilador_off':
            $estado["ventilador"] = [
                "estado" => "apagado",
                "valor" => "N/A",
                "timestamp" => date('Y-m-d H:i:s')
            ];
            break;
        
        case 'corriente_on':
            $estado["corriente"] = [
                "estado" => "sensando",
                "valor" => $input['valor'] ?? "N/A",
                "timestamp" => date('Y-m-d H:i:s')
            ];
            break;
        
        case 'corriente_off':
            $estado["corriente"] = [
                "estado" => "apagado",
                "valor" => "N/A",
                "timestamp" => date('Y-m-d H:i:s')
            ];
            break;
        
        case 'all_off':
            $estado = [
                "temperatura" => ["estado" => "apagado", "valor" => "N/A", "timestamp" => date('Y-m-d H:i:s')],
                "ventilador" => ["estado" => "apagado", "valor" => "N/A", "timestamp" => date('Y-m-d H:i:s')],
                "corriente" => ["estado" => "apagado", "valor" => "N/A", "timestamp" => date('Y-m-d H:i:s')]
            ];
            break;
        
        default:
            echo json_encode(["status" => "error", "msg" => "Acción inválida"]);
            exit;
    }
    
    // Guardar estado
    file_put_contents($sensor_file, json_encode($estado, JSON_PRETTY_PRINT));
    
    echo json_encode([
        "status" => "ok",
        "msg" => "Estado actualizado",
        "data" => $estado
    ]);
    exit;
}

// Método no permitido
http_response_code(405);
echo json_encode(["status" => "error", "msg" => "Método no permitido"]);
?>
