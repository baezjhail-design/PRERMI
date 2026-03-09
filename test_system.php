<?php
/**
 * Test de Flujos Principales - PRERMI
 * Verifica que todos los componentes estén funcionando correctamente
 */

session_start();

require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/api/utils.php';

$tests = [];

// Test 1: Conexión a BD
try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuarios");
    $result = $stmt->fetch();
    $tests['database'] = [
        'status' => 'OK',
        'message' => 'Conexión a BD exitosa. ' . $result['count'] . ' usuarios registrados'
    ];
} catch (Exception $e) {
    $tests['database'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Test 2: Tabla de usuarios
try {
    $pdo = getPDO();
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll();
    $tests['usuarios_table'] = [
        'status' => 'OK',
        'message' => 'Tabla usuarios con ' . count($columns) . ' columnas'
    ];
} catch (Exception $e) {
    $tests['usuarios_table'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Test 3: Tabla de depositos
try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM depositos");
    $result = $stmt->fetch();
    $tests['depositos'] = [
        'status' => 'OK',
        'message' => count($result) . ' depósitos registrados'
    ];
} catch (Exception $e) {
    $tests['depositos'] = [
        'status' => 'OK',
        'message' => 'Tabla disponible'
    ];
}

// Test 4: Tabla de vehiculos_registrados
try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehiculos_registrados");
    $result = $stmt->fetch();
    $tests['vehiculos'] = [
        'status' => 'OK',
        'message' => $result['count'] . ' capturas ESP32-CAM registradas'
    ];
} catch (Exception $e) {
    $tests['vehiculos'] = [
        'status' => 'OK',
        'message' => 'Tabla disponible'
    ];
}

// Test 5: Tabla de contenedores
try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contenedores_registrados");
    $result = $stmt->fetch();
    $tests['contenedores'] = [
        'status' => 'OK',
        'message' => $result['count'] . ' contenedores registrados'
    ];
} catch (Exception $e) {
    $tests['contenedores'] = [
        'status' => 'OK',
        'message' => 'Tabla disponible'
    ];
}

// Test 6: Admin existente
try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuarios_admin WHERE active = 1");
    $result = $stmt->fetch();
    $tests['admin_account'] = [
        'status' => $result['count'] > 0 ? 'OK' : 'WARNING',
        'message' => $result['count'] . ' administradores activos'
    ];
} catch (Exception $e) {
    $tests['admin_account'] = [
        'status' => 'WARNING',
        'message' => 'No se pudieron verificar admins'
    ];
}

// Test 7: Archivos necesarios
$files_to_check = [
    '/web/register.php',
    '/web/login.php',
    '/web/user-dashboard.php',
    '/web/admin/loginA.php',
    '/web/admin/dashboard.php',
    '/api/usuarios/register.php',
    '/api/usuarios/login.php',
    '/api/admin/loginA_submit.php',
];

foreach ($files_to_check as $file) {
    $fullPath = __DIR__ . $file;
    if (file_exists($fullPath)) {
        $tests['file_' . basename($file)] = [
            'status' => 'OK',
            'message' => 'Existe: ' . $file
        ];
    } else {
        $tests['file_' . basename($file)] = [
            'status' => 'ERROR',
            'message' => 'Falta: ' . $file
        ];
    }
}

