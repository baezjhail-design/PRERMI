<?php
/**
 * dashboard.php - Panel de control (ultimas 24 horas)
 * PRERMI Admin - Hub principal con acceso a modulos independientes
 */
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: loginA.php"); exit; }
require_once __DIR__ . '/../../api/utils.php';

function tblOk(PDO $pdo, string $t): bool {
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $s->execute([$t]); return (bool)$s->fetchColumn();
}

$pdo = getPDO();
$stA = $pdo->prepare("SELECT id,usuario,email,rol FROM usuarios_admin WHERE id=? LIMIT 1");
$stA->execute([$_SESSION['admin_id']]);
$admin = $stA->fetch(PDO::FETCH_ASSOC) ?: ['usuario'=>'Admin','email'=>'','rol'=>'admin'];
$sT = tblOk($pdo,'sanciones') ? 'sanciones' : 'multas';
$h24 = date('Y-m-d H:i:s', strtotime('-24 hours'));

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS capturas_semaforo_rojo (
        id INT AUTO_INCREMENT PRIMARY KEY, vehiculo_id INT NOT NULL,
        marcado_por_admin_id INT NULL, nota VARCHAR(255) NULL,
        imagen VARCHAR(255) NULL, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vid (vehiculo_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $qi = function($sql,$p=[]) use($pdo){ $s=$pdo->prepare($sql); $s->execute($p); return intval($s->fetchColumn()); };
    $qf = function($sql,$p=[]) use($pdo){ $s=$pdo->prepare($sql); $s->execute($p); return floatval($s->fetchColumn()); };
    $capturas24h  = $qi("SELECT COUNT(*) FROM vehiculos_registrados WHERE creado_en>=?",[$h24]);
    $depositos24h = $qi("SELECT COUNT(*) FROM depositos WHERE COALESCE(creado_en,fecha_hora)>=?",[$h24]);
    $sanciones24h = $qi("SELECT COUNT(*) FROM {$sT} WHERE creado_en>=?",[$h24]);
    $kwh24h       = round($qf("SELECT COALESCE(SUM(credito_kwh),0) FROM depositos WHERE COALESCE(creado_en,fecha_hora)>=?",[$h24]),3);
    $rojo24h      = $qi("SELECT COUNT(*) FROM capturas_semaforo_rojo WHERE creado_en>=?",[$h24]);
    $usuarios24h  = $qi("SELECT COUNT(DISTINCT id_usuario) FROM depositos WHERE COALESCE(creado_en,fecha_hora)>=?",[$h24]);
    $noVistas     = $qi("SELECT COUNT(*) FROM {$sT} WHERE seen_by_admin=0");
    $pendAdmins   = $qi("SELECT COUNT(*) FROM usuarios_admin WHERE active=0");
    $totCapturas  = $qi("SELECT COUNT(*) FROM vehiculos_registrados");
    $totDepositos = $qi("SELECT COUNT(*) FROM depositos");
    $totSanciones = $qi("SELECT COUNT(*) FROM {$sT}");
    $totUsuarios  = $qi("SELECT COUNT(*) FROM usuarios");
    $totAdmins    = $qi("SELECT COUNT(*) FROM usuarios_admin");
    $kwhTotal     = round($qf("SELECT COALESCE(SUM(credito_kwh),0) FROM depositos"),3);
    $hrMapD=[]; $hrMapC=[];
    $s=$pdo->prepare("SELECT DATE_FORMAT(COALESCE(creado_en,fecha_hora),'%H') hr,COUNT(*) c FROM depositos WHERE COALESCE(creado_en,fecha_hora)>=? GROUP BY hr"); $s->execute([$h24]);
    foreach($s->fetchAll(PDO::FETCH_ASSOC) as $r) $hrMapD[intval($r['hr'])]=intval($r['c']);
    $s=$pdo->prepare("SELECT DATE_FORMAT(creado_en,'%H') hr,COUNT(*) c FROM vehiculos_registrados WHERE creado_en>=? GROUP BY hr"); $s->execute([$h24]);
    foreach($s->fetchAll(PDO::FETCH_ASSOC) as $r) $hrMapC[intval($r['hr'])]=intval($r['c']);
    $hrLabels=$hrD=$hrC=[];
    $nowH=intval(date('H'));
    for($i=23;$i>=0;$i--){$h=($nowH-$i+24)%24; $hrLabels[]=sprintf('%02d:00',$h); $hrD[]=$hrMapD[$h]??0; $hrC[]=$hrMapC[$h]??0;}
    $s=$pdo->prepare("SELECT descripcion,tipo,creado_en FROM logs_sistema WHERE creado_en>=? ORDER BY creado_en DESC LIMIT 15"); $s->execute([$h24]);
    $logs=$s->fetchAll(PDO::FETCH_ASSOC);
    $s=$pdo->prepare("SELECT d.id,d.peso,d.credito_kwh,d.metal_detectado,COALESCE(d.creado_en,d.fecha_hora) ts,u.usuario,c.codigo_contenedor FROM depositos d LEFT JOIN usuarios u ON u.id=d.id_usuario LEFT JOIN contenedores_registrados c ON c.id=d.id_contenedor WHERE COALESCE(d.creado_en,d.fecha_hora)>=? ORDER BY ts DESC LIMIT 6"); $s->execute([$h24]);
    $recDep=$s->fetchAll(PDO::FETCH_ASSOC);
    $s=$pdo->prepare("SELECT s.id,s.descripcion,s.creado_en,s.seen_by_admin,u.usuario FROM {$sT} s LEFT JOIN usuarios u ON u.id=s.user_id WHERE s.creado_en>=? ORDER BY s.creado_en DESC LIMIT 6"); $s->execute([$h24]);
    $recSanc=$s->fetchAll(PDO::FETCH_ASSOC);
    $s=$pdo->prepare("SELECT id,placa,tipo_vehiculo,ubicacion,probabilidad,creado_en FROM vehiculos_registrados WHERE creado_en>=? ORDER BY creado_en DESC LIMIT 6"); $s->execute([$h24]);
    $recCap=$s->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $capturas24h=$depositos24h=$sanciones24h=$kwh24h=$rojo24h=$usuarios24h=0;
    $noVistas=$pendAdmins=$totCapturas=$totDepositos=$totSanciones=$totUsuarios=$totAdmins=$kwhTotal=0;
    $hrLabels=$hrD=$hrC=$logs=$recDep=$recSanc=$recCap=[];
}
$ahorro24h = round($kwh24h * 14.00, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Panel de Control | PRERMI Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/PRERMI/web/assets/css/theme.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>(function(){var t=localStorage.getItem('prermi_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f1f5f9;min-height:100vh;font-family:'Segoe UI',sans-serif;}
.navbar-admin{background:linear-gradient(135deg,#1e40af 0%,#6d28d9 100%);padding:.75rem 1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;box-shadow:0 4px 20px rgba(30,64,175,.4);}
.nav-brand{color:#fff;font-weight:800;font-size:1.25rem;text-decoration:none;white-space:nowrap;}
.nav-links{display:flex;gap:.25rem;flex-wrap:wrap;flex:1;}
.nav-link-item{color:rgba(255,255,255,.8);padding:.4rem .75rem;border-radius:6px;text-decoration:none;font-size:.88rem;font-weight:500;transition:all .2s;white-space:nowrap;}
.nav-link-item:hover,.nav-link-item.active{background:rgba(255,255,255,.2);color:#fff;}
.nav-link-item .badge-nav{background:#ef4444;color:#fff;border-radius:10px;padding:1px 6px;font-size:.7rem;margin-left:3px;}
.nav-right{display:flex;align-items:center;gap:.5rem;flex-shrink:0;}
.nav-user{color:#fff;font-weight:600;font-size:.9rem;}
.btn-logout{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:.35rem .75rem;border-radius:6px;text-decoration:none;font-size:.85rem;transition:all .2s;}
.btn-logout:hover{background:#ef4444;border-color:#ef4444;color:#fff;}
.main{padding:1.5rem;}
.hero{background:linear-gradient(135deg,#1e40af,#6d28d9);border-radius:16px;padding:1.5rem 2rem;margin-bottom:1.5rem;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;}
.hero h1{font-size:1.6rem;font-weight:800;margin:0;}
.hero p{margin:.25rem 0 0;opacity:.85;font-size:.95rem;}
.hero-badge{background:rgba(255,255,255,.2);padding:.5rem 1rem;border-radius:20px;font-size:.85rem;font-weight:600;border:1px solid rgba(255,255,255,.3);}
.notif-bar{background:#fef3c7;border:1px solid #f59e0b;border-radius:10px;padding:.75rem 1.1rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.75rem;font-size:.92rem;}
.notif-bar.danger{background:#fee2e2;border-color:#ef4444;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:#fff;border-radius:12px;padding:1.1rem;box-shadow:0 2px 12px rgba(0,0,0,.07);border-top:4px solid #2563eb;transition:transform .2s;}
.stat-card:hover{transform:translateY(-2px);}
.stat-card .ic{font-size:1.5rem;margin-bottom:.4rem;}
.stat-card .val{font-size:1.7rem;font-weight:800;line-height:1.1;}
.stat-card .lbl{font-size:.8rem;color:#64748b;margin-top:.2rem;}
.stat-card .sub{font-size:.75rem;color:#94a3b8;margin-top:.1rem;}
.color-blue{border-top-color:#2563eb;} .color-blue .ic,.color-blue .val{color:#2563eb;}
.color-green{border-top-color:#10b981;} .color-green .ic,.color-green .val{color:#10b981;}
.color-orange{border-top-color:#f97316;} .color-orange .ic,.color-orange .val{color:#f97316;}
.color-red{border-top-color:#ef4444;} .color-red .ic,.color-red .val{color:#ef4444;}
.color-cyan{border-top-color:#06b6d4;} .color-cyan .ic,.color-cyan .val{color:#06b6d4;}
.color-purple{border-top-color:#7c3aed;} .color-purple .ic,.color-purple .val{color:#7c3aed;}
.section-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.1rem;margin-bottom:1.5rem;}
.section-card{background:#fff;border-radius:16px;padding:1.5rem;box-shadow:0 4px 18px rgba(0,0,0,.08);border:2px solid #e2e8f0;cursor:pointer;text-decoration:none;color:inherit;transition:all .25s;display:flex;flex-direction:column;gap:.75rem;}
.section-card:hover{transform:translateY(-4px);box-shadow:0 8px 28px rgba(0,0,0,.15);text-decoration:none;color:inherit;}
.sc-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;flex-shrink:0;}
.sc-blue{background:linear-gradient(135deg,#2563eb,#1e40af);border-color:#bfdbfe;}
.sc-green{background:linear-gradient(135deg,#10b981,#065f46);border-color:#a7f3d0;}
.sc-orange{background:linear-gradient(135deg,#f97316,#ea580c);border-color:#fed7aa;}
.sc-purple{background:linear-gradient(135deg,#7c3aed,#5b21b6);border-color:#ddd6fe;}
.section-card:hover.sc-card-blue{border-color:#2563eb;}
.section-card:hover.sc-card-green{border-color:#10b981;}
.section-card:hover.sc-card-orange{border-color:#f97316;}
.section-card:hover.sc-card-purple{border-color:#7c3aed;}
.sc-title{font-size:1.05rem;font-weight:700;color:#1e293b;margin:0;}
.sc-desc{font-size:.82rem;color:#64748b;margin:0;}
.sc-stat{font-size:.85rem;font-weight:600;padding:.25rem .65rem;border-radius:8px;}
.sc-arrow{margin-left:auto;opacity:.4;font-size:.85rem;}
.chart-box{background:#1e293b;border-radius:16px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:0 4px 18px rgba(0,0,0,.15);}
.chart-title{color:#e2e8f0;font-weight:700;font-size:1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;}
.activity-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1rem;margin-bottom:1.5rem;}
.activity-panel{background:#fff;border-radius:14px;padding:1.2rem;box-shadow:0 2px 12px rgba(0,0,0,.07);}
.activity-panel h6{font-weight:700;color:#1e293b;font-size:.95rem;margin-bottom:.8rem;padding-bottom:.5rem;border-bottom:2px solid #f1f5f9;}
.act-item{display:flex;align-items:flex-start;gap:.65rem;padding:.5rem 0;border-bottom:1px solid #f8fafc;}
.act-item:last-child{border-bottom:none;}
.act-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:.35rem;}
.dot-blue{background:#2563eb;} .dot-green{background:#10b981;} .dot-orange{background:#f97316;} .dot-red{background:#ef4444;} .dot-gray{background:#94a3b8;}
.act-text{font-size:.84rem;color:#334155;flex:1;}
.act-time{font-size:.75rem;color:#94a3b8;white-space:nowrap;}
.log-panel{background:#fff;border-radius:14px;padding:1.2rem;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:1.5rem;}
.log-entry{padding:.6rem 0;border-bottom:1px solid #f1f5f9;font-size:.84rem;}
.log-entry:last-child{border-bottom:none;}
.badge-tipo{font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:10px;}
.tipo-info{background:#dbeafe;color:#1e40af;} .tipo-warning{background:#fef3c7;color:#92400e;} .tipo-error{background:#fee2e2;color:#b91c1c;}
@media(max-width:768px){.hero{text-align:center;justify-content:center;} .nav-links{justify-content:center;}}
</style>
</head>
<body>
<nav class="navbar-admin">
  <a class="nav-brand" href="dashboard.php"><img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="nav-logo-img"></a>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="monitoreo.php" class="nav-link-item"><i class="fas fa-video"></i> Monitoreo</a>
    <a href="depositos.php" class="nav-link-item"><i class="fas fa-box-open"></i> Depositos</a>
    <a href="sanciones.php" class="nav-link-item"><i class="fas fa-exclamation-triangle"></i> Sanciones<?php if($noVistas>0): ?><span class="badge-nav"><?php echo $noVistas; ?></span><?php endif; ?></a>
    <a href="administradores.php" class="nav-link-item"><i class="fas fa-users-cog"></i> Administradores<?php if($pendAdmins>0): ?><span class="badge-nav"><?php echo $pendAdmins; ?></span><?php endif; ?></a>
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
  <?php if($noVistas>0||$pendAdmins>0): ?>
  <div class="notif-bar">
    <i class="fas fa-bell" style="color:#f59e0b;font-size:1.1rem;"></i>
    <div>
      <?php if($noVistas>0): ?>
        <a href="sanciones.php?filtro_visto=0" style="color:#92400e;font-weight:600;"><?php echo $noVistas; ?> sancion(es) sin revisar</a>
      <?php endif; ?>
      <?php if($pendAdmins>0): ?>
        <?php if($noVistas>0): ?> &nbsp;|&nbsp; <?php endif; ?>
        <a href="administradores.php#pendientes" style="color:#92400e;font-weight:600;"><?php echo $pendAdmins; ?> admin(s) pendientes de aprobacion</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="hero">
    <div>
      <h1><i class="fas fa-chart-line"></i> Panel de Control</h1>
      <p>Ultimas 24 horas &nbsp;|&nbsp; <?php echo date('d/m/Y H:i', strtotime('-24 hours')); ?> | <?php echo date('d/m/Y H:i'); ?></p>
    </div>
    <div class="hero-badge"><i class="fas fa-circle" style="color:#4ade80;font-size:.6rem;"></i> Sistema activo</div>
  </div>

  <!-- Stats 24h -->
  <div class="stat-grid">
    <div class="stat-card color-blue">
      <div class="ic"><i class="fas fa-video"></i></div>
      <div class="val"><?php echo $capturas24h; ?></div>
      <div class="lbl">Capturas hoy</div>
      <div class="sub"><?php echo $totCapturas; ?> total historico</div>
    </div>
    <div class="stat-card color-red">
      <div class="ic"><i class="fas fa-traffic-light"></i></div>
      <div class="val"><?php echo $rojo24h; ?></div>
      <div class="lbl">Semaforos rojos hoy</div>
    </div>
    <div class="stat-card color-green">
      <div class="ic"><i class="fas fa-box-open"></i></div>
      <div class="val"><?php echo $depositos24h; ?></div>
      <div class="lbl">Depositos hoy</div>
      <div class="sub"><?php echo $totDepositos; ?> total historico</div>
    </div>
    <div class="stat-card color-cyan">
      <div class="ic"><i class="fas fa-bolt"></i></div>
      <div class="val"><?php echo $kwh24h; ?></div>
      <div class="lbl">kWh generados hoy</div>
      <div class="sub">RD$ <?php echo number_format($ahorro24h,2); ?> ahorro estimado</div>
    </div>
    <div class="stat-card color-orange">
      <div class="ic"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="val"><?php echo $sanciones24h; ?></div>
      <div class="lbl">Sanciones hoy</div>
      <div class="sub"><?php echo $noVistas; ?> sin revisar</div>
    </div>
    <div class="stat-card color-purple">
      <div class="ic"><i class="fas fa-users"></i></div>
      <div class="val"><?php echo $usuarios24h; ?></div>
      <div class="lbl">Usuarios activos hoy</div>
      <div class="sub"><?php echo $totUsuarios; ?> usuarios registrados</div>
    </div>
  </div>

  <!-- Modulos -->
  <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:.75rem;">MODULOS DEL SISTEMA</div>
  <div class="section-cards">
    <a href="monitoreo.php" class="section-card sc-card-blue">
      <div class="sc-icon sc-blue"><i class="fas fa-video"></i></div>
      <div>
        <p class="sc-title">Monitoreo Vehicular</p>
        <p class="sc-desc">Capturas ESP32-CAM, semaforo rojo, mapa de incidentes y control de infracciones.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="sc-stat" style="background:#dbeafe;color:#1e40af;"><?php echo $capturas24h; ?> hoy</span>
        <?php if($rojo24h>0): ?><span class="sc-stat" style="background:#fee2e2;color:#b91c1c;"><?php echo $rojo24h; ?> rojos</span><?php endif; ?>
        <span class="sc-arrow"><i class="fas fa-arrow-right"></i></span>
      </div>
    </a>
    <a href="depositos.php" class="section-card sc-card-green">
      <div class="sc-icon sc-green"><i class="fas fa-box-open"></i></div>
      <div>
        <p class="sc-title">Depositos y energia</p>
        <p class="sc-desc">Depositos de biomasa y basura, generacion de kWh y ahorro electrico en RD$.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="sc-stat" style="background:#d1fae5;color:#065f46;"><?php echo $depositos24h; ?> hoy</span>
        <span class="sc-stat" style="background:#cffafe;color:#0e7490;"><?php echo $kwhTotal; ?> kWh total</span>
        <span class="sc-arrow"><i class="fas fa-arrow-right"></i></span>
      </div>
    </a>
    <a href="sanciones.php" class="section-card sc-card-orange">
      <div class="sc-icon sc-orange"><i class="fas fa-exclamation-triangle"></i></div>
      <div>
        <p class="sc-title">Sanciones</p>
        <p class="sc-desc">Crear, revisar y eliminar sanciones a usuarios por incumplimiento de normas.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="sc-stat" style="background:#ffedd5;color:#c2410c;"><?php echo $sanciones24h; ?> hoy</span>
        <?php if($noVistas>0): ?><span class="sc-stat" style="background:#fee2e2;color:#b91c1c;"><?php echo $noVistas; ?> sin ver</span><?php endif; ?>
        <span class="sc-arrow"><i class="fas fa-arrow-right"></i></span>
      </div>
    </a>
    <a href="administradores.php" class="section-card sc-card-purple">
      <div class="sc-icon sc-purple"><i class="fas fa-users-cog"></i></div>
      <div>
        <p class="sc-title">Administradores</p>
        <p class="sc-desc">Gestion de administradores: aprobar, bloquear, enviar mensajes y cambiar roles.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="sc-stat" style="background:#ede9fe;color:#5b21b6;"><?php echo $totAdmins; ?> admins</span>
        <?php if($pendAdmins>0): ?><span class="sc-stat" style="background:#fef3c7;color:#92400e;"><?php echo $pendAdmins; ?> pend.</span><?php endif; ?>
        <span class="sc-arrow"><i class="fas fa-arrow-right"></i></span>
      </div>
    </a>
  </div>

  <!-- Grafica de actividad 24h -->
  <div class="chart-box">
    <div class="chart-title"><i class="fas fa-chart-bar" style="color:#06b6d4;"></i> Actividad por hora | ultimas 24 horas</div>
    <canvas id="chartActividad" height="90"></canvas>
  </div>

  <!-- Actividad reciente -->
  <div class="activity-grid">
    <div class="activity-panel">
      <h6><i class="fas fa-video" style="color:#2563eb;"></i> Capturas recientes 24h</h6>
      <?php if(empty($recCap)): ?><div style="color:#94a3b8;font-size:.85rem;text-align:center;padding:1rem;">Sin capturas en las ultimas 24h</div>
      <?php else: foreach($recCap as $c): ?>
      <div class="act-item">
        <div class="act-dot dot-blue" style="<?php echo floatval($c['probabilidad'])>0.8?'background:#ef4444':''; ?>"></div>
        <div class="act-text">
          <strong><?php echo htmlspecialchars($c['placa']??'N/A'); ?></strong> | <?php echo htmlspecialchars($c['ubicacion']??'Sin ubicacion'); ?>
          <span style="font-size:.75rem;color:#94a3b8;display:block;"><?php echo htmlspecialchars($c['tipo_vehiculo']??''); ?></span>
        </div>
        <span class="act-time"><?php echo date('H:i',strtotime($c['creado_en'])); ?></span>
      </div>
      <?php endforeach; endif; ?>
      <div style="text-align:right;margin-top:.5rem;"><a href="monitoreo.php" style="font-size:.8rem;color:#2563eb;">Ver todo <i class="fas fa-arrow-right"></i></a></div>
    </div>
    <div class="activity-panel">
      <h6><i class="fas fa-box-open" style="color:#10b981;"></i> Depositos recientes 24h</h6>
      <?php if(empty($recDep)): ?><div style="color:#94a3b8;font-size:.85rem;text-align:center;padding:1rem;">Sin depositos en las ultimas 24h</div>
      <?php else: foreach($recDep as $d): ?>
      <div class="act-item">
        <div class="act-dot dot-green"></div>
        <div class="act-text">
          <strong><?php echo htmlspecialchars($d['usuario']??'Usuario'); ?></strong> | <?php echo htmlspecialchars($d['codigo_contenedor']??'Contenedor'); ?>
          <span style="font-size:.75rem;color:#94a3b8;display:block;"><?php echo $d['peso']!==null?number_format(floatval($d['peso']),2).'kg':''; ?> | <?php echo $d['credito_kwh']!==null?number_format(floatval($d['credito_kwh']),3).' kWh':''; ?></span>
        </div>
        <span class="act-time"><?php echo date('H:i',strtotime($d['ts'])); ?></span>
      </div>
      <?php endforeach; endif; ?>
      <div style="text-align:right;margin-top:.5rem;"><a href="depositos.php" style="font-size:.8rem;color:#10b981;">Ver todo <i class="fas fa-arrow-right"></i></a></div>
    </div>
    <div class="activity-panel">
      <h6><i class="fas fa-exclamation-triangle" style="color:#f97316;"></i> Sanciones recientes 24h</h6>
      <?php if(empty($recSanc)): ?><div style="color:#94a3b8;font-size:.85rem;text-align:center;padding:1rem;">Sin sanciones en las ultimas 24h</div>
      <?php else: foreach($recSanc as $s): ?>
      <div class="act-item">
        <div class="act-dot" style="background:<?php echo intval($s['seen_by_admin'])?'#10b981':'#ef4444'; ?>;"></div>
        <div class="act-text">
          <strong><?php echo htmlspecialchars($s['usuario']??'Usuario'); ?></strong><br>
          <span style="font-size:.75rem;"><?php echo htmlspecialchars(substr($s['descripcion'],0,50)); ?></span>
        </div>
        <span class="act-time"><?php echo date('H:i',strtotime($s['creado_en'])); ?></span>
      </div>
      <?php endforeach; endif; ?>
      <div style="text-align:right;margin-top:.5rem;"><a href="sanciones.php" style="font-size:.8rem;color:#f97316;">Ver todo <i class="fas fa-arrow-right"></i></a></div>
    </div>
  </div>

  <!-- Logs del sistema -->
  <?php if(!empty($logs)): ?>
  <div class="log-panel">
    <h6 style="font-weight:700;color:#1e293b;margin-bottom:.75rem;"><i class="fas fa-list text-secondary"></i> Logs del sistema | ultimas 24h</h6>
    <?php foreach($logs as $lg): ?>
    <div class="log-entry">
      <span class="badge-tipo tipo-<?php echo htmlspecialchars($lg['tipo']); ?>"><?php echo strtoupper(htmlspecialchars($lg['tipo'])); ?></span>
      <span style="margin-left:.5rem;"><?php echo htmlspecialchars($lg['descripcion']); ?></span>
      <span style="float:right;color:#94a3b8;font-size:.75rem;"><?php echo date('H:i:s',strtotime($lg['creado_en'])); ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/PRERMI/web/assets/js/theme.js"></script>
<script>
const hrLabels = <?php echo json_encode($hrLabels); ?>;
const hrD      = <?php echo json_encode($hrD); ?>;
const hrC      = <?php echo json_encode($hrC); ?>;
const ctx = document.getElementById('chartActividad');
if(ctx){
  new Chart(ctx,{
    type:'bar',
    data:{
      labels:hrLabels,
      datasets:[
        {label:'Depositos',data:hrD,backgroundColor:'rgba(16,185,129,.65)',borderColor:'#10b981',borderWidth:1.5,borderRadius:4,yAxisID:'y'},
        {label:'Capturas',data:hrC,type:'line',borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.1)',pointBackgroundColor:'#2563eb',pointRadius:3,borderWidth:2,fill:true,tension:.35,yAxisID:'y'}
      ]
    },
    options:{
      responsive:true,
      interaction:{mode:'index',intersect:false},
      plugins:{legend:{labels:{color:'#94a3b8',font:{size:12}}}},
      scales:{
        x:{ticks:{color:'#94a3b8',maxTicksLimit:12},grid:{color:'rgba(255,255,255,.05)'}},
        y:{ticks:{color:'#94a3b8',stepSize:1},grid:{color:'rgba(255,255,255,.07)'}}
      }
    }
  });
}
// Auto-refresh cada 2 minutos
setTimeout(()=>location.reload(), 120000);
</script>
</body>
</html>
