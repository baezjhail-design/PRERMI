# Código SQL Actualizado - Migración contenedor_id → id_contenedor

## 1. API/contenedores/registrar_basura.php

```php
<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$id_contenedor = isset($input['id_contenedor']) ? intval($input['id_contenedor']) : 0;
$peso = isset($input['peso']) ? floatval($input['peso']) : 0.0;
$metal = isset($input['metal']) ? intval($input['metal']) : 0;
$token_usado = isset($input['token_usado']) ? trim($input['token_usado']) : null;
$tipo_residuo = isset($input['tipo_residuo']) ? trim($input['tipo_residuo']) : 'general';
$credito_kwh = isset($input['credito_kwh']) ? floatval($input['credito_kwh']) : 0.0;

if (!$user_id || !$id_contenedor) jsonErr('missing');

$pdo = getPDO();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO depositos (user_id, id_contenedor, peso, tipo_residuo, metal_detectado, credito_kwh, token_usado, fecha_hora) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $id_contenedor, $peso, $tipo_residuo, $metal, $credito_kwh, $token_usado]);

    if ($metal) {
        $desc = 'Metal detectado en depósito';
        $stmt2 = $pdo->prepare("INSERT INTO multas (user_id, id_contenedor, descripcion, peso) VALUES (?, ?, ?, ?)");
        $stmt2->execute([$user_id, $id_contenedor, $desc, $peso]);
        $pdo->prepare("INSERT INTO logs_sistema (descripcion, tipo) VALUES (?, 'alert')")->execute(["Multa: usuario_id=$user_id, id_contenedor=$id_contenedor, peso=$peso"]);
    }

    $stmtU = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id = ? LIMIT 1");
    $stmtU->execute([$user_id]);
    $u = $stmtU->fetch();

    $pdo->commit();

    if ($metal) {
        $stmtA = $pdo->prepare("SELECT usuario AS name, email FROM usuarios_admin WHERE active = 1");
        $stmtA->execute();
        $admins = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        if (empty($admins)) $admins = [['name'=>'Admin','email'=>'admin@example.com']];
        sendAdminFineEmail($admins, $u['email'], $u['nombre'], $user_id, $id_contenedor, $peso);
    } else {
        $deposit_id = $pdo->lastInsertId();
        $deposit_date = date('Y-m-d H:i:s');
        sendDepositNotificationEmail($u['email'], $u['nombre'], $peso, $credito_kwh, $deposit_date, $deposit_id);
    }

    jsonOk(['metal'=>$metal]);
} catch (PDOException $e) {
    $pdo->rollBack();
    jsonErr('db error', 500);
}
```

## 2. API/contenedores/registrar_peso.php

```php
<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';

$appConfig = require __DIR__ . '/../../config/app_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['user_id'] ?? 0);
$id_contenedor = intval($input['id_contenedor'] ?? 0);
$peso = floatval($input['peso'] ?? 0.0);
$token_usado = isset($input['token_usado']) ? trim($input['token_usado']) : null;
$tipo_residuo = isset($input['tipo_residuo']) ? trim($input['tipo_residuo']) : 'general';

if (!$user_id || !$id_contenedor) jsonErr('missing');

$credito_por_kg = isset($appConfig['credito_kwh_por_kg']) ? floatval($appConfig['credito_kwh_por_kg']) : 0.5;
$credito_kwh = round($peso * $credito_por_kg, 5);

$pdo = getPDO();
try {
    $stmt = $pdo->prepare("INSERT INTO depositos (user_id, id_contenedor, peso, tipo_residuo, credito_kwh, token_usado, fecha_hora) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $id_contenedor, $peso, $tipo_residuo, $credito_kwh, $token_usado]);
    $deposit_id = $pdo->lastInsertId();

    $stmtU = $pdo->prepare("SELECT email, nombre, apellido FROM usuarios WHERE id = ? LIMIT 1");
    $stmtU->execute([$user_id]);
    $u = $stmtU->fetch(PDO::FETCH_ASSOC);

    $deposit_date = date('Y-m-d H:i:s');

    if (!empty($u['email'])) {
        $fullName = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')) ?: $u['email'];
        sendDepositNotificationEmail($u['email'], $fullName, $peso, $credito_kwh, $deposit_date, $deposit_id);
    }

    jsonOk(['deposit_id' => $deposit_id, 'credito_kwh' => $credito_kwh]);
} catch (PDOException $e) {
    jsonErr('db error', 500);
}
```

## 3. API/contenedores/registrar_multa.php

