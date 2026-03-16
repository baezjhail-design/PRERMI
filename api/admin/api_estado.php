<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "prermi_user";
$password = "Prermi2026!";
$dbname = "prer_mi";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "BD desconectada"]);
    exit;
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
]);