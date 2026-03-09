<?php
require_once __DIR__ . '/../../config/db_config.php';

header("Content-Type: application/json");

// Leer JSON recibido
$data = file_get_contents("php://input");
$json = json_decode($data, true);

if (!isset($json['imagen_base64'])) {
    echo json_encode(["success" => false, "error" => "No image received"]);
    exit;
}

$imgBase64 = $json['imagen_base64'];

// Crear carpeta temporal si no existe
$tempDir = __DIR__ . '/../../uploads/temp/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Guardar imagen temporal
$tempPath = $tempDir . 'temp_' . time() . '.jpg';

$imageData = base64_decode($imgBase64);
file_put_contents($tempPath, $imageData);

// Ejecutar Python
$pythonScript = "D:\\xampp\\htdocs\\PRERMI\\python\\face_verify.py";
$command = "python \"$pythonScript\" \"$tempPath\"";

$output = shell_exec($command);

// Eliminar imagen temporal
unlink($tempPath);

if (!$output) {
    echo json_encode(["success" => false, "error" => "Recognition failed"]);
    exit;
}

// Decodificar resultado Python
$result = json_decode($output, true);

if (!$result) {
    echo json_encode(["success" => false, "error" => "Invalid Python response"]);
    exit;
}

// Si reconocido, validar que usuario exista en BD
if ($result["success"]) {

    $user_id = intval($result["user_id"]);

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        echo json_encode([
            "success" => true,
            "user_id" => $user_id,
            "probability" => $result["probability"]
        ]);

    } else {
        echo json_encode(["success" => false, "error" => "User not found"]);
    }

} else {
    echo json_encode(["success" => false]);
}