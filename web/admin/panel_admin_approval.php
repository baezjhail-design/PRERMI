<?php
// web/admin/panel_admin_approval.php - Panel de aprobación de administradores (PRERMI)

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: loginA.php");
    exit;
}

require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../api/utils.php';

try {
    $pdo = getPDO();

    // Verificar que sea superadmin
    $stmt = $pdo->prepare("SELECT id, usuario, rol FROM usuarios_admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentAdmin || $currentAdmin['rol'] !== 'superadmin') {
        header("Location: dashboard.php");
        exit;
    }

    // Todos los admins ordenados: pendientes primero
    $stmt = $pdo->prepare(
        "SELECT id, usuario, nombre, apellido, email, verified, active, rol, creado_en
         FROM usuarios_admin
         ORDER BY active ASC, verified ASC, creado_en DESC"
    );
    $stmt->execute();
    $allAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pendientes  = array_values(array_filter($allAdmins, fn($a) => !intval($a['active'])));
    $aprobados   = array_values(array_filter($allAdmins, fn($a) => intval($a['active'])));
    $totalPend   = count($pendientes);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administradores Pendientes — PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --cyan:   #06b6d4;
            --green:  #10b981;
            --purple: #7c3aed;
            --dark:   #0f172a;
            --card-bg:#1e293b;
            --text:   #cbd5e1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--dark) 0%, #1e1b4b 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
        }

        /* ── NAVBAR ── */
        .topbar {
            background: rgba(15,23,42,.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(6,182,212,.2);
            padding: .9rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-brand {
            font-size: 1.3rem;
            font-weight: 800;
            background: linear-gradient(90deg, var(--cyan), var(--green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }
        .admin-chip {
            background: rgba(124,58,237,.25);
            border: 1px solid rgba(124,58,237,.5);
            color: #c4b5fd;
            padding: .35rem .9rem;
            border-radius: 20px;
            font-size: .83rem;
            font-weight: 600;
        }
        .btn-back-top {
            background: rgba(6,182,212,.15);
            border: 1px solid rgba(6,182,212,.4);
            color: var(--cyan);
            padding: .4rem 1.1rem;
            border-radius: 8px;
            font-size: .88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .25s;
        }
        .btn-back-top:hover {
            background: rgba(6,182,212,.3);
            color: white;
            transform: translateY(-2px);
        }

        /* ── MAIN ── */
        .main {
            max-width: 1200px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        /* ── PAGE HEADER ── */
        .page-hero {
            background: linear-gradient(135deg, rgba(124,58,237,.25) 0%, rgba(6,182,212,.15) 100%);
            border: 1px solid rgba(6,182,212,.2);
            border-radius: 16px;
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .hero-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: white;
            flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(124,58,237,.4);
        }
        .hero-text h1 { font-size: 1.8rem; font-weight: 800; color: white; }
        .hero-text p  { color: #94a3b8; font-size: .95rem; margin: .3rem 0 0; }

        /* ── STAT PILLS ── */
        .stat-row {
            display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;
        }
        .stat-pill {
            background: rgba(30,41,59,.8);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: .9rem 1.5rem;
            display: flex; align-items: center; gap: .9rem;
            flex: 1; min-width: 160px;
        }
        .stat-pill .ico {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .stat-pill .num { font-size: 1.8rem; font-weight: 800; color: white; }
        .stat-pill .lbl { font-size: .78rem; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }

        /* ── SECTION TITLE ── */
        .sec-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: white;
            margin: .5rem 0 1.2rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .sec-title .dot {
            width: 10px; height: 10px; border-radius: 50%;
        }

        /* ── ADMIN CARD ── */
        .adm-card {
            background: rgba(30,41,59,.9);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,.07);
            transition: all .25s;
            height: 100%;
        }
        .adm-card.pending-card {
            border-color: rgba(251,191,36,.35);
            background: linear-gradient(135deg, rgba(30,41,59,.95) 0%, rgba(43,28,10,.6) 100%);
        }
        .adm-card.active-card {
            border-color: rgba(16,185,129,.2);
        }
        .adm-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.35); }

        .adm-avatar {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; font-weight: 800; color: white;
            flex-shrink: 0;
        }
        .av-super  { background: linear-gradient(135deg, var(--purple), var(--cyan)); }
        .av-admin  { background: linear-gradient(135deg, var(--cyan), var(--green)); }
        .av-pend   { background: linear-gradient(135deg, #f97316, #dc2626); }

        .adm-name  { font-size: 1rem; font-weight: 700; color: white; }
        .adm-user  { font-size: .8rem; color: #94a3b8; }
        .adm-email { font-size: .82rem; color: #64748b; margin-top: .2rem; }
        .adm-date  { font-size: .78rem; color: #475569; margin-top: .3rem; }

        .status-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .25rem .75rem; border-radius: 20px;
            font-size: .76rem; font-weight: 700;
        }
        .sb-ok    { background: rgba(16,185,129,.18); color: #34d399; border: 1px solid rgba(16,185,129,.3); }
        .sb-pend  { background: rgba(251,191,36,.15); color: #fbbf24; border: 1px solid rgba(251,191,36,.3); }
        .sb-email { background: rgba(6,182,212,.15);  color: #22d3ee; border: 1px solid rgba(6,182,212,.3); }
        .sb-no-email { background: rgba(100,116,139,.15); color: #94a3b8; border: 1px solid rgba(100,116,139,.3); }
        .sb-super { background: rgba(124,58,237,.2); color: #a78bfa; border: 1px solid rgba(124,58,237,.4); }
        .sb-admin { background: rgba(6,182,212,.12); color: #67e8f9; border: 1px solid rgba(6,182,212,.25); }

        /* ── BUTTONS ── */
        .btn-aprob {
            background: linear-gradient(135deg, var(--green), #065f46);
            color: white; border: none; border-radius: 9px;
            padding: .5rem 1.1rem; font-size: .85rem; font-weight: 700;
            cursor: pointer; transition: all .2s;
            display: inline-flex; align-items: center; gap: .4rem;
        }
        .btn-aprob:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16,185,129,.45); }

        .btn-force {
            background: linear-gradient(135deg, var(--cyan), #0e7490);
            color: white; border: none; border-radius: 9px;
            padding: .5rem 1.1rem; font-size: .85rem; font-weight: 700;
            cursor: pointer; transition: all .2s;
            display: inline-flex; align-items: center; gap: .4rem;
        }
        .btn-force:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(6,182,212,.45); }

        .btn-reject {
            background: rgba(239,68,68,.15);
            color: #f87171; border: 1px solid rgba(239,68,68,.3);
            border-radius: 9px; padding: .5rem 1.1rem;
            font-size: .85rem; font-weight: 700;
            cursor: pointer; transition: all .2s;
            display: inline-flex; align-items: center; gap: .4rem;
        }
        .btn-reject:hover { background: rgba(239,68,68,.3); transform: translateY(-2px); }

        .btn-deact {
            background: rgba(245,158,11,.12);
            color: #fbbf24; border: 1px solid rgba(245,158,11,.3);
            border-radius: 9px; padding: .42rem .95rem;
            font-size: .82rem; font-weight: 600;
            cursor: pointer; transition: all .2s;
            display: inline-flex; align-items: center; gap: .4rem;
        }
        .btn-deact:hover { background: rgba(245,158,11,.25); }

        /* ── ALERT BANNER ── */
        .alert-pending-banner {
            background: linear-gradient(135deg, rgba(251,191,36,.12), rgba(249,115,22,.1));
            border: 1px solid rgba(251,191,36,.3);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            color: #fbbf24;
            font-size: .92rem;
        }
        .alert-pending-banner .pulse {
            width: 12px; height: 12px; border-radius: 50%;
            background: #f97316; flex-shrink: 0;
            animation: pulse-anim 1.5s infinite;
        }
        @keyframes pulse-anim {
            0%,100% { box-shadow: 0 0 0 0 rgba(249,115,22,.6); }
            50%      { box-shadow: 0 0 0 8px rgba(249,115,22,0); }
        }

        /* ── EMPTY STATE ── */
        .empty-box {
            background: rgba(30,41,59,.6);
            border: 1px dashed rgba(255,255,255,.12);
            border-radius: 16px;
            padding: 3.5rem 2rem;
            text-align: center;
        }
        .empty-box i { font-size: 3rem; color: #334155; margin-bottom: 1rem; }
        .empty-box p { color: #475569; font-size: .95rem; }

        /* ── TOAST ── */
        #prToast {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            padding: .85rem 1.4rem; border-radius: 12px;
            font-size: .9rem; font-weight: 600;
            z-index: 9999; display: none;
            animation: slideUp .3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .toast-ok  { background: rgba(16,185,129,.9); color: white; box-shadow: 0 8px 24px rgba(16,185,129,.4); }
        .toast-err { background: rgba(239,68,68,.9);  color: white; box-shadow: 0 8px 24px rgba(239,68,68,.4); }

        @media (max-width: 768px) {
            .main { padding: 0 .8rem; }
            .page-hero { padding: 1.2rem; }
            .hero-text h1 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <a class="topbar-brand" href="dashboard.php">
            <i class="fas fa-shield-alt" style="-webkit-text-fill-color:initial;color:var(--cyan);"></i> PRERMI
        </a>
        <div class="topbar-right">
            <span class="admin-chip">
                <i class="fas fa-crown"></i> <?php echo htmlspecialchars($currentAdmin['usuario']); ?> — Superadmin
            </span>
            <a href="dashboard.php" class="btn-back-top">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="main">

        <!-- HERO HEADER -->
        <div class="page-hero">
            <div class="hero-icon"><i class="fas fa-user-shield"></i></div>
            <div class="hero-text">
                <h1>Panel de Aprobación de Administradores</h1>
                <p>Gestiona quién puede acceder al sistema como administrador. Solo tú, como superadministrador, puedes aprobar accesos.</p>
            </div>
        </div>

        <!-- KPI PILLS -->
        <?php
        $totalTodos  = count($allAdmins);
        $totalActivos = count($aprobados);
        $totalPendientes = count($pendientes);
        ?>
        <div class="stat-row">
            <div class="stat-pill">
                <div class="ico" style="background:rgba(124,58,237,.2);color:#a78bfa;"><i class="fas fa-users-cog"></i></div>
                <div><div class="num"><?php echo $totalTodos; ?></div><div class="lbl">Total Admins</div></div>
            </div>
            <div class="stat-pill">
                <div class="ico" style="background:rgba(251,191,36,.15);color:#fbbf24;"><i class="fas fa-clock"></i></div>
                <div><div class="num" style="color:#fbbf24;"><?php echo $totalPendientes; ?></div><div class="lbl">Pendientes</div></div>
            </div>
            <div class="stat-pill">
                <div class="ico" style="background:rgba(16,185,129,.15);color:#34d399;"><i class="fas fa-check-circle"></i></div>
                <div><div class="num" style="color:#34d399;"><?php echo $totalActivos; ?></div><div class="lbl">Aprobados</div></div>
            </div>
        </div>

        <!-- ALERTA SI HAY PENDIENTES -->
        <?php if ($totalPendientes > 0): ?>
        <div class="alert-pending-banner">
            <div class="pulse"></div>
            <div>
                <strong><?php echo $totalPendientes; ?> administrador<?php echo $totalPendientes > 1 ? 'es' : ''; ?> esperando aprobación.</strong>
                Revisa y activa las cuentas verificadas o fuerza la activación de las no verificadas.
            </div>
        </div>
        <?php endif; ?>

        <!-- ═══════════════ PENDIENTES ═══════════════ -->
        <div class="sec-title">
            <div class="dot" style="background:#fbbf24;"></div>
            Pendientes de Aprobación
            <?php if ($totalPendientes > 0): ?>
            <span class="status-badge sb-pend"><?php echo $totalPendientes; ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($pendientes)): ?>
        <div class="empty-box mb-5">
            <i class="fas fa-check-double"></i>
            <p>No hay administradores pendientes de aprobación.<br>¡Todo está al día!</p>
        </div>
        <?php else: ?>
        <div class="row g-3 mb-5">
            <?php foreach ($pendientes as $adm): ?>
            <?php
            $ini = strtoupper(substr($adm['usuario'] ?? 'A', 0, 2));
            $verified = intval($adm['verified']);
            $isSelf = intval($adm['id']) === intval($_SESSION['admin_id']);
            ?>
            <div class="col-xl-4 col-lg-6" id="card-<?php echo intval($adm['id']); ?>">
                <div class="adm-card pending-card">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="adm-avatar av-pend"><?php echo htmlspecialchars($ini); ?></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="adm-name"><?php echo htmlspecialchars(($adm['nombre'] ?? '') . ' ' . ($adm['apellido'] ?? '')); ?></div>
                            <div class="adm-user">@<?php echo htmlspecialchars($adm['usuario']); ?></div>
                            <div class="adm-email"><i class="fas fa-envelope fa-xs"></i> <?php echo htmlspecialchars($adm['email']); ?></div>
                            <div class="adm-date"><i class="fas fa-calendar-alt fa-xs"></i> Registrado <?php echo date('d/m/Y H:i', strtotime($adm['creado_en'])); ?></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="status-badge sb-pend"><i class="fas fa-clock fa-xs"></i> Sin aprobar</span>
                        <?php if ($verified): ?>
                        <span class="status-badge sb-email"><i class="fas fa-envelope-open-text fa-xs"></i> Email verificado</span>
                        <?php else: ?>
                        <span class="status-badge sb-no-email"><i class="fas fa-envelope fa-xs"></i> Email pendiente</span>
                        <?php endif; ?>
                        <span class="status-badge <?php echo $adm['rol'] === 'superadmin' ? 'sb-super' : 'sb-admin'; ?>">
                            <i class="fas fa-<?php echo $adm['rol'] === 'superadmin' ? 'crown' : 'user-tie'; ?> fa-xs"></i>
                            <?php echo $adm['rol'] === 'superadmin' ? 'Superadmin' : 'Admin'; ?>
                        </span>
                    </div>

                    <?php if (!$isSelf): ?>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <?php if ($verified): ?>
                        <button class="btn-aprob" onclick="actionAdmin(<?php echo intval($adm['id']); ?>, 'approve', '<?php echo htmlspecialchars($adm['usuario'], ENT_QUOTES); ?>')">
                            <i class="fas fa-check"></i> Aprobar acceso
                        </button>
                        <?php else: ?>
                        <button class="btn-force" onclick="actionAdmin(<?php echo intval($adm['id']); ?>, 'force_activate', '<?php echo htmlspecialchars($adm['usuario'], ENT_QUOTES); ?>')">
                            <i class="fas fa-user-check"></i> Activar cuenta
                        </button>
                        <?php endif; ?>
                        <button class="btn-reject" onclick="actionAdmin(<?php echo intval($adm['id']); ?>, 'reject', '<?php echo htmlspecialchars($adm['usuario'], ENT_QUOTES); ?>')">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.82rem;color:#64748b;padding:.5rem 0;">
                        <i class="fas fa-info-circle"></i> Esta es tu cuenta — no modificable aquí.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ═══════════════ APROBADOS ═══════════════ -->
        <div class="sec-title">
            <div class="dot" style="background:#34d399;"></div>
            Administradores Activos
            <span class="status-badge sb-ok"><?php echo $totalActivos; ?></span>
        </div>

        <?php if (empty($aprobados)): ?>
        <div class="empty-box mb-5">
            <i class="fas fa-users"></i>
            <p>No hay administradores aprobados todavía.</p>
        </div>
        <?php else: ?>
        <div class="row g-3 mb-5">
            <?php foreach ($aprobados as $adm): ?>
            <?php
            $ini    = strtoupper(substr($adm['usuario'] ?? 'A', 0, 2));
            $isSelf = intval($adm['id']) === intval($_SESSION['admin_id']);
            $avCls  = $adm['rol'] === 'superadmin' ? 'av-super' : 'av-admin';
            ?>
            <div class="col-xl-4 col-lg-6" id="card-<?php echo intval($adm['id']); ?>">
                <div class="adm-card active-card">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="adm-avatar <?php echo $avCls; ?>"><?php echo htmlspecialchars($ini); ?></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="adm-name d-flex align-items-center gap-2 flex-wrap">
                                <?php echo htmlspecialchars(($adm['nombre'] ?? '') . ' ' . ($adm['apellido'] ?? '')); ?>
                                <?php if ($isSelf): ?><span class="status-badge" style="background:rgba(6,182,212,.2);color:#22d3ee;border:1px solid rgba(6,182,212,.4);font-size:.7rem;padding:.15rem .55rem;">TÚ</span><?php endif; ?>
                            </div>
                            <div class="adm-user">@<?php echo htmlspecialchars($adm['usuario']); ?></div>
                            <div class="adm-email"><i class="fas fa-envelope fa-xs"></i> <?php echo htmlspecialchars($adm['email']); ?></div>
                            <div class="adm-date"><i class="fas fa-calendar-alt fa-xs"></i> <?php echo date('d/m/Y', strtotime($adm['creado_en'])); ?></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="status-badge sb-ok"><i class="fas fa-check-circle fa-xs"></i> Activo</span>
                        <span class="status-badge sb-email"><i class="fas fa-envelope-open-text fa-xs"></i> Email verificado</span>
                        <span class="status-badge <?php echo $adm['rol'] === 'superadmin' ? 'sb-super' : 'sb-admin'; ?>">
                            <i class="fas fa-<?php echo $adm['rol'] === 'superadmin' ? 'crown' : 'user-tie'; ?> fa-xs"></i>
                            <?php echo $adm['rol'] === 'superadmin' ? 'Superadmin' : 'Admin'; ?>
                        </span>
                    </div>

                    <?php if (!$isSelf): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn-deact" onclick="actionAdmin(<?php echo intval($adm['id']); ?>, 'reject', '<?php echo htmlspecialchars($adm['usuario'], ENT_QUOTES); ?>')">
                            <i class="fas fa-ban"></i> Desactivar
                        </button>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.82rem;color:#64748b;">
                        <i class="fas fa-info-circle"></i> Tu cuenta — no modificable aquí.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /main -->

    <!-- TOAST -->
    <div id="prToast"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(msg, ok = true) {
            const t = document.getElementById('prToast');
            t.textContent = (ok ? '✅ ' : '❌ ') + msg;
            t.className   = ok ? 'toast-ok' : 'toast-err';
            t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, 4000);
        }

        async function actionAdmin(adminId, action, usuario) {
            const labels = {
                approve:        `¿Aprobar el acceso de ${usuario}? Se le enviará un correo de bienvenida.`,
                force_activate: `¿Activar forzosamente la cuenta de ${usuario} (marcarla como verificada y activa)?`,
                reject:         `¿Rechazar/desactivar a ${usuario}? Si está activo, se desactivará su acceso.`
            };
            if (!confirm(labels[action] || `¿Confirmar acción sobre ${usuario}?`)) return;

            try {
                const res  = await fetch('/PRERMI/api/admin/manage_admin_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_id: adminId, action })
                });
                const data = await res.json();
                if (data.success !== false && res.ok) {
                    showToast(data.message || 'Acción realizada.');
                    // Recargar página después de 1.2s para reflejar cambio
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(data.message || data.error || 'Error desconocido.', false);
                }
            } catch (err) {
                showToast('Error de conexión: ' + err.message, false);
            }
        }
    </script>
</body>
</html>


require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../api/utils.php';

// Verificar que sea superadmin
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT rol FROM usuarios_admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    
    if (!$admin || $admin['rol'] !== 'superadmin') {
        header("Location: dashboard.php");
        exit;
    }
    
    // Obtener admins pendientes
    $stmt = $pdo->prepare(
        "SELECT id, usuario, nombre, apellido, email, verified, active, rol, creado_en 
         FROM usuarios_admin 
         ORDER BY creado_en DESC"
    );
    $stmt->execute();
    $allAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administradores - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-admin {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            box-shadow: 0 5px 20px rgba(37, 99, 235, 0.3);
        }

        .navbar-admin .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .navbar-admin .nav-link {
            color: rgba(255,255,255,0.9);
            margin: 0 0.5rem;
        }

        .navbar-admin .nav-link:hover,
        .navbar-admin .nav-link.active {
            color: white;
        }

        .admin-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .container-main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: #2563eb;
        }

        .admin-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .admin-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .admin-details {
            flex: 1;
        }

        .admin-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .admin-email {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .admin-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-verified {
            background: #e7f5e7;
            color: #2b8a3e;
        }

        .badge-active {
            background: #ffe7e7;
            color: #1d4ed8;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .admin-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-approve {
            background: #51cf66;
            color: white;
        }

        .btn-approve:hover {
            background: #40c057;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #2563eb;
            color: white;
        }

        .btn-reject:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #5568d3;
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .empty-icon {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-text {
            color: #666;
            font-size: 16px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner-border-sm {
            width: 1.5rem;
            height: 1.5rem;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-admin">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt"></i> PRERMI Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-users-cog"></i> Administradores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../api/admin/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-main">
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>

        <div class="page-title">
            <i class="fas fa-users-cog"></i>
            Panel de Administradores
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number" id="totalAdmins">0</div>
                <div class="stat-label">Total de Admins</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" id="verifiedAdmins">0</div>
                <div class="stat-label">Verificados</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" id="activeAdmins">0</div>
                <div class="stat-label">Activos</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" id="pendingAdmins">0</div>
                <div class="stat-label">Pendientes</div>
            </div>
        </div>

        <!-- Loading State -->
        <div class="loading" id="loading">
            <span class="spinner-border spinner-border-sm"></span>
            Cargando...
        </div>

        <!-- Admins List -->
        <div id="adminsList">
            <!-- Populated by JavaScript -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar lista de admins
        async function loadAdmins() {
            try {
                // Mostrar cargando
                document.getElementById('loading').style.display = 'block';
                document.getElementById('adminsList').innerHTML = '';

                const response = await fetch('/PRERMI/api/admin/get_pending_admins.php');
                const data = await response.json();

                document.getElementById('loading').style.display = 'none';

                if (response.ok) {
                    renderAdmins(data.pending_admins || []);
                } else {
                    showError(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loading').style.display = 'none';
                showError('Error al cargar administradores');
            }
        }

        // Renderizar lista de admins
        function renderAdmins(admins) {
            const container = document.getElementById('adminsList');

            // Calcular estadÃ­sticas
            let total = admins.length;
            let verified = admins.filter(a => a.verified === 1).length;
            let active = admins.filter(a => a.active === 1).length;
            let pending = admins.filter(a => a.verified === 1 && a.active === 0).length;

            document.getElementById('totalAdmins').textContent = total;
            document.getElementById('verifiedAdmins').textContent = verified;
            document.getElementById('activeAdmins').textContent = active;
            document.getElementById('pendingAdmins').textContent = pending;

                if (admins.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="empty-text">No hay administradores registrados</div>
                    </div>
                `;
                return;
            }

                container.innerHTML = admins.map(admin => {
                const fecha = new Date(admin.creado_en).toLocaleDateString('es-ES');
                const verificado = admin.verified === 1;
                const activo = admin.active === 1;

                let html = `
                    <div class="admin-card">
                        <div class="admin-details">
                            <div class="admin-name">
                                <i class="fas fa-user-circle"></i> ${admin.nombre} ${admin.apellido}
                            </div>
                            <div class="admin-email">
                                <i class="fas fa-envelope"></i> ${admin.email}
                            </div>
                            <div class="admin-meta">
                                <span><strong>Usuario:</strong> ${admin.usuario}</span>
                                <span><strong>Rol:</strong> ${admin.rol}</span>
                                <span><strong>Fecha:</strong> ${fecha}</span>
                            </div>
                            <div style="margin-top: 10px; display: flex; gap: 10px;">
                                <span class="badge-status ${verificado ? 'badge-verified' : ''}">
                                    ${verificado ? '<i class="fas fa-check"></i> Verificado' : '<i class="fas fa-hourglass"></i> No Verificado'}
                                </span>
                                <span class="badge-status ${activo ? 'badge-active' : 'badge-pending'}">
                                    ${activo ? '<i class="fas fa-check-circle"></i> Activo' : '<i class="fas fa-clock"></i> Pendiente'}
                                </span>
                            </div>
                        </div>
                        <div class="admin-actions">
                `;

                // Mostrar botones segÃºn estado
                if (verificado && !activo) {
                    html += `
                        <button class="btn-action btn-approve" onclick="approveAdmin(${admin.id}, '${admin.usuario}')">
                            <i class="fas fa-check"></i> Aprobar
                        </button>
                        <button class="btn-action btn-reject" onclick="rejectAdmin(${admin.id}, '${admin.usuario}')">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    `;
                } else if (!verificado) {
                    // Mostrar botÃ³n para activar (forzar verificaciÃ³n + activar)
                    html += `
                        <button class="btn-action btn-approve" onclick="forceActivateAdmin(${admin.id}, '${admin.usuario}')">
                            <i class="fas fa-user-check"></i> Activar cuenta
                        </button>
                        <button class="btn-action btn-reject" onclick="rejectAdmin(${admin.id}, '${admin.usuario}')">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    `;
                } else {
                    html += `<span style="color: #666; font-size: 12px;">Ya aprobado</span>`;
                }

                html += `
                        </div>
                    </div>
                `;
                return html;
            }).join('');
        }

        // Aprobar admin
        async function approveAdmin(adminId, usuario) {
            if (!confirm(`Â¿Aprobar a ${usuario}?`)) return;

            try {
                const response = await fetch('/PRERMI/api/admin/manage_admin_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_id: adminId,
                        action: 'approve'
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    alert(data.message);
                    loadAdmins();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al aprobar');
            }
        }

        // Rechazar admin
        async function rejectAdmin(adminId, usuario) {
            if (!confirm(`Â¿Rechazar a ${usuario}? Se eliminarÃ¡ la solicitud.`)) return;

            try {
                const response = await fetch('/PRERMI/api/admin/manage_admin_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_id: adminId,
                        action: 'reject'
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    alert(data.message);
                    loadAdmins();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al rechazar');
            }
        }

        // Forzar activaciÃ³n: marcar verified=1 y active=1
        async function forceActivateAdmin(adminId, usuario) {
            if (!confirm(`Â¿Forzar activaciÃ³n de ${usuario}? Esto marcarÃ¡ la cuenta como verificada y activa.`)) return;

            try {
                const response = await fetch('/PRERMI/api/admin/manage_admin_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_id: adminId,
                        action: 'force_activate'
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    alert(data.message);
                    loadAdmins();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al activar');
            }
        }

        // Mostrar error
        function showError(message) {
            const container = document.getElementById('adminsList');
            container.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                </div>
            `;
        }

        // Cargar al iniciar
        loadAdmins();
    </script>
</body>
</html>

