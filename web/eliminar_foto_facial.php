<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userIdSesion = intval($_SESSION['user_id']);
$rostrosDir = __DIR__ . '/../uploads/rostros';

try {
    require_once __DIR__ . '/../api/utils.php';
    $pdo = getPDO();

    $pdo->beginTransaction();

    $stmtSelect = $pdo->prepare("SELECT filename FROM rostros WHERE user_id = ?");
    $stmtSelect->execute([$userIdSesion]);
    $filenames = $stmtSelect->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $filePaths = [];
    foreach ($filenames as $filename) {
        $cleanFilename = basename((string) $filename);
        if ($cleanFilename !== '') {
            $filePaths[] = $rostrosDir . '/' . $cleanFilename;
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
