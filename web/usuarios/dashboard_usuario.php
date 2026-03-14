<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

$usuarioId = (int)$_SESSION['usuario_id'];
$fotoFacialPath = __DIR__ . '/../../uploads/rostros/face_' . $usuarioId . '.jpg';
$fotoFacialWeb  = '../../uploads/rostros/face_' . $usuarioId . '.jpg';
$fotoFacialExiste = file_exists($fotoFacialPath);

// ---- CALCULAR AHORROS DE ELECTRICIDAD ----
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../api/utils.php';

$TARIFA_RD_KWH = 14.00; // Tarifa EDENORTE/EDESUR Rep. Dom. aprox.
$monthLabels  = [];
$monthSavings = []; // en RD$
$monthKwh     = []; // en kWh
$totalKwh     = 0;
$totalAhorroRD = 0;
$mesActualKwh  = 0;
$mesActualRD   = 0;

try {
    $pdo = getPDO();

    // Ahorros de los últimos 6 meses
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(creado_en, '%Y-%m') AS mes,
            DATE_FORMAT(creado_en, '%b %Y')   AS mes_label,
            SUM(COALESCE(credito_kwh, 0))      AS kwh_total
        FROM depositos
        WHERE id_usuario = ?
          AND creado_en >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY mes, mes_label
        ORDER BY mes ASC
    ");
    $stmt->execute([$usuarioId]);
    $rows = $stmt->fetchAll();

    // Rellenar los 6 meses aunque no haya datos
    for ($i = 5; $i >= 0; $i--) {
        $ts    = strtotime("-$i months");
        $key   = date('Y-m', $ts);
        $label = date('M Y', $ts);
        $monthLabels[]  = $label;
        $monthKwh[$key] = 0;
    }
    foreach ($rows as $r) {
        if (isset($monthKwh[$r['mes']])) {
            $monthKwh[$r['mes']] = (float)$r['kwh_total'];
        }
    }

    foreach ($monthKwh as $mk => $kwh) {
        $rd = round($kwh * $TARIFA_RD_KWH, 2);
        $monthSavings[] = $rd;
        $totalKwh      += $kwh;
        $totalAhorroRD += $rd;
    }

    // Mes actual
    $currentMonth = date('Y-m');
    $mesActualKwh = isset($monthKwh[$currentMonth]) ? $monthKwh[$currentMonth] : 0;
    $mesActualRD  = round($mesActualKwh * $TARIFA_RD_KWH, 2);

} catch (Throwable $e) {
    // Si falla la consulta de ahorros, mostrar ceros
}

