# ⚡ GUÍA RÁPIDA DE CAMBIOS COMUNES

## 🔌 CAMBIOS DE PINES RÁPIDOS

### Cambiar Pin del Relé Calentador

**Archivo:** `src/main.cpp` - Línea ~18

```cpp
// ANTES:
#define RELAY_HEATER     D1  // GPIO5

// DESPUÉS (para D0/GPIO16):
#define RELAY_HEATER     D0  // GPIO16
```

**Mapeo completo de pines NodeMCU:**
```
D0 = GPIO16
D1 = GPIO5
D2 = GPIO4
D3 = GPIO0
D4 = GPIO2
D5 = GPIO14
D6 = GPIO12
D7 = GPIO13
D8 = GPIO15
```

---

## 🌡️ CAMBIOS DE CONFIGURACIÓN COMÚN

### 1️⃣ Activar Ventilador a Diferente Temperatura

**Archivo:** `src/main.cpp` - Línea ~62

```cpp
// ANTES (30°C):
const float TEMP_THRESHOLD = 30.0;

// DESPUÉS (cambia el número):
const float TEMP_THRESHOLD = 25.0;  // 25°C
const float TEMP_THRESHOLD = 35.0;  // 35°C
const float TEMP_THRESHOLD = 40.0;  // 40°C
```

---

### 2️⃣ Cambiar Nombre de Red WiFi

**Archivo:** `src/main.cpp` - Línea ~56

```cpp
// ANTES:
const char* ssid = "BIOMASA_SYSTEM";

// DESPUÉS:
const char* ssid = "MI_RED_BIOMASA";
```

---

### 3️⃣ Cambiar Contraseña WiFi

**Archivo:** `src/main.cpp` - Línea ~57

```cpp
// ANTES:
const char* password = "biomasa2026";

// DESPUÉS:
const char* password = "nueva_password_123";
```

---

### 4️⃣ Cambiar Contraseña Admin Panel

**Archivo:** `src/main.cpp` - Línea ~58

```cpp
// ANTES:
const char* admin_password = "ADMIN_PRERMI";

// DESPUÉS:
const char* admin_password = "MI_PASSWORD_SUPER_SECRETO";
```

---

### 5️⃣ Cambiar Sensor de Corriente (ACS712)

**Sensibilidades por modelo:**

```cpp
// ACS712 5A (0.185 V/A) - DEFECTO
const float CURRENT_SENSITIVITY = 0.185;

// ACS712 20A (0.1 V/A)
const float CURRENT_SENSITIVITY = 0.100;

// ACS712 30A (0.066 V/A)
const float CURRENT_SENSITIVITY = 0.066;
```

---

### 6️⃣ Cambiar Dirección I2C del OLED

**Archivo:** `src/main.cpp` - Línea ~47

```cpp
// ANTES (dirección 0x3C):
#define OLED_ADDR     0x3C

// DESPUÉS (dirección 0x3D):
#define OLED_ADDR     0x3D
```

**¿Cómo saber tu dirección?**
Corre este código de prueba:

```cpp
#include <Wire.h>

void setup() {
  Serial.begin(115200);
  Wire.begin(D7, D8); // SDA, SCL
  
  Serial.println("Buscando dispositivos I2C...");
  for(int addr = 1; addr < 127; addr++) {
    Wire.beginTransmission(addr);
    if (Wire.endTransmission() == 0) {
      Serial.print("Encontrado en: 0x");
      Serial.println(addr, HEX);
    }
  }
}

void loop() {}
```

---

## 🌡️ CAMBIAR TIPO DE SENSOR TEMPERATURA

### Opción 1: DS18B20 (OneWire) - POR DEFECTO

```cpp
#include <OneWire.h>
#include <DallasTemperature.h>

OneWire oneWire(TEMP_SENSOR_PIN);
DallasTemperature sensors(&oneWire);

void readTemperature() {
  sensors.requestTemperatures();
  currentTemperature = sensors.getTempCByIndex(0);
}
```

---

### Opción 2: DHT22 (Temperatura + Humedad)

**Paso 1:** Editar `platformio.ini`

```ini
lib_deps = 
    adafruit/Adafruit SSD1306@^2.5.7
    adafruit/Adafruit GFX Library@^1.11.5
    paulstoffregen/OneWire@^2.3.8
    milesburton/DallasTemperature@^3.11.0
    adafruit/DHT sensor library@^1.4.4  ; AGREGAR ESTA LÍNEA
    esp8266/ESP8266WiFi@^1.0
    ESP8266WebServer
    LittleFS
```

**Paso 2:** Reemplazar en `main.cpp`

```cpp
// ELIMINAR:
#include <OneWire.h>
#include <DallasTemperature.h>
OneWire oneWire(TEMP_SENSOR_PIN);
DallasTemperature sensors(&oneWire);

// AGREGAR:
#include <DHT.h>
#define DHTTYPE DHT22
DHT dht(TEMP_SENSOR_PIN, DHTTYPE);

float currentHumidity = 0.0;  // Variable global nueva
```

**Paso 3:** Actualizar `setup()`

```cpp
// REEMPLAZAR:
// sensors.begin();

// CON:
dht.begin();
```

**Paso 4:** Actualizar función `readTemperature()`

```cpp
void readTemperature() {
  float humidity = dht.readHumidity();
  float temperature = dht.readTemperature();
  
  if (isnan(humidity) || isnan(temperature)) {
    Serial.println("Error leyendo DHT");
    return;
  }
  
  currentTemperature = temperature;
  currentHumidity = humidity;
}
```

**Paso 5:** Mostrar humedad en OLED (opcional)

```cpp
void updateDisplay() {
  display.clearDisplay();
  // ... código anterior ...
  
  // AGREGAR ESTA LÍNEA:
  display.print("Hum: ");
  display.print(currentHumidity);
  display.println(" %");
  
  // ... resto del código ...
}
```

