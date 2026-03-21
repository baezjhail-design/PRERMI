<?php
/**
 * test_conexion_esp32.php — Endpoint de diagnóstico para ESP32-S3 CAM
 * 
 * Recibe peticiones de prueba del ESP32 y verifica:
 *   - Conectividad HTTP/HTTPS
 *   - Recepción correcta de JSON
 *   - Recepción de imágenes base64
 *   - Conexión a base de datos (local e InfinityFree)
 *   - Validación de estructura de datos de depósitos
 * 
 * Uso: POST con JSON { "test_type": "ping|json|imagen|db|deposito_dry" }
 *      GET  sin parámetros → devuelve estado básico
 */

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../utils.php';

requireLocalAccess(true);

// ===== GET: estado básico =====
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        "success" => true,
        "msg" => "PRERMI Test Endpoint OK",
        "server_time" => date('Y-m-d H:i:s'),
        "php_version" => PHP_VERSION,
        "method" => "GET"
    ]);
    exit;
}

// ===== POST: pruebas específicas =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "msg" => "Método no permitido"]);
    exit;
}

// Leer datos: soporta JSON directo y multipart form-data (campo "data")
$rawInput = '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'multipart/form-data') !== false) {
    $rawInput = $_POST['data'] ?? '';
} else {
    $rawInput = file_get_contents('php://input');
}

$data = json_decode($rawInput, true);
if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "msg" => "JSON inválido o vacío",
        "raw_length" => strlen($rawInput),
        "content_type" => $contentType
    ]);
    exit;
}

$testType = $data['test_type'] ?? 'ping';

$result = [
    "success" => true,
    "test_type" => $testType,
    "server_time" => date('Y-m-d H:i:s'),
    "received_keys" => array_keys($data)
];

