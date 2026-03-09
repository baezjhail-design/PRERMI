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
<title>SCADA Biomasa</title>

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
    font-size:20px;
    font-weight:bold;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
}

.normal{ background:#064e3b; color:#34d399; }
.alerta{ background:#7f1d1d; color:#f87171; }

.activo{ color:#34d399; font-weight:bold; }
.inactivo{ color:#f87171; font-weight:bold; }

.valor{
    font-size:28px;
    font-weight:bold;
    color:#38bdf8;
    margin:10px 0;
}

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
SISTEMA SCADA - BIOMASA
</header>

<div class="dashboard">

<div class="panel">

<div id="estadoGeneral" class="status-box">Cargando...</div>

<div>Relay: <span id="relayEstado"></span></div>
<div>Ventilador: <span id="ventiladorEstado"></span></div>

<div class="info">
Última actualización: <span id="fecha"></span>
</div>

</div>

<div class="panel">

<h3>Generación Peltier</h3>
<div class="valor">Peltier 1: <span id="p1"></span> V</div>
<div class="valor">Peltier 2: <span id="p2"></span> V</div>

<h3>temperatura Biomasa</h3>
<div class="valor"><span id="gases"></span> °</div>

</div>

</div>

<script>
function actualizar(){
fetch("leer_biomasa.php")
.then(response=>response.json())
.then(data=>{

if(data.error) return;

let estado=document.getElementById("estadoGeneral");
estado.className="status-box";

document.getElementById("relayEstado").innerText=data.relay==1?"ACTIVO":"INACTIVO";
document.getElementById("relayEstado").className=data.relay==1?"activo":"inactivo";

document.getElementById("ventiladorEstado").innerText=data.ventilador==1?"ACTIVO":"INACTIVO";
document.getElementById("ventiladorEstado").className=data.ventilador==1?"activo":"inactivo";

document.getElementById("p1").innerText=data.peltier1;
document.getElementById("p2").innerText=data.peltier2;
document.getElementById("gases").innerText=data.gases;

if(data.gases>300){
estado.innerText="ALTA CONCENTRACIÓN DE GASES";
estado.classList.add("alerta");
}else{
estado.innerText="OPERACIÓN NORMAL";
estado.classList.add("normal");
}

document.getElementById("fecha").innerText=data.fecha;

});
}
setInterval(actualizar,1000);
actualizar();
</script>

</body>
</html>