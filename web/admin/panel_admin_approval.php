<?php
// web/admin/panel_admin_approval.php - Panel para aprobar/rechazar nuevos admins

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: loginA.php");
    exit;
}

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

