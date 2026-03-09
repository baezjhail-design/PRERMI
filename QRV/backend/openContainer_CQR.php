<?php
require_once "../includes/db_connect.php";

$contenedor = $_SESSION['contenedor'];
$token      = $_SESSION['token'];

// Validar nuevamente
$stmt = $conn->prepare("
    SELECT * FROM contenedores_registrados
    WHERE id = ?
      AND ultimo_token = ?
      AND token_expira_en > NOW()");
$stmt->execute([$contenedor, $token]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    die("Token inválido o expirado.");
}

// Marcar token como usado
$stmt2 = $conn->prepare("
    UPDATE contenedores_registrados 
    SET ultimo_token = NULL, token_expira_en = NULL 
    WHERE id = ?");
$stmt2->execute([$contenedor]);

// Acción física de apertura (ESP32)
echo "<h3>Contenedor abierto ✔️</h3>";
?>
