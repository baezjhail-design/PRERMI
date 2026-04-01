<?php 
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: loginA.php");
    exit;
}

require_once __DIR__ . '/../../api/utils.php';

$adminName = 'Admin';
try {
    $pdo = getPDO();
    $stA = $pdo->prepare("SELECT usuario FROM usuarios_admin WHERE id=? LIMIT 1");
    $stA->execute([$_SESSION['admin_id']]);
    $admin = $stA->fetch(PDO::FETCH_ASSOC);
    if ($admin && !empty($admin['usuario'])) {
        $adminName = $admin['usuario'];
    }
} catch (Exception $e) {
    // No bloquea el render visual si falla la consulta de admin.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Vehiculos - PRERMI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/PRERMI/web/assets/css/style.css">
    <link rel="stylesheet" href="/PRERMI/web/assets/css/theme.css">
    <style>
        body {
            background: #f1f5f9;
        }

        .navbar-admin {
            background: linear-gradient(135deg, #1e40af 0%, #6d28d9 100%);
            padding: .75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            box-shadow: 0 4px 20px rgba(30, 64, 175, .35);
        }

        .nav-brand {
            color: #fff;
            font-weight: 800;
            text-decoration: none;
            font-size: 1.1rem;
        }

        .nav-links {
            display: flex;
            gap: .25rem;
            flex: 1;
            flex-wrap: wrap;
        }

        .nav-link-item {
            color: rgba(255, 255, 255, .85);
            padding: .45rem .8rem;
            border-radius: 7px;
            text-decoration: none;
            font-size: .88rem;
            font-weight: 600;
        }

        .nav-link-item:hover,
        .nav-link-item.active {
            color: #fff;
            background: rgba(255, 255, 255, .2);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .nav-user {
            color: #fff;
            font-weight: 600;
            font-size: .88rem;
        }

        .btn-logout {
            background: rgba(255,255,255,.16);
            color: #fff;
            border: 1px solid rgba(255,255,255,.3);
            padding: .35rem .75rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: .82rem;
        }

        .btn-logout:hover {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 14px 14px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            border-left: 4px solid #2563eb;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.2);
        }

        .stat-icon {
            font-size: 3rem;
            color: #2563eb;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #999;
            font-size: 1rem;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .vehicle-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 4px solid #2563eb;
        }

        .vehicle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.2);
        }

        .vehicle-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
        }

        .vehicle-body {
            padding: 1.5rem;
        }

        .vehicle-plate {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 1rem;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
        }

        .vehicle-info {
            margin-bottom: 0.75rem;
        }

        .info-label {
            color: #999;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: #333;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .vehicle-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .status-online {
            background: rgba(81, 207, 102, 0.1);
            color: #51cf66;
        }

        .status-offline {
            background: rgba(169, 169, 169, 0.1);
            color: #adb5bd;
        }

        .status-mantenimiento {
            background: rgba(255, 217, 61, 0.1);
            color: #e6a800;
        }

        .vehicle-actions {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            flex: 1;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            color: white;
        }

        .table tbody tr:hover {
            background: rgba(37, 99, 235, 0.05);
        }

        .catalog-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .catalog-thumb {
            width: 70px;
            height: 52px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .catalog-actions {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
        }

        .roi-editor {
            position: relative;
            width: 100%;
            height: 180px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
            overflow: hidden;
            touch-action: none;
            user-select: none;
        }

        .roi-editor-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.16) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.16) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .roi-box {
            position: absolute;
            border: 2px solid #22d3ee;
            box-shadow: inset 0 0 0 9999px rgba(0, 0, 0, 0.22);
            border-radius: 6px;
            cursor: move;
        }

        .roi-handle {
            position: absolute;
            right: -6px;
            bottom: -6px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #f8fafc;
            border: 2px solid #0891b2;
            cursor: nwse-resize;
        }

        .vehicle-image.preview-image {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .vehicle-image.preview-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.38));
        }

        .vehicle-image.preview-image i {
            position: relative;
            z-index: 1;
        }

        .vehicle-empty {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            color: #64748b;
            border: 1px dashed #cbd5e1;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: .75rem;
        }

        .gallery-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .gallery-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
        }

        .gallery-meta {
            padding: .55rem;
            font-size: .78rem;
            color: #475569;
        }

        body.dark .vehicle-empty {
            background: #1e293b;
            color: #94a3b8;
            border-color: #475569;
        }

        body.dark .gallery-card {
            background: #0f172a;
            border-color: #334155;
        }

        body.dark .gallery-meta {
            color: #cbd5e1;
        }

        .small-muted {
            color: #6b7280;
            font-size: 0.8rem;
        }

        .upload-help {
            background: #f8fafc;
            border: 1px dashed #94a3b8;
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 0.75rem;
        }

        .api-message {
            display: none;
            margin-bottom: 1rem;
        }

        .api-message.visible {
            display: block;
        }

        /* ===== DARK MODE ===== */
        body.dark {
            background: #0f172a;
            color: #e2e8f0;
        }
        body.dark .dashboard-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e1b4b 100%);
        }
        body.dark .stat-card,
        body.dark .filter-section,
        body.dark .catalog-section {
            background: #1e293b;
            box-shadow: 0 5px 20px rgba(0,0,0,.35);
            border-left-color: #3b82f6;
        }
        body.dark .stat-value { color: #f1f5f9; }
        body.dark .stat-label { color: #94a3b8; }
        body.dark .stat-icon { color: #60a5fa; }
        body.dark .form-control,
        body.dark .form-select {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }
        body.dark .form-control:focus,
        body.dark .form-select:focus {
            background: #0f172a;
            border-color: #3b82f6;
            color: #e2e8f0;
            box-shadow: 0 0 0 .2rem rgba(59,130,246,.25);
        }
        body.dark .form-label { color: #cbd5e1; }
        body.dark .upload-help {
            background: #1e293b;
            border-color: #475569;
        }
        body.dark .vehicle-card {
            background: #1e293b;
            box-shadow: 0 5px 20px rgba(0,0,0,.35);
        }
        body.dark .vehicle-card:hover {
            box-shadow: 0 15px 40px rgba(59,130,246,.25);
        }
        body.dark .info-label { color: #64748b; }
        body.dark .info-value { color: #e2e8f0; }
        body.dark .vehicle-actions { border-top-color: #334155; }
        body.dark .table-responsive {
            box-shadow: 0 5px 20px rgba(0,0,0,.35);
        }
        body.dark .table {
            color: #e2e8f0;
            border-color: #334155;
        }
        body.dark .table tbody tr {
            background: #1e293b;
            border-color: #334155;
        }
        body.dark .table tbody tr:hover { background: #263248; }
        body.dark .table thead {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
        }
        body.dark .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: #172032;
            color: #e2e8f0;
        }
        body.dark .border.rounded { border-color: #334155 !important; }
        body.dark .small-muted { color: #64748b; }
        body.dark h3 { color: #e2e8f0; }
        body.dark #previewGrid { background: #0f172a; border-color: #334155; }
        body.dark .catalog-thumb { border-color: #334155; }
        body.dark .roi-editor {
            border-color: #475569;
            background: linear-gradient(135deg, #020617 0%, #0f172a 60%, #1e293b 100%);
        }
        body.dark .roi-box {
            border-color: #22d3ee;
            box-shadow: inset 0 0 0 9999px rgba(2, 6, 23, 0.30);
        }

        /* Dark mode toggle button */
        .btn-darkmode {
            background: rgba(255,255,255,.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,.25);
            padding: .32rem .65rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: .82rem;
            transition: background .2s;
        }
        .btn-darkmode:hover { background: rgba(255,255,255,.22); }
    </style>
</head>
<body>
    <nav class="navbar-admin">
        <a class="nav-brand" href="dashboard.php">
            <i class="fas fa-truck"></i> PRERMI Admin
        </a>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="monitoreo.php" class="nav-link-item"><i class="fas fa-video"></i> Monitoreo</a>
            <a href="vehiculos.php" class="nav-link-item active"><i class="fas fa-camera"></i> Catalogo IA</a>
<a href="sanciones.php" class="nav-link-item"><i class="fas fa-exclamation-triangle"></i> Sanciones</a>
        </div>
        <div class="nav-right">
            <button class="btn-darkmode" id="darkToggle" title="Cambiar tema" onclick="toggleDark()">
                <i class="fas fa-moon" id="darkIcon"></i>
            </button>
            <span class="nav-user"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($adminName); ?></span>
            <a href="../../api/admin/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </div>
    </nav>

    <!-- HEADER -->
    <div class="dashboard-header">
        <div class="container">
            <div class="page-title">
                <i class="fas fa-car"></i>
                Gestion de Vehiculos
            </div>
            <p class="page-subtitle">Administra la flota de vehiculos del sistema</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <div class="container-fluid">
            <div id="apiMessage" class="alert alert-danger api-message" role="alert"></div>

            <!-- STATS -->
            <div class="grid-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-value" id="statVehiculosTotal">0</div>
                    <div class="stat-label">Vehiculos totales</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <div class="stat-value" id="statVehiculosActivos">0</div>
                    <div class="stat-label">Vehiculos activos</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="stat-value" id="statCapturasTotal">0</div>
                    <div class="stat-label">Capturas en catalogo</div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-filter"></i> Estado</label>
                        <select class="form-control" id="filterStatus">
                            <option value="">Todos</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-search"></i> Busqueda</label>
                        <input type="text" class="form-control" id="searchVehicle" placeholder="Placa o modelo...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-sort"></i> Tipo</label>
                        <select class="form-control" id="filterVehicleType">
                            <option value="">Todos los tipos</option>
                            <option value="accidente">Accidente</option>
                            <option value="vehiculo_empresa">Vehiculo empresa</option>
                            <option value="camion_recolector">Camion recolector</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-danger w-100" type="button" onclick="scrollToCatalogForm()">
                            <i class="fas fa-plus"></i> Nuevo vehiculo
                        </button>
                    </div>
                </div>
            </div>

            <!-- VISTA DE GRID -->
            <h3 style="margin-top: 3rem; margin-bottom: 1.5rem;">
                <i class="fas fa-th-large"></i> Vista de Grid
            </h3>

            <div class="vehicle-grid" id="vehicleGridReal">
                <div class="vehicle-empty">Cargando vehiculos registrados...</div>
            </div>

            <!-- TABLA COMPLETA -->
            <div style="margin-top: 4rem; margin-bottom: 3rem;">
                <h3 style="margin-bottom: 1.5rem;">
                    <i class="fas fa-table"></i> Vista de Tabla
                </h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Placa</th>
                                <th>Tipo</th>
                                <th>Capturas</th>
                                <th>Descripcion</th>
                                <th>Estado</th>
                                <th>Ultima captura</th>
                                <th>Vista previa</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="vehicleTableBody">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">Cargando vehiculos registrados...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CATALOGO DE DETECCION -->
            <div class="catalog-section" style="margin-bottom: 3rem;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0"><i class="fas fa-camera"></i> Catalogo de deteccion vehicular</h3>
                    <button class="btn btn-outline-primary" onclick="loadCatalogo()">
                        <i class="fas fa-rotate"></i> Refrescar
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="border rounded p-3 h-100">
                            <h5><i class="fas fa-camera"></i> Registrar vehiculo (15 imagenes)</h5>
                            <form id="catalogUploadForm">
                                <div class="mb-2">
                                    <label class="form-label">Tipo *</label>
                                    <select class="form-control" id="tipo_vehiculo" required>
                                        <option value="">Seleccionar</option>
                                        <option value="accidente">Accidente</option>
                                        <option value="vehiculo_empresa">Vehiculo empresa</option>
                                        <option value="camion_recolector">Camion recolector</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Etiqueta (identificador) *</label>
                                        <input type="text" class="form-control" id="etiqueta" maxlength="80" required placeholder="Ej: Camion-ABC-1234">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Descripcion</label>
                                    <textarea class="form-control" id="descripcion" rows="2" maxlength="255"></textarea>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Marca</label>
                                        <input type="text" class="form-control" id="marca" maxlength="80" placeholder="Ej: Toyota">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Modelo</label>
                                        <input type="text" class="form-control" id="modelo" maxlength="80" placeholder="Ej: Hilux">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Año</label>
                                        <input type="text" class="form-control" id="anio" maxlength="10" placeholder="Ej: 2023">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Color</label>
                                        <input type="text" class="form-control" id="color" maxlength="40" placeholder="Ej: Amarillo">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Placa referencia</label>
                                        <input type="text" class="form-control" id="placa_referencia" maxlength="32" placeholder="Ej: ABC-1234">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Caja de enfoque ROI</label>
                                    <div id="roiEditor" class="roi-editor" title="Arrastra para mover. Usa la esquina para redimensionar.">
                                        <div class="roi-editor-grid"></div>
                                        <div id="roiBox" class="roi-box">
                                            <div class="roi-handle" data-handle="resize"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <div class="small-muted" id="roiHint">Arrastra el cuadro como en Edge Impulse para centrar el vehiculo.</div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetRoiBox()">Reset ROI</button>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0">Imagenes del vehiculo</label>
                                        <span id="captureCount" class="badge bg-secondary">0 / 15</span>
                                    </div>
                                    <div class="progress mb-2" style="height:6px;">
                                        <div class="progress-bar bg-primary" id="captureProgress" role="progressbar" style="width:0%"></div>
                                    </div>
                                    <div id="previewGrid" style="display:grid;grid-template-columns:repeat(5,1fr);gap:4px;min-height:52px;background:#f8fafc;border-radius:6px;padding:6px;border:1px dashed #94a3b8;"></div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" id="btnAddImage" onclick="document.getElementById('imagenInput').click()">
                                            <i class="fas fa-plus"></i> Agregar imagen
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnOpenWebcam" onclick="openWebcam()" title="Capturar con camara web">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" id="btnCaptureWebcam" onclick="captureFromWebcam()" title="Tomar foto" disabled>
                                            <i class="fas fa-circle"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllImages()" title="Reiniciar imagenes">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>
                                    </div>
                                    <div id="webcamPanel" class="mt-2" style="display:none;">
                                        <video id="webcamVideo" autoplay playsinline muted style="width:100%;max-height:220px;background:#0f172a;border-radius:6px;border:1px solid #334155;"></video>
                                        <canvas id="webcamCanvas" style="display:none;"></canvas>
                                        <div class="small-muted mt-1">Tip: usa el boton <i class="fas fa-circle"></i> para capturar desde la camara sin seleccionar archivos manualmente.</div>
                                    </div>
                                    <input type="file" id="imagenInput" accept="image/jpeg,image/jpg" style="display:none" multiple>
                                </div>
                                <div class="upload-help small-muted">
                                    Se requieren exactamente 15 fotos del mismo vehiculo desde distintos angulos. Puedes subir JPG manual o capturar directo desde la camara web.
                                </div>
                                <div id="uploadProgressBox" class="mt-2" style="display:none;">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="uploadProgressBar" style="width:0%"></div>
                                    </div>
                                    <div class="text-center small-muted mt-1" id="uploadProgressText"></div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mt-3" id="btnUploadCatalogo" disabled>
                                    <i class="fas fa-cloud-arrow-up"></i> Registrar vehiculo (<span id="btnCount">0</span>/15)
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="border rounded p-3 h-100">
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <select class="form-control" id="filtroTipo" onchange="loadCatalogo()">
                                        <option value="">Todos los tipos</option>
                                        <option value="accidente">Accidente</option>
                                        <option value="vehiculo_empresa">Vehiculo empresa</option>
                                        <option value="camion_recolector">Camion recolector</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-control" id="filtroEstado" onchange="loadCatalogo()">
                                        <option value="">Todos los estados</option>
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <span class="badge bg-primary" id="catalogCounter">0 registros</span>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Imagen</th>
                                            <th>Tipo</th>
                                            <th>Etiqueta</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody id="catalogTableBody">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">Cargando catalogo...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <input type="file" id="catalogReplaceInput" accept="image/jpeg,image/jpg" style="display:none">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="vehicleGalleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vehicleGalleryTitle">Capturas del vehiculo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="vehicleGalleryMeta" class="small-muted mb-3"></div>
                    <div class="gallery-grid" id="vehicleGalleryGrid"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let catalogItemsCache = [];
        let vehicleGroupsCache = [];
        let vehicleGalleryModal = null;

        function showApiMessage(msg, level) {
            const box = document.getElementById('apiMessage');
            box.className = 'alert api-message visible ' + (level || 'alert-danger');
            box.textContent = msg;
        }

        function clearApiMessage() {
            const box = document.getElementById('apiMessage');
            box.className = 'alert api-message';
            box.textContent = '';
        }

        async function apiFetchJson(url, options) {
            const response = await fetch(url, options || {});
            const rawText = await response.text();

            let parsed = null;
            try {
                parsed = JSON.parse(rawText);
            } catch (e) {
                throw new Error('Respuesta no JSON. HTTP ' + response.status + '. Detalle: ' + rawText.slice(0, 180));
            }

            if (response.status === 401) {
                window.location.href = 'loginA.php';
                return parsed;
            }

            return parsed;
        }

        async function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const result = reader.result || '';
                    const base64 = String(result).split(',')[1] || '';
                    resolve(base64);
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        function badgeForState(state) {
            if (state === 'activo') return '<span class="badge bg-success">Activo</span>';
            if (state === 'inactivo') return '<span class="badge bg-secondary">Inactivo</span>';
            return '<span class="badge bg-dark">' + (state || 'n/a') + '</span>';
        }

        function getVehicleIcon(tipo) {
            if (tipo === 'camion_recolector') return 'fas fa-truck-moving';
            if (tipo === 'vehiculo_empresa') return 'fas fa-car-side';
            if (tipo === 'accidente') return 'fas fa-triangle-exclamation';
            return 'fas fa-car';
        }

        function getVehicleTypeLabel(tipo) {
            if (tipo === 'camion_recolector') return 'Camion recolector';
            if (tipo === 'vehiculo_empresa') return 'Vehiculo empresa';
            if (tipo === 'accidente') return 'Accidente';
            return tipo || 'Sin tipo';
        }

        function mapGroupState(group) {
            if (!group || !group.items || !group.items.length) {
                return { key: 'offline', label: 'Sin capturas', css: 'status-offline' };
            }

            const activeCount = group.items.filter(item => item.estado === 'activo').length;
            if (activeCount === group.items.length) {
                return { key: 'online', label: 'Activo', css: 'status-online' };
            }
            if (activeCount === 0) {
                return { key: 'offline', label: 'Inactivo', css: 'status-offline' };
            }
            return { key: 'mantenimiento', label: 'Mixto', css: 'status-mantenimiento' };
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function groupCatalogItems(items) {
            const grouped = new Map();
            items.forEach(item => {
                const key = (item.tipo_vehiculo || '') + '||' + (item.etiqueta || 'Sin etiqueta');
                if (!grouped.has(key)) {
                    grouped.set(key, {
                        key,
                        etiqueta: item.etiqueta || 'Sin etiqueta',
                        tipo_vehiculo: item.tipo_vehiculo || '',
                        descripcion: item.descripcion || '',
                        marca: item.marca || '',
                        modelo: item.modelo || '',
                        anio: item.anio || '',
                        color: item.color || '',
                        placa_referencia: item.placa_referencia || '',
                        items: []
                    });
                }

                const group = grouped.get(key);
                group.items.push(item);
                if (!group.descripcion && item.descripcion) {
                    group.descripcion = item.descripcion;
                }
                if (!group.marca && item.marca) group.marca = item.marca;
                if (!group.modelo && item.modelo) group.modelo = item.modelo;
                if (!group.anio && item.anio) group.anio = item.anio;
                if (!group.color && item.color) group.color = item.color;
                if (!group.placa_referencia && item.placa_referencia) group.placa_referencia = item.placa_referencia;
            });

            return Array.from(grouped.values()).map(group => {
                group.items.sort((a, b) => String(b.created_at || '').localeCompare(String(a.created_at || '')));
                group.latest = group.items[0] || null;
                group.state = mapGroupState(group);
                return group;
            }).sort((a, b) => String(b.latest?.created_at || '').localeCompare(String(a.latest?.created_at || '')));
        }

        function updateVehicleStats(groups, items) {
            document.getElementById('statVehiculosTotal').textContent = groups.length;
            document.getElementById('statVehiculosActivos').textContent = groups.filter(group => group.state.key === 'online').length;
            document.getElementById('statCapturasTotal').textContent = items.length;
        }

        function getFilteredVehicleGroups() {
            const searchValue = document.getElementById('searchVehicle').value.trim().toLowerCase();
            const statusValue = document.getElementById('filterStatus').value;
            const typeValue = document.getElementById('filterVehicleType').value;

            return vehicleGroupsCache.filter(group => {
                if (statusValue && group.state.key !== statusValue) return false;
                if (typeValue && group.tipo_vehiculo !== typeValue) return false;
                if (!searchValue) return true;

                const text = [
                    group.etiqueta,
                    group.descripcion,
                    getVehicleTypeLabel(group.tipo_vehiculo),
                    group.latest?.created_at || ''
                ].join(' ').toLowerCase();
                return text.includes(searchValue);
            });
        }

        function renderVehicleViews() {
            const groups = getFilteredVehicleGroups();
            const grid = document.getElementById('vehicleGridReal');
            const tableBody = document.getElementById('vehicleTableBody');

            if (!groups.length) {
                grid.innerHTML = '<div class="vehicle-empty">No hay vehiculos registrados para los filtros seleccionados.</div>';
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No hay vehiculos registrados para los filtros seleccionados.</td></tr>';
                return;
            }

            grid.innerHTML = groups.map(group => {
                const latest = group.latest || {};
                const previewStyle = latest.ruta_archivo
                    ? ' preview-image" style="background-image:url(\'' + latest.ruta_archivo + '\')'
                    : '"';
                return '<div class="vehicle-card">' +
                    '<div class="vehicle-image' + previewStyle + '"><i class="' + getVehicleIcon(group.tipo_vehiculo) + '"></i></div>' +
                    '<div class="vehicle-body">' +
                        '<div class="vehicle-plate">' + escapeHtml(group.etiqueta) + '</div>' +
                        '<div class="vehicle-status ' + group.state.css + '">' +
                            '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + (group.state.key === 'online' ? '#51cf66' : (group.state.key === 'mantenimiento' ? '#e6a800' : '#94a3b8')) + ';margin-right:5px;"></span>' + escapeHtml(group.state.label) +
                        '</div>' +
                        '<div class="vehicle-info"><div class="info-label">Tipo</div><div class="info-value">' + escapeHtml(getVehicleTypeLabel(group.tipo_vehiculo)) + '</div></div>' +
                        '<div class="vehicle-info"><div class="info-label">Capturas</div><div class="info-value">' + group.items.length + ' imagenes</div></div>' +
                        '<div class="vehicle-info"><div class="info-label">Ultima captura</div><div class="info-value">' + escapeHtml(latest.created_at || 'N/D') + '</div></div>' +
                        '<div class="vehicle-info"><div class="info-label">Descripcion</div><div class="info-value">' + escapeHtml(group.descripcion || 'Sin descripcion') + '</div></div>' +
                        '<div class="vehicle-info"><div class="info-label">Datos</div><div class="info-value">' + escapeHtml((group.marca || 'N/D') + ' ' + (group.modelo || '') + ' | Ano ' + (group.anio || 'N/D') + ' | ' + (group.color || 'N/D')) + '</div></div>' +
                    '</div>' +
                    '<div class="vehicle-actions">' +
                        '<button type="button" class="btn btn-sm btn-info" onclick="viewVehicleGroup(' + JSON.stringify(group.key).replace(/"/g, '&quot;') + ')"><i class="fas fa-eye"></i> Ver</button>' +
                        '<button type="button" class="btn btn-sm btn-warning" onclick="editVehicleGroup(' + JSON.stringify(group.key).replace(/"/g, '&quot;') + ')"><i class="fas fa-edit"></i> Editar</button>' +
                        '<button type="button" class="btn btn-sm btn-danger" onclick="deleteVehicleGroup(' + JSON.stringify(group.key).replace(/"/g, '&quot;') + ')"><i class="fas fa-trash"></i> Borrar</button>' +
                    '</div>' +
                '</div>';
            }).join('');

            tableBody.innerHTML = groups.map(group => {
                const latest = group.latest || {};
                return '<tr>' +
                    '<td><strong>' + escapeHtml(group.etiqueta) + '</strong></td>' +
                    '<td>' + escapeHtml(getVehicleTypeLabel(group.tipo_vehiculo)) + '</td>' +
                    '<td>' + group.items.length + '</td>' +
                    '<td>' + escapeHtml(group.descripcion || 'Sin descripcion') + '</td>' +
                    '<td><span class="' + group.state.css + '">' + escapeHtml(group.state.label) + '</span></td>' +
                    '<td>' + escapeHtml(latest.created_at || 'N/D') + '</td>' +
                    '<td>' + (latest.ruta_archivo ? '<img class="catalog-thumb" src="' + latest.ruta_archivo + '" alt="preview">' : '<span class="small-muted">Sin imagen</span>') + '</td>' +
                    '<td>' +
                        '<button type="button" class="btn btn-sm btn-info" onclick="viewVehicleGroup(' + JSON.stringify(group.key).replace(/"/g, '&quot;') + ')"><i class="fas fa-eye"></i></button> ' +
                        '<button type="button" class="btn btn-sm btn-warning" onclick="editVehicleGroup(' + JSON.stringify(group.key).replace(/"/g, '&quot;') + ')"><i class="fas fa-edit"></i></button> ' +
                        '<button type="button" class="btn btn-sm btn-danger" onclick="deleteVehicleGroup(' + JSON.stringify(group.key).replace(/"/g, '&quot;') + ')"><i class="fas fa-trash"></i></button>' +
                    '</td>' +
                '</tr>';
            }).join('');
        }

        function findVehicleGroup(groupKey) {
            return vehicleGroupsCache.find(group => group.key === groupKey) || null;
        }

        function buildBulkUpdatePayload(group, etiqueta, descripcion) {
            return group.items.map(item => ({
                id: item.id,
                etiqueta,
                descripcion
            }));
        }

        async function runBulkCatalogUpdate(entries) {
            for (const entry of entries) {
                const data = await apiFetchJson('/PRERMI/api/vehiculos/manage_catalogo.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(entry)
                });
                if (!data.success) {
                    throw new Error(data.msg || 'No se pudo actualizar el catalogo');
                }
            }
        }

        async function runBulkCatalogDelete(group) {
            for (const item of group.items) {
                const data = await apiFetchJson('/PRERMI/api/vehiculos/manage_catalogo.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: item.id })
                });
                if (!data.success) {
                    throw new Error(data.msg || 'No se pudo eliminar el vehiculo');
                }
            }
        }

        function viewVehicleGroup(groupKey) {
            const group = findVehicleGroup(groupKey);
            if (!group) {
                showApiMessage('No se encontro el vehiculo seleccionado.', 'alert-warning');
                return;
            }

            document.getElementById('vehicleGalleryTitle').textContent = group.etiqueta + ' | ' + getVehicleTypeLabel(group.tipo_vehiculo);
            document.getElementById('vehicleGalleryMeta').textContent = group.items.length + ' capturas registradas. ' + (group.descripcion || 'Sin descripcion');
            document.getElementById('vehicleGalleryGrid').innerHTML = group.items.map(item => (
                '<div class="gallery-card">' +
                    '<img src="' + item.ruta_archivo + '" alt="captura">' +
                    '<div class="gallery-meta">Estado: ' + escapeHtml(item.estado || 'N/D') + '<br>Fecha: ' + escapeHtml(item.created_at || 'N/D') + '</div>' +
                '</div>'
            )).join('');

            if (!vehicleGalleryModal) {
                vehicleGalleryModal = new bootstrap.Modal(document.getElementById('vehicleGalleryModal'));
            }
            vehicleGalleryModal.show();
        }

        async function editVehicleGroup(groupKey) {
            const group = findVehicleGroup(groupKey);
            if (!group) {
                showApiMessage('No se encontro el vehiculo seleccionado.', 'alert-warning');
                return;
            }

            const nuevaEtiqueta = window.prompt('Nueva etiqueta para todas las capturas del vehiculo:', group.etiqueta || '');
            if (nuevaEtiqueta === null) return;

            const etiqueta = nuevaEtiqueta.trim();
            if (etiqueta.length < 2) {
                showApiMessage('La etiqueta debe tener al menos 2 caracteres.', 'alert-warning');
                return;
            }

            const nuevaDescripcion = window.prompt('Nueva descripcion para el vehiculo:', group.descripcion || '');
            if (nuevaDescripcion === null) return;

            try {
                clearApiMessage();
                await runBulkCatalogUpdate(buildBulkUpdatePayload(group, etiqueta, nuevaDescripcion.trim()));
                showApiMessage('Vehiculo actualizado correctamente.', 'alert-success');
                loadCatalogo();
            } catch (err) {
                showApiMessage('Error actualizando vehiculo: ' + err.message, 'alert-danger');
            }
        }

        async function deleteVehicleGroup(groupKey) {
            const group = findVehicleGroup(groupKey);
            if (!group) {
                showApiMessage('No se encontro el vehiculo seleccionado.', 'alert-warning');
                return;
            }

            if (!window.confirm('Se eliminaran del catalogo las ' + group.items.length + ' capturas de "' + group.etiqueta + '". Continuar?')) {
                return;
            }

            try {
                clearApiMessage();
                await runBulkCatalogDelete(group);
                showApiMessage('Vehiculo eliminado del catalogo.', 'alert-success');
                loadCatalogo();
            } catch (err) {
                showApiMessage('Error eliminando vehiculo: ' + err.message, 'alert-danger');
            }
        }

        function scrollToCatalogForm() {
            const form = document.getElementById('catalogUploadForm');
            if (form) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        async function loadCatalogo() {
            const tipo = document.getElementById('filtroTipo').value;
            const estado = document.getElementById('filtroEstado').value;
            const params = new URLSearchParams({ action: 'list' });
            if (tipo) params.append('tipo', tipo);
            if (estado) params.append('estado', estado);

            const tbody = document.getElementById('catalogTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Cargando...</td></tr>';
            clearApiMessage();

            try {
                const data = await apiFetchJson('/PRERMI/api/vehiculos/manage_catalogo.php?' + params.toString());
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">' + (data.msg || 'Error consultando catalogo') + '</td></tr>';
                    showApiMessage(data.msg || 'No se pudo listar el catalogo', 'alert-warning');
                    catalogItemsCache = [];
                    vehicleGroupsCache = [];
                    updateVehicleStats([], []);
                    renderVehicleViews();
                    return;
                }

                const items = data.catalogo || [];
                catalogItemsCache = items;
                vehicleGroupsCache = groupCatalogItems(items);
                updateVehicleStats(vehicleGroupsCache, items);
                renderVehicleViews();
                document.getElementById('catalogCounter').innerText = items.length + ' registros';

                if (!items.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Sin registros para los filtros seleccionados.</td></tr>';
                    return;
                }

                tbody.innerHTML = items.map(item => {
                    const accionEstado = item.estado === 'activo'
                        ? '<button class="btn btn-sm btn-outline-secondary" onclick="toggleEstado(' + item.id + ',\'inactivo\')">Desactivar</button>'
                        : '<button class="btn btn-sm btn-outline-success" onclick="toggleEstado(' + item.id + ',\'activo\')">Activar</button>';
                    const safeEtiqueta = JSON.stringify(item.etiqueta || '');
                    const safeDescripcion = JSON.stringify(item.descripcion || '');
                    const metaLine = [item.marca || '', item.modelo || '', item.anio ? ('Ano ' + item.anio) : '', item.color || '', item.placa_referencia ? ('Placa ' + item.placa_referencia) : '']
                        .filter(Boolean)
                        .join(' | ');
                    return '<tr>' +
                        '<td><img class="catalog-thumb" src="' + item.ruta_archivo + '" alt="catalog"></td>' +
                        '<td>' + item.tipo_vehiculo + '</td>' +
                        '<td><strong>' + (item.etiqueta || '') + '</strong><div class="small-muted">' + (item.descripcion || '') + '</div><div class="small-muted">' + escapeHtml(metaLine) + '</div></td>' +
                        '<td>' + badgeForState(item.estado) + '</td>' +
                        '<td>' + (item.created_at || '') + '</td>' +
                        '<td><div class="catalog-actions">' +
                            accionEstado +
                            '<button class="btn btn-sm btn-outline-primary" onclick="editCatalogItem(' + item.id + ',' + safeEtiqueta + ',' + safeDescripcion + ')">Editar</button>' +
                            '<button class="btn btn-sm btn-outline-info" onclick="selectReplaceImage(' + item.id + ')">Actualizar imagen</button>' +
                            '<button class="btn btn-sm btn-outline-danger" onclick="deleteCatalogItem(' + item.id + ')">Eliminar</button>' +
                        '</div></td>' +
                    '</tr>';
                }).join('');
            } catch (err) {
                showApiMessage('Error listando catalogo: ' + err.message, 'alert-danger');
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error de red cargando catalogo</td></tr>';
                catalogItemsCache = [];
                vehicleGroupsCache = [];
                updateVehicleStats([], []);
                renderVehicleViews();
            }
        }

        async function toggleEstado(id, estado) {
            try {
                clearApiMessage();
                const data = await apiFetchJson('/PRERMI/api/vehiculos/manage_catalogo.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, estado })
                });
                if (!data.success) {
                    showApiMessage(data.msg || 'No se pudo actualizar estado', 'alert-warning');
                    return;
                }
                loadCatalogo();
            } catch (err) {
                showApiMessage('Error al actualizar estado: ' + err.message, 'alert-danger');
            }
        }

        async function editCatalogItem(id, currentEtiqueta, currentDescripcion) {
            const nuevaEtiqueta = window.prompt('Nueva etiqueta para la imagen:', currentEtiqueta || '');
            if (nuevaEtiqueta === null) return;

            const etiqueta = nuevaEtiqueta.trim();
            if (etiqueta.length < 2) {
                showApiMessage('La etiqueta debe tener al menos 2 caracteres.', 'alert-warning');
                return;
            }

            const nuevaDescripcion = window.prompt('Nueva descripcion:', currentDescripcion || '');
            if (nuevaDescripcion === null) return;

            try {
                clearApiMessage();
                const data = await apiFetchJson('/PRERMI/api/vehiculos/manage_catalogo.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, etiqueta, descripcion: nuevaDescripcion.trim() })
                });
                if (!data.success) {
                    showApiMessage(data.msg || 'No se pudo actualizar la imagen', 'alert-warning');
                    return;
                }
                showApiMessage('Metadatos actualizados correctamente.', 'alert-success');
                loadCatalogo();
            } catch (err) {
                showApiMessage('Error actualizando imagen: ' + err.message, 'alert-danger');
            }
        }

        let pendingReplaceCatalogId = null;

        function selectReplaceImage(id) {
            pendingReplaceCatalogId = id;
            const input = document.getElementById('catalogReplaceInput');
            input.value = '';
            input.click();
        }

        async function replaceCatalogImage(id, file) {
            if (!file) return;
            if (file.size > 12 * 1024 * 1024) {
                showApiMessage('La imagen supera 12 MB.', 'alert-warning');
                return;
            }

            try {
                clearApiMessage();
                const fd = new FormData();
                fd.append('id', String(id));
                fd.append('photo_file', file, file.name || ('catalog_' + id + '.jpg'));

                const response = await fetch('/PRERMI/api/vehiculos/manage_catalogo.php?action=replace_image', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const raw = await response.text();
                let data = null;
                try {
                    data = JSON.parse(raw);
                } catch (_) {
                    throw new Error('Respuesta no JSON: ' + raw.slice(0, 180));
                }

                if (!data.success) {
                    showApiMessage(data.msg || 'No se pudo actualizar la imagen', 'alert-warning');
                    return;
                }

                showApiMessage('Imagen actualizada correctamente.', 'alert-success');
                loadCatalogo();
            } catch (err) {
                showApiMessage('Error reemplazando imagen: ' + err.message, 'alert-danger');
            }
        }

        async function deleteCatalogItem(id) {
            if (!window.confirm('Esta imagen se marcara como eliminada y dejara de usarse en el catalogo. Continuar?')) {
                return;
            }

            try {
                clearApiMessage();
                const data = await apiFetchJson('/PRERMI/api/vehiculos/manage_catalogo.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                if (!data.success) {
                    showApiMessage(data.msg || 'No se pudo eliminar la imagen', 'alert-warning');
                    return;
                }
                showApiMessage('Imagen eliminada del catalogo.', 'alert-success');
                loadCatalogo();
            } catch (err) {
                showApiMessage('Error eliminando imagen: ' + err.message, 'alert-danger');
            }
        }

        // --- Captura multiple de imagenes (15 fotos por vehiculo) ---
        let capturedImages = [];
        const MAX_IMAGES = 15;
        let webcamStream = null;

        const ROI_DEFAULT = { x: 0.15, y: 0.20, w: 0.70, h: 0.65 };
        const ROI_MIN_SIZE = 0.10;
        let roiState = { ...ROI_DEFAULT };
        let roiDrag = null;

        function clampRoi(value, min, max) {
            return Math.max(min, Math.min(max, value));
        }

        function updateRoiHint() {
            const hint = document.getElementById('roiHint');
            if (!hint) return;
            hint.textContent = 'ROI activo: X ' + Math.round(roiState.x * 100) + '%, Y ' + Math.round(roiState.y * 100) + '%, W ' + Math.round(roiState.w * 100) + '%, H ' + Math.round(roiState.h * 100) + '%';
        }

        function renderRoiBox() {
            const box = document.getElementById('roiBox');
            if (!box) return;
            box.style.left = (roiState.x * 100) + '%';
            box.style.top = (roiState.y * 100) + '%';
            box.style.width = (roiState.w * 100) + '%';
            box.style.height = (roiState.h * 100) + '%';
            updateRoiHint();
        }

        function resetRoiBox() {
            roiState = { ...ROI_DEFAULT };
            renderRoiBox();
        }

        function initRoiEditor() {
            const editor = document.getElementById('roiEditor');
            const box = document.getElementById('roiBox');
            if (!editor || !box) return;

            const getEditorRect = () => editor.getBoundingClientRect();

            const pointerDown = (event) => {
                const target = event.target;
                const mode = target && target.getAttribute('data-handle') === 'resize' ? 'resize' : 'move';
                const rect = getEditorRect();
                roiDrag = {
                    mode,
                    startX: event.clientX,
                    startY: event.clientY,
                    start: { ...roiState },
                    width: rect.width,
                    height: rect.height,
                };
                box.setPointerCapture(event.pointerId);
                event.preventDefault();
            };

            const pointerMove = (event) => {
                if (!roiDrag) return;
                const dx = (event.clientX - roiDrag.startX) / Math.max(1, roiDrag.width);
                const dy = (event.clientY - roiDrag.startY) / Math.max(1, roiDrag.height);

                if (roiDrag.mode === 'move') {
                    roiState.x = clampRoi(roiDrag.start.x + dx, 0, 1 - roiState.w);
                    roiState.y = clampRoi(roiDrag.start.y + dy, 0, 1 - roiState.h);
                } else {
                    const newW = clampRoi(roiDrag.start.w + dx, ROI_MIN_SIZE, 1 - roiDrag.start.x);
                    const newH = clampRoi(roiDrag.start.h + dy, ROI_MIN_SIZE, 1 - roiDrag.start.y);
                    roiState.w = newW;
                    roiState.h = newH;
                }
                renderRoiBox();
                event.preventDefault();
            };

            const pointerUp = (event) => {
                if (!roiDrag) return;
                try {
                    box.releasePointerCapture(event.pointerId);
                } catch (_) {}
                roiDrag = null;
            };

            box.addEventListener('pointerdown', pointerDown);
            editor.addEventListener('pointermove', pointerMove);
            editor.addEventListener('pointerup', pointerUp);
            editor.addEventListener('pointercancel', pointerUp);

            renderRoiBox();
        }

        function getRoiNormalized() {
            return {
                x: clampRoi(roiState.x, 0, 1),
                y: clampRoi(roiState.y, 0, 1),
                w: clampRoi(roiState.w, ROI_MIN_SIZE, 1),
                h: clampRoi(roiState.h, ROI_MIN_SIZE, 1)
            };
        }

        function cropCanvasByRoi(srcCanvas, roi) {
            const out = document.createElement('canvas');
            const sx = Math.round(srcCanvas.width * roi.x);
            const sy = Math.round(srcCanvas.height * roi.y);
            const sw = Math.max(1, Math.round(srcCanvas.width * roi.w));
            const sh = Math.max(1, Math.round(srcCanvas.height * roi.h));

            out.width = sw;
            out.height = sh;
            const octx = out.getContext('2d');
            octx.drawImage(srcCanvas, sx, sy, sw, sh, 0, 0, sw, sh);
            return out;
        }

        function loadImageFromFile(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = URL.createObjectURL(file);
            });
        }

        async function cropFileByRoi(file, roi) {
            const img = await loadImageFromFile(file);
            const baseCanvas = document.createElement('canvas');
            baseCanvas.width = img.naturalWidth || img.width;
            baseCanvas.height = img.naturalHeight || img.height;
            baseCanvas.getContext('2d').drawImage(img, 0, 0);

            const roiCanvas = cropCanvasByRoi(baseCanvas, roi);
            return await canvasToJpegFile(roiCanvas, 0.78);
        }

        function clearAllImages() {
            capturedImages = [];
            document.getElementById('imagenInput').value = '';
            renderPreviewGrid();
            updateCaptureState();
        }

        function removeImage(index) {
            capturedImages.splice(index, 1);
            renderPreviewGrid();
            updateCaptureState();
        }

        function renderPreviewGrid() {
            const grid = document.getElementById('previewGrid');
            if (!capturedImages.length) {
                grid.innerHTML = '<span style="grid-column:1/-1;text-align:center;color:#94a3b8;font-size:.8rem;padding:8px 0;">Sin imagenes aun</span>';
                return;
            }
            grid.innerHTML = capturedImages.map((file, i) => {
                const url = URL.createObjectURL(file);
                return '<div style="position:relative;">' +
                    '<img src="' + url + '" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:4px;border:1px solid #ddd;">' +
                    '<button type="button" onclick="removeImage(' + i + ')" style="position:absolute;top:1px;right:1px;width:16px;height:16px;font-size:9px;border-radius:50%;border:none;background:#ef4444;color:#fff;cursor:pointer;padding:0;line-height:1;">\u00d7</button>' +
                    '</div>';
            }).join('');
        }

        function updateCaptureState() {
            const count = capturedImages.length;
            document.getElementById('captureCount').textContent = count + ' / ' + MAX_IMAGES;
            document.getElementById('captureProgress').style.width = ((count / MAX_IMAGES) * 100) + '%';
            document.getElementById('captureCount').className = 'badge ' + (count === MAX_IMAGES ? 'bg-success' : 'bg-secondary');
            document.getElementById('btnCount').textContent = count;
            document.getElementById('btnUploadCatalogo').disabled = count !== MAX_IMAGES;
            document.getElementById('btnAddImage').disabled = count >= MAX_IMAGES;
            document.getElementById('btnCaptureWebcam').disabled = count >= MAX_IMAGES || !webcamStream;
        }

        async function openWebcam() {
            if (capturedImages.length >= MAX_IMAGES) {
                showApiMessage('Ya completaste las 15 imagenes.', 'alert-info');
                return;
            }

            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showApiMessage('Este navegador no soporta captura por camara.', 'alert-warning');
                    return;
                }

                if (webcamStream) {
                    document.getElementById('webcamPanel').style.display = '';
                    updateCaptureState();
                    return;
                }

                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'environment' },
                    audio: false
                });

                webcamStream = stream;
                const video = document.getElementById('webcamVideo');
                video.srcObject = stream;
                document.getElementById('webcamPanel').style.display = '';
                updateCaptureState();
                showApiMessage('Camara activa. Usa el boton de captura para tomar fotos rapidas.', 'alert-success');
            } catch (err) {
                showApiMessage('No se pudo abrir la camara: ' + err.message, 'alert-warning');
            }
        }

        function stopWebcam() {
            if (webcamStream) {
                webcamStream.getTracks().forEach(t => t.stop());
                webcamStream = null;
            }
            const video = document.getElementById('webcamVideo');
            if (video) video.srcObject = null;
            const panel = document.getElementById('webcamPanel');
            if (panel) panel.style.display = 'none';
            updateCaptureState();
        }

        function canvasToJpegFile(canvas, quality) {
            return new Promise((resolve, reject) => {
                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('No se pudo generar imagen JPEG'));
                        return;
                    }
                    const file = new File([blob], 'webcam_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                    resolve(file);
                }, 'image/jpeg', quality);
            });
        }

        async function captureFromWebcam() {
            if (!webcamStream) {
                showApiMessage('Primero abre la camara web.', 'alert-info');
                return;
            }
            if (capturedImages.length >= MAX_IMAGES) {
                showApiMessage('Ya completaste las 15 imagenes.', 'alert-info');
                return;
            }

            const video = document.getElementById('webcamVideo');
            const canvas = document.getElementById('webcamCanvas');
            if (!video.videoWidth || !video.videoHeight) {
                showApiMessage('Esperando senal de video, intenta de nuevo.', 'alert-warning');
                return;
            }

            const targetW = 960;
            const targetH = Math.round((video.videoHeight / video.videoWidth) * targetW);
            canvas.width = targetW;
            canvas.height = targetH;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, targetW, targetH);

            try {
                const roi = getRoiNormalized();
                const focusCanvas = cropCanvasByRoi(canvas, roi);
                const file = await canvasToJpegFile(focusCanvas, 0.78);
                if (file.size > 12 * 1024 * 1024) {
                    showApiMessage('La foto capturada supera 12 MB. Acercate o mejora la luz.', 'alert-warning');
                    return;
                }
                capturedImages.push(file);
                renderPreviewGrid();
                updateCaptureState();
            } catch (err) {
                showApiMessage('No se pudo capturar la imagen: ' + err.message, 'alert-warning');
            }
        }

        document.getElementById('imagenInput').addEventListener('change', async function () {
            const files = Array.from(this.files || []);
            let roi = null;
            try {
                roi = getRoiNormalized();
            } catch (err) {
                showApiMessage(err.message, 'alert-warning');
                this.value = '';
                return;
            }

            for (const file of files) {
                if (capturedImages.length >= MAX_IMAGES) break;
                if (file.size > 12 * 1024 * 1024) {
                    alert('La imagen "' + file.name + '" supera 12 MB y fue omitida.');
                    continue;
                }
                try {
                    const cropped = await cropFileByRoi(file, roi);
                    capturedImages.push(cropped);
                } catch (err) {
                    showApiMessage('No se pudo recortar una imagen: ' + err.message, 'alert-warning');
                }
            }
            this.value = '';
            renderPreviewGrid();
            updateCaptureState();
        });

        document.getElementById('catalogReplaceInput').addEventListener('change', function () {
            const file = (this.files || [])[0];
            const catalogId = pendingReplaceCatalogId;
            pendingReplaceCatalogId = null;
            this.value = '';
            if (!catalogId || !file) return;
            replaceCatalogImage(catalogId, file);
        });

        document.getElementById('catalogUploadForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const tipo = document.getElementById('tipo_vehiculo').value;
            const etiqueta = document.getElementById('etiqueta').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const marca = document.getElementById('marca').value.trim();
            const modelo = document.getElementById('modelo').value.trim();
            const anio = document.getElementById('anio').value.trim();
            const color = document.getElementById('color').value.trim();
            const placaReferencia = document.getElementById('placa_referencia').value.trim();

            let roi = null;
            try {
                roi = getRoiNormalized();
            } catch (err) {
                showApiMessage(err.message, 'alert-warning');
                return;
            }

            if (!tipo || !etiqueta) {
                alert('Completa los campos obligatorios: Tipo y Etiqueta');
                return;
            }
            if (capturedImages.length !== MAX_IMAGES) {
                alert('Debes agregar exactamente 15 imagenes del vehiculo');
                return;
            }

            const btn = document.getElementById('btnUploadCatalogo');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            clearApiMessage();
            const progressBox = document.getElementById('uploadProgressBox');
            progressBox.style.display = '';

            let uploaded = 0;
            let failed = 0;
            for (let i = 0; i < capturedImages.length; i++) {
                document.getElementById('uploadProgressText').textContent = 'Subiendo imagen ' + (i + 1) + ' de ' + MAX_IMAGES + '...';
                document.getElementById('uploadProgressBar').style.width = ((i / MAX_IMAGES) * 100) + '%';
                try {
                    const fd = new FormData();
                    fd.append('tipo_vehiculo', tipo);
                    fd.append('etiqueta', etiqueta);
                    fd.append('descripcion', descripcion);
                    fd.append('marca', marca);
                    fd.append('modelo', modelo);
                    fd.append('anio', anio);
                    fd.append('color', color);
                    fd.append('placa_referencia', placaReferencia);
                    fd.append('bbox_json', JSON.stringify(roi));
                    fd.append('photo_file', capturedImages[i], capturedImages[i].name || ('img_' + i + '.jpg'));

                    const res = await fetch('/PRERMI/api/vehiculos/upload_catalogo.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    });
                    const txt = await res.text();
                    let data = null;
                    try {
                        data = JSON.parse(txt);
                    } catch (_) {
                        data = { success: false, msg: 'Respuesta invalida del servidor' };
                    }
                    if (data && data.success) uploaded++;
                    else failed++;
                } catch (err) {
                    failed++;
                }
            }

            document.getElementById('uploadProgressBar').style.width = '100%';
            document.getElementById('uploadProgressText').textContent = 'Completado: ' + uploaded + ' subidas, ' + failed + ' errores.';

            if (failed === 0) {
                showApiMessage('Vehiculo registrado con ' + uploaded + ' imagenes correctamente.', 'alert-success');
                e.target.reset();
                clearAllImages();
                stopWebcam();
                loadCatalogo();
            } else {
                showApiMessage('Se subieron ' + uploaded + '/15 imagenes. ' + failed + ' fallaron.', 'alert-warning');
            }

            setTimeout(() => {
                progressBox.style.display = 'none';
                btn.innerHTML = '<i class="fas fa-cloud-arrow-up"></i> Registrar vehiculo (<span id="btnCount">' + capturedImages.length + '</span>/15)';
                btn.disabled = capturedImages.length !== MAX_IMAGES;
            }, 4000);
        });

        document.getElementById('filterStatus').addEventListener('change', renderVehicleViews);
        document.getElementById('filterVehicleType').addEventListener('change', renderVehicleViews);
        document.getElementById('searchVehicle').addEventListener('input', renderVehicleViews);

        initRoiEditor();
        renderPreviewGrid();
        updateCaptureState();
        loadCatalogo();

        window.addEventListener('beforeunload', () => {
            stopWebcam();
        });

        // --- Tema oscuro ---
        function toggleDark() {
            const isDark = document.body.classList.toggle('dark');
            document.getElementById('darkIcon').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            localStorage.setItem('prermi_dark', isDark ? '1' : '0');
        }

        (function initDark() {
            if (localStorage.getItem('prermi_dark') === '1') {
                document.body.classList.add('dark');
                const icon = document.getElementById('darkIcon');
                if (icon) icon.className = 'fas fa-sun';
            }
        })();
    </script>
</body>
</html>


