<?php
/**
 * manage_catalogo.php — Endpoint para gestionar catalogo de vehículos
 * Operaciones: listar, actualizar estado, eliminar, obtener detalles
 */

require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar sesión de admin
requireAdminSession();

function ensureVehiculosCatalogoTable(PDO $pdo) {
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
        registrarLog('No se pudieron leer columnas de vehiculos_catalogo: ' . $e->getMessage(), 'warning');
    }
    return $cols;
}

function resolveCatalogAbsolutePath(string $rutaArchivo): ?string {
    $relative = trim($rutaArchivo);
    if ($relative === '') {
        return null;
    }

    if (strpos($relative, '/PRERMI/') === 0) {
        $relative = substr($relative, strlen('/PRERMI/'));
    }

    $absolute = realpath(__DIR__ . '/../../');
    if (!$absolute) {
        return null;
    }

    return $absolute . '/' . ltrim(str_replace('\\', '/', $relative), '/');
}

function validateUploadedJpeg(array $upload, int $maxBytes): string {
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        jsonErr('Error subiendo archivo de imagen', 400);
    }

    $tmpPath = $upload['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        jsonErr('Archivo temporal inválido', 400);
    }

    $binary = file_get_contents($tmpPath);
    if ($binary === false || strlen($binary) === 0) {
        jsonErr('No se pudo leer archivo subido', 400);
    }

    if (strlen($binary) > $maxBytes) {
        jsonErr('Imagen excede tamaño permitido (máx 12MB)', 413);
    }

    if (!isValidJpegBinary($binary)) {
        jsonErr('Solo se aceptan imágenes JPEG válidas', 415);
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'jpeg_');
    file_put_contents($tmpFile, $binary);
    $imgInfo = @getimagesize($tmpFile);
    @unlink($tmpFile);

    if (!$imgInfo || $imgInfo[2] !== IMAGETYPE_JPEG) {
        jsonErr('Formato de imagen no válido', 415);
    }

    return $binary;
}

$action = isset($_GET['action']) ? limpiar($_GET['action']) : null;
$maxPhotoBytes = 12 * 1024 * 1024;

