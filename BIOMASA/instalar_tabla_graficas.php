<?php
/**
 * Script de instalación para crear la tabla datos_graficas
 * Ejecuta este archivo UNA VEZ para crear la tabla en la base de datos
 */

$host = "localhost";
$db   = "prer_mi";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Instalación de Tabla datos_graficas</h2>";
    echo "<p>Conectado a la base de datos: <strong>$db</strong></p>";
    
    // Crear tabla de datos de gráficas
    $sql = "CREATE TABLE IF NOT EXISTS datos_graficas (
        id int(11) NOT NULL AUTO_INCREMENT,
        periodo varchar(20) NOT NULL COMMENT 'dia, mes, anual',
        fecha_inicio date NOT NULL,
        fecha_fin date NOT NULL,
        total_temp_promedio decimal(5,2) DEFAULT 0,
        total_temp_max decimal(5,2) DEFAULT 0,
        total_temp_min decimal(5,2) DEFAULT 0,
        total_energia_generada decimal(10,4) DEFAULT 0,
        total_energia_consumida decimal(10,4) DEFAULT 0,
        total_energia_neta decimal(10,4) DEFAULT 0,
        ganancia_monetaria decimal(10,2) DEFAULT 0,
        datos_json longtext,
        fecha_creacion timestamp DEFAULT current_timestamp(),
        fecha_actualizacion timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY(id),
        UNIQUE KEY unique_periodo (periodo, fecha_inicio, fecha_fin)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    
    echo "<p style='color:green; font-weight:bold;'>✅ Tabla 'datos_graficas' creada exitosamente</p>";
    
    // Verificar que existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'datos_graficas'");
    $existe = $stmt->fetch();
    
    if ($existe) {
        echo "<p>✅ Verificación: La tabla existe en la base de datos</p>";
        
        // Mostrar estructura
        echo "<h3>📋 Estructura de la tabla:</h3>";
        $stmt = $pdo->query("DESCRIBE datos_graficas");
        $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr style='background:#1f4037; color:white;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach($campos as $campo) {
            echo "<tr>";
            echo "<td><strong>{$campo['Field']}</strong></td>";
            echo "<td>{$campo['Type']}</td>";
            echo "<td>{$campo['Null']}</td>";
            echo "<td>{$campo['Key']}</td>";
            echo "<td>{$campo['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM datos_graficas");
        $total = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>📊 Registros actuales en la tabla: <strong>{$total['total']}</strong></p>";
        
    } else {
        echo "<p style='color:red;'>❌ ERROR: La tabla no se pudo crear</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎯 Próximos pasos:</h3>";
    echo "<ol>";
    echo "<li>Cierra esta ventana</li>";
    echo "<li>Abre <a href='/PRERMI/web/admin/biores.php' target='_blank'><strong>biores.php</strong></a></li>";
    echo "<li>Selecciona un período (Día/Mes/Año)</li>";
    echo "<li>Elige las fechas</li>";
    echo "<li>Presiona <strong>\"📊 Cargar Gráficas\"</strong></li>";
    echo "</ol>";
    
    echo "<p style='background:#d4edda; padding:15px; border-radius:8px; margin-top:20px;'>";
    echo "✅ <strong>Instalación completada con éxito</strong><br>";
    echo "La tabla está lista para almacenar datos de gráficas.";
    echo "</p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>XAMPP esté corriendo</li>";
    echo "<li>MySQL esté activo</li>";
    echo "<li>La base de datos 'prer_mi' exista</li>";
    echo "<li>Las credenciales sean correctas (usuario: root, sin contraseña)</li>";
    echo "</ul>";
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
    width: 100%;
    background: white;
    margin: 20px 0;
}
th, td {
    text-align: left;
}
a {
    color: #1f4037;
    font-weight: bold;
}
</style>
