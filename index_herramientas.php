<?php
/**
 * index_herramientas.php
 * Panel de control central con todos los accesos a herramientas
 * 
 * Acceso: http://localhost/PRERMI/index_herramientas.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/api/utils.php';
requireLocalAccess(false);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - PRER_MI</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        
        header h1 {
            font-size: 48px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .card-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #764ba2;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .status {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .status h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            color: #666;
            font-weight: 500;
        }
        
        .status-value {
            color: #667eea;
            font-weight: bold;
        }
        
        .status-ok {
            color: #27ae60;
        }
        
        .status-error {
            color: #e74c3c;
        }
        
        .category {
            margin-bottom: 50px;
        }
        
        .category h2 {
            color: white;
            font-size: 28px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid rgba(255,255,255,0.3);
        }
        
        footer {
            text-align: center;
            color: white;
            opacity: 0.8;
            margin-top: 40px;
        }
        
        .quick-links {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .quick-links h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .quick-links a {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>ðŸš€ PRER_MI - Panel de Control</h1>
            <p>Sistema de gestiÃ³n de vehÃ­culos y contenedores inteligentes</p>
        </header>
        
        <!-- Estado RÃ¡pido -->
        <div class="status">
            <h3>âœ“ Estado del Sistema</h3>
            <?php
                require_once __DIR__ . '/config/db_config.php';
                require_once __DIR__ . '/api/utils.php';
                
                try {
                    $pdo = getPDO();
                    echo '<div class="status-item">';
                    echo '<span class="status-label">ConexiÃ³n a BD</span>';
                    echo '<span class="status-value status-ok">âœ“ Conectada</span>';
                    echo '</div>';
                    
                    $stmt = $pdo->prepare("SELECT DATABASE()");
                    $stmt->execute();
                    $db = $stmt->fetch()[0];
                    echo '<div class="status-item">';
                    echo '<span class="status-label">Base de Datos</span>';
                    echo '<span class="status-value">' . htmlspecialchars($db) . '</span>';
                    echo '</div>';
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios_admin WHERE active = 1 AND verified = 1");
                    $stmt->execute();
                    $admins = $stmt->fetch()['total'];
                    echo '<div class="status-item">';
                    echo '<span class="status-label">Admins Activos</span>';
                    echo '<span class="status-value">' . $admins . '</span>';
                    echo '</div>';
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios");
                    $stmt->execute();
                    $users = $stmt->fetch()['total'];
                    echo '<div class="status-item">';
                    echo '<span class="status-label">Usuarios Registrados</span>';
                    echo '<span class="status-value">' . $users . '</span>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="status-item">';
                    echo '<span class="status-label">Error</span>';
                    echo '<span class="status-value status-error">âœ— ' . $e->getMessage() . '</span>';
                    echo '</div>';
                }
            ?>
        </div>
        
        <!-- Enlaces RÃ¡pidos -->
        <div class="quick-links">
            <h3>ðŸ”— Enlaces RÃ¡pidos</h3>
            <a href="/PRERMI/index.php" class="btn btn-sm">PÃ¡gina Principal</a>
            <a href="/PRERMI/web/login.html" class="btn btn-sm">Login Usuario</a>
            <a href="/PRERMI/web/register.html" class="btn btn-sm">Registro Usuario</a>
            <a href="/phpmyadmin" class="btn btn-sm">phpMyAdmin</a>
        </div>
        
        <!-- InstalaciÃ³n y ConfiguraciÃ³n -->
        <div class="category">
            <h2>ðŸ“¦ InstalaciÃ³n y ConfiguraciÃ³n</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-icon">âš™ï¸</div>
                    <h3>Instalador de BD</h3>
                    <p>Importa automÃ¡ticamente la base de datos prer_mi.sql desde el navegador.</p>
                    <a href="./instalar_bd.php" class="btn">Ir al Instalador</a>
                </div>
                
                <div class="card">
                    <div class="card-icon">âœ…</div>
                    <h3>Verificador de Integridad</h3>
                    <p>Verifica que toda la estructura de la BD sea correcta y consistente.</p>
                    <a href="./verificar_bd_integridad.php" class="btn">Verificar Ahora</a>
                </div>
                
                <div class="card">
                    <div class="card-icon">ðŸ“„</div>
                    <h3>Schema de BD</h3>
                    <p>DocumentaciÃ³n tÃ©cnica completa de todas las tablas y campos.</p>
                    <a href="./DB_PRER_MI_SCHEMA.md" class="btn">Ver Schema</a>
                </div>
            </div>
        </div>
        
        <!-- Testing -->
        <div class="category">
            <h2>ðŸ§ª Testing y Debugging</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-icon">ðŸ”¬</div>
                    <h3>Test de APIs</h3>
                    <p>Interfaz grÃ¡fica para probar todos los endpoints y verificar respuestas.</p>
                    <a href="./test_apis.php" class="btn">Ejecutar Tests</a>
                </div>
                
                <div class="card">
                    <div class="card-icon">ðŸ“Š</div>
                    <h3>EstadÃ­sticas</h3>
                    <p>Ver estadÃ­sticas en tiempo real de tablas y datos en la BD.</p>
                    <a href="./verificar_bd_integridad.php" class="btn">Ver EstadÃ­sticas</a>
                </div>
                
                <div class="card">
                    <div class="card-icon">ðŸ›</div>
                    <h3>DiagnÃ³stico Completo</h3>
                    <p>Reporte detallado de estructura, integridad y posibles problemas.</p>
                    <a href="./verificar_bd_integridad.php" class="btn">Ver DiagnÃ³stico</a>
                </div>
            </div>
        </div>
        
        <!-- DocumentaciÃ³n -->
        <div class="category">
            <h2>ðŸ“š DocumentaciÃ³n</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-icon">ðŸ“–</div>
                    <h3>GuÃ­a de InstalaciÃ³n</h3>
                    <p>GuÃ­a completa paso a paso para instalar y configurar la BD.</p>
                    <a href="./GUIA_INSTALACION_BD.txt" class="btn">Leer GuÃ­a</a>
                </div>
                
                <div class="card">
                    <div class="card-icon">ðŸ—ï¸</div>
                    <h3>Infraestructura</h3>
                    <p>DescripciÃ³n de toda la arquitectura de base de datos.</p>
                    <a href="./INFRAESTRUCTURA_BD.txt" class="btn">Leer Info</a>
                </div>
                
                <div class="card">
                    <div class="card-icon">ðŸ’¾</div>
                    <h3>Script SQL Original</h3>
                    <p>Descarga el script SQL completo de la base de datos.</p>
                    <a href="./prer_mi.sql" class="btn">Descargar SQL</a>
                </div>
            </div>
        </div>
        
        <!-- Sistema -->
        <div class="category">
            <h2>ðŸŒ Sistema PRER_MI</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-icon">ðŸ‘¥</div>
                    <h3>Panel de Usuarios</h3>
                    <p>Accede al panel de usuarios del sistema.</p>
                    <a href="./web/index.php" class="btn">Ir a Panel</a>
                </div>
                
                <div class="card">
                    <div class="card-icon">ðŸ‘¨â€ðŸ’¼</div>
                    <h3>Panel de AdministraciÃ³n</h3>
                    <p>Panel de administraciÃ³n del sistema.</p>
                    <a href="./web/admin/" class="btn">Ir a Admin</a>
                </div>
                
                <div class="card">
                    <div class="card-icon">ðŸ”Œ</div>
                    <h3>APIs REST</h3>
                    <p>Accede a todos los endpoints REST de la aplicaciÃ³n.</p>
                    <a href="./api/" class="btn">Ver APIs</a>
                </div>
            </div>
        </div>
        
        <footer>
            <p>PRER_MI Â© 2025 | VersiÃ³n 1.0.0 | Base de Datos Oficial</p>
        </footer>
    </div>
</body>
</html>

