# 🏗️ ARQUITECTURA Y DIAGRAMA DEL SISTEMA BIOMASA

## 📊 Diagrama de Flujo General

```
┌─────────────────────────────────────────────────────────────────┐
│                     SISTEMA BIOMASA v1.0                         │
└─────────────────────────────────────────────────────────────────┘

                          ESP8266MOD
                        ┌──────────┐
                        │          │
         ┌──────────────┴──────────┴──────────────┐
         │                                         │
    🖥️ I2C BUS                              ☑️ GPIO PUERTOS
         │                                         │
    ┌────┴────┐                             ┌─────┴─────┐
    │          │                             │           │
 🔌 OLED      📊 BUS                    🔥 SALIDAS   📥 ENTRADAS
 📺 128x64     DATOS                       │           │
              │                           │           │
              │                    ┌──────┴───────┬──┘
              │                    │              │
              │              🔥 RELÉ          🔵 BOTÓN
              │              CALENTADOR       START/STOP
              │              
              │              💨 RELÉ
              │              VENTILADOR
              │
         ┌────┴─────────────┐
         │                  │
    🌡️ SENSORES       📡 ADC
         │                  │
    ┌────┴─────┐      ┌─────┴─────┐
    │           │      │           │
 DS18B20    ONEWIRE   ACS712    ANALÓGICO
 TEMP                 CORRIENTE
```

---

## 🔄 Ciclo Principal del Programa

```
INICIO
  │
  ├─ initializeSystem()
  │   ├─ Serial.begin(115200)
  │   ├─ Wire.begin()  [I2C]
  │   ├─ Display.begin()
  │   ├─ pinMode() [todos los pines]
  │   └─ sensors.begin()
  │
  ├─ setupWiFi()
  │   ├─ WiFi.mode(AP_STA)
  │   ├─ WiFi.softAP()
  │   └─ Mostrar IP en OLED
  │
  └─ setupWebServer()
      ├─ server.on("/", handleRoot)
      ├─ server.on("/api", handleAPI)
      ├─ server.on("/control", handleControl)
      └─ server.begin()
         │
         └─ LOOP INFINITO
            │
            ├─ [CADA 1000ms]
            │  ├─ readTemperature()
            │  ├─ readCurrentSensor()
            │  ├─ Evaluar lógica de control
            │  │  └─ Si Temp > 30°C → Activar ventilador
            │  └─ updateDisplay()
            │
            ├─ [CHEQUEO DE BOTONES]
            │  ├─ Si BUTTON_START → 
            │  │  └─ Activar calentador
            │  └─ Si BUTTON_STOP →
            │     └─ Apagar todo
            │
            └─ server.handleClient()
               └─ Procesar peticiones Web
```

---

## 📡 Arquitectura de Capas

```
┌─────────────────────────────────────────────────────┐
│         CAPA DE PRESENTACIÓN (WEB UI)               │
│  • HTML + CSS + JavaScript                          │
│  • Dashboard SCADA                                  │
│  • Controles interactivos                           │
└────────────────┬────────────────────────────────────┘
                 │ HTTP
                 ▼
┌─────────────────────────────────────────────────────┐
│    CAPA DE APLICACIÓN (HTTP Server)                 │
│  • ESP8266WebServer (puerto 80)                     │
│  • Endpoints: /, /api, /control                     │
│  • Variables JSON de estado                         │
└────────────────┬────────────────────────────────────┘
                 │ Funciones C++
                 ▼
┌─────────────────────────────────────────────────────┐
│    CAPA DE LÓGICA (Control & Lectura)              │
│  • Lecturas de sensores                             │
│  • Cálculos y acumuladores                          │
│  • Máquina de estados del sistema                   │
│  • Lógica de ventilador automático                  │
└────────────────┬────────────────────────────────────┘
                 │ GPIO + I2C + SPI
                 ▼
┌─────────────────────────────────────────────────────┐
│     CAPA FÍSICA (Hardware)                          │
│  • Relés (optocopladores)                           │
│  • Sensores (temperatura, corriente)                │
│  • Display OLED                                     │
│  • Botones físicos                                  │
└─────────────────────────────────────────────────────┘
```

---

## 🔌 Esquema de Pines Detallado

```
    ┌──────────────────────────────┐
    │      ESP8266MOD (NodeMCU)    │
    └──────────────────────────────┘

    ┌─ 3.3V                    GND ─┐
    │                              │
    ├─ D7 (GPIO13) ──I2C_SDA──┐   │
    │                         │   │
    ├─ D8 (GPIO15) ──I2C_SCL──┘   │
    │                             │
    ├─ D1 (GPIO5)  ──RELÉ_CALOR───┼─ OPTOCOPLADOR
    │                             │  CALENTADOR PTC
    ├─ D2 (GPIO4)  ──RELÉ_VENTI──┬┘
    │                             │  OPTOCOPLADOR
    │                             │  VENTILADOR DC
    ├─ D4 (GPIO2)  ──ONEWIRE────┉─ DS18B20
    │                    (Pull-up)  TEMPERATURA
    │
    ├─ D5 (GPIO14) ──BOTÓN_START───┐
    │                              │ Botones
    ├─ D6 (GPIO12) ──BOTÓN_STOP────┤ (Pull-up interno)
    │                              │
    │
    ├─ A0 (ADC0)   ──SENSOR_CORRIENTE
    │                (ACS712 5A)
    │
    └─ GND ────────────────────────────────────────

    OLED SSD1306 (dirección 0x3C)
    ┌────────────────────┐
    │ SDA ────── D7      │
    │ SCL ────── D8      │
    │ VCC ────── 3.3V    │
    │ GND ────── GND     │
    └────────────────────┘
```

