# Instrucciones para Insertar Datos de Prueba

## Opción 1: Vía phpMyAdmin (Recomendado para principiantes)

1. Abre tu navegador y ve a: `http://localhost/phpmyadmin`
2. Inicia sesión con tus credenciales
3. Selecciona la base de datos `prer_mi` en el panel izquierdo
4. Haz clic en la pestaña **SQL** en la parte superior
5. Abre el archivo `insertar_datos_prueba.sql` de esta carpeta
6. Copia TODO el contenido del archivo
7. Pégalo en el área de texto de phpMyAdmin
8. Haz clic en el botón **Ejecutar** (o presiona Ctrl+Enter)
9. Deberías ver un mensaje verde confirmando que se insertaron los registros

## Opción 2: Vía Línea de Comandos (MySQL)

### En Windows (CMD o PowerShell):
```bash
cd d:\xampp\mysql\bin

mysql -u root -p prer_mi < d:\xampp\htdocs\PRERMI\insertar_datos_prueba.sql
```
(Si no tienes contraseña, omite `-p`)

### En Mac/Linux:
```bash
mysql -u root -p prer_mi < /path/to/insertar_datos_prueba.sql
```

## Opción 3: Vía PHP Script

Crea un archivo `ejecutar_datos_prueba.php` en la raíz del proyecto con este contenido:

```php
<?php
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/api/utils.php';

try {
    $pdo = getPDO();
    
    // Limpiar datos anteriores (opcional)
    // $pdo->exec("DELETE FROM depositos");
    // $pdo->exec("DELETE FROM contenedores_registrados");
    
    // Insertar contenedores
    $contenedores = [
        ['CONT-001', 'Zona Centro, Av. Principal', 'plastico', 'activo', '550e8400-e29b-41d4-a716-446655440000'],
        ['CONT-002', 'Zona Norte, Calle 5', 'vidrio', 'activo', '550e8400-e29b-41d4-a716-446655440001'],
        ['CONT-003', 'Zona Sur, Parque Central', 'metal', 'activo', '550e8400-e29b-41d4-a716-446655440002'],
        ['CONT-004', 'Zona Este, Terminal de Transporte', 'papel', 'activo', '550e8400-e29b-41d4-a716-446655440003'],
        ['CONT-005', 'Zona Oeste, Centro Comercial', 'organico', 'inactivo', '550e8400-e29b-41d4-a716-446655440004']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO contenedores_registrados 
        (codigo_contenedor, ubicacion, tipo_contenedor, estado, ultimo_token, token_generado_en, token_expira_en)
        VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
    ");
    
    foreach ($contenedores as $cont) {
        $stmt->execute($cont);
    }
    
    echo "✓ " . count($contenedores) . " contenedores insertados<br>";
    
    // Insertar depósitos (requiere que exista usuario con id=1)
    $depositos = [
        [1, 1, '550e8400-e29b-41d4-a716-446655440000', 12.5, 'plastico', 6.25, 0, '2025-01-26 10:30:00', 'Depósito normal'],
        [1, 1, '550e8400-e29b-41d4-a716-446655440000', 8.3, 'plastico', 4.15, 0, '2025-01-26 11:45:00', 'Depósito normal'],
        [1, 2, '550e8400-e29b-41d4-a716-446655440001', 5.2, 'vidrio', 2.60, 0, '2025-01-26 13:20:00', 'Depósito normal'],
        [1, 3, '550e8400-e29b-41d4-a716-446655440002', 3.8, 'metal', 1.90, 1, '2025-01-26 14:15:00', 'Metal detectado'],
        [1, 4, '550e8400-e29b-41d4-a716-446655440003', 15.6, 'papel', 7.80, 0, '2025-01-26 15:30:00', 'Depósito normal']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO depositos 
        (id_usuario, id_contenedor, token_usado, peso, tipo_residuo, credito_kwh, metal_detectado, fecha_hora, observaciones)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($depositos as $dep) {
        $stmt->execute($dep);
    }
    
    echo "✓ " . count($depositos) . " depósitos insertados<br>";
    echo "<br><strong style=\"color: green;\">✓ Datos de prueba insertados correctamente</strong>";
    
} catch (Exception $e) {
    echo "<strong style=\"color: red;\">✗ Error: " . htmlspecialchars($e->getMessage()) . "</strong>";
}
?>
```

Luego abre en navegador: `http://localhost/PRERMI/ejecutar_datos_prueba.php`

## Verificar los Datos

Después de ejecutar cualquiera de las opciones anteriores:

1. En phpMyAdmin, selecciona `contenedores_registrados` y verifica que haya **5 registros**
2. Selecciona `depositos` y verifica que haya **6 registros**
3. Los mensajes "MySQL returned an empty result set" desaparecerán
4. En lugar de eso, verás datos reales en las tablas

## ¿Qué contienen los datos de prueba?

### Contenedores (5 total):
- CONT-001: Centro, tipo plástico, activo
- CONT-002: Zona Norte, tipo vidrio, activo
- CONT-003: Zona Sur, tipo metal, activo
- CONT-004: Zona Este, tipo papel, activo
- CONT-005: Zona Oeste, tipo orgánico, inactivo

### Depósitos (6 total):
- 2 depósitos en CONT-001 (plástico)
- 1 depósito en CONT-002 (vidrio)
- 1 depósito en CONT-003 (metal - con detección de metal)
- 1 depósito en CONT-004 (papel)
- 1 depósito en CONT-002 (vidrio - segundo)

Todos los depósitos están asociados al usuario con `id=1` en la tabla `usuarios`.

## Notas Importantes

- Los datos de prueba usarán **id_usuario = 1**, asegúrate de que este usuario exista en tu tabla `usuarios`
- Si necesitas datos para otro usuario, actualiza el valor `id_usuario` en el SQL o en el script PHP
- Puedes ejecutar el script múltiples veces para agregar más datos (o comenta las líneas `DELETE` si quieres limpiar primero)
