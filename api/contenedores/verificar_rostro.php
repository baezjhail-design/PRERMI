<?php
// verificar_rostro.php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../security.php';
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-MCU-KEY, X-MCU-ID, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
requireMCUAccess();

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
$pythonExe = "/usr/bin/python3";
$pythonScript = "/var/www/html/PRERMI/python/face_verify.py";
$cmd = $pythonExe . ' "' . $pythonScript . '" "' . $tempPath . '" 2>&1';
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

$confidence = isset($result["confidence"]) ? floatval($result["confidence"]) : 999.0;
$probability = isset($result["probability"]) ? floatval($result["probability"]) : 0.0;
$minProbabilityServer = 0.45;
$maxConfidenceServer = 118.0;

if (!empty($result["success"])) {
    // Doble validacion en servidor: evita aprobar matches debiles por cambios de modelo.
    if ($probability < $minProbabilityServer || $confidence > $maxConfidenceServer) {
        echo json_encode([
            "success" => false,
            "message" => "Low confidence match rejected",
            "confidence" => $confidence,
            "probability" => $probability
        ]);
        exit;
    }
}

// Si éxito, validar que el rostro esté registrado en la tabla rostros
if ($result["success"]) {
    $user_id = intval($result["user_id"]);
    $expectedFilename = "face_" . $user_id . ".jpg";
    $faceFilePath = __DIR__ . '/../../uploads/rostros/' . $expectedFilename;

    if (!file_exists($faceFilePath)) {
        echo json_encode([
            "success" => false,
            "message" => "Face file not found for predicted user",
            "user_id" => $user_id
        ]);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT r.user_id
         FROM rostros r
         INNER JOIN usuarios u ON u.id = r.user_id
         WHERE r.user_id = ? AND r.filename = ?
         ORDER BY r.id DESC LIMIT 1"
    );
    $stmt->bind_param("is", $user_id, $expectedFilename);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode([
            "success" => true,
            "user_id" => $user_id,
            "probability" => $probability,
            "confidence" => $confidence
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Predicted user is not valid in usuarios/rostros",
            "user_id" => $user_id
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => isset($result["message"]) ? $result["message"] : "Face not recognized",
        "confidence" => $confidence,
        "probability" => $probability
    ]);
}