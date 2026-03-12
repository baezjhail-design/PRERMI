<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "prer_mi";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión a la BD");
}

// ===== DATOS DE AHORRO ELÉCTRICO BIOMASA =====
$TARIFA_RD_KWH = 14.00;
$bioMonthLabels = [];
$bioMonthRD     = [];
$bioMonthKwh    = [];
$bioTotalKwh    = 0.0;
$bioTotalRD     = 0.0;
$bioMesKwh      = 0.0;
$bioMesRD       = 0.0;

$savSQL = "
    SELECT DATE_FORMAT(creado_en,'%Y-%m') AS mes,
           SUM(COALESCE(energia_generada,0)) AS kwh_total
    FROM biomasa
    WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes ASC";
$savResult = $conn->query($savSQL);
$bioMap = [];
if ($savResult) {
    while ($r = $savResult->fetch_assoc()) {
        $bioMap[$r['mes']] = (float)$r['kwh_total'];
    }
}
for ($i = 5; $i >= 0; $i--) {
    $ts    = strtotime("-$i months");
    $key   = date('Y-m', $ts);
    $label = date('M Y', $ts);
    $kwh   = $bioMap[$key] ?? 0;
    $rd    = round($kwh * $TARIFA_RD_KWH, 2);
    $bioMonthLabels[] = $label;
    $bioMonthKwh[]    = $kwh;
    $bioMonthRD[]     = $rd;
    $bioTotalKwh     += $kwh;
    $bioTotalRD      += $rd;
}
$curKey   = date('Y-m');
$bioMesKwh = $bioMap[$curKey] ?? 0;
$bioMesRD  = round($bioMesKwh * $TARIFA_RD_KWH, 2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SCADA Biomasa</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

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

/* ===== AHORRO ELÉCTRICO ===== */
.savings-biomasa{ padding:0 40px 40px; }
.sav-title{ text-align:center;font-size:20px;font-weight:800;margin-bottom:24px;letter-spacing:1px; }
.sav-title span{
    background:linear-gradient(90deg,#06b6d4,#10b981,#7c3aed);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.sav-kpi-row{ display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-bottom:28px; }
.sav-kpi{ flex:1;min-width:160px;max-width:200px;border-radius:16px;padding:20px;text-align:center; }
.sav-kpi.cyan  { background:linear-gradient(135deg,#0e7490,#06b6d4);box-shadow:0 4px 20px #06b6d440; }
.sav-kpi.green { background:linear-gradient(135deg,#065f46,#10b981);box-shadow:0 4px 20px #10b98140; }
.sav-kpi.purple{ background:linear-gradient(135deg,#5b21b6,#7c3aed);box-shadow:0 4px 20px #7c3aed40; }
.sav-kpi .kpi-icon{ font-size:26px;margin-bottom:6px; }
.sav-kpi .kpi-lbl{ font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.7);margin-bottom:4px; }
.sav-kpi .kpi-val{ font-size:22px;font-weight:800;color:#fff; }
.sav-kpi .kpi-sub{ font-size:11px;color:rgba(255,255,255,0.65);margin-top:3px; }
.sav-chart-box{
    background:#161b22;border-radius:16px;padding:24px;
    border:1px solid rgba(6,182,212,0.2);box-shadow:0 6px 24px rgba(0,0,0,0.4);
    max-width:900px;margin:0 auto;
}
.sav-note{ text-align:center;font-size:11px;color:#475569;margin-top:12px; }
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

<!-- ===== AHORRO ELÉCTRICO BIOMASA ===== -->
<section class="savings-biomasa">
    <p class="sav-title">⚡ <span>Ahorro Eléctrico — Sistema Biomasa (Pesos Dominicanos)</span></p>

    <div class="sav-kpi-row">
        <div class="sav-kpi cyan">
            <div class="kpi-icon">💡</div>
            <div class="kpi-lbl">Este mes</div>
            <div class="kpi-val">RD$ <?php echo number_format($bioMesRD, 2); ?></div>
            <div class="kpi-sub"><?php echo number_format($bioMesKwh, 4); ?> kWh generados</div>
        </div>
        <div class="sav-kpi green">
            <div class="kpi-icon">🌿</div>
            <div class="kpi-lbl">Ahorro acumulado (6 meses)</div>
            <div class="kpi-val">RD$ <?php echo number_format($bioTotalRD, 2); ?></div>
            <div class="kpi-sub"><?php echo number_format($bioTotalKwh, 4); ?> kWh totales</div>
        </div>
        <div class="sav-kpi purple">
            <div class="kpi-icon">📊</div>
            <div class="kpi-lbl">Tarifa de referencia</div>
            <div class="kpi-val">RD$ <?php echo number_format($TARIFA_RD_KWH, 2); ?></div>
            <div class="kpi-sub">por kWh (EDENORTE/EDESUR)</div>
        </div>
    </div>

    <div class="sav-chart-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <span style="font-size:15px;font-weight:700;color:#e2e8f0;">📈 Reducción mensual de costo eléctrico — Energía Biomasa</span>
            <span style="background:linear-gradient(90deg,#06b6d4,#7c3aed);color:#fff;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;">RD$ / kWh</span>
        </div>
        <canvas id="bioSavChart" height="120"></canvas>
        <p class="sav-note">Energía generada por sistema Peltier-Biomasa × tarifa RD$ <?php echo number_format($TARIFA_RD_KWH,2); ?>/kWh (EDENORTE/EDESUR)</p>
    </div>
</section>

<script>
(function(){
    const labels  = <?php echo json_encode($bioMonthLabels); ?>;
    const savings = <?php echo json_encode($bioMonthRD); ?>;
    const kwhs    = <?php echo json_encode($bioMonthKwh); ?>;
    const ctx = document.getElementById('bioSavChart');
    if(!ctx) return;
    new Chart(ctx, {
        type:'bar',
        data:{
            labels: labels,
            datasets:[
                {
                    label:'Ahorro RD$',
                    data:savings,
                    backgroundColor:savings.map((v,i)=>`hsla(${160+i*30},80%,50%,0.75)`),
                    borderColor:savings.map((v,i)=>`hsl(${160+i*30},80%,55%)`),
                    borderWidth:2,borderRadius:8,yAxisID:'yRD'
                },
                {
                    label:'kWh generados',data:kwhs,type:'line',
                    borderColor:'#7c3aed',backgroundColor:'rgba(124,58,237,0.12)',
                    pointBackgroundColor:'#7c3aed',pointRadius:5,
                    borderWidth:2.5,fill:true,tension:0.35,yAxisID:'yKwh'
                }
            ]
        },
        options:{
            responsive:true,
            interaction:{mode:'index',intersect:false},
            plugins:{
                legend:{labels:{color:'#cbd5e1',font:{size:12}}},
                tooltip:{callbacks:{label:c=>c.dataset.yAxisID==='yRD'?` Ahorro: RD$ ${c.parsed.y.toFixed(2)}`:`kWh: ${c.parsed.y.toFixed(6)}`}}
            },
            scales:{
                x:{ticks:{color:'#94a3b8'},grid:{color:'rgba(255,255,255,0.05)'}},
                yRD:{position:'left',ticks:{color:'#06b6d4',callback:v=>'RD$'+v.toFixed(2)},grid:{color:'rgba(6,182,212,0.1)'}},
                yKwh:{position:'right',ticks:{color:'#7c3aed',callback:v=>v.toFixed(4)+' kWh'},grid:{drawOnChartArea:false}}
            }
        }
    });
})();
</script>

</body>
</html>