---

## 📊 Máquina de Estados del Sistema

```
                    ┌─────────────┐
                    │   SYSTEM    │
                    │    STATE    │
                    └──────┬──────┘
                           │
                   ┌───────┴───────┐
                   │               │
               STOPPED         RUNNING
               (state=0)       (state=1)
                   │               │
                   │               │
             ┌─────┴───┐    ┌─────┴──────┐
             │          │    │            │
        Relés: OFF  Relés:    Calentador:
        Pantalla:    Active   SIEMPRE ON
        "STOPPED"    Pantalla:  │
                     "✓"        ├─ Determina
                                │  estado de
                         Lógica  │  ventilador
                         Auto:   │
                         Si T>30 │
                         → Fan ON└─────────┘
```

---

## 🌡️ Lógica de Control de Temperatura

```
                     Lectura cada 1000ms
                            │
                            ▼
                  ¿systemState == 1?
                            │
              ┌─────────────┴─────────────┐
              │                           │
             NO                          SÍ
              │                           │
          FAN OFF             ¿T > 30°C?
              │                │
              │    ┌───────────┴───────────┐
              │    │                       │
              │   SÍ                      NO
              │    │                       │
              │    ▼                       ▼
              │ FAN ON                 FAN OFF
              │    │                       │
              │    │       ┌───────────────┘
              │    │       │
              └────┴───────┴────────────┐
                                        │
                            Actualizar OLED
                                        │
                            Mostrar estado
```

---

## 📱 Comunicación HTTP

```
CLIENTE (Navegador/APP)
        │
        │ GET /
        ├────────────────────────────────────────────┐
        │                                            │
    [Envía HTML, CSS, JS]                           │
        │◄────────────────────────────────────────────┘
        │
        │ GET /api (recurrente cada 1s)
        ├────────────────────────────────────────────┐
        │                                            │
    {"temp": 35.2, "current": 2.45, ...}            │
        │◄────────────────────────────────────────────┘
        │
        │ GET /control?action=start
        ├────────────────────────────────────────────┐
        │                                            │
    {"success": true}                                │
    systemState = 1                                  │
    activateHeater()                                 │
        │◄────────────────────────────────────────────┘
        │
        │ GET /control?action=stop
        ├────────────────────────────────────────────┐
        │                                            │
    {"success": true}                                │
    systemState = 0                                  │
    deactivateAll()                                  │
        │◄────────────────────────────────────────────┘
        │
        └─ ACTUALIZAR UI CON DATOS
```

---

## 🔐 Flujo de Autenticación Admin

```
Usuario ingresa contraseña
        │
        ▼
JavaScript envía:
GET /control?action=admin&password=XXXXXX
        │
        ▼
Server compara:
server_password == admin_password?
        │
   ┌────┴────┐
   │          │
  SÍ         NO
   │          │
   ▼          ▼
true        false
   │          │
   └────┬─────┘
        │
   Responder JSON
   {"success": true/false}
```

---

## 📊 Variables Globales y Su Ciclo de Vida

```
VARIABLE                 FUENTE              ACTUALIZACIÓN   USO
─────────────────────────────────────────────────────────────────
currentTemperature       DS18B20             Cada 1000ms    OLED, API, Lógica
currentCurrent           ACS712 ADC          Cada 1000ms    OLED, API, Energía
energyGenerated          Cálculo acumulado   Cada 1000ms    OLED, API
systemState              Botón/Web           Inmediata      OLED, Lógica
heaterState             GPIO5               Inmediata      OLED, API
fanState                GPIO4               Inmediata      OLED, API
─────────────────────────────────────────────────────────────────
```

---

## 🔄 Ciclo de Lectura de Sensores

```
readTemperature()
    │
    ├─ sensors.requestTemperatures()
    │       [Envía comando al DS18B20]
    │
    ├─ sensors.getTempCByIndex(0)
    │       [Espera respuesta ~750ms]
    │
    └─ currentTemperature = valor
            [Para lectura siguiente]

readCurrentSensor()
    │
    ├─ analogRead(A0)
    │       [Lee ADC (10 bits): 0-1023]
    │
    ├─ Conversión: valor → voltios
    │       V = (valor/1023) × 3.3
    │
    ├─ Eliminación de offset: V - 1.65V
    │       [Centro del sensor]
    │
    ├─ Conversión: voltios → Amperios
    │       I = V / sensitivity (0.185)
    │
    ├─ Acumulación de energía:
    │       Wh = Wh_anterior + (I × V × Δt)
    │
    └─ currentCurrent = valor
```

