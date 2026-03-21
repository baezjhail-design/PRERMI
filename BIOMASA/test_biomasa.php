<?php
/**
 * test_biomasa.php - Script de testing para sistema BIOMASA
 * 
 * USO:
 * 1. Accede a http://localhost/PRERMI/BIOMASA/test_biomasa.php
 * 2. Lee los tests y verifica que todos pasen
 * 
 * O desde línea de comandos:
 * php test_biomasa.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../api/utils.php';
requireLocalAccess(false);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test BIOMASA System</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            background: #1a1a1a;
            color: #00ff00;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #00ff00;
        }
        h1 {
            text-align: center;
            color: #00ff00;
        }
        .test-group {
            margin: 20px 0;
            padding: 15px;
            border-left: 3px solid #00ff00;
            background: #333;
        }
        .test-title {
            font-weight: bold;
            color: #ffff00;
            margin-bottom: 10px;
        }
        .pass {
            color: #00ff00;
            font-weight: bold;
        }
        .fail {
            color: #ff0000;
            font-weight: bold;
        }
        .warn {
            color: #ffaa00;
            font-weight: bold;
        }
        .code {
            background: #1a1a1a;
            padding: 10px;
            margin: 10px 0;
            border-left: 2px solid #00ff00;
            overflow-x: auto;
        }
        button {
            background: #00ff00;
            color: #1a1a1a;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #00cc00;
        }
        .result {
            margin: 10px 0;
            padding: 10px;
            border-left: 2px solid #0088ff;
            background: #1a2a3a;
        }
        .json-output {
            background: #1a1a1a;
            color: #00ff00;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 Sistema de Testing BIOMASA</h1>
    
    <div class="test-group">
        <div class="test-title">1️⃣ TEST FILES - Verificar existencia de archivos</div>
        <?php
        $files = [
            'api/status.json' => $_SERVER['DOCUMENT_ROOT'] . '/PRERMI/api/status.json',
            'api/control.json' => $_SERVER['DOCUMENT_ROOT'] . '/PRERMI/api/control.json',
            'api/mediciones_biomasa.json' => $_SERVER['DOCUMENT_ROOT'] . '/PRERMI/api/mediciones_biomasa.json',
            'sensores_estado.php' => __DIR__ . '/sensores_estado.php',
            'control_biomasa.php' => __DIR__ . '/control_biomasa.php',
        ];
        
        foreach ($files as $name => $path) {
            $exists = file_exists($path);
            $readable = is_readable($path);
            $writable = is_writable(dirname($path));
            
            $status = $exists && $readable && $writable ? '<span class="pass">✓ OK</span>' : '';
            $status = !$exists ? '<span class="fail">✗ NO EXISTE</span>' : $status;
            $status = $exists && !$readable ? '<span class="fail">✗ NO LEGIBLE</span>' : $status;
            $status = $exists && !$writable ? '<span class="warn">⚠ DIR NO ESCRIBIBLE</span>' : $status;
            
            echo "<div class=\"result\">$name: $status</div>";
        }
        ?>
    </div>
    
    <div class="test-group">
        <div class="test-title">2️⃣ TEST JSON Files - Contenido de archivos</div>
        <?php
        $jsonFiles = [
            'api/status.json' => $_SERVER['DOCUMENT_ROOT'] . '/PRERMI/api/status.json',
            'api/control.json' => $_SERVER['DOCUMENT_ROOT'] . '/PRERMI/api/control.json',
            'api/mediciones_biomasa.json' => $_SERVER['DOCUMENT_ROOT'] . '/PRERMI/api/mediciones_biomasa.json',
        ];
        
        foreach ($jsonFiles as $name => $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $decoded = json_decode($content, true);
                $valid = $decoded !== null ? '<span class="pass">✓ JSON Válido</span>' : '<span class="fail">✗ JSON Inválido</span>';
                echo "<div class=\"result\">\n";
                echo "<strong>$name:</strong> $valid\n";
                echo "<div class=\"json-output\">" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</div>\n";
                echo "</div>\n";
            }
        }
        ?>
    </div>
    
    <div class="test-group">
        <div class="test-title">3️⃣ TEST API ENDPOINTS - Probar endpoints</div>
        <p>Click en los botones para probar los endpoints:</p>
        
        <button onclick="testGET()">📡 GET /sensores_estado.php</button>
        <button onclick="testPOSTSensores()">📤 POST /sensores_estado.php (Simular ESP)</button>
        <button onclick="testPOSTControl('START')">▶ POST START</button>
        <button onclick="testPOSTControl('STOP')">⏹ POST STOP</button>
        
        <div id="apiResults"></div>
    </div>
    
    <div class="test-group">
        <div class="test-title">4️⃣ TEST DATABASE - Tablas de BD</div>
        <?php
        $host = "localhost";
        $db   = "prer_mi";
        $user = "root";
        $pass = "";
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verificar tabla mediciones_biomasa
            $result = $pdo->query("SHOW TABLES LIKE 'mediciones_biomasa'");
            $exists = $result->rowCount() > 0;
            
            if ($exists) {
                echo '<div class="result"><span class="pass">✓ Tabla mediciones_biomasa existe</span>';
                
                $count = $pdo->query("SELECT COUNT(*) as cnt FROM mediciones_biomasa")->fetch();
                echo "<br/>Registros en tabla: " . $count['cnt'] . "</div>";
            } else {
                echo '<div class="result"><span class="warn">⚠ Tabla mediciones_biomasa no existe (se crea automáticamente en biores.php)</span></div>';
            }
        } catch (PDOException $e) {
            echo '<div class="result"><span class="fail">✗ Error BD: ' . htmlspecialchars($e->getMessage()) . '</span></div>';
        }
        ?>
    </div>
    
    <div class="test-group">
        <div class="test-title">5️⃣ TEST DASHBOARD - Acceso a biores.php</div>
        <?php
        $bioresPath = $_SERVER['DOCUMENT_ROOT'] . '/PRERMI/web/admin/biores.php';
        if (file_exists($bioresPath)) {
            echo '<div class="result"><span class="pass">✓ biores.php existe</span><br/>';
            echo '<a href="/PRERMI/web/admin/biores.php" target="_blank" style="color: #00ff00;">→ Abrir Dashboard</a></div>';
        } else {
            echo '<div class="result"><span class="fail">✗ biores.php no encontrado</span></div>';
        }
        ?>
    </div>
    
    <div class="test-group">
        <div class="test-title">6️⃣ ERROR LOG - Últimos errores del sistema</div>
        <?php
        $errorLog = $_SERVER['DOCUMENT_ROOT'] . '/../logs/php_errors.log';
        if (!file_exists($errorLog)) {
            $errorLog = ini_get('error_log');
        }
        
        if ($errorLog && file_exists($errorLog)) {
            $lines = array_slice(file($errorLog), -5);
            echo '<div class="json-output">' . htmlspecialchars(implode('', $lines)) . '</div>';
        } else {
            echo '<div class="result">No hay archivo de log disponible</div>';
        }
        ?>
    </div>
</div>

<script>
    function testGET() {
        const resultsDiv = document.getElementById('apiResults');
        resultsDiv.innerHTML = '<div class="result">⏳ Consultando...</div>';
        
        fetch('/PRERMI/BIOMASA/sensores_estado.php')
            .then(r => r.json())
            .then(data => {
                resultsDiv.innerHTML = '<div class="result"><span class="pass">✓ GET exitoso</span>\n' +
                    '<div class="json-output">' + JSON.stringify(data, null, 2) + '</div></div>';
            })
            .catch(e => {
                resultsDiv.innerHTML = '<div class="result"><span class="fail">✗ Error: ' + e.message + '</span></div>';
            });
    }
    
    function testPOSTSensores() {
        const resultsDiv = document.getElementById('apiResults');
        resultsDiv.innerHTML = '<div class="result">⏳ Enviando datos.....</div>';
        
        const data = {
            temperatura: 42.5,
            corriente: 2.1,
            ventilador: 1,
            calentador: 1,
            energia_generada: 16.3,
            sistema_activo: 1
        };
        
        fetch('/PRERMI/BIOMASA/sensores_estado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(data => {
                resultsDiv.innerHTML = '<div class="result"><span class="pass">✓ POST exitoso</span>\n' +
                    '<div class="json-output">' + JSON.stringify(data, null, 2) + '</div></div>';
                
                // Esperar 1 segundo y mostrar el status actualizado
                setTimeout(() => testGET(), 1000);
            })
            .catch(e => {
                resultsDiv.innerHTML = '<div class="result"><span class="fail">✗ Error: ' + e.message + '</span></div>';
            });
    }
    
    function testPOSTControl(accion) {
        const resultsDiv = document.getElementById('apiResults');
        resultsDiv.innerHTML = '<div class="result">⏳ Enviando ' + accion + '...</div>';
        
        fetch('/PRERMI/BIOMASA/control_biomasa.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'accion=' + accion
        })
            .then(r => r.json())
            .then(data => {
                resultsDiv.innerHTML = '<div class="result"><span class="pass">✓ Comando ' + accion + ' enviado</span>\n' +
                    '<div class="json-output">' + JSON.stringify(data, null, 2) + '</div></div>';
            })
            .catch(e => {
                resultsDiv.innerHTML = '<div class="result"><span class="fail">✗ Error: ' + e.message + '</span></div>';
            });
    }
</script>

</body>
</html>
