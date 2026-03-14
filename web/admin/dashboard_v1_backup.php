<?php
/**
 * dashboard.php — Panel de Control Principal (últimas 24 horas)
 * PRERMI Admin — Vista de resumen con acceso a módulos independientes
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginA.php");
    exit;
}

require_once __DIR__ . '/../../api/utils.php';

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$tableName]);
    return intval($stmt->fetchColumn()) > 0;
}

$flash = null;

try {
    $pdo = getPDO();

    // Estructura auxiliar para clasificar capturas en rojo
    $pdo->exec("CREATE TABLE IF NOT EXISTS capturas_semaforo_rojo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehiculo_id INT NOT NULL,
        marcado_por_admin_id INT NULL,
        nota VARCHAR(255) NULL,
        imagen VARCHAR(255) NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vehiculo_id (vehiculo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Agregar columna imagen si no existe (migración)
    $stmtColImg = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'capturas_semaforo_rojo' AND COLUMN_NAME = 'imagen'");
    $stmtColImg->execute();
    if (intval($stmtColImg->fetchColumn()) === 0) {
        $pdo->exec("ALTER TABLE capturas_semaforo_rojo ADD COLUMN imagen VARCHAR(255) NULL AFTER nota");
    }

    // Tabla de mensajes/notificaciones a usuarios (in-app + email)
    $pdo->exec("CREATE TABLE IF NOT EXISTS mensajes_usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        usuario_id INT NOT NULL,
        tipo ENUM('mensaje','advertencia','ban') DEFAULT 'mensaje',
        titulo VARCHAR(200) NOT NULL,
        contenido TEXT NOT NULL,
        leido TINYINT(1) DEFAULT 0,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_mu_usuario (usuario_id),
        INDEX idx_mu_admin (admin_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Columna 'activo' en usuarios para sistema de baneo
    $stmtColActivo = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'activo'");
    $stmtColActivo->execute();
    if (intval($stmtColActivo->fetchColumn()) === 0) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");
    }

    $sancionesTable = tableExists($pdo, 'sanciones') ? 'sanciones' : 'multas';

    // Acciones de control manual
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'toggle_rojo') {
            $vehiculoId = intval($_POST['vehiculo_id'] ?? 0);
            $marcar = intval($_POST['marcar'] ?? 0);

            if ($vehiculoId > 0) {
                if ($marcar === 1) {
                    // Copiar imagen del vehículo al registro de captura en rojo
                    $stmtVehImg = $pdo->prepare("SELECT imagen FROM vehiculos_registrados WHERE id = ? LIMIT 1");
                    $stmtVehImg->execute([$vehiculoId]);
                    $vehImagen = $stmtVehImg->fetchColumn() ?: null;

                    $stmt = $pdo->prepare("INSERT INTO capturas_semaforo_rojo (vehiculo_id, marcado_por_admin_id, nota, imagen) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE marcado_por_admin_id = VALUES(marcado_por_admin_id), nota = VALUES(nota), imagen = COALESCE(imagen, VALUES(imagen))");
                    $stmt->execute([$vehiculoId, intval($_SESSION['admin_id']), 'Marcado manualmente desde dashboard', $vehImagen]);
                    $flash = ['type' => 'success', 'msg' => 'Captura marcada en semaforo rojo'];
                } else {
                    $stmt = $pdo->prepare("DELETE FROM capturas_semaforo_rojo WHERE vehiculo_id = ?");
                    $stmt->execute([$vehiculoId]);
                    $flash = ['type' => 'warning', 'msg' => 'Captura removida de semaforo rojo'];
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
                $flash = ['type' => 'danger', 'msg' => 'Debe seleccionar usuario y contenedor'];
            }
        }

        if ($accion === 'cambiar_visto') {
            $id = intval($_POST['id'] ?? 0);
            $seen = intval($_POST['seen_by_admin'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE {$sancionesTable} SET seen_by_admin = ? WHERE id = ?");
                $stmt->execute([$seen, $id]);
                $flash = ['type' => 'info', 'msg' => $seen ? 'Sancion marcada como vista' : 'Sancion marcada como no vista'];
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

        // === GESTIÓN DE ADMINISTRADORES (solo superadmin) ===
        if (in_array($accion, ['aprobar_admin', 'rechazar_admin', 'cambiar_rol_admin'], true)) {
            $stmtRolCheck = $pdo->prepare("SELECT rol FROM usuarios_admin WHERE id = ? LIMIT 1");
            $stmtRolCheck->execute([intval($_SESSION['admin_id'])]);
            $rolActual = $stmtRolCheck->fetchColumn();

            if ($rolActual === 'superadmin') {
                $targetAdminId = intval($_POST['admin_id'] ?? 0);
                if ($targetAdminId > 0 && $targetAdminId !== intval($_SESSION['admin_id'])) {
                    if ($accion === 'aprobar_admin') {
                        $stmt = $pdo->prepare("UPDATE usuarios_admin SET active = 1 WHERE id = ?");
                        $stmt->execute([$targetAdminId]);
                        // Enviar correo de bienvenida al admin aprobado
                        $stmtAdmData = $pdo->prepare("SELECT usuario, email FROM usuarios_admin WHERE id = ? LIMIT 1");
                        $stmtAdmData->execute([$targetAdminId]);
                        $admData = $stmtAdmData->fetch(PDO::FETCH_ASSOC);
                        if ($admData && !empty($admData['email'])) {
                            require_once __DIR__ . '/../../config/mailer.php';
                            sendWelcomeEmail($admData['email'], $admData['usuario'], 'admin_approved');
                        }
                        $flash = ['type' => 'success', 'msg' => 'Administrador aprobado y activado correctamente'];
                    } elseif ($accion === 'rechazar_admin') {
                        $stmt = $pdo->prepare("UPDATE usuarios_admin SET active = 0 WHERE id = ?");
                        $stmt->execute([$targetAdminId]);
                        $flash = ['type' => 'warning', 'msg' => 'Administrador desactivado del sistema'];
                    } elseif ($accion === 'cambiar_rol_admin') {
                        $nuevoRol = (($_POST['nuevo_rol'] ?? '') === 'superadmin') ? 'superadmin' : 'admin';
                        $stmt = $pdo->prepare("UPDATE usuarios_admin SET rol = ? WHERE id = ?");
                        $stmt->execute([$nuevoRol, $targetAdminId]);
                        $flash = ['type' => 'info', 'msg' => 'Rol del administrador actualizado'];
                    }
                } elseif ($targetAdminId === intval($_SESSION['admin_id'])) {
                    $flash = ['type' => 'warning', 'msg' => 'No puedes modificar tu propia cuenta desde aquí'];
                }
            } else {
                $flash = ['type' => 'danger', 'msg' => 'Acceso denegado: solo superadministradores pueden gestionar admins'];
            }
        }

        // === GESTIÓN DE USUARIOS ===
        if ($accion === 'ban_usuario') {
            $targetUserId = intval($_POST['usuario_id'] ?? 0);
            if ($targetUserId > 0) {
                // Obtener datos del usuario antes de banear
                $stmtBanUser = $pdo->prepare("SELECT email, nombre, apellido, usuario FROM usuarios WHERE id = ?");
                $stmtBanUser->execute([$targetUserId]);
                $bannedUser = $stmtBanUser->fetch(PDO::FETCH_ASSOC);

                $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
                $stmt->execute([$targetUserId]);

                // Enviar correo de notificación de baneo
                if ($bannedUser && !empty($bannedUser['email'])) {
                    require_once __DIR__ . '/../../config/mailer.php';
                    $nombreCompleto = trim(($bannedUser['nombre'] ?? '') . ' ' . ($bannedUser['apellido'] ?? ''));
                    $nombreMostrar  = $nombreCompleto ?: $bannedUser['usuario'];
                    sendBanEmail($bannedUser['email'], $nombreMostrar);
                }

                // Registrar log
                $pdo->prepare("INSERT INTO logs_sistema (descripcion, tipo) VALUES (?, 'warning')")
                    ->execute(["Admin #" . intval($_SESSION['admin_id']) . " baneó al usuario #$targetUserId"]);
                $flash = ['type' => 'warning', 'msg' => 'Usuario baneado y notificado por correo'];
            }
        }

        if ($accion === 'desbanear_usuario') {
            $targetUserId = intval($_POST['usuario_id'] ?? 0);
            if ($targetUserId > 0) {
                $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
                $stmt->execute([$targetUserId]);
                $pdo->prepare("INSERT INTO logs_sistema (descripcion, tipo) VALUES (?, 'info')")
                    ->execute(["Admin #" . intval($_SESSION['admin_id']) . " reactivó al usuario #$targetUserId"]);
                $flash = ['type' => 'success', 'msg' => 'Usuario reactivado correctamente'];
            }
        }

        if ($accion === 'enviar_mensaje') {
            $targetUserId = intval($_POST['usuario_id'] ?? 0);
            $titulo       = trim($_POST['titulo'] ?? '');
            $contenido    = trim($_POST['contenido'] ?? '');
            $tipo         = in_array($_POST['tipo'] ?? '', ['mensaje', 'advertencia', 'ban'], true) ? $_POST['tipo'] : 'mensaje';

            if ($targetUserId > 0 && $titulo !== '' && $contenido !== '') {
                // Guardar notificación in-app
                $stmtMsg = $pdo->prepare("INSERT INTO mensajes_usuarios (admin_id, usuario_id, tipo, titulo, contenido) VALUES (?, ?, ?, ?, ?)");
                $stmtMsg->execute([intval($_SESSION['admin_id']), $targetUserId, $tipo, $titulo, $contenido]);

                // Enviar correo al usuario
                $stmtDestUser = $pdo->prepare("SELECT nombre, apellido, email FROM usuarios WHERE id = ? LIMIT 1");
                $stmtDestUser->execute([$targetUserId]);
                $destUser = $stmtDestUser->fetch(PDO::FETCH_ASSOC);
                if ($destUser && !empty($destUser['email'])) {
                    $nombreDest = htmlspecialchars(trim(($destUser['nombre'] ?? '') . ' ' . ($destUser['apellido'] ?? '')));
                    $tipoLabels = ['mensaje' => 'Mensaje', 'advertencia' => '⚠️ Advertencia', 'ban' => '🚫 Aviso de Suspensión'];
                    $asuntoEmail = '[PRERMI] ' . ($tipoLabels[$tipo] ?? 'Notificación') . ': ' . $titulo;
                    $bodyEmail   = "
                        <div style='font-family:sans-serif;max-width:600px;margin:auto;'>
                        <div style='background:linear-gradient(135deg,#667eea,#764ba2);padding:24px;border-radius:12px 12px 0 0;text-align:center;'>
                            <h2 style='color:white;margin:0;'>🛡️ PRERMI</h2>
                        </div>
                        <div style='background:#f9fafb;padding:28px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;'>
                            <p>Estimado/a <strong>{$nombreDest}</strong>,</p>
                            <p>{$contenido}</p>
                            <hr style='border-color:#e5e7eb;'>
                            <p style='color:#6b7280;font-size:12px;'>Este mensaje fue enviado desde el panel administrativo de PRERMI. Para consultas contacte a soporte.</p>
                        </div></div>";
                    enviarCorreo($destUser['email'], $asuntoEmail, $bodyEmail);
                }
                $pdo->prepare("INSERT INTO logs_sistema (descripcion, tipo) VALUES (?, 'info')")
                    ->execute(["Admin #" . intval($_SESSION['admin_id']) . " envió mensaje tipo '$tipo' al usuario #$targetUserId"]);
                $flash = ['type' => 'success', 'msg' => 'Mensaje enviado al usuario por notificación app y correo electrónico'];
            } else {
                $flash = ['type' => 'danger', 'msg' => 'Complete el título y contenido del mensaje'];
            }
        }
    }

    // Filtros en listado de sanciones
    $filtroUserId = intval($_GET['filtro_user_id'] ?? 0);
    $filtroVisto = $_GET['filtro_visto'] ?? 'all';

    // Admin
    $stmtAdmin = $pdo->prepare("SELECT id, usuario, email, rol FROM usuarios_admin WHERE id = ? LIMIT 1");
    $stmtAdmin->execute([$_SESSION['admin_id']]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC) ?: ['usuario' => 'Administrador', 'email' => '', 'rol' => 'admin'];

    // Capturas ESP32-CAM
    $stmtVehiculos = $pdo->query("SELECT id, placa, tipo_vehiculo, imagen, ubicacion, fecha, hora, probabilidad, latitud, longitud, creado_en FROM vehiculos_registrados ORDER BY creado_en DESC LIMIT 300");
    $vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);

    $rojoMap = [];
    $rojoStmt = $pdo->query("SELECT vehiculo_id, imagen FROM capturas_semaforo_rojo");
    foreach ($rojoStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rojoMap[intval($row['vehiculo_id'])] = ['imagen' => $row['imagen']];
    }

    $capturasRojo = [];
    foreach ($vehiculos as $v) {
        $vehId = intval($v['id']);
        $texto = strtolower(($v['tipo_vehiculo'] ?? '') . ' ' . ($v['ubicacion'] ?? '') . ' ' . ($v['placa'] ?? ''));
        $heuristicaRojo = strpos($texto, 'rojo') !== false || strpos($texto, 'semaforo') !== false || strpos($texto, 'infractor') !== false || strpos($texto, 'violacion') !== false;
        if (isset($rojoMap[$vehId]) || $heuristicaRojo) {
            // Usar la imagen almacenada en la captura, con fallback a la imagen del vehículo
            $v['imagen_rojo'] = $rojoMap[$vehId]['imagen'] ?? $v['imagen'];
            $capturasRojo[] = $v;
        }
    }

    // Contenedores (usado en formularios de sanciones)
    $stmtContenedores = $pdo->query("SELECT id, codigo_contenedor, ubicacion, tipo_contenedor, estado, ultimo_token, token_generado_en, token_expira_en, creado_en, actualizado_en FROM contenedores_registrados ORDER BY actualizado_en DESC");
    $contenedores = $stmtContenedores->fetchAll(PDO::FETCH_ASSOC);

    // Depositos registrados por usuarios
    $stmtDepositos = $pdo->query("SELECT d.id, d.id_usuario, d.id_contenedor, d.peso, d.tipo_residuo, d.credito_kwh, d.metal_detectado, d.token_usado, d.fecha_hora, d.creado_en,
                                         u.usuario, u.nombre, u.apellido,
                                         c.codigo_contenedor, c.ubicacion, c.latitud, c.longitud
                                  FROM depositos d
                                  LEFT JOIN usuarios u ON u.id = d.id_usuario
                                  LEFT JOIN contenedores_registrados c ON c.id = d.id_contenedor
                                  ORDER BY COALESCE(d.creado_en, d.fecha_hora) DESC
                                  LIMIT 400");
    $depositos = $stmtDepositos->fetchAll(PDO::FETCH_ASSOC);

    // Usuarios para formularios
    $stmtUsuarios = $pdo->query("SELECT id, usuario, nombre, apellido FROM usuarios ORDER BY usuario ASC");
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

    // Lista de sanciones (filtro + join usuario)
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
    $multas = $stmtSanciones->fetchAll(PDO::FETCH_ASSOC); // variable historica

    // Revision puntual de sancion
    $sancionDetalle = null;
    $reviewId = intval($_GET['review_id'] ?? 0);
    if ($reviewId > 0) {
        $stmtDetalle = $pdo->prepare("SELECT s.*, u.usuario, u.nombre, u.apellido, c.codigo_contenedor, c.ubicacion
                                      FROM {$sancionesTable} s
                                      LEFT JOIN usuarios u ON u.id = s.user_id
                                      LEFT JOIN contenedores_registrados c ON c.id = s.contenedor_id
                                      WHERE s.id = ? LIMIT 1");
        $stmtDetalle->execute([$reviewId]);
        $sancionDetalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);
    }

    // Logs
    $stmtLogs = $pdo->query("SELECT id, descripcion, tipo, creado_en FROM logs_sistema ORDER BY creado_en DESC LIMIT 40");
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

    // ===== AHORROS ELÉCTRICOS (sistema completo) =====
    $TARIFA_RD_KWH = 14.00;
    $savingsMonthLabels = [];
    $savingsMonthRD     = [];
    $savingsMonthKwh    = [];
    $savingsTotalKwh    = 0;
    $savingsTotalRD     = 0;
    $savingsMesRD       = 0;
    $savingsMesKwh      = 0;

    $stmtSav = $pdo->query("
        SELECT
            DATE_FORMAT(COALESCE(creado_en, fecha_hora), '%Y-%m') AS mes,
            SUM(COALESCE(credito_kwh, 0)) AS kwh_total
        FROM depositos
        WHERE COALESCE(creado_en, fecha_hora) >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY mes
        ORDER BY mes ASC
    ");
    $savRows = $stmtSav->fetchAll(PDO::FETCH_ASSOC);
    $savMap = [];
    foreach ($savRows as $r) { $savMap[$r['mes']] = (float)$r['kwh_total']; }

    for ($i = 5; $i >= 0; $i--) {
        $ts    = strtotime("-$i months");
        $key   = date('Y-m', $ts);
        $label = date('M Y', $ts);
        $kwh   = $savMap[$key] ?? 0;
        $rd    = round($kwh * $TARIFA_RD_KWH, 2);
        $savingsMonthLabels[] = $label;
        $savingsMonthKwh[]    = $kwh;
        $savingsMonthRD[]     = $rd;
        $savingsTotalKwh     += $kwh;
        $savingsTotalRD      += $rd;
    }
    $curKey      = date('Y-m');
    $savingsMesKwh = $savMap[$curKey] ?? 0;
    $savingsMesRD  = round($savingsMesKwh * $TARIFA_RD_KWH, 2);

    // Top usuarios por ahorro
    $stmtTopUser = $pdo->query("
        SELECT u.nombre, u.apellido, u.usuario,
               SUM(COALESCE(d.credito_kwh,0)) AS kwh_total
        FROM depositos d
        LEFT JOIN usuarios u ON u.id = d.id_usuario
        GROUP BY d.id_usuario
        ORDER BY kwh_total DESC
        LIMIT 8
    ");
    $topUsers = $stmtTopUser->fetchAll(PDO::FETCH_ASSOC);

    // ===== PANEL DE CONTROL: ADMINS Y USUARIOS =====
    $isSuperAdmin = ($admin['rol'] === 'superadmin');

    $stmtAllAdmins = $pdo->query("SELECT id, usuario, nombre, apellido, email, verified, active, rol, creado_en FROM usuarios_admin ORDER BY creado_en DESC");
    $allAdmins = $stmtAllAdmins->fetchAll(PDO::FETCH_ASSOC);

    $stmtTodosUsuarios = $pdo->query("SELECT id, nombre, apellido, usuario, email, telefono, verified, COALESCE(activo, 1) AS activo, creado_en FROM usuarios ORDER BY creado_en DESC");
    $todosUsuarios = $stmtTodosUsuarios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de conexiÃ³n: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="/PRERMI/web/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-admin {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            box-shadow: 0 5px 20px rgba(37, 99, 235, 0.3);
            padding: 1rem 2rem;
        }

        .navbar-admin .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .admin-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .container-main {
            padding: 2rem 1.4rem;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.2rem;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-top: 4px solid #2563eb;
        }

        .stat-icon {
            font-size: 1.9rem;
            color: #2563eb;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            line-height: 1;
        }

        .stat-label {
            color: #666;
            font-size: 0.93rem;
            margin-top: 0.4rem;
        }

        .tabs-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 1.2rem;
            margin-bottom: 1.3rem;
        }

        .nav-tabs .nav-link {
            color: #666;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }

        .nav-tabs .nav-link.active {
            color: #2563eb;
            background: transparent;
            border-bottom-color: #2563eb;
        }

        .tab-content {
            padding-top: 1.2rem;
        }

        .section-header {
            background: #fff;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 5px solid #2563eb;
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        }

        .section-header h2 {
            color: #2563eb;
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .table-container thead {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: white;
        }

        .table-container thead th {
            border: none;
            font-weight: 600;
            padding: 0.9rem;
            white-space: nowrap;
        }

        .table-container tbody td {
            padding: 0.85rem;
            border-color: #f0f0f0;
            vertical-align: middle;
        }

        .table-container tbody tr:hover {
            background: #f9f9f9;
        }

        .map-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        #mapCaptures, #mapDeposits {
            height: 350px;
        }

        .badge-metal {
            background: #2563eb;
            color: white;
            padding: 0.34rem 0.75rem;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .badge-ok {
            background: #51cf66;
            color: white;
            padding: 0.34rem 0.75rem;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 0.5rem 0.9rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #7c3aed;
            border-color: #7c3aed;
            color: white;
        }

        .red-capture-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 0.8rem;
            margin-bottom: 1rem;
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
            padding: 0.2rem 0.55rem;
        }

        .badge-tinyml {
            background: #2f9e44;
            color: #fff;
            font-weight: 600;
            border-radius: 12px;
            padding: 0.2rem 0.55rem;
        }

        .small-muted {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .log-item {
            padding: 1rem;
            border-left: 4px solid #ddd;
            margin-bottom: 1rem;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .log-item.error {
            border-left-color: #2563eb;
            background: rgba(37, 99, 235, 0.05);
        }

        .log-item.warning {
            border-left-color: #ffd93d;
            background: rgba(255, 217, 61, 0.05);
        }

        .log-item.info {
            border-left-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .log-time {
            color: #999;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }

            .container-main {
                padding: 1rem 0.6rem;
            }

            #mapCaptures, #mapDeposits {
                height: 280px;
            }

            .capture-card img {
                height: 120px;
            }
        }

        /* ===== PANEL DE CONTROL: ADMINS Y USUARIOS ===== */
        .mgmt-card {
            background: white;
            border-radius: 14px;
            padding: 1.25rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            border: 1.5px solid #e8edf5;
            transition: box-shadow 0.25s, transform 0.25s;
            height: 100%;
        }
        .mgmt-card:hover {
            box-shadow: 0 8px 28px rgba(102,126,234,0.2);
            transform: translateY(-2px);
        }
        .mgmt-card-pending { border-color: #f97316; background: #fffbf5; }
        .mgmt-card-super   { border-color: #7c3aed; background: #faf8ff; }

        .mgmt-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }
        .avatar-super   { background: linear-gradient(135deg, #667eea, #764ba2); }
        .avatar-admin   { background: linear-gradient(135deg, #2563eb, #7c3aed); }
        .avatar-pending { background: linear-gradient(135deg, #f97316, #ea580c); }

        .mgmt-avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }
        .mgmt-name  { font-weight: 700; font-size: 0.97rem; color: #1e293b; }
        .mgmt-email { font-size: 0.81rem; color: #64748b; word-break: break-all; }

        .badge-tu {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-size: 10px;
            font-weight: 700;
            border-radius: 20px;
            padding: 2px 9px;
            letter-spacing: .5px;
            white-space: nowrap;
        }
        .mgmt-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }
        .badge-mgmt-ok      { background: #d1fae5; color: #065f46; }
        .badge-mgmt-warn    { background: #fff3cd; color: #92400e; }
        .badge-mgmt-info    { background: #dbeafe; color: #1e40af; }
        .badge-mgmt-super   { background: #ede9fe; color: #5b21b6; }
        .badge-mgmt-neutral { background: #f1f5f9; color: #475569; }

        .ctrl-stat-card {
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            color: #fff;
            transition: transform 0.2s;
        }
        .ctrl-stat-card:hover { transform: translateY(-2px); }
        .ctrl-stat-card .num  { font-size: 28px; font-weight: 800; }
        .ctrl-stat-card .lbl  { font-size: 11px; opacity: .85; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-admin">
        <div class="container-fluid">
            <div class="navbar-brand">
                <i class="fas fa-shield-alt"></i> PRERMI - Admin
            </div>
            <div class="ms-auto admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin['usuario'] ?? 'A', 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($admin['usuario'] ?? 'Administrador'); ?></span>

                <a href="biores.php" class="logout-btn ms-2" title="Monitoreo en tiempo real de generacion energetica">
                    <i class="fas fa-chart-line"></i> Monitoreo en tiempo real de generacion energetica
                </a>

                <a href="../../api/admin/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-video"></i></div>
                <div class="stat-value"><?php echo count($vehiculos); ?></div>
                <div class="stat-label">Capturas Automovilisticas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-traffic-light"></i></div>
                <div class="stat-value"><?php echo count($capturasRojo); ?></div>
                <div class="stat-label">Capturas en Semaforo Rojo</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-trash"></i></div>
                <div class="stat-value"><?php echo count($depositos); ?></div>
                <div class="stat-label">Depositos Registrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?php echo count($multas); ?></div>
                <div class="stat-label">Sanciones registradas</div>
            </div>
        </div>

        <div class="tabs-section">
            <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="monitors-tab" data-bs-toggle="tab" data-bs-target="#monitors" type="button" role="tab">
                        <i class="fas fa-video"></i> Monitoreo Automovilistico
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="deposits-tab" data-bs-toggle="tab" data-bs-target="#deposits" type="button" role="tab">
                        <i class="fas fa-box-open"></i> Depositos Registrados
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="fines-tab" data-bs-toggle="tab" data-bs-target="#fines" type="button" role="tab">
                        <i class="fas fa-exclamation-triangle"></i> Sanciones a los Usuarios
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="savings-tab" data-bs-toggle="tab" data-bs-target="#savings" type="button" role="tab">
                        <i class="fas fa-bolt"></i> Ahorro Eléctrico (RD$)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                        <i class="fas fa-list"></i> Logs del Sistema
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab">
                        <i class="fas fa-users-cog"></i> Administradores
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="adminTabsContent">
                <div class="tab-pane fade show active" id="monitors" role="tabpanel">
                    <div class="section-header">
                        <h2><i class="fas fa-map"></i> Monitoreo de Capturas ESP32-CAM</h2>
                    </div>

                    <div class="section-header">
                        <h2><i class="fas fa-traffic-light"></i> Capturas de personas que pasaron el semaforo en rojo</h2>
                        <div class="small-muted mt-2">Marcado manual por admin o deteccion por texto (rojo/semaforo/infractor).</div>
                    </div>

                    <div class="red-capture-grid">
                        <?php foreach ($capturasRojo as $cap): ?>
                            <div class="capture-card">
                                <img src="/PRERMI/uploads/vehiculos/<?php echo htmlspecialchars($cap['imagen_rojo'] ?? $cap['imagen']); ?>" alt="Captura en rojo" onerror="this.src='https://placehold.co/400x250?text=Sin+Imagen';">
                                <div class="capture-body">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong><?php echo htmlspecialchars($cap['placa']); ?></strong>
                                        <span class="badge-rojo">ROJO</span>
                                    </div>
                                    <div><?php echo htmlspecialchars($cap['ubicacion'] ?? 'Sin ubicacion'); ?></div>
                                    <div class="small-muted mb-2"><?php echo date('d/m/Y H:i', strtotime($cap['creado_en'])); ?></div>
                                    <form method="POST">
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

                    <div class="map-container">
                        <div id="mapCaptures"></div>
                        <div class="px-3 py-2 border-top small-muted">
                            <span class="badge-rojo me-2">Rojo</span> Vehiculo pasando en semaforo rojo
                            <span class="badge-tinyml ms-3 me-2">Verde</span> Vehiculo detectado por TinyML
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Placa</th>
                                    <th>Tipo</th>
                                    <th>UbicaciÃ³n</th>
                                    <th>Fecha/Hora</th>
                                    <th>Confianza</th>
                                    <th>Imagen</th>
                                    <th>Semaforo Rojo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vehiculos as $veh): ?>
                                    <?php $vehId = intval($veh['id']); ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($veh['placa']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($veh['tipo_vehiculo']); ?></td>
                                        <td><?php echo htmlspecialchars($veh['ubicacion']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($veh['creado_en'])); ?></td>
                                        <td>
                                            <?php
                                            $conf = intval(floatval($veh['probabilidad']) * 100);
                                            $badgeClass = $conf >= 80 ? 'badge-ok' : 'badge-metal';
                                            ?>
                                            <span class="<?php echo $badgeClass; ?>"><?php echo $conf; ?>%</span>
                                        </td>
                                        <td>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal" onclick="showImage('<?php echo htmlspecialchars($veh['imagen']); ?>')">
                                                <i class="fas fa-image"></i> Ver
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (isset($rojoMap[$vehId])): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="accion" value="toggle_rojo">
                                                    <input type="hidden" name="vehiculo_id" value="<?php echo $vehId; ?>">
                                                    <input type="hidden" name="marcar" value="0">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Quitar</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="accion" value="toggle_rojo">
                                                    <input type="hidden" name="vehiculo_id" value="<?php echo $vehId; ?>">
                                                    <input type="hidden" name="marcar" value="1">
                                                    <button type="submit" class="btn btn-sm btn-danger">Marcar</button>
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

                <div class="tab-pane fade" id="deposits" role="tabpanel">
                    <div class="section-header">
                        <h2><i class="fas fa-box-open"></i> Depositos Registrados por Usuarios</h2>
                    </div>

                    <div class="small-muted mb-2">
                        El mapa muestra donde se realizo cada deposito. Cada usuario tiene un color diferente.
                    </div>

                    <div class="map-container">
                        <div id="mapDeposits"></div>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID Deposito</th>
                                    <th>Usuario</th>
                                    <th>Contenedor</th>
                                    <th>Ubicacion</th>
                                    <th>Peso</th>
                                    <th>Credito (kWh)</th>
                                    <th>Metal</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($depositos as $dep): ?>
                                    <?php
                                    $usuarioLabel = trim((string)(($dep['nombre'] ?? '') . ' ' . ($dep['apellido'] ?? '')));
                                    if ($usuarioLabel === '') {
                                        $usuarioLabel = $dep['usuario'] ?? ('Usuario #' . intval($dep['id_usuario']));
                                    }
                                    $fechaDeposito = $dep['creado_en'] ?: $dep['fecha_hora'];
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo intval($dep['id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($usuarioLabel); ?></td>
                                        <td><?php echo htmlspecialchars($dep['codigo_contenedor'] ?? ('ID ' . intval($dep['id_contenedor']))); ?></td>
                                        <td><?php echo htmlspecialchars($dep['ubicacion'] ?? 'Sin ubicacion'); ?></td>
                                        <td><?php echo $dep['peso'] !== null ? number_format(floatval($dep['peso']), 3) . ' kg' : '-'; ?></td>
                                        <td><?php echo $dep['credito_kwh'] !== null ? number_format(floatval($dep['credito_kwh']), 3) : '-'; ?></td>
                                        <td>
                                            <?php if (intval($dep['metal_detectado']) === 1): ?>
                                                <span class="badge bg-success">Si</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $fechaDeposito ? date('d/m/Y H:i', strtotime($fechaDeposito)) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($depositos)): ?>
                                    <tr><td colspan="8" class="text-center py-4"><em>No hay depositos registrados</em></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="fines" role="tabpanel">
                    <div class="section-header">
                        <h2><i class="fas fa-exclamation-triangle"></i> Sanciones - Control Manual</h2>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-lg-4">
                            <div class="table-container p-3">
                                <h6 class="mb-3"><i class="fas fa-plus-circle"></i> Crear sancion</h6>
                                <form method="POST">
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
                        </div>

                        <div class="col-lg-8">
                            <div class="table-container p-3">
                                <h6 class="mb-3"><i class="fas fa-sliders-h"></i> Filtros y listado</h6>
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
                                            <?php foreach ($multas as $multa): ?>
                                                <tr>
                                                    <td>#<?php echo intval($multa['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($multa['usuario'] ?: ('Usuario #' . intval($multa['user_id']))); ?></td>
                                                    <td><?php echo htmlspecialchars($multa['descripcion']); ?></td>
                                                    <td><?php echo $multa['peso'] !== null ? number_format(floatval($multa['peso']), 3) . ' kg' : '-'; ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($multa['creado_en'])); ?></td>
                                                    <td>
                                                        <?php if (intval($multa['seen_by_admin']) === 1): ?>
                                                            <span class="badge bg-success">Vista</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">No vista</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="d-flex flex-wrap gap-1">
                                                        <a class="btn btn-sm btn-outline-primary" href="?review_id=<?php echo intval($multa['id']); ?>&filtro_user_id=<?php echo intval($filtroUserId); ?>&filtro_visto=<?php echo urlencode($filtroVisto); ?>#fines">
                                                            <i class="fas fa-eye"></i>
                                                        </a>

                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="accion" value="cambiar_visto">
                                                            <input type="hidden" name="id" value="<?php echo intval($multa['id']); ?>">
                                                            <input type="hidden" name="seen_by_admin" value="<?php echo intval($multa['seen_by_admin']) === 1 ? 0 : 1; ?>">
                                                            <button class="btn btn-sm btn-outline-secondary" type="submit" title="Cambiar visto/no visto">
                                                                <i class="fas fa-toggle-on"></i>
                                                            </button>
                                                        </form>

                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Desea eliminar esta sancion?');">
                                                            <input type="hidden" name="accion" value="eliminar_sancion">
                                                            <input type="hidden" name="id" value="<?php echo intval($multa['id']); ?>">
                                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($multas)): ?>
                                                <tr><td colspan="7" class="text-center py-4"><em>No hay sanciones para el filtro seleccionado</em></td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($sancionDetalle): ?>
                        <div class="alert alert-info" id="sancion-review">
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

                <!-- ===== TAB: AHORRO ELÉCTRICO ===== -->
                <div class="tab-pane fade" id="savings" role="tabpanel">
                    <div class="section-header" style="border-left-color:#06b6d4;">
                        <h2 style="color:#06b6d4;"><i class="fas fa-bolt"></i> Ahorro en Electricidad — Sistema PRERMI (Pesos Dominicanos)</h2>
                    </div>

                    <!-- KPIs -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-sm-6">
                            <div style="background:linear-gradient(135deg,#0e7490,#06b6d4);border-radius:14px;padding:22px;text-align:center;box-shadow:0 4px 18px #06b6d440;">
                                <div style="font-size:28px;">💡</div>
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.75);margin:6px 0;">Este mes</div>
                                <div style="font-size:26px;font-weight:800;color:#fff;">RD$ <?php echo number_format($savingsMesRD,2); ?></div>
                                <div style="font-size:12px;color:rgba(255,255,255,0.7);"><?php echo number_format($savingsMesKwh,3); ?> kWh generados</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div style="background:linear-gradient(135deg,#065f46,#10b981);border-radius:14px;padding:22px;text-align:center;box-shadow:0 4px 18px #10b98140;">
                                <div style="font-size:28px;">🌿</div>
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.75);margin:6px 0;">Ahorro total (6 meses)</div>
                                <div style="font-size:26px;font-weight:800;color:#fff;">RD$ <?php echo number_format($savingsTotalRD,2); ?></div>
                                <div style="font-size:12px;color:rgba(255,255,255,0.7);"><?php echo number_format($savingsTotalKwh,3); ?> kWh totales</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div style="background:linear-gradient(135deg,#5b21b6,#7c3aed);border-radius:14px;padding:22px;text-align:center;box-shadow:0 4px 18px #7c3aed40;">
                                <div style="font-size:28px;">⚡</div>
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.75);margin:6px 0;">Tarifa referencia</div>
                                <div style="font-size:26px;font-weight:800;color:#fff;">RD$ <?php echo number_format($TARIFA_RD_KWH,2); ?></div>
                                <div style="font-size:12px;color:rgba(255,255,255,0.7);">por kWh (EDENORTE/EDESUR)</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div style="background:linear-gradient(135deg,#9a3412,#f97316);border-radius:14px;padding:22px;text-align:center;box-shadow:0 4px 18px #f9731640;">
                                <div style="font-size:28px;">📦</div>
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.75);margin:6px 0;">Total depósitos</div>
                                <div style="font-size:26px;font-weight:800;color:#fff;"><?php echo count($depositos); ?></div>
                                <div style="font-size:12px;color:rgba(255,255,255,0.7);">registros verificados</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficas -->
                    <div class="row g-3 mb-4">
                        <div class="col-lg-8">
                            <div style="background:#1e293b;border-radius:16px;padding:24px;border:1px solid rgba(6,182,212,0.2);box-shadow:0 6px 24px rgba(0,0,0,0.25);">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                                    <h6 style="color:#e2e8f0;margin:0;font-weight:700;"><i class="fas fa-chart-bar" style="color:#06b6d4;"></i> Reducción mensual de costo eléctrico (últimos 6 meses)</h6>
                                    <span style="background:linear-gradient(90deg,#06b6d4,#10b981);color:#fff;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;">RD$ / kWh</span>
                                </div>
                                <canvas id="adminSavingsChart" height="120"></canvas>
                                <p style="text-align:center;font-size:11px;color:#475569;margin-top:12px;">
                                    Crédito: 0.5 kWh por kg depositado × tarifa de referencia RD$ <?php echo number_format($TARIFA_RD_KWH,2); ?>/kWh
                                </p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div style="background:#1e293b;border-radius:16px;padding:24px;border:1px solid rgba(124,58,237,0.2);box-shadow:0 6px 24px rgba(0,0,0,0.25);">
                                <h6 style="color:#e2e8f0;margin:0 0 16px;font-weight:700;"><i class="fas fa-trophy" style="color:#7c3aed;"></i> Top usuarios por kWh</h6>
                                <canvas id="adminTopUsersChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla resumen mensual -->
                    <div class="table-container">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>kWh Generados</th>
                                    <th>Ahorro Estimado (RD$)</th>
                                    <th>Equivalencia (días sin luz)</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($savingsMonthLabels as $idx => $lbl): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($lbl); ?></strong></td>
                                    <td><?php echo number_format($savingsMonthKwh[$idx],4); ?> kWh</td>
                                    <td><strong style="color:#10b981;">RD$ <?php echo number_format($savingsMonthRD[$idx],2); ?></strong></td>
                                    <td><?php
                                        $dias = $savingsMonthKwh[$idx] > 0 ? round($savingsMonthKwh[$idx] / 1.2, 1) : 0;
                                        echo $dias . ' hrs equiv.';
                                    ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="logs" role="tabpanel">
                    <div class="section-header">
                        <h2><i class="fas fa-list"></i> Logs del Sistema</h2>
                    </div>

                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($logs as $log): ?>
                            <div class="log-item <?php echo htmlspecialchars($log['tipo']); ?>">
                                <div class="log-time">
                                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i:s', strtotime($log['creado_en'])); ?>
                                    <span class="ms-2"><strong><?php echo strtoupper($log['tipo']); ?></strong></span>
                                </div>
                                <div style="margin-top: 0.5rem;"><?php echo htmlspecialchars($log['descripcion']); ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <p class="text-center py-4"><em>No hay logs registrados</em></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="admins" role="tabpanel">

                    <!-- KPIs del panel de control -->
                    <div class="row g-3 mb-4 mt-1">
                        <?php
                        $admPendientes  = count(array_filter($allAdmins, fn($a) => !intval($a['active'])));
                        $admActivos     = count(array_filter($allAdmins, fn($a)  => intval($a['active'])));
                        $usrBaneados    = count(array_filter($todosUsuarios, fn($u) => !intval($u['activo'])));
                        ?>
                        <div class="col-6 col-md-3">
                            <div class="ctrl-stat-card" style="background:linear-gradient(135deg,#667eea,#764ba2);box-shadow:0 4px 15px rgba(102,126,234,.35);">
                                <div class="num"><?php echo count($allAdmins); ?></div>
                                <div class="lbl"><i class="fas fa-user-tie"></i> Total Admins</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <?php if ($isSuperAdmin): ?>
                            <div class="ctrl-stat-card" style="background:linear-gradient(135deg,#f97316,#ea580c);box-shadow:0 4px 15px rgba(249,115,22,.35);cursor:pointer;transition:transform .2s,box-shadow .2s;"
                                 onclick="window.location.href='panel_admin_approval.php'"
                                 onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 28px rgba(249,115,22,.55)'"
                                 onmouseleave="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(249,115,22,.35)'"
                                 title="Ver admins pendientes de aprobación">
                                <div class="num"><?php echo $admPendientes; ?></div>
                                <div class="lbl"><i class="fas fa-clock"></i> Admins Pendientes</div>
                                <?php if ($admPendientes > 0): ?>
                                <div style="font-size:0.7rem;margin-top:4px;opacity:.85;"><i class="fas fa-arrow-right"></i> Revisar ahora</div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="ctrl-stat-card" style="background:linear-gradient(135deg,#f97316,#ea580c);box-shadow:0 4px 15px rgba(249,115,22,.35);">
                                <div class="num"><?php echo $admPendientes; ?></div>
                                <div class="lbl"><i class="fas fa-clock"></i> Admins Pendientes</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="ctrl-stat-card" style="background:linear-gradient(135deg,#10b981,#065f46);box-shadow:0 4px 15px rgba(16,185,129,.35);">
                                <div class="num"><?php echo count($todosUsuarios); ?></div>
                                <div class="lbl"><i class="fas fa-users"></i> Total Usuarios</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="ctrl-stat-card" style="background:linear-gradient(135deg,#ef4444,#b91c1c);box-shadow:0 4px 15px rgba(239,68,68,.35);">
                                <div class="num"><?php echo $usrBaneados; ?></div>
                                <div class="lbl"><i class="fas fa-ban"></i> Usuarios Baneados</div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECCIÓN: VERIFICACIÓN DE ADMINISTRADORES ===== -->
                    <?php if ($isSuperAdmin): ?>
                    <div class="section-header" style="border-left-color:#667eea;">
                        <h2 style="color:#667eea;"><i class="fas fa-user-shield"></i> Verificación y Control de Administradores</h2>
                    </div>
                    <p class="small-muted mb-3">
                        <i class="fas fa-info-circle text-primary"></i>
                        Como <strong>superadministrador</strong> puedes aprobar, desactivar y cambiar el rol de cada administrador del sistema.
                        Los administradores deben ser aprobados antes de poder acceder.
                    </p>

                    <div class="row g-3 mb-4">
                        <?php foreach ($allAdmins as $admEntry): ?>
                        <?php
                        $isPending  = !intval($admEntry['active']);
                        $isVerified = intval($admEntry['verified']);
                        $isSelf     = (intval($admEntry['id']) === intval($_SESSION['admin_id']));
                        $initials   = strtoupper(substr($admEntry['usuario'] ?? 'A', 0, 2));
                        $cardClass  = $isSelf ? '' : ($isPending ? 'mgmt-card-pending' : ($admEntry['rol'] === 'superadmin' ? 'mgmt-card-super' : ''));
                        $avatarClass= $admEntry['rol'] === 'superadmin' ? 'avatar-super' : ($isPending ? 'avatar-pending' : 'avatar-admin');
                        ?>
                        <div class="col-xl-4 col-lg-6">
                            <div class="mgmt-card <?php echo $cardClass; ?>">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="mgmt-avatar <?php echo $avatarClass; ?>">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="mgmt-name d-flex align-items-center gap-2 flex-wrap">
                                            <?php echo htmlspecialchars($admEntry['usuario']); ?>
                                            <?php if ($isSelf): ?><span class="badge-tu">TÚ</span><?php endif; ?>
                                        </div>
                                        <div class="mgmt-email"><?php echo htmlspecialchars($admEntry['email']); ?></div>
                                        <div class="small-muted mt-1">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('d/m/Y', strtotime($admEntry['creado_en'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    <span class="mgmt-badge <?php echo intval($admEntry['active']) ? 'badge-mgmt-ok' : 'badge-mgmt-warn'; ?>">
                                        <i class="fas fa-<?php echo intval($admEntry['active']) ? 'check-circle' : 'clock'; ?>"></i>
                                        <?php echo intval($admEntry['active']) ? 'Activo' : 'Sin aprobar'; ?>
                                    </span>
                                    <span class="mgmt-badge <?php echo $isVerified ? 'badge-mgmt-info' : 'badge-mgmt-warn'; ?>">
                                        <i class="fas fa-<?php echo $isVerified ? 'envelope-open-text' : 'envelope'; ?>"></i>
                                        <?php echo $isVerified ? 'Email OK' : 'Email pendiente'; ?>
                                    </span>
                                    <span class="mgmt-badge <?php echo $admEntry['rol'] === 'superadmin' ? 'badge-mgmt-super' : 'badge-mgmt-neutral'; ?>">
                                        <i class="fas fa-<?php echo $admEntry['rol'] === 'superadmin' ? 'crown' : 'user-tie'; ?>"></i>
                                        <?php echo $admEntry['rol'] === 'superadmin' ? 'Super Admin' : 'Admin'; ?>
                                    </span>
                                </div>

                                <?php if (!$isSelf): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($isPending): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="accion" value="aprobar_admin">
                                        <input type="hidden" name="admin_id" value="<?php echo intval($admEntry['id']); ?>">
                                        <button class="btn btn-sm btn-success" type="submit">
                                            <i class="fas fa-check"></i> Aprobar acceso
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar el acceso de este administrador?');">
                                        <input type="hidden" name="accion" value="rechazar_admin">
                                        <input type="hidden" name="admin_id" value="<?php echo intval($admEntry['id']); ?>">
                                        <button class="btn btn-sm btn-outline-warning" type="submit">
                                            <i class="fas fa-ban"></i> Desactivar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Cambiar el rol a <?php echo $admEntry['rol'] === 'superadmin' ? 'Admin regular' : 'SuperAdmin'; ?>?');">
                                        <input type="hidden" name="accion" value="cambiar_rol_admin">
                                        <input type="hidden" name="admin_id" value="<?php echo intval($admEntry['id']); ?>">
                                        <input type="hidden" name="nuevo_rol" value="<?php echo $admEntry['rol'] === 'superadmin' ? 'admin' : 'superadmin'; ?>">
                                        <button class="btn btn-sm btn-outline-primary" type="submit">
                                            <i class="fas fa-<?php echo $admEntry['rol'] === 'superadmin' ? 'user-minus' : 'crown'; ?>"></i>
                                            <?php echo $admEntry['rol'] === 'superadmin' ? 'Quitar Super' : 'Dar Super'; ?>
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-light py-2 mb-0 small">
                                    <i class="fas fa-info-circle text-primary"></i> Tu cuenta — no modificable desde aquí.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($allAdmins)): ?>
                            <div class="col-12">
                                <div class="alert alert-light border text-center py-4">
                                    <i class="fas fa-users-cog fa-2x text-muted mb-2"></i>
                                    <div>No hay administradores registrados.</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
                        <i class="fas fa-lock fa-lg"></i>
                        <span>La gestión de administradores es exclusiva para <strong>superadministradores</strong>. Contacta a un superadmin si necesitas cambios en accesos.</span>
                    </div>
                    <?php endif; ?>

                    <!-- ===== SECCIÓN: GESTIÓN DE USUARIOS ===== -->
                    <div class="section-header" style="border-left-color:#10b981;">
                        <h2 style="color:#10b981;"><i class="fas fa-users-cog"></i> Gestión de Usuarios del Sistema</h2>
                    </div>
                    <p class="small-muted mb-3">
                        <i class="fas fa-info-circle text-success"></i>
                        Aquí puedes <strong>banear / reactivar</strong> usuarios y enviarles <strong>mensajes por app y correo electrónico</strong> directamente.
                    </p>

                    <div class="table-container">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todosUsuarios as $usr): ?>
                                <?php $banned = !intval($usr['activo']); ?>
                                <tr class="<?php echo $banned ? 'table-danger' : ''; ?>">
                                    <td><strong>#<?php echo intval($usr['id']); ?></strong></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="mgmt-avatar-sm" style="background:<?php echo $banned ? 'linear-gradient(135deg,#ef4444,#b91c1c)' : 'linear-gradient(135deg,#667eea,#764ba2)'; ?>;">
                                                <?php echo strtoupper(substr($usr['nombre'] ?? 'U', 0, 1)); ?>
                                            </div>
                                            <span><?php echo htmlspecialchars(trim($usr['nombre'] . ' ' . $usr['apellido'])); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usr['usuario']); ?></td>
                                    <td class="small-muted"><?php echo htmlspecialchars($usr['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usr['telefono'] ?? '—'); ?></td>
                                    <td>
                                        <?php if ($banned): ?>
                                            <span class="badge bg-danger"><i class="fas fa-ban"></i> Baneado</span>
                                        <?php elseif (!intval($usr['verified'])): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Sin verificar</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Activo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small-muted"><?php echo date('d/m/Y', strtotime($usr['creado_en'])); ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button class="btn btn-sm btn-primary"
                                                data-usuario-id="<?php echo intval($usr['id']); ?>"
                                                data-usuario-nombre="<?php echo htmlspecialchars(trim($usr['nombre'] . ' ' . $usr['apellido']), ENT_QUOTES); ?>"
                                                data-usuario-email="<?php echo htmlspecialchars($usr['email'], ENT_QUOTES); ?>"
                                                onclick="abrirModalMensaje(this)"
                                                title="Enviar mensaje por app y correo">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                            <?php if ($banned): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="accion" value="desbanear_usuario">
                                                <input type="hidden" name="usuario_id" value="<?php echo intval($usr['id']); ?>">
                                                <button class="btn btn-sm btn-success" type="submit" title="Desbanear usuario">
                                                    <i class="fas fa-user-check"></i> Desbanear
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Banear a este usuario? No podrá ingresar al sistema.');">
                                                <input type="hidden" name="accion" value="ban_usuario">
                                                <input type="hidden" name="usuario_id" value="<?php echo intval($usr['id']); ?>">
                                                <button class="btn btn-sm btn-danger" type="submit" title="Banear usuario">
                                                    <i class="fas fa-user-slash"></i> Banear
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($todosUsuarios)): ?>
                                    <tr><td colspan="8" class="text-center py-4"><em>No hay usuarios registrados</em></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Captura ESP32-CAM</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img id="modalImage" src="" alt="Captura" style="width: 100%; border-radius: 10px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Enviar Mensaje a Usuario -->
    <div class="modal fade" id="mensajeModal" tabindex="-1" aria-labelledby="mensajeModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;">
                    <h5 class="modal-title" id="mensajeModalLabel"><i class="fas fa-paper-plane"></i> Enviar Mensaje al Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="enviar_mensaje">
                    <input type="hidden" name="usuario_id" id="msg_usuario_id">
                    <div class="modal-body">
                        <div class="alert alert-light border d-flex align-items-center gap-3 py-2 mb-3">
                            <div class="mgmt-avatar-sm" style="background:linear-gradient(135deg,#667eea,#764ba2);">
                                <i class="fas fa-user"></i>
                            </div>
                            <span>Enviando a: <strong id="msg_usuario_nombre">—</strong>
                            &nbsp;<span class="small text-muted">&lt;<span id="msg_usuario_email"></span>&gt;</span></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de mensaje</label>
                            <select class="form-select" name="tipo" id="msg_tipo" onchange="actualizarColorMensaje(this)">
                                <option value="mensaje">💬 Mensaje General</option>
                                <option value="advertencia">⚠️ Advertencia Oficial</option>
                                <option value="ban">🚫 Aviso de Suspensión</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Asunto / Título</label>
                            <input type="text" name="titulo" class="form-control" required
                                placeholder="Ej: Actividad sospechosa en el sistema">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contenido del mensaje</label>
                            <textarea name="contenido" class="form-control" rows="5" required
                                placeholder="Redacte aquí el contenido completo del mensaje para el usuario..."></textarea>
                        </div>
                        <div class="alert alert-info d-flex align-items-start gap-2 py-2 mb-0">
                            <i class="fas fa-mail-bulk mt-1"></i>
                            <small>El mensaje será entregado como <strong>notificación dentro de la app</strong>
                            y también enviado al <strong>correo electrónico</strong> del usuario automáticamente.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar Mensaje</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        const vehiculos = <?php echo json_encode($vehiculos); ?>;
        const depositos = <?php echo json_encode($depositos); ?>;
        const rojoIds = <?php echo json_encode(array_map('intval', array_keys($rojoMap))); ?>;

        const mapCaptures = L.map('mapCaptures').setView([19.451, -70.6894], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapCaptures);

        vehiculos.forEach(function(veh) {
            if (!veh.latitud || !veh.longitud) return;
            const isRed = rojoIds.includes(parseInt(veh.id, 10));
            L.circleMarker([veh.latitud, veh.longitud], {
                radius: 8,
                fillColor: isRed ? '#b83232' : '#2f9e44',
                color: isRed ? '#8f2323' : '#2b8a3e',
                weight: 2,
                opacity: 0.8,
                fillOpacity: 0.75
            }).bindPopup(`<strong>${veh.placa}</strong><br>${veh.ubicacion}<br>${isRed ? 'Semaforo rojo' : 'Detectado por TinyML'}`).addTo(mapCaptures);
        });

        const mapDeposits = L.map('mapDeposits').setView([19.451, -70.6894], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapDeposits);

        // Leaflet necesita recalcular dimensiones cuando el mapa vive dentro de tabs.
        function refreshLeafletMaps() {
            setTimeout(function() {
                mapCaptures.invalidateSize();
                mapDeposits.invalidateSize();
            }, 200);
        }

        window.addEventListener('load', refreshLeafletMaps);
        window.addEventListener('resize', refreshLeafletMaps);

        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tabBtn) {
            tabBtn.addEventListener('shown.bs.tab', function() {
                refreshLeafletMaps();
            });
        });

        const colorPalette = ['#1f77b4', '#ff7f0e', '#2ca02c', '#9467bd', '#8c564b', '#17a2b8', '#e83e8c', '#20c997', '#6f42c1'];
        const userColorMap = {};
        let colorIndex = 0;

        function getColorByUser(userId) {
            const key = String(userId || '0');
            if (!userColorMap[key]) {
                userColorMap[key] = colorPalette[colorIndex % colorPalette.length];
                colorIndex += 1;
            }
            return userColorMap[key];
        }

        depositos.forEach(function(dep) {
            const lat = parseFloat(dep.latitud);
            const lng = parseFloat(dep.longitud);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            const userColor = getColorByUser(dep.id_usuario);
            const usuario = [dep.nombre, dep.apellido].filter(Boolean).join(' ').trim() || dep.usuario || `Usuario #${dep.id_usuario || '-'}`;
            const fecha = dep.creado_en || dep.fecha_hora || 'Sin fecha';
            const peso = dep.peso !== null ? `${Number(dep.peso).toFixed(3)} kg` : '-';

            L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: userColor,
                color: '#1f2937',
                weight: 1.5,
                opacity: 0.9,
                fillOpacity: 0.8
            }).bindPopup(
                `<strong>Deposito #${dep.id}</strong><br>` +
                `Usuario: ${usuario}<br>` +
                `Contenedor: ${dep.codigo_contenedor || dep.id_contenedor || '-'}<br>` +
                `Peso: ${peso}<br>` +
                `Ubicacion: ${dep.ubicacion || 'Sin ubicacion'}<br>` +
                `Fecha: ${fecha}`
            ).addTo(mapDeposits);
        });

        function showImage(imageName) {
            document.getElementById('modalImage').src = '/PRERMI/uploads/vehiculos/' + imageName;
        }

        function abrirModalMensaje(btn) {
            document.getElementById('msg_usuario_id').value    = btn.dataset.usuarioId;
            document.getElementById('msg_usuario_nombre').textContent = btn.dataset.usuarioNombre;
            document.getElementById('msg_usuario_email').textContent  = btn.dataset.usuarioEmail;
            // Reset form fields
            document.getElementById('msg_tipo').value = 'mensaje';
            actualizarColorMensaje(document.getElementById('msg_tipo'));
            const modal = new bootstrap.Modal(document.getElementById('mensajeModal'));
            modal.show();
        }

        function actualizarColorMensaje(sel) {
            const header = document.querySelector('#mensajeModal .modal-header');
            const colors = {
                'mensaje':      'linear-gradient(135deg,#667eea,#764ba2)',
                'advertencia':  'linear-gradient(135deg,#f97316,#ea580c)',
                'ban':          'linear-gradient(135deg,#ef4444,#b91c1c)'
            };
            header.style.background = colors[sel.value] || colors['mensaje'];
        }

        const hasReview = new URLSearchParams(window.location.search).get('review_id');
        if (hasReview) {
            const tabTrigger = document.querySelector('#fines-tab');
            if (tabTrigger) {
                const tab = new bootstrap.Tab(tabTrigger);
                tab.show();
            }
        }
    // ===== ADMIN SAVINGS CHARTS =====
    (function() {
        const monthLabels  = <?php echo json_encode($savingsMonthLabels); ?>;
        const monthRD      = <?php echo json_encode($savingsMonthRD); ?>;
        const monthKwh     = <?php echo json_encode($savingsMonthKwh); ?>;
        const topNames     = <?php echo json_encode(array_map(function($u){ $n = trim(($u['nombre']??'').' '.($u['apellido']??'')); return $n ?: $u['usuario']; }, $topUsers)); ?>;
        const topKwh       = <?php echo json_encode(array_map(function($u){ return round((float)$u['kwh_total'],4); }, $topUsers)); ?>;

        // Bar + Line chart
        const sCtx = document.getElementById('adminSavingsChart');
        if (sCtx) {
            new Chart(sCtx, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: 'Ahorro RD$',
                            data: monthRD,
                            backgroundColor: monthRD.map((v,i) => `hsla(${180+i*25},80%,55%,0.75)`),
                            borderColor:     monthRD.map((v,i) => `hsl(${180+i*25},80%,60%)`),
                            borderWidth: 2, borderRadius: 8, yAxisID: 'yRD'
                        },
                        {
                            label: 'kWh generados', data: monthKwh, type: 'line',
                            borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.12)',
                            pointBackgroundColor: '#10b981', pointRadius: 5,
                            borderWidth: 2.5, fill: true, tension: 0.35, yAxisID: 'yKwh'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { labels: { color: '#cbd5e1', font: { size: 12 } } },
                        tooltip: {
                            callbacks: {
                                label: c => c.dataset.yAxisID === 'yRD'
                                    ? ` Ahorro: RD$ ${c.parsed.y.toFixed(2)}`
                                    : ` kWh: ${c.parsed.y.toFixed(4)}`
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { color:'#94a3b8' }, grid: { color:'rgba(255,255,255,0.05)' } },
                        yRD:  { position:'left',  ticks: { color:'#06b6d4', callback: v=>'RD$'+v.toFixed(0) }, grid: { color:'rgba(6,182,212,0.1)' } },
                        yKwh: { position:'right', ticks: { color:'#10b981', callback: v=>v.toFixed(3)+' kWh' }, grid: { drawOnChartArea: false } }
                    }
                }
            });
        }

        // Horizontal bar for top users
        const tCtx = document.getElementById('adminTopUsersChart');
        if (tCtx && topNames.length) {
            const colors = ['#06b6d4','#10b981','#7c3aed','#f97316','#ec4899','#3b82f6','#a3e635','#facc15'];
            new Chart(tCtx, {
                type: 'bar',
                data: {
                    labels: topNames,
                    datasets: [{
                        label: 'kWh generados',
                        data: topKwh,
                        backgroundColor: topNames.map((_,i) => colors[i % colors.length] + 'bb'),
                        borderColor:     topNames.map((_,i) => colors[i % colors.length]),
                        borderWidth: 1.5, borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color:'#94a3b8', callback: v=>v+' kWh' }, grid: { color:'rgba(255,255,255,0.05)' } },
                        y: { ticks: { color:'#cbd5e1', font: { size: 11 } } }
                    }
                }
            });
        }
    })();

    </script>
</body>
</html>

