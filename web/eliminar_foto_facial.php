<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userIdSesion = intval($_SESSION['user_id']);
$rostrosDir = __DIR__ . '/../uploads/rostros';
$userFaceDir = $rostrosDir . '/' . $userIdSesion;

try {
    require_once __DIR__ . '/../api/utils.php';
    $pdo = getPDO();

    $pdo->beginTransaction();

    $stmtSelect = $pdo->prepare("SELECT filename, relative_path FROM rostros WHERE user_id = ?");
    $stmtSelect->execute([$userIdSesion]);
    $rows = $stmtSelect->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $filePaths = [];
    foreach ($rows as $row) {
        $cleanFilename = basename((string) ($row['filename'] ?? ''));
        if ($cleanFilename !== '') {
            $filePaths[] = $userFaceDir . '/' . $cleanFilename;
            $filePaths[] = $rostrosDir . '/' . $cleanFilename;
        }

        $relativePath = str_replace(['..', '\\'], '', (string) ($row['relative_path'] ?? ''));
        if ($relativePath !== '') {
            $filePaths[] = __DIR__ . '/../uploads/' . $relativePath;
        }
    }

    $legacyPatterns = [
        $rostrosDir . '/face_' . $userIdSesion . '.jpg',
        $rostrosDir . '/face_' . $userIdSesion . '.jpeg',
        $rostrosDir . '/face_' . $userIdSesion . '.png',
        $rostrosDir . '/face_' . $userIdSesion . '.webp',
    ];
    $filePaths = array_values(array_unique(array_merge($filePaths, $legacyPatterns)));

    foreach ($filePaths as $filePath) {
        if (is_file($filePath) && !unlink($filePath)) {
            throw new Exception('No se pudo eliminar el archivo físico: ' . basename($filePath));
        }
    }

    if (is_dir($userFaceDir)) {
        $userFiles = glob($userFaceDir . '/*');
        if (is_array($userFiles)) {
            foreach ($userFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
        @rmdir($userFaceDir);
    }

    $stmtDelete = $pdo->prepare("DELETE FROM rostros WHERE user_id = ?");
    $stmtDelete->execute([$userIdSesion]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Foto facial eliminada correctamente']);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
