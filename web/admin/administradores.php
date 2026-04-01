<?php
/**
 * administradores.php — Gestion de Administradores y Usuarios
 * PRERMI Admin — Pagina independiente
 */
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: loginA.php"); exit; }
require_once __DIR__ . '/../../api/utils.php';

$pdo = getPDO();
$stA = $pdo->prepare("SELECT id,usuario,email,rol FROM usuarios_admin WHERE id=? LIMIT 1");
$stA->execute([$_SESSION['admin_id']]);
$admin = $stA->fetch(PDO::FETCH_ASSOC) ?: ['usuario'=>'Admin','email'=>'','rol'=>'admin'];
$isSuperAdmin = ($admin['rol']==='superadmin');
$flash = null;

try {
    // Tabla mensajes
    $pdo->exec("CREATE TABLE IF NOT EXISTS mensajes_usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, usuario_id INT NOT NULL,
        tipo ENUM('mensaje','advertencia','ban') DEFAULT 'mensaje',
        titulo VARCHAR(200) NOT NULL, contenido TEXT NOT NULL, leido TINYINT(1) DEFAULT 0,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_mu_u(usuario_id), INDEX idx_mu_a(admin_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Columna activo
    $ck=$pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='activo'");
    $ck->execute(); if(!intval($ck->fetchColumn())) $pdo->exec("ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");

    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $accion=$_POST['accion']??'';

        if (in_array($accion,['aprobar_admin','rechazar_admin','cambiar_rol_admin'],true)) {
            if (!$isSuperAdmin) { $flash=['type'=>'danger','msg'=>'Solo superadmins pueden gestionar admins']; }
            else {
                $tId=intval($_POST['admin_id']??0);
                if ($tId>0 && $tId!==intval($_SESSION['admin_id'])) {
                    if ($accion==='aprobar_admin') {
                        $pdo->prepare("UPDATE usuarios_admin SET active=1 WHERE id=?")->execute([$tId]);
                        $stDat=$pdo->prepare("SELECT usuario,email FROM usuarios_admin WHERE id=? LIMIT 1"); $stDat->execute([$tId]); $d=$stDat->fetch(PDO::FETCH_ASSOC);
                        if($d&&!empty($d['email'])){ require_once __DIR__.'/../../config/mailer.php'; if(function_exists('sendWelcomeEmail')) sendWelcomeEmail($d['email'],$d['usuario'],'admin_approved'); }
                        $pdo->prepare("INSERT INTO logs_sistema(descripcion,tipo) VALUES(?,?)")->execute(["Superadmin #".intval($_SESSION['admin_id'])." aprobó admin #$tId",'info']);
                        $flash=['type'=>'success','msg'=>'Administrador aprobado'];
                    } elseif ($accion==='rechazar_admin') {
                        $pdo->prepare("UPDATE usuarios_admin SET active=0 WHERE id=?")->execute([$tId]);
                        $flash=['type'=>'warning','msg'=>'Administrador desactivado'];
                    } elseif ($accion==='cambiar_rol_admin') {
                        $rol=(($_POST['nuevo_rol']??'')==='superadmin')?'superadmin':'admin';
                        $pdo->prepare("UPDATE usuarios_admin SET rol=? WHERE id=?")->execute([$rol,$tId]);
                        $flash=['type'=>'info','msg'=>'Rol actualizado'];
                    }
                } elseif($tId===intval($_SESSION['admin_id'])){$flash=['type'=>'warning','msg'=>'No puedes modificar tu propia cuenta'];}
            }
        }

        if ($accion==='ban_usuario') {
            $uid=intval($_POST['usuario_id']??0);
            if ($uid>0) {
                $sU=$pdo->prepare("SELECT email,nombre,apellido,usuario FROM usuarios WHERE id=?"); $sU->execute([$uid]); $bU=$sU->fetch(PDO::FETCH_ASSOC);
                $pdo->prepare("UPDATE usuarios SET activo=0 WHERE id=?")->execute([$uid]);
                if($bU&&!empty($bU['email'])){ $nm=trim(($bU['nombre']??'').(' '.($bU['apellido']??'')))?: $bU['usuario']; require_once __DIR__.'/../../config/mailer.php'; if(function_exists('sendBanEmail')) sendBanEmail($bU['email'],$nm); }
                $pdo->prepare("INSERT INTO logs_sistema(descripcion,tipo) VALUES(?,?)")->execute(["Admin #".intval($_SESSION['admin_id'])." baneó usuario #$uid",'warning']);
                $flash=['type'=>'warning','msg'=>'Usuario baneado'];
            }
        }

        if ($accion==='desbanear_usuario') {
            $uid=intval($_POST['usuario_id']??0);
            if($uid>0){$pdo->prepare("UPDATE usuarios SET activo=1 WHERE id=?")->execute([$uid]); $pdo->prepare("INSERT INTO logs_sistema(descripcion,tipo) VALUES(?,?)")->execute(["Admin #".intval($_SESSION['admin_id'])." reactivó usuario #$uid",'info']); $flash=['type'=>'success','msg'=>'Usuario reactivado'];}
        }

        if ($accion==='enviar_mensaje') {
            $uid=intval($_POST['usuario_id']??0);
            $titulo=trim($_POST['titulo']??''); $contenido=trim($_POST['contenido']??'');
            $tipo=in_array($_POST['tipo']??'',['mensaje','advertencia','ban'],true)?$_POST['tipo']:'mensaje';
            if($uid>0&&$titulo!==''&&$contenido!==''){
                $pdo->prepare("INSERT INTO mensajes_usuarios(admin_id,usuario_id,tipo,titulo,contenido) VALUES(?,?,?,?,?)")->execute([intval($_SESSION['admin_id']),$uid,$tipo,$titulo,$contenido]);
                $stU=$pdo->prepare("SELECT nombre,apellido,email FROM usuarios WHERE id=? LIMIT 1"); $stU->execute([$uid]); $du=$stU->fetch(PDO::FETCH_ASSOC);
                if($du&&!empty($du['email'])){
                    $nom=htmlspecialchars(trim(($du['nombre']??'').(' '.($du['apellido']??''))));
                    $labs=['mensaje'=>'Mensaje','advertencia'=>'Advertencia','ban'=>'Aviso de Suspension'];
                    $body="<div style='font-family:sans-serif;max-width:600px;margin:auto;'><div style='background:linear-gradient(135deg,#667eea,#764ba2);padding:24px;border-radius:12px 12px 0 0;text-align:center;'><h2 style='color:white;margin:0;'>PRERMI</h2></div><div style='background:#f9fafb;padding:28px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;'><p>Estimado/a <strong>{$nom}</strong>,</p><p>".htmlspecialchars($contenido)."</p><hr><p style='color:#6b7280;font-size:12px;'>Mensaje del panel administrativo PRERMI.</p></div></div>";
                    if(function_exists('enviarCorreo')) enviarCorreo($du['email'],'[PRERMI] '.($labs[$tipo]??'Notificacion').': '.$titulo,$body);
                }
                $pdo->prepare("INSERT INTO logs_sistema(descripcion,tipo) VALUES(?,?)")->execute(["Admin #".intval($_SESSION['admin_id'])." envio mensaje '$tipo' a usuario #$uid",'info']);
                $flash=['type'=>'success','msg'=>'Mensaje enviado'];
            } else { $flash=['type'=>'danger','msg'=>'Complete titulo y contenido']; }
        }
    }

    $allAdmins    = $pdo->query("SELECT id,usuario,nombre,apellido,email,verified,active,rol,creado_en FROM usuarios_admin ORDER BY creado_en DESC")->fetchAll(PDO::FETCH_ASSOC);
    $todosUsuarios= $pdo->query("SELECT id,nombre,apellido,usuario,email,telefono,verified,COALESCE(activo,1) AS activo,creado_en FROM usuarios ORDER BY creado_en DESC")->fetchAll(PDO::FETCH_ASSOC);

    $admPend   = count(array_filter($allAdmins,fn($a)=>!intval($a['active'])));
    $admActivos= count(array_filter($allAdmins,fn($a)=>intval($a['active'])));
    $usrBan    = count(array_filter($todosUsuarios,fn($u)=>!intval($u['activo'])));
    $usrVerif  = count(array_filter($todosUsuarios,fn($u)=>intval($u['verified'])));

} catch(Exception $e) {
    $allAdmins=$todosUsuarios=[]; $admPend=$admActivos=$usrBan=$usrVerif=0;
    $flash=['type'=>'danger','msg'=>'Error: '.$e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Administradores — PRERMI Admin</title>
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
.page-header{background:linear-gradient(135deg,#5b21b6,#7c3aed);border-radius:16px;padding:1.5rem 2rem;margin-bottom:1.5rem;color:#fff;}
.page-header h1{font-size:1.5rem;font-weight:800;margin:0;}
.kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:1rem;margin-bottom:1.5rem;}
.kpi-card{border-radius:14px;padding:1.1rem;text-align:center;color:#fff;}
.kpi-v{font-size:1.9rem;font-weight:800;}
.kpi-l{font-size:.75rem;opacity:.85;text-transform:uppercase;letter-spacing:1px;}
.panel{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:1.5rem;overflow:hidden;}
.panel-head{padding:1rem 1.25rem;border-bottom:2px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;font-weight:700;color:#1e293b;}
.panel-body{padding:1.25rem;}
.mgmt-card{background:#fff;border-radius:14px;padding:1.2rem;border:1.5px solid #e8edf5;box-shadow:0 3px 12px rgba(0,0,0,.07);height:100%;transition:all .2s;}
.mgmt-card:hover{box-shadow:0 6px 24px rgba(102,126,234,.2);transform:translateY(-2px);}
.mgmt-card-pending{border-color:#f97316;}
.mgmt-card-super{border-color:#7c3aed;}
.mgmt-avatar{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff;flex-shrink:0;}
.av-super{background:linear-gradient(135deg,#667eea,#764ba2);}
.av-admin{background:linear-gradient(135deg,#2563eb,#7c3aed);}
.av-pend{background:linear-gradient(135deg,#f97316,#ea580c);}
.mgmt-avatar-sm{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;color:#fff;flex-shrink:0;}
.mgmt-name{font-weight:700;font-size:.97rem;color:#1e293b;}
.mgmt-email{font-size:.79rem;color:#64748b;word-break:break-all;}
.badge-tu{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-size:.65rem;font-weight:700;border-radius:20px;padding:2px 8px;}
.mgmt-badge{display:inline-flex;align-items:center;gap:4px;font-size:.72rem;font-weight:600;padding:3px 9px;border-radius:20px;white-space:nowrap;}
.b-ok{background:#d1fae5;color:#065f46;} .b-warn{background:#fef3c7;color:#92400e;} .b-info{background:#dbeafe;color:#1e40af;} .b-super{background:#ede9fe;color:#5b21b6;} .b-neu{background:#f1f5f9;color:#475569;}
.table-wrap{overflow-x:auto;}
.table-wrap table thead{background:linear-gradient(135deg,#5b21b6,#7c3aed);color:#fff;}
.table-wrap table thead th{border:none;padding:.85rem;white-space:nowrap;}
.table-wrap table tbody td{padding:.75rem;vertical-align:middle;border-color:#f0f0f0;}
.section-title{font-weight:800;color:#1e293b;font-size:1.1rem;padding-bottom:.5rem;border-bottom:3px solid #e8edf5;margin-bottom:1.2rem;display:flex;align-items:center;gap:.5rem;}
</style>
</head>
<body>
<nav class="navbar-admin">
  <a class="nav-brand" href="dashboard.php"><img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="nav-logo-img"></a>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="monitoreo.php" class="nav-link-item"><i class="fas fa-video"></i> Monitoreo</a>
<a href="sanciones.php" class="nav-link-item"><i class="fas fa-exclamation-triangle"></i> Sanciones</a>
    <a href="administradores.php" class="nav-link-item active"><i class="fas fa-users-cog"></i> Administradores</a>
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
    <?php echo $flash['msg']?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <div class="page-header">
    <h1><i class="fas fa-users-cog"></i> Gestion de Administradores y Usuarios</h1>
    <p>Control de accesos, roles, baneo y comunicacion con usuarios</p>
  </div>

  <!-- KPIs -->
  <div class="kpi-row">
    <div class="kpi-card" style="background:linear-gradient(135deg,#667eea,#764ba2);box-shadow:0 4px 15px rgba(102,126,234,.35);">
      <div class="kpi-v"><?php echo count($allAdmins); ?></div><div class="kpi-l">Total Admins</div>
    </div>
    <div class="kpi-card" style="background:linear-gradient(135deg,#f97316,#ea580c);box-shadow:0 4px 15px rgba(249,115,22,.35);">
      <div class="kpi-v"><?php echo $admPend; ?></div><div class="kpi-l">Admins Pendientes</div>
    </div>
    <div class="kpi-card" style="background:linear-gradient(135deg,#10b981,#065f46);box-shadow:0 4px 15px rgba(16,185,129,.35);">
      <div class="kpi-v"><?php echo count($todosUsuarios); ?></div><div class="kpi-l">Total Usuarios</div>
    </div>
    <div class="kpi-card" style="background:linear-gradient(135deg,#ef4444,#b91c1c);box-shadow:0 4px 15px rgba(239,68,68,.35);">
      <div class="kpi-v"><?php echo $usrBan; ?></div><div class="kpi-l">Usuarios Baneados</div>
    </div>
    <div class="kpi-card" style="background:linear-gradient(135deg,#0e7490,#06b6d4);box-shadow:0 4px 15px rgba(6,182,212,.35);">
      <div class="kpi-v"><?php echo $usrVerif; ?></div><div class="kpi-l">Usuarios Verificados</div>
    </div>
  </div>

  <!-- ADMINS -->
  <div id="pendientes">
  <?php if($isSuperAdmin): ?>
  <div class="panel">
    <div class="panel-head" style="background:#faf8ff;border-bottom-color:#ddd6fe;">
      <i class="fas fa-user-shield" style="color:#7c3aed;"></i>
      <span>Administradores del Sistema</span>
      <span class="ms-auto badge" style="background:#7c3aed;color:#fff;"><?php echo count($allAdmins); ?></span>
    </div>
    <div class="panel-body">
      <div class="row g-3">
        <?php foreach($allAdmins as $ae):
          $pend=!intval($ae['active']); $isSelf=(intval($ae['id'])===intval($_SESSION['admin_id']));
          $verif=intval($ae['verified']); $initials=strtoupper(substr($ae['usuario']??'A',0,2));
          $cardCls=$isSelf?'':(($pend?'mgmt-card-pending':($ae['rol']==='superadmin'?'mgmt-card-super':'')));
          $avCls=$ae['rol']==='superadmin'?'av-super':($pend?'av-pend':'av-admin');
        ?>
        <div class="col-xl-4 col-lg-6">
          <div class="mgmt-card <?php echo $cardCls; ?>">
            <div class="d-flex align-items-start gap-3 mb-3">
              <div class="mgmt-avatar <?php echo $avCls; ?>"><?php echo htmlspecialchars($initials); ?></div>
              <div class="flex-grow-1">
                <div class="mgmt-name d-flex align-items-center gap-2 flex-wrap">
                  <?php echo htmlspecialchars($ae['usuario']); ?>
                  <?php if($isSelf): ?><span class="badge-tu">TU</span><?php endif; ?>
                </div>
                <div class="mgmt-email"><?php echo htmlspecialchars($ae['email']); ?></div>
                <div style="font-size:.75rem;color:#94a3b8;margin-top:.2rem;"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y',strtotime($ae['creado_en'])); ?></div>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-3">
              <span class="mgmt-badge <?php echo intval($ae['active'])?'b-ok':'b-warn'; ?>"><i class="fas fa-<?php echo intval($ae['active'])?'check-circle':'clock'; ?>"></i> <?php echo intval($ae['active'])?'Activo':'Sin aprobar'; ?></span>
              <span class="mgmt-badge <?php echo $verif?'b-info':'b-warn'; ?>"><i class="fas fa-<?php echo $verif?'envelope-open-text':'envelope'; ?>"></i> <?php echo $verif?'Email OK':'Email pendiente'; ?></span>
              <span class="mgmt-badge <?php echo $ae['rol']==='superadmin'?'b-super':'b-neu'; ?>"><i class="fas fa-<?php echo $ae['rol']==='superadmin'?'crown':'user-tie'; ?>"></i> <?php echo $ae['rol']==='superadmin'?'Super Admin':'Admin'; ?></span>
            </div>
            <?php if(!$isSelf): ?>
            <div class="d-flex flex-wrap gap-2">
              <?php if($pend): ?>
              <form method="POST" class="d-inline"><input type="hidden" name="accion" value="aprobar_admin"><input type="hidden" name="admin_id" value="<?php echo intval($ae['id']); ?>"><button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Aprobar</button></form>
              <?php else: ?>
              <form method="POST" class="d-inline" onsubmit="return confirm('Desactivar a este admin?')"><input type="hidden" name="accion" value="rechazar_admin"><input type="hidden" name="admin_id" value="<?php echo intval($ae['id']); ?>"><button class="btn btn-sm btn-outline-warning"><i class="fas fa-ban"></i> Desactivar</button></form>
              <?php endif; ?>
              <form method="POST" class="d-inline" onsubmit="return confirm('Cambiar rol?')"><input type="hidden" name="accion" value="cambiar_rol_admin"><input type="hidden" name="admin_id" value="<?php echo intval($ae['id']); ?>"><input type="hidden" name="nuevo_rol" value="<?php echo $ae['rol']==='superadmin'?'admin':'superadmin'; ?>"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-<?php echo $ae['rol']==='superadmin'?'user-minus':'crown'; ?>"></i> <?php echo $ae['rol']==='superadmin'?'Quitar Super':'Dar Super'; ?></button></form>
            </div>
            <?php else: ?>
            <div class="alert alert-light py-2 mb-0 small"><i class="fas fa-info-circle text-primary"></i> Tu cuenta — no modificable aqui.</div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($allAdmins)): ?><div class="col-12"><div class="alert alert-light text-center">No hay administradores registrados</div></div><?php endif; ?>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
    <i class="fas fa-lock fa-lg"></i>
    <span>La gestion de administradores es exclusiva de <strong>superadministradores</strong>.</span>
  </div>
  <?php endif; ?>
  </div>

  <!-- USUARIOS -->
  <div class="panel">
    <div class="panel-head" style="background:#f0fdf4;border-bottom-color:#bbf7d0;">
      <i class="fas fa-users" style="color:#10b981;"></i>
      <span>Gestion de Usuarios del Sistema</span>
      <span class="ms-auto badge bg-success"><?php echo count($todosUsuarios); ?></span>
    </div>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Nombre</th><th>Usuario</th><th>Email</th><th>Telefono</th><th>Estado</th><th>Registro</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach($todosUsuarios as $usr): $banned=!intval($usr['activo']); ?>
          <tr class="<?php echo $banned?'table-danger':''; ?>">
            <td><strong>#<?php echo intval($usr['id']); ?></strong></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="mgmt-avatar-sm" style="background:<?php echo $banned?'linear-gradient(135deg,#ef4444,#b91c1c)':'linear-gradient(135deg,#667eea,#764ba2)'; ?>;"><?php echo strtoupper(substr($usr['nombre']??'U',0,1)); ?></div>
                <?php echo htmlspecialchars(trim(($usr['nombre']??'').(' '.($usr['apellido']??'')))); ?>
              </div>
            </td>
            <td><?php echo htmlspecialchars($usr['usuario']); ?></td>
            <td style="font-size:.82rem;color:#64748b;"><?php echo htmlspecialchars($usr['email']); ?></td>
            <td><?php echo htmlspecialchars($usr['telefono']??'—'); ?></td>
            <td>
              <?php if($banned): ?><span class="badge bg-danger"><i class="fas fa-ban"></i> Baneado</span>
              <?php elseif(!intval($usr['verified'])): ?><span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Sin verificar</span>
              <?php else: ?><span class="badge bg-success"><i class="fas fa-check-circle"></i> Activo</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:#94a3b8;"><?php echo date('d/m/Y',strtotime($usr['creado_en'])); ?></td>
            <td>
              <div class="d-flex flex-wrap gap-1">
                <button class="btn btn-sm btn-primary"
                  data-uid="<?php echo intval($usr['id']); ?>"
                  data-unombre="<?php echo htmlspecialchars(trim(($usr['nombre']??'').(' '.($usr['apellido']??''))),ENT_QUOTES); ?>"
                  data-uemail="<?php echo htmlspecialchars($usr['email'],ENT_QUOTES); ?>"
                  onclick="openMsgModal(this)" title="Enviar mensaje">
                  <i class="fas fa-envelope"></i>
                </button>
                <?php if($banned): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="accion" value="desbanear_usuario">
                  <input type="hidden" name="usuario_id" value="<?php echo intval($usr['id']); ?>">
                  <button class="btn btn-sm btn-success"><i class="fas fa-user-check"></i></button>
                </form>
                <?php else: ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Banear a este usuario?')">
                  <input type="hidden" name="accion" value="ban_usuario">
                  <input type="hidden" name="usuario_id" value="<?php echo intval($usr['id']); ?>">
                  <button class="btn btn-sm btn-danger"><i class="fas fa-user-slash"></i></button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($todosUsuarios)): ?><tr><td colspan="8" class="text-center py-5 text-muted">No hay usuarios registrados</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal mensaje -->
<div class="modal fade" id="mensajeModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header" id="msgModalHeader" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;">
      <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Enviar Mensaje al Usuario</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <input type="hidden" name="accion" value="enviar_mensaje">
      <input type="hidden" name="usuario_id" id="msg_uid">
      <div class="modal-body">
        <div class="alert alert-light border d-flex align-items-center gap-3 py-2 mb-3">
          <div class="mgmt-avatar-sm" style="background:linear-gradient(135deg,#667eea,#764ba2);"><i class="fas fa-user"></i></div>
          <span>Enviando a: <strong id="msg_nombre">—</strong> &nbsp;<span class="small text-muted">&lt;<span id="msg_email"></span>&gt;</span></span>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Tipo</label>
          <select class="form-select" name="tipo" id="msg_tipo" onchange="updMsgColor(this)">
            <option value="mensaje">Mensaje General</option>
            <option value="advertencia">Advertencia Oficial</option>
            <option value="ban">Aviso de Suspension</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Titulo</label>
          <input type="text" name="titulo" class="form-control" required placeholder="Asunto del mensaje...">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Contenido</label>
          <textarea name="contenido" class="form-control" rows="5" required placeholder="Escriba el mensaje aqui..."></textarea>
        </div>
        <div class="alert alert-info d-flex gap-2 py-2 mb-0">
          <i class="fas fa-mail-bulk mt-1"></i>
          <small>El mensaje sera entregado como <strong>notificacion en la app</strong> y por <strong>correo electronico</strong>.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button>
      </div>
    </form>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/PRERMI/web/assets/js/theme.js"></script>
<script>
function openMsgModal(btn){
  document.getElementById('msg_uid').value=btn.dataset.uid;
  document.getElementById('msg_nombre').textContent=btn.dataset.unombre;
  document.getElementById('msg_email').textContent=btn.dataset.uemail;
  document.getElementById('msg_tipo').value='mensaje'; updMsgColor(document.getElementById('msg_tipo'));
  new bootstrap.Modal(document.getElementById('mensajeModal')).show();
}
function updMsgColor(sel){
  const h=document.getElementById('msgModalHeader');
  const c={mensaje:'linear-gradient(135deg,#667eea,#764ba2)',advertencia:'linear-gradient(135deg,#f97316,#ea580c)',ban:'linear-gradient(135deg,#ef4444,#b91c1c)'};
  h.style.background=c[sel.value]||c.mensaje;
}
// Scroll a pendientes si hay hash
if(location.hash==='#pendientes'){document.getElementById('pendientes').scrollIntoView({behavior:'smooth'});}
</script>
</body>
</html>
