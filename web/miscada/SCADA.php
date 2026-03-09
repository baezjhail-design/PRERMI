<?php
date_default_timezone_set('America/Santo_Domingo');

/* ===== CONEXIÓN A BASE DE DATOS ===== */
$host = "localhost";
$db = "prer_mi";
$user = "root";
$pass = "";

$conexion = false;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8",$user,$pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $conexion = true;
} catch(PDOException $e){
    $conexion = false;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema SCADA</title>

<style>

/* ================= RESET ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

/* ================= BODY ================= */
body{
    font-family: 'Segoe UI', sans-serif;
    background:#12141c;
    color:#ffffff;
}

/* ================= HEADER ================= */
header{
    background:#0c0e14;
    padding:20px 40px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:3px solid #00ff99;
}

header h1{
    font-size:24px;
    letter-spacing:2px;
}

.header-info{
    text-align:right;
    font-size:14px;
}

.status-led{
    margin-top:5px;
    font-weight:bold;
}

.led{
    display:inline-block;
    width:10px;
    height:10px;
    border-radius:50%;
    margin-right:6px;
}

.led-green{
    background:#00ff99;
    box-shadow:0 0 10px #00ff99;
}

.led-red{
    background:red;
    box-shadow:0 0 10px red;
}

/* ================= DASHBOARD ================= */
.dashboard{
    display:flex;
    justify-content:center;
    gap:50px;
    padding:80px 20px;
    flex-wrap:wrap;
}

/* ================= CARDS ================= */
.card{
    width:300px;
    height:220px;
    padding:30px;
    border-radius:15px;
    text-decoration:none;
    color:white;
    transition:0.3s ease;
    box-shadow:0 10px 30px rgba(0,0,0,0.6);
    position:relative;
    overflow:hidden;
}

.card h2{
    margin-bottom:20px;
    font-size:20px;
}

.card p{
    font-size:14px;
    opacity:0.85;
}

/* Colores industriales */
.vehicular{
    background:linear-gradient(135deg,#00c6ff,#0072ff);
}

.biomasa{
    background:linear-gradient(135deg,#56ab2f,#a8e063);
}

.contenedores{
    background:linear-gradient(135deg,#ff9966,#ff5e62);
}

/* Hover */
.card:hover{
    transform:translateY(-8px);
    box-shadow:0 20px 40px rgba(0,0,0,0.8);
}

/* Línea decorativa */
.card::after{
    content:"";
    position:absolute;
    bottom:0;
    left:0;
    width:100%;
    height:5px;
    background:rgba(255,255,255,0.3);
}

/* ================= RESPONSIVE ================= */
@media(max-width:900px){
    .dashboard{
        flex-direction:column;
        align-items:center;
        padding-top:40px;
    }
}

</style>
</head>

<body>

<header>
    <div>
        <h1>SISTEMA SCADA</h1>
      
    </div>

    <div class="header-info">
        <div>Fecha: <?php echo date("d/m/Y"); ?></div>
        <div>Hora: <span id="reloj"><?php echo date("H:i:s"); ?></span></div>

        <div class="status-led">
            <?php if($conexion): ?>
                <span class="led led-green"></span>Conectado a prer_mi
            <?php else: ?>
                <span class="led led-red"></span>Error de Conexión
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <div class="dashboard">

        <a href="vehicular.php" class="card vehicular">
            <h2>🚛 Monitoreo Vehicular</h2>
            
        </a>

        <a href="biomasa.php" class="card biomasa">
            <h2>🌱 Sistema de Biomasa</h2>
            
        </a>

        <a href="contenedores.php" class="card contenedores">
            <h2>🗑 Gestión de Contenedores</h2>
            
        </a>

    </div>
</main>

<script>
setInterval(function(){
    const now = new Date();
    document.getElementById("reloj").innerHTML =
        now.toLocaleTimeString();
},1000);
</script>

</body>
</html>