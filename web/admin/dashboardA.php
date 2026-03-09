<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginA.php");
    exit;
}

require_once __DIR__ . '/../../api/utils.php';

$pdo = getPDO();
$flash = null;

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$tableName]);
    return intval($stmt->fetchColumn()) > 0;
}

try {
    // Tabla auxiliar para marcar capturas de semaforo en rojo
    $pdo->exec("CREATE TABLE IF NOT EXISTS capturas_semaforo_rojo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehiculo_id INT NOT NULL,
        marcado_por_admin_id INT NULL,
        nota VARCHAR(255) NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vehiculo_id (vehiculo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $sancionesTable = tableExists($pdo, 'sanciones') ? 'sanciones' : 'multas';

    // Acciones manuales del dashboard
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'toggle_rojo') {
            $vehiculoId = intval($_POST['vehiculo_id'] ?? 0);
            $marcar = intval($_POST['marcar'] ?? 0);

            if ($vehiculoId > 0) {
                if ($marcar === 1) {
                    $stmt = $pdo->prepare("INSERT INTO capturas_semaforo_rojo (vehiculo_id, marcado_por_admin_id, nota) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE marcado_por_admin_id = VALUES(marcado_por_admin_id), nota = VALUES(nota)");
                    $stmt->execute([$vehiculoId, intval($_SESSION['admin_id']), 'Marcado manualmente desde dashboard']);
                    $flash = ['type' => 'success', 'msg' => 'Captura marcada como semaforo en rojo'];
                } else {
                    $stmt = $pdo->prepare("DELETE FROM capturas_semaforo_rojo WHERE vehiculo_id = ?");
                    $stmt->execute([$vehiculoId]);
                    $flash = ['type' => 'warning', 'msg' => 'Captura removida de semaforo en rojo'];
                }
            }
        }

        if ($accion === 'crear_sancion') {
            $userId = intval($_POST['user_id'] ?? 0);
            $contenedorId = intval($_POST['contenedor_id'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? 'Sancion administrativa');
            $pesoRaw = trim($_POST['peso'] ?? '');
            $peso = $pesoRaw === '' ? null : floatval($pesoRaw);

            if ($userId > 0 && $contenedorId > 0) {
                $stmt = $pdo->prepare("INSERT INTO {$sancionesTable} (user_id, contenedor_id, descripcion, peso) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $contenedorId, $descripcion, $peso]);
                $flash = ['type' => 'success', 'msg' => 'Sancion creada correctamente'];
            } else {
                $flash = ['type' => 'danger', 'msg' => 'Seleccione usuario y contenedor para crear la sancion'];
            }
        }

        if ($accion === 'cambiar_visto') {
            $id = intval($_POST['id'] ?? 0);
            $seen = intval($_POST['seen_by_admin'] ?? 0);

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE {$sancionesTable} SET seen_by_admin = ? WHERE id = ?");
                $stmt->execute([$seen, $id]);
                $flash = ['type' => 'info', 'msg' => $seen === 1 ? 'Sancion marcada como vista' : 'Sancion marcada como no vista'];
            }
        }

        if ($accion === 'eliminar_sancion') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM {$sancionesTable} WHERE id = ?");
                $stmt->execute([$id]);
                $flash = ['type' => 'danger', 'msg' => 'Sancion eliminada'];
            }
        }
    }

    // Datos admin
    $adminStmt = $pdo->prepare("SELECT id, usuario, email FROM usuarios_admin WHERE id = ? LIMIT 1");
    $adminStmt->execute([$_SESSION['admin_id']]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC) ?: ['usuario' => 'Administrador', 'email' => ''];

    // Filtros de sanciones
    $filtroUserId = intval($_GET['filtro_user_id'] ?? 0);
    $filtroVisto = $_GET['filtro_visto'] ?? 'all';

    // Vehiculos/capturas
    $vehiculosStmt = $pdo->query("SELECT id, placa, tipo_vehiculo, imagen, ubicacion, fecha, hora, probabilidad, latitud, longitud, creado_en FROM vehiculos_registrados ORDER BY creado_en DESC LIMIT 200");
    $vehiculos = $vehiculosStmt->fetchAll(PDO::FETCH_ASSOC);

    // IDs marcados como rojo
    $rojoMap = [];
    $rojoStmt = $pdo->query("SELECT vehiculo_id FROM capturas_semaforo_rojo");
    foreach ($rojoStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $rojoMap[intval($r['vehiculo_id'])] = true;
    }

    // Capturas en rojo: marcadas manualmente + heuristica por texto
    $capturasRojo = [];
    foreach ($vehiculos as $v) {
        $id = intval($v['id']);
        $texto = strtolower(($v['tipo_vehiculo'] ?? '') . ' ' . ($v['ubicacion'] ?? '') . ' ' . ($v['placa'] ?? ''));
        $heuristicaRojo = strpos($texto, 'rojo') !== false || strpos($texto, 'infractor') !== false || strpos($texto, 'violacion') !== false || strpos($texto, 'semaforo') !== false;
        if (isset($rojoMap[$id]) || $heuristicaRojo) {
            $capturasRojo[] = $v;
        }
    }

    // Usuarios/Contenedores para formulario manual
    $usersStmt = $pdo->query("SELECT id, usuario, nombre, apellido FROM usuarios ORDER BY usuario ASC");
    $usuarios = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

    $contenedoresStmt = $pdo->query("SELECT id, codigo_contenedor, ubicacion FROM contenedores_registrados ORDER BY codigo_contenedor ASC");
    $contenedores = $contenedoresStmt->fetchAll(PDO::FETCH_ASSOC);

    // Lista sanciones con filtros
    $sqlSanciones = "SELECT s.id, s.user_id, s.contenedor_id, s.descripcion, s.peso, s.creado_en, s.seen_by_admin, u.usuario, u.nombre, u.apellido
                     FROM {$sancionesTable} s
                     LEFT JOIN usuarios u ON u.id = s.user_id
                     WHERE 1=1";
    $params = [];

    if ($filtroUserId > 0) {
        $sqlSanciones .= " AND s.user_id = ?";
        $params[] = $filtroUserId;
    }

    if ($filtroVisto === '0' || $filtroVisto === '1') {
        $sqlSanciones .= " AND s.seen_by_admin = ?";
        $params[] = intval($filtroVisto);
    }

    $sqlSanciones .= " ORDER BY s.creado_en DESC LIMIT 300";

    $stmtSanciones = $pdo->prepare($sqlSanciones);
    $stmtSanciones->execute($params);
    $sanciones = $stmtSanciones->fetchAll(PDO::FETCH_ASSOC);

    $sancionDetalle = null;
    $reviewId = intval($_GET['review_id'] ?? 0);
    if ($reviewId > 0) {
        $detalleStmt = $pdo->prepare("SELECT s.*, u.usuario, u.nombre, u.apellido, c.codigo_contenedor, c.ubicacion
                                      FROM {$sancionesTable} s
                                      LEFT JOIN usuarios u ON u.id = s.user_id
                                      LEFT JOIN contenedores_registrados c ON c.id = s.contenedor_id
                                      WHERE s.id = ? LIMIT 1");
        $detalleStmt->execute([$reviewId]);
        $sancionDetalle = $detalleStmt->fetch(PDO::FETCH_ASSOC);
    }

    $logsStmt = $pdo->query("SELECT id, descripcion, tipo, creado_en FROM logs_sistema ORDER BY creado_en DESC LIMIT 30");
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $flash = ['type' => 'danger', 'msg' => 'Error cargando dashboard: ' . $e->getMessage()];
    $admin = ['usuario' => 'Administrador', 'email' => ''];
    $vehiculos = [];
    $capturasRojo = [];
    $usuarios = [];
    $contenedores = [];
    $sanciones = [];
    $logs = [];
    $sancionDetalle = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f6f8ff 0%, #eef2ff 100%);
            min-height: 100vh;
            font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-admin {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
        }

        .main-wrap {
            padding: 2rem 1rem 3rem;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 1.2rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #2563eb;
        }

        .stat-value {
            font-size: 1.9rem;
            font-weight: 800;
            line-height: 1;
            color: #212529;
        }

        .panel-box {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            padding: 1rem;
            margin-top: 1rem;
        }

        .section-title {
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .red-capture-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 0.8rem;
        }

        .capture-card {
            border: 1px solid #f2c1c1;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .capture-card img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            background: #f5f5f5;
        }

        .capture-body {
            padding: 0.75rem;
            font-size: 0.86rem;
        }

        .badge-rojo {
            background: #b83232;
            color: #fff;
            font-weight: 600;
            border-radius: 12px;
            padding: 0.28rem 0.55rem;
        }

        .small-muted {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .table thead th {
            background: #f7d7d7;
        }

        .logs-wrap {
            max-height: 340px;
            overflow-y: auto;
        }

        .log-item {
            border-left: 4px solid #ddd;
            background: #fafafa;
            border-radius: 8px;
            padding: 0.7rem;
            margin-bottom: 0.6rem;
        }

        .log-item.info { border-left-color: #0d6efd; }
        .log-item.warning { border-left-color: #ffc107; }
        .log-item.error { border-left-color: #dc3545; }

        @media (max-width: 768px) {
            .main-wrap {
                padding: 1rem 0.5rem 2rem;
            }

            .capture-card img {
                height: 120px;
            }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-admin">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-shield-alt"></i> PRERMI Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navAdmin">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item me-3 text-white small">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($admin['usuario'] ?? 'Administrador'); ?>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light btn-sm" href="biores.php"><i class="fas fa-chart-line"></i> Monitoreo BioRES</a>
                </li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a class="btn btn-outline-light btn-sm" href="../../api/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid main-wrap">
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="small-muted">Capturas Totales</div>
            <div class="stat-value"><?php echo count($vehiculos); ?></div>
        </div>
        <div class="stat-card">
            <div class="small-muted">Capturas Semaforo Rojo</div>
            <div class="stat-value"><?php echo count($capturasRojo); ?></div>
        </div>
        <div class="stat-card">
            <div class="small-muted">Sanciones Registradas</div>
            <div class="stat-value"><?php echo count($sanciones); ?></div>
        </div>
        <div class="stat-card">
            <div class="small-muted">Sanciones No Vistas</div>
            <div class="stat-value"><?php echo count(array_filter($sanciones, fn($s) => intval($s['seen_by_admin']) === 0)); ?></div>
        </div>
    </div>

    <div class="panel-box">
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="monitoreo-tab" data-bs-toggle="tab" data-bs-target="#monitoreo" type="button" role="tab">
                    <i class="fas fa-video"></i> Monitoreo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sanciones-tab" data-bs-toggle="tab" data-bs-target="#sanciones" type="button" role="tab">
                    <i class="fas fa-exclamation-triangle"></i> Sanciones (Control Manual)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                    <i class="fas fa-list"></i> Logs
                </button>
            </li>
        </ul>

        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="monitoreo" role="tabpanel">
                <h5 class="section-title"><i class="fas fa-traffic-light"></i> Capturas de personas que pasaron el semaforo en rojo</h5>
                <p class="small-muted mb-3">Las capturas mostradas se obtienen por marcado manual admin o deteccion por texto (rojo/semaforo/infractor).</p>

                <div class="red-capture-grid mb-4">
                    <?php foreach ($capturasRojo as $cap): ?>
                        <div class="capture-card">
                            <img src="/PRERMI/uploads/vehiculos/<?php echo htmlspecialchars($cap['imagen']); ?>" alt="Captura rojo" onerror="this.src='https://via.placeholder.com/400x250?text=Sin+Imagen';">
                            <div class="capture-body">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong><?php echo htmlspecialchars($cap['placa']); ?></strong>
                                    <span class="badge-rojo">ROJO</span>
                                </div>
                                <div><?php echo htmlspecialchars($cap['ubicacion'] ?? 'Sin ubicacion'); ?></div>
                                <div class="small-muted mb-2"><?php echo date('d/m/Y H:i', strtotime($cap['creado_en'])); ?></div>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="accion" value="toggle_rojo">
                                    <input type="hidden" name="vehiculo_id" value="<?php echo intval($cap['id']); ?>">
                                    <input type="hidden" name="marcar" value="0">
                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Quitar de Rojo</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($capturasRojo)): ?>
                        <div class="alert alert-light border">No hay capturas marcadas en rojo actualmente.</div>
                    <?php endif; ?>
                </div>

                <h6 class="section-title"><i class="fas fa-camera"></i> Todas las capturas registradas</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Placa</th>
                                <th>Tipo</th>
                                <th>Ubicacion</th>
                                <th>Fecha</th>
                                <th>Probabilidad</th>
                                <th>Control Rojo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehiculos as $veh): ?>
                                <?php $vehId = intval($veh['id']); ?>
                                <tr>
                                    <td>
                                        <img src="/PRERMI/uploads/vehiculos/<?php echo htmlspecialchars($veh['imagen']); ?>" alt="captura" style="width:80px;height:50px;object-fit:cover;border-radius:6px;" onerror="this.src='https://via.placeholder.com/200x120?text=Sin+Imagen';">
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($veh['placa']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($veh['tipo_vehiculo']); ?></td>
                                    <td><?php echo htmlspecialchars($veh['ubicacion']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($veh['creado_en'])); ?></td>
                                    <td><?php echo number_format(floatval($veh['probabilidad']) * 100, 1); ?>%</td>
                                    <td>
                                        <?php if (isset($rojoMap[$vehId])): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="accion" value="toggle_rojo">
                                                <input type="hidden" name="vehiculo_id" value="<?php echo $vehId; ?>">
                                                <input type="hidden" name="marcar" value="0">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Quitar Rojo</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="accion" value="toggle_rojo">
                                                <input type="hidden" name="vehiculo_id" value="<?php echo $vehId; ?>">
                                                <input type="hidden" name="marcar" value="1">
                                                <button type="submit" class="btn btn-sm btn-danger">Marcar Rojo</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($vehiculos)): ?>
                                <tr><td colspan="7" class="text-center py-4"><em>No hay capturas registradas</em></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="sanciones" role="tabpanel">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <h5 class="section-title"><i class="fas fa-plus-circle"></i> Crear sancion manual</h5>
                        <form method="POST" class="border rounded p-3 bg-light">
                            <input type="hidden" name="accion" value="crear_sancion">

                            <label class="form-label">Usuario</label>
                            <select class="form-select mb-2" name="user_id" required>
                                <option value="">Seleccione usuario...</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?php echo intval($u['id']); ?>"><?php echo htmlspecialchars(($u['usuario'] ?? '') . ' - ' . ($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label class="form-label">Contenedor</label>
                            <select class="form-select mb-2" name="contenedor_id" required>
                                <option value="">Seleccione contenedor...</option>
                                <?php foreach ($contenedores as $c): ?>
                                    <option value="<?php echo intval($c['id']); ?>"><?php echo htmlspecialchars(($c['codigo_contenedor'] ?? 'N/A') . ' - ' . ($c['ubicacion'] ?? 'Sin ubicacion')); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label class="form-label">Descripcion</label>
                            <textarea class="form-control mb-2" name="descripcion" rows="3" required>Sancion administrativa por incumplimiento</textarea>

                            <label class="form-label">Peso (kg, opcional)</label>
                            <input class="form-control mb-3" type="number" step="0.001" name="peso" placeholder="Ejemplo: 2.500">

                            <button class="btn btn-danger w-100" type="submit"><i class="fas fa-save"></i> Crear sancion</button>
                        </form>
                    </div>

                    <div class="col-lg-8">
                        <h5 class="section-title"><i class="fas fa-sliders-h"></i> Control manual de sanciones</h5>

                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-5">
                                <select name="filtro_user_id" class="form-select">
                                    <option value="0">Todos los usuarios</option>
                                    <?php foreach ($usuarios as $u): ?>
                                        <option value="<?php echo intval($u['id']); ?>" <?php echo $filtroUserId === intval($u['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['usuario'] ?? ('Usuario #' . intval($u['id']))); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="filtro_visto" class="form-select">
                                    <option value="all" <?php echo $filtroVisto === 'all' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="0" <?php echo $filtroVisto === '0' ? 'selected' : ''; ?>>No vistas</option>
                                    <option value="1" <?php echo $filtroVisto === '1' ? 'selected' : ''; ?>>Vistas</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-secondary w-100" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Descripcion</th>
                                        <th>Peso</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sanciones as $s): ?>
                                        <tr>
                                            <td>#<?php echo intval($s['id']); ?></td>
                                            <td><?php echo htmlspecialchars($s['usuario'] ?: ('Usuario #' . intval($s['user_id']))); ?></td>
                                            <td><?php echo htmlspecialchars($s['descripcion']); ?></td>
                                            <td><?php echo $s['peso'] !== null ? number_format(floatval($s['peso']), 3) . ' kg' : '-'; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($s['creado_en'])); ?></td>
                                            <td>
                                                <?php if (intval($s['seen_by_admin']) === 1): ?>
                                                    <span class="badge bg-success">Vista</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">No vista</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="d-flex flex-wrap gap-1">
                                                <a class="btn btn-sm btn-outline-primary" href="?review_id=<?php echo intval($s['id']); ?>&filtro_user_id=<?php echo intval($filtroUserId); ?>&filtro_visto=<?php echo urlencode($filtroVisto); ?>#sanciones">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="accion" value="cambiar_visto">
                                                    <input type="hidden" name="id" value="<?php echo intval($s['id']); ?>">
                                                    <input type="hidden" name="seen_by_admin" value="<?php echo intval($s['seen_by_admin']) === 1 ? 0 : 1; ?>">
                                                    <button class="btn btn-sm btn-outline-secondary" type="submit" title="Cambiar visto/no visto">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                </form>

                                                <form method="POST" class="d-inline" onsubmit="return confirm('Desea eliminar esta sancion?');">
                                                    <input type="hidden" name="accion" value="eliminar_sancion">
                                                    <input type="hidden" name="id" value="<?php echo intval($s['id']); ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($sanciones)): ?>
                                        <tr><td colspan="7" class="text-center py-4"><em>No hay sanciones para el filtro seleccionado</em></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($sancionDetalle): ?>
                    <div class="alert alert-info mt-3" id="sancion-review">
                        <h6 class="mb-2"><i class="fas fa-search"></i> Revision de Sancion #<?php echo intval($sancionDetalle['id']); ?></h6>
                        <div><strong>Usuario:</strong> <?php echo htmlspecialchars($sancionDetalle['usuario'] ?: ('Usuario #' . intval($sancionDetalle['user_id']))); ?></div>
                        <div><strong>Contenedor:</strong> <?php echo htmlspecialchars($sancionDetalle['codigo_contenedor'] ?? ('ID ' . intval($sancionDetalle['contenedor_id']))); ?></div>
                        <div><strong>Ubicacion:</strong> <?php echo htmlspecialchars($sancionDetalle['ubicacion'] ?? 'Sin ubicacion'); ?></div>
                        <div><strong>Descripcion:</strong> <?php echo htmlspecialchars($sancionDetalle['descripcion']); ?></div>
                        <div><strong>Peso:</strong> <?php echo $sancionDetalle['peso'] !== null ? number_format(floatval($sancionDetalle['peso']), 3) . ' kg' : '-'; ?></div>
                        <div><strong>Estado:</strong> <?php echo intval($sancionDetalle['seen_by_admin']) === 1 ? 'Vista' : 'No vista'; ?></div>
                        <div><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s', strtotime($sancionDetalle['creado_en'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="logs" role="tabpanel">
                <h5 class="section-title"><i class="fas fa-clipboard-list"></i> Logs Recientes del Sistema</h5>
                <div class="logs-wrap">
                    <?php foreach ($logs as $log): ?>
                        <div class="log-item <?php echo htmlspecialchars($log['tipo']); ?>">
                            <div class="small-muted mb-1">
                                <strong><?php echo strtoupper(htmlspecialchars($log['tipo'])); ?></strong> - <?php echo date('d/m/Y H:i:s', strtotime($log['creado_en'])); ?>
                            </div>
                            <div><?php echo htmlspecialchars($log['descripcion']); ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <p class="text-muted">No hay logs disponibles.</p>
                    <?php endif; ?>
                </div>

                <div class="mt-3 panel-box">
                    <h6 class="section-title"><i class="fas fa-chart-bar"></i> Actividad semanal</h6>
                    <div style="height: 260px;"><canvas id="chartActividad"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ctx = document.getElementById('chartActividad');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'],
                datasets: [
                    {
                        label: 'Capturas',
                        data: [12, 18, 14, 20, 16, 9, 7],
                        backgroundColor: 'rgba(214, 69, 69, 0.7)',
                        borderColor: '#2563eb',
                        borderWidth: 1
                    },
                    {
                        label: 'Sanciones',
                        data: [3, 4, 2, 5, 4, 1, 2],
                        backgroundColor: 'rgba(102, 126, 234, 0.65)',
                        borderColor: '#667eea',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Si viene review_id, abrir automaticamente la pestana sanciones
    const hasReview = new URLSearchParams(window.location.search).get('review_id');
    if (hasReview) {
        const tabTrigger = document.querySelector('#sanciones-tab');
        if (tabTrigger) {
            const tab = new bootstrap.Tab(tabTrigger);
            tab.show();
        }
    }
</script>
</body>
</html>