---

## 🎛️ Panel de Control SCADA

```
╔════════════════════════════════════════════════════════════╗
║                  BIOMASA SCADA SYSTEM                     ║
╠════════════════════════════════════════════════════════════╣
║                                                            ║
║  📊 ESTADO DEL SISTEMA                                   ║
║  ┌──────────────┬──────────────┬──────────────┐          ║
║  │ Temperatura  │ Corriente    │ Energía      │          ║
║  │   35.2 °C    │  2.45 A      │ 125.3 Wh     │          ║
║  └──────────────┴──────────────┴──────────────┘          ║
║                                                            ║
║  ⚙️ CONTROL DEL SISTEMA                                  ║
║  ┌──────────────────┬──────────────────┐                ║
║  │  ▶️ INICIAR      │  ⏹️ DETENER      │                ║
║  └──────────────────┴──────────────────┘                ║
║                                                            ║
║  Calentador PTC:  🟢 ENCENDIDO                          ║
║  Ventilador DC:   🔴 APAGADO                            ║
║                                                            ║
║  🔐 Panel Admin (BIORES)                                ║
║  [Contraseña] [Acceder]                                 ║
║                                                            ║
╚════════════════════════════════════════════════════════════╝

ACTUALIZACIÓN: Tiempo real (cada 1000ms)
CONEXIÓN: WiFi 2.4GHz 802.11 b/g/n
PUERTO: 80 (HTTP)
```

---

## 🖥️ Pantalla OLED Local

```
╔════════════════════════════╗
║ === BIOMASA SYSTEM ===    ║
║                            ║
║ Estado: RUNNING            ║
║ Temp: 35.2 C               ║
║ Corr: 2.45 A               ║
║ Ener: 125.3 Wh             ║
║                            ║
║ Calentador: ON Ventilador: ON
║                            ║
║           ✓                ║
║                            ║
╚════════════════════════════╝

O EN ESTADO STOP:

╔════════════════════════════╗
║ === BIOMASA SYSTEM ===    ║
║                            ║
║ Estado: STOP               ║
║ Temp: 25.0 C               ║
║ Corr: 0.00 A               ║
║ Ener: 0.0 Wh               ║
║                            ║
║ Calentador: OFF Ventilador: OFF
║                            ║
║      STOPPED               ║
║                            ║
╚════════════════════════════╝
```

---

## 📈 Relación Temperatura → Ventilador

```
│ FAN
│  ON  │
│      │
│      │  ┌─────────────
│      │  │ VENTILADOR ACTIVADO
│      │  │
│      │  │ (T > 30°C)
│      │  │
│      ├──┤
│      │  │
│ OFF  │  │ VENTILADOR APAGADO
│      │  │ (T ≤ 30°C)
│      └──┘
└──────────────────── TEMPERATURA (°C)
       20    30    40    50
```

---

## 🔌 Consumo de Energía Aproximado

```
En STOP:
- ESP8266: ~50mA
- OLED: ~5mA
- Total: ~55mA

En RUNNING (sin carga):
- ESP8266: ~150mA
- OLED: ~5mA
- Calentador: ~1.5A (15W @ 12V)
- Total: ~1.655A

En RUNNING (con ventilador):
- Ventilador: ~0.3A (3.6W @ 12V)
- Total: ~1.955A

Recomendación: Fuente 5V/2A para ESP8266
                Fuente 12V/5A para relés y carga
```

---

## 📚 Relación de Archivos

```
main.cpp
  ├─ Includes de librerías
  ├─ Definición de pines
  ├─ Inicialización de objetos
  ├─ Variables globales
  ├─ initializeSystem()
  ├─ readTemperature()
  ├─ readCurrentSensor()
  ├─ updateDisplay()
  ├─ activateHeater/Deactivate
  ├─ activateFan/Deactivate
  ├─ setupWiFi()
  ├─ setupWebServer()
  ├─ getWebPage() → HTML/CSS/JS
  ├─ handleRoot()
  ├─ handleAPI() → JSON
  ├─ handleControl() → Lógica
  ├─ setup()
  └─ loop()

Documentación:
  ├─ DOCUMENTACION.md (completa)
  ├─ CAMBIOS_RAPIDOS.md (referencia)
  ├─ GUIA_INSTALACION.md (setup)
  └─ ARQUITECTURA.md (este archivo)
```

---

Este diagrama te ayuda a visualizar que tu sistema BIOMASA es una arquitectura **multicapa** donde:

1. **Capa Física**: Sensores y relés reales
2. **Capa de Control**: Lógica C++ que procesa datos
3. **Capa de Red**: Servidor web que expone datos
4. **Capa de Presentación**: Interfaz visual (web + OLED)

Todas estas capas trabajan juntas en tiempo real cada segundo.

