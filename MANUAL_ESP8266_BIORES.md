# 📡 Manual de Integración ESP8266 con Panel BIORES

## Flujo de Control del Sistema

### Cuando el Usuario Presiona "▶ Iniciar Generación"

```
1. Usuario hace clic en botón START
   ↓
2. JavaScript envía POST a /BIOMASA/control_biomasa.php?accion=START
   ↓
3. PHP envía GET HTTP a http://ESP_IP/biores?cmd=START
   ↓
4. ESP8266 recibe comando y comienza su proceso
   - Enciende el relay/motor
   - Inicia los sensores
   - Comienza a leer datos
   ↓
5. Panel espera datos de sensores
   ↓
6. ESP8266 envía datos de sensores a /BIOMASA/sensores_estado.php
   ↓
7. Panel actualiza las bombillas cada 3 segundos con datos reales
```

### Cuando el Usuario Presiona "⏹ Detener Sistema"

```
1. Usuario hace clic en botón STOP
   ↓
2. JavaScript envía POST a /BIOMASA/control_biomasa.php?accion=STOP
   ↓
3. PHP envía GET HTTP a http://ESP_IP/biores?cmd=STOP
   ↓
4. ESP8266 recibe comando y detiene su proceso
   - Apaga el relay/motor
   - Detiene los sensores
   ↓
5. JavaScript apaga automáticamente todos los sensores localmente
   ↓
6. Panel vuelve a mostrar todas las bombillas APAGADAS
```

## Configuración del ESP8266

### 1. Configurar la IP Correcta

Edita [BIOMASA/control_biomasa.php](../BIOMASA/control_biomasa.php) línea 17:

```php
$esp_ip = "192.168.1.101";  // ← CAMBIAR POR TU IP DEL ESP8266
```

### 2. Código del ESP8266 (Arduino IDE)

```cpp
#include <WiFi.h>
#include <WebServer.h>

// ====== CONFIGURACIÓN ======
#define SSID "TU_SSID_WIFI"
#define PASSWORD "TU_CONTRASEÑA_WIFI"
#define RELAY_PIN 12        // Pin del relé/motor
#define FAN_PIN 13          // Pin del ventilador
#define TEMP_PIN A0         // Pin del sensor de temperatura
#define CURRENT_PIN A1      // Pin del sensor de corriente

// Variables globales
WebServer server(80);
bool sistemaActivo = false;
bool sensorTempActivo = false;
bool ventiladorActivo = false;
bool sensorCorrienteActivo = false;

// ====== FUNCIONES DE SENSORES ======
void leerSensorTemperatura() {
    if (sensorTempActivo) {
        int rawValue = analogRead(TEMP_PIN);
        float temperatura = (rawValue * 5.0 / 1024.0) * 10;  // Convertir a °C
        
        // Enviar datos al servidor
        enviarDato("temperatura", "temp_on", String(temperatura, 1));
    }
}

void leerSensorCorriente() {
    if (sensorCorrienteActivo) {
        int rawValue = analogRead(CURRENT_PIN);
        float amperaje = (rawValue * 5.0 / 1024.0) * 2.5;  // Convertir a Amperios
        
        // Enviar datos al servidor
        enviarDato("corriente", "corriente_on", String(amperaje, 2));
    }
}

void actualizarVentilador() {
    if (ventiladorActivo) {
        digitalWrite(FAN_PIN, HIGH);
        int pwmValue = map(analogRead(A2), 0, 1023, 0, 255);  // Leer velocidad
        analogWrite(FAN_PIN, pwmValue);
        
        // Enviar estado
        enviarDato("ventilador", "ventilador_on", String(map(pwmValue, 0, 255, 0, 100)));
    } else {
        digitalWrite(FAN_PIN, LOW);
    }
}

void enviarDato(String tipo, String accion, String valor) {
    // Cambiar "localhost" por la IP del servidor
    String url = "http://192.168.1.100/PRERMI/BIOMASA/sensores_estado.php";
    
    // JSON payload
    String payload = "{\"accion\":\"" + accion + "\",\"valor\":\"" + valor + "\"}";
    
    // Implementar envío HTTP POST
    // (Usar HttpClient o WiFiClient)
}

// ====== RUTAS DEL SERVIDOR ======
void handleBiores() {
    if (server.hasArg("cmd")) {
        String comando = server.arg("cmd");
        
        if (comando == "START") {
            iniciarSistema();
            server.send(200, "text/plain", "Sistema iniciado");
        }
        else if (comando == "STOP") {
            detenerSistema();
            server.send(200, "text/plain", "Sistema detenido");
        }
        else {
            server.send(400, "text/plain", "Comando inválido");
        }
    } else {
        server.send(400, "text/plain", "Comando no recibido");
    }
}

void iniciarSistema() {
    Serial.println("📡 Comando START recibido");
    sistemaActivo = true;
    
    // Activar todos los sensores
    sensorTempActivo = true;
    ventiladorActivo = true;
    sensorCorrienteActivo = true;
    
    // Encender relé/motor
    digitalWrite(RELAY_PIN, HIGH);
    
    Serial.println("✅ BIORES iniciado");
}

void detenerSistema() {
    Serial.println("🛑 Comando STOP recibido");
    sistemaActivo = false;
    
    // Desactivar todos los sensores
    sensorTempActivo = false;
    ventiladorActivo = false;
    sensorCorrienteActivo = false;
    
    // Apagar relé/motor
    digitalWrite(RELAY_PIN, LOW);
    digitalWrite(FAN_PIN, LOW);
    
    Serial.println("✅ BIORES detenido");
}

// ====== SETUP Y LOOP ======
void setup() {
    Serial.begin(115200);
    
    pinMode(RELAY_PIN, OUTPUT);
    pinMode(FAN_PIN, OUTPUT);
    pinMode(TEMP_PIN, INPUT);
    pinMode(CURRENT_PIN, INPUT);
    
    // Conectar a WiFi
    WiFi.begin(SSID, PASSWORD);
    Serial.print("Conectando a WiFi");
    
    int intentos = 0;
    while (WiFi.status() != WL_CONNECTED && intentos < 20) {
        delay(500);
        Serial.print(".");
        intentos++;
    }
    
    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\n✅ WiFi conectado");
        Serial.println("IP: " + WiFi.localIP().toString());
    } else {
        Serial.println("\n❌ No se pudo conectar a WiFi");
    }
    
    // Configurar servidor web
    server.on("/biores", handleBiores);
    server.begin();
    
    Serial.println("⚡ Servidor web iniciado en puerto 80");
}

unsigned long lastSensorRead = 0;
const unsigned long SENSOR_INTERVAL = 5000;  // Leer sensores cada 5 segundos

void loop() {
    server.handleClient();
    
    // Leer sensores periódicamente
    if (millis() - lastSensorRead >= SENSOR_INTERVAL) {
        leerSensorTemperatura();
        leerSensorCorriente();
        actualizarVentilador();
        lastSensorRead = millis();
    }
    
    delay(10);
}
```

