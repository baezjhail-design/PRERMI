<?php
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json; charset=utf-8');

try {
    getPDO();
} catch (Exception $e) {
    jsonErr('BD desconectada', 503);
}

/*
   Aquí en producción:
   - El ESP32 enviará datos vía HTTP POST
   - O leerás desde una tabla estados_sensores
*/

// Simulación industrial temporal
$ir = rand(0,1);

if($ir == 1){
    $estado = "ALERTA";
    $led = "ROJO";
}else{
    $estado = "NORMAL";
    $led = "VERDE";
}

echo json_encode([
    "sensor_ir" => $ir,
    "estado_sistema" => $estado,
    "led_activo" => $led,
    "timestamp" => date("H:i:s")
], JSON_UNESCAPED_UNICODE);