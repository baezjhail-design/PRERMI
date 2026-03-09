<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "prer_mi";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión a la BD");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SCADA Contenedores</title>

<style>
body{
    margin:0;
    font-family:Segoe UI, sans-serif;
    background:#0b0f18;
    color:#e5e7eb;
}

.boton-volver{
    position:absolute;
    top:20px;
    left:20px;
    padding:10px 18px;
    background:#1f2937;
    color:#e5e7eb;
    border:1px solid #374151;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
    transition:0.3s;
}

.boton-volver:hover{
    background:#2563eb;
    border-color:#2563eb;
}

header{
    background:#111827;
    padding:20px;
    text-align:center;
    font-size:22px;
    border-bottom:3px solid #1f2937;
}

.dashboard{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:30px;
    padding:40px;
}

.panel{
    background:#161b22;
    padding:30px;
    border-radius:12px;
    border:1px solid #1f2937;
    box-shadow:0 0 25px rgba(0,0,0,0.6);
}

.status-box{
    text-align:center;
    font-size:22px;
    font-weight:bold;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
}

.normal{ background:#064e3b; color:#34d399; }
.alerta{ background:#7f1d1d; color:#f87171; }

.sensor-activo{ color:#f87171; font-weight:bold; }
.sensor-inactivo{ color:#34d399; font-weight:bold; }

.valor-grande{
    font-size:40px;
    font-weight:bold;
    color:#38bdf8;
    text-align:center;
    margin:20px 0;
}

.indicador{
    width:120px;
    height:120px;
    border-radius:50%;
    margin:20px auto;
    background:#111;
    box-shadow:inset 0 0 20px #000;
}

.activo{ background:red; box-shadow:0 0 25px red; }
.inactivo{ background:lime; box-shadow:0 0 25px lime; }

.info{
    text-align:center;
    font-size:14px;
    margin-top:15px;
}
</style>
</head>

<body>

<button class="boton-volver" onclick="window.location.href='SCADA.php'">
⬅ Volver al Panel Principal
</button>

<header>
SISTEMA SCADA - CONTENEDORES
</header>

<div class="dashboard">

<div class="panel">
    <div id="estado" class="status-box">Cargando...</div>

    <div style="text-align:center;">
        Sensor de Metales:
        <span id="sensorEstado"></span>
    </div>

    <div id="indicador" class="indicador"></div>

    <div class="info">
        Última actualización: <span id="fecha"></span>
    </div>
</div>

<div class="panel">
    <h3 style="text-align:center;">Celda de Carga</h3>
    <div id="peso" class="valor-grande">0 kg</div>
</div>

</div>

<script>
function actualizar(){
fetch("leer_contenedor.php")
.then(response => response.json())
.then(data => {

if(data.error) return;

let estado=document.getElementById("estado");
let indicador=document.getElementById("indicador");
let sensorEstado=document.getElementById("sensorEstado");

estado.className="status-box";
indicador.className="indicador";

document.getElementById("peso").innerText=data.peso+" kg";

if(data.sensor_metal==1){
estado.innerText="METAL DETECTADO";
estado.classList.add("alerta");
sensorEstado.innerText="ACTIVO";
sensorEstado.className="sensor-activo";
indicador.classList.add("activo");
}else{
estado.innerText="OPERACIÓN NORMAL";
estado.classList.add("normal");
sensorEstado.innerText="INACTIVO";
sensorEstado.className="sensor-inactivo";
indicador.classList.add("inactivo");
}

document.getElementById("fecha").innerText=data.fecha;

});
}
setInterval(actualizar,1000);
actualizar();
</script>

</body>
</html>