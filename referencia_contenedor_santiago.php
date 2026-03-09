<?php
/**
 * REFERENCIA RÁPIDA - CONTENEDOR SANTIAGO
 * 
 * Este archivo documenta la configuración del contenedor fijo para Santiago
 */
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referencia - Contenedor Santiago</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .code-block { background: #f8f8f8; border-left: 4px solid #4CAF50; padding: 15px; margin: 10px 0; border-radius: 4px; font-family: 'Courier New'; overflow-x: auto; }
        .success { color: #4CAF50; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #4CAF50; color: white; }
        table tr:hover { background: #f5f5f5; }
        .info-box { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin: 15px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 CONTENEDOR FIJO SANTIAGO DE LOS CABALLEROS</h1>
        
        <div class="info-box">
            <h3>✅ Estado: ACTIVO Y FUNCIONAL</h3>
            <p>El contenedor está configurado correctamente en la base de datos y todos los sistemas están sincronizados.</p>
        </div>

        <h2>🔧 Configuración</h2>
        <table>
            <tr>
                <th>Parámetro</th>
                <th>Valor</th>
                <th>Descripción</th>
            </tr>
            <tr>
                <td><strong>ID</strong></td>
                <td><span class="success">1</span></td>
                <td>Identificador único fijo e invariable</td>
            </tr>
            <tr>
                <td><strong>Código</strong></td>
                <td>CONT-SANTIAGO-001</td>
                <td>Código del contenedor en el sistema</td>
            </tr>
            <tr>
                <td><strong>Ubicación</strong></td>
                <td>Santiago de los Caballeros</td>
                <td>Localización geográfica</td>
            </tr>
            <tr>
                <td><strong>Tipo</strong></td>
                <td>general</td>
                <td>Tipo de residuos aceptados</td>
            </tr>
            <tr>
                <td><strong>Estado</strong></td>
                <td><span class="success">activo</span></td>
                <td>Operativo y disponible</td>
            </tr>
        </table>

        <h2>💻 Configuración ESP32-S3 CAM</h2>
        <div class="code-block">
// En src/main.cpp:<br><br>
const int CONTAINER_ID_SANTIAGO = 1;<br><br>
// En función sendWeightData():<br>
doc["id_contenedor"] = CONTAINER_ID_SANTIAGO;
        </div>

        <h2>🖥️ Configuración PHP</h2>
        <div class="code-block">
// En api/contenedores/registrar_depositos.php:<br><br>
// ===== CONTENEDOR FIJO PARA SANTIAGO =====<br>
$id_contenedor = 1; // ID invariable, ignora cualquier valor enviado
        </div>

        <h2>📊 Flujo de Datos</h2>
        <div class="code-block">
1. ESP32 mide peso durante 20 segundos<br>
2. ESP32 prepara JSON con id_contenedor = 1<br>
3. ESP32 POST → /api/contenedores/registrar_depositos.php<br>
4. PHP recibe JSON pero IGNORA id_contenedor<br>
5. PHP SIEMPRE establece $id_contenedor = 1<br>
6. PHP valida que ID=1 existe en contenedores_registrados (✅ EXISTE)<br>
7. PHP INSERT en tabla depositos con id_contenedor = 1<br>
8. MySQL FK constraint verifica ID=1 existe (✅ OK)<br>
9. HTTP 200 con éxito
        </div>

        <h2>🧪 Verificación de BD</h2>
        <div class="code-block">
SELECT * FROM contenedores_registrados WHERE id = 1;<br>
<br>
Debe mostrar:<br>
id: 1<br>
codigo_contenedor: CONT-SANTIAGO-001<br>
ubicacion: Santiago de los Caballeros<br>
tipo_contenedor: general<br>
estado: activo
        </div>

        <h2>📋 Verificación de Depositos</h2>
        <div class="code-block">
SELECT * FROM depositos<br>
WHERE id_usuario = 4<br>
AND id_contenedor = 1<br>
ORDER BY fecha_hora DESC<br>
LIMIT 5;
        </div>

        <h2>⚠️ Puntos Importantes</h2>
        <ul>
            <li><span class="warning">NUNCA cambiar</span> CONTAINER_ID_SANTIAGO de 1</li>
            <li><span class="warning">NUNCA cambiar</span> el $id_contenedor = 1 en el PHP</li>
            <li><span class="warning">DO NOT</span> eliminar el registro de contenedores_registrados con id=1</li>
            <li><span class="success">SIEMPRE</span> mantener sincronizados ESP32 y PHP</li>
            <li><span class="success">SIEMPRE</span> validar que FK constraint está activa</li>
        </ul>

        <h2>🐛 Troubleshooting</h2>
        <table>
            <tr>
                <th>Problema</th>
                <th>Causa</th>
                <th>Solución</th>
            </tr>
            <tr>
                <td><span class="error">HTTP 500</span></td>
                <td>Contenedor ID=1 no existe</td>
                <td>Ejecutar setup_contenedor_santiago.php</td>
            </tr>
            <tr>
                <td><span class="error">Foreign Key Constraint</span></td>
                <td>No se validó ID=1</td>
                <td>Revisar tabla contenedores_registrados</td>
            </tr>
            <tr>
                <td><span class="warning">Ubicación incorrecta</span></td>
                <td>Datos desincronizados</td>
                <td>Verificar ambos códigos usan CONTAINER_ID_SANTIAGO</td>
            </tr>
        </table>

        <h2>📞 Scripts de Utilidad</h2>
        <ul>
            <li><strong>setup_contenedor_santiago.php</strong> - Crear/verificar contenedor</li>
            <li><strong>test_insert_deposito.php</strong> - Probar inserción con ID=1</li>
            <li><strong>verify_bd_integridad.php</strong> - Verificar integridad de BD</li>
        </ul>

        <div class="info-box" style="background: #f1f8e9; border-left-color: #689F38;">
            <h3>✨ Última Actualización</h3>
            <p><strong>Fecha</strong>: 2026-03-02</p>
            <p><strong>Cambios</strong>: Implementación de contenedor fijo e invariable para Santiago de los Caballeros</p>
            <p><strong>Estado</strong>: <span class="success">✅ VERIFICADO Y FUNCIONAL</span></p>
        </div>
    </div>
</body>
</html>
