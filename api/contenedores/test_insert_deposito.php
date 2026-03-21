<?php
/**
 * test_insert_deposito.php
 * Script para verificar que el ID=1 se acepta en la tabla depositos
 */

require_once __DIR__ . '/../utils.php';
requireLocalAccess(false);

require_once __DIR__ . '/../../config/db_config.php';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

echo "=== TEST DE INSERCIÓN CON ID_CONTENEDOR=1 ===\n\n";

// Test: Intentar insertar un depósito con id_contenedor=1
$usuario_id = 4;
$id_contenedor = 1;
$token = "test_token_santiago";
$peso = 5.5;
$metal = 0;

$stmt = $conn->prepare("
    INSERT INTO depositos 
    (id_usuario, id_contenedor, token_usado, peso, metal_detectado)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("iisdi", $usuario_id, $id_contenedor, $token, $peso, $metal);

if ($stmt->execute()) {
    echo "✅ ÉXITO: Depósito insertado correctamente\n";
    echo "   ID Inserción: " . $stmt->insert_id . "\n";
    echo "   Usuario: $usuario_id\n";
    echo "   Contenedor: $id_contenedor (Santiago)\n";
    echo "   Peso: $peso kg\n";
} else {
    echo "❌ ERROR al insertar: " . $stmt->error . "\n";
    echo "   Error de conexión: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();

echo "\n✅ Test completado.\n";
?>
