# 📋 REFERENCIA: CONFIGURACIÓN ESP32 DEVKIT1

**Archivo de referencia guardado el:** 22/02/2026  
**Propósito:** Mantener una referencia completa de la configuración para ESP32 DEVKIT1 por si se necesita cambiar en el futuro.

---

## 🔌 CONFIGURACIÓN DE PINES - ESP32 DEVKIT1

```cpp
// MAIN.CPP - Pines para ESP32 DEVKIT1
#define RELAY_HEATER     23    // GPIO 23 → Relé calentador
#define RELAY_FAN        22    // GPIO 22 → Relé ventilador
#define TEMP_SENSOR_PIN  14    // GPIO 14 → OneWire temp sensor (SDA-compatible)
#define CURRENT_SENSOR   35    // GPIO 35 → ADC1_CH7 (entrada de corriente, solo entrada)

const char* prermi_token = "esp32_devkit1_biomasa";
```

---

## ⚙️ PLATFORMIO.INI - ESP32 DEVKIT1

```ini
; PlatformIO Project Configuration File - BIOMASA ESP32 DEVKIT1

[env:esp32doit_devkit_v1]
platform = espressif32
board = esp32doit-devkit-v1
framework = arduino
monitor_speed = 115200
upload_speed = 921600

; Librerías requeridas
lib_deps = 
	paulstoffregen/OneWire@2.3.7
	milesburton/DallasTemperature@^4.1.0
	bblanchon/ArduinoJson@^7.4.2

; Monitor serial
monitor_dtr = 0
monitor_rts = 0

; Build flags para DEVKIT1
build_flags = 
	-DBOARD_ESP32_DEVKIT1
```

---

## 🔌 ESQUEMA DE CONEXIÓN - DEVKIT1

### DS18B20 (Temperatura)
```
        ┌─────────────┐
        │   DS18B20   │
        │  ⬜⬜⬜    │
        └─────┬───┬───┘
              │   │   
       GND ──┤   └─ VDD (3.3V)
             │
        ││  DATA
        └────[4.7kΩ]──── VDD
             GPIO 14
```

**Conexión:**
- Pin 1 (GND) → GND
- Pin 2 (DATA) → GPIO 14 + Pull-up 4.7kΩ a 3.3V
- Pin 3 (VDD) → 3.3V

### ACS712-5A (Corriente)
```
GND  ───→ GND (DEVKIT1)
OUT  ───→ GPIO 35 (ADC)
VCC  ───→ 3.3V (DEVKIT1)
```

### Relés
```
GPIO 22 (FAN)   ───→ Relé Ventilador
GPIO 23 (HEAT)  ───→ Relé Calentador
GND            ───→ Retorno
VCC            ───→ 5V (si es necesario, vía optoacoplador)
```

---

## 📊 TABLA COMPARATIVA: S3 CAM vs DEVKIT1

| Aspecto | ESP32-S3 CAM | ESP32 DEVKIT1 |
|---------|-------------|---------------|
| **Board PlatformIO** | freenove_esp32_s3_wroom | esp32doit-devkit-v1 |
| **GPIO Relé Calentador** | 4 | 23 |
| **GPIO Relé Ventilador** | 5 | 22 |
| **GPIO Temp (OneWire)** | 6 | 14 |
| **GPIO Corriente (ADC)** | 7 | 35 |
| **Token** | esp8266_sensor_token | esp32_devkit1_biomasa |
| **Upload Speed** | Estándar | 921600 bps |
| **Disponibilidad** | Media | ⭐⭐⭐ Excelente |
| **Precio** | $$$ | $ Económico |
| **Pines GPIO libres** | 10-15 | 20+ |

---

## 🧪 COMANDOS PARA COMPILAR Y CARGAR - DEVKIT1

```bash
# Listar placas disponibles
platformio boards | grep devkit

# Compilar para DEVKIT1
platformio run --environment esp32doit_devkit_v1

# Cargar código en DEVKIT1
platformio run --target upload --environment esp32doit_devkit_v1

# Monitor en tiempo real
platformio device monitor --environment esp32doit_devkit_v1 -b 115200

# Todo en uno: compilar, cargar y monitorear
platformio run --target upload --environment esp32doit_devkit_v1 && platformio device monitor -b 115200
```

---

## 📋 DIAGNÓSTICO ESPERADO - DEVKIT1

Cuando se inicia con DEVKIT1, la salida Serial debería ser:

```
═══════════════════════════════════════════════════════════════╗
║  BIOMASA - ESP32 DEVKIT1               ║
╚═══════════════════════════════════════════════════════════════╝
✓ Sensor de temperatura iniciado correctamente
✓ Sistema inicializado

📡 Conectando a WiFi: Jhail-WIFI
✓ WiFi conectado. IP: 192.168.1.XXX

=== SISTEMA BIOMASA LISTO ===
WiFi Estado: Conectado

📊 DIAGNÓSTICO DE HARDWARE (DEVKIT1):
   Sensores OneWire detectados: 1
   Voltaje ADC (GPIO 35): 1.65 V

Temperatura: 28.50 °C
Corriente: 0.00 A
POST a PRERMI → http://192.168.1.106:8080/PRERMI/BIOMASA/sensores_estado.php?token=esp32_devkit1_biomasa
HTTP Code: 200
Respuesta: {"status":"ok","msg":"Estado actualizado",...}
```

---

## ⚠️ NOTAS IMPORTANTES DEVKIT1

1. **GPIO 35 es SOLO ENTRADA**: No puede usarse como salida digital
2. **Mejor rango ADC**: DEVKIT1 tiene mejor soporte para rango 0-3.3V
3. **Más pines disponibles**: Ideal para expansión futura
4. **Better documentation**: Comunidad ESP32 más grande
5. **Mejor precio**: Significativamente más económico

---

## 🔄 CÓMO VOLVER A DEVKIT1

Si necesitas volver a usar DEVKIT1 en el futuro:

### 1. **Cambiar pines en main.cpp:**
```cpp
#define RELAY_HEATER     23    // ← antes 4
#define RELAY_FAN        22    // ← antes 5
#define TEMP_SENSOR_PIN  14    // ← antes 6
#define CURRENT_SENSOR   35    // ← antes 7

const char* prermi_token = "esp32_devkit1_biomasa";  // ← antes esp8266_sensor_token
```

### 2. **Cambiar platformio.ini:**
```ini
[env:esp32doit_devkit_v1]
platform = espressif32
board = esp32doit-devkit-v1
```

### 3. **Actualizar servidor PHP para aceptar el token:**
```php
$tokens_validos = [
    'esp8266_sensor_token',      // S3 CAM
    'esp32_devkit1_biomasa',     // DEVKIT1
    'esp32s3_sensor_token'       // Alternativo
];
```

### 4. **Reconfigurar pines físicos**
Ver esquema de conexión arriba

### 5. **Compilar y cargar:**
```bash
platformio run --target upload --environment esp32doit_devkit_v1
```

---

## 📚 RECURSOS DEVKIT1

- [Datasheet ESP32](https://www.espressif.com/sites/default/files/documentation/esp32_datasheet_en.pdf)
- [Pineado DEVKIT1](https://docs.espressif.com/projects/esp-idf/en/latest/esp32/hw-reference/esp32_devkitc.html)
- [PlatformIO ESP32](https://docs.platformio.org/en/stable/boards/espressif32/esp32doit-devkit-v1.html)

---

**Última actualización:** 22/02/2026  
**Estado:** Guardado como referencia  
**Hardware actual:** ESP32-S3 CAM  
**Hardware alternativo:** ESP32 DEVKIT1 (config guardada aquí)