```php
<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['user_id'] ?? 0);
$id_contenedor = intval($input['id_contenedor'] ?? 0);
$peso = floatval($input['peso'] ?? 0.0);
$desc = isset($input['descripcion']) ? trim($input['descripcion']) : 'Multa generada';

if (!$user_id || !$id_contenedor) jsonErr('missing');

$pdo = getPDO();
try {
    $stmt = $pdo->prepare("INSERT INTO multas (user_id, id_contenedor, descripcion, peso) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $id_contenedor, $desc, $peso]);

    $stmtU = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id = ? LIMIT 1");
    $stmtU->execute([$user_id]);
    $u = $stmtU->fetch();
    
    $stmtA = $pdo->prepare("SELECT usuario AS name, email FROM usuarios_admin WHERE active = 1");
    $stmtA->execute();
    $admins = $stmtA->fetchAll(PDO::FETCH_ASSOC);
    if (empty($admins)) $admins = [['name'=>'Admin','email'=>'admin@example.com']];

    sendAdminFineEmail($admins, $u['email'], $u['nombre'], $user_id, $id_contenedor, $peso);

    jsonOk();
} catch (PDOException $e) {
    jsonErr('db error', 500);
}
```

## 4. API/contenedores/validar_token_rfid.php

```php
<?php
require_once __DIR__ . '/../utils.php';

header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonErr('Datos inválidos', 400);
}

$token = isset($input['token']) ? trim($input['token']) : null;
$id_contenedor = isset($input['id_contenedor']) ? intval($input['id_contenedor']) : 0;

if (!$token || !$id_contenedor) {
    jsonErr('Token y id_contenedor son obligatorios', 400);
}

try {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare("SELECT id, usuario, nombre, apellido, email FROM usuarios WHERE token = ? AND token_activo = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        registrarLog("Intento fallido de validación RFID con token inválido", "warning");
        jsonErr('Token no válido', 401);
    }
    
    $stmt = $pdo->prepare("SELECT id, id_contenedor, ubicacion, ultimo_token, token_expira_en FROM contenedores_registrados WHERE id = ?");
    $stmt->execute([$id_contenedor]);
    $contenedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contenedor) {
        jsonErr('Contenedor no encontrado', 404);
    }
    
    // Validar si el token ha expirado
    if ($contenedor['token_expira_en'] && strtotime($contenedor['token_expira_en']) < time()) {
        jsonErr('Token expirado para este contenedor', 403);
    }
    
    // Verificar que el token sea el mismo
    if ($contenedor['ultimo_token'] !== $token) {
        jsonErr('Token no autorizado para este contenedor', 403);
    }
    
    registrarLog("Usuario {$user['usuario']} accedió al contenedor {$contenedor['id_contenedor']}", "info");
    
    jsonOk([
        'message' => 'Acceso válido',
        'user_id' => $user['id'],
        'user_name' => "{$user['nombre']} {$user['apellido']}",
        'contenedor_id' => $contenedor['id'],
        'contenedor_nombre' => $contenedor['id_contenedor'],
        'ubicacion' => $contenedor['ubicacion']
    ]);
    
} catch (PDOException $e) {
    error_log('Token validation error: ' . $e->getMessage());
    jsonErr('Error validando token', 500);
}
```

## 5. API/contenedores/validar_acceso.php

```php
<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../utils.php';

$headers = getallheaders();
$provided_key = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : null;

$input = json_decode(file_get_contents('php://input'), true);
$token = isset($input['token']) ? trim($input['token']) : '';
if (!$token) jsonErr('token missing');

$pdo = getPDO();
$stmt = $pdo->prepare("SELECT id, nombre, apellido, token_activo FROM usuarios WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) jsonErr('no_registered', 404);
if (!$user['token_activo']) jsonErr('token_disabled', 403);

jsonOk(['user_id'=>$user['id'],'nombre'=>$user['nombre'].' '.$user['apellido']]);
```

## 6. web/user-dashboard.php

