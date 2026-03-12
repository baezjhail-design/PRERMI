# BIOMASA TEST MODE - ESP32-S3 CAM

## 🎯 Propósito

Este código es una **versión de prueba** del sistema BIOMASA adaptada para **ESP32-S3 CAM** que:
- ✅ **NO requiere sensores físicos** (DS18B20, ACS712, etc)
- ✅ Genera **valores aleatorios simulados**
- ✅ Prueba la **comunicación con servidor XAMPP**
- ✅ Muestra **todo en Serial Monitor** a 115200 baud
- ✅ Funciona **sin OLED** (opcional)

---

## ⚙️ Configuración Inicial

### 1. Configurar WiFi y Servidor

Edita en `main.cpp` (líneas ~125-130):

```cpp
const char* WIFI_SSID = "TU_RED_WIFI";        // ← Cambia esto
const char* WIFI_PASS = "TU_PASSWORD";        // ← Cambia esto
const char* SERVER_HOST = "192.168.1.100";    // ← IP de tu PC con XAMPP
const int SERVER_PORT = 80;
```

### 2. Modos Configurables

En `main.cpp` (líneas ~7-8):

```cpp
#define TEST_MODE true        // true = simulación, false = sensores reales
#define ENABLE_OLED false     // true = usar OLED, false = solo Serial
```

**Recomendado para pruebas:** Ambos como están (TEST_MODE=true, ENABLE_OLED=false)

---

## 🚀 Cómo Usar

### 1. Compilar y Subir
```bash
platformio run --target upload
```

### 2. Abrir Serial Monitor
```bash
platformio device monitor
```

O usa el Serial Monitor del IDE (115200 baud)

### 3. Abrir Dashboard Web
```
http://localhost/PRERMI/web/admin/biores.php
```

### 4. Hacer Click en "Iniciar Generación"

Deberías ver en Serial Monitor:
```
[POLL] Consultando comandos del servidor...
[POLL] HTTP Code: 200
[POLL] ✓ Respuesta recibida:
{"accion":"start","command":"start_generacion",...}
[EVENT] INICIO - PTC activadas

>>> SISTEMA ACTIVO <<<

[SIM] Temperatura generada: 32.45 C
[SIM] Corriente generada: 2.34 A
[OUTPUT] PTC: ON, FAN: OFF

[SEND] Enviando estado al servidor...
[SEND] Payload JSON:
{"temperatura":32.45,"corriente":2.34,...}
[SEND] HTTP Code: 200
[SEND] ✓ Respuesta del servidor:
```

---

## 📊 Valores Simulados

### Temperatura
- **Rango:** 25.0°C - 50.0°C (configurable)
- **Comportamiento:** Incrementa 0.1°C por segundo cuando generación activa
- **Umbral ventilador:** > 40°C = ON, < 35°C = OFF

### Corriente
- **Rango:** 0.5A - 4.5A (configurable)
- **Comportamiento:** Solo > 0 cuando generación activa
- **Aleatorio:** Cambia cada lectura

### Energía
- **Cálculo:** Basado en fórmula Peltier real
- **Incremento:** Continuo mientras generación activa
- **Unidad:** Wh (Watt-hora)

---

## 📺 Serial Monitor Output

### Al Iniciar
```
==================================================
===  BIOMASA TEST MODE - ESP32-S3 CAM  ===
==================================================

[MODE] TEST MODE ACTIVO
[MODE] Usando valores SIMULADOS (sin sensores)
[MODE] Pines GPIO no se activarán físicamente

[INIT] GPIO skipped (TEST_MODE)
[INIT] OLED deshabilitado (usando solo Serial)
[INIT] Generador aleatorio inicializado

[WIFI] Conectando a: TU_RED_WIFI
.........................
[WIFI] ✓ CONECTADO
[WIFI] IP Local: 192.168.1.150
[WIFI] Gateway: 192.168.1.1
[WIFI] RSSI: -45 dBm
[WIFI] MAC: AA:BB:CC:DD:EE:FF

[SERVER] Host: 192.168.1.100:80
[SERVER] API Endpoint: /PRERMI/BIOMASA/sensores_estado.php

==================================================
===     SISTEMA LISTO PARA PRUEBAS      ===
==================================================

Esperando comandos del servidor...
Abre biores.php y haz clic en INICIAR
```

### Durante Operación (cada 3 segundos)
```
========== ESTADO SISTEMA ==========
Página: 1/5
--- SENSORES ---
Temperatura: 35.67 C 
Corriente: 2.13 A 
PTC: ON
Ventilador: OFF
Energía: 0.45 Wh (Total: 12.34 Wh)
===================================
```

### Cuando Temperatura > 40°C
```
>>> VENTILADOR ENCENDIDO (40.2 C >= 40.0 C) <<<

[EVENT] VENTILADOR ON - 40.2C
[OUTPUT] PTC: ON, FAN: ON
```

### Al Hacer STOP desde Web
```
[POLL] ✓ Respuesta recibida:
{"accion":"stop","command":"stop_generacion",...}

[ENERGY] Ciclo finalizado: 15.67 Wh. Total: 15.67 Wh
[CMD] STOP GENERACION
[EVENT] PARADA - 15.7Wh
[OUTPUT] PTC: OFF, FAN: OFF
```

---

## 🔄 Ciclo Completo de Prueba

