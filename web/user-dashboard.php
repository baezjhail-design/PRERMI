<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userIdSesion = intval($_SESSION['user_id']);
$fotoFacialRutaFisica = __DIR__ . '/../uploads/rostros/face_' . $userIdSesion . '.jpg';
$fotoFacialRutaWeb = '../uploads/rostros/face_' . $userIdSesion . '.jpg';
$fotoFacialDisponible = file_exists($fotoFacialRutaFisica);

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../api/utils.php';

try {
    $pdo = getPDO();

    // Get user info
    $stmtUser = $pdo->prepare("SELECT nombre, apellido, usuario, email, token FROM usuarios WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Stats (totales) para el usuario
    $stmtStats = $pdo->prepare("SELECT COUNT(*) as totalDepositos, COALESCE(SUM(peso),0) as totalPeso, COALESCE(SUM(credito_kwh),0) as totalCredito FROM depositos WHERE id_usuario = ?");
    $stmtStats->execute([$_SESSION['user_id']]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    $totalDepositos = intval($stats['totalDepositos']);
    $totalPeso = floatval($stats['totalPeso']);
    $totalCredito = floatval($stats['totalCredito']);

    // Últimos 50 depósitos para historial
    $stmtDepositos = $pdo->prepare("SELECT d.id, d.id_contenedor, d.peso, d.metal_detectado, d.credito_kwh, d.creado_en, c.codigo_contenedor, c.ubicacion FROM depositos d LEFT JOIN contenedores_registrados c ON d.id_contenedor = c.id WHERE d.id_usuario = ? ORDER BY d.creado_en DESC LIMIT 50");
    $stmtDepositos->execute([$_SESSION['user_id']]);
    $depositos = $stmtDepositos->fetchAll(PDO::FETCH_ASSOC);

    // Datos para gráficas por mes (últimos 12 meses)
    $stmtMonthly = $pdo->prepare("SELECT DATE_FORMAT(creado_en, '%Y-%m') as ym, COUNT(*) as cnt, COALESCE(SUM(credito_kwh),0) as energy FROM depositos WHERE id_usuario = ? AND creado_en >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym ASC");
    $stmtMonthly->execute([$_SESSION['user_id']]);
    $monthly = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

    // Sanciones del usuario (tabla `sanciones`)
    $stmtSancionesList = $pdo->prepare("SELECT id, descripcion, peso, creado_en, seen_by_admin FROM sanciones WHERE user_id = ? ORDER BY creado_en DESC LIMIT 50");
    $stmtSancionesList->execute([$_SESSION['user_id']]);
    $sancionesList = $stmtSancionesList->fetchAll(PDO::FETCH_ASSOC);

    // Contar sanciones activas (no vistas por admin)
    $activeCount = 0;
    foreach ($sancionesList as $s) {
        if (isset($s['seen_by_admin']) && intval($s['seen_by_admin']) === 0) $activeCount++;
    }
    $totalMultas = $activeCount;

    // Tarifa eléctrica y cálculos de ahorro en pesos dominicanos (RD$)
    $tarifaKwhRD = 11.50; // RD$ por kWh — tarifa residencial promedio República Dominicana
    $consumoPromedioMensualKwh = 200; // kWh promedio mensual hogar dominicano
    $ahorroTotalRD = $totalCredito * $tarifaKwhRD;
    $mesesEquivalentes = ($consumoPromedioMensualKwh > 0) ? ($totalCredito / $consumoPromedioMensualKwh) : 0;

    $mesActual = date('Y-m');
    $ahorroMesActualKwh = 0;
    foreach ($monthly as $m) {
        if ($m['ym'] === $mesActual) {
            $ahorroMesActualKwh = floatval($m['energy']);
            break;
        }
    }
    $ahorroMesActualRD = $ahorroMesActualKwh * $tarifaKwhRD;

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Usuario - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-user {
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            padding: 1rem 2rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-user .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .navbar-user .nav-link {
            color: rgba(255,255,255,0.9);
            margin: 0 1rem;
            transition: all 0.3s ease;
        }

        .navbar-user .nav-link:hover {
            color: white;
            transform: translateY(-2px);
        }

        .user-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #ff6b6b;
            border-color: #ff6b6b;
            color: white;
        }

        .facial-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .facial-btn:hover {
            background: rgba(255,255,255,0.35);
            color: white;
        }

        .facial-modal-img {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }

        .container-main {
            padding: 3rem 2rem;
        }

        .card-custom {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: none;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .card-header-custom h3 {
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-top: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .table-container table {
            margin-bottom: 0;
        }

        .table-container thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .table-container thead th {
            border: none;
            font-weight: 600;
            padding: 1.5rem;
        }

        .table-container tbody td {
            padding: 1.2rem 1.5rem;
            border-color: #f0f0f0;
            vertical-align: middle;
        }

        .table-container tbody tr:hover {
            background: #f9f9f9;
        }

        .badge-metal {
            background: #ff6b6b;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-ok {
            background: #51cf66;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 2rem 0;
        }

        @media (max-width: 768px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }

            .stat-box {
                padding: 1.2rem 0.8rem;
            }

            .stat-icon {
                font-size: 1.8rem;
                margin-bottom: 0.5rem;
            }

            .stat-value {
                font-size: 1.4rem;
            }

            .stat-label {
                font-size: 0.8rem;
            }

            .container-main {
                padding: 1.5rem 0.8rem;
            }

            .card-header-custom {
                padding: 1.2rem;
            }

            .card-header-custom h3 {
                font-size: 1.1rem;
            }

            .chart-container {
                height: 220px;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table-container table {
                min-width: 500px;
            }

            .table-container thead th,
            .table-container tbody td {
                padding: 0.8rem 0.6rem;
                font-size: 0.85rem;
                white-space: nowrap;
            }

            .token-display {
                font-size: 0.85rem;
            }

            .navbar-user {
                padding: 0.8rem 1rem;
            }

            .user-info {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.8rem;
                padding: 0.8rem 0;
            }
        }

        @media (max-width: 480px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }

            .container-main {
                padding: 1rem 0.5rem;
            }

            .row > .col-lg-6 {
                padding-left: 0;
                padding-right: 0;
            }
        }

        /* ========================================================
           SECCIÓN POTENCIAL DE AHORRO EN PESOS DOMINICANOS
        ======================================================== */
        .savings-section-header {
            background: linear-gradient(135deg, #0f9b8e 0%, #11998e 40%, #38ef7d 100%);
            color: white;
            padding: 2rem 2.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 40px rgba(17, 153, 142, 0.35);
        }

        .savings-section-header h2 {
            font-weight: 700;
            font-size: 1.7rem;
            margin-bottom: 0.3rem;
        }

        .savings-section-header p {
            opacity: 0.9;
            font-size: 0.97rem;
            margin: 0;
        }

        .savings-stat-box {
            background: white;
            padding: 1.8rem 1.2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-top: 4px solid #11998e;
            transition: all 0.3s ease;
            height: 100%;
        }

        .savings-stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(17,153,142,0.22);
        }

        .savings-icon {
            font-size: 2.3rem;
            color: #11998e;
            margin-bottom: 0.7rem;
        }

        .savings-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #333;
        }

        .savings-value.highlight {
            font-size: 1.9rem;
            color: #0f9b8e;
        }

        .savings-label {
            color: #666;
            font-size: 0.88rem;
            margin-top: 0.4rem;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 5px solid #11998e;
            height: 100%;
        }

        .info-card.blue  { border-left-color: #667eea; }
        .info-card.orange { border-left-color: #fd7e14; }

        .info-card h6 {
            font-weight: 700;
            margin-bottom: 0.9rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .info-card p, .info-card li {
            font-size: 0.9rem;
            color: #555;
            line-height: 1.6;
        }

        .rate-badge {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            padding: 0.35rem 1.1rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            margin: 0.4rem 0;
            box-shadow: 0 3px 10px rgba(17,153,142,0.3);
        }

        .savings-progress-wrap {
            background: #e9ecef;
            border-radius: 50px;
            height: 10px;
            margin: 0.5rem 0;
            overflow: hidden;
        }

        .savings-progress-bar {
            background: linear-gradient(90deg, #11998e, #38ef7d);
            height: 100%;
            border-radius: 50px;
            transition: width 1.6s ease-in-out;
        }

        .savings-tip-box {
            background: linear-gradient(135deg, rgba(17,153,142,0.08), rgba(56,239,125,0.08));
            border: 1px solid rgba(17,153,142,0.25);
            border-radius: 10px;
            padding: 1rem 1.4rem;
            font-size: 0.9rem;
            color: #0f6b64;
        }

        .savings-tip-box i { color: #11998e; }

        @media (max-width: 768px) {
            .savings-value      { font-size: 1.3rem; }
            .savings-value.highlight { font-size: 1.5rem; }
            .savings-icon       { font-size: 1.8rem; }
            .savings-section-header { padding: 1.3rem; }
            .savings-section-header h2 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-user">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="fas fa-recycle"></i> PRERMI
            </span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUserContent" aria-controls="navbarUserContent" aria-expanded="false" aria-label="Toggle navigation" style="border-color: rgba(255,255,255,0.5);">
                <i class="fas fa-bars" style="color: white;"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarUserContent">
                <div class="ms-auto user-info flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['usuario'], 0, 1)); ?>
                        </div>
                        <div>
                            <div><strong><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></strong></div>
                            <small><?php echo htmlspecialchars($user['usuario']); ?></small>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-lg-0 mt-2">
                        <a href="Registrofac.php" class="btn btn-outline-light btn-sm" id="goOtherPageBtn" role="button">
                            <i class="fas fa-arrow-right"></i> Registro facial
                        </a>

                        <button type="button" class="facial-btn btn-sm" data-bs-toggle="modal" data-bs-target="#modalFotoFacial">
                            <i class="fas fa-camera"></i> Ver foto
                        </button>

                        <a href="../api/usuarios/logout.php" class="logout-btn btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container-main">
        <!-- Stats Grid -->
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-icon"><i class="fas fa-recycle"></i></div>
                <div class="stat-value"><?php echo $totalDepositos; ?></div>
                <div class="stat-label">Depósitos Totales</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon"><i class="fas fa-weight"></i></div>
                <div class="stat-value"><?php echo number_format($totalPeso, 1); ?> kg</div>
                <div class="stat-label">Peso Total Reciclado</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon"><i class="fas fa-bolt"></i></div>
                <div class="stat-value"><?php echo number_format($totalCredito, 2); ?> kWh</div>
                <div class="stat-label">Crédito Acumulado</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" style="color: #ff6b6b;"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?php echo $totalMultas; ?></div>
                <div class="stat-label">Sanciones Activas</div>
            </div>
        </div>

        <!-- Charts and Details -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h3><i class="fas fa-chart-bar"></i> Depósitos por Mes</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="depositChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h3><i class="fas fa-gavel"></i> Sanciones</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Descripción</th>
                                        <th>Peso (kg)</th>
                                        <th>Fecha</th>
                                        <th>Visto por admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sancionesList as $s): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['descripcion'] ?? 'N/A'); ?></td>
                                        <td><?php echo isset($s['peso']) ? number_format($s['peso'],3) : '-'; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($s['creado_en'])); ?></td>
                                        <td><?php echo (isset($s['seen_by_admin']) && $s['seen_by_admin']) ? 'Sí' : 'No'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($sancionesList)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4"><em>No hay sanciones registradas para tu usuario.</em></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================
             SECCIÓN: POTENCIAL DE AHORRO EN PESOS DOMINICANOS
        ================================================================ -->

        <!-- Encabezado de sección -->
        <div class="savings-section-header">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div style="font-size: 3rem; line-height: 1;">💰</div>
                <div>
                    <h2><i class="fas fa-piggy-bank me-2"></i>Potencial de Ahorro en tu Factura Eléctrica</h2>
                    <p>Visualiza cuánto dinero podrías reducir en tu factura de luz con la energía generada a partir de tus depósitos de biomasa.</p>
                </div>
            </div>
        </div>

        <!-- Cajas de estadísticas de ahorro -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="savings-stat-box">
                    <div class="savings-icon"><i class="fas fa-bolt"></i></div>
                    <div class="savings-value"><?php echo number_format($totalCredito, 3); ?> kWh</div>
                    <div class="savings-label">Energía Total Generada</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="savings-stat-box" style="border-top-color: #fd7e14;">
                    <div class="savings-icon" style="color:#fd7e14;"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="savings-value highlight">RD$ <?php echo number_format($ahorroTotalRD, 2); ?></div>
                    <div class="savings-label">Ahorro Total Acumulado</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="savings-stat-box" style="border-top-color: #764ba2;">
                    <div class="savings-icon" style="color:#764ba2;"><i class="fas fa-calendar-check"></i></div>
                    <div class="savings-value">RD$ <?php echo number_format($ahorroMesActualRD, 2); ?></div>
                    <div class="savings-label">Ahorro Este Mes</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="savings-stat-box" style="border-top-color: #38ef7d;">
                    <div class="savings-icon" style="color:#38ef7d;"><i class="fas fa-home"></i></div>
                    <div class="savings-value"><?php echo number_format($mesesEquivalentes, 3); ?></div>
                    <div class="savings-label">Meses de Luz Equivalentes</div>
                </div>
            </div>
        </div>

        <!-- Gráficas de ahorro -->
        <div class="row g-3 mb-4">
            <!-- Gráfica de barras: ahorro mensual en RD$ -->
            <div class="col-lg-7">
                <div class="card-custom h-100">
                    <div class="card-header-custom" style="background: linear-gradient(135deg, #0f9b8e 0%, #38ef7d 100%);">
                        <h3><i class="fas fa-chart-bar"></i> Ahorro Mensual en Pesos Dominicanos</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="savingsChart"></canvas>
                        </div>
                        <p class="text-muted text-center small mt-2">
                            <i class="fas fa-info-circle"></i>
                            Calculado con tarifa residencial de <strong>RD$<?php echo number_format($tarifaKwhRD, 2); ?>/kWh</strong>
                            &mdash; valor orientativo, puede variar según tu distribuidora (EDEESTE, EDENORTE, EDESUR).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Gráfica de dona: % del mes cubierto -->
            <div class="col-lg-5">
                <div class="card-custom h-100">
                    <div class="card-header-custom" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3><i class="fas fa-chart-pie"></i> Cobertura del Consumo Mensual</h3>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <div style="max-width: 240px; width: 100%; position: relative;">
                            <canvas id="energyDonutChart"></canvas>
                        </div>
                        <p class="text-muted text-center small mt-3 mb-0">
                            Comparado con el consumo promedio mensual de un hogar dominicano
                            (<strong><?php echo $consumoPromedioMensualKwh; ?> kWh/mes</strong>).
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas informativas -->
        <div class="row g-3 mb-4">
            <!-- Cómo se calcula -->
            <div class="col-md-4">
                <div class="info-card blue">
                    <h6><i class="fas fa-calculator" style="color:#667eea;"></i> ¿Cómo se calcula el ahorro?</h6>
                    <p>Cada kilogramo de biomasa depositada genera un crédito en kWh según el sistema PRERMI. Ese kWh se convierte en dinero usando la tarifa eléctrica:</p>
                    <div class="text-center my-2">
                        <span class="rate-badge" style="background: linear-gradient(135deg,#667eea,#764ba2);">RD$<?php echo number_format($tarifaKwhRD, 2); ?> / kWh</span>
                    </div>
                    <p class="mb-2"><strong>Fórmula:</strong></p>
                    <div class="savings-tip-box">
                        <code style="color:#0f6b64; font-size:0.88rem;">Ahorro = kWh generados &times; RD$<?php echo number_format($tarifaKwhRD, 2); ?></code>
                    </div>
                    <p class="mt-2 mb-0 text-muted" style="font-size:0.83rem;">Tu acumulado: <strong><?php echo number_format($totalCredito, 3); ?> kWh &times; <?php echo number_format($tarifaKwhRD, 2); ?> = RD$<?php echo number_format($ahorroTotalRD, 2); ?></strong></p>
                </div>
            </div>

            <!-- Equivalencias -->
            <div class="col-md-4">
                <div class="info-card orange">
                    <h6><i class="fas fa-lightbulb" style="color:#fd7e14;"></i> ¿Qué equivale tu energía?</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> <strong>1 kWh</strong> = ~6 horas de bombillo LED 20 W</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> <strong>5 kWh</strong> = 1 día de ventilador de techo (8 hrs)</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> <strong>10 kWh</strong> = 1 día de A/C 1 ton (8 hrs)</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> <strong>200 kWh</strong> = un mes completo de luz en casa</li>
                        <li class="pt-1 border-top">
                            <i class="fas fa-star text-warning me-1"></i>
                            <strong>Tú generaste:</strong> <?php echo number_format($totalCredito, 3); ?> kWh
                            &mdash; equivalente a
                            <strong><?php echo number_format($totalCredito * 5, 1); ?> horas</strong> de bombillo LED
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Impacto ambiental -->
            <div class="col-md-4">
                <div class="info-card">
                    <h6><i class="fas fa-leaf" style="color:#11998e;"></i> Impacto Ambiental</h6>
                    <p>Al generar energía renovable con biomasa, evitas emisiones de CO₂ que produciría la misma energía con combustibles fósiles:</p>
                    <div class="text-center my-2">
                        <div style="font-size:2rem; font-weight:700; color:#11998e;">
                            <?php echo number_format($totalCredito * 0.37, 3); ?> kg
                        </div>
                        <small class="text-muted">de CO₂ no emitido<br><em>(0.37 kg CO₂/kWh, factor promedio RD)</em></small>
                    </div>
                    <div class="savings-progress-wrap mt-2">
                        <div class="savings-progress-bar" id="co2ProgressBar"
                             data-width="<?php echo min(100, ($totalCredito / $consumoPromedioMensualKwh) * 100); ?>"
                             style="width: 0%;"></div>
                    </div>
                    <small class="text-muted">
                        <?php echo number_format(min(100, ($totalCredito / $consumoPromedioMensualKwh) * 100), 2); ?>%
                        hacia la meta de <?php echo $consumoPromedioMensualKwh; ?> kWh mensuales
                    </small>
                </div>
            </div>
        </div>

        <!-- Tip motivacional -->
        <div class="savings-tip-box mb-4 d-flex align-items-center gap-3">
            <i class="fas fa-rocket fa-2x"></i>
            <div>
                <strong>¿Sabías que…?</strong> Si depositas regularmente, podrías alcanzar hasta
                <strong>RD$<?php echo number_format($consumoPromedioMensualKwh * $tarifaKwhRD, 2); ?>/mes</strong>
                en reducción de factura — el equivalente a un mes de luz para un hogar dominicano promedio.
                ¡Sigue reciclando y maximiza tu ahorro!
            </div>
        </div>

        <!-- FIN SECCIÓN AHORRO -->

        <!-- Deposits History -->
        <div class="card-custom">
            <div class="card-header-custom">
                <h3><i class="fas fa-history"></i> Historial de Depósitos</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Contenedor</th>
                                <th>Ubicación</th>
                                <th>Peso (kg)</th>
                                <th>Metal Detectado</th>
                                <th>Crédito (kWh)</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($depositos as $dep): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dep['codigo_contenedor'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($dep['ubicacion'] ?? 'No especificada'); ?></td>
                                <td><?php echo number_format($dep['peso'], 2); ?> kg</td>
                                <td>
                                    <?php 
                                    if ($dep['metal_detectado']) {
                                        echo '<span class="badge-metal"><i class="fas fa-exclamation"></i> Detectado</span>';
                                    } else {
                                        echo '<span class="badge-ok"><i class="fas fa-check"></i> Normal</span>';
                                    }
                                    ?>
                                </td>
                                <td><strong><?php echo number_format($dep['credito_kwh'], 4); ?> kWh</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($dep['creado_en'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($depositos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <em>Aún no has realizado depósitos. ¡Comienza a reciclar!</em>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFotoFacial" tabindex="-1" aria-labelledby="modalFotoFacialLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title" id="modalFotoFacialLabel"><i class="fas fa-id-card"></i> Foto Facial Vinculada</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <?php if ($fotoFacialDisponible): ?>
                        <img class="facial-modal-img" src="<?php echo htmlspecialchars($fotoFacialRutaWeb); ?>?v=<?php echo time(); ?>" alt="Foto facial del usuario" style="max-height: 400px; border: 3px solid #667eea; border-radius: 10px;">
                    <?php else: ?>
                        <p class="mb-0 text-muted"><i class="fas fa-exclamation-triangle"></i> No se encontró una foto facial vinculada para esta cuenta.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer" style="justify-content: space-between;">
                    <?php if ($fotoFacialDisponible): ?>
                        <button type="button" class="btn btn-danger" onclick="eliminarFotoFacial()">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                        <div>
                            <button type="button" class="btn btn-primary" onclick="actualizarFotoFacial()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary w-100" onclick="registrarFotoFacial()">
                            <i class="fas fa-camera"></i> Registrar Foto Facial
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').then(function(reg){
                console.log('SW registered', reg.scope);
            }).catch(function(err){
                console.warn('SW registration failed', err);
            });
        }
    </script>

    <script>
        // Prepare chart data using monthly aggregates from PHP
        const monthly = <?php echo json_encode($monthly); ?>; // [{ym: '2025-11', cnt: 3, energy: '0.012'}, ...]

        // Build labels (month names) and two datasets: deposits count and energy (kWh)
        const labels = monthly.map(m => {
            const parts = m.ym.split('-');
            const date = new Date(parts[0], parts[1]-1, 1);
            return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
        });
        const depositCounts = monthly.map(m => parseInt(m.cnt));
        const energySums = monthly.map(m => parseFloat(m.energy));

        // Generate a color palette (one color per month)
        const colorPalette = labels.map((_, i) => {
            const hue = Math.floor((i * 47) % 360);
            return `hsl(${hue}deg 70% 55%)`;
        });

        // Grouped bar chart: deposits (left) and energy (right) — energy scaled visually
        const depositCtx = document.getElementById('depositChart').getContext('2d');
        new Chart(depositCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Depósitos',
                        data: depositCounts,
                        backgroundColor: colorPalette,
                        borderColor: colorPalette,
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Energía generada (kWh)',
                        data: energySums,
                        backgroundColor: colorPalette.map(c => c.replace('55%)','40%)')),
                        borderColor: colorPalette,
                        borderWidth: 1,
                        type: 'line',
                        fill: false,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { type: 'linear', position: 'left', beginAtZero: true, title: { display: true, text: 'Depósitos' } },
                    y1: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'kWh' } }
                }
            }
        });

        // =====================================================================
        // GRÁFICAS DE AHORRO EN PESOS DOMINICANOS
        // =====================================================================
        const tarifaKwhRD   = <?php echo json_encode($tarifaKwhRD); ?>;
        const consumoMensual = <?php echo json_encode($consumoPromedioMensualKwh); ?>;
        const totalEnergyGen = <?php echo json_encode($totalCredito); ?>;

        // --- Gráfica de barras: ahorro mensual en RD$ ---
        const savingsData   = monthly.map(m => parseFloat((parseFloat(m.energy) * tarifaKwhRD).toFixed(2)));
        const savingsColors = savingsData.map((_, i) => {
            const hue = Math.floor((i * 53 + 150) % 360);
            return `hsl(${hue}deg 62% 52%)`;
        });

        const savingsCtx = document.getElementById('savingsChart').getContext('2d');
        new Chart(savingsCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ahorro en RD$',
                    data: savingsData,
                    backgroundColor: savingsColors,
                    borderColor: savingsColors.map(c => c.replace('52%)', '38%)')),
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `RD$ ${ctx.parsed.y.toFixed(2)}`,
                            afterLabel: ctx => `(${(ctx.parsed.y / tarifaKwhRD).toFixed(4)} kWh)`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Pesos Dominicanos (RD$)' },
                        ticks: { callback: v => `RD$ ${v.toFixed(2)}` }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        // --- Gráfica de dona: % del consumo mensual cubierto ---
        const energyForDonut    = Math.min(totalEnergyGen, consumoMensual);
        const remainingForDonut = Math.max(0, consumoMensual - energyForDonut);
        const coveragePct       = consumoMensual > 0 ? Math.min(100, (totalEnergyGen / consumoMensual) * 100) : 0;

        const donutCtx = document.getElementById('energyDonutChart').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Energía Reciclada (kWh)', 'Restante para 1 mes (kWh)'],
                datasets: [{
                    data: [energyForDonut > 0 ? energyForDonut : 0, remainingForDonut],
                    backgroundColor: ['#11998e', '#e9ecef'],
                    borderColor: ['#0d7a71', '#dee2e6'],
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 12 }, padding: 14 }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.label}: ${parseFloat(ctx.parsed).toFixed(4)} kWh`
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw(chart) {
                    const { ctx: c, chartArea: { width, height, left, top } } = chart;
                    c.save();
                    const cx = left + width / 2;
                    const cy = top + height / 2;
                    c.font = 'bold 24px Segoe UI, sans-serif';
                    c.fillStyle = '#0f9b8e';
                    c.textAlign = 'center';
                    c.textBaseline = 'middle';
                    c.fillText(coveragePct.toFixed(1) + '%', cx, cy - 11);
                    c.font = '12px Segoe UI, sans-serif';
                    c.fillStyle = '#666';
                    c.fillText('del mes cubierto', cx, cy + 13);
                    c.restore();
                }
            }]
        });

        // Animar barra de progreso CO₂ al cargar
        window.addEventListener('load', () => {
            const bar = document.getElementById('co2ProgressBar');
            if (bar) {
                const target = parseFloat(bar.dataset.width) || 0;
                setTimeout(() => { bar.style.width = target + '%'; }, 300);
            }
        });

        // JavaScript adicional: si prefieres un <button> que haga la redirección via JS
        document.getElementById('goOtherPageBtn')?.addEventListener('click', function(e){
            // Si quieres prevenir navegación normal y controlar la redirección con JS,
            // descomenta la siguiente línea:
            // e.preventDefault();
            // location.href = 'otra_pagina.php';
            // Actualmente el enlace <a href="otra_pagina.php"> funcionará de forma normal.
        });

        // Funciones para gestión de foto facial
        function eliminarFotoFacial() {
            if(!confirm('¿Estás seguro de eliminar tu foto facial? Esta acción no se puede deshacer.')) {
                return;
            }
            
            fetch('eliminar_foto_facial.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('Foto facial eliminada correctamente');
                    location.reload();
                } else {
                    alert('Error al eliminar: ' + (data.error || 'Desconocido'));
                }
            })
            .catch(err => {
                alert('Error de conexión: ' + err.message);
            });
        }

        function actualizarFotoFacial() {
            window.location.href = 'Registrofac.php?actualizar=1';
        }

        function registrarFotoFacial() {
            window.location.href = 'Registrofac.php';
        }
    </script>
</body>
</html>
