<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userIdSesion = intval($_SESSION['user_id']);
$fotoFacialRutaFisica = __DIR__ . '/../uploads/rostros/face_' . $userIdSesion . '.jpg';

try {
    // Eliminar archivo físico
    if (file_exists($fotoFacialRutaFisica)) {
        if (!unlink($fotoFacialRutaFisica)) {
            throw new Exception('No se pudo eliminar el archivo físico');
        }
    }

    // Eliminar registro de la base de datos
    require_once __DIR__ . '/../api/utils.php';
    $pdo = getPDO();
    
    $stmt = $pdo->prepare("DELETE FROM rostros WHERE user_id = ?");
    $stmt->execute([$userIdSesion]);

    echo json_encode(['success' => true, 'message' => 'Foto facial eliminada correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
