<?php
/**
 * test_apis.php
 * Prueba rápida de todos los APIs principales
 * 
 * Acceso: http://localhost/PRERMI/test_apis.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/api/utils.php';

requireLocalAccess(false);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test APIs - PRER_MI</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .test-case {
            margin: 15px 0;
            padding: 15px;
            background: #ecf0f1;
            border-radius: 5px;
            border-left: 3px solid #95a5a6;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .info {
            color: #3498db;
            font-weight: bold;
        }
        code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            display: block;
            margin: 10px 0;
            padding: 10px;
            overflow-x: auto;
        }
        .response {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px 5px 5px 0;
        }
        button:hover {
            background: #2980b9;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .stat-box {
            background: #3498db;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 28px;
            font-weight: bold;
        }
        .stat-box .label {
            font-size: 12px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧪 Test de APIs - PRER_MI</h1>
        <p>Verifica que todos los endpoints principales funcionan correctamente</p>
    </div>

    <?php
    try {
        $pdo = getPDO();
        echo '<div class="test-section">';
        echo '<h2>✓ Conexión a Base de Datos</h2>';
        echo '<p class="success">Conexión PDO a prer_mi establecida correctamente</p>';
        
        // Estadísticas
        $tablas = ['usuarios', 'usuarios_admin', 'vehiculos_registrados', 'contenedores_registrados', 'depositos', 'multas', 'logs_sistema'];
        echo '<div class="stats">';
        
        foreach ($tablas as $tabla) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM $tabla");
            $stmt->execute();
            $count = $stmt->fetch()['total'];
            echo '<div class="stat-box">';
            echo '<div class="number">' . $count . '</div>';
            echo '<div class="label">' . ucfirst(str_replace('_', ' ', $tabla)) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="test-section">';
        echo '<p class="error">❌ Error de conexión: ' . $e->getMessage() . '</p>';
        echo '</div>';
        exit;
    }
    ?>

    <div class="test-section">
        <h2>🔐 Test de APIs de Autenticación</h2>
        
        <div class="test-case">
            <h3>1. Obtener Admins (GET)</h3>
            <code>GET /api/admin/obtener_admins.php</code>
            <button onclick="testObtenerAdmins()">Ejecutar</button>
            <div id="response-admins" class="response" style="display:none;"></div>
        </div>

        <div class="test-case">
            <h3>2. Estructura de Usuarios</h3>
            <p>Verifica que la tabla usuarios existe y tiene la estructura correcta</p>
            <button onclick="testEstructuraUsuarios()">Ejecutar</button>
            <div id="response-usuarios" class="response" style="display:none;"></div>
        </div>

        <div class="test-case">
            <h3>3. Listar Vehículos Registrados</h3>
            <code>GET /api/vehiculos/vehiculos_registrados.php</code>
            <button onclick="testVehiculos()">Ejecutar</button>
            <div id="response-vehiculos" class="response" style="display:none;"></div>
        </div>

        <div class="test-case">
            <h3>4. Integridad de Foreign Keys</h3>
            <p>Verifica que todas las referencias entre tablas son válidas</p>
            <button onclick="testForeignKeys()">Ejecutar</button>
            <div id="response-fk" class="response" style="display:none;"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>📊 Herramientas de Diagnóstico</h2>
        
        <div class="test-case">
            <h3>Verificación Completa de Integridad</h3>
            <p>Revisa toda la estructura de la BD y verifica que todo sea correcto</p>
            <button onclick="window.location.href='/PRERMI/verificar_bd_integridad.php'">Ver Reporte Completo</button>
        </div>

        <div class="test-case">
            <h3>Instalador de Base de Datos</h3>
            <p>Si aún no has importado la BD, usa este script automático</p>
            <button onclick="window.location.href='/PRERMI/instalar_bd.php'">Ir al Instalador</button>
        </div>

        <div class="test-case">
            <h3>Schema de Base de Datos</h3>
            <p>Documentación completa de todas las tablas y campos</p>
            <button onclick="window.location.href='/PRERMI/DB_PRER_MI_SCHEMA.md'">Ver Schema</button>
        </div>
    </div>

    <script>
        function testObtenerAdmins() {
            const div = document.getElementById('response-admins');
            div.textContent = 'Cargando...';
            div.style.display = 'block';
            
            fetch('/PRERMI/api/admin/obtener_admins.php')
                .then(r => r.json())
                .then(data => {
                    div.innerHTML = '<strong>Respuesta:</strong>\n' + 
                                   JSON.stringify(data, null, 2);
                    if (data.success && data.data.admins.length > 0) {
                        div.innerHTML = '<span class="success">✓ OK</span>\n' + 
                                       JSON.stringify(data, null, 2);
                    }
                })
                .catch(e => {
                    div.innerHTML = '<span class="error">✗ Error: ' + e.message + '</span>';
                });
        }

        function testEstructuraUsuarios() {
            const div = document.getElementById('response-usuarios');
            div.textContent = 'Cargando...';
            div.style.display = 'block';
            
            fetch('/PRERMI/verificar_bd_integridad.php')
                .then(r => r.json())
                .then(data => {
                    const estructura = data.verificaciones.estructura_usuarios;
                    let html = '<strong>Estructura de tabla usuarios:</strong>\n\n';
                    let allOk = true;
                    
                    for (let [campo, estado] of Object.entries(estructura)) {
                        html += `${estado} ${campo}\n`;
                        if (estado !== '✓') allOk = false;
                    }
                    
                    if (allOk) {
                        div.innerHTML = '<span class="success">✓ OK - Todos los campos presentes</span>\n' + 
                                       html.replace(/\n/g, '<br>');
                    } else {
                        div.innerHTML = '<span class="error">✗ Faltan campos</span>\n' + 
                                       html.replace(/\n/g, '<br>');
                    }
                })
                .catch(e => {
                    div.innerHTML = '<span class="error">✗ Error: ' + e.message + '</span>';
                });
        }

        function testVehiculos() {
            const div = document.getElementById('response-vehiculos');
            div.textContent = 'Cargando...';
            div.style.display = 'block';
            
            fetch('/PRERMI/api/vehiculos/vehiculos_registrados.php')
                .then(r => r.json())
                .then(data => {
                    div.innerHTML = '<strong>Respuesta:</strong>\n' + 
                                   JSON.stringify(data, null, 2);
                    if (data.success) {
                        const count = data.data.vehiculos ? data.data.vehiculos.length : 0;
                        div.innerHTML = '<span class="success">✓ OK - ' + count + ' vehículo(s)</span>\n' + 
                                       JSON.stringify(data, null, 2);
                    }
                })
                .catch(e => {
                    div.innerHTML = '<span class="error">✗ Error: ' + e.message + '</span>';
                });
        }

        function testForeignKeys() {
            const div = document.getElementById('response-fk');
            div.textContent = 'Cargando...';
            div.style.display = 'block';
            
            fetch('/PRERMI/verificar_bd_integridad.php')
                .then(r => r.json())
                .then(data => {
                    const fks = data.verificaciones.foreign_keys;
                    let html = '<strong>Foreign Keys:</strong>\n\n';
                    let allOk = true;
                    
                    for (let [fk, estado] of Object.entries(fks)) {
                        html += `${estado} ${fk}\n`;
                        if (!estado.includes('✓')) allOk = false;
                    }
                    
                    if (allOk) {
                        div.innerHTML = '<span class="success">✓ OK - Todas las referencias válidas</span>\n' + 
                                       html.replace(/\n/g, '<br>');
                    } else {
                        div.innerHTML = '<span class="error">⚠ Algunas referencias inválidas</span>\n' + 
                                       html.replace(/\n/g, '<br>');
                    }
                })
                .catch(e => {
                    div.innerHTML = '<span class="error">✗ Error: ' + e.message + '</span>';
                });
        }
    </script>
</body>
</html>
