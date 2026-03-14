<?php
/**
 * ahorro_electrico.php — Ahorro Eléctrico BIOMASA
 * PRERMI Admin — Monitoreo de ciclos de generación energética y ahorro RD$
 */
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: loginA.php"); exit; }
require_once __DIR__ . '/../../api/utils.php';

$pdo = getPDO();
$stA = $pdo->prepare("SELECT id,usuario,email,rol FROM usuarios_admin WHERE id=? LIMIT 1");
$stA->execute([$_SESSION['admin_id']]);
$admin = $stA->fetch(PDO::FETCH_ASSOC) ?: ['usuario'=>'Admin','email'=>'','rol'=>'admin'];

$TARIFA_RD = 14.00;
$flash = null;

$filtroPeriodo = $_GET['periodo'] ?? '30d';
$timeWhere = match($filtroPeriodo) {
    '24h' => "registrado_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
    '7d'  => "registrado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    '30d' => "registrado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    '90d' => "registrado_en >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
    default => "1=1"
};

// Asegurar que la tabla existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS energia_biomasa_ciclos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        energia_wh DECIMAL(10,4) NOT NULL DEFAULT 0,
        duracion_seg INT NOT NULL DEFAULT 0,
        temp_promedio DECIMAL(6,2) DEFAULT NULL,
        corriente_promedio DECIMAL(6,3) DEFAULT NULL,
        timestamp_inicio BIGINT DEFAULT NULL,
        registrado_en DATETIME DEFAULT NULL,
        potencia_media_w DECIMAL(8,3) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

$ciclos       = [];
$totalWh      = 0;
$totalRD      = 0;
$totalCiclos  = 0;
$avgTemp      = 0;
$avgPotencia  = 0;
$totalSegundos= 0;
$dLabels = $dKwh = $dPotencia = [];