try {
    $pdo = getPDO();

    // Ensure table exists — non-fatal if it fails (table may already exist)
    try {
        ensureVehiculosCatalogoTable($pdo);
    } catch (PDOException $eTable) {
        registrarLog('ensureVehiculosCatalogoTable falló: ' . $eTable->getMessage(), 'warning');
    }

    switch ($action) {
        case 'list':
            // Listar todas las imágenes del catálogo con filtros opcionales
            $tipoVehiculo = isset($_GET['tipo']) ? limpiar($_GET['tipo']) : null;
            $estado = isset($_GET['estado']) ? limpiar($_GET['estado']) : null;
            
            $cols = getVehiculosCatalogoColumns($pdo);
            // If getVehiculosCatalogoColumns returned empty (table issues), return empty list gracefully
            if (empty($cols)) {
                jsonOk(['catalogo' => [], 'total' => 0, 'warn' => 'Tabla no disponible']);
            }
            $optional = ['marca', 'modelo', 'anio', 'color', 'placa_referencia', 'bbox_json'];
            $selectCols = ['id', 'tipo_vehiculo', 'etiqueta', 'descripcion', 'ruta_archivo', 'estado', 'created_at'];
            foreach ($optional as $col) {
                if (isset($cols[$col])) {
                    $selectCols[] = $col;
                } else {
                    $selectCols[] = "NULL AS " . $col;
                }
            }

            $query = "SELECT " . implode(', ', $selectCols) . " FROM vehiculos_catalogo WHERE estado != 'eliminado'";
            $params = [];
            
            if ($tipoVehiculo) {
                $query .= " AND tipo_vehiculo = ?";
                $params[] = $tipoVehiculo;
            }
            
            if ($estado) {
                $query .= " AND estado = ?";
                $params[] = $estado;
            }
            
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $catalogo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonOk(['catalogo' => $catalogo, 'total' => count($catalogo)]);
            
        case 'get':
            // Obtener detalles de una imagen específica
            $catalogoId = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if (!$catalogoId) {
                jsonErr('ID de catálogo requerido', 400);
            }
            
            $stmt = $pdo->prepare("SELECT * FROM vehiculos_catalogo WHERE id = ?");
            $stmt->execute([$catalogoId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                jsonErr('Imagen no encontrada', 404);
            }
            
            jsonOk(['item' => $item]);
            
        case 'update':
            // Actualizar estado o metadatos de una imagen
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            
            $catalogoId = isset($data['id']) ? intval($data['id']) : null;
            if (!$catalogoId) {
                jsonErr('ID de catálogo requerido', 400);
            }
            
            $cols = getVehiculosCatalogoColumns($pdo);
            // Construir query dinámico según campos proporcionados
            $updates = [];
            $params = [];
            
            if (isset($data['estado'])) {
                $estado = limpiar($data['estado']);
                if (!in_array($estado, ['activo', 'inactivo', 'eliminado'], true)) {
                    jsonErr('Estado no válido (activo, inactivo o eliminado)', 400);
                }
                $updates[] = "estado = ?";
                $params[] = $estado;
            }
            
            if (isset($data['etiqueta'])) {
                $etiqueta = limpiar($data['etiqueta']);
                if (strlen($etiqueta) < 2) {
                    jsonErr('Etiqueta debe tener al menos 2 caracteres', 400);
                }
                $updates[] = "etiqueta = ?";
                $params[] = $etiqueta;
            }
            
            if (isset($data['descripcion'])) {
                $descripcion = limpiar($data['descripcion']);
                $updates[] = "descripcion = ?";
                $params[] = $descripcion;
            }

            if (isset($data['marca']) && isset($cols['marca'])) {
                $updates[] = "marca = ?";
                $params[] = limpiar((string)$data['marca']);
            }

            if (isset($data['modelo']) && isset($cols['modelo'])) {
                $updates[] = "modelo = ?";
                $params[] = limpiar((string)$data['modelo']);
            }

            if (isset($data['anio']) && isset($cols['anio'])) {
                $updates[] = "anio = ?";
                $params[] = limpiar((string)$data['anio']);
            }

            if (isset($data['color']) && isset($cols['color'])) {
                $updates[] = "color = ?";
                $params[] = limpiar((string)$data['color']);
            }

            if (isset($data['placa_referencia']) && isset($cols['placa_referencia'])) {
                $updates[] = "placa_referencia = ?";
                $params[] = limpiar((string)$data['placa_referencia']);
            }

            if (array_key_exists('bbox_json', $data) && isset($cols['bbox_json'])) {
                $bboxJson = $data['bbox_json'];
                if ($bboxJson === null || $bboxJson === '') {
                    $updates[] = "bbox_json = NULL";
                } else {
                    if (is_array($bboxJson)) {
                        $bboxDecoded = $bboxJson;
                    } else {
                        $bboxDecoded = json_decode((string)$bboxJson, true);
                    }

                    if (!is_array($bboxDecoded)) {
                        jsonErr('bbox_json inválido', 400);
                    }

                    $x = isset($bboxDecoded['x']) ? floatval($bboxDecoded['x']) : null;
                    $y = isset($bboxDecoded['y']) ? floatval($bboxDecoded['y']) : null;
                    $w = isset($bboxDecoded['w']) ? floatval($bboxDecoded['w']) : null;
                    $h = isset($bboxDecoded['h']) ? floatval($bboxDecoded['h']) : null;
                    if ($x === null || $y === null || $w === null || $h === null) {
                        jsonErr('bbox_json requiere x,y,w,h', 400);
                    }

                    if ($x < 0 || $y < 0 || $w <= 0 || $h <= 0 || $x > 1 || $y > 1 || $w > 1 || $h > 1 || ($x + $w) > 1.001 || ($y + $h) > 1.001) {
                        jsonErr('bbox_json fuera de rango. Usa proporciones 0..1', 400);
                    }

                    $updates[] = "bbox_json = ?";
                    $params[] = json_encode([
                        'x' => round($x, 4),
                        'y' => round($y, 4),
                        'w' => round($w, 4),
                        'h' => round($h, 4),
                    ], JSON_UNESCAPED_UNICODE);
                }
            }
            
            if (empty($updates)) {
                jsonErr('No hay campos para actualizar', 400);
            }
            
            $params[] = $catalogoId;
            $query = "UPDATE vehiculos_catalogo SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($query);
            
            if (!$stmt->execute($params)) {
                jsonErr('Error al actualizar registro', 500);
            }
            
            registrarLog("Catálogo de vehículos actualizado (ID: $catalogoId)", 'info');
            jsonOk(['msg' => 'Actualizado exitosamente']);

        case 'replace_image':
            $catalogoId = isset($_POST['id']) ? intval($_POST['id']) : null;
            if (!$catalogoId) {
                jsonErr('ID de catálogo requerido', 400);
            }

            if (!isset($_FILES['photo_file']) || !is_array($_FILES['photo_file'])) {
                jsonErr('Campo photo_file requerido', 400);
            }

            $stmt = $pdo->prepare("SELECT id, tipo_vehiculo, etiqueta, ruta_archivo FROM vehiculos_catalogo WHERE id = ? LIMIT 1");
            $stmt->execute([$catalogoId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                jsonErr('Imagen no encontrada', 404);
            }

            $photoBinary = validateUploadedJpeg($_FILES['photo_file'], $maxPhotoBytes);

            $baseDir = __DIR__ . '/../../uploads/vehiculos_registrados';
            $tipoDir = $baseDir . '/' . $item['tipo_vehiculo'];
            if (!is_dir($tipoDir) && !mkdir($tipoDir, 0755, true) && !is_dir($tipoDir)) {
                jsonErr('No se pudo preparar directorio destino', 500);
            }

            $timestamp = date('YmdHis');
            $randomSuffix = bin2hex(random_bytes(4));
            $labelSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$item['etiqueta']);
            $filename = "{$item['tipo_vehiculo']}_{$labelSafe}_{$timestamp}_{$randomSuffix}.jpg";
            $newPath = $tipoDir . '/' . $filename;

            if (file_put_contents($newPath, $photoBinary) === false) {
                jsonErr('Error guardando nueva imagen', 500);
            }

            $newRuta = '/PRERMI/uploads/vehiculos_registrados/' . $item['tipo_vehiculo'] . '/' . $filename;
            $stmt = $pdo->prepare("UPDATE vehiculos_catalogo SET ruta_archivo = ?, photo_base64 = NULL WHERE id = ?");
            if (!$stmt->execute([$newRuta, $catalogoId])) {
                @unlink($newPath);
                jsonErr('Error actualizando imagen del catálogo', 500);
            }

            $oldAbsolutePath = resolveCatalogAbsolutePath((string)$item['ruta_archivo']);
            if ($oldAbsolutePath && is_file($oldAbsolutePath) && realpath($oldAbsolutePath) !== realpath($newPath)) {
                @unlink($oldAbsolutePath);
            }

            registrarLog("Imagen de catálogo reemplazada (ID: $catalogoId)", 'info');
            jsonOk([
                'msg' => 'Imagen actualizada exitosamente',
                'ruta_archivo' => $newRuta,
            ]);
            
        case 'delete':
            // Eliminar una imagen (suave: marcar como eliminado)
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            
            $catalogoId = isset($data['id']) ? intval($data['id']) : null;
            if (!$catalogoId) {
                jsonErr('ID de catálogo requerido', 400);
            }
            
            $stmt = $pdo->prepare("SELECT id FROM vehiculos_catalogo WHERE id = ?");
            $stmt->execute([$catalogoId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                jsonErr('Imagen no encontrada', 404);
            }
            
            // Marcar como inactivo (soft delete)
            $stmt = $pdo->prepare("UPDATE vehiculos_catalogo SET estado = 'eliminado' WHERE id = ?");
            $stmt->execute([$catalogoId]);
            
            registrarLog("Catálogo eliminado (ID: $catalogoId)", 'info');
            jsonOk(['msg' => 'Eliminado exitosamente']);
            
        case 'stats':
            // Estadísticas del catálogo
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN tipo_vehiculo = 'accidente' THEN 1 ELSE 0 END) as accidentes,
                    SUM(CASE WHEN tipo_vehiculo = 'vehiculo_empresa' THEN 1 ELSE 0 END) as empresa,
                    SUM(CASE WHEN tipo_vehiculo = 'camion_recolector' THEN 1 ELSE 0 END) as camiones
                FROM vehiculos_catalogo WHERE estado != 'eliminado'
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            jsonOk(['stats' => $stats]);
            
        default:
            jsonErr('Acción no válida (list, get, update, replace_image, delete, stats)', 400);
    }
    
} catch (PDOException $e) {
    $errMsg = $e->getMessage();
    $errCode = $e->getCode();
    // Log without calling getPDO again to avoid double-error
    error_log("manage_catalogo PDOException [{$errCode}]: {$errMsg}");
    jsonErr('Error de base de datos: [' . $errCode . '] ' . $errMsg, 500);
}
