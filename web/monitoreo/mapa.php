<?php require_once __DIR__ . '/../admin/protect.php'; ?>
<?php require_once __DIR__ . '/../../config/db_config.php'; ?>
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Mapa Inteligente</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<style>
  #map { height: 600px; }
  .semaforo-info { 
    position: absolute; top: 10px; right: 10px; 
    background: white; padding: 15px; border-radius: 5px; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 1000;
    font-family: monospace; font-size: 12px;
  }
  .status-ok { color: #2e7d32; font-weight: bold; }
  .status-warning { color: #f57f17; font-weight: bold; }
  .status-error { color: #c62828; font-weight: bold; }
  .luz { width: 20px; height: 20px; border-radius: 50%; margin: 5px; display: inline-block; }
  .luz.verde { background-color: #4caf50; } 
  .luz.amarillo { background-color: #ffc107; }
  .luz.rojo { background-color: #f44336; }
</style>
</head>
<body>
<h2>Mapa de Semáforo Inteligente</h2>
<div id="map"></div>
<div class="semaforo-info">
  <div><strong>SEMÁFORO-001</strong></div>
  <div id="estado-semaforo" class="status-ok">Cargando...</div>
  <div id="luces-semaforo" style="margin-top: 10px;"></div>
  <div style="margin-top: 10px; border-top: 1px solid #ccc; padding-top: 10px;">
    <div>Eventos 24h:</div>
    <div>Normal: <span id="eventos-normal">0</span></div>
    <div>Rojo: <span id="eventos-rojo">0</span></div>
    <div>Sanciones: <span id="sanciones">0</span></div>
  </div>
</div>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
var map = L.map('map').setView([18.7357, -70.1627], 8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18}).addTo(map);

// Actualizar estado del semáforo cada 2 segundos
async function actualizarEstadoSemaforo() {
  try {
    const res = await fetch('/PRERMI/api/vehiculos/semaforo_status.php');
    const data = await res.json();
    if (data.success) {
      const sem = data.semaforo;
      const stats = data.estadisticas_24h;
      
      document.getElementById('estado-semaforo').innerHTML = 
        `<span class="status-ok">${sem.estado_actual.toUpperCase()}</span> (${sem.tiempo_restante_segundos}s)`;
      
      const luces = ['verde', 'amarillo', 'rojo'];
      let html = luces.map(l => 
        `<div class="luz ${l} ${l === sem.estado_actual ? 'encendido' : ''}"></div>`
      ).join('');
      document.getElementById('luces-semaforo').innerHTML = html;
      
      document.getElementById('eventos-normal').textContent = stats.eventos_normales;
      document.getElementById('eventos-rojo').textContent = stats.violaciones_rojo;
      document.getElementById('sanciones').textContent = stats.sanciones_aplicadas;
      
      // Agregar marcador del semáforo
      if (!window.semaforoMarker) {
        window.semaforoMarker = L.circleMarker([sem.latitud, sem.longitud], {
          radius: 12,
          color: '#1565c0',
          fillColor: '#42a5f5',
          fillOpacity: 0.8,
          weight: 3
        }).addTo(map);
      }
      const color = sem.estado_actual === 'verde' ? '#4caf50' : 
                    sem.estado_actual === 'amarillo' ? '#ffc107' : '#f44336';
      window.semaforoMarker.setStyle({ fillColor: color, color: color });
      window.semaforoMarker.bindPopup(
        `<b>SEMÁFORO-001</b><br>Estado: ${sem.estado_actual.toUpperCase()}<br>Tiempo: ${sem.tiempo_restante_segundos}s<br>` +
        `Eventos 24h: ${stats.total_eventos}<br>Violaciones: ${stats.violaciones_rojo}`
      );
    }
  } catch (e) {
    console.error('Error actualizando semáforo:', e);
  }
}
actualizarEstadoSemaforo();
setInterval(actualizarEstadoSemaforo, 2000);
</script>

<?php
$res = $conn->query("SELECT v.id,v.placa,v.tipo_vehiculo,v.imagen,v.ubicacion,v.fecha,v.hora,v.latitud,v.longitud, CASE WHEN csr.vehiculo_id IS NULL THEN 'normal' ELSE 'semaforo_rojo' END AS evento FROM vehiculos_registrados v LEFT JOIN capturas_semaforo_rojo csr ON csr.vehiculo_id = v.id WHERE v.latitud IS NOT NULL AND v.longitud IS NOT NULL ORDER BY v.id DESC LIMIT 100");
while($r=$res->fetch_assoc()):
?>
<script>
L.circleMarker([<?=floatval($r['latitud'])?>, <?=floatval($r['longitud'])?>], {
	radius: 8,
	color: "<?=($r['evento'] === 'semaforo_rojo' ? '#c62828' : '#1565c0')?>",
	fillColor: "<?=($r['evento'] === 'semaforo_rojo' ? '#ef5350' : '#42a5f5')?>",
	fillOpacity: 0.9,
	weight: 2
}).addTo(map)
.bindPopup("<b>Placa:</b> <?=addslashes($r['placa'])?><br><b>Tipo:</b> <?=addslashes($r['tipo_vehiculo'])?><br><b>Evento:</b> <?=addslashes($r['evento'])?><br><b>Ubic:</b> <?=addslashes($r['ubicacion'])?><br><b>Fecha:</b> <?=addslashes($r['fecha'])?> <?=addslashes($r['hora'])?><br><img src='/PRERMI/uploads/vehiculos/<?=addslashes($r['imagen'])?>' width='200'>");
</script>
<?php endwhile; ?>
</body></html>

