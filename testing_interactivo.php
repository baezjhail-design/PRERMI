<?php
/**
 * testing_interactivo.php
 * Guía de testing interactiva con todo integrado
 * 
 * Acceso: http://localhost/PRERMI/testing_interactivo.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/api/utils.php';

requireLocalAccess(false);

// Recolectar información del sistema
$tests = [];

// TEST 1: Base de datos
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?");
    $stmt->execute([$DB_NAME]);
    $tables_count = $stmt->fetch()['count'];
    $tests['BD Conectada'] = ['status' => 'ok', 'mensaje' => "Conexión establecida ($tables_count tablas)"];
} catch (Exception $e) {
    $tests['BD Conectada'] = ['status' => 'error', 'mensaje' => $e->getMessage()];
}

// TEST 2: Admins
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios_admin WHERE active = 1 AND verified = 1");
    $stmt->execute();
    $admins = $stmt->fetch()['count'];
    $tests['Admins Activos'] = ['status' => 'ok', 'mensaje' => "$admins admin(s) activo(s)"];
} catch (Exception $e) {
    $tests['Admins Activos'] = ['status' => 'error', 'mensaje' => $e->getMessage()];
}

// TEST 3: Usuarios
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios");
    $stmt->execute();
    $users = $stmt->fetch()['count'];
    $tests['Tabla Usuarios'] = ['status' => 'ok', 'mensaje' => "$users usuario(s) registrado(s)"];
} catch (Exception $e) {
    $tests['Tabla Usuarios'] = ['status' => 'error', 'mensaje' => $e->getMessage()];
}

// TEST 4: Vehículos
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vehiculos_registrados");
    $stmt->execute();
    $vehicles = $stmt->fetch()['count'];
    $tests['Vehículos'] = ['status' => 'ok', 'mensaje' => "$vehicles vehículo(s) registrado(s)"];
} catch (Exception $e) {
    $tests['Vehículos'] = ['status' => 'error', 'mensaje' => $e->getMessage()];
}

// TEST 5: PDO
try {
    $stmt = $pdo->prepare("SELECT DATABASE() as db");
    $stmt->execute();
    $db_name = $stmt->fetch()['db'];
    $tests['PDO Funcionando'] = ['status' => 'ok', 'mensaje' => "BD actual: $db_name"];
} catch (Exception $e) {
    $tests['PDO Funcionando'] = ['status' => 'error', 'mensaje' => $e->getMessage()];
}

// TEST 6: Prepared Statements
try {
    $test_val = "test";
    $stmt = $pdo->prepare("SELECT ? as test");
    $stmt->execute([$test_val]);
    $result = $stmt->fetch()['test'];
    if ($result === $test_val) {
        $tests['Prepared Statements'] = ['status' => 'ok', 'mensaje' => 'Funcionando correctamente'];
    } else {
        $tests['Prepared Statements'] = ['status' => 'error', 'mensaje' => 'Resultado inesperado'];
    }
} catch (Exception $e) {
    $tests['Prepared Statements'] = ['status' => 'error', 'mensaje' => $e->getMessage()];
}

// TEST 7: Foreign Keys
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL");
    $stmt->execute([$DB_NAME]);
    $fk_count = $stmt->fetch()['count'];
    $tests['Foreign Keys'] = ['status' => 'ok', 'mensaje' => "$fk_count relaciones encontradas"];
} catch (Exception $e) {
    $tests['Foreign Keys'] = ['status' => 'error', 'mensaje' => $e->getMessage()];
}

// Contar pruebas exitosas
$ok_count = count(array_filter($tests, function($t) { return $t['status'] === 'ok'; }));
$total_count = count($tests);
$percentage = round(($ok_count / $total_count) * 100);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testing Interactivo - PRER_MI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .progress-bar {
            background: rgba(255, 255, 255, 0.2);
            height: 40px;
            border-radius: 20px;
            overflow: hidden;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            width: <?php echo $percentage; ?>%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            color: white;
            text-align: center;
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .test-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .test-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .test-item.ok {
            border-left-color: #27ae60;
        }
        
        .test-item.error {
            border-left-color: #e74c3c;
        }
        
        .test-icon {
            font-size: 24px;
            margin-right: 15px;
            min-width: 30px;
        }
        
        .test-icon.ok::before {
            content: "✓";
            color: #27ae60;
        }
        
        .test-icon.error::before {
            content: "✗";
            color: #e74c3c;
        }
        
        .test-content {
            flex: 1;
        }
        
        .test-name {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }
        
        .test-message {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .test-item.error .test-message {
            color: #e74c3c;
        }
        
        .test-item.ok .test-message {
            color: #27ae60;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .summary {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid #667eea;
        }
        
        .summary h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .summary-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .summary-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .summary-label {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .status-ok {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-error {
            color: #e74c3c;
            font-weight: bold;
        }
        
        footer {
            text-align: center;
            color: white;
            margin-top: 30px;
            opacity: 0.8;
        }
        
        .guides {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .guide-card {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .guide-card:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.6);
            transform: translateY(-5px);
        }
        
        .guide-card h4 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .guide-card p {
            font-size: 13px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🧪 Testing Interactivo</h1>
            <p>PRER_MI - Verificación de Funcionalidad</p>
        </header>
        
        <div class="summary">
            <h3>📊 Resumen General</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-number"><?php echo $ok_count; ?>/<?php echo $total_count; ?></div>
                    <div class="summary-label">Pruebas Pasadas</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number"><?php echo $percentage; ?>%</div>
                    <div class="summary-label">Completado</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number"><?php echo $DB_NAME; ?></div>
                    <div class="summary-label">Base de Datos</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number"><?php echo PHP_VERSION; ?></div>
                    <div class="summary-label">Versión PHP</div>
                </div>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        <div class="progress-text">
            <?php 
            if ($percentage == 100) {
                echo "✓ Sistema 100% Funcional";
            } elseif ($percentage >= 80) {
                echo "✓ Sistema Funcionando (Revisión Necesaria)";
            } else {
                echo "⚠ Problemas Detectados";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>🔍 Pruebas Automáticas</h2>
            
            <?php foreach ($tests as $name => $test): ?>
                <div class="test-item <?php echo $test['status']; ?>">
                    <div class="test-icon <?php echo $test['status']; ?>"></div>
                    <div class="test-content">
                        <div class="test-name"><?php echo htmlspecialchars($name); ?></div>
                        <div class="test-message"><?php echo htmlspecialchars($test['mensaje']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="location.reload()">🔄 Actualizar Pruebas</button>
                <a href="/PRERMI/verificar_bd_integridad.php" class="btn btn-secondary">📋 Informe Detallado</a>
                <a href="/PRERMI/test_apis.php" class="btn btn-secondary">🧪 Probar APIs</a>
            </div>
        </div>
        
        <div class="test-section">
            <h2>📚 Guías de Testing</h2>
            <div class="guides">
                <a href="/PRERMI/GUIA_TESTING_COMPLETA.txt" class="guide-card">
                    <h4>📖 Guía Completa</h4>
                    <p>Testing detallado en 6 niveles (60 min)</p>
                </a>
                <a href="/PRERMI/START_HERE.txt" class="guide-card">
                    <h4>⚡ Inicio Rápido</h4>
                    <p>Testing básico en 30 segundos</p>
                </a>
                <a href="/PRERMI/test_apis.php" class="guide-card">
                    <h4>🔌 Test de APIs</h4>
                    <p>Interfaz gráfica para probar endpoints</p>
                </a>
                <a href="/phpmyadmin" class="guide-card">
                    <h4>💾 phpMyAdmin</h4>
                    <p>Administración visual de la BD</p>
                </a>
            </div>
        </div>
        
        <?php if ($percentage == 100): ?>
            <div class="test-section" style="background: #d4edda; border-left: 4px solid #27ae60;">
                <h2 style="color: #155724;">✅ ¡Sistema Completamente Funcional!</h2>
                <p style="color: #155724; margin-top: 10px;">Todas las pruebas pasaron correctamente. Puedes comenzar a usar el sistema.</p>
                <div class="action-buttons">
                    <a href="/PRERMI/web/" class="btn btn-success">Ir a Página Principal</a>
                    <a href="/PRERMI/index_herramientas.php" class="btn btn-success">Ir a Panel de Control</a>
                </div>
            </div>
        <?php elseif ($percentage >= 80): ?>
            <div class="test-section" style="background: #fff3cd; border-left: 4px solid #ffc107;">
                <h2 style="color: #856404;">⚠ Revisar Problemas</h2>
                <p style="color: #856404; margin-top: 10px;">Algunas pruebas tuvieron problemas. Por favor revísalas en la sección anterior.</p>
            </div>
        <?php else: ?>
            <div class="test-section" style="background: #f8d7da; border-left: 4px solid #dc3545;">
                <h2 style="color: #721c24;">❌ Problemas Encontrados</h2>
                <p style="color: #721c24; margin-top: 10px;">Hay problemas significativos que necesitan ser resueltos.</p>
                <div class="action-buttons">
                    <a href="/PRERMI/instalar_bd.php" class="btn btn-secondary">Importar BD</a>
                    <a href="/PRERMI/GUIA_TESTING_COMPLETA.txt" class="btn btn-secondary">Ver Soluciones</a>
                </div>
            </div>
        <?php endif; ?>
        
        <footer>
            <p>PRER_MI v1.0.0 | Testing Interactivo | <?php echo date('d/m/Y H:i'); ?></p>
        </footer>
    </div>
</body>
</html>
