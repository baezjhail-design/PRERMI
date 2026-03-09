<?php
// verify_face.php
// Este endpoint actua como proxy que recibe una imagen (base64 o multipart) desde el dispositivo
// y la reenvía al servicio Python de reconocimiento facial. Devuelve exactamente la respuesta JSON
// que entregue el servicio Python, o un error JSON si algo falló.

// Cargar utilidades comunes (getPDO, jsonOk, jsonErr, limpiar, registrarLog)
require_once __DIR__ . '/utils.php';

// Forzar respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Esperamos recibir POST con uno de los siguientes formatos:
// 1) JSON: { "image_b64": "...base64..." }
// 2) multipart/form-data con campo "image" (archivo)
// Además se puede recibir "python_url" opcional para sobreescribir la URL del servizio python.

$raw = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$python_url_default = 'http://10.0.0.162:5000/verify'; // URL por defecto del servicio Python

$image_b64 = null;
$python_url = $python_url_default;

// Si el contenido es JSON, intentar decodificar
if (stripos($contentType, 'application/json') !== false && $raw) {
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        jsonErr('JSON inválido', 400);
    }
    if (!empty($data['image_b64'])) {
        $image_b64 = $data['image_b64'];
    }
    if (!empty($data['python_url'])) {
        $python_url = limpiar($data['python_url']);
    }
}

// Si no tenemos en JSON, revisar multipart/form-data (archivo enviado)
if (!$image_b64 && !empty($_FILES['image']['tmp_name'])) {
    $tmp = $_FILES['image']['tmp_name'];
    $contents = file_get_contents($tmp);
    $image_b64 = base64_encode($contents);
}

if (!$image_b64) {
    jsonErr('No se recibió imagen (campo image o image_b64)', 400);
}

// Preparar petición HTTP al servicio Python
$payload = json_encode(['image_b64' => $image_b64]);

$ch = curl_init($python_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

if ($response === false) {
    registrarLog('Error proxy a servicio Python: ' . $curl_err, 'error');
    jsonErr('Error al comunicarse con el servicio de reconocimiento facial', 502);
}

// Reenviamos la respuesta tal cual (suponemos que es JSON)
http_response_code($http_code ?: 200);
echo $response;
exit;
