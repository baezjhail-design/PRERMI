<?php
/**
 * depositos.php — Depositos de Biomasa / Energia
 * PRERMI Admin — Pagina independiente de depositos
 */
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: loginA.php"); exit; }
require_once __DIR__ . '/../../api/utils.php';

$pdo = getPDO();
$stA = $pdo->prepare("SELECT id,usuario,email,rol FROM usuarios_admin WHERE id=? LIMIT 1");
$stA->execute([$_SESSION['admin_id']]);
$admin = $stA->fetch(PDO::FETCH_ASSOC) ?: ['usuario'=>'Admin','email'=>'','rol'=>'admin'];
$flash = null;
$TARIFA_RD = 14.00;

try {
    $filtroPeriodo = $_GET['periodo'] ?? '30d';
    $timeWhere = match($filtroPeriodo) {
        '24h' => "COALESCE(d.creado_en,d.fecha_hora) >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
        '7d'  => "COALESCE(d.creado_en,d.fecha_hora) >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        '30d' => "COALESCE(d.creado_en,d.fecha_hora) >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        '90d' => "COALESCE(d.creado_en,d.fecha_hora) >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
        default => "1=1"
    };

    $depositos = $pdo->query("SELECT d.id,d.id_usuario,d.id_contenedor,d.peso,d.tipo_residuo,d.credito_kwh,d.metal_detectado,d.token_usado,d.fecha_hora,d.creado_en,
                                     u.usuario,u.nombre,u.apellido,c.codigo_contenedor,c.ubicacion,c.latitud,c.longitud
                              FROM depositos d
                              LEFT JOIN usuarios u ON u.id=d.id_usuario
                              LEFT JOIN contenedores_registrados c ON c.id=d.id_contenedor
                              WHERE {$timeWhere}
                              ORDER BY COALESCE(d.creado_en,d.fecha_hora) DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);

    $contenedores = $pdo->query("SELECT id,codigo_contenedor,ubicacion,tipo_contenedor,estado,latitud,longitud,creado_en,actualizado_en FROM contenedores_registrados ORDER BY actualizado_en DESC")->fetchAll(PDO::FETCH_ASSOC);

    // KPIs
    $totalDep   = count($depositos);
    $totalPeso  = array_sum(array_column($depositos,'peso'));
    $totalKwh   = array_sum(array_column($depositos,'credito_kwh'));
    $totalRD    = round($totalKwh * $TARIFA_RD, 2);
    $conMetal   = count(array_filter($depositos, fn($d)=>intval($d['metal_detectado'])===1));
    $totalCont  = count($contenedores);

    // Grafica: kWh por mes (6 meses)
    $savRows = $pdo->query("SELECT DATE_FORMAT(COALESCE(creado_en,fecha_hora),'%Y-%m') mes, SUM(COALESCE(credito_kwh,0)) kwh, COUNT(*) cnt FROM depositos WHERE COALESCE(creado_en,fecha_hora)>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes")->fetchAll(PDO::FETCH_ASSOC);
    $savMap = []; foreach($savRows as $r) $savMap[$r['mes']]=['kwh'=>floatval($r['kwh']),'cnt'=>intval($r['cnt'])];
    $mLabels=$mKwh=$mRD=$mCnt=[];
    for($i=5;$i>=0;$i--){ $ts=strtotime("-{$i} months"); $k=date('Y-m',$ts); $mLabels[]=date('M Y',$ts); $mKwh[]=$savMap[$k]['kwh']??0; $mRD[]=round(($savMap[$k]['kwh']??0)*$TARIFA_RD,2); $mCnt[]=$savMap[$k]['cnt']??0; }

    // Top usuarios
    $topUsers = $pdo->query("SELECT u.nombre,u.apellido,u.usuario,SUM(COALESCE(d.credito_kwh,0)) kwh,COUNT(d.id) cnt FROM depositos d LEFT JOIN usuarios u ON u.id=d.id_usuario GROUP BY d.id_usuario ORDER BY kwh DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // Por tipo de residuo
    $tipoResiduo = []; foreach($depositos as $d){ $t=$d['tipo_residuo']??'No especificado'; $tipoResiduo[$t]=($tipoResiduo[$t]??0)+1; }

} catch(Exception $e) {
    $depositos=$contenedores=$topUsers=[]; $totalDep=$totalPeso=$totalKwh=$totalRD=$conMetal=$totalCont=0;
    $mLabels=$mKwh=$mRD=$mCnt=$tipoResiduo=[];
    $flash=['type'=>'danger','msg'=>'Error: '.$e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Depositos y Energia — PRERMI Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
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
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:#fff;border-radius:12px;padding:1.1rem;box-shadow:0 2px 12px rgba(0,0,0,.07);text-align:center;border-top:4px solid #10b981;}
.sc-val{font-size:1.8rem;font-weight:800;color:#10b981;}
.sc-lbl{font-size:.82rem;color:#64748b;}
.sc-sub{font-size:.72rem;color:#94a3b8;margin-top:.2rem;}
.filtro-bar{background:#fff;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;}
.panel{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:1.5rem;overflow:hidden;}
.panel-head{padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;font-weight:700;color:#1e293b;}
.map-box{height:380px;}
.chart-dark{background:#1e293b;border-radius:14px;padding:1.5rem;}
.chart-dark-title{color:#e2e8f0;font-weight:700;font-size:.95rem;margin-bottom:1rem;}
.table-wrap{overflow-x:auto;}
.table-wrap table thead{background:linear-gradient(135deg,#065f46,#10b981);color:#fff;}
.table-wrap table thead th{border:none;padding:.85rem;white-space:nowrap;}
.table-wrap table tbody td{padding:.75rem;vertical-align:middle;border-color:#f0f0f0;}
.table-wrap table tbody tr:hover{background:#f9fffe;}
.cont-card{background:#fff;border-radius:12px;padding:1rem;border:1.5px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.cont-card .status-ok{color:#10b981;font-weight:700;}
.cont-card .status-off{color:#ef4444;font-weight:700;}
.kpi-block{background:linear-gradient(135deg,#065f46,#10b981);border-radius:14px;padding:1.25rem;text-align:center;color:#fff;}
.kpi-block .kpi-v{font-size:2rem;font-weight:900;}
.kpi-block .kpi-l{font-size:.75rem;opacity:.85;text-transform:uppercase;letter-spacing:1px;}
</style>
</head>
<body>
<nav class="navbar-admin">
  <a class="nav-brand" href="dashboard.php"><img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="nav-logo-img"></a>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="monitoreo.php" class="nav-link-item"><i class="fas fa-video"></i> Monitoreo</a>
    <a href="depositos.php" class="nav-link-item active"><i class="fas fa-box-open"></i> Depositos</a>
    <a href="sanciones.php" class="nav-link-item"><i class="fas fa-exclamation-triangle"></i> Sanciones</a>
    <a href="administradores.php" class="nav-link-item"><i class="fas fa-users-cog"></i> Administradores</a>
    <a href="biores.php" class="nav-link-item"><i class="fas fa-leaf"></i> BIOMASA</a>
    <a href="ahorro_electrico.php" class="nav-link-item"><i class="fas fa-bolt"></i> Ahorro</a>
  </div>
  <div class="nav-right">
    <button class="btn-theme" id="btnTheme" onclick="toggleTheme()" title="Cambiar tema"><i class="fas fa-moon"></i></button>
    <span class="nav-user"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($admin['usuario']); ?></span>
    <a href="../../api/admin/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
  </div>
</nav>
<div class="main">
  <?php if($flash): ?>
  <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mb-3">
    <?php echo $flash['msg']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <div class="page-header">
    <h1><i class="fas fa-box-open"></i> Depositos y Generacion Energetica</h1>
    <p>Registro de biomasa/basura, creditos kWh y ahorro estimado en RD$</p>
  </div>

  <!-- Filtro -->
  <div class="filtro-bar">
    <strong style="color:#1e293b;font-size:.88rem;"><i class="fas fa-filter"></i> Periodo:</strong>
    <?php foreach(['24h'=>'24h','7d'=>'7 dias','30d'=>'30 dias','90d'=>'90 dias','all'=>'Todo el historico'] as $k=>$l): ?>
    <a href="?periodo=<?php echo $k; ?>" class="btn btn-sm <?php echo $filtroPeriodo===$k?'btn-success':'btn-outline-secondary'; ?>"><?php echo $l; ?></a>
    <?php endforeach; ?>
  </div>

  <!-- KPIs coloridos -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="kpi-block">
      <div class="kpi-v"><?php echo $totalDep; ?></div><div class="kpi-l"><i class="fas fa-box"></i> Depositos</div>
    </div></div>
    <div class="col-6 col-md-3"><div class="kpi-block" style="background:linear-gradient(135deg,#0e7490,#06b6d4);">
      <div class="kpi-v"><?php echo number_format($totalKwh,2); ?></div><div class="kpi-l"><i class="fas fa-bolt"></i> kWh generados</div>
    </div></div>
    <div class="col-6 col-md-3"><div class="kpi-block" style="background:linear-gradient(135deg,#5b21b6,#7c3aed);">
      <div class="kpi-v">RD$ <?php echo number_format($totalRD,2); ?></div><div class="kpi-l"><i class="fas fa-dollar-sign"></i> Ahorro estimado</div>
    </div></div>
    <div class="col-6 col-md-3"><div class="kpi-block" style="background:linear-gradient(135deg,#9a3412,#f97316);">
      <div class="kpi-v"><?php echo number_format($totalPeso,2); ?> kg</div><div class="kpi-l"><i class="fas fa-weight"></i> Peso total</div>
    </div></div>
  </div>

  <!-- Graficas -->
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="chart-dark">
        <div class="chart-dark-title"><i class="fas fa-chart-bar" style="color:#06b6d4;"></i> Historial mensual — kWh y Ahorro RD$ (6 meses)</div>
        <canvas id="chartMensual" height="130"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="chart-dark" style="height:100%;">
        <div class="chart-dark-title"><i class="fas fa-trophy" style="color:#f97316;"></i> Top usuarios por kWh</div>
        <canvas id="chartTopUsers" height="200"></canvas>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-5">
      <div class="chart-dark">
        <div class="chart-dark-title"><i class="fas fa-recycle" style="color:#10b981;"></i> Depositos por tipo de residuo</div>
        <canvas id="chartTipo" height="180"></canvas>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="stat-row" style="margin-bottom:0;">
        <div class="stat-card"><div class="sc-val"><?php echo $totalDep; ?></div><div class="sc-lbl">Total depositos</div></div>
        <div class="stat-card" style="border-top-color:#06b6d4;"><div class="sc-val" style="color:#06b6d4;"><?php echo number_format($totalKwh,3); ?></div><div class="sc-lbl">kWh acumulados</div></div>
        <div class="stat-card" style="border-top-color:#7c3aed;"><div class="sc-val" style="color:#7c3aed;"><?php echo number_format($totalPeso,2); ?></div><div class="sc-lbl">Kg de biomasa</div></div>
        <div class="stat-card" style="border-top-color:#f97316;"><div class="sc-val" style="color:#f97316;"><?php echo $conMetal; ?></div><div class="sc-lbl">Con metal detectado</div></div>
        <div class="stat-card" style="border-top-color:#2563eb;"><div class="sc-val" style="color:#2563eb;"><?php echo $totalCont; ?></div><div class="sc-lbl">Contenedores activos</div></div>
      </div>
    </div>
  </div>

  <!-- Mapa de depositos -->
  <div class="map-section">
    <div class="map-header">
      <div class="map-header-title">
        <i class="fas fa-map-marked-alt" style="color:#10b981;"></i>
        Mapa de depositos geolocalizados
      </div>
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="map-legend">
          <span class="map-legend-item"><span class="map-dot" style="background:#10b981;"></span>Organico / Biomasa</span>
          <span class="map-legend-item"><span class="map-dot" style="background:#3b82f6;"></span>Con metal detectado</span>
          <span class="map-legend-item"><span class="map-dot" style="background:#f97316;"></span>Otro residuo</span>
        </div>
        <div class="map-controls">
          <button class="btn-map-ctrl" onclick="mapDep.setView([19.451,-70.6894],12)" title="Centrar mapa"><i class="fas fa-crosshairs"></i></button>
          <button class="btn-map-ctrl" id="btnMapDepFull" onclick="toggleMapFull('mapDepContainer','btnMapDepFull')" title="Pantalla completa"><i class="fas fa-expand"></i></button>
        </div>
      </div>
    </div>
    <div id="mapDepContainer">
      <div class="map-box" id="mapDeposits" style="height:420px;"></div>
    </div>
    <div class="map-stats-bar">
      <div class="map-stat">Puntos mapeados: <strong id="mapDepPts">0</strong></div>
      <div class="map-stat">Total depositos: <strong><?php echo count($depositos); ?></strong></div>
      <div class="map-stat">kWh generados: <strong><?php echo number_format($totalKwh,3); ?></strong></div>
      <div class="map-stat">RD$ ahorrado: <strong><?php echo number_format($totalRD,2); ?></strong></div>
      <div class="map-stat ms-auto" style="font-size:.75rem;"><i class="fas fa-hand-pointer"></i> Clic en marcador para detalles</div>
    </div>
  </div>

  <!-- Contenedores -->
  <div class="panel">
    <div class="panel-head"><i class="fas fa-trash-alt" style="color:#f97316;"></i> Contenedores registrados (<?php echo count($contenedores); ?>)</div>
    <div style="padding:1.25rem;">
      <div class="row g-3">
        <?php foreach($contenedores as $c): ?>
        <div class="col-xl-3 col-md-4 col-sm-6">
          <div class="cont-card">
            <div style="font-weight:700;font-size:.9rem;color:#1e293b;margin-bottom:.3rem;">
              <i class="fas fa-trash-alt" style="color:#f97316;"></i> <?php echo htmlspecialchars($c['codigo_contenedor']??'N/A'); ?>
            </div>
            <div style="font-size:.8rem;color:#64748b;"><?php echo htmlspecialchars($c['ubicacion']??'Sin ubicacion'); ?></div>
            <div style="font-size:.78rem;margin-top:.35rem;">
              Tipo: <strong><?php echo htmlspecialchars($c['tipo_contenedor']??'N/A'); ?></strong>
            </div>
            <div class="<?php echo ($c['estado']??'')==='activo'?'status-ok':'status-off'; ?>" style="font-size:.78rem;margin-top:.2rem;">
              <i class="fas fa-circle" style="font-size:.55rem;"></i> <?php echo htmlspecialchars($c['estado']??'desconocido'); ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($contenedores)): ?>
        <div class="col-12"><div class="alert alert-light text-center">No hay contenedores registrados</div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Tabla depositos -->
  <div class="panel">
    <div class="panel-head"><i class="fas fa-table" style="color:#065f46;"></i> Registro de depositos (<?php echo count($depositos); ?>)</div>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Usuario</th><th>Contenedor</th><th>Ubicacion</th><th>Peso (kg)</th><th>kWh</th><th>Residuo</th><th>Metal</th><th>Fecha</th></tr></thead>
        <tbody>
          <?php foreach($depositos as $d):
            $uLabel = trim(($d['nombre']??'').' '.($d['apellido']??'')) ?: ($d['usuario']??'Usuario #'.intval($d['id_usuario']));
            $fecha = $d['creado_en']??$d['fecha_hora'];
          ?>
          <tr>
            <td><strong>#<?php echo intval($d['id']); ?></strong></td>
            <td><?php echo htmlspecialchars($uLabel); ?></td>
            <td><?php echo htmlspecialchars($d['codigo_contenedor']??'ID '.intval($d['id_contenedor'])); ?></td>
            <td><?php echo htmlspecialchars($d['ubicacion']??'Sin ubicacion'); ?></td>
            <td><?php echo $d['peso']!==null?number_format(floatval($d['peso']),3):'-'; ?></td>
            <td><strong style="color:#10b981;"><?php echo $d['credito_kwh']!==null?number_format(floatval($d['credito_kwh']),4):'-'; ?></strong></td>
            <td><?php echo htmlspecialchars($d['tipo_residuo']??'-'); ?></td>
            <td><?php echo intval($d['metal_detectado'])===1?'<span class="badge bg-success">Si</span>':'<span class="badge bg-secondary">No</span>'; ?></td>
            <td><?php echo $fecha?date('d/m/Y H:i',strtotime($fecha)):'-'; ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($depositos)): ?>
          <tr><td colspan="9" class="text-center py-5 text-muted">No hay depositos para el periodo seleccionado</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="/PRERMI/web/assets/js/theme.js"></script>
<script>
const depositos  = <?php echo json_encode(array_map(fn($d)=>['id'=>intval($d['id']),'usuario'=>$d['usuario'],'codigo_contenedor'=>$d['codigo_contenedor'],'latitud'=>$d['latitud'],'longitud'=>$d['longitud'],'peso'=>$d['peso'],'credito_kwh'=>$d['credito_kwh'],'ubicacion'=>$d['ubicacion'],'id_usuario'=>$d['id_usuario'],'tipo_residuo'=>$d['tipo_residuo']??'','metal_detectado'=>intval($d['metal_detectado']??0)], $depositos)); ?>;
const mLabels    = <?php echo json_encode($mLabels); ?>;
const mKwh       = <?php echo json_encode($mKwh); ?>;
const mRD        = <?php echo json_encode($mRD); ?>;
const mCnt       = <?php echo json_encode($mCnt); ?>;
const topNames   = <?php echo json_encode(array_map(fn($u)=>trim(($u['nombre']??'').(' '.($u['apellido']??'')))?: $u['usuario'], $topUsers)); ?>;
const topKwh     = <?php echo json_encode(array_map(fn($u)=>round(floatval($u['kwh']),3), $topUsers)); ?>;
const tipoLabels = <?php echo json_encode(array_keys($tipoResiduo)); ?>;
const tipoVals   = <?php echo json_encode(array_values($tipoResiduo)); ?>;
const TARIFA     = <?php echo $TARIFA_RD; ?>;

// Mapa depositos
const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
const lightTileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const darkTileUrl  = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
const mapDep = L.map('mapDeposits', {zoomControl:true}).setView([19.451,-70.6894],12);
const tileLayerDep = L.tileLayer(isDark() ? darkTileUrl : lightTileUrl, {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors',
  maxZoom: 19
}).addTo(mapDep);
window._prermiMaps.push({map: mapDep, tileLayer: tileLayerDep});

let mapDepPts = 0;
depositos.forEach(d => {
  const lat = parseFloat(d.latitud), lng = parseFloat(d.longitud);
  if (!isFinite(lat) || !isFinite(lng)) return;
  mapDepPts++;
  const tipoL = (d.tipo_residuo||'').toLowerCase();
  const color = d.metal_detectado == 1 ? '#3b82f6' : (tipoL.includes('org') || tipoL.includes('bio') ? '#10b981' : '#f97316');
  const marker = L.circleMarker([lat, lng], {
    radius: 9, fillColor: color, color: '#fff',
    weight: 2, opacity: 1, fillOpacity: .85
  });
  marker.bindPopup(
    `<div style="min-width:170px;">`+
    `<div style="font-weight:700;font-size:.95rem;margin-bottom:.4rem;">Deposito #${d.id}</div>`+
    `<div><i class="fas fa-user" style="color:#94a3b8;width:14px;"></i> ${d.usuario||'Anonimo'}</div>`+
    `<div><i class="fas fa-trash" style="color:#94a3b8;width:14px;"></i> ${d.codigo_contenedor||'-'}</div>`+
    `<div><i class="fas fa-weight" style="color:#94a3b8;width:14px;"></i> ${d.peso ? parseFloat(d.peso).toFixed(3)+' kg' : '-'}</div>`+
    `<div><i class="fas fa-bolt" style="color:#06b6d4;width:14px;"></i> ${d.credito_kwh ? parseFloat(d.credito_kwh).toFixed(4)+' kWh' : '-'}</div>`+
    `<div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem;">${d.tipo_residuo||'-'}</div>`+
    `</div>`
  );
  marker.addTo(mapDep);
});
document.getElementById('mapDepPts').textContent = mapDepPts;


// Grafica mensual
const c1=document.getElementById('chartMensual');
if(c1){new Chart(c1,{type:'bar',data:{labels:mLabels,datasets:[
  {label:'kWh',data:mKwh,backgroundColor:mKwh.map((_,i)=>`hsla(${180+i*25},80%,55%,.7)`),borderColor:mKwh.map((_,i)=>`hsl(${180+i*25},80%,60%)`),borderWidth:2,borderRadius:6,yAxisID:'yK'},
  {label:'Ahorro RD$',data:mRD,type:'line',borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.12)',pointBackgroundColor:'#10b981',pointRadius:4,borderWidth:2.5,fill:true,tension:.35,yAxisID:'yR'}
]},options:{responsive:true,interaction:{mode:'index',intersect:false},plugins:{legend:{labels:{color:'#94a3b8'}}},scales:{x:{ticks:{color:'#94a3b8'}},yK:{position:'left',ticks:{color:'#06b6d4',callback:v=>v.toFixed(2)+' kWh'}},yR:{position:'right',ticks:{color:'#10b981',callback:v=>'RD$'+v.toFixed(0)},grid:{drawOnChartArea:false}}}}});}

// Top usuarios
const c2=document.getElementById('chartTopUsers');
if(c2&&topNames.length){const cols=['#06b6d4','#10b981','#7c3aed','#f97316','#ec4899','#3b82f6'];
new Chart(c2,{type:'bar',data:{labels:topNames,datasets:[{label:'kWh',data:topKwh,backgroundColor:topNames.map((_,i)=>cols[i%cols.length]+'aa'),borderColor:topNames.map((_,i)=>cols[i%cols.length]),borderWidth:1.5,borderRadius:5}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#94a3b8'}},y:{ticks:{color:'#cbd5e1',font:{size:11}}}}}})}

// Tipo residuo
const c3=document.getElementById('chartTipo');
if(c3&&tipoLabels.length){const cols2=['#10b981','#2563eb','#f97316','#7c3aed','#ef4444','#06b6d4','#facc15'];
new Chart(c3,{type:'doughnut',data:{labels:tipoLabels,datasets:[{data:tipoVals,backgroundColor:tipoLabels.map((_,i)=>cols2[i%cols2.length]+'cc'),borderColor:tipoLabels.map((_,i)=>cols2[i%cols2.length]),borderWidth:2}]},options:{responsive:true,plugins:{legend:{labels:{color:'#94a3b8',font:{size:11}}}}}})}
</script>
</body>
</html>
