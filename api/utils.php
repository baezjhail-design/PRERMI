<?php
/**
 * utils.php — Funciones comunes del sistema PRERMI (Módulo 1 + Módulo 2)
 * Actualizado oficialmente según DB y requerimientos del proyecto.
 */

// Cargar configuración PRIMERO
require_once __DIR__ . '/../config/db_config.php';

// Nota: no enviar headers aquí — utils.php se incluye desde páginas web y APIs.
// Los endpoints JSON deben establecer su propio header("Content-Type: application/json")
// antes de emitir la respuesta. Evitamos enviar headers globalmente para no
// provocar "headers already sent" en páginas HTML que incluyen este archivo.

// =====================================
// RESPUESTAS JSON
// =====================================
function jsonOk($data = []) {
    echo json_encode(array_merge(["success" => true], $data));
    exit;
}

function jsonErr($msg = "Error desconocido", $code = 400) {
    http_response_code($code);
    echo json_encode(["success" => false, "msg" => $msg]);
    exit;
}


// =====================================
// LIMPIAR STRINGS
// =====================================
function limpiar($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}


// =====================================
// CONEXIÓN A BASE DE DATOS (PDO)
// =====================================
function getPDO() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    
    // Las variables ya están cargadas por el require_once en el inicio de utils.php
    if (!isset($DB_HOST) || !isset($DB_NAME) || !isset($DB_USER) || !isset($DB_PASS)) {
        jsonErr("Variables de configuración DB no definidas", 500);
    }

    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

    try {
        return new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    catch (PDOException $e) {
        jsonErr("Error de conexión a la base de datos: " . $e->getMessage(), 500);
    }
}

// =====================================
// CONEXIÓN A BASE DE DATOS REMOTA - INFINITYFREE (PDO)
// =====================================
function getPDORemote() {
    global $DB_HOST_REMOTE, $DB_NAME_REMOTE, $DB_USER_REMOTE, $DB_PASS_REMOTE, $isInfinityFree;

    // sql208.infinityfree.com solo es accesible desde la red de InfinityFree.
    // Desde localhost, el ESP32 envía datos a ambos servidores directamente.
    if (!$isInfinityFree) {
        return null;
    }

    if (!isset($DB_HOST_REMOTE) || !isset($DB_NAME_REMOTE) || !isset($DB_USER_REMOTE) || !isset($DB_PASS_REMOTE)) {
        return null; // Silencioso: si no hay config remota, no falla
    }

    $dsn = "mysql:host=$DB_HOST_REMOTE;dbname=$DB_NAME_REMOTE;charset=utf8mb4";

    try {
        return new PDO($dsn, $DB_USER_REMOTE, $DB_PASS_REMOTE, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    catch (PDOException $e) {
        error_log("[REMOTE DB] Error de conexión: " . $e->getMessage());
        return null;
    }
}

// =====================================
// OBTENER AMBAS CONEXIONES (local + remota)
// =====================================
function getAllPDO() {
    $connections = [];
    
    $local = getPDO();
    if ($local) {
        $connections['local'] = $local;
    }
    
    $remote = getPDORemote();
    if ($remote) {
        $connections['remote'] = $remote;
    }
    
    return $connections;
}



// =====================================
// REGISTRO DE LOGS
// =====================================
function registrarLog($descripcion, $tipo = "info") {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            INSERT INTO logs_sistema (descripcion, tipo)
            VALUES (?, ?)
        ");
        $stmt->execute([$descripcion, $tipo]);
    } catch (Exception $e) {
        // Si falla el log, no rompe la ejecución
    }
}


// =====================================
// SESIÓN PARA ADMINISTRADORES
// =====================================
function requireAdminSession() {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        jsonErr("No autorizado", 401);
    }
}


// =====================================
// UUID v4 — Token Único
// =====================================
function uuidv4() {
    $data = random_bytes(16);

    // versión y variante
    $data[6] = chr((ord($data[6]) & 0x0F) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3F) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}


// =====================================
// TOKEN ÚNICO PARA USUARIOS
// (PRERMI usa UUIDv4)
// =====================================
function generarToken() {
    return uuidv4();
}


// =====================================
// ENVÍO DE CORREOS (PHPMailer)
// =====================================
function enviarCorreo($destino, $asunto, $mensajeHTML) {

    require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'baezjhail@gmail.com';   // EDITAR
        $mail->Password   = 'gzghfibxuryaebuj';         // EDITAR
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Emisor
        $mail->setFrom('baezjhail@gmail.com', 'PRERMI');

        // Destino
        $mail->addAddress($destino);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensajeHTML;

        $mail->send();
        return true;

    } catch (Exception $e) {
        registrarLog("Error enviando correo a $destino: ".$mail->ErrorInfo, "error");
        return false;
    }
}
