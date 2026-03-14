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
        SELECT d.id, d.id_contenedor, d.peso, d.tipo_residuo, d.credito_kwh, d.fecha_hora, d.token_usado, c.codigo_contenedor, c.ubicacion 
        FROM depositos d 
        LEFT JOIN contenedores_registrados c ON d.id_contenedor = c.id 
        WHERE d.id_usuario = ? 
        ORDER BY d.fecha_hora DESC
    ");
    $stmt->execute([$id]);
    $depositos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $depositos = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Depósitos - PRERMI</title>
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
            background: #00aa99;
            color: white;
        }
    </style>
</head>

<body>

<header>
    <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="header-logo">
</header>

<h2 style="text-align:center; color:white;">Historial de Depósitos</h2>

<table>
    <tr>
        <th>Contenedor</th>
        <th>Peso (kg)</th>
        <th>Tipo Residuo</th>
        <th>Crédito (kWh)</th>
        <th>Fecha/Hora</th>
        <th>Token Usado</th>
        <th>Ubicación</th>
    </tr>

    <?php if (!empty($depositos)): ?>
        <?php foreach($depositos as $row): ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($row['codigo_contenedor'] ?? 'Desconocido'); ?></strong></td>
            <td><?php echo number_format($row['peso'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['tipo_residuo'] ?? 'general'); ?></td>
            <td><?php echo number_format($row['credito_kwh'], 4); ?></td>
            <td><?php echo date('d/m/Y H:i:s', strtotime($row['fecha_hora'])); ?></td>
            <td><code style="font-size: 0.75rem;"><?php echo substr($row['token_usado'] ?? 'N/A', 0, 12); ?>...</code></td>
            <td><?php echo htmlspecialchars($row['ubicacion'] ?? 'No especificada'); ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                <strong>Sin depósitos registrados</strong><br>
                <small>Inicia tu primer depósito para ver el historial aquí</small>
            </td>
        </tr>
    <?php endif; ?>
</table>

<p style="text-align:center;">
    <a href="dashboard_usuario.php">← Volver al Dashboard</a>
</p>

</body>
</html>
