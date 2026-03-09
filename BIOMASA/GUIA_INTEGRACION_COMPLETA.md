# Guía Completa de Integración BIOMASA

## 📋 Índice
1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Requisitos](#requisitos)
3. [Configuración Inicial](#configuración-inicial)
4. [Flujo de Datos](#flujo-de-datos)
5. [Endpoints API](#endpoints-api)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)

---

## Arquitectura del Sistema

### Componentes Principales

```
ESP8266 (Firmware)
├── Sensor DS18B20 (Temperatura)
├── Relay PTC (Calentador)
├── Relay Ventilador
└── ACS712 (Sensor de Corriente)
         ↓ POST JSON
    sensores_estado.php ← GET Status
         ↓
    status.json
    control.json
    mediciones_biomasa.json
         ↓
    biores.php (Dashboard)
         ↓
    Usuario Final
```

---

## Requisitos

### Hardware
- **ESP8266** con WiFi integrado
- **OLED Display** SSD1306 (128x64 I2C)
- **DS18B20** OneWire temperature sensor
- **ACS712** Current sensor (analog A0)
- **Peltier Cells** (6 units @ 12V/5A)
- **Relays** para PTC y Ventilador

### Software
- **PHP 7.4+** en XAMPP
- **Main.cpp** compilado para ESP8266
- **MySQL Database** (prer_mi)
- **Navegador moderno** (Chrome, Firefox, Edge)

### Librerías Arduino Requeridas
```
- ESP8266WiFi
- ESP8266HTTPClient
- OneWire
- DallasTemperature
- Adafruit GFX
- Adafruit SSD1306
```

---

## Configuración Inicial

### 1. Preparar el ESP8266

**En main.cpp, configura:**
```cpp
const char* ssid = "TU_RED_WIFI";
const char* password = "TU_PASSWORD";
const char* serverAddress = "192.168.1.100"; // Tu servidor
const uint16_t serverPort = 80;
```

**Verifica los pines utilizados:**
- D2: DS18B20 Temperature (OneWire)
- D3: PTC Heater Relay Control
- D4: Ventilator Relay Control
- A0: ACS712 Current Sensor

### 2. Crear Directorios Necesarios

```
XAMPP htdocs/
└── PRERMI/
    ├── BIOMASA/
    │   ├── sensores_estado.php ✓
    │   ├── control_biomasa.php ✓
    │   └── (otros archivos)
    └── api/
        ├── status.json ✓
        ├── control.json ✓
        ├── mediciones_biomasa.json ✓
        └── (otros archivos)
```

### 3. Conferma Permisos de Archivos

```bash
# En Windows/XAMPP generalmente no es necesario,
# pero verifica que php pueda escribir en:
# - api/status.json
# - api/control.json  
# - api/mediciones_biomasa.json
```

### 4. Inicializar Base de Datos

El dashboard biores.php crea automáticamente la tabla `mediciones_biomasa` en la base de datos.

---

## Flujo de Datos

### Iniciación del Sistema (START)

```
1. Usuario hace click en "Iniciar Generación"
   ↓
2. biores.php → POST control_biomasa.php?accion=START
   ↓
3. control_biomasa.php guarda en control.json:
   {
     "command": "start_generacion",
     "raw": "start_generacion",
     "created_at": "2026-02-24T10:30:00+01:00",
     ...
   }
   ↓
4. ESP8266 poll cada 5 segundos en main.cpp (processRemoteCommand)
   ↓
5. ESP8266 lee control.json y ejecuta "start_generacion"
   - Activa relay PTC
   - Inicia generationOn flag
   - Comienza track de tiempo
   ↓
6. LED y botones actualizan en tiempo real
```

### Envío de Sensores en Tiempo Real

```
1. ESP8266 loop() cada 3 segundos:
   - Lee temperatura DS18B20
   - Lee corriente ACS712
   - Calcula energía generada
   
2. Si tiempo_env > TEMP_INTERVAL (3s):
   - POST → sensores_estado.php con JSON:
     {
       "temperatura": 45.5,
       "corriente": 2.3,
       "ventilador": 1,
       "calentador": 1,
       "energia_generada": 18.5,
       "sistema_activo": 1
     }
   
3. sensores_estado.php:
   - Lee JSON del POST
   - Actualiza status.json
   - Guarda en mediciones_biomasa.json
   - Responde con control state
   
4. Dashboard (biores.php):
   - GET sensores_estado.php cada 3 segundos
   - Actualiza cards de sensores
   - Anima bombillas (LED indicators)
   - Actualiza valor energía
```

### Detención del Sistema (STOP)

```
1. Usuario hace click en "Detener Sistema"
   ↓
2. biores.php → POST control_biomasa.php?accion=STOP
   ↓
3. ESP8266 recibe comando y:
   - Apaga relays (PTC, Ventilador)
   - Calcula energía total
   - Guarda energia_generada final
   - Set sistema_activo = 0
   ↓
4. Dashboard muestra Detenido
```

### Apagado de Emergencia

```
1. Usuario hace click en "Apagado de Emergencia"
   ↓
2. biores.php → POST sensores_estado.php con:
   {
     "accion": "temp_off" | "ventilador_off" | "corriente_off"
   }
   ↓
3. sensores_estado.php actualiza status.json
   ↓
4. ESP8266 lee estado apagado y cierra relays específicos
```

---

## Endpoints API

### GET /PRERMI/BIOMASA/sensores_estado.php

**Parámetros:** Ninguno

**Respuesta (200 OK):**
```json
{
  "status": "ok",
  "data": {
    "sistema_activo": 1,
    "temperatura": {
      "estado": "activo",
      "valor": 45.5,
      "timestamp": "2026-02-24T10:35:00+01:00"
    },
    "ventilador": {
      "estado": "activo",
      "valor": 1,
      "timestamp": "2026-02-24T10:35:00+01:00"
    },
    "corriente": {
      "estado": "sensando",
      "valor": 2.3,
      "timestamp": "2026-02-24T10:35:00+01:00"
    },
    "energia_generada": 18.5
  }
}
```

### POST /PRERMI/BIOMASA/sensores_estado.php

**Body (JSON):**
```json
{
  "temperatura": 45.5,
  "corriente": 2.3,
  "ventilador": 1,
  "calentador": 1,
  "energia_generada": 18.5,
  "sistema_activo": 1
}
```

**Respuesta (200 OK):**
```json
{
  "status": "ok",
  "accion": "start_generacion",
  "raw": "start_generacion",
  "command_id": 1,
  "bypass_temp": false,
  "bypass_fan": false,
  "bypass_heater": false,
  "bypass_current": false,
  "system_off": false
}
```

### POST /PRERMI/BIOMASA/control_biomasa.php

**Parámetros:**
- `accion` (requerido): START | STOP | EMERGENCY | SYSTEM_OFF

**Ejemplo:**
```
POST /PRERMI/BIOMASA/control_biomasa.php
Content-Type: application/x-www-form-urlencoded

accion=START
```

**Respuesta:**
```json
{
  "status": "ok",
  "stored": {
    "command": "start_generacion",
    "raw": "start_generacion",
    "created_at": "2026-02-24T10:30:00+01:00",
    ...
  }
}
```

---

## Testing

### Test 1: Verificar Endpoints API

```bash
# Test sensores_estado GET
curl http://localhost/PRERMI/BIOMASA/sensores_estado.php

# Test control_biomasa POST
curl -X POST http://localhost/PRERMI/BIOMASA/control_biomasa.php \
  -d "accion=START"
```

### Test 2: Simular ESP8266 POST

```bash
curl -X POST http://localhost/PRERMI/BIOMASA/sensores_estado.php \
  -H "Content-Type: application/json" \
  -d '{
    "temperatura": 42.5,
    "corriente": 2.1,
    "ventilador": 1,
    "calentador": 1,
    "energia_generada": 16.3,
    "sistema_activo": 1
  }'
```

### Test 3: Verificar Archivos JSON actualizados

```bash
# Ver status.json
cat api/status.json

# Ver control.json
cat api/control.json

# Ver histórico de mediciones
cat api/mediciones_biomasa.json
```

### Test 4: Acceder al Dashboard

```
http://localhost/PRERMI/web/admin/biores.php
```

Deberías ver:
- ✓ Botones START/STOP
- ✓ Cards de Temperatura, Ventilador, Corriente
- ✓ Display de Energía Generada
- ✓ Gráficos de Histórico

---

## Troubleshooting

### Problema: Dashboard muestra "N/A" en sensores

**Solución:**
1. Verifica que ESP8266 está conectado WiFi
2. Confirma dirección IP del servidor es correcta
3. Verifica sensores_estado.php devuelve datos:
   ```bash
   curl http://localhost/PRERMI/BIOMASA/sensores_estado.php
   ```
4. Revisa consola del navegador (F12) para errores

### Problema: Botones START/STOP no responden

**Solución:**
1. Verifica control_biomasa.php devuelve `status: "ok"`
   ```bash
   curl -X POST http://localhost/PRERMI/BIOMASA/control_biomasa.php -d "accion=START"
   ```
2. Verifica que control.json tiene permisos de escritura
3. Revisa consola JavaScript: `F12` → Tab "Console"

### Problema: Energía no se incrementa

**Solución:**
1. Verifica temperatura > 40°C para activar ventilador
2. Confirma ACS712 está leyendo corriente (analizador de circuito)
3. Revisa cálculo en main.cpp function `calculateEnergyGenerated()`
4. Verifica que `sistema_activo` = 1 en status.json

### Problema: Ventilador no se enciende a 40°C

**Solución:**
1. En main.cpp, busca sección de hysteresis:
   ```cpp
   if (temp > 40.0 && !fanOn) {
       // Activar ventilador
   }
   if (temp < 35.0 && fanOn) {
       // Desactivar ventilador
   }
   ```
2. Verifica relay está conectado a pin D4
3. Prueba manualmente: `digitalWrite(D4, HIGH)`

### Problema: OLED no muestra información

**Solución:**
1. Verifica dirección I2C: `0x3C` o `0x3D`
2. Modifica en main.cpp si es necesario:
   ```cpp
   // display.begin(SSD1306_SWITCHCAPVCC, 0x3D);
   display.begin(SSD1306_SWITCHCAPVCC, 0x3C); // Intenta esta
   ```
3. Verifica pines SDA (D1) y SCL (D3) no están en uso

---

## Parámetros de Calibración BIOMASA

Los siguientes valores están configurados en main.cpp y pueden ajustarse:

```cpp
// Peltier Cell Configuration
#define PELTIER_CELLS 6
#define PELTIER_VOLTAGE 12.0
#define PELTIER_CURRENT_MAX 5.0
#define SEEBECK_EFFICIENCY 0.05

// Temperature Thresholds
#define TEMP_THRESHOLD_FAN_ON 40.0
#define TEMP_THRESHOLD_FAN_OFF 35.0

// Energy Calculation
#define RESIDUE_HEAT_MULTIPLIER 1.2
#define BASELINE_POWER (PELTIER_CELLS * PELTIER_VOLTAGE * PELTIER_CURRENT_MAX * SEEBECK_EFFICIENCY)
// Result: 6 * 12V * 5A * 0.05 = 18W baseline @ 25°C delta

// API Polling
#define TEMP_INTERVAL 3000 // ms
```

---

## Próximos Pasos

1. ✅ **Compilar y cargar firmware** en ESP8266
2. ✅ **Configurar WiFi** en main.cpp
3. ✅ **Probar endpoints API** con curl
4. ✅ **Verificar Dashboard** carga correctamente
5. ✅ **Enviar datos de prueba** desde ESP8266
6. ✅ **Monitorear energía generada** en tiempo real
7. ⏳ **Integración con base de datos completa** (opcional)
8. ⏳ **Gráficos avanzados y reportes** (opcional)

---

## Contacto y Soporte

Para reportar problemas o sugerencias, revisa:
- Logs de XAMPP (apache_error.log)
- Console del navegador (F12)
- Serial Monitor del ESP8266 (115200 baud)

