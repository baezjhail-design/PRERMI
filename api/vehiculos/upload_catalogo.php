<?php
/**
 * upload_catalogo.php — Endpoint para cargar imágenes de referencia de vehículos
 * Tipos soportados: accidente, vehiculo_empresa, camion_recolector
 * Almacenamiento: uploads/vehiculos_registrados/{tipo}/
 */

require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json; charset=utf-8');

requireAdminSession();

function ensureVehiculosCatalogoTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehiculos_catalogo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo_vehiculo VARCHAR(40) NOT NULL,
        etiqueta VARCHAR(120) NOT NULL,
        descripcion VARCHAR(255) NULL,
        ruta_archivo VARCHAR(255) NOT NULL,
        photo_base64 LONGTEXT NULL,
        estado VARCHAR(20) NOT NULL DEFAULT 'activo',
        created_at DATETIME NOT NULL,
        INDEX idx_tipo_estado (tipo_vehiculo, estado),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $extraColumns = [
        'marca' => "ALTER TABLE vehiculos_catalogo ADD COLUMN marca VARCHAR(80) NULL AFTER descripcion",
        'modelo' => "ALTER TABLE vehiculos_catalogo ADD COLUMN modelo VARCHAR(80) NULL AFTER marca",
        'anio' => "ALTER TABLE vehiculos_catalogo ADD COLUMN anio VARCHAR(10) NULL AFTER modelo",
        'color' => "ALTER TABLE vehiculos_catalogo ADD COLUMN color VARCHAR(40) NULL AFTER anio",
        'placa_referencia' => "ALTER TABLE vehiculos_catalogo ADD COLUMN placa_referencia VARCHAR(32) NULL AFTER color",
        'bbox_json' => "ALTER TABLE vehiculos_catalogo ADD COLUMN bbox_json TEXT NULL AFTER placa_referencia",
    ];

    foreach ($extraColumns as $col => $ddl) {
        $colQuoted = $pdo->quote($col);
        $check = $pdo->query("SHOW COLUMNS FROM vehiculos_catalogo LIKE {$colQuoted}");
        if (!$check || !$check->fetch(PDO::FETCH_ASSOC)) {
            try {
                $pdo->exec($ddl);
            } catch (Exception $e) {
                registrarLog('No se pudo agregar columna ' . $col . ': ' . $e->getMessage(), 'warning');
            }
        }
    }
}

function getVehiculosCatalogoColumns(PDO $pdo): array {
    $cols = [];
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM vehiculos_catalogo");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = isset($row['Field']) ? (string)$row['Field'] : '';
            if ($name !== '') {
                $cols[$name] = true;
            }
        }
    } catch (Exception $e) {
        registrarLog('No se pudo leer columnas de vehiculos_catalogo: ' . $e->getMessage(), 'warning');
    }
    return $cols;
}

$maxPhotoBytes = 12 * 1024 * 1024;
$isMultipart = isset($_FILES['photo_file']);
$data = [];

if ($isMultipart) {
    $data = [
        'tipo_vehiculo' => $_POST['tipo_vehiculo'] ?? null,
        'etiqueta' => $_POST['etiqueta'] ?? null,
        'descripcion' => $_POST['descripcion'] ?? '',
        'marca' => $_POST['marca'] ?? '',
        'modelo' => $_POST['modelo'] ?? '',
        'anio' => $_POST['anio'] ?? '',
        'color' => $_POST['color'] ?? '',
        'placa_referencia' => $_POST['placa_referencia'] ?? '',
        'bbox_json' => $_POST['bbox_json'] ?? null,
    ];
} else {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        jsonErr('No se recibió payload', 400);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        jsonErr('JSON inválido', 400);
    }
}

$photoB64 = $data['photo_b64'] ?? null;
$tipoVehiculo = isset($data['tipo_vehiculo']) ? limpiar($data['tipo_vehiculo']) : null;
$etiqueta = isset($data['etiqueta']) ? limpiar($data['etiqueta']) : null;
$descripcion = isset($data['descripcion']) ? limpiar($data['descripcion']) : '';
$marca = isset($data['marca']) ? limpiar($data['marca']) : '';
$modelo = isset($data['modelo']) ? limpiar($data['modelo']) : '';
$anio = isset($data['anio']) ? limpiar($data['anio']) : '';
$color = isset($data['color']) ? limpiar($data['color']) : '';
$placaReferencia = isset($data['placa_referencia']) ? limpiar($data['placa_referencia']) : '';

$bboxJson = null;
if (isset($data['bbox_json']) && $data['bbox_json'] !== null && $data['bbox_json'] !== '') {
    $decodedBbox = json_decode((string)$data['bbox_json'], true);
    if (!is_array($decodedBbox)) {
        jsonErr('bbox_json inválido', 400);
    }

    $x = isset($decodedBbox['x']) ? floatval($decodedBbox['x']) : null;
    $y = isset($decodedBbox['y']) ? floatval($decodedBbox['y']) : null;
    $w = isset($decodedBbox['w']) ? floatval($decodedBbox['w']) : null;
    $h = isset($decodedBbox['h']) ? floatval($decodedBbox['h']) : null;

    if ($x === null || $y === null || $w === null || $h === null) {
        jsonErr('bbox_json requiere x,y,w,h', 400);
    }

    if ($x < 0 || $y < 0 || $w <= 0 || $h <= 0 || $x > 1 || $y > 1 || $w > 1 || $h > 1 || ($x + $w) > 1.001 || ($y + $h) > 1.001) {
        jsonErr('bbox_json fuera de rango. Usa proporciones 0..1', 400);
    }

    $bboxJson = json_encode([
        'x' => round($x, 4),
        'y' => round($y, 4),
        'w' => round($w, 4),
        'h' => round($h, 4),
    ], JSON_UNESCAPED_UNICODE);
}

