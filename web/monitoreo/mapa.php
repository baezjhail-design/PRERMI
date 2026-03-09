<?php require_once __DIR__ . '/../admin/protect.php'; ?>
<?php require_once __DIR__ . '/../../config/db_config.php'; ?>
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Mapa</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/></head>
<body>
<h2>Mapa de Capturas</h2>
<div id="map" style="height:600px;"></div>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
var map = L.map('map').setView([18.7357, -70.1627], 8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18}).addTo(map);
</script>
<?php
$res = $conn->query("SELECT id,placa,imagen,ubicacion,fecha,hora,latitud,longitud FROM vehiculos_registrados WHERE latitud IS NOT NULL AND longitud IS NOT NULL");
while($r=$res->fetch_assoc()):
?>
<script>
L.marker([<?=floatval($r['latitud'])?>, <?=floatval($r['longitud'])?>]).addTo(map)
.bindPopup("<b>Placa:</b> <?=addslashes($r['placa'])?><br><b>Ubic:</b> <?=addslashes($r['ubicacion'])?><br><img src='/PRERMI/uploads/vehiculos/<?=addslashes($r['imagen'])?>' width='180'>");
</script>
<?php endwhile; ?>
</body></html>