1. **Compila y sube** código a ESP32-S3
2. **Abre Serial Monitor** a 115200 baud
3. **Verifica conexión WiFi** (debe mostrar IP asignada)
4. **Abre biores.php** en navegador
5. **Click "Iniciar Generación"**
   - Verás en Serial: `[EVENT] INICIO - PTC activadas`
   - Temperatura comienza a subir
6. **Espera ~30 segundos** hasta temp > 40°C
   - Verás: `>>> VENTILADOR ENCENDIDO <<<`
7. **Click "Detener Sistema"**
   - Verás energía total generada
8. **Verifica en biores.php** que sensores actualizan en tiempo real

---

## 🐛 Troubleshooting

### WiFi no conecta
```
[WIFI] ✗ FALLO al conectar
[WIFI] Iniciando AP fallback...
[WIFI] AP Name: BIOMASA_TEST_AP
```
**Solución:** 
- Verifica SSID y password correctos
- Verifica red 2.4GHz (ESP32 no soporta 5GHz)
- Conéctate al AP "BIOMASA_TEST_AP" y accede por IP del AP

### HTTP Error 404
```
[SEND] ✗ Error HTTP: 404
```
**Solución:**
- Verifica XAMPP Apache esté corriendo
- Verifica ruta: `http://SERVER_HOST/PRERMI/BIOMASA/sensores_estado.php` existe
- Prueba abrir URL directamente en navegador

### No se reciben comandos
```
[POLL] HTTP Code: 200
[POLL] ✓ Respuesta recibida:
{"accion":"none",...}
```
**Solución:**
- Es normal si no has hecho click en botones
- Haz click "Iniciar Generación" en biores.php
- Verifica `control.json` tiene comando pendiente

### Valores no aparecen en Dashboard
**Solución:**
- Verifica Serial muestra `[SEND] HTTP Code: 200`
- Abre biores.php y espera 3 segundos (auto-refresh)
- Abre F12 → Console en navegador para ver errores JS
- Verifica `status.json` se actualiza: `http://localhost/PRERMI/api/status.json`

---

## 📝 Cambiar Rangos de Simulación

En `main.cpp` (líneas ~37-40):

```cpp
float simTempMin = 25.0;   // Temperatura mínima °C
float simTempMax = 50.0;   // Temperatura máxima °C
float simCurrentMin = 0.5; // Corriente mínima A
float simCurrentMax = 4.5; // Corriente máxima A
```

---

## 🔧 Activar Sensores Reales

Para usar con hardware real:

1. Cambia en main.cpp:
```cpp
#define TEST_MODE false        // ← Activa modo real
#define ENABLE_OLED true       // ← Si tienes OLED
```

2. Conecta sensores:
   - DS18B20 → GPIO2 (TEMP_SENSOR_PIN)
   - ACS712 → A0 (CURRENT_PIN)
   - PTC Relay → GPIO2 (PTC_CELDA_PIN)
   - Fan Relay → GPIO4 (FAN_PIN)
   - OLED → SDA=GPIO21, SCL=GPIO22

3. Descomenta librerías:
```cpp
#include <OneWire.h>
#include <DallasTemperature.h>
```

4. Instala librerías en platformio.ini:
```ini
lib_deps = 
    adafruit/Adafruit SSD1306
    adafruit/Adafruit GFX Library
    milesburton/DallasTemperature
    paulstoffregen/OneWire
```

---

## 📊 Páginas de Estado (Rotación cada 3s)

### Página 1/5: SENSORES
- Temperatura actual
- Corriente medida
- Estado PTC (ON/OFF)
- Estado Ventilador (ON/OFF)
- Energía generada acumulada

### Página 2/5: RED/API
- Estado WiFi
- IP asignada
- RSSI (fuerza señal)
- Servidor y endpoint

### Página 3/5: CONTROL
- Última acción recibida
- Command ID
- Bypass flags
- System OFF state

### Página 4/5: INFO SISTEMA
- Configuración Peltier (6 celdas)
- Parámetros eléctricos
- Tiempo activo
- Umbrales temperatura

### Página 5/5: ACCIONES USUARIO
- Últimas 6 acciones registradas
- Timestamp relativo
- Detalles de cada acción

---

## ✅ Resultados Esperados

Al finalizar una prueba completa deberías ver:

**En Serial Monitor:**
```
[EVENT] SISTEMA - Iniciado
[EVENT] INICIO - PTC activadas
[EVENT] VENTILADOR ON - 40.2C
[EVENT] PARADA - 15.7Wh
```

**En biores.php:**
- ✓ LED verde cuando sistema activo
- ✓ Temperatura actualiza en tiempo real
- ✓ Energía incrementa continuamente
- ✓ Ventilador se activa a 40°C
- ✓ Cards de sensores con estado correcto

**En archivos JSON:**
- `status.json` actualizado cada 3s con datos reales
- `control.json` con comandos START/STOP
- `mediciones_biomasa.json` con histórico

---

## 🎓 Conclusión

Este código de prueba te permite:
- ✅ Verificar comunicación ESP32 ↔ Servidor XAMPP
- ✅ Probar lógica de control sin hardware
- ✅ Depurar problemas de red
- ✅ Ver flujo completo de datos en Serial
- ✅ Confirmar que dashboard funciona correctamente

Una vez que todo funcione en TEST_MODE, puedes cambiar a modo real conectando los sensores físicos.

---

**Última actualización:** 6 Marzo 2026  
**Plataforma:** ESP32-S3 CAM  
**Framework:** Arduino + PlatformIO  
**Baudrate:** 115200
