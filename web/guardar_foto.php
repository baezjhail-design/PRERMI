<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$minPhotosRequired = 15;
$batchRequired = 3;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Registrofac.php');
    exit;
}

$imageDataList = [];
if (isset($_POST['image_data_list'])) {
    $decoded = json_decode((string) $_POST['image_data_list'], true);
    if (is_array($decoded)) {
        $imageDataList = $decoded;
    }
} elseif (isset($_POST['image_data'])) {
    $imageDataList[] = (string) $_POST['image_data'];
}

if (count($imageDataList) !== $batchRequired) {
    echo "<script>alert('Debes capturar exactamente 3 fotos por lote.'); window.location='Registrofac.php';</script>";
    exit;
}

$uploadsDir = __DIR__ . '/../uploads';
$rostrosDir = $uploadsDir . '/rostros';
$userFaceDir = $rostrosDir . '/' . $userId;

if (!is_dir($userFaceDir) && !mkdir($userFaceDir, 0775, true) && !is_dir($userFaceDir)) {
    echo "<script>alert('No se pudo crear la carpeta de rostros del usuario.'); window.location='Registrofac.php';</script>";
    exit;
}

try {
    require_once __DIR__ . '/../api/utils.php';

    $pdo = getPDO();
    $pdo->beginTransaction();

    $pdo->exec("CREATE TABLE IF NOT EXISTS rostros (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        filename VARCHAR(255),
        image LONGBLOB,
        relative_path VARCHAR(255) NULL,
        calidad DECIMAL(6,2) NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $userIdCheck = $pdo->query("SHOW COLUMNS FROM rostros LIKE 'user_id'")->fetch(PDO::FETCH_ASSOC);
    if (!$userIdCheck) {
        $pdo->exec("ALTER TABLE rostros ADD COLUMN user_id INT NULL AFTER id");
    }

    $filenameCheck = $pdo->query("SHOW COLUMNS FROM rostros LIKE 'filename'")->fetch(PDO::FETCH_ASSOC);
    if (!$filenameCheck) {
        $pdo->exec("ALTER TABLE rostros ADD COLUMN filename VARCHAR(255) NULL AFTER user_id");
    }

    $imageCheck = $pdo->query("SHOW COLUMNS FROM rostros LIKE 'image'")->fetch(PDO::FETCH_ASSOC);
    if (!$imageCheck) {
        $pdo->exec("ALTER TABLE rostros ADD COLUMN image LONGBLOB NULL AFTER filename");
    }

    $columnCheck = $pdo->query("SHOW COLUMNS FROM rostros LIKE 'relative_path'")->fetch(PDO::FETCH_ASSOC);
    if (!$columnCheck) {
        $pdo->exec("ALTER TABLE rostros ADD COLUMN relative_path VARCHAR(255) NULL AFTER image");
    }
    $qualityCheck = $pdo->query("SHOW COLUMNS FROM rostros LIKE 'calidad'")->fetch(PDO::FETCH_ASSOC);
    if (!$qualityCheck) {
        $pdo->exec("ALTER TABLE rostros ADD COLUMN calidad DECIMAL(6,2) NULL AFTER relative_path");
    }

    $createdAtCheck = $pdo->query("SHOW COLUMNS FROM rostros LIKE 'creado_en'")->fetch(PDO::FETCH_ASSOC);
    if (!$createdAtCheck) {
        $pdo->exec("ALTER TABLE rostros ADD COLUMN creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }

    // Migracion de esquema legado: si user_id es unico, rompe el guardado por lote (3 fotos).
    $indexes = $pdo->query("SHOW INDEX FROM rostros")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $uniqueUserOnlyIndex = null;
    $indexColumns = [];
    foreach ($indexes as $idx) {
        if (!isset($idx['Key_name'], $idx['Column_name'], $idx['Non_unique'])) {
            continue;
        }
        $keyName = (string) $idx['Key_name'];
        $columnName = (string) $idx['Column_name'];
        $nonUnique = (int) $idx['Non_unique'];

        if (!isset($indexColumns[$keyName])) {
            $indexColumns[$keyName] = ['non_unique' => $nonUnique, 'cols' => []];
        }
        $indexColumns[$keyName]['cols'][] = $columnName;
    }
    foreach ($indexColumns as $keyName => $meta) {
        $cols = array_values(array_unique($meta['cols']));
        if ((int) $meta['non_unique'] === 0 && count($cols) === 1 && $cols[0] === 'user_id' && strtoupper($keyName) !== 'PRIMARY') {
            $uniqueUserOnlyIndex = $keyName;
            break;
        }
    }
    if ($uniqueUserOnlyIndex !== null) {
        $safeIndex = str_replace('`', '``', $uniqueUserOnlyIndex);
        $pdo->exec("ALTER TABLE rostros DROP INDEX `{$safeIndex}`");
    }

    $insertStmt = $pdo->prepare(
        "INSERT INTO rostros (user_id, filename, image, relative_path, calidad) VALUES (?, ?, NULL, ?, ?)"
    );

    $savedCount = 0;
    $latestFacePath = '';

    foreach ($imageDataList as $index => $rawImage) {
        $rawImage = (string) $rawImage;
        $rawImage = preg_replace('#^data:image/\w+;base64,#i', '', $rawImage);
        $rawImage = str_replace(' ', '+', $rawImage);
        $binary = base64_decode($rawImage, true);

        if ($binary === false || strlen($binary) < 5000) {
            continue;
        }

        $qualityScore = min(100.0, max(0.0, (strlen($binary) / 2500.0)));
        $filename = 'face_' . $userId . '_' . date('Ymd_His') . '_' . ($index + 1) . '_' . substr(md5($binary), 0, 8) . '.jpg';
        $facePath = $userFaceDir . '/' . $filename;
        $relativePath = 'rostros/' . $userId . '/' . $filename;

        if (file_put_contents($facePath, $binary) === false) {
            continue;
        }

        $insertStmt->bindValue(1, $userId, PDO::PARAM_INT);
        $insertStmt->bindValue(2, $filename, PDO::PARAM_STR);
        $insertStmt->bindValue(3, $relativePath, PDO::PARAM_STR);
        $insertStmt->bindValue(4, round($qualityScore, 2));
        $insertStmt->execute();

        $savedCount++;
        $latestFacePath = $facePath;
    }

    if ($savedCount === 0) {
        throw new Exception('No se pudo guardar ninguna foto valida.');
    }

    // Mantener compatibilidad con flujo legado (face_<id>.jpg)
    if ($latestFacePath !== '' && is_file($latestFacePath)) {
        $legacyPath = $rostrosDir . '/face_' . $userId . '.jpg';
        @copy($latestFacePath, $legacyPath);
    }

    // Limitar historico por usuario para no crecer indefinidamente.
    $maxPhotosPerUser = 80;
    $stmtIds = $pdo->prepare("SELECT id, filename, relative_path FROM rostros WHERE user_id = ? ORDER BY creado_en DESC, id DESC");
    $stmtIds->execute([$userId]);
    $rows = $stmtIds->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (count($rows) > $maxPhotosPerUser) {
        $toDelete = array_slice($rows, $maxPhotosPerUser);
        $deleteStmt = $pdo->prepare("DELETE FROM rostros WHERE id = ?");
        foreach ($toDelete as $r) {
            $deleteStmt->execute([(int) $r['id']]);
            $fileFromPath = '';
            if (!empty($r['relative_path'])) {
                $fileFromPath = $uploadsDir . '/' . str_replace(['..', '\\'], '', (string) $r['relative_path']);
            } elseif (!empty($r['filename'])) {
                $fileFromPath = $userFaceDir . '/' . basename((string) $r['filename']);
            }
            if ($fileFromPath !== '' && is_file($fileFromPath)) {
                @unlink($fileFromPath);
            }
        }
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM rostros WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $totalPhotos = (int) $countStmt->fetchColumn();

    $pdo->commit();

    $remaining = max(0, $minPhotosRequired - $totalPhotos);
    $msg = $remaining > 0
        ? "Lote guardado ({$savedCount} fotos). Te faltan {$remaining} para llegar a {$minPhotosRequired}."
        : "Excelente: ya tienes {$totalPhotos} fotos registradas para reconocimiento robusto.";

    echo "<script>alert('" . addslashes($msg) . "'); window.location='Registrofac.php';</script>";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (function_exists('registrarLog')) {
        registrarLog('Error guardando lote facial: ' . $e->getMessage(), 'error');
    }

    echo "<script>alert('Ocurrió un error al guardar las fotos. Intente de nuevo.'); window.location='Registrofac.php';</script>";
}
?>