<?php 
session_start();
require_once __DIR__ . '/../config/db_config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin/loginA.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestiÃ³n de Contenedores - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }

        .stat-icon {
            font-size: 3rem;
            color: #667eea;
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

        .container-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #4ecdc4;
            margin-bottom: 1.5rem;
        }

        .container-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(78, 205, 196, 0.2);
        }

        .container-id {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .container-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label {
            color: #999;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-lleno {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }

        .status-medio {
            background: rgba(255, 217, 61, 0.1);
            color: #e6a800;
        }

        .status-vacio {
            background: rgba(81, 207, 102, 0.1);
            color: #51cf66;
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/PRERMI/index.php">
                <i class="fas fa-truck"></i> PRERMI
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/PRERMI/web/admin/dashboardA.php">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PRERMI/web/admin/usuarios.php">
                            <i class="fas fa-users"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-box"></i> Contenedores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PRERMI/web/admin/vehiculos.php">
                            <i class="fas fa-car"></i> VehÃ­culos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PRERMI/web/admin/reportes.php">
                            <i class="fas fa-file-pdf"></i> Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PRERMI/web/admin/configuracion.php">
                            <i class="fas fa-cog"></i> ConfiguraciÃ³n
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PRERMI/api/admin/logoutA.php">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HEADER -->
    <div class="dashboard-header">
        <div class="container">
            <div class="page-title">
                <i class="fas fa-box"></i>
                GestiÃ³n de Contenedores
            </div>
            <p class="page-subtitle">Monitorea y administra tus contenedores de basura</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <div class="container-fluid">
            <!-- STATS -->
            <div class="grid-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-value">87</div>
                    <div class="stat-label">Contenedores Activos</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-weight"></i>
                    </div>
                    <div class="stat-value">12.5 T</div>
                    <div class="stat-label">Basura Recolectada</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">$4,280</div>
                    <div class="stat-label">Dinero Reducido</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-value">12</div>
                    <div class="stat-label">Contenedores Llenos</div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-filter"></i> Estado</label>
                        <select class="form-control" id="filterStatus">
                            <option value="">Todos</option>
                            <option value="lleno">Lleno (100%)</option>
                            <option value="medio">Medio (50-90%)</option>
                            <option value="vacio">VacÃ­o (0-50%)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-search"></i> BÃºsqueda</label>
                        <input type="text" class="form-control" id="searchContainer" placeholder="UbicaciÃ³n o ID...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-map"></i> Zona</label>
                        <select class="form-control">
                            <option>Todas las zonas</option>
                            <option>Zona Centro</option>
                            <option>Zona Norte</option>
                            <option>Zona Sur</option>
                            <option>Zona Este</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Nuevo Contenedor
                        </button>
                    </div>
                </div>
            </div>

            <!-- CONTENEDORES -->
            <div class="row">
                <div class="col-12">
                    <h3 style="margin-bottom: 2rem;">
                        <i class="fas fa-list"></i> Contenedores Registrados
                    </h3>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="container-card">
                        <div class="container-id">
                            <i class="fas fa-box"></i> CONT-001
                            <span class="status-badge status-lleno float-end">LLENO</span>
                        </div>
                        <div class="container-info">
                            <div class="info-item">
                                <i class="fas fa-location-dot"></i>
                                <span class="info-label">UbicaciÃ³n:</span>
                                <span class="info-value">Centro, Av. Principal</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-weight"></i>
                                <span class="info-label">Peso:</span>
                                <span class="info-value">98 kg</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span class="info-label">Fecha:</span>
                                <span class="info-value">2024-12-08</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-dollar-sign"></i>
                                <span class="info-label">Dinero:</span>
                                <span class="info-value">$48.90</span>
                            </div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="container-card">
                        <div class="container-id">
                            <i class="fas fa-box"></i> CONT-002
                            <span class="status-badge status-medio float-end">MEDIO</span>
                        </div>
                        <div class="container-info">
                            <div class="info-item">
                                <i class="fas fa-location-dot"></i>
                                <span class="info-label">UbicaciÃ³n:</span>
                                <span class="info-value">Zona Norte, Calle 5</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-weight"></i>
                                <span class="info-label">Peso:</span>
                                <span class="info-value">65 kg</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span class="info-label">Fecha:</span>
                                <span class="info-value">2024-12-07</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-dollar-sign"></i>
                                <span class="info-label">Dinero:</span>
                                <span class="info-value">$32.50</span>
                            </div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="container-card">
                        <div class="container-id">
                            <i class="fas fa-box"></i> CONT-003
                            <span class="status-badge status-vacio float-end">VACÃO</span>
                        </div>
                        <div class="container-info">
                            <div class="info-item">
                                <i class="fas fa-location-dot"></i>
                                <span class="info-label">UbicaciÃ³n:</span>
                                <span class="info-value">Zona Sur, Parque</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-weight"></i>
                                <span class="info-label">Peso:</span>
                                <span class="info-value">25 kg</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span class="info-label">Fecha:</span>
                                <span class="info-value">2024-12-06</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-dollar-sign"></i>
                                <span class="info-label">Dinero:</span>
                                <span class="info-value">$12.50</span>
                            </div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: 25%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="container-card">
                        <div class="container-id">
                            <i class="fas fa-box"></i> CONT-004
                            <span class="status-badge status-medio float-end">MEDIO</span>
                        </div>
                        <div class="container-info">
                            <div class="info-item">
                                <i class="fas fa-location-dot"></i>
                                <span class="info-label">UbicaciÃ³n:</span>
                                <span class="info-value">Zona Este, Terminal</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-weight"></i>
                                <span class="info-label">Peso:</span>
                                <span class="info-value">73 kg</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span class="info-label">Fecha:</span>
                                <span class="info-value">2024-12-08</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-dollar-sign"></i>
                                <span class="info-label">Dinero:</span>
                                <span class="info-value">$36.50</span>
                            </div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: 73%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA COMPLETA -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-table"></i> Vista de Tabla
                        </div>
                        <div class="card-body">
                            <div style="overflow-x: auto;">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID Contenedor</th>
                                            <th>CÃ©dula Propietario</th>
                                            <th>Nombre</th>
                                            <th>UbicaciÃ³n</th>
                                            <th>Peso (kg)</th>
                                            <th>Dinero Reducido</th>
                                            <th>Fecha Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>CONT-001</strong></td>
                                            <td>12345678</td>
                                            <td>Juan GarcÃ­a</td>
                                            <td>Centro</td>
                                            <td>98</td>
                                            <td>$48.90</td>
                                            <td>2024-12-08</td>
                                            <td>
                                                <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p>&copy; 2024 PRERMI - GestiÃ³n de Contenedores</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

