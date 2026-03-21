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
        SELECT s.id, s.contenedor_id AS id_contenedor, s.descripcion, s.peso, s.creado_en, c.codigo_contenedor, c.ubicacion 
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
            background: #cc0000;
            color: white;
            white-space: nowrap;
        }
        .fecha-cell {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
        @media (max-width: 768px) {
            table {
                min-width: 760px;
            }
        }
    </style>
</head>

<body>

<header>
    <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="header-logo">
</header>

<h2 style="color:white; text-align:center;">Sanciones Registradas</h2>

<div class="table-wrap">
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
            <td class="fecha-cell"><?php echo htmlspecialchars(formatDateTimeRD($row['creado_en'] ?? null)); ?></td>
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
</div>

<p style="text-align:center;">
    <a href="dashboard_usuario.php">← Volver al Dashboard</a>
</p>

</body>
</html>
