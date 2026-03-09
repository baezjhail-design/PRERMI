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
    <title>GestiÃ³n de Usuarios - PRERMI Admin</title>
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

        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .user-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 4px solid #2563eb;
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.2);
        }

        .user-header {
            background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: #2563eb;
        }

        .user-name {
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .user-email {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .user-body {
            padding: 1.5rem;
        }

        .user-info {
            margin-bottom: 1rem;
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

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .status-active {
            background: #51cf66;
        }

        .status-inactive {
            background: #adb5bd;
        }

        .user-actions {
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
                        <a class="nav-link active" href="#">
                            <i class="fas fa-users"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PRERMI/web/contenedores.php">
                            <i class="fas fa-box"></i> Contenedores
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
                <i class="fas fa-users"></i>
                GestiÃ³n de Usuarios
            </div>
            <p class="page-subtitle">Administra los usuarios del sistema PRERMI</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <div class="container-fluid">
            <!-- STATS -->
            <div class="grid-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value">156</div>
                    <div class="stat-label">Usuarios Registrados</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-value">142</div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-value">14</div>
                    <div class="stat-label">Usuarios Inactivos</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value">3</div>
                    <div class="stat-label">Usuarios Bloqueados</div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-filter"></i> Estado</label>
                        <select class="form-control" id="filterStatus">
                            <option value="">Todos</option>
                            <option value="activo">Activos</option>
                            <option value="inactivo">Inactivos</option>
                            <option value="bloqueado">Bloqueados</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-search"></i> BÃºsqueda</label>
                        <input type="text" class="form-control" id="searchUser" placeholder="Nombre o email...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-sort"></i> Ordenar por</label>
                        <select class="form-control">
                            <option>MÃ¡s Recientes</option>
                            <option>Nombres (A-Z)</option>
                            <option>MÃ¡s Activos</option>
                            <option>Menos Activos</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-danger w-100">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </button>
                    </div>
                </div>
            </div>

            <!-- VISTA DE GRID -->
            <h3 style="margin-top: 3rem; margin-bottom: 1.5rem;">
                <i class="fas fa-th-large"></i> Vista de Grid
            </h3>

            <div class="user-grid">
                <!-- USUARIO 1 -->
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-name">Juan GarcÃ­a</div>
                        <div class="user-email">juan@example.com</div>
                    </div>
                    <div class="user-body">
                        <div class="user-info">
                            <div class="info-label">CÃ©dula</div>
                            <div class="info-value">12345678</div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">TelÃ©fono</div>
                            <div class="info-value">+1 (555) 123-4567</div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">Estado</div>
                            <div class="info-value">
                                <span class="status-indicator status-active"></span> Activo
                            </div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">Registrado</div>
                            <div class="info-value">2024-11-15</div>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Borrar</button>
                    </div>
                </div>

                <!-- USUARIO 2 -->
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-name">MarÃ­a LÃ³pez</div>
                        <div class="user-email">maria@example.com</div>
                    </div>
                    <div class="user-body">
                        <div class="user-info">
                            <div class="info-label">CÃ©dula</div>
                            <div class="info-value">87654321</div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">TelÃ©fono</div>
                            <div class="info-value">+1 (555) 987-6543</div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">Estado</div>
                            <div class="info-value">
                                <span class="status-indicator status-active"></span> Activo
                            </div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">Registrado</div>
                            <div class="info-value">2024-10-20</div>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Borrar</button>
                    </div>
                </div>

                <!-- USUARIO 3 -->
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-name">Carlos PÃ©rez</div>
                        <div class="user-email">carlos@example.com</div>
                    </div>
                    <div class="user-body">
                        <div class="user-info">
                            <div class="info-label">CÃ©dula</div>
                            <div class="info-value">11111111</div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">TelÃ©fono</div>
                            <div class="info-value">+1 (555) 555-5555</div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">Estado</div>
                            <div class="info-value">
                                <span class="status-indicator status-inactive"></span> Inactivo
                            </div>
                        </div>
                        <div class="user-info">
                            <div class="info-label">Registrado</div>
                            <div class="info-value">2024-09-10</div>
                        </div>
                    </div>
                    <div class="user-actions">
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
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>CÃ©dula</th>
                                <th>TelÃ©fono</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Juan GarcÃ­a</strong></td>
                                <td>juan@example.com</td>
                                <td>12345678</td>
                                <td>+1 (555) 123-4567</td>
                                <td><span class="status-indicator status-active"></span> Activo</td>
                                <td>2024-11-15</td>
                                <td>
                                    <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>MarÃ­a LÃ³pez</strong></td>
                                <td>maria@example.com</td>
                                <td>87654321</td>
                                <td>+1 (555) 987-6543</td>
                                <td><span class="status-indicator status-active"></span> Activo</td>
                                <td>2024-10-20</td>
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
            <p>&copy; 2024 PRERMI Admin - GestiÃ³n de Usuarios</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


