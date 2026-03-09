<?php
// Usar configuración centralizada
require_once __DIR__ . '/../../config/db_config.php';

try {
    $conn = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8", $DB_USER, $DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Conexión fallida: " . $e->getMessage());
}

// Solo intentar conexión remota si estamos en InfinityFree
// (sql208.infinityfree.com no es accesible desde localhost)
$conn_remote = null;
if ($isInfinityFree) {
    try {
        $conn_remote = new PDO("mysql:host=$DB_HOST_REMOTE;dbname=$DB_NAME_REMOTE;charset=utf8", $DB_USER_REMOTE, $DB_PASS_REMOTE);
        $conn_remote->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log("[REMOTE DB QRV] Error: " . $e->getMessage());
    }
}
?>
