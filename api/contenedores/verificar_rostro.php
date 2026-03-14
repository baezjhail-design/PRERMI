<?php
// verificar_rostro.php
require_once __DIR__ . '/../../config/db_config.php';
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Recibir JSON o form-data
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

if (!isset($data['imagen_base64'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No image received"]);
    exit;
}

$imgBase64 = $data['imagen_base64'];

// Guardar imagen temporal
$tempDir = __DIR__ . '/../../uploads/temp/';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$tempPath = $tempDir . 'face_temp_' . time() . '.jpg';
file_put_contents($tempPath, base64_decode($imgBase64));

// Ejecutar Python
$pythonExe = "C:\\Users\\jhail\\AppData\\Local\\Programs\\Python\\Python314\\python.exe";
$pythonScript = "C:\\xampp\\htdocs\\PRERMI\\python\\face_verify.py";
$cmd = '"' . $pythonExe . '" "' . $pythonScript . '" "' . $tempPath . '" 2>&1';
$output = shell_exec($cmd);

// Guardar captura antes de eliminar temporal
$capturasDir = __DIR__ . '/../../uploads/capturas_cam/';
if (!is_dir($capturasDir)) mkdir($capturasDir, 0777, true);

$pyResult = json_decode($output, true);
$status = ($pyResult && isset($pyResult['success']) && $pyResult['success']) ? 'reconocido' : 'no_reconocido';
$userId = ($pyResult && isset($pyResult['user_id'])) ? intval($pyResult['user_id']) : 0;
$capturaName = date('Y-m-d_H-i-s') . "_{$status}_uid{$userId}.jpg";
copy($tempPath, $capturasDir . $capturaName);

// Eliminar temporal
if (file_exists($tempPath)) unlink($tempPath);

if (!$output) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Recognition failed", "debug_cmd" => $cmd]);
    exit;
}

$result = json_decode($output, true);
if (!$result) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Invalid Python response", "raw_output" => substr($output, 0, 500)]);
    exit;
}

// Si éxito, validar que el rostro esté registrado en la tabla rostros
if ($result["success"]) {
    $user_id = intval($result["user_id"]);
    $expectedFilename = "face_" . $user_id . ".jpg";
    $stmt = $conn->prepare(
        "SELECT user_id FROM rostros
         WHERE user_id = ? AND filename = ?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("is", $user_id, $expectedFilename);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode([
            "success" => true,
            "user_id" => $user_id,
            "probability" => $result["probability"]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found in face registry"]);
    }
} else {
    echo json_encode(["success" => false]);
}