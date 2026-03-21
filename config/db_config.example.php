<?php
/**
 * db_config.example.php — Plantilla de configuración de base de datos
 *
 * INSTRUCCIONES PARA COLABORADORES:
 *   1. Copia este archivo: cp config/db_config.example.php config/db_config.php
 *   2. Rellena los valores reales de tu entorno (NO los subas al repo).
 *   3. El archivo config/db_config.php está en .gitignore por seguridad.
 */

$DB_HOST = "localhost";          // Servidor MySQL
$DB_USER = "tu_usuario_db";     // Usuario de la base de datos
$DB_PASS = "tu_contraseña_db";  // Contraseña — NUNCA commitear el valor real
$DB_NAME = "nombre_base_datos"; // Nombre de la base de datos

// Variables remotas (dejar null si no aplica)
$DB_HOST_REMOTE = null;
$DB_NAME_REMOTE = null;
$DB_USER_REMOTE = null;
$DB_PASS_REMOTE = null;
$isInfinityFree  = false;