```php
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../api/utils.php';

try {
    $pdo = getPDO();
    
    $stmtUser = $pdo->prepare("SELECT nombre, apellido, usuario, email, token FROM usuarios WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    // Actualizado: usar id_contenedor y campos nuevos de depositos
    $stmtDepositos = $pdo->prepare("
        SELECT d.id, d.id_contenedor, d.peso, d.tipo_residuo, d.metal_detectado, d.credito_kwh, d.fecha_hora, d.token_usado, 
               c.id_contenedor as contenedor_nombre, c.ubicacion
        FROM depositos d
        LEFT JOIN contenedores_registrados c ON d.id_contenedor = c.id
        WHERE d.user_id = ?
        ORDER BY d.fecha_hora DESC
        LIMIT 50
    ");
    $stmtDepositos->execute([$_SESSION['user_id']]);
    $depositos = $stmtDepositos->fetchAll(PDO::FETCH_ASSOC);
    
    $totalDepositos = count($depositos);
    $totalPeso = 0;
    $totalCredito = 0;
    $metalDetectados = 0;
    
    foreach ($depositos as $dep) {
        $totalPeso += $dep['peso'];
        $totalCredito += $dep['credito_kwh'];
        if ($dep['metal_detectado']) {
            $metalDetectados++;
        }
    }
    
    $stmtMultas = $pdo->prepare("SELECT COUNT(*) as total FROM multas WHERE user_id = ?");
    $stmtMultas->execute([$_SESSION['user_id']]);
    $multasResult = $stmtMultas->fetch();
    $totalMultas = $multasResult['total'];
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<!-- ... resto de HTML igual ... -->
                        <tbody>
                            <?php foreach ($depositos as $dep): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dep['contenedor_nombre'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($dep['ubicacion'] ?? 'No especificada'); ?></td>
                                <td><?php echo number_format($dep['peso'], 2); ?> kg</td>
                                <td><?php echo htmlspecialchars($dep['tipo_residuo']); ?></td>
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
                                <td><?php echo date('d/m/Y H:i', strtotime($dep['fecha_hora'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($depositos)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
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

    <script>
        const depositos = <?php echo json_encode($depositos); ?>;
        
        const monthCounts = {};
        depositos.forEach(dep => {
            const date = new Date(dep.fecha_hora);
            const month = date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
            monthCounts[month] = (monthCounts[month] || 0) + 1;
        });

        const containerWeights = {};
        depositos.forEach(dep => {
            const name = dep.contenedor_nombre || 'Desconocido';
            containerWeights[name] = (containerWeights[name] || 0) + parseFloat(dep.peso);
        });
    </script>
</html>
```

## 7. web/admin/dashboard.php

```php
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginA.php");
    exit;
}

require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../api/utils.php';

try {
    $pdo = getPDO();
    
    $stmtAdmin = $pdo->prepare("SELECT usuario, email, rol FROM usuarios_admin WHERE id = ?");
    $stmtAdmin->execute([$_SESSION['admin_id']]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
    
    $stmtVehiculos = $pdo->query("SELECT id, placa, tipo_vehiculo, imagen, ubicacion, fecha, hora, probabilidad, latitud, longitud, creado_en FROM vehiculos_registrados ORDER BY creado_en DESC");
    $vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizado: usar id_contenedor
    $stmtContenedores = $pdo->query("SELECT id, id_contenedor, nivel_basura, ubicacion, latitud, longitud, ultimo_token, token_generado_en, token_expira_en FROM contenedores_registrados ORDER BY actualizado_en DESC");
    $contenedores = $stmtContenedores->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizado: usar id_contenedor en JOIN
    $stmtMultas = $pdo->query("SELECT m.id, m.user_id, m.id_contenedor, m.descripcion, m.peso, m.creado_en, u.usuario FROM multas m JOIN usuarios u ON m.user_id = u.id ORDER BY m.creado_en DESC LIMIT 20");
    $multas = $stmtMultas->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtLogs = $pdo->query("SELECT id, descripcion, tipo, creado_en FROM logs_sistema ORDER BY creado_en DESC LIMIT 20");
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
```

## 8. web/usuarios/depositos_usuario.php

```php
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

require_once "../../config/db_config.php";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$id = $_SESSION['usuario_id'];

// Actualizado: usar id_contenedor, tipo_residuo, fecha_hora, token_usado
$query = $conn->query("SELECT d.id, d.id_contenedor, d.peso, d.tipo_residuo, d.credito_kwh, d.fecha_hora, d.token_usado, c.ubicacion 
                       FROM depositos d 
                       LEFT JOIN contenedores_registrados c ON d.id_contenedor = c.id 
                       WHERE d.user_id='$id' 
                       ORDER BY d.fecha_hora DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Depósitos - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">
    <style>
        table {
            width: 90%;
            margin: 25px auto;
            background: white;
            border-radius: 10px;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background: #00aa99;
            color: white;
        }
    </style>
</head>

<body>
<header>
    <h1>PRERMI</h1>
</header>

<h2 style="text-align:center; color:white;">Historial de Depósitos</h2>

<table>
    <tr>
        <th>Contenedor</th>
        <th>Peso Registrado (kg)</th>
        <th>Tipo Residuo</th>
        <th>Crédito (kWh)</th>
        <th>Fecha/Hora</th>
        <th>Token Usado</th>
    </tr>

    <?php while($row = $query->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['id_contenedor'] ?? 'Desconocido'); ?></td>
            <td><?php echo number_format($row['peso'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['tipo_residuo']); ?></td>
            <td><?php echo number_format($row['credito_kwh'], 4); ?></td>
            <td><?php echo date('d/m/Y H:i:s', strtotime($row['fecha_hora'])); ?></td>
            <td><?php echo htmlspecialchars($row['token_usado'] ?? 'N/A'); ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<p style="text-align:center;">
    <a href="dashboard_usuario.php">← Volver al Dashboard</a>
</p>

</body>
</html>
```

