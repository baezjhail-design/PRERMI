<?php
/**
 * sanciones.php � Gestion de Sanciones
 * PRERMI Admin � Pagina independiente de sanciones
 */
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: loginA.php"); exit; }
require_once __DIR__ . '/../../api/utils.php';

$pdo = getPDO();
$stA = $pdo->prepare("SELECT id,usuario,email,rol FROM usuarios_admin WHERE id=? LIMIT 1");
$stA->execute([$_SESSION['admin_id']]);
$admin = $stA->fetch(PDO::FETCH_ASSOC) ?: ['usuario'=>'Admin','email'=>'','rol'=>'admin'];
$flash = null;

function tblOk2(PDO $p, string $t): bool {
    $s=$p->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $s->execute([$t]); return (bool)$s->fetchColumn();
}

try {
    $sT = tblOk2($pdo,'sanciones') ? 'sanciones' : 'multas';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear_sancion') {
            $userId       = intval($_POST['user_id']??0);
            $contenedorId = intval($_POST['contenedor_id']??0);
            $descripcion  = trim($_POST['descripcion']??'Sancion administrativa');
            $pesoRaw      = trim($_POST['peso']??'');
            $peso         = $pesoRaw===''?null:floatval($pesoRaw);
            if ($userId>0 && $contenedorId>0) {
                $pdo->prepare("INSERT INTO {$sT}(user_id,contenedor_id,descripcion,peso) VALUES(?,?,?,?)")
                    ->execute([$userId,$contenedorId,$descripcion,$peso]);
                $pdo->prepare("INSERT INTO logs_sistema(descripcion,tipo) VALUES(?,?)")
                    ->execute(["Admin #".intval($_SESSION['admin_id'])." cre� sancion para usuario #$userId",'warning']);
                $flash=['type'=>'success','msg'=>'Sancion creada correctamente'];
            } else {
                $flash=['type'=>'danger','msg'=>'Debe seleccionar usuario y contenedor'];
            }
        }

        if ($accion === 'cambiar_visto') {
            $id=$intval=intval($_POST['id']??0); $seen=intval($_POST['seen_by_admin']??0);
            if ($id>0){$pdo->prepare("UPDATE {$sT} SET seen_by_admin=? WHERE id=?")->execute([$seen,$id]); $flash=['type'=>'info','msg'=>$seen?'Marcada como vista':'Marcada como no vista'];}
        }

        if ($accion === 'eliminar_sancion') {
            $id=intval($_POST['id']??0);
            if ($id>0){$pdo->prepare("DELETE FROM {$sT} WHERE id=?")->execute([$id]); $flash=['type'=>'danger','msg'=>'Sancion eliminada'];}
        }
    }

    $filtroUserId = intval($_GET['filtro_user_id']??0);
    $filtroVisto  = $_GET['filtro_visto']??'all';
    $reviewId     = intval($_GET['review_id']??0);

    $usuarios    = $pdo->query("SELECT id,usuario,nombre,apellido FROM usuarios ORDER BY usuario ASC")->fetchAll(PDO::FETCH_ASSOC);
    $contenedores= $pdo->query("SELECT id,codigo_contenedor,ubicacion FROM contenedores_registrados ORDER BY codigo_contenedor ASC")->fetchAll(PDO::FETCH_ASSOC);

    $sql="SELECT s.id,s.user_id,s.contenedor_id,s.descripcion,s.peso,s.creado_en,s.seen_by_admin,u.usuario,u.nombre,u.apellido FROM {$sT} s LEFT JOIN usuarios u ON u.id=s.user_id WHERE 1=1";
    $params=[];
    if ($filtroUserId>0){$sql.=" AND s.user_id=?";$params[]=$filtroUserId;}
    if ($filtroVisto==='0'||$filtroVisto==='1'){$sql.=" AND s.seen_by_admin=?";$params[]=intval($filtroVisto);}
    $sql.=" ORDER BY s.creado_en DESC LIMIT 400";
    $stSanc=$pdo->prepare($sql); $stSanc->execute($params);
    $sanciones=$stSanc->fetchAll(PDO::FETCH_ASSOC);

    $sancionDetalle=null;
    if($reviewId>0){
        $stDet=$pdo->prepare("SELECT s.*,u.usuario,u.nombre,u.apellido,c.codigo_contenedor,c.ubicacion FROM {$sT} s LEFT JOIN usuarios u ON u.id=s.user_id LEFT JOIN contenedores_registrados c ON c.id=s.contenedor_id WHERE s.id=? LIMIT 1");
        $stDet->execute([$reviewId]); $sancionDetalle=$stDet->fetch(PDO::FETCH_ASSOC);
    }

    // Stats
    $total   = count($sanciones);
    $noVistas= count(array_filter($sanciones,fn($s)=>!intval($s['seen_by_admin'])));
    $s24h    = count(array_filter($sanciones,fn($s)=>strtotime($s['creado_en'])>strtotime('-24 hours')));

    // Grafica por usuario
    $porUsuario=[]; foreach($sanciones as $s){ $u=$s['usuario']??('ID '.$s['user_id']); $porUsuario[$u]=($porUsuario[$u]??0)+1; }
    arsort($porUsuario); $porUsuario=array_slice($porUsuario,0,10,true);

    // Grafica por mes
    $porMes=[];
    foreach($sanciones as $s){$m=date('M Y',strtotime($s['creado_en'])); $porMes[$m]=($porMes[$m]??0)+1;}

} catch(Exception $e) {
    $sanciones=$usuarios=$contenedores=[]; $total=$noVistas=$s24h=0; $porUsuario=$porMes=[];
    $sancionDetalle=null;
    $flash=['type'=>'danger','msg'=>'Error: '.$e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sanciones � PRERMI Admin</title>
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
.page-header{background:linear-gradient(135deg,#92400e,#f97316);border-radius:16px;padding:1.5rem 2rem;margin-bottom:1.5rem;color:#fff;}
.page-header h1{font-size:1.5rem;font-weight:800;margin:0;}
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:#fff;border-radius:12px;padding:1.1rem;box-shadow:0 2px 12px rgba(0,0,0,.07);text-align:center;border-top:4px solid #f97316;}
.sc-val{font-size:1.8rem;font-weight:800;color:#f97316;}
.sc-lbl{font-size:.82rem;color:#64748b;}
.filtro-bar{background:#fff;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;}
.panel{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:1.5rem;overflow:hidden;}
.panel-head{padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;font-weight:700;color:#1e293b;}
.panel-body{padding:1.25rem;}
.chart-dark{background:#1e293b;border-radius:14px;padding:1.5rem;}
.chart-dark-title{color:#e2e8f0;font-weight:700;font-size:.95rem;margin-bottom:1rem;}
.table-wrap{overflow-x:auto;}
.table-wrap table thead{background:linear-gradient(135deg,#92400e,#f97316);color:#fff;}
.table-wrap table thead th{border:none;padding:.85rem;white-space:nowrap;}
.table-wrap table tbody td{padding:.75rem;vertical-align:middle;border-color:#f0f0f0;}
.table-wrap table tbody tr:hover{background:#fffbf5;}
.form-sancion label{font-weight:600;font-size:.88rem;color:#374151;}
.detalle-box{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;}
</style>
</head>
<body>
<nav class="navbar-admin">
  <a class="nav-brand" href="dashboard.php"><img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="nav-logo-img"></a>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="monitoreo.php" class="nav-link-item"><i class="fas fa-video"></i> Monitoreo</a>
    <a href="depositos.php" class="nav-link-item"><i class="fas fa-box-open"></i> Depositos</a>
    <a href="sanciones.php" class="nav-link-item active"><i class="fas fa-exclamation-triangle"></i> Sanciones</a>
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
    <h1><i class="fas fa-exclamation-triangle"></i> Gestion de Sanciones</h1>
    <p>Crear, revisar y gestionar sanciones aplicadas a los usuarios del sistema</p>
  </div>

  <!-- Stats -->
  <div class="stat-row">
    <div class="stat-card"><div class="sc-val"><?php echo $total; ?></div><div class="sc-lbl">Total sanciones</div></div>
    <div class="stat-card" style="border-top-color:#ef4444;"><div class="sc-val" style="color:#ef4444;"><?php echo $noVistas; ?></div><div class="sc-lbl">Sin revisar</div></div>
    <div class="stat-card" style="border-top-color:#2563eb;"><div class="sc-val" style="color:#2563eb;"><?php echo $s24h; ?></div><div class="sc-lbl">Hoy (24h)</div></div>
    <div class="stat-card" style="border-top-color:#10b981;"><div class="sc-val" style="color:#10b981;"><?php echo $total-$noVistas; ?></div><div class="sc-lbl">Revisadas</div></div>
  </div>

  <!-- Detalle de sancion si viene review_id -->
  <?php if($sancionDetalle): ?>
  <div class="detalle-box">
    <h6 class="mb-3"><i class="fas fa-search text-success"></i> Detalle de Sancion #<?php echo intval($sancionDetalle['id']); ?></h6>
    <div class="row g-2 mb-2">
      <div class="col-md-3"><strong>Usuario:</strong> <?php echo htmlspecialchars($sancionDetalle['usuario']??'#'.intval($sancionDetalle['user_id'])); ?></div>
      <div class="col-md-3"><strong>Contenedor:</strong> <?php echo htmlspecialchars($sancionDetalle['codigo_contenedor']??'ID '.intval($sancionDetalle['contenedor_id'])); ?></div>
      <div class="col-md-3"><strong>Ubicacion:</strong> <?php echo htmlspecialchars($sancionDetalle['ubicacion']??'N/A'); ?></div>
      <div class="col-md-3"><strong>Peso:</strong> <?php echo $sancionDetalle['peso']!==null?number_format(floatval($sancionDetalle['peso']),3).' kg':'-'; ?></div>
    </div>
    <div><strong>Descripcion:</strong> <?php echo htmlspecialchars($sancionDetalle['descripcion']); ?></div>
    <div class="mt-1"><strong>Estado:</strong> <?php echo intval($sancionDetalle['seen_by_admin'])?'<span class="badge bg-success">Vista</span>':'<span class="badge bg-warning text-dark">No vista</span>'; ?></div>
    <div class="mt-1"><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s',strtotime($sancionDetalle['creado_en'])); ?></div>
    <a href="sanciones.php" class="btn btn-sm btn-outline-secondary mt-2">Cerrar detalle</a>
  </div>
  <?php endif; ?>

  <!-- Graficas -->
  <div class="row g-3 mb-4">
    <div class="col-lg-7">
      <div class="chart-dark">
        <div class="chart-dark-title"><i class="fas fa-chart-bar" style="color:#f97316;"></i> Sanciones por usuario (top 10)</div>
        <canvas id="chartUsers" height="150"></canvas>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="chart-dark">
        <div class="chart-dark-title"><i class="fas fa-chart-pie" style="color:#7c3aed;"></i> Vistas vs No vistas</div>
        <canvas id="chartVisto" height="180"></canvas>
      </div>
    </div>
  </div>

  <!-- Crear sancion + listado -->
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="panel">
        <div class="panel-head"><i class="fas fa-plus-circle" style="color:#f97316;"></i> Crear nueva sancion</div>
        <div class="panel-body">
          <form method="POST" class="form-sancion">
            <input type="hidden" name="accion" value="crear_sancion">
            <div class="mb-3">
              <label>Usuario</label>
              <select class="form-select" name="user_id" required>
                <option value="">Seleccione usuario...</option>
                <?php foreach($usuarios as $u): ?>
                <option value="<?php echo intval($u['id']); ?>"><?php echo htmlspecialchars($u['usuario'].' - '.($u['nombre']??'').(' '.($u['apellido']??''))); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label>Contenedor</label>
              <select class="form-select" name="contenedor_id" required>
                <option value="">Seleccione contenedor...</option>
                <?php foreach($contenedores as $c): ?>
                <option value="<?php echo intval($c['id']); ?>"><?php echo htmlspecialchars(($c['codigo_contenedor']??'N/A').' - '.($c['ubicacion']??'')); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label>Descripcion</label>
              <textarea class="form-control" name="descripcion" rows="3" required placeholder="Motivo de la sancion...">Sancion administrativa por incumplimiento</textarea>
            </div>
            <div class="mb-3">
              <label>Peso (kg, opcional)</label>
              <input class="form-control" type="number" step="0.001" name="peso" placeholder="Ej: 2.500">
            </div>
            <button class="btn btn-danger w-100"><i class="fas fa-save"></i> Crear Sancion</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="panel">
        <div class="panel-head"><i class="fas fa-filter" style="color:#f97316;"></i> Filtros y listado de sanciones</div>
        <div class="panel-body pb-0">
          <form method="GET" class="row g-2 mb-3">
            <div class="col-md-5">
              <select name="filtro_user_id" class="form-select">
                <option value="0">Todos los usuarios</option>
                <?php foreach($usuarios as $u): ?>
                <option value="<?php echo intval($u['id']); ?>" <?php echo $filtroUserId===intval($u['id'])?'selected':''; ?>>
                  <?php echo htmlspecialchars($u['usuario']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <select name="filtro_visto" class="form-select">
                <option value="all" <?php echo $filtroVisto==='all'?'selected':''; ?>>Todas</option>
                <option value="0" <?php echo $filtroVisto==='0'?'selected':''; ?>>No vistas</option>
                <option value="1" <?php echo $filtroVisto==='1'?'selected':''; ?>>Vistas</option>
              </select>
            </div>
            <div class="col-md-3">
              <button class="btn btn-outline-secondary w-100"><i class="fas fa-search"></i> Filtrar</button>
            </div>
          </form>
        </div>
        <div class="table-wrap">
          <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Usuario</th><th>Descripcion</th><th>Peso</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
              <?php foreach($sanciones as $s): ?>
              <tr>
                <td><strong>#<?php echo intval($s['id']); ?></strong></td>
                <td><?php echo htmlspecialchars($s['usuario']??'Usuario #'.intval($s['user_id'])); ?></td>
                <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($s['descripcion']); ?>"><?php echo htmlspecialchars($s['descripcion']); ?></td>
                <td><?php echo $s['peso']!==null?number_format(floatval($s['peso']),3).' kg':'-'; ?></td>
                <td style="white-space:nowrap;"><?php echo date('d/m/Y H:i',strtotime($s['creado_en'])); ?></td>
                <td>
                  <?php if(intval($s['seen_by_admin'])): ?>
                  <span class="badge bg-success">Vista</span>
                  <?php else: ?>
                  <span class="badge bg-warning text-dark">No vista</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <a href="?review_id=<?php echo intval($s['id']); ?>&filtro_user_id=<?php echo $filtroUserId; ?>&filtro_visto=<?php echo urlencode($filtroVisto); ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="fas fa-eye"></i></a>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="accion" value="cambiar_visto">
                      <input type="hidden" name="id" value="<?php echo intval($s['id']); ?>">
                      <input type="hidden" name="seen_by_admin" value="<?php echo intval($s['seen_by_admin'])===1?0:1; ?>">
                      <button class="btn btn-sm btn-outline-secondary" title="Cambiar estado"><i class="fas fa-toggle-on"></i></button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar esta sancion?')">
                      <input type="hidden" name="accion" value="eliminar_sancion">
                      <input type="hidden" name="id" value="<?php echo intval($s['id']); ?>">
                      <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($sanciones)): ?>
              <tr><td colspan="7" class="text-center py-5 text-muted">No hay sanciones para este filtro</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/PRERMI/web/assets/js/theme.js"></script>
<script>
const porUsuario  = <?php echo json_encode($porUsuario); ?>;
const totalVistas = <?php echo $total-$noVistas; ?>;
const totalNoVistas= <?php echo $noVistas; ?>;

const c1=document.getElementById('chartUsers');
if(c1&&Object.keys(porUsuario).length){
  const cols=['#f97316','#ef4444','#7c3aed','#2563eb','#10b981','#06b6d4'];
  new Chart(c1,{type:'bar',data:{
    labels:Object.keys(porUsuario),
    datasets:[{label:'Sanciones',data:Object.values(porUsuario),backgroundColor:Object.keys(porUsuario).map((_,i)=>cols[i%cols.length]+'bb'),borderColor:Object.keys(porUsuario).map((_,i)=>cols[i%cols.length]),borderWidth:1.5,borderRadius:5}]
  },options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#94a3b8'}},y:{ticks:{color:'#cbd5e1',font:{size:11}}}}}});
}

const c2=document.getElementById('chartVisto');
if(c2){
  new Chart(c2,{type:'doughnut',data:{
    labels:['Vistas','No vistas'],
    datasets:[{data:[totalVistas,totalNoVistas],backgroundColor:['#10b981bb','#ef4444bb'],borderColor:['#10b981','#ef4444'],borderWidth:2}]
  },options:{responsive:true,plugins:{legend:{labels:{color:'#94a3b8'}}}}});
}
</script>
</body>
</html>
