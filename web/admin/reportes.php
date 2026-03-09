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
    <title>Reportes - PRERMI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/PRERMI/web/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 3rem;
        }

        .report-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #2563eb;
            margin-bottom: 2rem;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.2);
        }

        .report-icon {
            font-size: 2.5rem;
            color: #2563eb;
            margin-bottom: 1rem;
        }

        .report-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .report-description {
            color: #999;
            font-size: 0.9rem;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            height: 400px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .badge-success {
            background: rgba(81, 207, 102, 0.1);
            color: #51cf66;
        }

        .badge-info {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .badge-warning {
            background: rgba(255, 217, 61, 0.1);
            color: #e6a800;
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
                        <a class="nav-link active" href="#">
                            <i class="fas fa-file-pdf"></i> Reportes
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
                <i class="fas fa-file-pdf"></i>
                Generador de Reportes
            </div>
            <p class="page-subtitle">Crea reportes detallados del sistema PRERMI</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <div class="container-fluid">
            <!-- TIPOS DE REPORTES -->
            <h3 style="margin-bottom: 2rem;">
                <i class="fas fa-list"></i> Selecciona un Reporte
            </h3>

            <div class="report-grid">
                <!-- REPORTE 1 -->
                <div class="report-card" data-report="actividad">
                    <div class="report-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="report-title">Reporte de Actividad</div>
                    <div class="report-description">Resumen de actividades del sistema por perÃ­odo</div>
                </div>

                <!-- REPORTE 2 -->
                <div class="report-card" data-report="vehiculos">
                    <div class="report-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="report-title">Reporte de VehÃ­culos</div>
                    <div class="report-description">Detalles de flota y desempeÃ±o vehicular</div>
                </div>

                <!-- REPORTE 3 -->
                <div class="report-card" data-report="usuarios">
                    <div class="report-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="report-title">Reporte de Usuarios</div>
                    <div class="report-description">EstadÃ­sticas de usuarios registrados</div>
                </div>

                <!-- REPORTE 4 -->
                <div class="report-card" data-report="contenedores">
                    <div class="report-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="report-title">Reporte de Contenedores</div>
                    <div class="report-description">Monitoreo de contenedores de basura</div>
                </div>

                <!-- REPORTE 5 -->
                <div class="report-card" data-report="financiero">
                    <div class="report-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="report-title">Reporte Financiero</div>
                    <div class="report-description">AnÃ¡lisis de gastos e ingresos</div>
                </div>

                <!-- REPORTE 6 -->
                <div class="report-card" data-report="mantenimiento">
                    <div class="report-icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="report-title">Reporte de Mantenimiento</div>
                    <div class="report-description">Historial de mantenimiento vehicular</div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filter-section">
                <h4 style="margin-bottom: 1.5rem;">
                    <i class="fas fa-filter"></i> Filtros del Reporte
                </h4>
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PerÃ­odo</label>
                        <select class="form-control">
                            <option>Ãšltimo mes</option>
                            <option>Ãšltimos 3 meses</option>
                            <option>Ãšltimo aÃ±o</option>
                            <option>Personalizado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-danger w-100">
                            <i class="fas fa-download"></i> Descargar PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- GRÃFICOS -->
            <h3 style="margin-top: 3rem; margin-bottom: 1.5rem;">
                <i class="fas fa-chart-line"></i> AnÃ¡lisis Visual
            </h3>

            <div class="row">
                <div class="col-lg-6">
                    <div class="chart-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- TABLA DE DATOS -->
            <h3 style="margin-bottom: 1.5rem;">
                <i class="fas fa-table"></i> Datos Detallados
            </h3>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>VehÃ­culos</th>
                            <th>Contenedores</th>
                            <th>Usuarios Activos</th>
                            <th>Dinero Generado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2024-12-08</td>
                            <td>RecolecciÃ³n Diaria</td>
                            <td>42</td>
                            <td>87</td>
                            <td>156</td>
                            <td>$4,280.50</td>
                            <td><span class="stat-badge badge-success">Completado</span></td>
                        </tr>
                        <tr>
                            <td>2024-12-07</td>
                            <td>RecolecciÃ³n Diaria</td>
                            <td>40</td>
                            <td>85</td>
                            <td>152</td>
                            <td>$3,950.75</td>
                            <td><span class="stat-badge badge-success">Completado</span></td>
                        </tr>
                        <tr>
                            <td>2024-12-06</td>
                            <td>Mantenimiento</td>
                            <td>38</td>
                            <td>83</td>
                            <td>148</td>
                            <td>$3,120.00</td>
                            <td><span class="stat-badge badge-warning">Parcial</span></td>
                        </tr>
                        <tr>
                            <td>2024-12-05</td>
                            <td>RecolecciÃ³n Diaria</td>
                            <td>45</td>
                            <td>88</td>
                            <td>158</td>
                            <td>$4,580.25</td>
                            <td><span class="stat-badge badge-success">Completado</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- RESUMEN EJECUTIVO -->
            <div style="margin-top: 3rem; margin-bottom: 3rem;">
                <h3 style="margin-bottom: 1.5rem;">
                    <i class="fas fa-summary"></i> Resumen Ejecutivo
                </h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card" style="border-left: 4px solid #2563eb;">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-info-circle"></i> Datos Clave
                                </h5>
                                <ul style="margin-top: 1rem; list-style: none; padding: 0;">
                                    <li style="padding: 0.5rem 0;">
                                        <strong>Total de VehÃ­culos:</strong> 48 activos
                                    </li>
                                    <li style="padding: 0.5rem 0;">
                                        <strong>Total de Contenedores:</strong> 87 unidades
                                    </li>
                                    <li style="padding: 0.5rem 0;">
                                        <strong>Usuarios Registrados:</strong> 156
                                    </li>
                                    <li style="padding: 0.5rem 0;">
                                        <strong>Ingresos Este Mes:</strong> $125,680.50
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card" style="border-left: 4px solid #667eea;">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-flag-checkered"></i> Objetivos
                                </h5>
                                <ul style="margin-top: 1rem; list-style: none; padding: 0;">
                                    <li style="padding: 0.5rem 0;">
                                        <strong>Meta de Ingresos:</strong> 85% alcanzada
                                    </li>
                                    <li style="padding: 0.5rem 0;">
                                        <strong>Disponibilidad de Flota:</strong> 87.5%
                                    </li>
                                    <li style="padding: 0.5rem 0;">
                                        <strong>SatisfacciÃ³n de Usuarios:</strong> 92%
                                    </li>
                                    <li style="padding: 0.5rem 0;">
                                        <strong>Eficiencia Operativa:</strong> 88%
                                    </li>
                                </ul>
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
            <p>&copy; 2024 PRERMI Admin - Generador de Reportes</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // GRÃFICO DE ACTIVIDAD
        const ctxActivity = document.getElementById('activityChart').getContext('2d');
        new Chart(ctxActivity, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'MiÃ©', 'Jue', 'Vie', 'Sab', 'Dom'],
                datasets: [{
                    label: 'Actividad Diaria',
                    data: [65, 72, 68, 80, 85, 78, 72],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                }
            }
        });

        // GRÃFICO DE ESTADO
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Completado', 'Parcial', 'Pendiente'],
                datasets: [{
                    data: [75, 15, 10],
                    backgroundColor: ['#51cf66', '#e6a800', '#2563eb'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // SELECCIÃ“N DE REPORTES
        document.querySelectorAll('.report-card').forEach(card => {
            card.addEventListener('click', function() {
                const reportType = this.dataset.report;
                console.log('Reporte seleccionado:', reportType);
                alert('Cargando reporte de ' + reportType);
            });
        });
    </script>
</body>
</html>


