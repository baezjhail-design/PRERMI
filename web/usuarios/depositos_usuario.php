<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

require_once "../../config/db_config.php";
require_once "../../api/utils.php";

date_default_timezone_set('America/Santo_Domingo');

function formatDateTimeRD(?string $value, string $format = 'd/m/Y H:i:s'): string {
    if (empty($value)) return '—';
    try {
        $tz = new DateTimeZone('America/Santo_Domingo');
        $dt = new DateTimeImmutable($value);
        return $dt->setTimezone($tz)->format($format);
    } catch (Exception $e) {
        return (string)$value;
    }
}

$id = $_SESSION['usuario_id'];

try {
    $pdo = getPDO();
    $pdo->exec("SET time_zone = '-04:00'");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .table-wrap {
            width: 92%;
            margin: 25px auto;
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        }
        table {
            width: 100%;
            margin: 0;
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
            white-space: nowrap;
        }
        td {
            color: #263238;
        }
        .fecha-cell {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
        .token-cell {
            white-space: nowrap;
        }
        @media (max-width: 768px) {
            table {
                min-width: 860px;
            }
        }
    </style>
</head>

<body>

<header>
    <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="header-logo">
</header>

<h2 style="text-align:center; color:white;">Historial de Depósitos</h2>

<div class="table-wrap">
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
            <td class="fecha-cell"><?php echo htmlspecialchars(formatDateTimeRD($row['fecha_hora'] ?? null)); ?></td>
            <td class="token-cell"><code style="font-size: 0.75rem;"><?php $tok = trim((string)($row['token_usado'] ?? '')); echo $tok !== '' ? htmlspecialchars(substr($tok, 0, 12) . '...') : 'N/A'; ?></code></td>
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
</div>

<p style="text-align:center;">
    <a href="dashboard_usuario.php">← Volver al Dashboard</a>
</p>

</body>
</html>