if (!$isMultipart && !$photoB64) {
    jsonErr('Campo photo_b64 requerido en modo JSON', 400);
}

if (!$tipoVehiculo) {
    jsonErr('Campo tipo_vehiculo requerido (accidente, vehiculo_empresa, camion_recolector)', 400);
}

$tiposValidos = ['accidente', 'vehiculo_empresa', 'camion_recolector'];
if (!in_array($tipoVehiculo, $tiposValidos, true)) {
    jsonErr('Tipo de vehículo no válido', 400);
}

if (!$etiqueta || strlen($etiqueta) < 2) {
    jsonErr('Etiqueta debe tener al menos 2 caracteres', 400);
}

$photoBinary = null;
if ($isMultipart) {
    if (!isset($_FILES['photo_file']) || !is_array($_FILES['photo_file'])) {
        jsonErr('Campo photo_file requerido en multipart', 400);
    }

    $up = $_FILES['photo_file'];
    if (($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        jsonErr('Error subiendo archivo de imagen', 400);
    }

    $tmpPath = $up['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        jsonErr('Archivo temporal inválido', 400);
    }

    $photoBinary = file_get_contents($tmpPath);
    if ($photoBinary === false) {
        jsonErr('No se pudo leer archivo subido', 400);
    }
} else {
    $photoBinary = base64_decode((string)$photoB64, true);
    if ($photoBinary === false) {
        jsonErr('Base64 inválido', 400);
    }
}

$binaryLength = strlen($photoBinary);
if ($binaryLength === 0 || $binaryLength > $maxPhotoBytes) {
    jsonErr('Imagen excede tamaño permitido (máx 12MB)', 413);
}

if (!isValidJpegBinary($photoBinary)) {
    jsonErr('Solo se aceptan imágenes JPEG válidas', 415);
}

$tmpFile = tempnam(sys_get_temp_dir(), 'jpeg_');
file_put_contents($tmpFile, $photoBinary);
$imgInfo = @getimagesize($tmpFile);
@unlink($tmpFile);

if (!$imgInfo || $imgInfo[2] !== IMAGETYPE_JPEG) {
    jsonErr('Formato de imagen no válido', 415);
}

$baseDir = __DIR__ . '/../../uploads/vehiculos_registrados';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}

$tipoDir = $baseDir . '/' . $tipoVehiculo;
if (!is_dir($tipoDir)) {
    mkdir($tipoDir, 0755, true);
}

$timestamp = date('YmdHis');
$randomSuffix = bin2hex(random_bytes(4));
$labelSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $etiqueta);
$filename = "{$tipoVehiculo}_{$labelSafe}_{$timestamp}_{$randomSuffix}.jpg";
$filePath = $tipoDir . '/' . $filename;

$bytesWritten = file_put_contents($filePath, $photoBinary);
if ($bytesWritten === false) {
    jsonErr('Error guardando archivo', 500);
}

try {
    $pdo = getPDO();
    ensureVehiculosCatalogoTable($pdo);
    $columns = getVehiculosCatalogoColumns($pdo);

    $insertData = [
        'tipo_vehiculo' => $tipoVehiculo,
        'etiqueta' => $etiqueta,
        'descripcion' => $descripcion,
        'ruta_archivo' => '/PRERMI/uploads/vehiculos_registrados/' . $tipoVehiculo . '/' . $filename,
        'photo_base64' => $isMultipart ? null : $photoB64,
        'estado' => 'activo',
    ];

    $optionalData = [
        'marca' => $marca,
        'modelo' => $modelo,
        'anio' => $anio,
        'color' => $color,
        'placa_referencia' => $placaReferencia,
        'bbox_json' => $bboxJson,
    ];

    foreach ($optionalData as $col => $value) {
        if (isset($columns[$col])) {
            $insertData[$col] = $value;
        }
    }

    $insertCols = array_keys($insertData);
    $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
    $sql = 'INSERT INTO vehiculos_catalogo (' . implode(', ', $insertCols) . ', created_at) VALUES (' . $placeholders . ', NOW())';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($insertData));

    $catalogoId = $pdo->lastInsertId();
    registrarLog('Imagen de referencia cargada: ' . $tipoVehiculo . '/' . $etiqueta . ' (' . $filename . ')', 'info');

    jsonOk([
        'catalogo_id' => $catalogoId,
        'filename' => $filename,
        'tipo_vehiculo' => $tipoVehiculo,
        'etiqueta' => $etiqueta,
        'marca' => $marca,
        'modelo' => $modelo,
        'anio' => $anio,
        'color' => $color,
        'placa_referencia' => $placaReferencia,
        'bbox_json' => $bboxJson,
        'modo' => $isMultipart ? 'multipart' : 'json_base64',
        'msg' => 'Imagen cargada exitosamente'
    ]);
} catch (PDOException $e) {
    @unlink($filePath);
    registrarLog('Error al registrar imagen en BD: ' . $e->getMessage(), 'error');
    jsonErr('Error al registrar en base de datos: ' . $e->getMessage(), 500);
}
