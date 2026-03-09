<?php
/**
 * gateway.php — Gateway universal para bypass de seguridad InfinityFree
 * 
 * InfinityFree bloquea:
 * - POST con Content-Type: application/json
 * - User-Agents no-browser (ESP32, Arduino, etc.)
 * - Requests sin cookie de sesion del challenge JS
 * 
 * Este gateway acepta requests como form-data normal (que IF permite)
 * y los rutea internamente a los endpoints reales del API.
 * 
 * USO:
 *   POST gateway.php
 *   Body (form-data o url-encoded):
 *     action = "login" | "register" | "deposito" | "sancion" | "verificar_rostro" | "obtener_usuario" | "sensor" | "validar_acceso"
 *     data   = JSON string con los datos del endpoint real
 *   
 *   GET gateway.php?action=obtener_usuario&id=5
 *   GET gateway.php?action=ping  (test de conectividad)
 */

// Forzar que IF lo trate como pagina web normal
if (!headers_sent()) {
    header("Content-Type: text/html; charset=UTF-8");
}

// Si es un bot-check de InfinityFree, dejar pasar el HTML challenge
// Solo activar la API si hay un parametro 'action'
$action = $_REQUEST['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    // Sin accion = pagina web normal. IF no bloquea esto.
    echo "<!DOCTYPE html><html><head><title>PRERMI</title></head><body>";
    echo "<h1>PRERMI Sistema</h1><p>Gateway activo.</p>";
    echo "</body></html>";
    exit;
}

// ===== A PARTIR DE AQUI: Modo API =====
// Cambiar Content-Type a JSON para las respuestas API
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===== Obtener datos del request =====
// Prioridad: campo 'data' (JSON string) > body JSON directo > campos individuales POST/GET
$inputData = [];

// 1. Intentar campo 'data' que contiene JSON como string
if (isset($_REQUEST['data'])) {
    $decoded = json_decode($_REQUEST['data'], true);
    if (is_array($decoded)) {
        $inputData = $decoded;
    }
}

// 2. Si no hay 'data', intentar body JSON crudo
if (empty($inputData)) {
    $rawBody = file_get_contents('php://input');
    if ($rawBody) {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $inputData = $decoded;
            // Si el JSON tiene 'action' pero no lo teniamos aun
            if (!$action && isset($decoded['action'])) {
                $action = $decoded['action'];
            }
        }
    }
}

// 3. Si aun esta vacio, usar todos los campos POST/GET
if (empty($inputData)) {
    $inputData = array_merge($_GET, $_POST);
    unset($inputData['action']); // No incluir 'action' como dato
}

// ===== Funciones helper =====
function gwOk($data = []) {
    echo json_encode(array_merge(["success" => true], $data));
    exit;
}

function gwErr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(["success" => false, "msg" => $msg]);
    exit;
}

// ===== Ping / Test =====
if ($action === 'ping' || $action === 'test') {
    gwOk([
        "message" => "PRERMI Gateway activo",
        "server" => "InfinityFree",
        "timestamp" => date('Y-m-d H:i:s'),
        "php_version" => phpversion()
    ]);
}

// ===== Cargar configuracion =====
require_once __DIR__ . '/config/db_config.php';

// ===== Funcion para simular php://input para los endpoints =====
// Los endpoints leen json_decode(file_get_contents('php://input'))
// Necesitamos inyectar los datos antes de incluirlos
function setupInputForEndpoint($data) {
    // Poblar $_POST para que los endpoints con fallback $_POST funcionen
    $_POST = $data;
    
    // Tambien crear un stream temporal para php://input
    $json = json_encode($data);
    
    // Guardar en variable global para que utils.php pueda acceder
    $GLOBALS['__gateway_input'] = $json;
}

// ===== Router de acciones =====
switch ($action) {

    // ----- LOGIN -----
    case 'login':
        setupInputForEndpoint($inputData);
        // Incluir directamente el endpoint (se ejecuta en el mismo proceso PHP)
        include __DIR__ . '/api/usuarios/login.php';
        break;

    // ----- REGISTRO -----    
    case 'register':
        setupInputForEndpoint($inputData);
        include __DIR__ . '/api/usuarios/register.php';
        break;

    // ----- REGISTRAR DEPOSITO -----
    case 'deposito':
        setupInputForEndpoint($inputData);
        include __DIR__ . '/api/contenedores/registrar_depositos.php';
        break;

    // ----- REGISTRAR PESO -----
    case 'peso':
        setupInputForEndpoint($inputData);
        include __DIR__ . '/api/contenedores/registrar_peso.php';
        break;

    // ----- SANCION AUTOMATICA -----
    case 'sancion':
        setupInputForEndpoint($inputData);
        include __DIR__ . '/api/sanciones/crear_sancion_auto.php';
        break;

    // ----- VERIFICAR ROSTRO -----
    case 'verificar_rostro':
        setupInputForEndpoint($inputData);
        include __DIR__ . '/api/contenedores/verificar_rostro.php';
        break;

    // ----- OBTENER USUARIO -----
    case 'obtener_usuario':
        // Este es GET
        $id = $inputData['id'] ?? $_GET['id'] ?? null;
        if ($id) $_GET['id'] = $id;
        include __DIR__ . '/api/usuarios/obtener_usuario.php';
        break;

    // ----- VALIDAR ACCESO (token contenedor) -----
    case 'validar_acceso':
        setupInputForEndpoint($inputData);
        include __DIR__ . '/api/contenedores/validar_acceso.php';
        break;

    // ----- REGISTRAR SENSOR -----
    case 'sensor':
        setupInputForEndpoint($inputData);
        include __DIR__ . '/api/sensores/registrar.php';
        break;

    // ----- OBTENER SENSORES -----
    case 'sensores':
        $uid = $inputData['user_id'] ?? $_GET['user_id'] ?? null;
        if ($uid) $_GET['user_id'] = $uid;
        $limit = $inputData['limit'] ?? $_GET['limit'] ?? 50;
        $_GET['limit'] = $limit;
        include __DIR__ . '/api/sensores/obtener.php';
        break;

    default:
        gwErr("Accion no reconocida: " . htmlspecialchars($action, ENT_QUOTES, 'UTF-8'), 400);
}
