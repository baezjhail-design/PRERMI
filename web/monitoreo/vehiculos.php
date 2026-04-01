<?php 
session_start();
require_once __DIR__ . '/../../config/db_config.php';

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
    <title>Monitoreo de VehÃ­culos - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/PRERMI/web/assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 3rem;
        }

        #map {
            border-radius: 12px;
            height: 600px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .vehicle-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .vehicle-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .vehicle-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
            transform: translateX(5px);
        }

        .vehicle-item.active {
            border-left-color: #51cf66;
            background: #f8f9fa;
        }

        .vehicle-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .vehicle-status {
            font-size: 0.85rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-online {
            color: #51cf66;
        }

        .status-offline {
            color: #ff6b6b;
        }

        .location-badge {
            display: inline-block;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .vehicle-thumbnail {
            width: 100%;
            max-width: 150px;
            border-radius: 8px;
            object-fit: cover;
        }

        .catalog-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }

        .catalog-mini-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .catalog-mini-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: #f8fafc;
        }

        .catalog-mini-body {
            padding: 0.75rem;
        }

        .text-mini {
            font-size: 0.82rem;
            color: #6b7280;
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
                        <a class="nav-link active" href="#">
                            <i class="fas fa-map-location-dot"></i> Monitoreo
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
                <i class="fas fa-map-location-dot"></i>
                Monitoreo en Tiempo Real
            </div>
            <p class="page-subtitle">Rastreo de vehÃ­culos de tu flota</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <div class="container-fluid">
            <!-- FILTROS -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-filter"></i> Estado</label>
                        <select class="form-control" id="filterStatus">
                            <option value="">Todos</option>
                            <option value="online">En lÃ­nea</option>
                            <option value="offline">Desconectado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-search"></i> BÃºsqueda</label>
                        <input type="text" class="form-control" id="searchVehicle" placeholder="Placa o modelo...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-history"></i> PerÃ­odo</label>
                        <select class="form-control">
                            <option>Ãšltima hora</option>
                            <option>Ãšltimas 24 horas</option>
                            <option>Ãšltima semana</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100">
                            <i class="fas fa-refresh"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- MAPA Y LISTA -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-map"></i> Mapa en Tiempo Real
                        </div>
                        <div id="map"></div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-list"></i> VehÃ­culos Activos
                        </div>
                        <div class="vehicle-list">
                            <div class="vehicle-item active">
                                <div class="vehicle-name">
                                    <i class="fas fa-truck"></i> TAXI-001
                                </div>
                                <div class="vehicle-status status-online">
                                    <i class="fas fa-circle"></i> En lÃ­nea
                                </div>
                                <div class="location-badge">
                                    <i class="fas fa-location-dot"></i> Centro, 2.4 km
                                </div>
                            </div>

                            <div class="vehicle-item">
                                <div class="vehicle-name">
                                    <i class="fas fa-truck"></i> TAXI-002
                                </div>
                                <div class="vehicle-status status-online">
                                    <i class="fas fa-circle"></i> En lÃ­nea
                                </div>
                                <div class="location-badge">
                                    <i class="fas fa-location-dot"></i> Aeropuerto, 12.8 km
                                </div>
                            </div>

                            <div class="vehicle-item">
                                <div class="vehicle-name">
                                    <i class="fas fa-truck"></i> TAXI-003
                                </div>
                                <div class="vehicle-status status-online">
                                    <i class="fas fa-circle"></i> En lÃ­nea
                                </div>
                                <div class="location-badge">
                                    <i class="fas fa-location-dot"></i> Terminal, 5.1 km
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA DETALLES -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-table"></i> Detalles de VehÃ­culos
                        </div>
                        <div class="card-body">
                            <div style="overflow-x: auto;">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Placa</th>
                                            <th>Modelo</th>
                                            <th>Conductor</th>
                                            <th>UbicaciÃ³n</th>
                                            <th>Velocidad</th>
                                            <th>Estado</th>
                                            <th>Ãšltima ActualizaciÃ³n</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>TAXI-001</strong></td>
                                            <td>Toyota Prius 2023</td>
                                            <td>Juan GarcÃ­a</td>
                                            <td>Centro, Av. Principal</td>
                                            <td>45 km/h</td>
                                            <td><span class="badge bg-success">En Ruta</span></td>
                                            <td>Hace 2 min</td>
                                        </tr>
                                        <tr>
                                            <td><strong>TAXI-002</strong></td>
                                            <td>Honda Accord 2022</td>
                                            <td>MarÃ­a LÃ³pez</td>
                                            <td>Carretera a Aeropuerto</td>
                                            <td>85 km/h</td>
                                            <td><span class="badge bg-success">En Ruta</span></td>
                                            <td>Hace 1 min</td>
                                        </tr>
                                        <tr>
                                            <td><strong>TAXI-003</strong></td>
                                            <td>Hyundai Elantra 2023</td>
                                            <td>Carlos RodrÃ­guez</td>
                                            <td>Terminal de Buses</td>
                                            <td>0 km/h</td>
                                            <td><span class="badge bg-warning">Estacionado</span></td>
                                            <td>Hace 5 min</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CATALOGO ACTIVO Y PRUEBA DE VERIFICACION -->
            <div class="row mt-4 mb-4">
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-camera"></i> Catalogo activo de deteccion</span>
                            <button class="btn btn-sm btn-outline-primary" onclick="loadCatalogoActivo()">
                                <i class="fas fa-rotate"></i> Refrescar
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Filtrar tipo</label>
                                    <select class="form-control" id="catalogTipoFilter" onchange="loadCatalogoActivo()">
                                        <option value="">Todos</option>
                                        <option value="accidente">Accidente</option>
                                        <option value="vehiculo_empresa">Vehiculo empresa</option>
                                        <option value="camion_recolector">Camion recolector</option>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end justify-content-md-end">
                                    <span class="badge bg-primary" id="catalogoActivoCount">0 activos</span>
                                </div>
                            </div>
                            <div id="catalogMiniContainer" class="catalog-mini-grid">
                                <div class="text-muted">Cargando catalogo...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-vial"></i> Prueba rapida de verificacion
                        </div>
                        <div class="card-body">
                            <p class="text-mini mb-3">Prueba manual para validar que el endpoint de verificacion responde categoria y confianza.</p>
                            <form id="verifyTestForm">
                                <div class="mb-2">
                                    <label class="form-label">Imagen JPG</label>
                                    <input type="file" class="form-control" id="verifyImage" accept="image/jpeg,image/jpg" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Evento</label>
                                    <input type="text" class="form-control" id="verifyEvent" value="test_manual" maxlength="60">
                                </div>
                                <button type="submit" class="btn btn-primary w-100" id="btnVerifyNow">
                                    <i class="fas fa-play"></i> Verificar imagen
                                </button>
                            </form>
                            <pre id="verifyResult" class="mt-3 p-2 bg-light rounded text-mini" style="max-height: 220px; overflow: auto;">Sin resultados</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p>&copy; 2024 PRERMI - Monitoreo de VehÃ­culos</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Inicializar mapa Leaflet
        const map = L.map('map').setView([10.5, -66.9], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Â© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Marcadores de ejemplo
        const vehicles = [
            { id: 1, name: 'TAXI-001', lat: 10.5, lng: -66.85, status: 'online' },
            { id: 2, name: 'TAXI-002', lat: 10.45, lng: -66.95, status: 'online' },
            { id: 3, name: 'TAXI-003', lat: 10.55, lng: -66.88, status: 'online' }
        ];

        vehicles.forEach(vehicle => {
            const color = vehicle.status === 'online' ? 'green' : 'gray';
            const marker = L.circleMarker([vehicle.lat, vehicle.lng], {
                radius: 10,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);

            marker.bindPopup(`<strong>${vehicle.name}</strong><br>Estado: ${vehicle.status}`);
        });

        async function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(String(reader.result || '').split(',')[1] || '');
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        async function loadCatalogoActivo() {
            const tipo = document.getElementById('catalogTipoFilter').value;
            const params = new URLSearchParams({ action: 'list', estado: 'activo' });
            if (tipo) params.append('tipo', tipo);

            const target = document.getElementById('catalogMiniContainer');
            target.innerHTML = '<div class="text-muted">Cargando...</div>';

            try {
                const res = await fetch('/PRERMI/api/vehiculos/manage_catalogo.php?' + params.toString());
                const data = await res.json();

                if (!data.success) {
                    target.innerHTML = '<div class="text-danger">' + (data.msg || 'Error cargando catalogo') + '</div>';
                    return;
                }

                const items = data.catalogo || [];
                document.getElementById('catalogoActivoCount').innerText = items.length + ' activos';

                if (!items.length) {
                    target.innerHTML = '<div class="text-muted">No hay referencias activas.</div>';
                    return;
                }

                target.innerHTML = items.map(item => (
                    '<div class="catalog-mini-card">' +
                        '<img src="' + item.ruta_archivo + '" alt="catalog-item">' +
                        '<div class="catalog-mini-body">' +
                            '<div><strong>' + (item.etiqueta || '') + '</strong></div>' +
                            '<div class="text-mini">Tipo: ' + item.tipo_vehiculo + '</div>' +
                            '<div class="text-mini">Estado: ' + item.estado + '</div>' +
                        '</div>' +
                    '</div>'
                )).join('');
            } catch (err) {
                target.innerHTML = '<div class="text-danger">Error de red cargando catalogo.</div>';
            }
        }

        document.getElementById('verifyTestForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const file = document.getElementById('verifyImage').files[0];
            const eventName = document.getElementById('verifyEvent').value.trim() || 'test_manual';
            const resultBox = document.getElementById('verifyResult');
            const btn = document.getElementById('btnVerifyNow');

            if (!file) {
                alert('Selecciona una imagen JPG');
                return;
            }

            if (!/image\/jpeg/.test(file.type)) {
                alert('Solo se permite formato JPEG');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            resultBox.textContent = 'Procesando...';

            try {
                const imagen_base64 = await fileToBase64(file);
                const res = await fetch('/PRERMI/api/vehiculos/verificar_vehiculo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        imagen_base64,
                        evento: eventName,
                        origen: 'web_monitoreo_manual'
                    })
                });

                const data = await res.json();
                resultBox.textContent = JSON.stringify(data, null, 2);
            } catch (err) {
                resultBox.textContent = 'Error de red al verificar imagen';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-play"></i> Verificar imagen';
            }
        });

        loadCatalogoActivo();
    </script>
</body>
</html>