switch ($testType) {

    // ===== TEST 1: Ping — verificar conectividad básica =====
    case 'ping':
        $result["msg"] = "Conexión exitosa";
        $result["echo"] = $data['echo'] ?? 'sin_echo';
        break;

    // ===== TEST 2: JSON — verificar recepción de datos estructurados =====
    case 'json':
        $requiredFields = ['id_usuario', 'id_contenedor', 'peso'];
        $missing = [];
        foreach ($requiredFields as $f) {
            if (!isset($data[$f])) {
                $missing[] = $f;
            }
        }
        if (!empty($missing)) {
            $result["success"] = false;
            $result["msg"] = "Campos faltantes";
            $result["missing_fields"] = $missing;
        } else {
            $result["msg"] = "Estructura JSON válida";
            $result["validated"] = [
                "id_usuario" => (int)$data['id_usuario'],
                "id_contenedor" => (int)$data['id_contenedor'],
                "peso" => (float)$data['peso'],
                "tipo_residuo" => $data['tipo_residuo'] ?? 'no_especificado',
                "credito_kwh" => (float)($data['credito_kwh'] ?? 0),
                "metal_detectado" => (int)($data['metal_detectado'] ?? 0)
            ];
        }
        break;

    // ===== TEST 3: Imagen — verificar recepción de base64 =====
    case 'imagen':
        $b64 = $data['imagen_base64'] ?? '';
        $b64Len = strlen($b64);

        if ($b64Len === 0) {
            $result["success"] = false;
            $result["msg"] = "Campo imagen_base64 vacío o ausente";
        } else {
            $decoded = base64_decode($b64, true);
            if ($decoded === false) {
                $result["success"] = false;
                $result["msg"] = "Base64 inválido";
                $result["base64_length"] = $b64Len;
            } else {
                $imageSize = strlen($decoded);
                // Verificar que parece un JPEG (empieza con FF D8)
                $isJpeg = ($imageSize >= 2 && ord($decoded[0]) === 0xFF && ord($decoded[1]) === 0xD8);
                $result["msg"] = "Imagen recibida correctamente";
                $result["base64_length"] = $b64Len;
                $result["image_bytes"] = $imageSize;
                $result["is_jpeg"] = $isJpeg;
                $result["image_ok"] = ($imageSize > 100 && $isJpeg);
            }
        }
        break;

    // ===== TEST 4: DB — verificar conexión a base de datos =====
    case 'db':
        $dbResults = [];

        // Test DB local
        try {
            $pdo = getPDO();
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $dbResults["local"] = [
                "connected" => true,
                "total_usuarios" => (int)$row['total']
            ];
        } catch (Exception $e) {
            $dbResults["local"] = [
                "connected" => false,
                "error" => $e->getMessage()
            ];
        }

        // Test DB remota (InfinityFree)
        try {
            $pdoRemote = getPDORemote();
            if ($pdoRemote) {
                $stmt = $pdoRemote->query("SELECT COUNT(*) as total FROM usuarios");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $dbResults["infinityfree"] = [
                    "connected" => true,
                    "total_usuarios" => (int)$row['total']
                ];
            } else {
                $dbResults["infinityfree"] = [
                    "connected" => false,
                    "error" => "getPDORemote() retornó null"
                ];
            }
        } catch (Exception $e) {
            $dbResults["infinityfree"] = [
                "connected" => false,
                "error" => $e->getMessage()
            ];
        }

        $result["msg"] = "Test de base de datos completado";
        $result["databases"] = $dbResults;
        break;

    // ===== TEST 5: Depósito dry-run — simula registro sin insertar =====
    case 'deposito_dry':
        $required = ['id_usuario', 'id_contenedor', 'peso', 'token_usado'];
        $missing = [];
        foreach ($required as $f) {
            if (!isset($data[$f])) {
                $missing[] = $f;
            }
        }

        if (!empty($missing)) {
            $result["success"] = false;
            $result["msg"] = "Campos requeridos faltantes para depósito";
            $result["missing_fields"] = $missing;
            break;
        }

        // Validar tipos de datos
        $validation = [];
        $validation["id_usuario"] = is_numeric($data['id_usuario']) ? "OK" : "DEBE SER NUMÉRICO";
        $validation["id_contenedor"] = is_numeric($data['id_contenedor']) ? "OK" : "DEBE SER NUMÉRICO";
        $validation["peso"] = is_numeric($data['peso']) ? "OK" : "DEBE SER NUMÉRICO";
        $validation["token_usado"] = is_string($data['token_usado']) && strlen($data['token_usado']) > 0 ? "OK" : "DEBE SER STRING NO VACÍO";
        $validation["tipo_residuo"] = isset($data['tipo_residuo']) ? "OK (" . $data['tipo_residuo'] . ")" : "NO ENVIADO (usará default)";
        $validation["credito_kwh"] = isset($data['credito_kwh']) && is_numeric($data['credito_kwh']) ? "OK" : "NO ENVIADO O INVÁLIDO";

        $allValid = !in_array(false, array_map(function($v) { return strpos($v, 'OK') === 0; }, $validation));

        // Verificar que el usuario existe en DB
        $userExists = false;
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM usuarios WHERE id = ?");
            $stmt->execute([(int)$data['id_usuario']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userExists = ($user !== false);
            if ($userExists) {
                $result["user_found"] = $user['nombre'] . ' ' . $user['apellido'];
            }
        } catch (Exception $e) {
            $result["db_error"] = $e->getMessage();
        }

        $result["msg"] = $allValid ? "Datos de depósito válidos (dry-run, NO insertado)" : "Datos con errores de validación";
        $result["validation"] = $validation;
        $result["user_exists"] = $userExists;
        $result["dry_run"] = true;
        break;

    // ===== TEST 6: Full — ejecuta todos los tests =====
    case 'full':
        $results = [];

        // Ping
        $results["ping"] = ["success" => true, "msg" => "OK"];

        // JSON structure
        $jsonOk = isset($data['id_usuario']) && isset($data['peso']);
        $results["json_structure"] = [
            "success" => $jsonOk,
            "msg" => $jsonOk ? "Estructura correcta" : "Falta id_usuario o peso"
        ];

        // Imagen
        $hasImage = isset($data['imagen_base64']) && strlen($data['imagen_base64']) > 0;
        if ($hasImage) {
            $decoded = base64_decode($data['imagen_base64'], true);
            $imgOk = ($decoded !== false && strlen($decoded) > 100);
            $results["imagen"] = [
                "success" => $imgOk,
                "msg" => $imgOk ? "Imagen válida (" . strlen($decoded) . " bytes)" : "Imagen inválida",
                "base64_length" => strlen($data['imagen_base64'])
            ];
        } else {
            $results["imagen"] = [
                "success" => false,
                "msg" => "No se envió imagen (campo imagen_base64)"
            ];
        }

        // DB
        try {
            $pdo = getPDO();
            $stmt = $pdo->query("SELECT 1");
            $results["db_local"] = ["success" => true, "msg" => "Conectado"];
        } catch (Exception $e) {
            $results["db_local"] = ["success" => false, "msg" => $e->getMessage()];
        }

        try {
            $pdoRemote = getPDORemote();
            if ($pdoRemote) {
                $stmt = $pdoRemote->query("SELECT 1");
                $results["db_remote"] = ["success" => true, "msg" => "Conectado"];
            } else {
                $results["db_remote"] = ["success" => false, "msg" => "No disponible"];
            }
        } catch (Exception $e) {
            $results["db_remote"] = ["success" => false, "msg" => $e->getMessage()];
        }

        $passed = 0;
        $total = count($results);
        foreach ($results as $r) {
            if ($r['success']) $passed++;
        }

        $result["msg"] = "Test completo: $passed/$total pasaron";
        $result["tests"] = $results;
        $result["passed"] = $passed;
        $result["total"] = $total;
        break;

    default:
        $result["success"] = false;
        $result["msg"] = "test_type no reconocido: " . htmlspecialchars($testType, ENT_QUOTES, 'UTF-8');
        break;
}

echo json_encode($result);
exit;
