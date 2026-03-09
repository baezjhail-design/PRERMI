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
    <title>ConfiguraciÃ³n - PRERMI Admin</title>
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

        .settings-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .settings-menu {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .settings-menu .nav {
            flex-direction: column;
        }

        .settings-menu .nav-link {
            color: #333;
            border-left: 4px solid transparent;
            border-radius: 0;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
        }

        .settings-menu .nav-link:hover,
        .settings-menu .nav-link.active {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            border-left-color: #2563eb;
        }

        .settings-panel {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            display: none;
        }

        .settings-panel.active {
            display: block;
        }

        .settings-panel h3 {
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 1rem;
        }

        .form-group-custom {
            margin-bottom: 2rem;
        }

        .form-group-custom label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-group-custom .form-text {
            color: #999;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .toggle-switch {
            display: inline-block;
            width: 50px;
            height: 28px;
            background: #ccc;
            border-radius: 14px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            vertical-align: middle;
            margin-left: 1rem;
        }

        .toggle-switch.active {
            background: #2563eb;
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: all 0.3s ease;
        }

        .toggle-switch.active::after {
            left: 24px;
        }

        .alert-custom {
            background: rgba(81, 207, 102, 0.1);
            border-left: 4px solid #51cf66;
            color: #51cf66;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .table-custom {
            width: 100%;
            margin-top: 1rem;
        }

        .table-custom thead {
            background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            color: white;
        }

        .table-custom tbody tr:hover {
            background: rgba(37, 99, 235, 0.05);
        }

        .btn-save {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-save:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }

            .settings-menu {
                position: static;
            }
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
                <i class="fas fa-cog"></i>
                ConfiguraciÃ³n del Sistema
            </div>
            <p class="page-subtitle">Administra los parÃ¡metros y preferencias de PRERMI</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <div class="container-fluid">
            <!-- MENÃš DE CONFIGURACIÃ“N -->
            <div class="settings-container">
                <!-- SIDEBAR -->
                <div class="settings-menu">
                    <nav class="nav">
                        <a class="nav-link active" href="#general" data-bs-toggle="tab">
                            <i class="fas fa-sliders-h"></i> General
                        </a>
                        <a class="nav-link" href="#base-datos" data-bs-toggle="tab">
                            <i class="fas fa-database"></i> Base de Datos
                        </a>
                        <a class="nav-link" href="#usuarios" data-bs-toggle="tab">
                            <i class="fas fa-users-cog"></i> Usuarios
                        </a>
                        <a class="nav-link" href="#email" data-bs-toggle="tab">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                        <a class="nav-link" href="#notificaciones" data-bs-toggle="tab">
                            <i class="fas fa-bell"></i> Notificaciones
                        </a>
                        <a class="nav-link" href="#respaldo" data-bs-toggle="tab">
                            <i class="fas fa-backup"></i> Respaldos
                        </a>
                    </nav>
                </div>

                <!-- CONTENIDO -->
                <div class="tab-content">
                    <!-- CONFIGURACIÃ“N GENERAL -->
                    <div class="tab-pane fade show active" id="general">
                        <div class="settings-panel active">
                            <h3><i class="fas fa-sliders-h"></i> ConfiguraciÃ³n General</h3>
                            
                            <div class="form-group-custom">
                                <label>Nombre de la AplicaciÃ³n</label>
                                <input type="text" class="form-control" value="PRERMI" placeholder="Nombre del sistema">
                                <div class="form-text">Nombre que aparece en toda la aplicaciÃ³n</div>
                            </div>

                            <div class="form-group-custom">
                                <label>Email de Contacto</label>
                                <input type="email" class="form-control" value="admin@prermi.com" placeholder="correo@ejemplo.com">
                                <div class="form-text">Email principal para notificaciones</div>
                            </div>

                            <div class="form-group-custom">
                                <label>TelÃ©fono de Contacto</label>
                                <input type="tel" class="form-control" value="+1 (555) 000-1234" placeholder="+1 (555) 000-0000">
                            </div>

                            <div class="form-group-custom">
                                <label>Zona Horaria</label>
                                <select class="form-control">
                                    <option selected>America/Caracas</option>
                                    <option>America/New_York</option>
                                    <option>America/Mexico_City</option>
                                    <option>Europe/Madrid</option>
                                </select>
                            </div>

                            <div class="form-group-custom">
                                <label>
                                    Modo de Mantenimiento
                                    <span class="toggle-switch"></span>
                                </label>
                                <div class="form-text">Desactiva la aplicaciÃ³n para todos excepto administradores</div>
                            </div>

                            <button class="btn-save">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>

                    <!-- CONFIGURACIÃ“N BASE DE DATOS -->
                    <div class="tab-pane fade" id="base-datos">
                        <div class="settings-panel">
                            <h3><i class="fas fa-database"></i> Base de Datos</h3>
                            
                            <div class="alert-custom">
                                <i class="fas fa-check-circle"></i> ConexiÃ³n activa y funcionando correctamente
                            </div>

                            <div class="form-group-custom">
                                <label>Host</label>
                                <input type="text" class="form-control" value="localhost" readonly>
                            </div>

                            <div class="form-group-custom">
                                <label>Puerto</label>
                                <input type="text" class="form-control" value="3306" readonly>
                            </div>

                            <div class="form-group-custom">
                                <label>Base de Datos</label>
                                <input type="text" class="form-control" value="prermi_db" readonly>
                            </div>

                            <div class="form-group-custom">
                                <label>Usuario</label>
                                <input type="text" class="form-control" value="root" readonly>
                            </div>

                            <h4 style="margin-top: 2rem; margin-bottom: 1rem;">
                                <i class="fas fa-chart-bar"></i> EstadÃ­sticas
                            </h4>
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>MÃ©trica</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>TamaÃ±o de BD</td>
                                        <td><strong>245.5 MB</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Tablas</td>
                                        <td><strong>12</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Registros Totales</td>
                                        <td><strong>185,432</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Ãšltimo Respaldo</td>
                                        <td><strong>2024-12-08 03:00 AM</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CONFIGURACIÃ“N USUARIOS -->
                    <div class="tab-pane fade" id="usuarios">
                        <div class="settings-panel">
                            <h3><i class="fas fa-users-cog"></i> GestiÃ³n de Usuarios</h3>
                            
                            <div class="form-group-custom">
                                <label>Requerir ConfirmaciÃ³n de Email</label>
                                <span class="toggle-switch active"></span>
                                <div class="form-text">Los nuevos usuarios deben confirmar su email</div>
                            </div>

                            <div class="form-group-custom">
                                <label>Longitud MÃ­nima de ContraseÃ±a</label>
                                <input type="number" class="form-control" value="8" min="6" max="20">
                            </div>

                            <div class="form-group-custom">
                                <label>ExpiraciÃ³n de SesiÃ³n (minutos)</label>
                                <input type="number" class="form-control" value="30" min="5" max="1440">
                                <div class="form-text">Cierre automÃ¡tico de sesiÃ³n despuÃ©s de inactividad</div>
                            </div>

                            <div class="form-group-custom">
                                <label>Intentos de Login Permitidos</label>
                                <input type="number" class="form-control" value="5" min="1" max="20">
                                <div class="form-text">Bloqueo temporal despuÃ©s de estos intentos fallidos</div>
                            </div>

                            <div class="form-group-custom">
                                <label>Roles Permitidos</label>
                                <div style="margin-top: 1rem;">
                                    <div style="margin-bottom: 0.75rem;">
                                        <input type="checkbox" id="role-admin" checked>
                                        <label for="role-admin" style="display: inline; margin-left: 0.5rem;">Administrador</label>
                                    </div>
                                    <div style="margin-bottom: 0.75rem;">
                                        <input type="checkbox" id="role-user" checked>
                                        <label for="role-user" style="display: inline; margin-left: 0.5rem;">Usuario</label>
                                    </div>
                                    <div style="margin-bottom: 0.75rem;">
                                        <input type="checkbox" id="role-conductor">
                                        <label for="role-conductor" style="display: inline; margin-left: 0.5rem;">Conductor</label>
                                    </div>
                                </div>
                            </div>

                            <button class="btn-save">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>

                    <!-- CONFIGURACIÃ“N EMAIL -->
                    <div class="tab-pane fade" id="email">
                        <div class="settings-panel">
                            <h3><i class="fas fa-envelope"></i> ConfiguraciÃ³n de Email</h3>
                            
                            <div class="form-group-custom">
                                <label>Servidor SMTP</label>
                                <input type="text" class="form-control" value="smtp.gmail.com" placeholder="smtp.ejemplo.com">
                            </div>

                            <div class="form-group-custom">
                                <label>Puerto SMTP</label>
                                <input type="number" class="form-control" value="587" placeholder="587">
                            </div>

                            <div class="form-group-custom">
                                <label>Usuario/Email</label>
                                <input type="email" class="form-control" value="noreply@prermi.com" placeholder="correo@ejemplo.com">
                            </div>

                            <div class="form-group-custom">
                                <label>ContraseÃ±a</label>
                                <input type="password" class="form-control" value="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" placeholder="ContraseÃ±a">
                            </div>

                            <div class="form-group-custom">
                                <label>
                                    Usar TLS
                                    <span class="toggle-switch active"></span>
                                </label>
                            </div>

                            <button class="btn-save" onclick="alert('Email de prueba enviado')">
                                <i class="fas fa-paper-plane"></i> Enviar Email de Prueba
                            </button>
                            <button class="btn-save" style="background: #667eea; margin-left: 0.5rem;">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>

                    <!-- NOTIFICACIONES -->
                    <div class="tab-pane fade" id="notificaciones">
                        <div class="settings-panel">
                            <h3><i class="fas fa-bell"></i> Notificaciones</h3>
                            
                            <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Alertas del Sistema</h4>

                            <div class="form-group-custom">
                                <label>
                                    Contenedor Lleno
                                    <span class="toggle-switch active"></span>
                                </label>
                                <div class="form-text">Notificar cuando un contenedor estÃ© al 100%</div>
                            </div>

                            <div class="form-group-custom">
                                <label>
                                    VehÃ­culo Offline
                                    <span class="toggle-switch active"></span>
                                </label>
                                <div class="form-text">Alertar si un vehÃ­culo pierde conexiÃ³n GPS</div>
                            </div>

                            <div class="form-group-custom">
                                <label>
                                    Mantenimiento Vencido
                                    <span class="toggle-switch active"></span>
                                </label>
                                <div class="form-text">Recordar mantenimiento programado</div>
                            </div>

                            <div class="form-group-custom">
                                <label>
                                    Reporte de Errores
                                    <span class="toggle-switch"></span>
                                </label>
                                <div class="form-text">Reportes automÃ¡ticos de errores del sistema</div>
                            </div>

                            <h4 style="margin-top: 2rem; margin-bottom: 1rem;">MÃ©todos de NotificaciÃ³n</h4>

                            <div style="margin-bottom: 1rem;">
                                <input type="checkbox" id="notif-email" checked>
                                <label for="notif-email" style="display: inline; margin-left: 0.5rem;">Email</label>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <input type="checkbox" id="notif-sms" checked>
                                <label for="notif-sms" style="display: inline; margin-left: 0.5rem;">SMS</label>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <input type="checkbox" id="notif-push">
                                <label for="notif-push" style="display: inline; margin-left: 0.5rem;">NotificaciÃ³n Push</label>
                            </div>

                            <button class="btn-save">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>

                    <!-- RESPALDOS -->
                    <div class="tab-pane fade" id="respaldo">
                        <div class="settings-panel">
                            <h3><i class="fas fa-backup"></i> Respaldos y RecuperaciÃ³n</h3>
                            
                            <h4 style="margin-bottom: 1rem;">Respaldos AutomÃ¡ticos</h4>

                            <div class="form-group-custom">
                                <label>Frecuencia de Respaldo</label>
                                <select class="form-control">
                                    <option>Cada 6 horas</option>
                                    <option selected>Diariamente a las 3:00 AM</option>
                                    <option>Semanalmente (Domingo)</option>
                                </select>
                            </div>

                            <h4 style="margin-top: 2rem; margin-bottom: 1rem;">Respaldos Recientes</h4>

                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>TamaÃ±o</th>
                                        <th>Estado</th>
                                        <th>AcciÃ³n</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2024-12-08 03:00 AM</td>
                                        <td>245.5 MB</td>
                                        <td><span style="color: #51cf66; font-weight: 600;">âœ“ Completado</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-download"></i> Descargar
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2024-12-07 03:00 AM</td>
                                        <td>240.2 MB</td>
                                        <td><span style="color: #51cf66; font-weight: 600;">âœ“ Completado</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-download"></i> Descargar
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2024-12-06 03:00 AM</td>
                                        <td>235.8 MB</td>
                                        <td><span style="color: #51cf66; font-weight: 600;">âœ“ Completado</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-download"></i> Descargar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div style="margin-top: 2rem;">
                                <button class="btn-save" onclick="alert('Iniciando respaldo...')">
                                    <i class="fas fa-plus"></i> Crear Respaldo Ahora
                                </button>
                                <button class="btn-save" style="background: #667eea; margin-left: 0.5rem;">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
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
            <p>&copy; 2024 PRERMI Admin - ConfiguraciÃ³n del Sistema</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle switches functionality
        document.querySelectorAll('.toggle-switch').forEach(toggle => {
            toggle.addEventListener('click', function() {
                this.classList.toggle('active');
            });
        });

        // Tab switching
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>


