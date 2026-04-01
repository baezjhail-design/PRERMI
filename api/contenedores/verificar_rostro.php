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

/**
 * Extrae el ultimo objeto JSON valido de una salida que puede incluir warnings/logs.
 */
function extractLastJsonFromOutput($output) {
    $text = trim((string) $output);
    if ($text === '') {
        return null;
    }

    // Caso ideal: todo el output ya es JSON.
    $direct = json_decode($text, true);
    if (is_array($direct)) {
        return $direct;
    }

    // Buscar desde la ultima linea hacia arriba un bloque JSON valido.
    $lines = preg_split('/\r\n|\r|\n/', $text);
    if (!is_array($lines)) {
        return null;
    }
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $line = trim((string) $lines[$i]);
        if ($line === '') {
            continue;
        }
        if ($line[0] !== '{') {
            continue;
        }
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

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

// Ejecutar Python (preferir python del sistema Ubuntu 22.04)
$pythonExe = "/usr/bin/python3.10";
if (!is_executable($pythonExe)) {
    $pythonExe = "/usr/bin/python3";
}
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
    // Respuesta controlada para que el MCU no lo trate como caida total del servidor.
    echo json_encode([
        "success" => false,
        "message" => "Recognition failed",
        "debug_cmd" => $cmd
    ]);
    exit;
}

$result = extractLastJsonFromOutput($output);
if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid Python response",
        "raw_output" => substr((string)$output, 0, 800)
    ]);
    exit;
}

$confidence = isset($result["confidence"]) ? floatval($result["confidence"]) : 999.0;
$probability = isset($result["probability"]) ? floatval($result["probability"]) : 0.0;
$votes = isset($result["votes"]) ? intval($result["votes"]) : 1;
$hour = (int) date('G');
$isDay = ($hour >= 7 && $hour < 19);
$nightMinProbability = 0.34;
$nightMaxConfidence = 145.0;

// Dia mas estricto para reducir falsos positivos.
$minProbabilityServer = $isDay ? round($nightMinProbability * 1.05, 4) : $nightMinProbability;
$maxConfidenceServer = $isDay ? round($nightMaxConfidence * 0.94, 2) : $nightMaxConfidence;

if (!empty($result["success"])) {
    if ($probability < $minProbabilityServer || $confidence > $maxConfidenceServer) {
        echo json_encode([
            "success" => false,
            "message" => "Low confidence match rejected",
            "confidence" => $confidence,
            "probability" => $probability,
            "votes" => $votes,
            "is_day" => $isDay,
            "max_confidence" => $maxConfidenceServer,
            "min_probability" => $minProbabilityServer
        ]);
        exit;
    }
}

// Si éxito, validar que el rostro esté registrado en la tabla rostros
if ($result["success"]) {
    $user_id = intval($result["user_id"]);
    $faceDir = __DIR__ . '/../../uploads/rostros/' . $user_id;
    $legacyFacePath = __DIR__ . '/../../uploads/rostros/face_' . $user_id . '.jpg';

    $hasPhysicalFaces = false;
    if (is_dir($faceDir)) {
        $userFiles = glob($faceDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        $hasPhysicalFaces = is_array($userFiles) && count($userFiles) > 0;
    }
    if (!$hasPhysicalFaces && file_exists($legacyFacePath)) {
        $hasPhysicalFaces = true;
    }

    if (!$hasPhysicalFaces) {
        echo json_encode([
            "success" => false,
            "message" => "No face files found for predicted user",
            "user_id" => $user_id
        ]);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT r.user_id
         FROM rostros r
         INNER JOIN usuarios u ON u.id = r.user_id
         WHERE r.user_id = ?
         ORDER BY r.id DESC LIMIT 1"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM rostros WHERE user_id = ?");
        $countStmt->bind_param("i", $user_id);
        $countStmt->execute();
        $countStmt->bind_result($registeredCount);
        $countStmt->fetch();
        $countStmt->close();

        $registeredCount = intval($registeredCount);
        if ($registeredCount < 15) {
            echo json_encode([
                "success" => false,
                "message" => "User has insufficient registered face set",
                "user_id" => $user_id,
                "registered_faces" => $registeredCount,
                "required_faces" => 15
            ]);
            exit;
        }

        echo json_encode([
            "success" => true,
            "user_id" => $user_id,
            "probability" => $probability,
            "confidence" => $confidence,
            "is_day" => $isDay,
            "registered_faces" => $registeredCount
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
        "probability" => $probability,
        "is_day" => $isDay
    ]);
}