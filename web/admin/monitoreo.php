<?php
/**
 * monitoreo.php - Monitoreo vehicular ESP32-CAM
 * PRERMI Admin - Pagina independiente de monitoreo
 */
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: loginA.php"); exit; }
require_once __DIR__ . '/../../api/utils.php';

$pdo = getPDO();
$stA = $pdo->prepare("SELECT id,usuario,email,rol FROM usuarios_admin WHERE id=? LIMIT 1");
$stA->execute([$_SESSION['admin_id']]);
$admin = $stA->fetch(PDO::FETCH_ASSOC) ?: ['usuario'=>'Admin','email'=>'','rol'=>'admin'];
$flash = null;

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS capturas_semaforo_rojo (
        id INT AUTO_INCREMENT PRIMARY KEY, vehiculo_id INT NOT NULL,
        marcado_por_admin_id INT NULL, nota VARCHAR(255) NULL,
        imagen VARCHAR(255) NULL, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vid (vehiculo_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $colImg = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='capturas_semaforo_rojo' AND COLUMN_NAME='imagen'");
    $colImg->execute();
    if (!intval($colImg->fetchColumn())) {
        $pdo->exec("ALTER TABLE capturas_semaforo_rojo ADD COLUMN imagen VARCHAR(255) NULL AFTER nota");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';
        if ($accion === 'toggle_rojo') {
            $vid = intval($_POST['vehiculo_id'] ?? 0);
            $marcar = intval($_POST['marcar'] ?? 0);
            if ($vid > 0) {
                if ($marcar === 1) {
                    $si = $pdo->prepare("SELECT imagen FROM vehiculos_registrados WHERE id=? LIMIT 1");
                    $si->execute([$vid]); $img = $si->fetchColumn() ?: null;
                    $pdo->prepare("INSERT INTO capturas_semaforo_rojo (vehiculo_id,marcado_por_admin_id,nota,imagen) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE marcado_por_admin_id=VALUES(marcado_por_admin_id),imagen=COALESCE(imagen,VALUES(imagen))")
                        ->execute([$vid, intval($_SESSION['admin_id']), 'Marcado manualmente', $img]);
                    $pdo->prepare("INSERT INTO logs_sistema(descripcion,tipo) VALUES(?,?)")
                        ->execute(["Admin #".intval($_SESSION['admin_id'])." marco vehiculo #$vid en rojo", 'warning']);
                    $flash = ['type'=>'success','msg'=>'Captura marcada en semaforo rojo'];
                } else {
                    $pdo->prepare("DELETE FROM capturas_semaforo_rojo WHERE vehiculo_id=?")->execute([$vid]);
                    $flash = ['type'=>'warning','msg'=>'Captura removida de semaforo rojo'];
                }
            }
        }
        if (!empty($flash)) { header("Location: monitoreo.php?flash=".urlencode($flash['msg'])."&ftype=".$flash['type']); exit; }
    }

    if (isset($_GET['flash'])) {
        $flash = ['type'=>htmlspecialchars($_GET['ftype']??'info'), 'msg'=>htmlspecialchars($_GET['flash'])];
    }

    $filtroPeriodo = $_GET['periodo'] ?? '7d';
    $sqlWhere = match($filtroPeriodo) {
        '24h' => "WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
        '3d'  => "WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 3 DAY)",
        '7d'  => "WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        '30d' => "WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        default => ""
    };

    $vehiculos = $pdo->query("SELECT id,placa,tipo_vehiculo,imagen,ubicacion,fecha,hora,probabilidad,latitud,longitud,creado_en FROM vehiculos_registrados $sqlWhere ORDER BY creado_en DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);

    $rojoMap = [];
    foreach ($pdo->query("SELECT vehiculo_id,imagen FROM capturas_semaforo_rojo")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $rojoMap[intval($r['vehiculo_id'])] = ['imagen' => $r['imagen']];
    }

    $capturasRojo = [];
    foreach ($vehiculos as $v) {
        $id = intval($v['id']);
        $txt = strtolower(($v['tipo_vehiculo']??'').' '.($v['ubicacion']??'').' '.($v['placa']??''));
        if (isset($rojoMap[$id]) || strpos($txt,'rojo')!==false || strpos($txt,'semaforo')!==false || strpos($txt,'infractor')!==false) {
            $v['imagen_rojo'] = $rojoMap[$id]['imagen'] ?? $v['imagen'];
            $capturasRojo[] = $v;
        }
    }

    $totalCap = count($vehiculos);
    $totalRojo = count($capturasRojo);
    $highConf = count(array_filter($vehiculos, fn($v) => floatval($v['probabilidad']) >= 0.8));

    // Conteo por tipo de vehiculo
    $tipoCount = [];
    foreach ($vehiculos as $v) { $t = $v['tipo_vehiculo']??'Desconocido'; $tipoCount[$t]=($tipoCount[$t]??0)+1; }

} catch(Exception $e) {
    $vehiculos=$capturasRojo=[]; $rojoMap=[]; $totalCap=$totalRojo=$highConf=0; $tipoCount=[];
    $flash=['type'=>'danger','msg'=>'Error: '.$e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Monitoreo Vehicular | PRERMI Admin</title>
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
.page-header{background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:16px;padding:1.5rem 2rem;margin-bottom:1.5rem;color:#fff;}
.page-header h1{font-size:1.5rem;font-weight:800;margin:0;}
.page-header p{margin:.25rem 0 0;opacity:.8;font-size:.9rem;}
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:#fff;border-radius:12px;padding:1.1rem;box-shadow:0 2px 12px rgba(0,0,0,.07);text-align:center;}
.sc-val{font-size:2rem;font-weight:800;}
.sc-lbl{font-size:.82rem;color:#64748b;}
.panel{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:1.5rem;overflow:hidden;}
.panel-head{padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;font-weight:700;color:#1e293b;}
.panel-body{padding:1.25rem;}
.map-box{height:400px;width:100%;}
.capture-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.85rem;}
.capture-card{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff;transition:all .2s;}
.capture-card:hover{box-shadow:0 4px 18px rgba(0,0,0,.12);transform:translateY(-2px);}
.capture-card img{width:100%;height:140px;object-fit:cover;background:#f5f5f5;}
.capture-body{padding:.75rem;font-size:.85rem;}
.badge-rojo{background:#b83232;color:#fff;font-weight:600;border-radius:10px;padding:2px 8px;font-size:.72rem;}
.badge-ml{background:#2f9e44;color:#fff;font-weight:600;border-radius:10px;padding:2px 8px;font-size:.72rem;}
.filtro-bar{background:#fff;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;}
.table-container{overflow-x:auto;}
.table-container table thead{background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;}
.table-container table thead th{border:none;padding:.85rem;white-space:nowrap;font-weight:600;}
.table-container table tbody td{padding:.75rem;vertical-align:middle;border-color:#f0f0f0;}
.table-container table tbody tr:hover{background:#f9f9f9;}
.conf-high{background:#d1fae5;color:#065f46;border-radius:8px;padding:2px 8px;font-size:.78rem;font-weight:600;}
.conf-low{background:#fee2e2;color:#b91c1c;border-radius:8px;padding:2px 8px;font-size:.78rem;font-weight:600;}
.chart-dark{background:#1e293b;border-radius:14px;padding:1.5rem;}
.chart-dark-title{color:#e2e8f0;font-weight:700;font-size:.95rem;margin-bottom:1rem;}
.quick-links{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem;}
.quick-link-card{display:flex;align-items:center;gap:.85rem;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:.85rem 1rem;border-radius:12px;color:#fff;text-decoration:none;min-width:260px;transition:all .2s;}
.quick-link-card:hover{background:rgba(255,255,255,.18);color:#fff;transform:translateY(-1px);}
.quick-link-icon{width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
.quick-link-copy strong{display:block;font-size:.95rem;line-height:1.2;}
.quick-link-copy span{display:block;font-size:.78rem;opacity:.85;}
</style>
</head>
<body>
<nav class="navbar-admin">
  <a class="nav-brand" href="dashboard.php"><img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="nav-logo-img"></a>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="monitoreo.php" class="nav-link-item active"><i class="fas fa-video"></i> Monitoreo</a>
    <a href="depositos.php" class="nav-link-item"><i class="fas fa-box-open"></i> Depositos</a>
    <a href="vehiculos.php" class="nav-link-item"><i class="fas fa-camera"></i> Catalogo IA</a>
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
  <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mb-3" role="alert">
    <?php echo $flash['msg']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="page-header">
    <h1><i class="fas fa-video"></i> Monitoreo Vehicular ESP32-CAM</h1>
    <p>Capturas de vehiculos, semaforos en rojo e incidentes de transito</p>
    <div class="quick-links">
      <a class="quick-link-card" href="vehiculos.php">
        <div class="quick-link-icon"><i class="fas fa-camera"></i></div>
        <div class="quick-link-copy">
          <strong>Gestionar catalogo de deteccion</strong>
          <span>Sube imagenes de referencia y activa o desactiva coincidencias.</span>
        </div>
      </a>
    </div>
  </div>

  <!-- Filtro de periodo -->
  <div class="filtro-bar">
    <strong style="color:#1e293b;font-size:.88rem;"><i class="fas fa-filter"></i> Periodo:</strong>
    <?php foreach(['24h'=>'Ultimas 24h','3d'=>'3 dias','7d'=>'7 dias','30d'=>'30 dias','all'=>'Todo'] as $k=>$lbl): ?>
    <a href="?periodo=<?php echo $k; ?>" class="btn btn-sm <?php echo $filtroPeriodo===$k?'btn-primary':'btn-outline-secondary'; ?>"><?php echo $lbl; ?></a>
    <?php endforeach; ?>
    <span class="ms-auto" style="font-size:.82rem;color:#64748b;"><?php echo $totalCap; ?> capturas encontradas</span>
  </div>

  <!-- Stats -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="sc-val" style="color:#2563eb;"><?php echo $totalCap; ?></div>
      <div class="sc-lbl">Total capturas</div>
    </div>
    <div class="stat-card">
      <div class="sc-val" style="color:#ef4444;"><?php echo $totalRojo; ?></div>
      <div class="sc-lbl">En semaforo rojo</div>
    </div>
    <div class="stat-card">
      <div class="sc-val" style="color:#10b981;"><?php echo $highConf; ?></div>
      <div class="sc-lbl">Alta confianza (&ge;80%)</div>
    </div>
    <div class="stat-card">
      <div class="sc-val" style="color:#7c3aed;"><?php echo count($tipoCount); ?></div>
      <div class="sc-lbl">Tipos de vehiculo</div>
    </div>
  </div>

  <!-- Capturas en rojo -->
  <?php if(!empty($capturasRojo)): ?>
  <div class="panel">
    <div class="panel-head" style="background:#fff5f5;border-bottom:2px solid #fee2e2;">
      <i class="fas fa-traffic-light" style="color:#ef4444;"></i>
      <span>Capturas marcadas en semaforo rojo (<?php echo count($capturasRojo); ?>)</span>
    </div>
    <div class="panel-body">
      <div class="capture-grid">
        <?php foreach($capturasRojo as $cap): ?>
        <div class="capture-card">
          <img src="/PRERMI/uploads/vehiculos/<?php echo htmlspecialchars($cap['imagen_rojo']??$cap['imagen']); ?>"
               alt="Captura" onerror="this.src='https://placehold.co/400x250?text=Sin+Imagen'">
          <div class="capture-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <strong><?php echo htmlspecialchars($cap['placa']??'N/A'); ?></strong>
              <span class="badge-rojo">ROJO</span>
            </div>
            <div class="text-muted" style="font-size:.78rem;"><?php echo htmlspecialchars($cap['ubicacion']??'Sin ubicacion'); ?></div>
            <div class="text-muted" style="font-size:.75rem;margin-bottom:.5rem;"><?php echo date('d/m/Y H:i',strtotime($cap['creado_en'])); ?></div>
            <form method="POST">
              <input type="hidden" name="accion" value="toggle_rojo">
              <input type="hidden" name="vehiculo_id" value="<?php echo intval($cap['id']); ?>">
              <input type="hidden" name="marcar" value="0">
              <button class="btn btn-sm btn-outline-danger w-100">Quitar de Rojo</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Graficas -->
  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="chart-dark">
        <div class="chart-dark-title"><i class="fas fa-chart-bar" style="color:#3b82f6;"></i> Capturas por tipo de vehiculo</div>
        <canvas id="chartTipos" height="120"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="chart-dark" style="height:100%;">
        <div class="chart-dark-title"><i class="fas fa-chart-pie" style="color:#7c3aed;"></i> Distribucion rojo vs. normal</div>
        <canvas id="chartDonut" height="170"></canvas>
      </div>
    </div>
  </div>

  <!-- Mapa de incidentes -->
  <div class="map-section mb-3">
    <div class="map-header">
      <div class="map-header-title">
        <i class="fas fa-map-marked-alt" style="color:#2563eb;"></i>
        Mapa de incidentes ESP32-CAM
      </div>
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="map-legend">
          <span class="map-legend-item"><span class="map-dot" style="background:#ef4444;"></span>Semaforo rojo</span>
          <span class="map-legend-item"><span class="map-dot" style="background:#22c55e;"></span>Normal / TinyML</span>
          <span class="map-legend-item"><span class="map-dot" style="background:#f59e0b;"></span>Alta confianza</span>
        </div>
        <div class="map-controls">
          <button class="btn-map-ctrl" onclick="mapCap.setView([19.451,-70.6894],12)" title="Centrar mapa"><i class="fas fa-crosshairs"></i></button>
          <button class="btn-map-ctrl" id="btnMapCapFull" onclick="toggleMapFull('mapCapContainer','btnMapCapFull')" title="Pantalla completa"><i class="fas fa-expand"></i></button>
        </div>
      </div>
    </div>
    <div id="mapCapContainer">
      <div class="map-box" id="mapCaptures" style="height:440px;"></div>
    </div>
    <div class="map-stats-bar">
      <div class="map-stat">Capturas mapeadas: <strong id="mapCapPts">0</strong></div>
      <div class="map-stat">Semaforo rojo: <strong style="color:#ef4444;"><?php echo $totalRojo; ?></strong></div>
      <div class="map-stat">Alta confianza: <strong style="color:#f59e0b;"><?php echo $highConf; ?></strong></div>
      <div class="map-stat">Total capturas: <strong><?php echo $totalCap; ?></strong></div>
      <div class="map-stat ms-auto" style="font-size:.75rem;"><i class="fas fa-hand-pointer"></i> Clic en marcador para detalles</div>
    </div>
  </div>

  <!-- Tabla completa -->
  <div class="panel">
    <div class="panel-head"><i class="fas fa-table" style="color:#1e40af;"></i> Registro completo de capturas</div>
    <div class="table-container">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Placa</th><th>Tipo</th><th>Ubicacion</th><th>Fecha/Hora</th><th>Confianza</th><th>Imagen</th><th>Semaforo rojo</th></tr></thead>
        <tbody>
          <?php foreach($vehiculos as $v): ?>
          <?php $vId=intval($v['id']); $isRojo=isset($rojoMap[$vId]); $conf=intval(floatval($v['probabilidad'])*100); ?>
          <tr>
            <td><strong>#<?php echo $vId; ?></strong></td>
            <td><strong><?php echo htmlspecialchars($v['placa']??'N/A'); ?></strong></td>
            <td><?php echo htmlspecialchars($v['tipo_vehiculo']??''); ?></td>
            <td><?php echo htmlspecialchars($v['ubicacion']??''); ?></td>
            <td><?php echo date('d/m/Y H:i',strtotime($v['creado_en'])); ?></td>
            <td><span class="<?php echo $conf>=80?'conf-high':'conf-low'; ?>"><?php echo $conf; ?>%</span></td>
            <td>
              <?php if(!empty($v['imagen'])): ?>
              <a href="#" onclick="abrirImagen('<?php echo htmlspecialchars($v['imagen'],ENT_QUOTES); ?>');return false;" style="color:#2563eb;"><i class="fas fa-image"></i> Ver</a>
              <?php else: ?><span class="text-muted">-</span><?php endif; ?>
            </td>
            <td>
              <?php if($isRojo): ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="accion" value="toggle_rojo">
                <input type="hidden" name="vehiculo_id" value="<?php echo $vId; ?>">
                <input type="hidden" name="marcar" value="0">
                <button class="btn btn-sm btn-outline-danger">Quitar</button>
              </form>
              <?php else: ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="accion" value="toggle_rojo">
                <input type="hidden" name="vehiculo_id" value="<?php echo $vId; ?>">
                <input type="hidden" name="marcar" value="1">
                <button class="btn btn-sm btn-danger">Marcar</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($vehiculos)): ?>
          <tr><td colspan="8" class="text-center py-5 text-muted">No hay capturas para el periodo seleccionado</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal imagen -->
<div class="modal fade" id="imgModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Captura ESP32-CAM</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center">
      <img id="imgModalSrc" src="" alt="Captura" style="max-width:100%;border-radius:10px;">
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="/PRERMI/web/assets/js/theme.js"></script>
<script>
const vehiculos = <?php echo json_encode(array_map(fn($v)=>['id'=>intval($v['id']),'placa'=>$v['placa'],'ubicacion'=>$v['ubicacion'],'latitud'=>$v['latitud'],'longitud'=>$v['longitud'],'probabilidad'=>$v['probabilidad']??0,'creado_en'=>$v['creado_en']], $vehiculos)); ?>;
const rojoIds   = <?php echo json_encode(array_map('intval', array_keys($rojoMap))); ?>;
const tipoData  = <?php echo json_encode($tipoCount); ?>;
const totalCap  = <?php echo $totalCap; ?>;
const totalRojo = <?php echo $totalRojo; ?>;

// Mapa capturas
const isDarkM = () => document.documentElement.getAttribute('data-theme') === 'dark';
const lightTileUrlM = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const darkTileUrlM  = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
const mapCap = L.map('mapCaptures', {zoomControl:true}).setView([19.451,-70.6894],12);
const tileLayerCap = L.tileLayer(isDarkM() ? darkTileUrlM : lightTileUrlM, {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors',
  maxZoom: 19
}).addTo(mapCap);
window._prermiMaps.push({map: mapCap, tileLayer: tileLayerCap});

let mapCapPts = 0;
vehiculos.forEach(v => {
  if (!v.latitud || !v.longitud) return;
  const lat = parseFloat(v.latitud), lng = parseFloat(v.longitud);
  if (!isFinite(lat) || !isFinite(lng)) return;
  mapCapPts++;
  const red  = rojoIds.includes(v.id);
  const conf = parseFloat(v.probabilidad || 0);
  const color  = red ? '#ef4444' : (conf >= 80 ? '#f59e0b' : '#22c55e');
  const border = red ? '#991b1b' : (conf >= 80 ? '#92400e' : '#15803d');
  const radius = red ? 11 : 8;
  const marker = L.circleMarker([lat, lng], {
    radius, fillColor: color, color: border,
    weight: 2, opacity: 1, fillOpacity: .85
  });
  const ts = v.creado_en ? new Date(v.creado_en).toLocaleString('es-DO') : '-';
  marker.bindPopup(
    `<div style="min-width:160px;">`+
    `<div style="font-weight:700;font-size:.95rem;margin-bottom:.35rem;">${v.placa||'Sin placa'}</div>`+
    `<div><i class="fas fa-map-marker-alt" style="color:#94a3b8;width:14px;"></i> ${v.ubicacion||'Sin ubicacion'}</div>`+
    (red ? `<div style="color:#ef4444;font-weight:600;"><i class="fas fa-traffic-light" style="width:14px;"></i> Semaforo rojo</div>` : '')+
    (conf>=80 ? `<div style="color:#f59e0b;"><i class="fas fa-star" style="width:14px;"></i> Conf. alta (${conf}%)</div>` : '')+
    `<div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem;">${ts}</div>`+
    `</div>`
  );
  marker.addTo(mapCap);
});
document.getElementById('mapCapPts').textContent = mapCapPts;


// Grafica tipos
const ctx1 = document.getElementById('chartTipos');
if(ctx1 && Object.keys(tipoData).length){
  const colors=['#3b82f6','#10b981','#f97316','#7c3aed','#ef4444','#06b6d4','#facc15','#ec4899'];
  new Chart(ctx1,{type:'bar',data:{
    labels:Object.keys(tipoData),
    datasets:[{label:'Capturas',data:Object.values(tipoData),backgroundColor:Object.keys(tipoData).map((_,i)=>colors[i%colors.length]+'bb'),borderColor:Object.keys(tipoData).map((_,i)=>colors[i%colors.length]),borderWidth:1.5,borderRadius:6}]
  },options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#94a3b8'}},y:{ticks:{color:'#94a3b8',stepSize:1}}}}});
}

// Donut rojo vs normal
const ctx2 = document.getElementById('chartDonut');
if(ctx2){
  new Chart(ctx2,{type:'doughnut',data:{
    labels:['Rojo','Normal'],
    datasets:[{data:[totalRojo,Math.max(0,totalCap-totalRojo)],backgroundColor:['#ef4444bb','#10b981bb'],borderColor:['#ef4444','#10b981'],borderWidth:2}]
  },options:{responsive:true,plugins:{legend:{labels:{color:'#94a3b8'}}}}});
}

function abrirImagen(img){
  document.getElementById('imgModalSrc').src='/PRERMI/uploads/vehiculos/'+img;
  new bootstrap.Modal(document.getElementById('imgModal')).show();
}
</script>
</body>
</html>
