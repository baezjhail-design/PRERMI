<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: loginA.php");
    exit;
}

/* Usar configuraci�n centralizada */
require_once __DIR__ . '/../../config/db_config.php';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexi�n: " . $e->getMessage());
}

// Solo intentar conexi�n remota si estamos en InfinityFree
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

/* �LTIMO REGISTRO */
try {
    $stmt = $pdo->query("SELECT temperatura, energia FROM mediciones_biomasa ORDER BY fecha DESC LIMIT 1");
    $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ultimo = ['temperatura' => 0, 'energia' => 0];
}

$tempActual = $ultimo['temperatura'] ?? 0;
$energiaActual = $ultimo['energia'] ?? 0;

/* INICIALIZAR VARIABLES PARA JAVASCRIPT */
// Se cargar�n via AJAX cuando el usuario seleccione las fechas
$labels = [];
$tempData = [];
$energiaData = [];
$consumoData = [];

// Fecha por defecto para los pickers
$fechaHoy = date('Y-m-d');
$fechaPrimerDia = date('Y-m-01');
$fechaAnioActual = date('Y');

// === DEPOSITOS ORGANICOS: calculo de energia proyectada ===
// Formula: kWh = peso_kg * FACTOR * min(horas_transcurridas / TIEMPO_COMPLETO, 1)
$FACTOR_KWH_KG = 0.08;  // Rendimiento termoelectrico Peltier ~8%
$TIEMPO_GEN_H  = 72;    // Horas para generacion completa por lote
try {
    $stDep = $pdo->prepare("
        SELECT d.id, COALESCE(d.peso, 0) AS peso, d.tipo_residuo,
               COALESCE(d.creado_en, d.fecha_hora) AS ts,
               u.usuario,
               TIMESTAMPDIFF(HOUR, COALESCE(d.creado_en, d.fecha_hora), NOW()) AS horas
        FROM depositos d
        LEFT JOIN usuarios u ON u.id = d.id_usuario
        WHERE (d.tipo_residuo IS NULL OR d.tipo_residuo NOT LIKE '%metal%')
          AND COALESCE(d.creado_en, d.fecha_hora) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY COALESCE(d.creado_en, d.fecha_hora) DESC
        LIMIT 20
    ");
    $stDep->execute();
    $depositosRecientes = $stDep->fetchAll(PDO::FETCH_ASSOC);

    $pesoTotalOrg  = 0;
    $kwhProyectado = 0;
    $deposConCalc  = [];
    foreach ($depositosRecientes as $dep) {
        $kg      = floatval($dep['peso']);
        $h       = intval($dep['horas']);
        $prog    = min($h / $TIEMPO_GEN_H, 1.0);
        $kwh     = round($kg * $FACTOR_KWH_KG * $prog, 4);
        $dep['kwh_est']  = $kwh;
        $dep['prog_pct'] = round($prog * 100, 1);
        $deposConCalc[]  = $dep;
        $pesoTotalOrg   += $kg;
        $kwhProyectado  += $kwh;
    }

    $resDepTotales = $pdo->query("
        SELECT COUNT(*) AS total,
               COALESCE(SUM(COALESCE(peso,0)), 0) AS peso_total,
               COALESCE(SUM(COALESCE(credito_kwh,0)), 0) AS kwh_credito
        FROM depositos
        WHERE (tipo_residuo IS NULL OR tipo_residuo NOT LIKE '%metal%')
          AND COALESCE(creado_en, fecha_hora) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $deposConCalc  = [];
    $resDepTotales = ['total'=>0,'peso_total'=>0,'kwh_credito'=>0];
    $kwhProyectado = 0;
    $pesoTotalOrg  = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Bioenergetico</title>
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

/* GR�FICO DE GANANCIAS */
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

/* DEPOSITOS ORGANICOS */
.depositos-section{background:rgba(255,255,255,.95);border-radius:20px;padding:1.4rem 1.5rem;margin-bottom:1.5rem;border:1px solid rgba(31,64,55,.12);box-shadow:0 12px 28px rgba(15,23,42,.12);}
.depositos-section h3{color:#1f4037;font-size:1.08rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;}
.dep-stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.9rem;margin-bottom:1rem;}
.dep-stat{background:#f7faf9;border-radius:12px;padding:.9rem;text-align:center;color:#1f2937;border:1px solid #d1e6de;}
.dep-stat .dep-ic{font-size:1.3rem;margin-bottom:.3rem;opacity:.95;color:#2563eb;}
.dep-stat .dep-val{font-size:1.3rem;font-weight:700;line-height:1.2;color:#111827;}
.dep-stat .dep-lbl{font-size:.75rem;color:#475569;margin-top:.2rem;opacity:1;}
.formula-info{background:#f8fafc;border:1px solid #dbe6ec;border-radius:8px;padding:.6rem .9rem;font-size:.8rem;color:#334155;margin-bottom:.9rem;}
.dep-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.dep-table th{background:#eef3f7;color:#0f172a;padding:.45rem .7rem;text-align:left;font-weight:700;white-space:nowrap;}
.dep-table td{padding:.45rem .7rem;color:#334155;border-bottom:1px solid #e5edf3;}
.dep-table tr:hover td{background:#f8fbfd;}
.prog-wrap{background:#e2e8f0;border-radius:10px;height:14px;position:relative;min-width:70px;}
.prog-fill{background:linear-gradient(90deg,#10b981,#34d399);border-radius:10px;height:100%;}
.prog-lbl{position:absolute;right:4px;top:0;font-size:.68rem;line-height:14px;color:#fff;font-weight:700;}
.kwh-cell{color:#34d399;font-weight:700;}
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

<h2 class="dashboard-title">Panel Bioenergetico</h2>

<!-- DEPOSITOS ORGANICOS SECTION -->
<div class="depositos-section">
    <h3><i class="fas fa-seedling"></i> Depositos organicos activos (ultimos 7 dias)</h3>
    <div class="dep-stats-row">
        <div class="dep-stat">
            <div class="dep-ic"><i class="fas fa-box-open"></i></div>
            <div class="dep-val"><?= intval($resDepTotales['total']) ?></div>
            <div class="dep-lbl">Depositos registrados</div>
        </div>
        <div class="dep-stat">
            <div class="dep-ic"><i class="fas fa-weight-hanging"></i></div>
            <div class="dep-val"><?= round(floatval($resDepTotales['peso_total']), 2) ?> kg</div>
            <div class="dep-lbl">Peso organico total</div>
        </div>
        <div class="dep-stat" style="background:rgba(52,211,153,.25);">
            <div class="dep-ic"><i class="fas fa-bolt"></i></div>
            <div class="dep-val"><?= round($kwhProyectado, 4) ?> kWh</div>
            <div class="dep-lbl">Energia proyectada activa</div>
        </div>
        <div class="dep-stat">
            <div class="dep-ic"><i class="fas fa-leaf"></i></div>
            <div class="dep-val"><?= round(floatval($resDepTotales['kwh_credito']), 4) ?> kWh</div>
            <div class="dep-lbl">Credito kWh acumulado</div>
        </div>
    </div>
    <div class="formula-info">
        <i class="fas fa-flask"></i>
        <strong>Formula Peltier:</strong>&nbsp;
        kWh = peso(kg) &times; 0.08 &times; min(horas_transcurridas&nbsp;/&nbsp;72,&nbsp;1)
        &nbsp;&mdash;&nbsp;
        <span style="opacity:.72">Rendimiento termoelectrico ~8% &bull; Ciclo completo: 72 h</span>
    </div>
    <?php if (!empty($deposConCalc)): ?>
    <div style="overflow-x:auto;">
    <table class="dep-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Usuario</th>
                <th>Tipo residuo</th>
                <th>Peso (kg)</th>
                <th>Tiempo proceso</th>
                <th>Progreso generacion</th>
                <th>kWh estimado</th>
                <th>Fecha deposito</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($deposConCalc as $dep): ?>
            <tr>
                <td>#<?= intval($dep['id']) ?></td>
                <td><?= htmlspecialchars($dep['usuario'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($dep['tipo_residuo'] ?? 'No especificado') ?></td>
                <td><?= round(floatval($dep['peso']), 2) ?></td>
                <td><?= intval($dep['horas']) ?> h</td>
                <td>
                    <div class="prog-wrap">
                        <div class="prog-fill" style="width:<?= $dep['prog_pct'] ?>%"></div>
                        <span class="prog-lbl"><?= $dep['prog_pct'] ?>%</span>
                    </div>
                </td>
                <td class="kwh-cell"><?= $dep['kwh_est'] ?></td>
                <td><?= htmlspecialchars(substr($dep['ts'], 0, 16)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:22px;color:#64748b;">
        <i class="fas fa-inbox" style="font-size:1.7rem;display:block;margin-bottom:.4rem;"></i>
        No hay depositos organicos en los ultimos 7 dias
    </div>
    <?php endif; ?>
</div>

<!-- CONTROL TOTAL SECTION -->
<div class="control-section">
    <h3 class="control-title"><i class="fas fa-sliders-h"></i> Control total del sistema</h3>
    
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
                <button class="control-btn btn-start" id="btnStart" onclick="iniciarSistema()" title="Envia comando START al ESP8266 para iniciar el sistema BIORES">
                    <i class="fas fa-play"></i> Iniciar generacion
                </button>
                <button class="control-btn btn-stop" id="btnStop" onclick="detenerSistema()" title="Envia comando STOP al ESP8266 para detener el sistema y apagar todos los sensores">
                    <i class="fas fa-stop"></i> Detener sistema
                </button>
            </div>
            <div style="margin-top:12px; text-align:center;">
                <button class="control-btn btn-stop" style="background:#b91c1c;color:#fff;margin-right:8px" onclick="paradaEmergenciaTotal()"><i class="fas fa-triangle-exclamation"></i> Emergencia total</button>
                <button class="control-btn btn-stop" style="background:#5b21b6;color:#fff" onclick="sendSystemOff()">Apagado Sistema Completo</button>
            </div>
            <div class="alert-info" id="statusMessage">
                Sistema listo para operar - Presione "Iniciar generacion" para enviar senal al ESP8266
            </div>
        </div>
    </div>
</div>

<!-- SENSORES SECTION -->
<div class="sensores-section">
    <h3 class="sensores-title"><i class="fas fa-microchip"></i> Estado de sensores</h3>
    
    <div class="sensores-grid">
        <!-- Sensor de Temperatura -->
        <div class="sensor-card" id="sensorTemp">
            <div class="sensor-name">
                <i class="fas fa-temperature-high"></i> Temperatura
            </div>
            <div class="bombilla-container">
                <div class="bombilla" id="bombillaTemp"></div>
            </div>
            <div class="sensor-status apagado" id="statusTemp">Apagado</div>
            <div class="sensor-valor" id="valorTemp">N/A</div>
            <div class="sensor-timestamp" id="timestampTemp">Esperando senal...</div>
            <button class="sensor-emergency-btn" onclick="apagarEmergencia('temperatura', this)" title="Apago de emergencia del sensor de temperatura">
                <i class="fas fa-power-off"></i> Apagado de emergencia
            </button>
        </div>
        
        <!-- Sensor de Ventilador -->
        <div class="sensor-card" id="sensorVent">
            <div class="sensor-name">
                <i class="fas fa-fan"></i> Ventilador
            </div>
            <div class="bombilla-container">
                <div class="bombilla" id="bombillaVent"></div>
            </div>
            <div class="sensor-status apagado" id="statusVent">Apagado</div>
            <div class="sensor-valor" id="valorVent">N/A</div>
            <div class="sensor-timestamp" id="timestampVent">Esperando senal...</div>
            <button class="sensor-emergency-btn" onclick="apagarEmergencia('ventilador', this)" title="Apago de emergencia del ventilador">
                <i class="fas fa-power-off"></i> Apagado de emergencia
            </button>
        </div>
        
        <!-- Sensor de Corriente -->
        <div class="sensor-card" id="sensorCor">
            <div class="sensor-name">
                <i class="fas fa-bolt"></i> Corriente
            </div>
            <div class="bombilla-container">
                <div class="bombilla" id="bombillaCor"></div>
            </div>
            <div class="sensor-status apagado" id="statusCor">Apagado</div>
            <div class="sensor-valor" id="valorCor">N/A</div>
            <div class="sensor-timestamp" id="timestampCor">Esperando senal...</div>
            <button class="sensor-emergency-btn" onclick="apagarEmergencia('corriente', this)" title="Apago de emergencia del sensor de corriente">
                <i class="fas fa-power-off"></i> Apagado de emergencia
            </button>
        </div>
    </div>
    
    <div class="sensores-info">
        <i class="fas fa-circle-info"></i> Los datos se actualizan automaticamente cuando el ESP8266 envia informacion.
        Los sensores estaran <strong>apagados</strong> hasta que el sistema inicie.
    </div>
</div>

<div class="row g-4">

<div class="col-md-6">
<div class="stat-card">
<div class="stat-value temp"><?= $tempActual ?> &deg;C</div>
<div class="stat-label">Temperatura de operacion</div>
</div>
</div>

<div class="col-md-6">
<div class="stat-card">
<div class="stat-value energy"><?= $energiaActual ?> kWh</div>
<div class="stat-label">Energia generada</div>
</div>
</div>

</div>

<!-- FILTROS DE FECHA -->
<div class="chart-box">
    <div class="date-filters">
        <h5 style="text-align:center; margin-bottom:20px; color:#1f4037;"><i class="fas fa-calendar-days"></i> Seleccione periodo para ver graficas</h5>
        <div class="date-filters-row">
            <div class="filter-group">
                <label>Periodo:</label>
                <select id="periodoSelect" onchange="actualizarRangoFechas()">
                    <option value="dia" selected>Dia</option>
                    <option value="mes">Mes</option>
                    <option value="anual">Ano</option>
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
                <label>Ano:</label>
                <input type="number" id="fechaAnual" min="2020" max="2100">
            </div>
        </div>
        <div style="text-align:center; margin-top:15px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
            <button class="btn-filter" onclick="aplicarFiltros()"><i class="fas fa-chart-line"></i> Cargar graficas</button>
            <button class="btn-filter" style="background:linear-gradient(135deg, #667eea, #764ba2);" onclick="descargarDatos()"><i class="fas fa-download"></i> Descargar datos</button>
        </div>
        <div id="loadingIndicator" style="text-align:center; display:none; margin-top:10px; color:#1f4037; font-weight:bold;">
            <i class="fas fa-spinner fa-spin"></i> Cargando datos...
        </div>
    </div>
</div>

<div class="chart-box">
<h5 class="mb-3">Historico de temperatura</h5>
<div id="mensajeGraficas" style="text-align:center; padding:40px; color:#999; font-size:1.1rem;">
    Seleccione un periodo y presione "Cargar graficas" para visualizar los datos
</div>
<canvas id="chartTemp"></canvas>
</div>

<div class="chart-box">
<h5 class="mb-3">Historico de energia</h5>
<canvas id="chartEnergia"></canvas>
</div>

<!-- NUEVO GR�FICO DE GANANCIAS -->
<div class="chart-box">
<h5 class="mb-3"><i class="fas fa-chart-column"></i> Analisis de ganancias del sistema</h5>
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
const APP_TIMEZONE = 'America/Santo_Domingo';

// Datos de depositos organicos para proyeccion en grafica
const depositosParaCalculo = <?= json_encode(array_map(fn($d) => [
    'peso' => floatval($d['peso']),
    'ts'   => $d['ts']
], $deposConCalc)) ?>;
const FACTOR_KWH_KG_JS = 0.08;
const TIEMPO_GEN_MS    = 72 * 3600 * 1000;

function calcularProyeccionEnPunto(lblStr) {
    // Parsea etiqueta 'dd/mm/yyyy HH:ii:ss' o 'yyyy-mm-dd HH:ii:ss'
    let tsRef;
    const m = lblStr.match(/^(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})$/);
    if (m) {
        tsRef = new Date(m[3], m[2]-1, m[1], m[4], m[5], m[6]);
    } else {
        tsRef = new Date(lblStr.replace(' ', 'T'));
    }
    if (!tsRef || isNaN(tsRef.getTime())) return 0;
    let total = 0;
    for (const dep of depositosParaCalculo) {
        const tsD = new Date(dep.ts.replace(' ', 'T'));
        if (tsD <= tsRef) {
            const ms   = tsRef - tsD;
            const prog = Math.min(ms / TIEMPO_GEN_MS, 1.0);
            total += dep.peso * FACTOR_KWH_KG_JS * prog;
        }
    }
    return parseFloat(total.toFixed(4));
}
let maxTempHistorica = 0;
let ultimoTotalEnergiaWh = 0;
let lastSystemOffFlag = null;
let fechaDiaPicker, fechaMesPicker, fechaAnualPicker;
let chartTemp, chartEnergia, chartGanancias;

function formatDateOnlyLocal(dateObj = new Date()) {
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function formatDateTimeRD(value) {
    if (!value) return 'Sin fecha';
    const dt = (value instanceof Date) ? value : new Date(value);
    if (Number.isNaN(dt.getTime())) return String(value);
    return new Intl.DateTimeFormat('es-DO', {
        timeZone: APP_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).format(dt);
}

async function postControlAction(accion) {
    const response = await fetch('/PRERMI/BIOMASA/control_biomasa.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'accion=' + encodeURIComponent(accion)
    });

    const text = await response.text();
    let payload = {};
    try {
        payload = text ? JSON.parse(text) : {};
    } catch (e) {
        payload = { status: 'error', msg: 'respuesta_no_json', raw: text };
    }

    if (!response.ok || payload.status !== 'ok') {
        const msg = payload.msg || `HTTP ${response.status}`;
        throw new Error(msg);
    }

    return payload;
}

// ===== GR�FICOS INICIALES VAC�OS =====
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
            label: 'Energia (kWh)',
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
                label: 'Energia generada (kWh)',
                type: 'bar',
                data: [],
                backgroundColor: 'rgba(29,132,181,0.65)',
                borderColor: '#1d84b5',
                borderWidth: 2,
                order: 2
            },
            {
                label: 'Energia consumida (kWh)',
                type: 'bar',
                data: [],
                backgroundColor: 'rgba(255,152,0,0.65)',
                borderColor: '#ff9800',
                borderWidth: 2,
                order: 2
            },
            {
                label: 'Proyeccion por depositos organicos (kWh)',
                type: 'line',
                data: [],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                borderWidth: 2,
                order: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => `${ctx.dataset.label}: ${Number(ctx.raw).toFixed(4)} kWh`
                }
            }
        },
        scales: {
            x: { ticks: { maxRotation: 45, font: { size: 10 } } },
            y: {
                beginAtZero: true,
                title: { display: true, text: 'kWh' }
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
    // Solo actualizar si los gr�ficos est�n inicializados
    if (!chartTemp || !chartEnergia) return;
    
    const nowLabel = formatDateTimeRD(ts || new Date());
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
        statusText.textContent = 'En operacion';
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
    setStatusMessage('<span class="loading"></span> Iniciando sistema BIOMASA...', 'info');
    
    console.log('Enviando comando INICIO al Arduino...');
    
    postControlAction('START')
    .then(data => {
        if (data.status === 'ok') {
            // Sistema iniciado exitosamente
            estadoSistema = true;
            console.log('Comando INICIO encolado:', data);
            
            // Mostrar que el sistema est� operando
            setStatusMessage('Sistema iniciado correctamente', 'ok');
            
            // Actualizar interfaz inmediatamente
            actualizarInterfaz();
            
            // Recargar estado de sensores despu�s de 3 segundos
            setTimeout(() => {
                cargarEstadoSensores();
            }, 3500);
        } else {
            console.error('Error del servidor:', data.msg);
            setStatusMessage('Error al iniciar el sistema', 'error');
            btnStart.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error de conexion:', error);
        setStatusMessage('Error de conexion con el dispositivo', 'error');
        btnStart.disabled = false;
    });
}

function sendSystemOff(){
    postControlAction('SYSTEM_OFF').then(j=>{
        if (j.status==='ok') {
            estadoSistema = false;
            actualizarInterfaz();
            setStatusMessage('Sistema apagado completamente', 'warn');
        } else {
            setStatusMessage('Error al apagar el sistema', 'error');
        }
    }).catch(e=>setStatusMessage('Error de conexion: ' + e.message, 'error'));
}

function paradaEmergenciaTotal(){
    postControlAction('EMERGENCY').then(() => {
        estadoSistema = false;
        actualizarInterfaz();
        setStatusMessage('Parada de emergencia enviada al sistema', 'warn');
        setTimeout(() => {
            cargarEstadoSensores();
        }, 1500);
    }).catch(e => setStatusMessage('Error en emergencia: ' + e.message, 'error'));
}

function detenerSistema() {
    const btnStop = document.getElementById('btnStop');
    const statusMessage = document.getElementById('statusMessage');
    
    // Mostrar estado de carga
    btnStop.disabled = true;
    setStatusMessage('<span class="loading"></span> Deteniendo sistema BIOMASA...', 'warn');
    
    console.log('Enviando comando PARADA al Arduino...');
    
    postControlAction('STOP')
    .then(data => {
        if (data.status === 'ok') {
            // Sistema detenido exitosamente
            estadoSistema = false;
            console.log('Comando PARADA encolado:', data);
            
            setStatusMessage('Sistema detenido correctamente', 'ok');
            
            actualizarInterfaz();
            
            // Recargar estado despu�s de 3 segundos
            setTimeout(() => {
                cargarEstadoSensores();
            }, 3500);
        } else {
            console.error('Error del servidor:', data.msg);
            setStatusMessage('Error al detener el sistema', 'error');
            btnStop.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error de conexion:', error);
        setStatusMessage('Error de conexion con el dispositivo', 'error');
        btnStop.disabled = false;
    });
}

// Inicializar interfaz al cargar
window.addEventListener('DOMContentLoaded', () => {
    // Establecer fecha actual como valor por defecto
    const hoyObj = new Date();
    const hoy = formatDateOnlyLocal(hoyObj);
    document.getElementById('fechaDia').value = hoy;
    document.getElementById('fechaAnual').value = hoyObj.getFullYear();
    
    // Establecer rango del mes actual para el filtro de mes
    const primerDia = formatDateOnlyLocal(new Date(hoyObj.getFullYear(), hoyObj.getMonth(), 1));
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
                
                // Actualizar indicador LED seg�n estado del sistema
                const ledIndicator = document.getElementById('ledIndicator');
                const statusDot = document.getElementById('statusDot');
                const statusText = document.getElementById('statusText');
                
                if (sensores.sistema_activo) {
                    ledIndicator.classList.remove('off');
                    ledIndicator.classList.add('on');
                    statusDot.classList.remove('inactive');
                    statusDot.classList.add('active');
                    statusText.textContent = 'En operacion';
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
                
                // Actualizar energ�a generada en tiempo real
                const energiaWh = parseFloat(sensores.energia_generada || 0);
                ultimoTotalEnergiaWh = Number.isFinite(energiaWh) ? energiaWh : 0;
                actualizarEnergiaGenerada(ultimoTotalEnergiaWh);
                
                // Actualizar temperatura en card de estad�sticas
                const tempValor = parseFloat((sensores.temperatura && sensores.temperatura.valor) || 0);
                actualizarTemperaturaCard(tempValor);

                const ts = sensores.temperatura ? sensores.temperatura.timestamp : null;
                pushHistorico(tempValor, ultimoTotalEnergiaWh, ts);

                const control = sensores.control || {};
                const systemOffActivo = !!control.system_off;
                if (lastSystemOffFlag !== systemOffActivo) {
                    lastSystemOffFlag = systemOffActivo;
                    if (systemOffActivo) {
                        setStatusMessage('Sistema en modo de seguridad', 'warn');
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
    const timestamp = sensor.timestamp || 'Esperando senal...';
    
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
    
    // Actualizar seg�n estado
    if (estado === 'apagado') {
        statusEl.textContent = 'Apagado';
        statusEl.classList.add('apagado');
        valorEl.textContent = 'N/A';
    } else if (estado === 'activo') {
        card.classList.add('activo');
        bombilla.classList.add('activa');
        
        // Para ventilador: mostrar "Autom�tico" si est� activo por temperatura > 40�C
        if (id === 'Vent') {
            statusEl.textContent = 'Automatico';
        } else {
            statusEl.textContent = 'Activo';
        }
        
        statusEl.classList.add('activo');
        
        // Formato especial para valores
        let valorFormato = 'N/A';
        if (valor !== 'N/A') {
            if (id === 'Vent') {
                // Para ventilador: mostrar que est� activo (no necesita valor num�rico)
                valorFormato = 'En marcha';
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
    if (timestamp && timestamp !== 'Esperando senal...') {
        timestampEl.textContent = 'Ultima actualizacion: ' + formatDateTimeRD(timestamp);
    } else {
        timestampEl.textContent = 'Esperando senal...';
    }
}

// ===== APAGADO DE EMERGENCIA =====
function apagarEmergencia(sensor, btn) {
    // Confirmaci�n de apagado de emergencia
    if (!confirm('APAGADO DE EMERGENCIA\n\nEsta a punto de detener el sensor de ' + sensor.toUpperCase() + '.\n\nEsta seguro?')) {
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Apagando...';
    
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
            btn.innerHTML = '<i class="fas fa-power-off"></i> Apagado de emergencia';
            return;
    }
    
    postControlAction(accion)
    .then(data => {
        if (data.status === 'ok') {
            setStatusMessage('Sensor detenido por emergencia', 'warn');
        } else {
            setStatusMessage('Error en apagado de emergencia', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-power-off"></i> Apagado de emergencia';
        cargarEstadoSensores(); // Refrescar estado
    })
    .catch(error => {
        console.error('Error:', error);
        setStatusMessage('Error de conexion', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-power-off"></i> Apagado de emergencia';
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
            alert('Por favor seleccione un rango valido');
            return;
        }
        fechaInicio = partes[0];
        fechaFin = partes[1];
    } else if (periodo === 'anual') {
        const fecha = document.getElementById('fechaAnual').value;
        if (!fecha || isNaN(fecha)) {
            alert('Por favor seleccione un ano valido');
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
    
    console.log('Cargando datos desde:', url);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            console.log('Respuesta recibida:', data);
            
            if (data.status === 'ok') {
                if (data.resumen && data.resumen.registros === 0) {
                    alert('No hay datos disponibles para el periodo seleccionado.\n\nPor favor, seleccione otra fecha o verifique que existan mediciones en la base de datos.');
                    return;
                }
                
                // Actualizar gr�ficos con los nuevos datos
                actualizarGraficas(data.datos, data.resumen);
                
                // Mostrar notificaci�n de �xito
                const registros = data.resumen.registros || 0;
                console.log(`${registros} registros cargados correctamente`);
                
            } else {
                alert('Error al cargar datos:\n' + data.msg);
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            console.error('Error:', error);
            alert('Error de conexion al cargar datos:\n' + error.message + '\n\nVerifique que el servidor este funcionando.');
        });
}

function actualizarGraficas(datos, resumen) {
    const mensaje = document.getElementById('mensajeGraficas');
    if (mensaje) mensaje.style.display = 'none';

    // Grafica de temperatura
    chartTemp.data.labels = datos.labels;
    chartTemp.data.datasets[0].data = datos.tempData;
    chartTemp.update();

    // Grafica de energia
    chartEnergia.data.labels = datos.labels;
    chartEnergia.data.datasets[0].data = datos.energiaData;
    chartEnergia.update();

    // Calculo de proyeccion por depositos organicos para cada punto de medicion
    const proyData = datos.labels.map(lbl => calcularProyeccionEnPunto(lbl));

    // Grafica de ganancias: generada (barras) + consumida (barras) + proyeccion depositos (linea)
    chartGanancias.data.labels = datos.labels;
    chartGanancias.data.datasets[0].data = datos.energiaData;
    chartGanancias.data.datasets[1].data = datos.consumoData;
    chartGanancias.data.datasets[2].data = proyData;
    chartGanancias.update();

    actualizarResumenGanancias(resumen);
    console.log('Graficas actualizadas:', { registros: datos.labels.length, periodo: resumen });
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
            alert('Por favor seleccione un rango valido');
            return;
        }
        fechaInicio = partes[0];
        fechaFin = partes[1];
    } else if (periodo === 'anual') {
        const fecha = document.getElementById('fechaAnual').value;
        if (!fecha || isNaN(fecha)) {
            alert('Por favor seleccione un ano valido');
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
                alert('? Datos guardados correctamente en la carpeta de reportes (BIOMASA/reportes/)');
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