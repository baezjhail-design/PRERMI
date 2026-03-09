# 📡 Sistema de Monitoreo de Sensores BIORES - Guía Completa

## Descripción General

Se ha implementado un sistema interactivo de monitoreo de sensores en el panel BIORES que permite visualizar el estado de:

- 🌡️ **Sensor de Temperatura**: Muestra el estado (Activo/Apagado) y la temperatura actual
- ❄️ **Ventilador**: Muestra si el ventilador de enfriamiento está activo/inactivo y su velocidad
- ⚡ **Sensor de Corriente**: Muestra si está sensando datos y la corriente en Amperios

## Características

✅ **Bombillas Visuales**:
- 🔴 **ROJO/APAGADO**: Sin brillo, sensor desactivado
- 🟡 **NARANJA/SENSANDO**: Efecto pulsante, sensor leyendo datos
- 🟢 **VERDE/ACTIVO**: Efecto de brillo intenso, funcionando normalmente

✅ **Actualización Automática**:
- Se actualizan cada 3 segundos
- No requiere recargar la página
- Timestamps sincronizados

✅ **Diseño Responsivo**:
- Adapta a móviles, tablets y escritorio
- Grid flexible de 3 columnas
- Cards con sombras y animaciones suaves

## Archivos Involucrados

### 1. **web/admin/biores.php** (MODIFICADO)
- Sección visual de sensores con bombillas
- estilos CSS animados para las bombillas
- JavaScript para actualizar estados en tiempo real

### 2. **BIOMASA/sensores_estado.php** (NUEVO)
- API RESTful para GET/POST de estado de sensores
- Almacena datos en JSON
- Interfaz para ESP8266 y administrador

### 3. **data/sensores/estado.json** (NUEVO)
- Archivo de almacenamiento del estado actual
- Se actualiza con cada cambio de sensor

## Integración con ESP8266

### Opción 1: Envío Manual desde ESP8266

El ESP8266 debe enviar POST con los datos de los sensores:

```cpp
#include <WiFi.h>
#include <HTTPClient.h>

#define TEMP_SENSOR_PIN A0
#define FAN_PIN 12
#define CURRENT_SENSOR_PIN A1

void enviarEstadoSensores() {
    HTTPClient http;
    String url = "http://localhost:8080/PRERMI/BIOMASA/sensores_estado.php";
    
    // Leer sensores
    int tempSegura = analogRead(TEMP_SENSOR_PIN);
    float temperatura = (tempSegura * 100.0) / 1024.0; // Convertir a °C
    
    bool ventiladorActivo = digitalRead(FAN_PIN);
    
    int corriente = analogRead(CURRENT_SENSOR_PIN);
    float amperaje = (corriente * 5.0) / 1024.0; // Convertir a A
    
    // Preparar JSON
    String payload = "{\"accion\":\"temp_on\",\"valor\":\"" + String(temperatura, 1) + "\"}";
    
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    int httpCode = http.POST(payload);
    
    if (httpCode == 200) {
        Serial.println("Sensores enviados correctamente");
    } else {
        Serial.println("Error: " + String(httpCode));
    }
    
    http.end();
}

void setup() {
    Serial.begin(115200);
    WiFi.begin("SSID", "PASSWORD");
    
    pinMode(FAN_PIN, OUTPUT);
    pinMode(TEMP_SENSOR_PIN, INPUT);
    pinMode(CURRENT_SENSOR_PIN, INPUT);
    
    Timer.setInterval(5000, enviarEstadoSensores); // Cada 5 segundos
}

void loop() {
    Timer.run();
}
```

### Opción 2: Script de Prueba desde Administrador

Se puede usar un script para simular datos de sensores (para testing):

```php
<?php
// test_sensores.php - Para testing sin ESP8266

$datos = [
    "temperatura" => [
        "estado" => "activo",
        "valor" => "45.2",
        "timestamp" => date('Y-m-d H:i:s')
    ],
    "ventilador" => [
        "estado" => "activo",
        "valor" => "85",
        "timestamp" => date('Y-m-d H:i:s')
    ],
    "corriente" => [
        "estado" => "sensando",
        "valor" => "12.5",
        "timestamp" => date('Y-m-d H:i:s')
    ]
];

$json = json_encode($datos, JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/data/sensores/estado.json', $json);

echo "Sensores actualizados correctamente";
?>
```

## API Reference

### GET `/PRERMI/BIOMASA/sensores_estado.php`

**Obtener estado actual de todos los sensores:**

```bash
curl http://localhost:8080/PRERMI/BIOMASA/sensores_estado.php
```

**Respuesta:**
```json
{
    "status": "ok",
    "data": {
        "temperatura": {
            "estado": "activo",
            "valor": "45.2",
            "timestamp": "2026-02-22 14:30:15"
        },
        "ventilador": {
            "estado": "activo",
            "valor": "85",
            "timestamp": "2026-02-22 14:30:15"
        },
        "corriente": {
            "estado": "sensando",
            "valor": "12.5",
            "timestamp": "2026-02-22 14:30:15"
        }
    }
}
```

### POST `/PRERMI/BIOMASA/sensores_estado.php`

**Actualizar estado de un sensor:**