---

### Opción 3: LM35 (Analógico)

```cpp
void readTemperature() {
  int rawValue = analogRead(TEMP_SENSOR_PIN); // A0
  float voltage = (rawValue / 1023.0) * 3.3;
  currentTemperature = voltage * 100.0;  // LM35: 10mV por °C
}
```

---

## ⏱️ CAMBIAR INTERVALO DE LECTURA

**Archivo:** `src/main.cpp` - Loop principal

```cpp
// ANTES (1000ms):
static unsigned long lastSensorRead = 0;
if (millis() - lastSensorRead >= 1000) {

// DESPUÉS (500ms):
if (millis() - lastSensorRead >= 500) {

// DESPUÉS (2000ms = 2 segundos):
if (millis() - lastSensorRead >= 2000) {
```

---

## 📱 AGREGAR FUNCIONALIDAD AL INTERFACE WEB

### Agregar Botón para Toggle Ventilador Manual

En la función `getWebPage()`, busca:

```javascript
function stopSystem() {
```

Agrega antes:

```javascript
function toggleFan() {
    fetch('/control?action=togglefan')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateData();
            }
        });
}
```

Y en la sección HTML, agrega botón:

```html
<button class="btn-toggle" onclick="toggleFan()">🌀 TOGGLE VENTILADOR</button>
```

Y en `main.cpp` en la función `handleControl()`, agrega:

```cpp
else if (action == "togglefan") {
    if (fanState == 0) {
        activateFan();
    } else {
        deactivateFan();
    }
    success = true;
}
```

---

## 📊 CAMBIAR LÍMITES DE VISUALIZACIÓN GRÁFICOS

### Agregar Máximos y Mínimos en la Página

Agrega variables globales:

```cpp
float maxTemperature = 0.0;
float minTemperature = 100.0;
float maxCurrent = 0.0;
```

Actualiza en `loop()`:

```cpp
readTemperature();
if (currentTemperature > maxTemperature) {
  maxTemperature = currentTemperature;
}
if (currentTemperature < minTemperature) {
  minTemperature = currentTemperature;
}
```

Retorna en `/api`:

```cpp
void handleAPI() {
  String json = "{";
  json += "\"temperature\":" + String(currentTemperature) + ",";
  json += "\"maxTemp\":" + String(maxTemperature) + ",";
  json += "\"minTemp\":" + String(minTemperature) + ",";
  // ... resto ...
}
```

---

## 🔐 ACTIVAR/DESACTIVAR COMPONENTES

### Desactivar Ventilador Automático (Solo Manual)

En `main.cpp` `loop()`, comenta esta línea:

```cpp
// if (currentTemperature > TEMP_THRESHOLD) {
//   if (fanState == 0) {
//     activateFan();
//   }
// }
```

---

### Activar Calentador Solo en Horas Específicas

```cpp
void loop() {
  time_t now = time(nullptr);
  struct tm* timeinfo = localtime(&now);
  int hour = timeinfo->tm_hour;
  
  if (systemState == 1) {
    if (hour >= 6 && hour <= 22) {  // Solo 6 AM a 10 PM
      activateHeater();
    } else {
      deactivateHeater();
    }
  }
}
```

---

## 🎨 CAMBIAR ESTILOS DE PÁGINA WEB

### Cambiar Color de Fondo Principal

En `getWebPage()`, busca:

```css
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

Cambia a:

```css
body {
    background: linear-gradient(135deg, #FF6B6B 0%, #FF8E72 100%);  // Rojo/Naranja
}
```

**Otras paletas:**
```css
/* Verde */
background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);

/* Azul */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Naranja */
background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
```

---

## 🔧 DEBUGGING Y PRUEBAS

### Ver Logs en Serial Monitor

```
Baud Rate: 115200
Databits: 8
Parity: None
Stopbits: 1
```

### Comando para resetear ESP8266

```cpp
// Agrega este botón en la web:
<button onclick="reset()">🔄 RESET</button>

// Función JavaScript:
function reset() {
  fetch('/control?action=reset')
    .then(() => {
      alert('Reseteando...');
      setTimeout(() => location.reload(), 2000);
    });
}

// En handleControl():
else if (action == "reset") {
  success = true;
  delay(1000);
  ESP.restart();
}
```

---

## 💾 GUARDAR DATOS EN MEMORIA (EEPROM)

Agregar persistencia de datos:

```cpp
#include <EEPROM.h>

void saveEnergy() {
  EEPROM.put(0, energyGenerated);
  EEPROM.commit();
}

void loadEnergy() {
  EEPROM.get(0, energyGenerated);
}
```

En `setup()`:
```cpp
EEPROM.begin(512);
loadEnergy();
```

En `loop()` cada minuto:
```cpp
static unsigned long lastSave = 0;
if (millis() - lastSave >= 60000) {  // Cada minuto
  saveEnergy();
  lastSave = millis();
}
```

---

## 🚀 TIPS DE PRODUCTIVIDAD

### 1. Compilar Rápido
```
Ctrl + Alt + B  (PlatformIO: Build)
```

### 2. Upload Rápido
```
Ctrl + Alt + U  (PlatformIO: Upload)
```

### 3. Abrir Serial Monitor
```
Ctrl + Alt + S  (PlatformIO: Serial Monitor)
```

### 4. Device Monitor (Mejor que Serial Monitor)
```
Ctrl + Alt + J  (PlatformIO: Device Monitor)
```

---

## 📞 CONTACTO Y SOPORTE

- Revisa los logs del Serial Monitor
- Verifica todas las conexiones físicas
- Comprueba voltajes con multímetro
- Lee la documentación completa en `DOCUMENTACION.md`

