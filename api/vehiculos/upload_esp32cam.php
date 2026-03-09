<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

// Este endpoint espera multipart/form-data con:
// imagen (file), placa, tipo, ubicacion, modelo_ml, probabilidad, latitud, longitud

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr("Method not allowed");

$placa     = $_POST['placa'] ?? 'DESCONOCIDA';
$tipo      = $_POST['tipo'] ?? 'civil';
$ubicacion = $_POST['ubicacion'] ?? 'Sin especificar';
$modelo_ml = $_POST['modelo_ml'] ?? 'TinyML';
$prob      = floatval($_POST['probabilidad'] ?? 0);
$lat       = isset($_POST['latitud']) && $_POST['latitud'] !== '' ? floatval($_POST['latitud']) : null;
$long      = isset($_POST['longitud']) && $_POST['longitud'] !== '' ? floatval($_POST['longitud']) : null;

if (!isset($_FILES['imagen'])) jsonErr("No se envió imagen");

// guardar
$uploadDir = __DIR__ . '/../../uploads/vehiculos/';
if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
$nombre = 'veh_' . preg_replace('/[^0-9A-Za-z_-]/','', $placa) . '_' . date("Ymd_His") . '_' . rand(100,999) . '.jpg';
$fullPath = $uploadDir . $nombre;
if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $fullPath)) jsonErr("No se pudo guardar imagen");

// fecha y hora las maneja el servidor (NOW())
$fecha = date("Y-m-d");
$hora  = date("H:i:s");

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO vehiculos_registrados (placa, tipo_vehiculo, imagen, ubicacion, fecha, hora, modelo_ml, probabilidad, latitud, longitud, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([$placa, $tipo, $nombre, $ubicacion, $fecha, $hora, $modelo_ml, $prob, $lat, $long]);
    
    jsonOk(['msg' => 'Guardado', 'file' => $nombre]);
} catch (PDOException $e) {
    jsonErr("Error en la base de datos", 500);
}
