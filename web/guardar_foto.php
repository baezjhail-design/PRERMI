<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['image_data'])) {

    $image = $_POST['image_data'];
    $image = preg_replace('#^data:image/\w+;base64,#i', '', $image);
    $image = str_replace(' ', '+', $image);
    $imageData = base64_decode($image);

    // Guardar en uploads/rostros con nombre face_{user_id}.jpg
    $uploadsDir = __DIR__ . '/../uploads';
    $rostrosDir = $uploadsDir . '/rostros';
    if (!file_exists($rostrosDir)) {
        mkdir($rostrosDir, 0777, true);
    }

    $faceFilename = 'face_' . $_SESSION['user_id'] . '.jpg';
    $facePath = $rostrosDir . '/' . $faceFilename;
    file_put_contents($facePath, $imageData);

    // Insertar en base de datos (tabla `rostros`). Usamos utils->getPDO()
    try {
        require_once __DIR__ . '/../api/utils.php';

        $pdo = getPDO();

        // Crear tabla si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS rostros (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            filename VARCHAR(255),
            image LONGBLOB,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $pdo->prepare("INSERT INTO rostros (user_id, filename, image) VALUES (?, ?, ?)");
        $stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(2, $faceFilename, PDO::PARAM_STR);
        $stmt->bindParam(3, $imageData, PDO::PARAM_LOB);
        $stmt->execute();
    } catch (Exception $e) {
        // Registrar fallo de forma no intrusiva
        if (function_exists('registrarLog')) {
            registrarLog('Error guardando rostro en DB: ' . $e->getMessage(), 'error');
        }
    }

    echo "<script>alert('Registro facial guardado correctamente'); window.location='user-dashboard.php';</script>";
}
?>