```json
{
    "accion": "temp_on",
    "valor": "45.2"
}
```

**Acciones disponibles:**
- `temp_on`: Activar sensor temperatura (incluir `valor`)
- `temp_off`: Desactivar sensor temperatura
- `ventilador_on`: Activar ventilador (incluir `valor` para velocidad)
- `ventilador_off`: Desactivar ventilador
- `corriente_on`: Activar sensor corriente (incluir `valor` para Amperios)
- `corriente_off`: Desactivar sensor corriente
- `all_off`: Desactivar todos los sensores

**Ejemplo con cURL:**
```bash
curl -X POST http://localhost:8080/PRERMI/BIOMASA/sensores_estado.php \
  -H "Content-Type: application/json" \
  -d '{"accion":"temp_on","valor":"42.5"}'
```

## Estados de los Sensores

### Sensor de Temperatura
```
Estado: "apagado" → Bombilla ROJA sin brillo
Estado: "activo"  → Bombilla AMARILLA con efecto de brillo
Valor: Temperatura en °C
```

### Ventilador
```
Estado: "apagado" → Bombilla ROJA sin brillo
Estado: "activo"  → Bombilla VERDE con efecto de brillo
Valor: Velocidad en % (0-100)
```

### Sensor de Corriente
```
Estado: "apagado"   → Bombilla ROJA sin brillo
Estado: "sensando"  → Bombilla NARANJA con efecto pulsante
Valor: Corriente en Amperios (A)
```

## Estructura del JSON de Estado

```json
{
    "temperatura": {
        "estado": "activo|apagado",
        "valor": "45.2",
        "timestamp": "2026-02-22 14:30:15"
    },
    "ventilador": {
        "estado": "activo|apagado",
        "valor": "85",
        "timestamp": "2026-02-22 14:30:15"
    },
    "corriente": {
        "estado": "sensando|apagado",
        "valor": "12.5",
        "timestamp": "2026-02-22 14:30:15"
    }
}
```

## Flujo de Operación

1. **Panel BIORES carga**: Todos los sensores muestran estado "Apagado" (Bombillas ROJAS)
2. **Usuario inicia sistema**: Presiona botón "Iniciar Generación"
3. **ESP8266 inicia**: Enciende sensores y comienza a enviar datos
4. **API recibe datos**: `sensores_estado.php` actualiza el JSON
5. **Panel se actualiza**: Cada 3 segundos, JavaScript carga el estado y actualiza bombillas
6. **Visualización dinámica**:
   - Bombillas cambian de color según estado
   - Valores se actualizan en tiempo real
   - Timestamps muestran cuándo fue la última lectura

## Configuración de Colores y Efectos

### Bombilla Apagada (Todos los sensores)
```css
background: linear-gradient(135deg, #888, #444);
box-shadow: inset 0 -5px 15px rgba(0,0,0,0.5);
```

### Bombilla Activa (Temperatura, Ventilador)
```css
background: linear-gradient(135deg, #ffeb3b, #ffc107);
animation: bombilla-brillo 2s ease-in-out infinite;
box-shadow: 0 0 40px #ffc107;
```

### Bombilla Sensando (Corriente)
```css
background: linear-gradient(135deg, #ff9800, #f57c00);
animation: bombilla-pulso 1.5s ease-in-out infinite;
box-shadow: 0 0 40px #ff9800;
```

## Testing sin ESP8266

Para probar el sistema sin tener un ESP8266 real, puedes usar JavaScript en la consola del navegador:

```javascript
// Simular temperatura activa
fetch('/PRERMI/BIOMASA/sensores_estado.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({accion: 'temp_on', valor: '42.5'})
});

// Simular ventilador activo
fetch('/PRERMI/BIOMASA/sensores_estado.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({accion: 'ventilador_on', valor: '75'})
});

// Simular corriente sensando
fetch('/PRERMI/BIOMASA/sensores_estado.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({accion: 'corriente_on', valor: '8.3'})
});

// Apagar todo
fetch('/PRERMI/BIOMASA/sensores_estado.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({accion: 'all_off'})
});
```

## Troubleshooting

### Las bombillas no cambian de color
- Verifica que JavaScript esté habilitado
- Revisa la consola del navegador (F12)
- Comprueba que `sensores_estado.php` responde correctamente

### Los datos no se actualizan
- Verifica que el archivo `data/sensores/estado.json` existe y es escribible
- Valida los permisos de carpeta: `chmod 777 data/sensores`
- Revisa que el ESP8266 esté enviando datos correctamente

### El ESP8266 no se conecta
- Verifica la URL en el código del ESP8266
- Asegúrate de que está en la misma red que el servidor
- Prueba la conectividad: `ping localhost` desde la máquina del servidor

## Próximas Mejoras Opcionales

- [ ] Historial de datos de sensores
- [ ] Gráficos en tiempo real de temperatura y corriente
- [ ] Alertas por umbral de temperatura
- [ ] Control automático del ventilador
- [ ] Exportar datos a CSV
- [ ] Calibración de sensores desde el panel

---

**Estado**: ✅ Implementado y listo para usar
**Última actualización**: 22/02/2026
**Requiere**: PHP 7.0+, permisos de escritura en `/data/sensores/`
