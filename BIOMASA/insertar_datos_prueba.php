<?php
/**
 * Script de prueba para insertar datos de ejemplo en mediciones_biomasa
 * Ejecuta este archivo UNA VEZ para tener datos de prueba
 */

$host = "localhost";
$db   = "prer_mi";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🧪 Inserción de Datos de Prueba</h2>";
    echo "<p>Conectado a la base de datos: <strong>$db</strong></p>";
    
    // Verificar si ya hay datos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mediciones_biomasa");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalExistente = $resultado['total'];
    
    echo "<p>📊 Registros existentes: <strong>$totalExistente</strong></p>";
    
    if ($totalExistente > 50) {
        echo "<p style='background:#fff3cd; padding:15px; border-radius:8px;'>";
        echo "⚠️ Ya hay suficientes datos en la tabla. No es necesario insertar más.";
        echo "</p>";
    } else {
        // Insertar datos de las últimas 24 horas
        echo "<p>⏳ Insertando datos de prueba para las últimas 24 horas...</p>";
        
        $insertados = 0;
        $ahora = time();
        
        for ($i = 0; $i < 24; $i++) {
            $timestamp = $ahora - ($i * 3600); // Cada hora hacia atrás
            $fecha = date('Y-m-d H:i:s', $timestamp);
            
            // Generar datos realistas
            $temperatura = 25 + (rand(0, 30) / 10); // 25-28°C
            $energia = rand(100, 500); // 100-500 Wh
            $relay = rand(10, 50);
            $ventilador = rand(5, 30);
            $peltier1 = rand(20, 80);
            $peltier2 = rand(20, 80);
            $gases = rand(5, 25);
            
            $sql = "INSERT INTO mediciones_biomasa 
                    (temperatura, energia, relay, ventilador, peltier1, peltier2, gases, fecha) 
                    VALUES (:temp, :energia, :relay, :vent, :p1, :p2, :gases, :fecha)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':temp' => $temperatura,
                ':energia' => $energia,
                ':relay' => $relay,
                ':vent' => $ventilador,
                ':p1' => $peltier1,
                ':p2' => $peltier2,
                ':gases' => $gases,
                ':fecha' => $fecha
            ]);
            
            $insertados++;
        }
        
        echo "<p style='color:green; font-weight:bold;'>✅ $insertados registros insertados correctamente</p>";
    }
    
    // Verificar total actual
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mediciones_biomasa");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalFinal = $resultado['total'];
    
    echo "<p>📊 Total de registros ahora: <strong>$totalFinal</strong></p>";
    
    // Mostrar últimos 5 registros
    echo "<h3>📋 Últimos 5 registros:</h3>";
    $stmt = $pdo->query("SELECT temperatura, energia, fecha FROM mediciones_biomasa ORDER BY fecha DESC LIMIT 5");
    $ultimos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; width:100%;'>";
    echo "<tr style='background:#1f4037; color:white;'><th>Fecha</th><th>Temperatura (°C)</th><th>Energía (Wh)</th></tr>";
    foreach($ultimos as $reg) {
        echo "<tr>";
        echo "<td>{$reg['fecha']}</td>";
        echo "<td>{$reg['temperatura']}</td>";
        echo "<td>{$reg['energia']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>🎯 Próximos pasos:</h3>";
    echo "<ol>";
    echo "<li>Cierra esta ventana</li>";
    echo "<li>Abre <a href='instalar_tabla_graficas.php' target='_blank'><strong>instalar_tabla_graficas.php</strong></a> (si aún no lo hiciste)</li>";
    echo "<li>Luego abre <a href='/PRERMI/web/admin/biores.php' target='_blank'><strong>biores.php</strong></a></li>";
    echo "<li>Selecciona <strong>\"Día\"</strong> y elige <strong>HOY</strong></li>";
    echo "<li>Presiona <strong>\"📊 Cargar Gráficas\"</strong></li>";
    echo "</ol>";
    
    echo "<p style='background:#d4edda; padding:15px; border-radius:8px; margin-top:20px;'>";
    echo "✅ <strong>Datos de prueba listos</strong><br>";
    echo "Ahora puedes probar el sistema de gráficas.";
    echo "</p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: linear-gradient(135deg, #1f4037, #99f2c8);
}
h2, h3 {
    color: #1f4037;
}
table {
    background: white;
    margin: 20px 0;
}
th, td {
    text-align: left;
}
a {
    color: #1f4037;
    font-weight: bold;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