$monthLabelsJson  = json_encode(array_values($monthLabels));
$monthSavingsJson = json_encode(array_values($monthSavings));
$monthKwhJson     = json_encode(array_values(array_values($monthKwh)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Usuario PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>(function(){var t=localStorage.getItem('prermi_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* ===== LAYOUT DASHBOARD ===== */
        .dashboard-container {
            width: 90%;
            margin: 30px auto;
        }

        .welcome {
            text-align: center;
            font-size: 26px;
            margin-bottom: 20px;
            color: white;
        }

        .card-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: center;
        }

        .card {
            background: white;
            color: #004466;
            width: 250px;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0px 5px 15px #00000040;
            transition: 0.3s;
            text-decoration: none;
            display: block;
        }

        .card:hover {
            transform: scale(1.08);
            background: #e1ffff;
        }

        /* ===== AHORRO ELECTRICO ===== */
        .savings-section {
            margin: 40px auto;
            width: 95%;
            max-width: 900px;
        }

        .savings-title {
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 24px;
            letter-spacing: 1px;
        }

        .savings-title span {
            background: linear-gradient(90deg, #06b6d4, #10b981, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .savings-kpi-row {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .kpi-card {
            flex: 1;
            min-width: 180px;
            max-width: 220px;
            border-radius: 16px;
            padding: 22px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .kpi-card.cyan  { background: linear-gradient(135deg,#0e7490,#06b6d4); box-shadow: 0 4px 20px #06b6d440; }
        .kpi-card.green { background: linear-gradient(135deg,#065f46,#10b981); box-shadow: 0 4px 20px #10b98140; }
        .kpi-card.purple{ background: linear-gradient(135deg,#5b21b6,#7c3aed); box-shadow: 0 4px 20px #7c3aed40; }

        .kpi-card .kpi-icon { font-size: 28px; margin-bottom: 6px; }
        .kpi-card .kpi-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.75); margin-bottom: 6px; }
        .kpi-card .kpi-value { font-size: 26px; font-weight: 800; color: #fff; line-height: 1; }
        .kpi-card .kpi-unit  { font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 4px; }

        .savings-chart-box {
            background: rgba(15,23,42,0.75);
            border-radius: 18px;
            padding: 28px 24px 20px;
            border: 1px solid rgba(6,182,212,0.25);
            box-shadow: 0 8px 32px rgba(0,0,0,0.35);
            backdrop-filter: blur(8px);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .chart-header h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #e2e8f0;
        }

        .chart-header .chart-badge {
            background: linear-gradient(90deg,#06b6d4,#10b981);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        .savings-note {
            text-align: center;
            font-size: 12px;
            color: rgba(255,255,255,0.55);
            margin-top: 14px;
        }

        /* ===== MODAL FACIAL ===== */
        .btn-facial {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 1000;
            background: #004466;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 12px #00000040;
        }

        .btn-facial:hover { background: #006b8a; }

        .facial-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 1100;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .facial-modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 420px;
            width: 100%;
            padding: 18px;
            text-align: center;
            box-shadow: 0 8px 24px #00000055;
        }

        .facial-modal-content h3 { margin: 0 0 12px; color: #004466; }
        .facial-modal-content img {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #d7eef6;
        }

        .facial-modal-close {
            margin-top: 14px;
            border: none;
            border-radius: 8px;
            background: #cc0000;
            color: #fff;
            padding: 8px 16px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<button class="btn-facial" onclick="abrirModalFacial()">Ver Foto Facial</button>

<header>
    <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="header-logo">
    <p>Dashboard de Usuario</p>
    <button id="btnTheme" class="btn-theme-user" onclick="toggleTheme()" title="Cambiar tema"><i class="fas fa-moon"></i></button>
</header>

<div class="dashboard-container">

    <div class="welcome">
        Bienvenido, <b><?php echo $_SESSION['usuario_nombre']; ?></b>
    </div>

    <div class="card-grid">

        <a href="tarjeta_usuario.php" class="card">
            Tarjeta Digital
        </a>

        <a href="depositos_usuario.php" class="card">
            Mis Depósitos
        </a>

        <a href="sanciones_usuario.php" class="card">
            Mis Sanciones
        </a>

        <a href="perfil_usuario.php" class="card">
            Mi Perfil
        </a>

        <a href="logout_usuario.php" class="card" style="background:#cc0000; color:white;">
            Cerrar Sesión
        </a>

    </div>

</div>

<!-- ===== AHORRO EN ELECTRICIDAD ===== -->
<section class="savings-section">
    <p class="savings-title">⚡ <span>Ahorro en Electricidad — Pesos Dominicanos</span></p>

    <!-- KPIs -->
    <div class="savings-kpi-row">
        <div class="kpi-card cyan">
            <div class="kpi-icon">💡</div>
            <div class="kpi-label">Este mes</div>
            <div class="kpi-value">RD$ <?php echo number_format($mesActualRD, 2); ?></div>
            <div class="kpi-unit"><?php echo number_format($mesActualKwh, 3); ?> kWh generados</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-icon">🌿</div>
            <div class="kpi-label">Ahorro total acumulado</div>
            <div class="kpi-value">RD$ <?php echo number_format($totalAhorroRD, 2); ?></div>
            <div class="kpi-unit"><?php echo number_format($totalKwh, 3); ?> kWh totales</div>
        </div>
        <div class="kpi-card purple">
            <div class="kpi-icon">📊</div>
            <div class="kpi-label">Tarifa de referencia</div>
            <div class="kpi-value">RD$ <?php echo number_format($TARIFA_RD_KWH, 2); ?></div>
            <div class="kpi-unit">por kWh (EDENORTE/EDESUR)</div>
        </div>
    </div>

    <!-- Gráfica -->
    <div class="savings-chart-box">
        <div class="chart-header">
            <h3>📈 Reducción mensual de costo eléctrico (últimos 6 meses)</h3>
            <span class="chart-badge">RD$ / kWh</span>
        </div>
        <canvas id="savingsChart" height="100"></canvas>
        <p class="savings-note">
            Cálculo basado en tus depósitos registrados × tarifa eléctrica de referencia.
            Cada kg depositado genera 0.5 kWh de crédito energético.
        </p>
    </div>
</section>

<div id="facialModal" class="facial-modal" onclick="cerrarModalFacial(event)">
    <div class="facial-modal-content">
        <h3>Foto Facial Vinculada</h3>
        <?php if ($fotoFacialExiste): ?>
            <img src="<?php echo htmlspecialchars($fotoFacialWeb); ?>" alt="Foto facial del usuario">
        <?php else: ?>
            <p>No se encontro una foto facial vinculada para esta cuenta.</p>
        <?php endif; ?>
        <button class="facial-modal-close" onclick="cerrarModalFacial()">Cerrar</button>
    </div>
</div>

<script>
function abrirModalFacial() {
    document.getElementById('facialModal').style.display = 'flex';
}

function cerrarModalFacial(event) {
    if (!event || event.target.id === 'facialModal') {
        document.getElementById('facialModal').style.display = 'none';
    }
}

// ===== GRÁFICA DE AHORRO ELÉCTRICO =====
(function() {
    const labels  = <?php echo $monthLabelsJson; ?>;
    const savings = <?php echo $monthSavingsJson; ?>;
    const kwhs    = <?php echo $monthKwhJson; ?>;

    const ctx = document.getElementById('savingsChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ahorro RD$',
                    data: savings,
                    backgroundColor: savings.map((v, i) =>
                        `hsla(${180 + i * 30},85%,55%,0.75)`
                    ),
                    borderColor: savings.map((v, i) =>
                        `hsl(${180 + i * 30},85%,60%)`
                    ),
                    borderWidth: 2,
                    borderRadius: 8,
                    yAxisID: 'yRD'
                },
                {
                    label: 'kWh generados',
                    data: kwhs,
                    type: 'line',
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.12)',
                    pointBackgroundColor: '#10b981',
                    pointRadius: 5,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    yAxisID: 'yKwh'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    labels: { color: '#cbd5e1', font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            if (ctx.dataset.yAxisID === 'yRD')
                                return ` Ahorro: RD$ ${ctx.parsed.y.toFixed(2)}`;
                            return ` kWh: ${ctx.parsed.y.toFixed(4)}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#94a3b8' },
                    grid:  { color: 'rgba(255,255,255,0.06)' }
                },
                yRD: {
                    position: 'left',
                    ticks: {
                        color: '#06b6d4',
                        callback: v => 'RD$ ' + v.toFixed(0)
                    },
                    grid: { color: 'rgba(6,182,212,0.1)' },
                    title: { display: true, text: 'Pesos Dominicanos (RD$)', color: '#06b6d4', font: { size: 11 } }
                },
                yKwh: {
                    position: 'right',
                    ticks: {
                        color: '#10b981',
                        callback: v => v.toFixed(3) + ' kWh'
                    },
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'kWh generados', color: '#10b981', font: { size: 11 } }
                }
            }
        }
    });
})();
</script>
<script src="/PRERMI/web/assets/js/theme.js"></script>

</body>
</html>
