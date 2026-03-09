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
