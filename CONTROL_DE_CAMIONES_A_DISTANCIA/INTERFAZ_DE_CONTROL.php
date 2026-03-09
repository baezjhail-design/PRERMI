<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Remoto del Camión - PRERMI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #00d4ff;
            --danger: #f44336;
            --success: #00e676;
            --warning: #ffeb3b;
            --bg-dark: #0a0f1e;
            --bg-card: #1a1f35;
            --text-light: #e8ecff;
            --text-muted: #8892b8;
            --grad-main: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1426 100%);
            color: var(--text-light);
            font-family: 'Manrope', sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 0.6s ease-in;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--grad-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .control-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .control-panel {
            background: var(--bg-card);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .panel-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--accent);
        }

        .panel-title i {
            background: var(--grad-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .joystick-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .joystick {
            position: relative;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle at center, rgba(0, 212, 255, 0.1) 0%, rgba(0, 212, 255, 0.05) 70%, transparent 100%);
            border: 2px solid var(--accent);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: grab;
            touch-action: none;
        }

        .joystick.active {
            border-color: var(--success);
            background: radial-gradient(circle at center, rgba(0, 230, 118, 0.1) 0%, rgba(0, 230, 118, 0.05) 70%, transparent 100%);
        }

        .joystick-handle {
            position: absolute;
            width: 60px;
            height: 60px;
            background: var(--grad-main);
            border-radius: 50%;
            cursor: grabbing;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 1.5rem;
        }

        .directional-buttons {
            display: grid;
            grid-template-columns: repeat(3, 60px);
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            user-select: none;
            font-family: 'Manrope', sans-serif;
        }

        .btn.primary {
            background: var(--grad-main);
            color: white;
        }

        .btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn.primary:active {
            transform: translateY(0);
        }

        .btn.direction {
            width: 60px;
            height: 60px;
            padding: 0;
            background: rgba(102, 126, 234, 0.2);
            color: var(--accent);
            border: 1px solid var(--accent);
        }

        .btn.direction:hover {
            background: var(--grad-main);
            color: white;
        }

        .btn.direction:active {
            background: var(--secondary);
        }

        .btn.danger {
            background: linear-gradient(135deg, #f44336 0%, #e53935 100%);
            color: white;
        }

        .btn.danger:hover {
            box-shadow: 0 8px 20px rgba(244, 67, 54, 0.4);
            transform: translateY(-2px);
        }

        .btn.success {
            background: linear-gradient(135deg, #00e676 0%, #00c853 100%);
            color: #000;
        }

        .btn.success:hover {
            box-shadow: 0 8px 20px rgba(0, 230, 118, 0.4);
        }

        .control-group {
            margin-bottom: 20px;
        }

        .control-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--accent);
            font-size: 0.95rem;
        }

        .wheel-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .wheel-control {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 10px;
            padding: 15px;
        }

        .wheel-control h4 {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .speed-slider {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: linear-gradient(to right, rgba(0, 212, 255, 0.3), var(--accent));
            outline: none;
            -webkit-appearance: none;
            appearance: none;
        }

        .speed-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--grad-main);
            cursor: pointer;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }

        .speed-slider::-moz-range-thumb {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--grad-main);
            cursor: pointer;
            border: none;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }

        .speed-value {
            display: text-align center;
            color: var(--accent);
            font-weight: 700;
            font-size: 0.85rem;
            margin-top: 5px;
            text-align: center;
        }

        .servo-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .servo-control {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-servo {
            padding: 15px;
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid var(--accent);
            color: var(--accent);
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-servo:hover {
            background: var(--grad-main);
            color: white;
        }

        .btn-servo.active {
            background: var(--success);
            color: #000;
            border-color: var(--success);
        }

        .status-panel {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .status-item {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(0, 230, 118, 0.3);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .status-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .status-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--success);
        }

        .status-value.warning {
            color: var(--warning);
        }

        .status-value.danger {
            color: var(--danger);
        }

        .emergency-panel {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), rgba(229, 57, 53, 0.05));
            border: 2px solid var(--danger);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }

        .emergency-panel h3 {
            color: var(--danger);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .btn-emergency {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #f44336 0%, #e53935 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-emergency:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(244, 67, 54, 0.5);
        }

        .btn-emergency:active {
            transform: translateY(0);
        }

        .info-log {
            background: var(--bg-card);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
            font-size: 0.85rem;
            font-family: 'Courier New', monospace;
        }

        .log-entry {
            padding: 8px;
            margin-bottom: 5px;
            border-left: 3px solid var(--accent);
            background: rgba(0, 212, 255, 0.05);
            color: var(--text-muted);
        }

        .log-entry.error {
            border-left-color: var(--danger);
            background: rgba(244, 67, 54, 0.05);
        }

        .log-entry.success {
            border-left-color: var(--success);
            background: rgba(0, 230, 118, 0.05);
        }

        .connection-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: var(--danger);
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 1s infinite;
        }

        .connection-indicator.connected {
            background: var(--success);
            animation: none;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .control-grid {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .joystick {
                width: 150px;
                height: 150px;
            }

            .joystick-handle {
                width: 45px;
                height: 45px;
            }

            .directional-buttons {
                grid-template-columns: repeat(3, 50px);
            }

            .btn.direction {
                width: 50px;
                height: 50px;
            }
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-truck"></i> Control Remoto del Camión</h1>
            <p>Sistema de control para camión de recolección de residuos (4 ruedas + 2 servos)</p>
        </div>

        <!-- Panel de Emergencia -->
        <div class="emergency-panel">
            <h3><i class="fas fa-exclamation-triangle"></i> BOTÓN DE EMERGENCIA</h3>
            <button class="btn-emergency" onclick="emergencyStop()">
                <i class="fas fa-stop-circle"></i> DETENER TODOS LOS MOTORES
            </button>
        </div>

        <!-- Grilla Principal de Control -->
        <div class="control-grid">
            <!-- Panel de Movimiento -->
            <div class="control-panel">
                <h2 class="panel-title">
                    <i class="fas fa-arrows-move"></i> Movimiento del Camión
                </h2>

                <!-- Botones Direccionales -->
                <div style="margin-bottom: 20px;">
                    <label style="text-align: center; display: block; margin-bottom: 15px; color: var(--text-muted); font-size: 0.9rem;">
                        Usa los botones para mover el camión
                    </label>
                    <div class="directional-buttons">
                        <button class="btn direction" id="btn-up" title="Adelante">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button class="btn direction" style="visibility: hidden;"></button>
                        <button class="btn direction" id="btn-diagonal-up-right" title="Arriba-Derecha">
                            <i class="fas fa-arrow-up"></i>
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <button class="btn direction" id="btn-left" title="Izquierda">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <button class="btn direction" id="btn-stop" title="Detener">
                            <i class="fas fa-stop-circle"></i>
                        </button>
                        <button class="btn direction" id="btn-right" title="Derecha">
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <button class="btn direction" style="visibility: hidden;"></button>
                        <button class="btn direction" id="btn-down" title="Atrás">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button class="btn direction" style="visibility: hidden;"></button>
                    </div>
                </div>

                <!-- Control de Velocidad por Rueda -->
                <div class="control-group">
                    <label>Control de Velocidad por Rueda</label>
                    <div class="wheel-controls">
                        <div class="wheel-control">
                            <h4>Rueda Delantera Izquierda (IN1)</h4>
                            <input type="range" class="speed-slider" id="speed-in1" min="0" max="255" value="150">
                            <div class="speed-value"><span id="value-in1">150</span>/255</div>
                        </div>
                        <div class="wheel-control">
                            <h4>Rueda Delantera Derecha (IN2)</h4>
                            <input type="range" class="speed-slider" id="speed-in2" min="0" max="255" value="150">
                            <div class="speed-value"><span id="value-in2">150</span>/255</div>
                        </div>
                        <div class="wheel-control">
                            <h4>Rueda Trasera Izquierda (IN3)</h4>
                            <input type="range" class="speed-slider" id="speed-in3" min="0" max="255" value="150">
                            <div class="speed-value"><span id="value-in3">150</span>/255</div>
                        </div>
                        <div class="wheel-control">
                            <h4>Rueda Trasera Derecha (IN4)</h4>
                            <input type="range" class="speed-slider" id="speed-in4" min="0" max="255" value="150">
                            <div class="speed-value"><span id="value-in4">150</span>/255</div>
                        </div>
                    </div>
                </div>

                <!-- Estado de Conexión -->
                <div class="status-panel">
                    <div class="status-item">
                        <div class="status-label">Estado de Conexión</div>
                        <div class="status-value">
                            <span class="connection-indicator" id="connection-indicator"></span>
                            <span id="connection-status">Desconectado</span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Última Actualización</div>
                        <div class="status-value" style="font-size: 0.9rem;" id="last-update">---</div>
                    </div>
                </div>
            </div>

            <!-- Panel de Servos -->
            <div class="control-panel">
                <h2 class="panel-title">
                    <i class="fas fa-cogs"></i> Control de Servomotores
                </h2>

                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                    Servomotores para levantamiento de contenedores y descarga de residuos
                </p>

                <div class="servo-controls">
                    <!-- Servo 1 -->
                    <div class="servo-control">
                        <label style="color: var(--accent); font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-lever"></i> Servomotor 1 (ServoPin1)
                        </label>
                        <button class="btn-servo" id="servo1-up" onclick="moveServo1Up()">
                            <i class="fas fa-arrow-circle-up"></i> Levantador
                        </button>
                        <button class="btn-servo" id="servo1-down" onclick="moveServo1Down()" style="margin-top: 10px;">
                            <i class="fas fa-arrow-circle-down"></i> Bajar
                        </button>
                        <div style="color: var(--text-muted); font-size: 0.8rem; text-align: center; margin-top: 10px;">
                            Ángulo: <strong id="servo1-angle">90</strong>°
                        </div>
                    </div>

                    <!-- Servo 2 -->
                    <div class="servo-control">
                        <label style="color: var(--accent); font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-lever"></i> Servomotor 2 (ServoPin2)
                        </label>
                        <button class="btn-servo" id="servo2-up" onclick="moveServo2Up()">
                            <i class="fas fa-arrow-circle-up"></i> Levantador
                        </button>
                        <button class="btn-servo" id="servo2-down" onclick="moveServo2Down()" style="margin-top: 10px;">
                            <i class="fas fa-arrow-circle-down"></i> Bajar
                        </button>
                        <div style="color: var(--text-muted); font-size: 0.8rem; text-align: center; margin-top: 10px;">
                            Ángulo: <strong id="servo2-angle">90</strong>°
                        </div>
                    </div>
                </div>

                <!-- Control de Ángulo Manual -->
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(102, 126, 234, 0.2);">
                    <label style="color: var(--accent); font-weight: 600; display: block; margin-bottom: 15px;">
                        <i class="fas fa-sliders-h"></i> Control de Ángulo Manual
                    </label>

                    <div class="wheel-controls" style="margin-bottom: 0;">
                        <div class="wheel-control">
                            <h4>Servo 1 - Ángulo Custom</h4>
                            <input type="range" class="speed-slider" id="servo1-angle-slider" min="0" max="180" value="90">
                            <div class="speed-value"><span id="servo1-custom">90</span>°</div>
                            <button class="btn primary" style="width: 100%; margin-top: 10px; padding: 8px;" onclick="setServo1Angle()">
                                Aplicar
                            </button>
                        </div>
                        <div class="wheel-control">
                            <h4>Servo 2 - Ángulo Custom</h4>
                            <input type="range" class="speed-slider" id="servo2-angle-slider" min="0" max="180" value="90">
                            <div class="speed-value"><span id="servo2-custom">90</span>°</div>
                            <button class="btn primary" style="width: 100%; margin-top: 10px; padding: 8px;" onclick="setServo2Angle()">
                                Aplicar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Información y Logs -->
        <div class="control-panel">
            <h2 class="panel-title">
                <i class="fas fa-terminal"></i> Información del Sistema
            </h2>
            <div class="info-log" id="log-container">
                <div class="log-entry success">
                    <i class="fas fa-check-circle"></i> Sistema inicializado correctamente
                </div>
                <div class="log-entry">
                    <i class="fas fa-info-circle"></i> Esperando conexión del ESP8266...
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============================================================================
        // CONFIGURACIÓN Y CONSTANTES
        // ============================================================================
        const API_ENDPOINT = 'control_api.php';
        const SERVO1_MIN_ANGLE = 0;
        const SERVO1_MAX_ANGLE = 180;
        const SERVO2_MIN_ANGLE = 0;
        const SERVO2_MAX_ANGLE = 180;

        // Estado atual del sistema
        let systemState = {
            connected: false,
            in1Speed: 150,
            in2Speed: 150,
            in3Speed: 150,
            in4Speed: 150,
            servo1Angle: 90,
            servo2Angle: 90
        };

        // ============================================================================
        // FUNCIONES DE UTILIDAD
        // ============================================================================

        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('log-container');
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.innerHTML = `<i class="fas fa-${getLogIcon(type)}"></i> ${message}`;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;

            // Limitar el número de entradas
            if (logContainer.children.length > 50) {
                logContainer.removeChild(logContainer.firstChild);
            }
        }

        function getLogIcon(type) {
            switch(type) {
                case 'success': return 'check-circle';
                case 'error': return 'times-circle';
                default: return 'info-circle';
            }
        }

        function updateConnectionStatus(connected) {
            systemState.connected = connected;
            const indicator = document.getElementById('connection-indicator');
            const status = document.getElementById('connection-status');
            const lastUpdate = document.getElementById('last-update');

            if (connected) {
                indicator.classList.add('connected');
                status.textContent = 'Conectado';
                status.style.color = 'var(--success)';
                addLog('Conexión establecida con ESP8266', 'success');
            } else {
                indicator.classList.remove('connected');
                status.textContent = 'Desconectado';
                status.style.color = 'var(--danger)';
            }

            lastUpdate.textContent = new Date().toLocaleTimeString();
        }

        function sendCommand(command) {
            const payload = {
                command: command,
                in1: systemState.in1Speed,
                in2: systemState.in2Speed,
                in3: systemState.in3Speed,
                in4: systemState.in4Speed,
                servo1: systemState.servo1Angle,
                servo2: systemState.servo2Angle
            };

            fetch(API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addLog(`Comando: ${command}`, 'success');
                    updateConnectionStatus(true);
                } else {
                    addLog(`Error: ${data.message}`, 'error');
                    updateConnectionStatus(false);
                }
            })
            .catch(error => {
                addLog(`Error de conexión: ${error.message}`, 'error');
                updateConnectionStatus(false);
            });
        }

        // ============================================================================
        // CONTROL DE MOVIMIENTO
        // ============================================================================

        document.getElementById('btn-up').addEventListener('mousedown', () => sendCommand('FORWARD'));
        document.getElementById('btn-up').addEventListener('mouseup', () => sendCommand('STOP'));
        document.getElementById('btn-up').addEventListener('touchstart', () => sendCommand('FORWARD'));
        document.getElementById('btn-up').addEventListener('touchend', () => sendCommand('STOP'));

        document.getElementById('btn-down').addEventListener('mousedown', () => sendCommand('BACKWARD'));
        document.getElementById('btn-down').addEventListener('mouseup', () => sendCommand('STOP'));
        document.getElementById('btn-down').addEventListener('touchstart', () => sendCommand('BACKWARD'));
        document.getElementById('btn-down').addEventListener('touchend', () => sendCommand('STOP'));

        document.getElementById('btn-left').addEventListener('mousedown', () => sendCommand('LEFT'));
        document.getElementById('btn-left').addEventListener('mouseup', () => sendCommand('STOP'));
        document.getElementById('btn-left').addEventListener('touchstart', () => sendCommand('LEFT'));
        document.getElementById('btn-left').addEventListener('touchend', () => sendCommand('STOP'));

        document.getElementById('btn-right').addEventListener('mousedown', () => sendCommand('RIGHT'));
        document.getElementById('btn-right').addEventListener('mouseup', () => sendCommand('STOP'));
        document.getElementById('btn-right').addEventListener('touchstart', () => sendCommand('RIGHT'));
        document.getElementById('btn-right').addEventListener('touchend', () => sendCommand('STOP'));

        document.getElementById('btn-stop').addEventListener('click', () => sendCommand('STOP'));

        // ============================================================================
        // CONTROL DE VELOCIDAD
        // ============================================================================

        ['speed-in1', 'speed-in2', 'speed-in3', 'speed-in4'].forEach((id, index) => {
            const slider = document.getElementById(id);
            const valueElement = document.getElementById(`value-in${index + 1}`);
            slider.addEventListener('input', (e) => {
                const value = e.target.value;
                valueElement.textContent = value;
                systemState[`in${index + 1}Speed`] = parseInt(value);
                addLog(`Velocidad IN${index + 1}: ${value}/255`);
            });
        });

        // ============================================================================
        // CONTROL DE SERVOMOTORES
        // ============================================================================

        function moveServo1Up() {
            systemState.servo1Angle = Math.max(systemState.servo1Angle - 10, SERVO1_MIN_ANGLE);
            updateServo1Display();
            sendCommand('SERVO1_MOVE');
        }

        function moveServo1Down() {
            systemState.servo1Angle = Math.min(systemState.servo1Angle + 10, SERVO1_MAX_ANGLE);
            updateServo1Display();
            sendCommand('SERVO1_MOVE');
        }

        function moveServo2Up() {
            systemState.servo2Angle = Math.max(systemState.servo2Angle - 10, SERVO2_MIN_ANGLE);
            updateServo2Display();
            sendCommand('SERVO2_MOVE');
        }

        function moveServo2Down() {
            systemState.servo2Angle = Math.min(systemState.servo2Angle + 10, SERVO2_MAX_ANGLE);
            updateServo2Display();
            sendCommand('SERVO2_MOVE');
        }

        function setServo1Angle() {
            const slider = document.getElementById('servo1-angle-slider');
            systemState.servo1Angle = parseInt(slider.value);
            updateServo1Display();
            sendCommand('SERVO1_MOVE');
        }

        function setServo2Angle() {
            const slider = document.getElementById('servo2-angle-slider');
            systemState.servo2Angle = parseInt(slider.value);
            updateServo2Display();
            sendCommand('SERVO2_MOVE');
        }

        function updateServo1Display() {
            document.getElementById('servo1-angle').textContent = systemState.servo1Angle;
            document.getElementById('servo1-custom').textContent = systemState.servo1Angle;
            document.getElementById('servo1-angle-slider').value = systemState.servo1Angle;
        }

        function updateServo2Display() {
            document.getElementById('servo2-angle').textContent = systemState.servo2Angle;
            document.getElementById('servo2-custom').textContent = systemState.servo2Angle;
            document.getElementById('servo2-angle-slider').value = systemState.servo2Angle;
        }

        // ============================================================================
        // BOTÓN DE EMERGENCIA
        // ============================================================================

        function emergencyStop() {
            if (confirm('¿Deseas detener todos los motores de emergencia?')) {
                sendCommand('EMERGENCY_STOP');
                addLog('EMERGENCIA: Todos los motores detenidos', 'error');
            }
        }

        // ============================================================================
        // INICIALIZACIÓN
        // ============================================================================

        document.addEventListener('DOMContentLoaded', () => {
            addLog('Interfaz de control cargada');
            // Intentar conexión inicial
            updateConnectionStatus(false);
        });

        // Actualizar el timestamp cada segundo
        setInterval(() => {
            if (systemState.connected) {
                document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
            }
        }, 1000);
    </script>
</body>
</html>