try {
    // Datos de ciclos con filtro de periodo
    $ciclos = $pdo->query("SELECT id, energia_wh, duracion_seg, temp_promedio, corriente_promedio,
                                  timestamp_inicio, registrado_en, potencia_media_w
                           FROM energia_biomasa_ciclos
                           WHERE {$timeWhere}
                           ORDER BY registrado_en DESC
                           LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);

    // Si la tabla está vacía, cargar desde JSON como fallback
    if (empty($ciclos)) {
        $jsonPath = __DIR__ . '/../../api/energia_biomasa.json';
        if (file_exists($jsonPath)) {
            $jsonData = json_decode(file_get_contents($jsonPath), true);
            if (is_array($jsonData)) {
                foreach ($jsonData as $entry) {
                    $ciclos[] = [
                        'id'                 => null,
                        'energia_wh'         => $entry['energia_wh'] ?? 0,
                        'duracion_seg'        => $entry['duracion_seg'] ?? 0,
                        'temp_promedio'       => $entry['temp_promedio'] ?? null,
                        'corriente_promedio'  => $entry['corriente_promedio'] ?? null,
                        'timestamp_inicio'    => $entry['timestamp_inicio'] ?? null,
                        'registrado_en'       => $entry['registrado_en'] ?? null,
                        'potencia_media_w'    => $entry['potencia_media_w'] ?? null,
                    ];
                }
            }
        }
    }

    // KPIs globales (sin filtro de periodo, siempre totales históricos)
    $kpiRow = $pdo->query("SELECT
        COALESCE(SUM(energia_wh),0)       AS total_wh,
        COUNT(*)                           AS total_ciclos,
        COALESCE(AVG(temp_promedio),0)     AS avg_temp,
        COALESCE(AVG(potencia_media_w),0)  AS avg_potencia,
        COALESCE(SUM(duracion_seg),0)      AS total_seg
        FROM energia_biomasa_ciclos")->fetch(PDO::FETCH_ASSOC);

    // Si BD vacía, calcular desde JSON
    if ((int)($kpiRow['total_ciclos'] ?? 0) === 0 && !empty($ciclos)) {
        $totalWh       = array_sum(array_column($ciclos, 'energia_wh'));
        $totalCiclos   = count($ciclos);
        $temps         = array_filter(array_column($ciclos, 'temp_promedio'));
        $avgTemp       = $temps ? array_sum($temps) / count($temps) : 0;
        $pots          = array_filter(array_column($ciclos, 'potencia_media_w'));
        $avgPotencia   = $pots  ? array_sum($pots)  / count($pots)  : 0;
        $totalSegundos = array_sum(array_column($ciclos, 'duracion_seg'));
    } else {
        $totalWh       = floatval($kpiRow['total_wh']       ?? 0);
        $totalCiclos   = intval($kpiRow['total_ciclos']     ?? 0);
        $avgTemp       = floatval($kpiRow['avg_temp']       ?? 0);
        $avgPotencia   = floatval($kpiRow['avg_potencia']   ?? 0);
        $totalSegundos = intval($kpiRow['total_seg']        ?? 0);
    }

    $totalRD    = round($totalWh / 1000 * $TARIFA_RD, 2);
    $totalHoras = round($totalSegundos / 3600, 2);

    // Gráfica: kWh por día (últimos 14 días)
    $dayRows = $pdo->query("SELECT DATE(registrado_en) dia, SUM(energia_wh) kwh, AVG(potencia_media_w) pot
                            FROM energia_biomasa_ciclos
                            WHERE registrado_en >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                            GROUP BY dia ORDER BY dia")->fetchAll(PDO::FETCH_ASSOC);
    $dayMap = [];
    foreach ($dayRows as $r) $dayMap[$r['dia']] = ['kwh'=>floatval($r['kwh']), 'pot'=>floatval($r['pot'])];

    // Si sin datos de DB, agrupar desde JSON
    if (empty($dayMap) && !empty($ciclos)) {
        foreach ($ciclos as $c) {
            if (!$c['registrado_en']) continue;
            $dia = substr($c['registrado_en'], 0, 10);
            if (!isset($dayMap[$dia])) $dayMap[$dia] = ['kwh'=>0,'pot'=>0,'cnt'=>0];
            $dayMap[$dia]['kwh'] += floatval($c['energia_wh']);
            $dayMap[$dia]['pot'] += floatval($c['potencia_media_w'] ?? 0);
            $dayMap[$dia]['cnt']++;
        }
        foreach ($dayMap as $k => &$v) {
            if (($v['cnt'] ?? 0) > 0) $v['pot'] /= $v['cnt'];
        }
        ksort($dayMap);
    }

    for ($i = 13; $i >= 0; $i--) {
        $ts  = strtotime("-{$i} days");
        $key = date('Y-m-d', $ts);
        $dLabels[]   = date('d/m', $ts);
        $dKwh[]      = round(($dayMap[$key]['kwh'] ?? 0) / 1000, 4); // Wh → kWh
        $dPotencia[] = round($dayMap[$key]['pot'] ?? 0, 2);
    }

} catch (Exception $e) {
    $ciclos = [];
    $totalWh = $totalRD = $totalCiclos = $avgTemp = $avgPotencia = $totalHoras = 0;
    $dLabels = $dKwh = $dPotencia = [];
    $flash = ['type'=>'danger', 'msg'=>'Error al cargar datos: '.htmlspecialchars($e->getMessage())];
}

// Leer status.json para live panel
$statusPath = __DIR__ . '/../../api/status.json';
$liveData = ['temperatura'=>0,'corriente'=>0,'ventilador'=>0,'calentador'=>0,
             'energia_generada'=>0,'sistema_activo'=>0,'updated_at'=>'—'];
if (file_exists($statusPath)) {
    $raw = json_decode(file_get_contents($statusPath), true);
    if ($raw) $liveData = array_merge($liveData, $raw);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Ahorro Eléctrico BIOMASA — PRERMI Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/PRERMI/web/assets/css/theme.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>(function(){var t=localStorage.getItem('prermi_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f1f5f9;font-family:'Segoe UI',sans-serif;}
.navbar-admin{background:linear-gradient(135deg,#1e40af 0%,#6d28d9 100%);padding:.75rem 1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;box-shadow:0 4px 20px rgba(30,64,175,.4);}
.nav-brand{color:#fff;font-weight:800;font-size:1.25rem;text-decoration:none;white-space:nowrap;}
.nav-links{display:flex;gap:.25rem;flex-wrap:wrap;flex:1;}
.nav-link-item{color:rgba(255,255,255,.8);padding:.4rem .75rem;border-radius:6px;text-decoration:none;font-size:.88rem;font-weight:500;transition:all .2s;white-space:nowrap;}
.nav-link-item:hover,.nav-link-item.active{background:rgba(255,255,255,.2);color:#fff;}
.nav-right{display:flex;align-items:center;gap:.5rem;}
.nav-user{color:#fff;font-weight:600;font-size:.9rem;}
.btn-logout{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:.35rem .75rem;border-radius:6px;text-decoration:none;font-size:.85rem;}
.btn-logout:hover{background:#ef4444;border-color:#ef4444;color:#fff;}
.main{padding:1.5rem;}
.page-header{background:linear-gradient(135deg,#065f46,#10b981);border-radius:16px;padding:1.5rem 2rem;margin-bottom:1.5rem;color:#fff;}
.page-header h1{font-size:1.5rem;font-weight:800;margin:0;}
.page-header p{margin:.25rem 0 0;opacity:.8;font-size:.9rem;}
.kpi-block{background:linear-gradient(135deg,#065f46,#10b981);border-radius:14px;padding:1.25rem;text-align:center;color:#fff;}
.kpi-block .kpi-v{font-size:1.9rem;font-weight:900;line-height:1.1;}
.kpi-block .kpi-l{font-size:.75rem;opacity:.85;text-transform:uppercase;letter-spacing:1px;margin-top:.25rem;}
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:#fff;border-radius:12px;padding:1.1rem;box-shadow:0 2px 12px rgba(0,0,0,.07);text-align:center;border-top:4px solid #10b981;}
.sc-val{font-size:1.8rem;font-weight:800;color:#10b981;}
.sc-lbl{font-size:.82rem;color:#64748b;}
.filtro-bar{background:#fff;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;}
.panel{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:1.5rem;overflow:hidden;}
.panel-head{padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;font-weight:700;color:#1e293b;}
.chart-dark{background:#1e293b;border-radius:14px;padding:1.5rem;}
.chart-dark-title{color:#e2e8f0;font-weight:700;font-size:.95rem;margin-bottom:1rem;}
.table-wrap{overflow-x:auto;}
.table-wrap table thead{background:linear-gradient(135deg,#065f46,#10b981);color:#fff;}
.table-wrap table thead th{border:none;padding:.85rem;white-space:nowrap;}
.table-wrap table tbody td{padding:.75rem;vertical-align:middle;border-color:#f0f0f0;}
.table-wrap table tbody tr:hover{background:#f0fdf4;}

/* Live status panel */
.live-panel{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:1.5rem;overflow:hidden;}
.live-panel-head{padding:.85rem 1.25rem;background:linear-gradient(135deg,#064e3b,#059669);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:.5rem;}
.live-panel-head .live-title{font-weight:700;font-size:.95rem;}
.live-badge{display:inline-flex;align-items:center;gap:.35rem;font-size:.78rem;background:rgba(255,255,255,.2);padding:.2rem .6rem;border-radius:20px;}
.live-badge .pulse{width:8px;height:8px;background:#4ade80;border-radius:50%;animation:pulse 1.4s infinite;}
@keyframes pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(74,222,128,.5);}50%{opacity:.8;box-shadow:0 0 0 5px rgba(74,222,128,0);}}
.live-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;padding:1.25rem;}
.live-item{text-align:center;padding:.9rem;border-radius:10px;background:#f0fdf4;border:1.5px solid #d1fae5;}
.live-item.warn{background:#fef9c3;border-color:#fde68a;}
.live-item.off{background:#f8fafc;border-color:#e2e8f0;}
.live-item .l-val{font-size:1.5rem;font-weight:800;}
.live-item .l-lbl{font-size:.73rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:.15rem;}
.live-updated{font-size:.72rem;color:#94a3b8;padding:.5rem 1.25rem .75rem;text-align:right;}

/* Dark theme overrides */
[data-theme="dark"] body{background:#0f172a;}
[data-theme="dark"] .stat-card,[data-theme="dark"] .filtro-bar,[data-theme="dark"] .panel,[data-theme="dark"] .live-panel{background:#1e293b;color:#e2e8f0;}
[data-theme="dark"] .panel-head{color:#e2e8f0;border-bottom-color:#334155;}
[data-theme="dark"] .sc-lbl,[data-theme="dark"] .live-item .l-lbl{color:#94a3b8;}
[data-theme="dark"] .table-wrap table tbody td{border-color:#334155;color:#e2e8f0;}
[data-theme="dark"] .table-wrap table tbody tr:hover{background:#0f2820;}
[data-theme="dark"] .live-item{background:#172231;border-color:#334155;}
[data-theme="dark"] .live-item.warn{background:#1a1a05;border-color:#713f12;}
[data-theme="dark"] .live-updated{color:#475569;}
[data-theme="dark"] .filtro-bar .btn-outline-secondary{color:#94a3b8;border-color:#475569;}
</style>
</head>
<body>
<nav class="navbar-admin">
  <a class="nav-brand" href="dashboard.php"><img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="nav-logo-img"></a>
  <div class="nav-links">
    <a href="dashboard.php"        class="nav-link-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="monitoreo.php"        class="nav-link-item"><i class="fas fa-video"></i> Monitoreo</a>
    <a href="depositos.php"        class="nav-link-item"><i class="fas fa-box-open"></i> Depositos</a>
    <a href="sanciones.php"        class="nav-link-item"><i class="fas fa-exclamation-triangle"></i> Sanciones</a>
    <a href="administradores.php"  class="nav-link-item"><i class="fas fa-users-cog"></i> Administradores</a>
    <a href="biores.php"           class="nav-link-item"><i class="fas fa-leaf"></i> BIOMASA</a>
    <a href="ahorro_electrico.php" class="nav-link-item active"><i class="fas fa-bolt"></i> Ahorro</a>
  </div>
  <div class="nav-right">
    <button class="btn-theme" id="btnTheme" onclick="toggleTheme()" title="Cambiar tema"><i class="fas fa-moon"></i></button>
    <span class="nav-user"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($admin['usuario']); ?></span>
    <a href="../../api/admin/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
  </div>
</nav>

<div class="main">
  <?php if ($flash): ?>
  <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mb-3">
    <?php echo $flash['msg']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="page-header">
    <h1><i class="fas fa-bolt"></i> Ahorro Eléctrico — BIOMASA</h1>
    <p>Ciclos de generación energética, kWh producidos y ahorro estimado en RD$</p>
  </div>

  <!-- Filtro periodo -->
  <div class="filtro-bar">
    <strong style="color:#1e293b;font-size:.88rem;"><i class="fas fa-filter"></i> Periodo de ciclos:</strong>
    <?php foreach(['24h'=>'24h','7d'=>'7 días','30d'=>'30 días','90d'=>'90 días','all'=>'Todo el historial'] as $k=>$l): ?>
    <a href="?periodo=<?php echo $k; ?>" class="btn btn-sm <?php echo $filtroPeriodo===$k?'btn-success':'btn-outline-secondary'; ?>"><?php echo $l; ?></a>
    <?php endforeach; ?>
    <span class="ms-auto text-muted" style="font-size:.78rem;"><i class="fas fa-info-circle"></i> Los KPIs de totales son históricos</span>
  </div>

  <!-- Live sensor status -->
  <div class="live-panel">
    <div class="live-panel-head">
      <span class="live-title"><i class="fas fa-satellite-dish"></i> Estado en tiempo real — Sistema BIOMASA</span>
      <div class="d-flex align-items-center gap-2">
        <span class="live-badge" id="liveBadge">
          <span class="pulse"></span>
          <span id="liveStatus"><?php echo $liveData['sistema_activo'] ? 'ACTIVO' : 'INACTIVO'; ?></span>
        </span>
        <button class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:.78rem;" onclick="refrescarLive()">
          <i class="fas fa-sync-alt" id="refreshIcon"></i> Actualizar
        </button>
      </div>
    </div>
    <div class="live-grid" id="liveGrid">
      <div class="live-item <?php echo floatval($liveData['temperatura'])>65?'warn':''; ?>">
        <div class="l-val" id="liveTemp" style="color:#ef4444;"><?php echo number_format(floatval($liveData['temperatura']),1); ?>°C</div>
        <div class="l-lbl"><i class="fas fa-thermometer-half"></i> Temperatura</div>
      </div>
      <div class="live-item">
        <div class="l-val" id="liveCorriente" style="color:#3b82f6;"><?php echo number_format(floatval($liveData['corriente']),2); ?> A</div>
        <div class="l-lbl"><i class="fas fa-tachometer-alt"></i> Corriente</div>
      </div>
      <div class="live-item <?php echo !$liveData['ventilador']?'off':''; ?>">
        <div class="l-val" id="liveVentilador" style="color:<?php echo $liveData['ventilador']?'#10b981':'#94a3b8'; ?>;">
          <?php echo $liveData['ventilador'] ? '<i class="fas fa-fan"></i> ON' : '<i class="fas fa-fan"></i> OFF'; ?>
        </div>
        <div class="l-lbl">Ventilador</div>
      </div>
      <div class="live-item <?php echo !$liveData['calentador']?'off':''; ?>">
        <div class="l-val" id="liveCalentador" style="color:<?php echo $liveData['calentador']?'#f97316':'#94a3b8'; ?>;">
          <?php echo $liveData['calentador'] ? '<i class="fas fa-fire"></i> ON' : '<i class="fas fa-fire"></i> OFF'; ?>
        </div>
        <div class="l-lbl">Calentador</div>
      </div>
      <div class="live-item">
        <div class="l-val" id="liveEnergia" style="color:#10b981;"><?php echo number_format(floatval($liveData['energia_generada']),2); ?> Wh</div>
        <div class="l-lbl"><i class="fas fa-bolt"></i> Energía generada</div>
      </div>
    </div>
    <div class="live-updated" id="liveUpdated">Última actualización: <?php echo htmlspecialchars($liveData['updated_at']); ?></div>
  </div>

  <!-- KPIs históricos -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
      <div class="kpi-block">
        <div class="kpi-v"><?php echo number_format($totalWh/1000,4); ?></div>
        <div class="kpi-l"><i class="fas fa-bolt"></i> kWh Total</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-block" style="background:linear-gradient(135deg,#5b21b6,#7c3aed);">
        <div class="kpi-v">RD$ <?php echo number_format($totalRD,2); ?></div>
        <div class="kpi-l"><i class="fas fa-dollar-sign"></i> Ahorro estimado</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-block" style="background:linear-gradient(135deg,#0e7490,#06b6d4);">
        <div class="kpi-v"><?php echo $totalCiclos; ?></div>
        <div class="kpi-l"><i class="fas fa-sync"></i> Ciclos totales</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-block" style="background:linear-gradient(135deg,#9a3412,#f97316);">
        <div class="kpi-v"><?php echo number_format($avgTemp,1); ?>°</div>
        <div class="kpi-l"><i class="fas fa-thermometer-half"></i> Temp. promedio</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-block" style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);">
        <div class="kpi-v"><?php echo number_format($avgPotencia,1); ?> W</div>
        <div class="kpi-l"><i class="fas fa-tachometer-alt"></i> Potencia media</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-block" style="background:linear-gradient(135deg,#134e4a,#0d9488);">
        <div class="kpi-v"><?php echo number_format($totalHoras,1); ?> h</div>
        <div class="kpi-l"><i class="fas fa-clock"></i> Tiempo activo</div>
      </div>
    </div>
  </div>

  <!-- Tarifa referencia -->
  <div class="stat-row mb-3">
    <div class="stat-card">
      <div class="sc-val" style="color:#10b981;"><?php echo number_format($totalWh,2); ?> Wh</div>
      <div class="sc-lbl">Total Wh generados</div>
    </div>
    <div class="stat-card" style="border-top-color:#7c3aed;">
      <div class="sc-val" style="color:#7c3aed;"><?php echo number_format($totalWh/1000,4); ?> kWh</div>
      <div class="sc-lbl">Total kWh generados</div>
    </div>
    <div class="stat-card" style="border-top-color:#f97316;">
      <div class="sc-val" style="color:#f97316;"><?php echo $totalCiclos; ?></div>
      <div class="sc-lbl">Ciclos en periodo</div>
    </div>
    <div class="stat-card" style="border-top-color:#2563eb;">
      <div class="sc-val" style="color:#2563eb;">RD$ <?php echo number_format($TARIFA_RD,2); ?>/kWh</div>
      <div class="sc-lbl">Tarifa eléctrica referencia</div>
    </div>
    <div class="stat-card" style="border-top-color:#0d9488;">
      <div class="sc-val" style="color:#0d9488;"><?php echo round($totalSegundos/60,0); ?> min</div>
      <div class="sc-lbl">Minutos activos totales</div>
    </div>
  </div>

  <!-- Gráficas -->
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="chart-dark">
        <div class="chart-dark-title"><i class="fas fa-chart-line" style="color:#10b981;"></i> kWh generados — últimos 14 días</div>
        <canvas id="chartKwh" height="130"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="chart-dark" style="height:100%;">
        <div class="chart-dark-title"><i class="fas fa-tachometer-alt" style="color:#06b6d4;"></i> Potencia media por día (W)</div>
        <canvas id="chartPotencia" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Tabla de ciclos -->
  <div class="panel">
    <div class="panel-head">
      <i class="fas fa-table" style="color:#065f46;"></i>
      Historial de ciclos de generación
      <span class="badge bg-success ms-auto"><?php echo count($ciclos); ?> registros</span>
    </div>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha/Hora</th>
            <th>Energía (Wh)</th>
            <th>kWh</th>
            <th>Ahorro RD$</th>
            <th>Duración</th>
            <th>Temp. Prom. (°C)</th>
            <th>Potencia Media (W)</th>
            <th>Corriente (A)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ciclos)): ?>
          <tr><td colspan="9" class="text-center py-5 text-muted">
            <i class="fas fa-leaf fa-2x mb-2 d-block" style="color:#10b981;opacity:.5;"></i>
            No hay ciclos registrados para este periodo
          </td></tr>
          <?php else: ?>
          <?php foreach ($ciclos as $idx => $c):
            $fecha   = $c['registrado_en'] ? date('d/m/Y H:i', strtotime($c['registrado_en'])) : '—';
            $energWh = floatval($c['energia_wh'] ?? 0);
            $kwh     = round($energWh / 1000, 6);
            $rdCiclo = round($kwh * $TARIFA_RD, 4);
            $dur     = intval($c['duracion_seg'] ?? 0);
            $durStr  = $dur>=60 ? floor($dur/60).'m '.($dur%60).'s' : $dur.'s';
            $tmpProm = $c['temp_promedio'] !== null ? number_format(floatval($c['temp_promedio']),2) : '—';
            $potMed  = $c['potencia_media_w'] !== null ? number_format(floatval($c['potencia_media_w']),3) : '—';
            $corr    = $c['corriente_promedio'] !== null ? number_format(floatval($c['corriente_promedio']),3) : '—';
          ?>
          <tr>
            <td><strong><?php echo $c['id'] ? '#'.intval($c['id']) : '#'.($idx+1); ?></strong></td>
            <td><?php echo htmlspecialchars($fecha); ?></td>
            <td><strong style="color:#10b981;"><?php echo number_format($energWh,4); ?></strong></td>
            <td style="color:#059669;"><?php echo number_format($kwh,6); ?></td>
            <td><strong style="color:#7c3aed;">RD$ <?php echo number_format($rdCiclo,4); ?></strong></td>
            <td><span class="badge bg-light text-dark border"><?php echo $durStr; ?></span></td>
            <td><?php echo $tmpProm !== '—' ? '<span style="color:'.($c['temp_promedio']>65?'#ef4444':'#f97316').';">'.htmlspecialchars($tmpProm).'</span>' : '—'; ?></td>
            <td><?php echo $potMed !== '—' ? '<span style="color:#3b82f6;">'.htmlspecialchars($potMed).'</span>' : '—'; ?></td>
            <td><?php echo htmlspecialchars($corr); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/PRERMI/web/assets/js/theme.js"></script>
<script>
// Chart data from PHP
const dLabels   = <?php echo json_encode($dLabels); ?>;
const dKwh      = <?php echo json_encode($dKwh); ?>;
const dPotencia = <?php echo json_encode($dPotencia); ?>;
const TARIFA    = <?php echo $TARIFA_RD; ?>;

// kWh por día
const c1 = document.getElementById('chartKwh');
if (c1) {
  new Chart(c1, {
    type: 'line',
    data: {
      labels: dLabels,
      datasets: [{
        label: 'kWh generados',
        data: dKwh,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,.15)',
        pointBackgroundColor: '#10b981',
        pointRadius: 4,
        borderWidth: 2.5,
        fill: true,
        tension: .35
      }]
    },
    options: {
      responsive: true,
      interaction: {mode:'index', intersect:false},
      plugins: {
        legend: { labels: { color:'#94a3b8' } },
        tooltip: {
          callbacks: {
            afterLabel: ctx => `RD$ ${(ctx.parsed.y * TARIFA).toFixed(4)}`
          }
        }
      },
      scales: {
        x: { ticks: { color:'#94a3b8' }, grid: { color:'rgba(148,163,184,.1)' } },
        y: { ticks: { color:'#10b981', callback: v => v.toFixed(4)+' kWh' }, grid: { color:'rgba(148,163,184,.1)' } }
      }
    }
  });
}

// Potencia media por día
const c2 = document.getElementById('chartPotencia');
if (c2) {
  new Chart(c2, {
    type: 'bar',
    data: {
      labels: dLabels,
      datasets: [{
        label: 'Potencia (W)',
        data: dPotencia,
        backgroundColor: dPotencia.map((_,i) => `hsla(${200+i*10},75%,55%,.75)`),
        borderColor: dPotencia.map((_,i) => `hsl(${200+i*10},75%,55%)`),
        borderWidth: 1.5,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color:'#94a3b8', font:{size:9} }, grid: { color:'rgba(148,163,184,.1)' } },
        y: { ticks: { color:'#06b6d4', callback: v => v.toFixed(0)+'W' }, grid: { color:'rgba(148,163,184,.1)' } }
      }
    }
  });
}

// Live sensor refresh
let liveInterval = null;

function refrescarLive() {
  const icon = document.getElementById('refreshIcon');
  if (icon) { icon.style.animation = 'spin 1s linear infinite'; }

  fetch('/PRERMI/api/status.json?_t=' + Date.now())
    .then(r => r.json())
    .then(d => {
      const setText = (id, val) => { const el=document.getElementById(id); if(el) el.innerHTML=val; };
      setText('liveTemp',       (parseFloat(d.temperatura)||0).toFixed(1) + '°C');
      setText('liveCorriente',  (parseFloat(d.corriente)||0).toFixed(2) + ' A');
      setText('liveVentilador', d.ventilador
        ? '<i class="fas fa-fan"></i> ON'
        : '<i class="fas fa-fan"></i> OFF');
      setText('liveCalentador', d.calentador
        ? '<i class="fas fa-fire"></i> ON'
        : '<i class="fas fa-fire"></i> OFF');
      setText('liveEnergia',    (parseFloat(d.energia_generada)||0).toFixed(2) + ' Wh');
      const statusEl = document.getElementById('liveStatus');
      if (statusEl) statusEl.textContent = d.sistema_activo ? 'ACTIVO' : 'INACTIVO';
      const updEl = document.getElementById('liveUpdated');
      if (updEl) updEl.textContent = 'Última actualización: ' + (d.updated_at || new Date().toISOString());
    })
    .catch(() => {})
    .finally(() => { if (icon) icon.style.animation = ''; });
}

// Auto-refresh every 8 seconds
liveInterval = setInterval(refrescarLive, 8000);

// CSS spin keyframe
const spinStyle = document.createElement('style');
spinStyle.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
document.head.appendChild(spinStyle);
</script>
</body>
</html>
