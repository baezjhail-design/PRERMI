<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Control Camión PRERMI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.btn-control {
    width: 180px;
    height: 60px;
    font-size: 18px;
    margin: 5px;
}
.section-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}
</style>
</head>
<body class="container py-4">

<h2 class="text-center mb-4">🚛 Control Camión PRERMI</h2>

<div class="section-box">
<h4>Movimiento</h4>
<div class="text-center">
    <button class="btn btn-success btn-control" onclick="enviar('UP')">ADELANTE</button>
    <button class="btn btn-warning btn-control" onclick="enviar('DOWN')">ATRÁS</button>
    <button class="btn btn-primary btn-control" onclick="enviar('LEFT')">IZQUIERDA</button>
    <button class="btn btn-primary btn-control" onclick="enviar('RIGHT')">DERECHA</button>
    <button class="btn btn-danger btn-control" onclick="enviar('STOP')">STOP</button>
</div>
</div>

<div class="section-box">
<h4>Sistema Hidráulico</h4>
<div class="text-center">
    <button class="btn btn-dark btn-control" onclick="enviar('ARM_UP')">⬆ SUBIR BRAZO</button>
    <button class="btn btn-secondary btn-control" onclick="enviar('ARM_DOWN')">⬇ BAJAR BRAZO</button>
    <button class="btn btn-danger btn-control" onclick="enviar('ARM_STOP')">⛔ STOP BRAZO</button>
</div>
</div>

<div class="alert alert-info text-center">
    Último comando: <span id="estado">Ninguno</span>
</div>

<script>
function enviar(comando) {

    fetch('../../api/motores/enviar_comando.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'comando=' + encodeURIComponent(comando)
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === "ok"){
            document.getElementById("estado").innerText = comando;
        } else {
            alert(data.msg);
        }
    })
    .catch(error => {
        alert("Error en la comunicación");
    });
}
</script>

</body>
</html>