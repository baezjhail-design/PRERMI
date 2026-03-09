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
<title>Monitoreo Vehicular</title>

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

.sensor-box{
    text-align:center;
    font-size:18px;
    margin-bottom:20px;
}

.sensor-activo{ color:#f87171; font-weight:bold; }
.sensor-inactivo{ color:#34d399; font-weight:bold; }

.semaforo{
    width:120px;
    margin:30px auto;
    background:#222;
    padding:20px;
    border-radius:20px;
    box-shadow:inset 0 0 20px #000;
}

.luz{
    width:70px;
    height:70px;
    border-radius:50%;
    margin:20px auto;
    background:#111;
}

.activa.verde{ background:lime; box-shadow:0 0 25px lime; }
.activa.amarillo{ background:yellow; box-shadow:0 0 25px yellow; }
.activa.rojo{ background:red; box-shadow:0 0 25px red; }

.imagen{
    width:100%;
    height:300px;
    object-fit:cover;
    border-radius:10px;
    border:2px solid #1f2937;
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
SISTEMA SCADA-MONITOREO VEHICULAR
</header>

<div class="dashboard">

<div class="panel">
    <div id="estado" class="status-box">Cargando...</div>
    <div class="sensor-box">
        Sensor IR: <span id="sensorEstado"></span>
    </div>

    <div class="semaforo">
        <div id="rojo" class="luz"></div>
        <div id="amarillo" class="luz"></div>
        <div id="verde" class="luz"></div>
    </div>

    <div class="info">
        Última detección: <span id="fecha"></span>
    </div>
</div>

<div class="panel">
    <h3>Última Imagen Capturada</h3>
    <img id="foto" class="imagen" src="" alt="Sin imagen">
</div>

</div>

<script>
function actualizar(){
fetch("leer_registro.php")
.then(response => response.json())
.then(data => {

if(data.error) return;

let estadoBox = document.getElementById("estado");
let rojo = document.getElementById("rojo");
let amarillo = document.getElementById("amarillo");
let verde = document.getElementById("verde");
let sensorEstado = document.getElementById("sensorEstado");

rojo.className="luz";
amarillo.className="luz";
verde.className="luz";
estadoBox.className="status-box";

if(data.sensor_ir==1){
estadoBox.innerText="VEHÍCULO DETECTADO";
estadoBox.classList.add("alerta");
sensorEstado.innerText="ACTIVO";
sensorEstado.className="sensor-activo";
rojo.classList.add("activa","rojo");
amarillo.classList.add("activa","amarillo");
}else{
estadoBox.innerText="OPERACIÓN NORMAL";
estadoBox.classList.add("normal");
sensorEstado.innerText="INACTIVO";
sensorEstado.className="sensor-inactivo";
verde.classList.add("activa","verde");
}

document.getElementById("fecha").innerText=data.fecha;
document.getElementById("foto").src=data.ruta_imagen+"?t="+new Date().getTime();

});
}
setInterval(actualizar,1000);
actualizar();
</script>

</body>
</html>