## 9. web/usuarios/multas_usuario.php

```php
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

require_once "../../config/db_config.php";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$id = $_SESSION['usuario_id'];

// Actualizado: usar id_contenedor
$query = $conn->query("SELECT m.id, m.id_contenedor, m.descripcion, m.peso, m.creado_en, c.ubicacion 
                       FROM multas m 
                       LEFT JOIN contenedores_registrados c ON m.id_contenedor = c.id 
                       WHERE m.user_id='$id'
                       ORDER BY m.creado_en DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Multas - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">
    <style>
        table {
            width: 90%;
            margin: 25px auto;
            background: white;
            border-radius: 10px;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background: #cc0000;
            color: white;
        }
    </style>
</head>

<body>
<header>
    <h1>PRERMI</h1>
</header>

<h2 style="color:white; text-align:center;">Multas Registradas</h2>

<table>
    <tr>
        <th>ID Contenedor</th>
        <th>Ubicación</th>
        <th>Motivo</th>
        <th>Peso (kg)</th>
        <th>Fecha</th>
    </tr>

    <?php while($row = $query->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['id_contenedor'] ?? 'Desconocido'); ?></td>
        <td><?php echo htmlspecialchars($row['ubicacion'] ?? 'No especificada'); ?></td>
        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
        <td><?php echo number_format($row['peso'], 2); ?></td>
        <td><?php echo date('d/m/Y H:i', strtotime($row['creado_en'])); ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<p style="text-align:center;">
    <a href="dashboard_usuario.php">← Volver al Dashboard</a>
</p>

</body>
</html>
```

## 10. verificar_bd_integridad.php (Consultas de verificación)

```php
// Verificar integridad de Foreign Keys
$resultado['verificaciones']['foreign_keys'] = [];

// Verificar depositos.user_id
$stmt = $pdo->prepare("
    SELECT COUNT(*) as invalidos FROM depositos d
    WHERE d.user_id NOT IN (SELECT id FROM usuarios)
");
$stmt->execute();
$invalidos = $stmt->fetch()['invalidos'];
$resultado['verificaciones']['foreign_keys']['depositos.user_id'] = $invalidos == 0 ? '✓' : "✗ ($invalidos inválidos)";

// Verificar depositos.id_contenedor (ACTUALIZADO)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as invalidos FROM depositos d
    WHERE d.id_contenedor NOT IN (SELECT id FROM contenedores_registrados)
");
$stmt->execute();
$invalidos = $stmt->fetch()['invalidos'];
$resultado['verificaciones']['foreign_keys']['depositos.id_contenedor'] = $invalidos == 0 ? '✓' : "✗ ($invalidos inválidos)";

// Verificar multas.user_id
$stmt = $pdo->prepare("
    SELECT COUNT(*) as invalidos FROM multas m
    WHERE m.user_id NOT IN (SELECT id FROM usuarios)
");
$stmt->execute();
$invalidos = $stmt->fetch()['invalidos'];
$resultado['verificaciones']['foreign_keys']['multas.user_id'] = $invalidos == 0 ? '✓' : "✗ ($invalidos inválidos)";

// Verificar multas.id_contenedor (ACTUALIZADO)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as invalidos FROM multas m
    WHERE m.id_contenedor NOT IN (SELECT id FROM contenedores_registrados)
");
$stmt->execute();
$invalidos = $stmt->fetch()['invalidos'];
$resultado['verificaciones']['foreign_keys']['multas.id_contenedor'] = $invalidos == 0 ? '✓' : "✗ ($invalidos inválidos)";
```

---

## RESUMEN DE CAMBIOS

### Tablas Afectadas:
- **depositos**: `contenedor_id` → `id_contenedor`, agregados `tipo_residuo`, `token_usado`, `fecha_hora`
- **multas**: `contenedor_id` → `id_contenedor`
- **contenedores_registrados**: agregados `ultimo_token`, `token_generado_en`, `token_expira_en`

### Archivos Actualizados:
1. ✓ api/contenedores/registrar_basura.php
2. ✓ api/contenedores/registrar_peso.php
3. ✓ api/contenedores/registrar_multa.php
4. ✓ api/contenedores/validar_token_rfid.php
5. ✓ api/contenedores/validar_acceso.php
6. ✓ web/user-dashboard.php
7. ✓ web/admin/dashboard.php
8. ✓ web/usuarios/depositos_usuario.php
9. ✓ web/usuarios/multas_usuario.php
10. ✓ verificar_bd_integridad.php
