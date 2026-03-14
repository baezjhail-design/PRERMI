<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

require_once "../../config/db_config.php";
require_once "../../api/utils.php";

$id = $_SESSION['usuario_id'];

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT m.id, m.id_contenedor, m.descripcion, m.peso, m.creado_en, c.codigo_contenedor, c.ubicacion 
        FROM sanciones s 
        LEFT JOIN contenedores_registrados c ON s.contenedor_id = c.id 
        WHERE s.user_id = ?
        ORDER BY s.creado_en DESC
    ");
    $stmt->execute([$id]);
    $multas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $multas = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Sanciones - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">

    <style>
        table {
            width: 90%;
            margin: 25px auto;
            background: white;
            border-radius: 10px;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background: #cc0000;
            color: white;
        }
    </style>
</head>

<body>

<header>
    <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="header-logo">
</header>

<h2 style="color:white; text-align:center;">Sanciones Registradas</h2>

<table>
    <tr>
        <th>ID Contenedor</th>
        <th>Ubicación</th>
        <th>Motivo</th>
        <th>Peso (kg)</th>
        <th>Fecha</th>
    </tr>

    <?php if (!empty($multas)): ?>
        <?php foreach($multas as $row): ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($row['codigo_contenedor'] ?? 'Desconocido'); ?></strong></td>
            <td><?php echo htmlspecialchars($row['ubicacion'] ?? 'No especificada'); ?></td>
            <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
            <td><?php echo number_format($row['peso'], 2); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($row['creado_en'])); ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-check-circle" style="font-size: 2rem; color: #51cf66; display: block; margin-bottom: 10px;"></i>
                <strong>Sin sanciones registradas</strong><br>
                <small>¡Excelente! Mantén tus depósitos limpios</small>
            </td>
        </tr>
    <?php endif; ?>
</table>

<p style="text-align:center;">
    <a href="dashboard_usuario.php">← Volver al Dashboard</a>
</p>

</body>
</html>
