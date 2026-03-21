<?php
// upload_photo.php
// Endpoint para recibir foto codificada en base64 desde el ESP32 y guardarla en la carpeta uploads/
// La foto se guarda con timestamp para identificarla fácilmente

require_once __DIR__ . '/utils.php'; // Cargar funciones comunes

header('Content-Type: application/json; charset=utf-8'); // Respuesta en JSON

$maxPhotoBytes = 5 * 1024 * 1024;

// Obtener datos raw del request
$raw = file_get_contents('php://input'); // Leer payload completo
if (!$raw) { // Si no hay datos
    jsonErr('No se recibió payload', 400); // Error: sin datos
}

// Decodificar JSON
$data = json_decode($raw, true); // Decodificar a array asociativo
if (!is_array($data)) { // Si no es un array válido
    jsonErr('JSON inválido', 400); // Error: JSON malformado
}

// Extraer foto en base64
$photoB64 = isset($data['photo_b64']) ? $data['photo_b64'] : null; // Obtener campo photo_b64
if (!$photoB64) { // Si no está presente
    jsonErr('Campo photo_b64 no encontrado', 400); // Error: falta campo
}

// Decodificar base64 a datos binarios
$photoBinary = base64_decode($photoB64, true); // Decodificar con validación estricta
if ($photoBinary === false) { // Si falla la decodificación
    jsonErr('Base64 inválido', 400); // Error: base64 corrupto
}

$binaryLength = strlen($photoBinary);
if ($binaryLength === 0 || $binaryLength > $maxPhotoBytes) {
    jsonErr('La imagen excede el tamano permitido', 413);
}

if (!isValidJpegBinary($photoBinary)) {
    jsonErr('Formato de imagen no permitido. Solo se aceptan JPEG validos', 415);
}

// Crear nombre del archivo con timestamp
$timestamp = date('YmdHis'); // Formato: 20260225142530
$randomSuffix = uniqid(); // ID único adicional
$filename = "photo_" . $timestamp . "_" . $randomSuffix . ".jpg"; // Nombre: photo_20260225142530_12345abc.jpg

// Ruta de guardado en la carpeta uploads
$uploadsDir = __DIR__ . '/../uploads'; // Ruta a uploads (subir un nivel desde api/)
if (!is_dir($uploadsDir)) { // Si la carpeta no existe
    mkdir($uploadsDir, 0755, true); // Crear con permisos
}

$filePath = $uploadsDir . '/' . $filename; // Ruta completa

// Guardar foto en disco
$bytesWritten = file_put_contents($filePath, $photoBinary); // Escribir datos binarios a archivo
if ($bytesWritten === false) { // Si falla la escritura
    jsonErr('Error guardando archivo en disco', 500); // Error de servidor
}

// Registrar en logs
registrarLog("Foto capturada y guardada: " . $filename . " (" . $bytesWritten . " bytes)", 'info');

// Responder con éxito
jsonOk([
    'message' => 'Foto guardada exitosamente',
    'filename' => $filename, // Nombre del archivo guardado
    'path' => '/uploads/' . $filename, // Ruta relativa
    'size_bytes' => $bytesWritten // Tamaño en bytes
]);
