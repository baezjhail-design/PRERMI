<?php
session_start();

// Permitir acceso si:
// 1. Está logueado (tiene SESSION)
// 2. O proporciona token válido
$token_valido = false;
$tiene_sesion = isset($_SESSION['user_id']);

if (!$tiene_sesion && isset($_GET['token'])) {
    $tokens_aceptados = ['esp8266_sensor_token', 'esp32_devkit1_biomasa', 'esp32s3_sensor_token'];
    $token_valido = in_array($_GET['token'], $tokens_aceptados);
}

// Si no tiene sesión ni token válido, redirigir
if (!$tiene_sesion && !$token_valido) {
    header("Location: ../web/login.php");
    exit;
}

$mensaje = "";
$tipo_mensaje = "";

// Procesar comando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comando'])) {
    $comando = $_POST['comando'] ?? "";
    $descripcion = $_POST['descripcion'] ?? "Basado en: " . $comando;
    
    // Construir URL para enviar comando al servidor
    $url = "https://prermi.duckdns.org/PRERMI/BIOMASA/comandos.php";
    
    // Preparar datos incluyendo token si no tiene sesión
    $data_array = [
        "comando" => $comando,
        "descripcion" => $descripcion
    ];
    
    // Agregar token si está disponible (no hay sesión)
    if (!$tiene_sesion && $token_valido) {
        $data_array["token"] = $_GET['token'];
    }
    
    $data = json_encode($data_array);
    
    // Enviar comando
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Content-Length: " . strlen($data)
    ]);
    
    // Pasar sesión si está disponible
    if ($tiene_sesion) {
        curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result['status'] === 'ok') {
        $mensaje = "✓ Comando '{$comando}' enviado al Arduino exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "✗ Error: " . ($result['msg'] ?? "No se pudo enviar el comando");
        $tipo_mensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control BIOMASA - PRERMI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: none;
            animation: fadeIn 0.5s ease-in;
        }
        
        .mensaje.show {
            display: block;
        }
        
        .mensaje.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 30px;
        }
        
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-enviar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            grid-column: 1 / -1;
        }
        
        .btn-enviar:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-enviar:active {
            transform: translateY(0);
        }
        
        .comandos-rapidos {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-rapido {
            padding: 12px;
            background: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            color: #333;
        }
        
        .btn-rapido:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: #555;
        }
        
        .info-box strong {
            color: #333;
        }
        
        .monitor-link {
            display: inline-block;
            margin-top: 10px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .monitor-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔥 Control BIOMASA</h1>
        <p class="subtitle">Sistema de control remoto para ESP32-S3 CAM</p>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?> show">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>⌨️ Comandos Rápidos</label>
                <div class="comandos-rapidos">
                    <button type="button" class="btn-rapido" onclick="establecerComando('inicio', 'Iniciar sistema BIOMASA')">
                        ▶️ Iniciar
                    </button>
                    <button type="button" class="btn-rapido" onclick="establecerComando('parada', 'Detener sistema BIOMASA')">
                        ⏹️ Parar
                    </button>
                    <button type="button" class="btn-rapido" onclick="establecerComando('diagnostico', 'Ejecutar diagnóstico')">
                        🔍 Diagnóstico
                    </button>
                    <button type="button" class="btn-rapido" onclick="establecerComando('reset', 'Reiniciar ESP32')">
                        🔄 Reset
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="comando">📩 Seleccionar Comando</label>
                <select id="comando" name="comando" required>
                    <option value="">-- Selecciona un comando --</option>
                    <option value="inicio">Iniciar (START)</option>
                    <option value="parada">Parar (STOP)</option>
                    <option value="diagnostico">Ejecutar Diagnóstico</option>
                    <option value="reset">Reiniciar Sistema</option>
                    <option value="calibracion">Calibración</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="descripcion">📝 Descripción (Opcional)</label>
                <textarea id="descripcion" name="descripcion" placeholder="Descripción adicional para el comando..."></textarea>
            </div>
            
            <button type="submit" name="enviar_comando" class="btn-enviar">📤 Enviar Comando al Arduino</button>
        </form>
        
        <div class="info-box">
            <strong>ℹ️ Información:</strong><br>
            El comando se enviará al Arduino y verás la respuesta en el Serial Monitor.<br>
            <a href="#" class="monitor-link">Ver Serial Monitor en tiempo real →</a>
        </div>
    </div>
    
    <script>
        function establecerComando(cmd, desc) {
            document.getElementById('comando').value = cmd;
            document.getElementById('descripcion').value = desc;
            // Auto-submit
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control BIOMASA - PRERMI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: none;
            animation: fadeIn 0.5s ease-in;
        }
        
        .mensaje.show {
            display: block;
        }
        
        .mensaje.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 30px;
        }
        
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-enviar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            grid-column: 1 / -1;
        }
        
        .btn-enviar:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-enviar:active {
            transform: translateY(0);
        }
        
        .comandos-rapidos {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-rapido {
            padding: 12px;
            background: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            color: #333;
        }
        
        .btn-rapido:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: #555;
        }
        
        .info-box strong {
            color: #333;
        }
        
        .monitor-link {
            display: inline-block;
            margin-top: 10px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .monitor-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔥 Control BIOMASA</h1>
        <p class="subtitle">Sistema de control remoto para ESP32-S3 CAM</p>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?> show">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>⌨️ Comandos Rápidos</label>
                <div class="comandos-rapidos">
                    <button type="button" class="btn-rapido" onclick="establecerComando('inicio', 'Iniciar sistema BIOMASA')">
                        ▶️ Iniciar
                    </button>
                    <button type="button" class="btn-rapido" onclick="establecerComando('parada', 'Detener sistema BIOMASA')">
                        ⏹️ Parar
                    </button>
                    <button type="button" class="btn-rapido" onclick="establecerComando('diagnostico', 'Ejecutar diagnóstico')">
                        🔍 Diagnóstico
                    </button>
                    <button type="button" class="btn-rapido" onclick="establecerComando('reset', 'Reiniciar ESP32')">
                        🔄 Reset
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="comando">📩 Seleccionar Comando</label>
                <select id="comando" name="comando" required>
                    <option value="">-- Selecciona un comando --</option>
                    <option value="inicio">Iniciar (START)</option>
                    <option value="parada">Parar (STOP)</option>
                    <option value="diagnostico">Ejecutar Diagnóstico</option>
                    <option value="reset">Reiniciar Sistema</option>
                    <option value="calibracion">Calibración</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="descripcion">📝 Descripción (Opcional)</label>
                <textarea id="descripcion" name="descripcion" placeholder="Descripción adicional para el comando..."></textarea>
            </div>
            
            <button type="submit" name="enviar_comando" class="btn-enviar">📤 Enviar Comando al Arduino</button>
        </form>
        
        <div class="info-box">
            <strong>ℹ️ Información:</strong><br>
            El comando se enviará al Arduino y verás la respuesta en el Serial Monitor.<br>
            <a href="#" class="monitor-link">Ver Serial Monitor en tiempo real →</a>
        </div>
    </div>
    
    <script>
        function establecerComando(cmd, desc) {
            document.getElementById('comando').value = cmd;
            document.getElementById('descripcion').value = desc;
            // Auto-submit
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
