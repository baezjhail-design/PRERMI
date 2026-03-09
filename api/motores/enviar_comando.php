<?php
session_start();
header('Content-Type: application/json');

// 🔐 Validación básica de sesión (opcional)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "msg" => "No autorizado"]);
    exit;
}

if (!isset($_POST['comando'])) {
    echo json_encode(["status" => "error", "msg" => "Comando no recibido"]);
    exit;
}

$comando = $_POST['comando'];

// ⚠ IP del ESP
$esp_ip = "192.168.1.101"; // Cambiar por la IP real

$url = "http://$esp_ip/comando?btn=" . urlencode($comando);

// Enviar solicitud al ESP
$respuesta = @file_get_contents($url);

if ($respuesta === FALSE) {
    echo json_encode(["status" => "error", "msg" => "No se pudo conectar al ESP"]);
} else {
    echo json_encode(["status" => "ok", "msg" => "Comando enviado", "comando" => $comando]);
}