<?php
/**
 * db_config.php — Configuración centralizada de la base de datos
 * Soporta conexión LOCAL (XAMPP) e INFINITYFREE simultáneamente.
 * Auto-detecta el entorno según el servidor donde se ejecuta.
 */

// ===== INFINITYFREE (Producción) =====
$DB_HOST_REMOTE = "sql208.infinityfree.com";
$DB_USER_REMOTE = "if0_41000503";
$DB_PASS_REMOTE = "PRERMI12345";
$DB_NAME_REMOTE = "if0_41000503_prer_mi";

// ===== Detectar entorno =====
// Si estamos corriendo en InfinityFree, usar las credenciales remotas como principales
$isInfinityFree = (
    isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], '.infinityfreeapp.com') !== false
) || (
    isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.infinityfreeapp.com') !== false
) || (
    isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['DOCUMENT_ROOT'], '/htdocs') !== false
    && !file_exists('C:\\xampp') && !file_exists('D:\\xampp')
);

if ($isInfinityFree) {
    // En InfinityFree: usar las credenciales remotas como DB principal
    $DB_HOST = $DB_HOST_REMOTE;
    $DB_USER = $DB_USER_REMOTE;
    $DB_PASS = $DB_PASS_REMOTE;
    $DB_NAME = $DB_NAME_REMOTE;
} else {
    // En LOCAL (XAMPP)
    $DB_HOST = "localhost";
    $DB_USER = "root";
    $DB_PASS = "";
    $DB_NAME = "prer_mi";
}