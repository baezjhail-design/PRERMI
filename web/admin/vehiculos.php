<?php 
session_start();
require_once __DIR__ . '/../config/db_config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /PRERMI/web/admin/loginA.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestiÃ³n de VehÃ­culos - PRERMI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/PRERMI/web/assets/css/style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
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
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/PRERMI/index.php">
                <i class="fas fa-truck"></i> PRERMI Admin
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
                            <i class="fas fa-car"></i> VehÃ­culos
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
                <i class="fas fa-car"></i>
                GestiÃ³n de VehÃ­culos
            </div>
            <p class="page-subtitle">Administra la flota de vehÃ­culos del sistema</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <div class="container-fluid">
            <!-- STATS -->
            <div class="grid-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-value">48</div>
                    <div class="stat-label">VehÃ­culos Totales</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <div class="stat-value">42</div>
                    <div class="stat-label">VehÃ­culos Online</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="stat-value">4</div>
                    <div class="stat-label">En Mantenimiento</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-gas-pump"></i>
                    </div>
                    <div class="stat-value">$12,450</div>
                    <div class="stat-label">Gasto Combustible</div>
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
                        <label class="form-label"><i class="fas fa-search"></i> BÃºsqueda</label>
                        <input type="text" class="form-control" id="searchVehicle" placeholder="Placa o modelo...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-sort"></i> Tipo</label>
                        <select class="form-control">
                            <option>Todos los tipos</option>
                            <option>CamiÃ³n</option>
                            <option>Furgoneta</option>
                            <option>Auto</option>
                            <option>Bicicleta</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-danger w-100">
                            <i class="fas fa-plus"></i> Nuevo VehÃ­culo
                        </button>
                    </div>
                </div>
            </div>

            <!-- VISTA DE GRID -->
            <h3 style="margin-top: 3rem; margin-bottom: 1.5rem;">
                <i class="fas fa-th-large"></i> Vista de Grid
            </h3>

            <div class="vehicle-grid">
                <!-- VEHÃCULO 1 -->
                <div class="vehicle-card">
                    <div class="vehicle-image">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="vehicle-body">
                        <div class="vehicle-plate">ABC-1234</div>
                        <div class="vehicle-status status-online">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #51cf66; margin-right: 5px;"></span> Online
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">Modelo</div>
                            <div class="info-value">Volvo FH16</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">AÃ±o</div>
                            <div class="info-value">2022</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">Conductor</div>
                            <div class="info-value">Carlos PÃ©rez</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">KM Recorridos</div>
                            <div class="info-value">12,450 km</div>
                        </div>
                    </div>
                    <div class="vehicle-actions">
                        <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Borrar</button>
                    </div>
                </div>

                <!-- VEHÃCULO 2 -->
                <div class="vehicle-card">
                    <div class="vehicle-image">
                        <i class="fas fa-van-shuttle"></i>
                    </div>
                    <div class="vehicle-body">
                        <div class="vehicle-plate">XYZ-5678</div>
                        <div class="vehicle-status status-online">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #51cf66; margin-right: 5px;"></span> Online
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">Modelo</div>
                            <div class="info-value">Mercedes Sprinter</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">AÃ±o</div>
                            <div class="info-value">2021</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">Conductor</div>
                            <div class="info-value">Juan GarcÃ­a</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">KM Recorridos</div>
                            <div class="info-value">8,320 km</div>
                        </div>
                    </div>
                    <div class="vehicle-actions">
                        <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Borrar</button>
                    </div>
                </div>

                <!-- VEHÃCULO 3 -->
                <div class="vehicle-card">
                    <div class="vehicle-image">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="vehicle-body">
                        <div class="vehicle-plate">DEF-9999</div>
                        <div class="vehicle-status status-mantenimiento">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #e6a800; margin-right: 5px;"></span> Mantenimiento
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">Modelo</div>
                            <div class="info-value">Scania R440</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">AÃ±o</div>
                            <div class="info-value">2020</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">Conductor</div>
                            <div class="info-value">Mantenimiento</div>
                        </div>
                        <div class="vehicle-info">
                            <div class="info-label">KM Recorridos</div>
                            <div class="info-value">95,680 km</div>
                        </div>
                    </div>
                    <div class="vehicle-actions">
                        <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Borrar</button>
                    </div>
                </div>
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
                                <th>Modelo</th>
                                <th>AÃ±o</th>
                                <th>Conductor</th>
                                <th>Estado</th>
                                <th>KM</th>
                                <th>Ãšltima UbicaciÃ³n</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>ABC-1234</strong></td>
                                <td>Volvo FH16</td>
                                <td>2022</td>
                                <td>Carlos PÃ©rez</td>
                                <td><span class="status-online">Online</span></td>
                                <td>12,450</td>
                                <td>Centro, Av. Principal</td>
                                <td>
                                    <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>XYZ-5678</strong></td>
                                <td>Mercedes Sprinter</td>
                                <td>2021</td>
                                <td>Juan GarcÃ­a</td>
                                <td><span class="status-online">Online</span></td>
                                <td>8,320</td>
                                <td>Zona Norte, Calle 5</td>
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

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p>&copy; 2024 PRERMI Admin - GestiÃ³n de VehÃ­culos</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


