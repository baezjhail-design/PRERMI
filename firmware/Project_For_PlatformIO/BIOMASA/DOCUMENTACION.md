# 📚 DOCUMENTACIÓN COMPLETA - SISTEMA BIOMASA

## 🎯 Descripción General

El sistema BIOMASA es un controlador IoT basado en **ESP8266MOD** que gestiona una celda Peltier generadora de energía con monitoreo en tiempo real. El sistema incluye:

- ✅ Control local via botones físicos
- ✅ Control remoto via página web SCADA
- ✅ Monitoreo de temperatura, corriente y energía
- ✅ Pantalla OLED para información local
- ✅ Red WiFi embebida para acceso remoto
- ✅ Seguridad administrativa con contraseña

---

## 📋 TABLA DE CONTENIDOS

1. [Configuración de Hardware](#configuración-de-hardware)
2. [Descripción de Pines](#descripción-de-pines)
3. [Explicación del Código](#explicación-del-código)
4. [Variables I Constantes Principales](#variables-y-constantes-principales)
5. [Funciones Principales](#funciones-principales)
6. [Lógica de Control](#lógica-de-control)
7. [Interfaz Web SCADA](#interfaz-web-scada)
8. [Cómo Modificar el Código](#cómo-modificar-el-código)

---

## 🔧 Configuración de Hardware

### Componentes Necesarios:

```
ESP8266MOD (NodeMCU)
│
├── Display OLED 128x64 (I2C)
│   ├── SDA → D7 (GPIO13)
│   └── SCL → D8 (GPIO15)
│
├── Sensor Temperatura (DS18B20 / OneWire)
│   └── Data → D4 (GPIO2)
│
├── Sensor de Corriente (ACS712 5A)
│   └── AN0 → A0 (ADC)
│
├── Optocoplador Calentador PTC
│   └── Entrada → D1 (GPIO5)
│
├── Optocoplador Ventilador DC
│   └── Entrada → D2 (GPIO4)
│
├── Botón START
│   └── → D5 (GPIO14)
│
└── Botón STOP
    └── → D6 (GPIO12)
```

### Esquema de Conexión I2C (OLED):

```
ESP8266      OLED
GND     ─────────  GND
3.3V    ─────────  VCC
D7      ─────────  SDA
D8      ─────────  SCL
```

### Esquema Sensor de Temperatura (OneWire):

```
ESP8266 ─ 4.7kΩ ─ VCC
D4      ──────────────  DS18B20 Data
GND  ──────────────  GND
```

### Esquema Sensor de Corriente (ACS712):

```
ACS712
VCC  ─── 5V
GND  ─── GND
OUT  ─── A0 (con capacitor de 100nF a GND)
```

---

## 📍 Descripción de Pines

| Pin  | GPIO  | Función              | Tipo     | Descripción                                |
|------|-------|----------------------|----------|-------------------------------------------|
| D1   | 5     | RELAY_HEATER         | OUTPUT   | Optocoplador calentador PTC               |
| D2   | 4     | RELAY_FAN            | OUTPUT   | Optocoplador ventilador DC                |
| D4   | 2     | TEMP_SENSOR_PIN      | INPUT    | Sensor de temperatura (OneWire)           |
| D5   | 14    | BUTTON_START         | INPUT    | Botón físico START                        |
| D6   | 12    | BUTTON_STOP          | INPUT    | Botón físico STOP                         |
| A0   | ADC0  | CURRENT_SENSOR       | INPUT    | Sensor de corriente (analógico)           |
| D7   | 13    | OLED_SDA             | I2C      | Display OLED (datos)                      |
| D8   | 15    | OLED_SCL             | I2C      | Display OLED (reloj)                      |

---

## 💻 Explicación del Código

### SECCIÓN 1: Includes y Configuración

```cpp
#include <Arduino.h>
#include <ESP8266WiFi.h>          // Control WiFi
#include <ESP8266WebServer.h>     // Servidor HTTP
#include <Wire.h>                 // Comunicación I2C
#include <Adafruit_SSD1306.h>     // Display OLED
#include <OneWire.h>              // Protocolo OneWire
#include <DallasTemperature.h>    // Sensor de Temperatura
#include <LittleFS.h>             // Sistema de archivos
```

**¿Por qué?** Estas librerías proporcionan las funcionalidades necesarias para:
- Conectar a WiFi y crear un servidor web
- Comunicarse con dispositivos I2C (OLED)
- Leer temperaturas via OneWire
- Acceder al sistema de archivos

### SECCIÓN 2: Definición de Pines

```cpp
#define RELAY_HEATER     D1       // Pin para calentador
#define RELAY_FAN        D2       // Pin para ventilador
#define BUTTON_START     D5       // Pin para botón de inicio
#define BUTTON_STOP      D6       // Pin para botón de parada
#define TEMP_SENSOR_PIN  D4       // Pin para sensor de temperatura
#define CURRENT_SENSOR   A0       // Pin analógico para sensor de corriente
```

**¿Por qué usar #define?** Nos permite cambiar los pines en un mismo lugar sin buscar por todo el código.

### SECCIÓN 3: Inicialización de Objetos

```cpp
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
OneWire oneWire(TEMP_SENSOR_PIN);
DallasTemperature sensors(&oneWire);
ESP8266WebServer server(80);  // Puerto HTTP
```

Estos objetos representan nuestros dispositivos y se usan para comunicarnos con ellos.

### SECCIÓN 4: Variables Globales

```cpp
float currentTemperature = 0.0;    // Temperatura actual en °C
float currentCurrent = 0.0;         // Corriente en Amperios
float energyGenerated = 0.0;        // Energía acumulada en Wh
int systemState = 0;               // 0=STOP, 1=RUNNING
int heaterState = 0;               // 0=OFF, 1=ON
int fanState = 0;                  // 0=OFF, 1=ON
```

Estas variables se actualizan continuamente y son accesibles desde cualquier función.

---

## 📊 Variables Y Constantes Principales

### Constantes Configurables:

```cpp
const char* ssid = "BIOMASA_SYSTEM";           
// Nombre de la red WiFi que crea el ESP8266

const char* password = "biomasa2026";          
// Contraseña para conectarse a la red WiFi

const char* admin_password = "ADMIN_PRERMI";   
// Contraseña administrativa (cambiar según sea necesario)

const float TEMP_THRESHOLD = 30.0;
// Temperatura (°C) para activar automáticamente el ventilador
// Si quieres activar el ventilador a 25°C, cambia a 25.0

const float VOLTAGE_REFERENCE = 3.3;
// Voltaje máximo del ADC del ESP8266

const float CURRENT_SENSITIVITY = 0.185;
// Sensibilidad del sensor ACS712 5A en V/A
// Para ACS712 30A, cambiar a 0.066
```

**¿Cómo modificar constantes?**
- Para cambiar el SSID WiFi, edita la línea `const char* ssid = "BIOMASA_SYSTEM";`
- Para cambiar la temperatura de ventilador a 25°C: `const float TEMP_THRESHOLD = 25.0;`
- Para cambiar la contraseña admin: `const char* admin_password = "MI_NUEVA_PASSWORD";`

---

## 🔧 Funciones Principales

### 1. `initializeSystem()`

```cpp
void initializeSystem() {
  Serial.begin(115200);         // Iniciar puerto serial
  Wire.begin(OLED_SDA, OLED_SCL); // Iniciar I2C
  display.begin(SSD1306_SWITCHCAPVCC, OLED_ADDR); // Iniciar OLED
  pinMode(RELAY_HEATER, OUTPUT); // Configurar pines como salidas
  pinMode(RELAY_FAN, OUTPUT);
  digitalWrite(RELAY_HEATER, LOW); // Apagar relés
  digitalWrite(RELAY_FAN, LOW);
  sensors.begin();               // Iniciar sensor de temperatura
}
```

**¿Qué hace?** Prepara todos los dispositivos al inicio. Se ejecuta solo una vez en `setup()`.

### 2. `readTemperature()`

```cpp
void readTemperature() {
  sensors.requestTemperatures(); // Solicitar temperatura
  currentTemperature = sensors.getTempCByIndex(0); // Obtener valor en °C
}
```

**¿Qué hace?** Lee el sensor de temperatura y guarda el valor en `currentTemperature`.

**Cambiar sensor:** Si usas DHT22, reemplaza con:
```cpp
float humidity = dht.readHumidity();
currentTemperature = dht.readTemperature();
```

### 3. `readCurrentSensor()`

```cpp
void readCurrentSensor() {
  int rawValue = analogRead(CURRENT_SENSOR); // Leer ADC (0-1023)
  float voltage = (rawValue / 1023.0) * VOLTAGE_REFERENCE; // Convertir a voltios
  float offsetVoltage = VOLTAGE_REFERENCE / 2.0; // Voltage central del ACS712
  currentCurrent = (voltage - offsetVoltage) / CURRENT_SENSITIVITY; // Cálculo de corriente
  
  // Acumular energía
  unsigned long timeDiff = currentTime - lastEnergyUpdate;
  if (currentCurrent > 0) {
    energyGenerated += (currentCurrent * 12.0 * timeDiff) / 3600000.0;
  }
}
```

**¿Cómo funciona?**
1. Lee el valor analógico (0-1023)
2. Lo convierte a voltios (0-3.3V)
3. Resta el voltaje central (1.65V para sensor de 5A)
4. Divide entre la sensibilidad para obtener Amperios
5. Acumula la energía en Wh

**Fórmula de energía:** `Wh = (Corriente en A × Voltaje × Tiempo en horas)`

### 4. `activateHeater()` y `deactivateHeater()`

```cpp
void activateHeater() {
  digitalWrite(RELAY_HEATER, HIGH);  // Enviar señal alta
  heaterState = 1;
}

void deactivateHeater() {
  digitalWrite(RELAY_HEATER, LOW);   // Enviar señal baja
  heaterState = 0;
}
```

**¿Qué hace?** Activa o desactiva el optocoplador para el calentador PTC.

### 5. `activateFan()` y `deactivateFan()`

```cpp
void activateFan() {
  digitalWrite(RELAY_FAN, HIGH);
  fanState = 1;
}

void deactivateFan() {
  digitalWrite(RELAY_FAN, LOW);
  fanState = 0;
}
```

**¿Qué hace?** Controla el ventilador DC mediante optocoplador.

### 6. `setupWiFi()`

```cpp
void setupWiFi() {
  WiFi.mode(WIFI_AP_STA);           // Modo Punto de Acceso + Cliente
  WiFi.softAP(ssid, password);      // Crear red WiFi
  Serial.print("IP AP: ");
  Serial.println(WiFi.softAPIP());  // Mostrar IP (normalmente 192.168.4.1)
}
```

**¿Qué hace?** Crea una red WiFi donde puedes conectarte con tu celular o computadora.

### 7. `updateDisplay()`

```cpp
void updateDisplay() {
  display.clearDisplay();           // Limpiar pantalla
  display.println("=== BIOMASA SYSTEM ===");
  display.print("Temp: ");
  display.print(currentTemperature);
  display.println(" C");
  // ... más información ...
  display.display();               // Enviar a pantalla OLED
}
```

**¿Qué hace?** Actualiza la pantalla OLED con información en tiempo real.

**Estructura del display:**
```
=== BIOMASA SYSTEM ===

Estado: RUNNING
Temp: 35.2 C
Corr: 2.45 A
Ener: 125.3 Wh

Calentador: ON  Ventilador: ON

✓
```

---

## 🎯 Lógica de Control

### Flujo Principal del Sistema:

```
LOOP PRINCIPAL
    ↓
[Cada 1000ms]
    ↓
├─ Leer temperatura
├─ Leer corriente
├─ Evaluar: ¿systemState == 1?
│   ├─ SÍ: ¿Temperatura > 30°C?
│   │   ├─ SÍ: Activar ventilador
│   │   └─ NO: Desactivar ventilador
│   └─ NO: Apagar todo
└─ Actualizar pantalla OLED
    ↓
[Chequear botones]
    ↓
├─ ¿Botón START presionado?
│   └─ Activar calentador
├─ ¿Botón STOP presionado?
│   └─ Apagar calentador y ventilador
└─ Manejar peticiones HTTP
```

### Máquina de Estados:

```
SISTEMA:
  ├─ STOP (0)
  │   ├─ Calentador: OFF
  │   ├─ Ventilador: OFF
  │   └─ OLED: Muestra "STOPPED"
  │
  └─ RUNNING (1)
      ├─ Calentador: ON (siempre)
      ├─ Ventilador: ON/OFF (según temperatura)
      └─ OLED: Muestra "✓" (check mark)
```

---

## 🌐 Interfaz Web SCADA

### Estructura HTML:

```
┌─────────────────────────────────────────┐
│   BIOMASA SCADA System (Header)         │
├─────────────────────────────────────────┤
│                                         │
│  📊 ESTADO DEL SISTEMA                 │
│  ┌─────────────┬─────────────┐         │
│  │Temperatura  │  Corriente  │         │
│  │  35.2°C     │   2.45 A    │         │
│  ├─────────────┼─────────────┤         │
│  │Energía      │  Sistema    │         │
│  │ 125.3 Wh    │  RUNNING    │         │
│  └─────────────┴─────────────┘         │
│                                         │
│  ⚙️ CONTROL DEL SISTEMA                │
│  [▶️ INICIAR]  [⏹️ DETENER]            │
│                                         │
│  Calentador: 🟢 ON                     │
│  Ventilador: 🔴 OFF                    │
│                                         │
└─────────────────────────────────────────┘
```

### Endpoints HTTP:

#### 1. `GET /` - Página Principal

```
Devuelve: Página HTML completa con interfaz SCADA
```

#### 2. `GET /api` - JSON con Datos en Tiempo Real

```json
{
  "temperature": 35.2,
  "current": 2.45,
  "energy": 125.3,
  "system": 1,
  "heater": 1,
  "fan": 0
}
```

**Uso en JavaScript:**
```javascript
fetch('/api')
  .then(response => response.json())
  .then(data => {
    console.log('Temperatura:', data.temperature);
  });
```

#### 3. `GET /control?action=start` - Iniciar Sistema

```json
{"success": true}
```

#### 4. `GET /control?action=stop` - Detener Sistema

```json
{"success": true}
```

#### 5. `GET /control?action=admin&password=ADMIN_PRERMI` - Login Admin

```json
{"success": true}  // Si contraseña correcta
{"success": false} // Si contraseña incorrecta
```

### Panel Administrativo:

El sistema incluye un panel de login para acceso administrativo. Los usuarios deben ingresar la contraseña `ADMIN_PRERMI` (predeterminada) para acceder a funciones avanzadas.

**Cómo cambiar contraseña admin:**

En `main.cpp`, línea que dice:
```cpp
const char* admin_password = "ADMIN_PRERMI";
```

Cambiar a:
```cpp
const char* admin_password = "MI_NUEVA_PASSWORD";
```

---

## 🔧 Cómo Modificar el Código

### CAMBIO 1: Modificar Pines

**Problema:** Quiero usar GPIO16 (D0) para el relé del calentador en lugar de D1.

**Solución:**

1. Busca esta línea:
```cpp
#define RELAY_HEATER     D1  // GPIO5
```

2. Cámbiala por:
```cpp
#define RELAY_HEATER     D0  // GPIO16
```

### CAMBIO 2: Modificar Temperatura Umbral para Ventilador

**Problema:** Quiero que el ventilador se active a 25°C en lugar de 30°C.

**Solución:**

1. Busca esta línea:
```cpp
const float TEMP_THRESHOLD = 30.0;
```

2. Cámbiala por:
```cpp
const float TEMP_THRESHOLD = 25.0;
```

### CAMBIO 3: Cambiar Sensor de Temperatura

**Problema:** Tengo un sensor DHT22 en lugar de DS18B20.

**Solución:**

1. En `platformio.ini`, agrega:
```ini
lib_deps = 
    adafruit/DHT sensor library@^1.4.4
```

2. En `main.cpp`, reemplaza:
```cpp
#include <OneWire.h>
#include <DallasTemperature.h>

OneWire oneWire(TEMP_SENSOR_PIN);
DallasTemperature sensors(&oneWire);
```

Por:
```cpp
#include <DHT.h>
#define DHTTYPE DHT22
DHT dht(TEMP_SENSOR_PIN, DHTTYPE);
```

3. Reemplaza la función `readTemperature()`:
```cpp
void readTemperature() {
  currentTemperature = dht.readTemperature();
  if (isnan(currentTemperature)) {
    currentTemperature = 0.0;
  }
}
```

4. En `setup()`, reemplaza:
```cpp
sensors.begin();
```

Por:
```cpp
dht.begin();
```

### CAMBIO 4: Cambiar Sensor de Corriente (ACS712 30A)

**Problema:** Tengo un ACS712 para 30A en lugar de 5A.

**Solución:**

1. Busca:
```cpp
const float CURRENT_SENSITIVITY = 0.185;
```

2. Reemplaza por:
```cpp
const float CURRENT_SENSITIVITY = 0.066;  // Para ACS712 30A
```

Sensibilidades comunes:
- ACS712 5A: `0.185` V/A
- ACS712 20A: `0.100` V/A
- ACS712 30A: `0.066` V/A

### CAMBIO 5: Modificar SSID WIFi

**Problema:** Quiero cambiar a "MI_RED_BIOMASA".

**Solución:**

1. Busca:
```cpp
const char* ssid = "BIOMASA_SYSTEM";
```

2. Cambia a:
```cpp
const char* ssid = "MI_RED_BIOMASA";
```

### CAMBIO 6: Modificar Contraseña WiFi

**Problema:** Quiero cambiar la contraseña WiFi.

**Solución:**

1. Busca:
```cpp
const char* password = "biomasa2026";
```

2. Cambia a:
```cpp
const char* password = "mi_nueva_password_123";
```

### CAMBIO 7: Agregar más Componentes al Display OLED

**Problema:** Quiero mostrar la humedad también en la pantalla OLED.

**Solución:**

1. Agrega variable global:
```cpp
float currentHumidity = 0.0;
```

2. Agrega lectura en `readTemperature()`:
```cpp
currentHumidity = dht.readHumidity();
```

3. En `updateDisplay()`, agrega:
```cpp
display.print("Hum: ");
display.print(currentHumidity);
display.println(" %");
```

---

## 📡 Conexión a la Página Web

### Paso 1: Conectar a WiFi

1. Sube el código al ESP8266
2. Abre el monitor serial
3. Verás: `IP AP: 192.168.4.1`

### Paso 2: Conectar Dispositivo

En tu celular o computadora:
1. Abre WiFi
2. Busca red **BIOMASA_SYSTEM**
3. Contraseña: **biomasa2026**

### Paso 3: Acceder a Página

Abre en browser: **http://192.168.4.1**

---

## 🐛 Troubleshooting

### El OLED no muestra nada

- Verifica pines SDA/SCL
- Comprueba dirección I2C (por defecto 0x3C)
- En `platformio.ini`, instala: `adafruit/Adafruit SSD1306`

### El sensor de temperatura no funciona

- Verifica la resistencia pull-up de 4.7kΩ
- Comprueba conexión física
- Lee puerto serial para ver errores

### No se conecta a WiFi

- Verifica SSID y contraseña
- Comprueba antena WiFi
- Resetea el ESP8266

### Los botones no responden

- Verifica pines físicos
- Comprueba debounce time
- Lee puerto serial para confirmar si se detectan

---

## 📊 Monitoreo Serial

Para debuggear, abre el Monitor Serial (115200 baud):

```
=== SISTEMA BIOMASA INICIANDO ===
Temperatura: 28.5 °C
Corriente: 2.34 A
Calentador ACTIVADO
=== SISTEMA LISTO ===
IP AP: 192.168.4.1
```

---

## 📝 Notas Importantes

1. **Voltajes:** El ESP8266 es de 3.3V, los relés necesitan nivel lógico de 3.3V o optocopladores.

2. **Energía:** El ESP8266 puede consumir ~500mA en WiFi. Usa fuente de poder estable de 5V/2A.

3. **Calibración:** El sensor de corriente puede necesitar calibración (offset). Ajusta `CURRENT_SENSITIVITY` según mediciones reales.

4. **Seguridad:** Cambia las contraseñas WiFi y Admin antes de usar en producción.

5. **Intervalo de lectura:** Por defecto es 1000ms (1 segundo). Cambiar en `loop()` si necesitas frecuencia diferente.

---

## 📚 Referencias Útiles

- [ESP8266 GPIO Pins](https://www.electronicwings.com/nodemcu/nodemcu-board-structure-pin-configuration-system)
- [Adafruit SSD1306](https://github.com/adafruit/Adafruit_SSD1306)
- [ACS712 Current Sensor](https://datasheet.octopart.com/ACS712ELCTR-05B-T-Allegro-Microsystems-datasheet-60766.pdf)
- [DS18B20 Temperature](https://datasheets.maximintegrated.com/en/ds/DS18B20.pdf)

---

**Versión:** 1.0
**Última actualización:** Febrero 2026
**Autor:** Sistema BIOMASA