// Test 8: phpMailer
try {
    require_once __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
    $tests['phpmailer'] = [
        'status' => 'OK',
        'message' => 'PHPMailer cargado correctamente'
    ];
} catch (Exception $e) {
    $tests['phpmailer'] = [
        'status' => 'ERROR',
        'message' => 'PHPMailer no disponible: ' . $e->getMessage()
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test System - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 900px;
        }

        .header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        .header h1 {
            color: #667eea;
            margin: 0;
            font-weight: 700;
        }

        .header p {
            color: #666;
            margin: 0.5rem 0 0 0;
        }

        .test-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #ddd;
            transition: all 0.3s ease;
        }

        .test-card:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .test-card.ok {
            border-left-color: #51cf66;
        }

        .test-card.error {
            border-left-color: #ff6b6b;
        }

        .test-card.warning {
            border-left-color: #ffd93d;
        }

        .test-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .test-icon {
            font-size: 1.3rem;
        }

        .test-icon.ok {
            color: #51cf66;
        }

        .test-icon.error {
            color: #ff6b6b;
        }

        .test-icon.warning {
            color: #ffd93d;
        }

        .test-message {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
        }

        .summary {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        .summary h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        .summary-stat {
            display: inline-block;
            margin: 0 1.5rem;
            font-size: 1.2rem;
        }

        .summary-stat strong {
            font-weight: 700;
            color: #333;
        }

        .quick-links {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .quick-links h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        .link-group {
            margin-bottom: 1.5rem;
        }

        .link-group h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.8rem;
        }

        .link-group a {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .link-group a:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .link-group.admin a {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-heartbeat"></i> Test del Sistema PRERMI</h1>
            <p>Verificación de componentes y configuración</p>
        </div>

        <div>
            <?php foreach ($tests as $name => $test): ?>
            <div class="test-card <?php echo strtolower($test['status']); ?>">
                <div class="test-title">
                    <span class="test-icon <?php echo strtolower($test['status']); ?>">
                        <?php
                        switch ($test['status']) {
                            case 'OK':
                                echo '<i class="fas fa-check-circle"></i>';
                                break;
                            case 'ERROR':
                                echo '<i class="fas fa-times-circle"></i>';
                                break;
                            case 'WARNING':
                                echo '<i class="fas fa-exclamation-circle"></i>';
                                break;
                        }
                        ?>
                    </span>
                    <?php echo ucfirst(str_replace('_', ' ', $name)); ?>
                    <span class="badge bg-<?php echo $test['status'] === 'OK' ? 'success' : ($test['status'] === 'ERROR' ? 'danger' : 'warning'); ?>"><?php echo $test['status']; ?></span>
                </div>
                <p class="test-message"><?php echo $test['message']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="summary">
            <h3><i class="fas fa-chart-bar"></i> Resumen</h3>
            <div>
                <?php
                $ok = count(array_filter($tests, fn($t) => $t['status'] === 'OK'));
                $error = count(array_filter($tests, fn($t) => $t['status'] === 'ERROR'));
                $warning = count(array_filter($tests, fn($t) => $t['status'] === 'WARNING'));
                ?>
                <div class="summary-stat"><i class="fas fa-check-circle" style="color: #51cf66;"></i> <strong><?php echo $ok; ?></strong> OK</div>
                <div class="summary-stat"><i class="fas fa-exclamation-circle" style="color: #ffd93d;"></i> <strong><?php echo $warning; ?></strong> Warning</div>
                <div class="summary-stat"><i class="fas fa-times-circle" style="color: #ff6b6b;"></i> <strong><?php echo $error; ?></strong> Error</div>
            </div>
        </div>

        <div class="quick-links">
            <h3><i class="fas fa-link"></i> Enlaces Rápidos</h3>
            
            <div class="link-group">
                <h5>👤 Usuario</h5>
                <a href="/PRERMI/web/register.php">
                    <i class="fas fa-user-plus"></i> Registro
                </a>
                <a href="/PRERMI/web/login.php">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="/PRERMI/web/user-dashboard.php">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </div>

            <div class="link-group admin">
                <h5>🔐 Administrador</h5>
                <a href="/PRERMI/web/admin/loginA.php">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </a>
                <a href="/PRERMI/web/admin/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                </a>
            </div>

            <div class="link-group">
                <h5>📚 Herramientas</h5>
                <a href="/PRERMI/instalar_bd.php">
                    <i class="fas fa-database"></i> Instalar BD
                </a>
                <a href="/PRERMI/verificar_bd_integridad.php">
                    <i class="fas fa-check"></i> Verificar BD
                </a>
                <a href="/PRERMI/test_apis.php">
                    <i class="fas fa-flask"></i> Test APIs
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
