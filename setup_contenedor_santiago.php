<?php
/**
 * setup_contenedor_santiago.php
 * Script para crear/verificar el contenedor fijo de Santiago de los Caballeros
 */

require_once __DIR__ . '/config/db_config.php';

// Crear conexión
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "=== VERIFICAR/CREAR CONTENEDOR SANTIAGO DE LOS CABALLEROS ===\n\n";

// Verificar si el contenedor ya existe
$check = $conn->prepare("SELECT id, codigo_contenedor, ubicacion FROM contenedores_registrados WHERE ubicacion LIKE ?");
$ubicacion_search = "%Santiago%";
$check->bind_param("s", $ubicacion_search);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "✅ Contenedores encontrados en Santiago:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: " . $row['id'] . ", Código: " . $row['codigo_contenedor'] . ", Ubicación: " . $row['ubicacion'] . "\n";
    }
} else {
    echo "❌ No hay contenedores en Santiago. Creando uno...\n";
    
    // Insert el contenedor fijo
    $codigo = "CONT-SANTIAGO-001";
    $ubicacion = "Santiago de los Caballeros";
    $tipo = "general";
    $estado = "activo";
    
    $insert = $conn->prepare("
        INSERT INTO contenedores_registrados 
        (codigo_contenedor, ubicacion, tipo_contenedor, estado)
        VALUES (?, ?, ?, ?)
    ");
    
    $insert->bind_param("ssss", $codigo, $ubicacion, $tipo, $estado);
    
    if ($insert->execute()) {
        $new_id = $insert->insert_id;
        echo "✅ Contenedor creado exitosamente con ID: $new_id\n";
        echo "   Código: $codigo\n";
        echo "   Ubicación: $ubicacion\n";
        echo "   Tipo: $tipo\n";
        echo "   Estado: $estado\n";
    } else {
        echo "❌ Error al crear contenedor: " . $insert->error . "\n";
    }
    
    $insert->close();
}

// Obtener el ID del contenedor de Santiago
$find = $conn->prepare("SELECT id FROM contenedores_registrados WHERE ubicacion LIKE ? LIMIT 1");
$find->bind_param("s", $ubicacion_search);
$find->execute();
$result_find = $find->get_result();
$row_find = $result_find->fetch_assoc();

if ($row_find) {
    $santiago_id = $row_find['id'];
    echo "\n👉 ID FIJO PARA SANTIAGO: $santiago_id\n";
    echo "\n   USE ESTE ID EN EL CÓDIGO ESP32:\n";
    echo "   const int CONTAINER_ID_SANTIAGO = $santiago_id;\n";
    echo "   Y EN EL REGISTRO: doc[\"id_contenedor\"] = $santiago_id;\n";
} else {
    echo "\n⚠️  No se pudo obtener el ID del contenedor de Santiago\n";
}

$check->close();
$find->close();
$conn->close();

echo "\n✅ Script completado.\n";
?>