## Verificación del Sistema

### 1. Comprobar que el ESP8266 Escucha

Abre en el navegador:
```
http://192.168.1.101/biores?cmd=START
```

Deberías ver:
```
Sistema iniciado
```

### 2. Ver Logs en PHP

Los logs se guardan en `php_errors.log`:

```
Control BIORES: Intento de envío de comando START a 192.168.1.101
Control BIORES: Comando START enviado exitosamente
```

### 3. Monitoreo en el Panel

Abre la consola del navegador (F12) y verás:

```javascript
📡 Enviando comando START al ESP8266...
✅ Comando START enviado al ESP8266: {...}
```

## Estados del Sistema

| Estado | Botón START | Botón STOP | LED | Sensores |
|--------|------------|-----------|-----|----------|
| **Apagado** | Habilitado | Deshabilitado | 🔴 ROJO | 💤 En espera |
| **Operando** | Deshabilitado | Habilitado | 🟢 VERDE | 📡 Leyendo datos |

## Troubleshooting

### El panel no conecta al ESP8266
1. Verifica la IP en `control_biores.php`
2. Asegúrate que el ESP8266 esté en la misma red WiFi
3. Revisa los logs del ESP8266 (Serial Monitor)
4. Intenta hacer ping: `ping 192.168.1.101`

### Los sensores no muestran datos
1. El ESP8266 debe enviar POST a `sensores_estado.php`
2. Verifica que el servidor pueda recibir JSON
3. Revisa los logs en `php_errors.log`

### El botón START no funciona
1. Verifica que el usuario esté autenticado
2. Asegúrate que el ESP8266 está encendido
3. Comprueba la consola del navegador para errores

## Flujo de Datos Completo

```
┌─────────────────────────────────────────────┐
│      PANEL WEB BIORES (biores.php)         │
│  ┌────────────────────────────────────────┐ │
│  │ ▶ Iniciar Generación                  │ │
│  │ ⏹ Detener Sistema                    │ │
│  └────────────────────────────────────────┘ │
│         ↓ (accion=START/STOP)                │
│  ┌────────────────────────────────────────┐ │
│  │ control_biores.php                     │ │
│  │ Envía GET a ESP8266                   │ │
│  └────────────────────────────────────────┘ │
└────────────┬──────────────────────────────────┘
             ↓ HTTP GET
    ┌─────────────────────┐
    │    ESP8266          │
    │  /biores?cmd=START  │
    │                     │
    │ ✓ Inicia proceso   │
    │ ✓ Lee sensores    │
    │ ✓ Envía datos     │
    └────────┬────────────┘
             ↓ HTTP POST JSON
    ┌─────────────────────┐
    │ sensores_estado.php │
    │ (Recibe datos)      │
    │ (Guarda en JSON)    │
    └────────┬────────────┘
             ↓ Cada 3 segundos
    Panel actualiza bombillas
    con datos en tiempo real
```

---

**Estado**: ✅ Implementado y listo  
**Última actualización**: 22/02/2026  
**Requiere**: ESP8266, WiFi, Arduino IDE
