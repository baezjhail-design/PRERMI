<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: loginA.php");
    exit;
}

/* Usar configuración centralizada */
require_once __DIR__ . '/../../config/db_config.php';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Solo intentar conexión remota si estamos en InfinityFree
// (sql208.infinityfree.com no es accesible desde localhost)
$pdo_remote = null;
if ($isInfinityFree) {
    try {
        $pdo_remote = new PDO("mysql:host=$DB_HOST_REMOTE;dbname=$DB_NAME_REMOTE;charset=utf8mb4", $DB_USER_REMOTE, $DB_PASS_REMOTE);
        $pdo_remote->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        error_log("[REMOTE DB biores] Error: " . $e->getMessage());
    }
}

/* Crear tabla mediciones_biomasa si no existe */
$pdo->exec("CREATE TABLE IF NOT EXISTS mediciones_biomasa (
    id int(11) NOT NULL AUTO_INCREMENT,
    temperatura decimal(5,2) NOT NULL,
    energia decimal(8,2) NOT NULL,
    user_id int(11),
    relay decimal(8,2) DEFAULT 0.0,
    ventilador decimal(8,2) DEFAULT 0.0,
    peltier1 decimal(8,2) DEFAULT 0.0,
    peltier2 decimal(8,2) DEFAULT 0.0,
    gases decimal(8,2) DEFAULT 0.0,
    fecha timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ÚLTIMO REGISTRO */
try {
    $stmt = $pdo->query("SELECT temperatura, energia FROM mediciones_biomasa ORDER BY fecha DESC LIMIT 1");
    $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ultimo = ['temperatura' => 0, 'energia' => 0];
}

$tempActual = $ultimo['temperatura'] ?? 0;
$energiaActual = $ultimo['energia'] ?? 0;

/* INICIALIZAR VARIABLES PARA JAVASCRIPT */
// Se cargarán via AJAX cuando el usuario seleccione las fechas
$labels = [];
$tempData = [];
$energiaData = [];
$consumoData = [];

// Fecha por defecto para los pickers
$fechaHoy = date('Y-m-d');
$fechaPrimerDia = date('Y-m-01');
$fechaAnioActual = date('Y');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Bioenergético</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/PRERMI/web/assets/css/theme.css">
<script>(function(){var t=localStorage.getItem('prermi_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>

<style>
body{
    margin:0;
    font-family:'Poppins', sans-serif;
    background: linear-gradient(135deg, #1f4037, #99f2c8);
    min-height:100vh;
}

.dashboard-container{
    padding:40px 30px;
}

.dashboard-title{
    text-align:center;
    color:white;
    font-weight:700;
    margin-bottom:40px;
}

.stat-card{
    background: rgba(255,255,255,0.95);
    padding:30px;
    border-radius:20px;
    box-shadow:0 15px 35px rgba(0,0,0,0.15);
    text-align:center;
    transition:0.3s;
}

.stat-card:hover{
    transform:translateY(-8px);
}

.stat-value{
    font-size:3rem;
    font-weight:700;
}

.temp{
    color:#ff4b5c;
}

.energy{
    color:#1d84b5;
}

.stat-label{
    font-size:1.1rem;
    margin-top:10px;
    color:#555;
}

.chart-box{
    background:white;
    padding:25px;
    border-radius:20px;
    box-shadow:0 15px 35px rgba(0,0,0,0.1);
    margin-top:30px;
}

canvas{
    height:280px !important;
}

.footer{
    text-align:center;
    margin-top:40px;
    color:white;
    font-size:0.9rem;
}

/* CONTROL TOTAL SECTION */
.control-section{
    background: rgba(255,255,255,0.95);
    padding:40px;
    border-radius:20px;
    box-shadow:0 15px 35px rgba(0,0,0,0.15);
    margin-top:30px;
    margin-bottom:30px;
}

.control-title{
    text-align:center;
    color:#1f4037;
    font-size:1.8rem;
    font-weight:700;
    margin-bottom:30px;
}

.control-content{
    display:flex;
    justify-content:space-around;
    align-items:center;
    flex-wrap:wrap;
    gap:30px;
}

.control-group{
    flex:1;
    min-width:250px;
    text-align:center;
}

.led-indicator{
    width:80px;
    height:80px;
    border-radius:50%;
    margin:0 auto 15px;
    box-shadow:0 0 20px rgba(0,0,0,0.3);
    transition:all 0.3s ease;
    border:3px solid #ccc;
}

.led-indicator.off{
    background:linear-gradient(135deg, #555, #333);
    box-shadow:0 0 10px rgba(0,0,0,0.4);
}

.led-indicator.on{
    background:linear-gradient(135deg, #00ff00, #00cc00);
    box-shadow:0 0 30px #00ff00, inset 0 0 20px rgba(255,255,255,0.3);
}

.led-label{
    font-size:1rem;
    color:#555;
    font-weight:600;
    margin-bottom:10px;
}

.button-group{
    display:flex;
    gap:15px;
    justify-content:center;
    flex-wrap:wrap;
}

.control-btn{
    padding:15px 40px;
    font-size:1.1rem;
    font-weight:700;
    border:none;
    border-radius:12px;
    cursor:pointer;
    transition:all 0.3s ease;
    text-transform:uppercase;
    letter-spacing:1px;
    box-shadow:0 8px 15px rgba(0,0,0,0.2);
}

.control-btn:hover:not(:disabled){
    transform:translateY(-3px);
    box-shadow:0 12px 25px rgba(0,0,0,0.3);
}

.control-btn:active:not(:disabled){
    transform:translateY(-1px);
}

.control-btn:disabled{
    opacity:0.6;
    cursor:not-allowed;
}

.btn-start{
    background:linear-gradient(135deg, #00b894, #00a383);
    color:white;
}

.btn-start:hover:not(:disabled){
    background:linear-gradient(135deg, #00d084, #00c470);
}

.btn-stop{
    background:linear-gradient(135deg, #2563eb, #7c3aed);
    color:white;
}

.btn-stop:hover:not(:disabled){
    background:linear-gradient(135deg, #60a5fa, #2563eb);
}

.status-text{
    margin-top:15px;
    font-size:0.95rem;
    color:#666;
    font-weight:600;
}

.status-indicator{
    display:inline-block;
    width:12px;
    height:12px;
    border-radius:50%;
    margin-right:8px;
    animation:pulse 2s infinite;
}

.status-indicator.active{
    background:#00ff00;
}

.status-indicator.inactive{
    background:#7c3aed;
    animation:none;
}

@keyframes pulse{
    0%, 100%{
        box-shadow:0 0 0 0 rgba(0,255,0,0.7);
    }
    50%{
        box-shadow:0 0 0 10px rgba(0,255,0,0);
    }
}

.loading{
    display:inline-block;
    width:16px;
    height:16px;
    border:2px solid #f3f3f3;
    border-top:2px solid #1f4037;
    border-radius:50%;
    animation:spin 1s linear infinite;
    margin-left:8px;
    vertical-align:middle;
}

@keyframes spin{
    0%{transform:rotate(0deg);}
    100%{transform:rotate(360deg);}
}

.alert-info{
    background:#d1ecf1;
    color:#0c5460;
    border:1px solid #bee5eb;
    padding:12px 20px;
    border-radius:8px;
    margin-top:20px;
    font-size:0.95rem;
}

@media(max-width:770px){
    .control-content{
        flex-direction:column;
    }
    
    .button-group{
        flex-direction:column;
    }
    
    .control-btn{
        width:100%;
    }
}

/* SENSORES SECTION */
.sensores-section{
    background: rgba(255,255,255,0.95);
    padding:40px;
    border-radius:20px;
    box-shadow:0 15px 35px rgba(0,0,0,0.15);
    margin-top:30px;
    margin-bottom:30px;
}

.sensores-title{
    text-align:center;
    color:#1f4037;
    font-size:1.8rem;
    font-weight:700;
    margin-bottom:35px;
}

.sensores-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
    gap:30px;
    margin-bottom:20px;
}

.sensor-card{
    background:linear-gradient(135deg, #f5f7fa, #c3cfe2);
    padding:30px;
    border-radius:15px;
    text-align:center;
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
    transition:all 0.3s ease;
    border:2px solid transparent;
}

.sensor-card.activo{
    border-color:#00ff00;
    box-shadow:0 8px 20px rgba(0,255,0,0.3);
}

.sensor-card.sensando{
    border-color:#ffc107;
    box-shadow:0 8px 20px rgba(255,193,7,0.3);
}

.sensor-card:hover{
    transform:translateY(-5px);
}

.bombilla-container{
    margin:0 auto 20px;
    width:100px;
    height:120px;
    position:relative;
}

/* Bombilla apagada */
.bombilla{
    width:100%;
    height:85%;
    background:linear-gradient(135deg, #888, #444);
    border-radius:50% 50% 45% 45%;
    position:relative;
    box-shadow:inset 0 -5px 15px rgba(0,0,0,0.5), 0 8px 18px rgba(0,0,0,0.3);
    margin:0 auto;
}

.bombilla::before{
    content:'';
    position:absolute;
    width:60%;
    height:12px;
    background:#333;
    top:-8px;
    left:20%;
    border-radius:3px;
}

.bombilla::after{
    content:'';
    position:absolute;
    bottom:-15px;
    left:50%;
    transform:translateX(-50%);
    width:50%;
    height:15px;
    background:linear-gradient(90deg, #666, #333, #666);
    border-radius:2px;
}

/* Bombilla activa */
.bombilla.activa{
    background:linear-gradient(135deg, #ffeb3b, #ffc107);
    box-shadow:inset 0 -5px 15px rgba(255,193,7,-0.5), 0 0 40px #ffc107, 0 8px 18px rgba(255,193,7,0.5);
    animation:bombilla-brillo 2s ease-in-out infinite;
}

.bombilla.sensando{
    background:linear-gradient(135deg, #ff9800, #f57c00);
    box-shadow:inset 0 -5px 15px rgba(245,124,0,-0.5), 0 0 40px #ff9800, 0 8px 18px rgba(255,152,0,0.5);
    animation:bombilla-pulso 1.5s ease-in-out infinite;
}

@keyframes bombilla-brillo{
    0%{
        opacity:0.8;
        box-shadow:inset 0 -5px 15px rgba(255,193,7,-0.5), 0 0 40px #ffc107, 0 8px 18px rgba(255,193,7,0.5);
    }
    50%{
        opacity:1;
        box-shadow:inset 0 -5px 15px rgba(255,193,7,-0.5), 0 0 60px #ffc107, 0 8px 18px rgba(255,193,7,0.7);
    }
    100%{
        opacity:0.8;
        box-shadow:inset 0 -5px 15px rgba(255,193,7,-0.5), 0 0 40px #ffc107, 0 8px 18px rgba(255,193,7,0.5);
    }
}

@keyframes bombilla-pulso{
    0%{
        opacity:0.7;
        box-shadow:inset 0 -5px 15px rgba(245,124,0,-0.5), 0 0 30px #ff9800, 0 8px 18px rgba(255,152,0,0.4);
    }
    50%{
        opacity:1;
        box-shadow:inset 0 -5px 15px rgba(245,124,0,-0.5), 0 0 50px #ff9800, 0 8px 18px rgba(255,152,0,0.7);
    }
    100%{
        opacity:0.7;
        box-shadow:inset 0 -5px 15px rgba(245,124,0,-0.5), 0 0 30px #ff9800, 0 8px 18px rgba(255,152,0,0.4);
    }
}

.sensor-name{
    font-size:1.2rem;
    font-weight:700;
    color:#1f4037;
    margin-bottom:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}

.sensor-status{
    font-size:0.95rem;
    color:#666;
    font-weight:600;
    margin-bottom:5px;
    text-transform:uppercase;
    letter-spacing:1px;
}

.sensor-status.activo{
    color:#00b894;
}

.sensor-status.sensando{
    color:#ff9800;
}

.sensor-status.apagado{
    color:#999;
}

.sensor-valor{
    font-size:1.3rem;
    font-weight:700;
    color:#1f4037;
    margin-top:8px;
    padding:10px;
    background:rgba(255,255,255,0.7);
    border-radius:8px;
}

.sensor-timestamp{
    font-size:0.75rem;
    color:#999;
    margin-top:10px;
    font-style:italic;
}

.sensores-info{
    background:#f0f4fa;
    border-left:4px solid #1f4037;
    padding:15px;
    border-radius:8px;
    margin-top:20px;
    font-size:0.9rem;
    color:#333;
}

.sensores-info strong{
    color:#1f4037;
}

/* FILTROS DE FECHA */
.date-filters{
    background:rgba(255,255,255,0.9);
    padding:20px;
    border-radius:12px;
    margin-bottom:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.date-filters-row{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:center;
}

.filter-group{
    display:flex;
    flex-direction:column;
    gap:5px;
}

.filter-group label{
    font-size:0.85rem;
    font-weight:600;
    color:#1f4037;
}

.filter-group select,
.filter-group input{
    padding:8px 12px;
    border:2px solid #99f2c8;
    border-radius:8px;
    font-size:0.9rem;
    transition:all 0.3s;
}

.filter-group select:focus,
.filter-group input:focus{
    outline:none;
    border-color:#1f4037;
    box-shadow:0 0 0 3px rgba(31,64,55,0.1);
}

.btn-filter{
    padding:10px 25px;
    background:linear-gradient(135deg, #1f4037, #99f2c8);
    color:white;
    border:none;
    border-radius:8px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.3s;
    margin-top:20px;
}

.btn-filter:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(31,64,55,0.3);
}

/* GRÁFICO DE GANANCIAS */
.ganancias-summary{
    display:flex;
    justify-content:space-around;
    margin-top:15px;
    flex-wrap:wrap;
    gap:15px;
}

.ganancia-item{
    text-align:center;
    padding:15px 25px;
    background:rgba(255,255,255,0.5);
    border-radius:10px;
}

.ganancia-item .valor{
    font-size:1.8rem;
    font-weight:700;
    margin-bottom:5px;
}

.ganancia-item .label{
    font-size:0.9rem;
    color:#666;
    font-weight:600;
}

.ganancia-item.positivo .valor{color:#00b894;}
.ganancia-item.negativo .valor{color:#7c3aed;}
.ganancia-item.generado .valor{color:#1d84b5;}
.ganancia-item.consumido .valor{color:#ff9800;}

/* BOTONES DE EMERGENCIA */
.sensor-emergency-btn{
    width:100%;
    padding:10px 15px;
    margin-top:15px;
    background:linear-gradient(135deg, #7c3aed, #5b21b6);
    color:white;
    border:none;
    border-radius:8px;
    font-weight:700;
    font-size:0.85rem;
    cursor:pointer;
    transition:all 0.3s ease;
    text-transform:uppercase;
    letter-spacing:1px;
    box-shadow:0 4px 12px rgba(124,58,237,0.3);
    position:relative;
    overflow:hidden;
}

.sensor-emergency-btn::before{
    content:'';
    position:absolute;
    top:0;
    left:-100%;
    width:100%;
    height:100%;
    background:rgba(255,255,255,0.2);
    transition:left 0.3s ease;
}

.sensor-emergency-btn:hover {
    background:linear-gradient(135deg, #8b5cf6, #6d28d9);
    box-shadow:0 6px 18px rgba(124,58,237,0.5), 0 0 20px rgba(96,165,250,0.35);
    transform:translateY(-2px);
}

.sensor-emergency-btn:hover::before{
    left:100%;
}

.sensor-emergency-btn:active{
    transform:translateY(0);
    box-shadow:0 2px 8px rgba(124,58,237,0.3);
}

.sensor-emergency-btn:disabled{
    opacity:0.5;
    cursor:not-allowed;
    box-shadow:0 2px 4px rgba(0,0,0,0.2);
}

.sensor-emergency-btn i{
    margin-right:6px;
}

@media(max-width:768px){
    .sensores-grid{
        grid-template-columns:1fr;
    }
    
    .sensores-section{
        padding:25px;
    }
    
    .sensores-title{
        font-size:1.4rem;
    }
}

/* NAVBAR ADMIN */
.navbar-admin{background:linear-gradient(135deg,#1e40af 0%,#6d28d9 100%);padding:.75rem 1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;box-shadow:0 4px 20px rgba(30,64,175,.4);}
.nav-brand{color:#fff;font-weight:800;font-size:1.25rem;text-decoration:none;white-space:nowrap;}
.nav-links{display:flex;gap:.25rem;flex-wrap:wrap;flex:1;}
.nav-link-item{color:rgba(255,255,255,.8);padding:.4rem .75rem;border-radius:6px;text-decoration:none;font-size:.88rem;font-weight:500;transition:all .2s;white-space:nowrap;}
.nav-link-item:hover,.nav-link-item.active{background:rgba(255,255,255,.2);color:#fff;}
.nav-right{display:flex;align-items:center;gap:.5rem;}
.nav-user{color:#fff;font-weight:600;font-size:.9rem;}
.btn-logout{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:.35rem .75rem;border-radius:6px;text-decoration:none;font-size:.85rem;}
.btn-logout:hover{background:#ef4444;border-color:#ef4444;color:#fff;}
</style>
</head>

<body>

<nav class="navbar-admin">
  <a class="nav-brand" href="dashboard.php"><img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="nav-logo-img"></a>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="monitoreo.php" class="nav-link-item"><i class="fas fa-video"></i> Monitoreo</a>
    <a href="depositos.php" class="nav-link-item"><i class="fas fa-box-open"></i> Depositos</a>
    <a href="sanciones.php" class="nav-link-item"><i class="fas fa-exclamation-triangle"></i> Sanciones</a>
    <a href="administradores.php" class="nav-link-item"><i class="fas fa-users-cog"></i> Administradores</a>
    <a href="biores.php" class="nav-link-item active"><i class="fas fa-leaf"></i> BIOMASA</a>
    <a href="ahorro_electrico.php" class="nav-link-item"><i class="fas fa-bolt"></i> Ahorro</a>
  </div>
  <div class="nav-right">
    <button class="btn-theme" id="btnTheme" onclick="toggleTheme()" title="Cambiar tema"><i class="fas fa-moon"></i></button>
    <span class="nav-user"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'Admin'); ?></span>
    <a href="../../api/admin/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
  </div>
</nav>

<div class="dashboard-container container">

<h2 class="dashboard-title">Panel Bioenergético </h2>

<!-- CONTROL TOTAL SECTION -->
<div class="control-section">
    <h3 class="control-title">🎛️ Control Total del Sistema</h3>
    
    <div class="control-content">
        <!-- LEFT SIDE: LED Indicators -->
        <div class="control-group">
            <div class="led-label">Estado del Sistema</div>
            <div class="led-indicator off" id="ledIndicator"></div>
            <div class="status-text">
                <span class="status-indicator inactive" id="statusDot"></span>
                <span id="statusText">Detenido</span>
            </div>
        </div>
        
        <!-- RIGHT SIDE: Control Buttons -->
        <div class="control-group">
            <div class="button-group">
                <button class="control-btn btn-start" id="btnStart" onclick="iniciarSistema()" title="Envía comando START al ESP8266 para iniciar el sistema BIORES">
                    ▶ Iniciar Generación
                </button>
                <button class="control-btn btn-stop" id="btnStop" onclick="detenerSistema()" title="Envía comando STOP al ESP8266 para detener el sistema y apagar todos los sensores">
                    ⏹ Detener Sistema
                </button>
            </div>
            <div style="margin-top:12px; text-align:center;">
                <button class="control-btn btn-stop" style="background:#5b21b6;color:#fff" onclick="sendSystemOff()">Apagado Sistema Completo</button>
            </div>
            <div class="alert-info" id="statusMessage">
                Sistema listo para operar - Presione "Iniciar Generación" para enviar señal al ESP8266
            </div>
        </div>
    </div>
</div>

<!-- SENSORES SECTION -->
<div class="sensores-section">
    <h3 class="sensores-title">📡 Estado de Sensores</h3>
    
    <div class="sensores-grid">
        <!-- Sensor de Temperatura -->
        <div class="sensor-card" id="sensorTemp">
            <div class="sensor-name">
                <span>🌡️</span> Temperatura
            </div>
            <div class="bombilla-container">
                <div class="bombilla" id="bombillaTemp"></div>
            </div>
            <div class="sensor-status apagado" id="statusTemp">Apagado</div>
            <div class="sensor-valor" id="valorTemp">N/A</div>
            <div class="sensor-timestamp" id="timestampTemp">Esperando señal...</div>
            <button class="sensor-emergency-btn" onclick="apagarEmergencia('temperatura', this)" title="Apago de emergencia del sensor de temperatura">
                ⚠️ Apagado Emergencia
            </button>
        </div>
        
        <!-- Sensor de Ventilador -->
        <div class="sensor-card" id="sensorVent">
            <div class="sensor-name">
                <span>❄️</span> Ventilador
            </div>
            <div class="bombilla-container">
                <div class="bombilla" id="bombillaVent"></div>
            </div>
            <div class="sensor-status apagado" id="statusVent">Apagado</div>
            <div class="sensor-valor" id="valorVent">N/A</div>
            <div class="sensor-timestamp" id="timestampVent">Esperando señal...</div>
            <button class="sensor-emergency-btn" onclick="apagarEmergencia('ventilador', this)" title="Apago de emergencia del ventilador">
                ⚠️ Apagado Emergencia
            </button>
        </div>
        
        <!-- Sensor de Corriente -->
        <div class="sensor-card" id="sensorCor">
            <div class="sensor-name">
                <span>⚡</span> Corriente
            </div>
            <div class="bombilla-container">
                <div class="bombilla" id="bombillaCor"></div>
            </div>
            <div class="sensor-status apagado" id="statusCor">Apagado</div>
            <div class="sensor-valor" id="valorCor">N/A</div>
            <div class="sensor-timestamp" id="timestampCor">Esperando señal...</div>
            <button class="sensor-emergency-btn" onclick="apagarEmergencia('corriente', this)" title="Apago de emergencia del sensor de corriente">
                ⚠️ Apagado Emergencia
            </button>
        </div>
    </div>
    
    <div class="sensores-info">
        ℹ️ Los datos se actualizan automáticamente cuando el ESP8266 envía información. 
        Los sensores estarán <strong>apagados</strong> hasta que el sistema inicie.
    </div>
</div>

<div class="row g-4">

<div class="col-md-6">
<div class="stat-card">
<div class="stat-value temp"><?= $tempActual ?> °C</div>
<div class="stat-label">Temperatura de Operación</div>
</div>
</div>

<div class="col-md-6">
<div class="stat-card">
<div class="stat-value energy"><?= $energiaActual ?> kWh</div>
<div class="stat-label">Energía Generada</div>
</div>
</div>

</div>

<!-- FILTROS DE FECHA -->
<div class="chart-box">
    <div class="date-filters">
        <h5 style="text-align:center; margin-bottom:20px; color:#1f4037;">📅 Seleccione Período para Ver Gráficas</h5>
        <div class="date-filters-row">
            <div class="filter-group">
                <label>Período:</label>
                <select id="periodoSelect" onchange="actualizarRangoFechas()">
                    <option value="dia" selected>Día</option>
                    <option value="mes">Mes</option>
                    <option value="anual">Año</option>
                </select>
            </div>
            <div class="filter-group" id="fechaDiaGroup">
                <label>Seleccione una fecha:</label>
                <input type="date" id="fechaDia">
            </div>
            <div class="filter-group" id="fechaMesGroup" style="display:none;">
                <label>Rango de fechas:</label>
                <input type="text" id="fechaMes" placeholder="YYYY-MM-DD - YYYY-MM-DD">
            </div>
            <div class="filter-group" id="fechaAnualGroup" style="display:none;">
                <label>Año:</label>
                <input type="number" id="fechaAnual" min="2020" max="2100">
            </div>
        </div>
        <div style="text-align:center; margin-top:15px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
            <button class="btn-filter" onclick="aplicarFiltros()">📊 Cargar Gráficas</button>
            <button class="btn-filter" style="background:linear-gradient(135deg, #667eea, #764ba2);" onclick="descargarDatos()">💾 Descargar Datos</button>
        </div>
        <div id="loadingIndicator" style="text-align:center; display:none; margin-top:10px; color:#1f4037; font-weight:bold;">
            ⏳ Cargando datos...
        </div>
    </div>
</div>

<div class="chart-box">
<h5 class="mb-3">Histórico de Temperatura</h5>
<div id="mensajeGraficas" style="text-align:center; padding:40px; color:#999; font-size:1.1rem;">
    📊 Seleccione un período y presione "Cargar Gráficas" para visualizar los datos
</div>
<canvas id="chartTemp"></canvas>
</div>

<div class="chart-box">
<h5 class="mb-3">Histórico de Energía</h5>
<canvas id="chartEnergia"></canvas>
</div>

<!-- NUEVO GRÁFICO DE GANANCIAS -->
<div class="chart-box">
<h5 class="mb-3">💰 Análisis de Ganancias del Sistema</h5>
<canvas id="chartGanancias"></canvas>
<div class="ganancias-summary" id="gananciasSummary">
    <div class="ganancia-item generado">
        <div class="valor" id="totalGenerado">0.00</div>
        <div class="label">kWh Generados</div>
    </div>
    <div class="ganancia-item consumido">
        <div class="valor" id="totalConsumido">0.00</div>
        <div class="label">kWh Consumidos</div>
    </div>
    <div class="ganancia-item positivo" id="gananciaNeta">
        <div class="valor" id="netaValor">0.00</div>
        <div class="label">kWh Netos</div>
    </div>
    <div class="ganancia-item positivo" id="gananciaMonetaria">
        <div class="valor" id="monetariaValor">RD$0.00</div>
        <div class="label">Ganancia Aproximada (DOP)</div>
    </div>
</div>
</div>

<div class="footer">
Sistema de monitoreo en tiempo real
</div>

</div>

<script>

const labels = [];
const tempData = [];
const energiaData = [];
const consumoData = [];
const MAX_POINTS = 40;
const PRECIO_KWH_DOP = 65.00; // Precio aproximado por kWh en pesos dominicanos
let maxTempHistorica = 0;
let ultimoTotalEnergiaWh = 0;
let lastSystemOffFlag = null;
let fechaDiaPicker, fechaMesPicker, fechaAnualPicker;
let chartTemp, chartEnergia, chartGanancias;

// ===== GRÁFICOS INICIALES VACÍOS =====
chartTemp = new Chart(document.getElementById('chartTemp'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Temperatura (°C)',
            data: [],
            borderColor: '#ff4b5c',
            backgroundColor: 'rgba(255,75,92,0.2)',
            fill: true,
            tension: 0.4,
            pointRadius: 4
        }]
    },
    options:{
        plugins:{legend:{display:true}},
        responsive:true
    }
});

chartEnergia = new Chart(document.getElementById('chartEnergia'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Energía (kWh)',
            data: [],
            borderColor: '#1d84b5',
            backgroundColor: 'rgba(29,132,181,0.2)',
            fill: true,
            tension: 0.4,
            pointRadius: 4
        }]
    },
    options:{
        plugins:{legend:{display:false}},
        responsive:true
    }
});

chartGanancias = new Chart(document.getElementById('chartGanancias'), {
    type: 'bar',
    data: {
        labels: [],
        datasets: [
            {
                label: 'Energía Generada (kWh)',
                data: [],
                backgroundColor: 'rgba(29,132,181,0.7)',
                borderColor: '#1d84b5',
                borderWidth: 2
            },
            {
                label: 'Energía Consumida (kWh)',
                data: [],
                backgroundColor: 'rgba(255,152,0,0.7)',
                borderColor: '#ff9800',
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function actualizarResumenGanancias(resumen = null) {
    if (!resumen) {
        // Si no hay datos, mostrar ceros
        document.getElementById('totalGenerado').textContent = '0.000';
        document.getElementById('totalConsumido').textContent = '0.000';
        document.getElementById('netaValor').textContent = '0.000';
        document.getElementById('monetariaValor').textContent = 'RD$0.00';
        return;
    }
    
    const totalGen = resumen.totalEnergia || 0;
    const totalCons = resumen.totalConsumo || 0;
    const neto = resumen.totalNeto || 0;
    const gananciaMonetaria = resumen.gananciaMonetaria || 0;
    
    document.getElementById('totalGenerado').textContent = totalGen.toFixed(3);
    document.getElementById('totalConsumido').textContent = totalCons.toFixed(3);
    document.getElementById('netaValor').textContent = neto.toFixed(3);
    document.getElementById('monetariaValor').textContent = 'RD$' + gananciaMonetaria.toFixed(2);
    
    const gananciaNetaDiv = document.getElementById('gananciaNeta');
    const gananciaMonetariaDiv = document.getElementById('gananciaMonetaria');
    
    if (neto >= 0) {
        gananciaNetaDiv.className = 'ganancia-item positivo';
        gananciaMonetariaDiv.className = 'ganancia-item positivo';
    } else {
        gananciaNetaDiv.className = 'ganancia-item negativo';
        gananciaMonetariaDiv.className = 'ganancia-item negativo';
    }
}

// ===== CONTROL DEL SISTEMA =====
let estadoSistema = false;

function setStatusMessage(msg, mode = 'info') {
    const statusMessage = document.getElementById('statusMessage');
    if (!statusMessage) return;

    statusMessage.innerHTML = msg;
    if (mode === 'ok') {
        statusMessage.style.background = '#d4edda';
        statusMessage.style.color = '#155724';
        statusMessage.style.borderColor = '#c3e6cb';
    } else if (mode === 'warn') {
        statusMessage.style.background = '#fff3cd';
        statusMessage.style.color = '#856404';
        statusMessage.style.borderColor = '#ffeeba';
    } else if (mode === 'error') {
        statusMessage.style.background = '#f8d7da';
        statusMessage.style.color = '#721c24';
        statusMessage.style.borderColor = '#f5c6cb';
    } else {
        statusMessage.style.background = '#e7f3ff';
        statusMessage.style.color = '#004085';
        statusMessage.style.borderColor = '#b8daff';
    }
}

function pushHistorico(tempVal, energiaWh, ts) {
    // Solo actualizar si los gráficos están inicializados
    if (!chartTemp || !chartEnergia) return;
    
    const nowLabel = ts ? new Date(ts).toLocaleTimeString() : new Date().toLocaleTimeString();
    const safeTemp = Number.isFinite(tempVal) ? tempVal : 0;
    const energiaKwh = (Number.isFinite(energiaWh) ? energiaWh : 0) / 1000;

    maxTempHistorica = Math.max(maxTempHistorica, safeTemp);

    chartTemp.data.labels.push(nowLabel);
    chartTemp.data.datasets[0].data.push(safeTemp);

    chartEnergia.data.labels.push(nowLabel);
    chartEnergia.data.datasets[0].data.push(energiaKwh.toFixed(4));

    if (chartTemp.data.labels.length > MAX_POINTS) {
        chartTemp.data.labels.shift();
        chartTemp.data.datasets[0].data.shift();
    }
    if (chartEnergia.data.labels.length > MAX_POINTS) {
        chartEnergia.data.labels.shift();
        chartEnergia.data.datasets[0].data.shift();
    }

    chartTemp.update('none');
    chartEnergia.update('none');
}

function actualizarInterfaz() {
    const ledIndicator = document.getElementById('ledIndicator');
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');
    const btnStart = document.getElementById('btnStart');
    const btnStop = document.getElementById('btnStop');
    
    if (estadoSistema) {
        // ENCENDIDO
        ledIndicator.classList.remove('off');
        ledIndicator.classList.add('on');
        statusDot.classList.remove('inactive');
        statusDot.classList.add('active');
        statusText.textContent = 'En Operación';
        btnStart.disabled = true;
        btnStop.disabled = false;
    } else {
        // APAGADO
        ledIndicator.classList.remove('on');
        ledIndicator.classList.add('off');
        statusDot.classList.remove('active');
        statusDot.classList.add('inactive');
        statusText.textContent = 'Detenido';
        btnStart.disabled = false;
        btnStop.disabled = true;
    }
}

function iniciarSistema() {
    const btnStart = document.getElementById('btnStart');
    const statusMessage = document.getElementById('statusMessage');
    
    // Mostrar estado de carga
    btnStart.disabled = true;
    setStatusMessage('<span class="loading"></span> 📡 Iniciando sistema BIOMASA...', 'info');
    
    console.log('📡 Enviando comando INICIO al Arduino...');
    
    fetch('/PRERMI/BIOMASA/control_biomasa.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'accion=START'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            // Sistema iniciado exitosamente
            estadoSistema = true;
            console.log('✅ Comando INICIO encolado:', data);
            
            // Mostrar que el sistema está operando
            setStatusMessage('✅ Sistema iniciado correctamente', 'ok');
            
            // Actualizar interfaz inmediatamente
            actualizarInterfaz();
            
            // Recargar estado de sensores después de 3 segundos
            setTimeout(() => {
                cargarEstadoSensores();
            }, 3500);
        } else {
            console.error('❌ Error del servidor:', data.msg);
            setStatusMessage('⚠️ Error al iniciar el sistema', 'error');
            btnStart.disabled = false;
        }
    })
    .catch(error => {
        console.error('❌ Error de conexión:', error);
        setStatusMessage('❌ Error de conexión con el dispositivo', 'error');
        btnStart.disabled = false;
    });
}

function sendSystemOff(){
    fetch('/PRERMI/BIOMASA/control_biomasa.php', {
        method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'accion=SYSTEM_OFF'
    }).then(r=>r.json()).then(j=>{
        if (j.status==='ok') {
            estadoSistema = false;
            actualizarInterfaz();
            setStatusMessage('🛑 Sistema apagado completamente', 'warn');
        } else {
            setStatusMessage('❌ Error al apagar el sistema', 'error');
        }
    }).catch(e=>setStatusMessage('❌ Error de conexión', 'error'));
}

function detenerSistema() {
    const btnStop = document.getElementById('btnStop');
    const statusMessage = document.getElementById('statusMessage');
    
    // Mostrar estado de carga
    btnStop.disabled = true;
    setStatusMessage('<span class="loading"></span> 🛑 Deteniendo sistema BIOMASA...', 'warn');
    
    console.log('🛑 Enviando comando PARADA al Arduino...');
    
    fetch('/PRERMI/BIOMASA/control_biomasa.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'accion=STOP'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            // Sistema detenido exitosamente
            estadoSistema = false;
            console.log('✅ Comando PARADA encolado:', data);
            
            setStatusMessage('✅ Sistema detenido correctamente', 'ok');
            
            actualizarInterfaz();
            
            // Recargar estado después de 3 segundos
            setTimeout(() => {
                cargarEstadoSensores();
            }, 3500);
        } else {
            console.error('❌ Error del servidor:', data.msg);
            setStatusMessage('⚠️ Error al detener el sistema', 'error');
            btnStop.disabled = false;
        }
    })
    .catch(error => {
        console.error('❌ Error de conexión:', error);
        setStatusMessage('❌ Error de conexión con el dispositivo', 'error');
        btnStop.disabled = false;
    });
}

// Inicializar interfaz al cargar
window.addEventListener('DOMContentLoaded', () => {
    // Establecer fecha actual como valor por defecto
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaDia').value = hoy;
    document.getElementById('fechaAnual').value = new Date().getFullYear();
    
    // Establecer rango del mes actual para el filtro de mes
    const primerDia = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    document.getElementById('fechaMes').value = primerDia + ' - ' + hoy;
    
    actualizarInterfaz();
    cargarEstadoSensores();
    
    // Actualizar sensores cada 3 segundos
    setInterval(cargarEstadoSensores, 3000);
});

// ===== CONTROL DE SENSORES =====
function cargarEstadoSensores() {
    fetch('/PRERMI/BIOMASA/sensores_estado.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                const sensores = data.data;
                
                // Actualizar indicador LED según estado del sistema
                const ledIndicator = document.getElementById('ledIndicator');
                const statusDot = document.getElementById('statusDot');
                const statusText = document.getElementById('statusText');
                
                if (sensores.sistema_activo) {
                    ledIndicator.classList.remove('off');
                    ledIndicator.classList.add('on');
                    statusDot.classList.remove('inactive');
                    statusDot.classList.add('active');
                    statusText.textContent = 'En Operación';
                    estadoSistema = true;
                } else {
                    ledIndicator.classList.remove('on');
                    ledIndicator.classList.add('off');
                    statusDot.classList.remove('active');
                    statusDot.classList.add('inactive');
                    statusText.textContent = 'Detenido';
                    estadoSistema = false;
                }
                
                actualizarInterfaz();
                
                // Actualizar sensores individuales
                actualizarSensor('Temperatura', sensores.temperatura, 'Temp', '°C');
                actualizarSensor('Ventilador', sensores.ventilador, 'Vent', '%');
                actualizarSensor('Corriente', sensores.corriente, 'Cor', 'A');
                
                // Actualizar energía generada en tiempo real
                const energiaWh = parseFloat(sensores.energia_generada || 0);
                ultimoTotalEnergiaWh = Number.isFinite(energiaWh) ? energiaWh : 0;
                actualizarEnergiaGenerada(ultimoTotalEnergiaWh);
                
                // Actualizar temperatura en card de estadísticas
                const tempValor = parseFloat((sensores.temperatura && sensores.temperatura.valor) || 0);
                actualizarTemperaturaCard(tempValor);

                const ts = sensores.temperatura ? sensores.temperatura.timestamp : null;
                pushHistorico(tempValor, ultimoTotalEnergiaWh, ts);

                const control = sensores.control || {};
                const systemOffActivo = !!control.system_off;
                if (lastSystemOffFlag !== systemOffActivo) {
                    lastSystemOffFlag = systemOffActivo;
                    if (systemOffActivo) {
                        setStatusMessage('⚠️ Sistema en modo de seguridad', 'warn');
                    }
                }
            }
        })
        .catch(error => console.error('Error cargando sensores:', error));
}

function actualizarEnergiaGenerada(energia) {
    const energiaElement = document.querySelector('.energy');
    if (energiaElement) {
        // Convertir Wh a kWh (dividir por 1000)
        const energiaKwh = (energia / 1000).toFixed(4);
        energiaElement.textContent = energiaKwh + ' kWh';
    }
}

function actualizarTemperaturaCard(temperatura) {
    const tempElement = document.querySelector('.temp');
    if (tempElement) {
        const tempValue = Number.isFinite(temperatura) ? temperatura.toFixed(2) : '0.00';
        tempElement.textContent = tempValue + ' °C';
    }
}

function actualizarSensor(nombre, sensor, id, unidad) {
    const estado = sensor.estado || 'apagado';
    const valor = sensor.valor || 'N/A';
    const timestamp = sensor.timestamp || 'Esperando señal...';
    
    // Actualizar card
    const card = document.getElementById('sensor' + id);
    const bombilla = document.getElementById('bombilla' + id);
    const statusEl = document.getElementById('status' + id);
    const valorEl = document.getElementById('valor' + id);
    const timestampEl = document.getElementById('timestamp' + id);
    
    // Limpiar clases
    card.classList.remove('activo', 'sensando');
    bombilla.classList.remove('activa', 'sensando');
    statusEl.classList.remove('activo', 'sensando', 'apagado');
    
    // Actualizar según estado
    if (estado === 'apagado') {
        statusEl.textContent = 'Apagado';
        statusEl.classList.add('apagado');
        valorEl.textContent = 'N/A';
    } else if (estado === 'activo') {
        card.classList.add('activo');
        bombilla.classList.add('activa');
        
        // Para ventilador: mostrar "Automático" si está activo por temperatura > 40°C
        if (id === 'Vent') {
            statusEl.textContent = 'Automático';
        } else {
            statusEl.textContent = 'Activo';
        }
        
        statusEl.classList.add('activo');
        
        // Formato especial para valores
        let valorFormato = 'N/A';
        if (valor !== 'N/A') {
            if (id === 'Vent') {
                // Para ventilador: mostrar que está activo (no necesita valor numérico)
                valorFormato = '✓ En marcha';
            } else {
                valorFormato = valor + ' ' + unidad;
            }
        }
        valorEl.textContent = valorFormato;
    } else if (estado === 'sensando') {
        card.classList.add('sensando');
        bombilla.classList.add('sensando');
        statusEl.textContent = 'Sensando';
        statusEl.classList.add('sensando');
        const valorFormato = valor === 'N/A' ? 'N/A' : valor + ' ' + unidad;
        valorEl.textContent = valorFormato;
    }
    
    // Actualizar timestamp
    if (timestamp && timestamp !== 'Esperando señal...') {
        timestampEl.textContent = 'Última actualización: ' + new Date(timestamp).toLocaleTimeString();
    } else {
        timestampEl.textContent = 'Esperando señal...';
    }
}

// ===== APAGADO DE EMERGENCIA =====
function apagarEmergencia(sensor, btn) {
    // Confirmación de apagado de emergencia
    if (!confirm('⚠️ APAGADO DE EMERGENCIA\n\nEstá a punto de detener el sensor de ' + sensor.toUpperCase() + '.\n\n¿Está seguro?')) {
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '⏳ Apagando...';
    
    let accion = '';
    switch(sensor.toLowerCase()) {
        case 'temperatura':
            accion = 'TEMP_OFF';
            break;
        case 'ventilador':
            accion = 'VENTILADOR_OFF';
            break;
        case 'corriente':
            accion = 'CORRIENTE_OFF';
            break;
        default:
            btn.disabled = false;
            btn.innerHTML = '⚠️ Apagado Emergencia';
            return;
    }
    
    fetch('/PRERMI/BIOMASA/control_biomasa.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'accion=' + encodeURIComponent(accion)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            setStatusMessage('⚠️ Sensor detenido por emergencia', 'warn');
        } else {
            setStatusMessage('⚠️ Error en apagado de emergencia', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '⚠️ Apagado Emergencia';
        cargarEstadoSensores(); // Refrescar estado
    })
    .catch(error => {
        console.error('Error:', error);
        setStatusMessage('❌ Error de conexión', 'error');
        btn.disabled = false;
        btn.innerHTML = '⚠️ Apagado Emergencia';
    });
}

// ===== FILTROS DE FECHA =====
function actualizarRangoFechas() {
    const periodo = document.getElementById('periodoSelect').value;
    
    // Ocultar todos los grupos
    document.getElementById('fechaDiaGroup').style.display = 'none';
    document.getElementById('fechaMesGroup').style.display = 'none';
    document.getElementById('fechaAnualGroup').style.display = 'none';
    
    // Mostrar el grupo correspondiente
    if (periodo === 'dia') {
        document.getElementById('fechaDiaGroup').style.display = 'block';
    } else if (periodo === 'mes') {
        document.getElementById('fechaMesGroup').style.display = 'block';
    } else if (periodo === 'anual') {
        document.getElementById('fechaAnualGroup').style.display = 'block';
    }
}

function aplicarFiltros() {
    const periodo = document.getElementById('periodoSelect').value;
    let fechaInicio = null;
    let fechaFin = null;
    
    if (periodo === 'dia') {
        const fecha = document.getElementById('fechaDia').value;
        if (!fecha) {
            alert('Por favor seleccione una fecha');
            return;
        }
        fechaInicio = fecha;
        fechaFin = fecha;
    } else if (periodo === 'mes') {
        const fechas = document.getElementById('fechaMes').value;
        if (!fechas) {
            alert('Por favor seleccione un rango de fechas');
            return;
        }
        const partes = fechas.split(' - ');
        if (partes.length !== 2) {
            alert('Por favor seleccione un rango válido');
            return;
        }
        fechaInicio = partes[0];
        fechaFin = partes[1];
    } else if (periodo === 'anual') {
        const fecha = document.getElementById('fechaAnual').value;
        if (!fecha || isNaN(fecha)) {
            alert('Por favor seleccione un año válido');
            return;
        }
        fechaInicio = fecha + '-01-01';
        fechaFin = fecha + '-12-31';
    }
    
    cargarDatosGraficas(periodo, fechaInicio, fechaFin);
}

function cargarDatosGraficas(periodo, fechaInicio, fechaFin) {
    const loading = document.getElementById('loadingIndicator');
    loading.style.display = 'block';
    
    const url = `/PRERMI/BIOMASA/obtener_datos_graficas.php?periodo=${periodo}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
    
    console.log('🔄 Cargando datos desde:', url);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            console.log('📦 Respuesta recibida:', data);
            
            if (data.status === 'ok') {
                if (data.resumen && data.resumen.registros === 0) {
                    alert('⚠️ No hay datos disponibles para el período seleccionado.\n\nPor favor, seleccione otra fecha o verifique que existan mediciones en la base de datos.');
                    return;
                }
                
                // Actualizar gráficos con los nuevos datos
                actualizarGraficas(data.datos, data.resumen);
                
                // Mostrar notificación de éxito
                const registros = data.resumen.registros || 0;
                console.log(`✅ ${registros} registros cargados correctamente`);
                
            } else {
                alert('❌ Error al cargar datos:\n' + data.msg);
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            console.error('❌ Error:', error);
            alert('❌ Error de conexión al cargar datos:\n' + error.message + '\n\nVerifique que el servidor esté funcionando.');
        });
}

function actualizarGraficas(datos, resumen) {
    // Ocultar mensaje de instrucciones si existe
    const mensaje = document.getElementById('mensajeGraficas');
    if (mensaje) {
        mensaje.style.display = 'none';
    }
    
    // Actualizar gráfico de temperatura
    chartTemp.data.labels = datos.labels;
    chartTemp.data.datasets[0].data = datos.tempData;
    chartTemp.update();
    
    // Actualizar gráfico de energía
    chartEnergia.data.labels = datos.labels;
    chartEnergia.data.datasets[0].data = datos.energiaData;
    chartEnergia.update();
    
    // Actualizar gráfico de ganancias
    chartGanancias.data.labels = datos.labels;
    chartGanancias.data.datasets[0].data = datos.energiaData;
    chartGanancias.data.datasets[1].data = datos.consumoData;
    chartGanancias.update();
    
    // Actualizar resumen
    actualizarResumenGanancias(resumen);
    
    // Mostrar notificación de éxito
    console.log('✅ Gráficas actualizadas:', {
        registros: datos.labels.length,
        periodo: resumen
    });
}

function descargarDatos() {
    const periodo = document.getElementById('periodoSelect').value;
    let fechaInicio = null;
    let fechaFin = null;
    
    if (periodo === 'dia') {
        const fecha = document.getElementById('fechaDia').value;
        if (!fecha) {
            alert('Por favor seleccione una fecha');
            return;
        }
        fechaInicio = fecha;
        fechaFin = fecha;
    } else if (periodo === 'mes') {
        const fechas = document.getElementById('fechaMes').value;
        if (!fechas) {
            alert('Por favor seleccione un rango de fechas');
            return;
        }
        const partes = fechas.split(' - ');
        if (partes.length !== 2) {
            alert('Por favor seleccione un rango válido');
            return;
        }
        fechaInicio = partes[0];
        fechaFin = partes[1];
    } else if (periodo === 'anual') {
        const fecha = document.getElementById('fechaAnual').value;
        if (!fecha || isNaN(fecha)) {
            alert('Por favor seleccione un año válido');
            return;
        }
        fechaInicio = fecha + '-01-01';
        fechaFin = fecha + '-12-31';
    }
    
    // Hacer solicitud para guardar los datos localmente
    const url = `/PRERMI/BIOMASA/obtener_datos_graficas.php?periodo=${periodo}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&guardar_local=1`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                alert('✅ Datos guardados correctamente en la carpeta de reportes (BIOMASA/reportes/)');
            } else {
                alert('Error: ' + data.msg);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al descargar: ' + error.message);
        });
}



</script>
<script src="/PRERMI/web/assets/js/theme.js"></script>
</body>
</html>