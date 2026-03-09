<?php
// subir_foto_deposito.php
require_once __DIR__ . '/../../config/db_config.php';
header("Content-Type: application/json");

// Leer JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['deposito_id']) || !isset($data['imagen_base64'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Parámetros incompletos"]);
    exit;
}

$deposito_id = intval($data['deposito_id']);
$imgBase64 = $data['imagen_base64'];

// Directorio de almacenamiento
$uploadDir = __DIR__ . '/../../uploads/depositos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generar nombre único de imagen
$filename = "dep_" . $deposito_id . "_" . time() . ".jpg";
$filepath = $uploadDir . $filename;

// Guardar imagen en disco
$imageData = base64_decode($imgBase64);
if (!file_put_contents($filepath, $imageData)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error guardando imagen"]);
    exit;
}

// Podrías guardar ruta en una tabla adicional si lo deseas

echo json_encode([
    "success" => true,
    "ruta" => "uploads/depositos/" . $filename
]);