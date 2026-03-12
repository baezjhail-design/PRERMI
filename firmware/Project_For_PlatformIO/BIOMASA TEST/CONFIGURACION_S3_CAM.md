# 🔧 CONFIGURACIÓN BIOMASA - ESP32-S3 CAM

## ✅ CONFIGURACIÓN ACTUAL

Sistema BIOMASA operando con **ESP32-S3 CAM** (freenove_esp32_s3_wroom)

---

## 🔌 DISTRIBUCIÓN DE PINES (S3 CAM)

### Pines Usados en BIOMASA:

| Componente | GPIO | Función | Tipo |
|-----------|------|---------|------|
| **Relé Calentador** | GPIO 4 | OUTPUT | Digital |
| **Relé Ventilador** | GPIO 5 | OUTPUT | Digital |
| **Sensor Temperatura** | GPIO 6 | OneWire | Digital |
| **Sensor Corriente** | GPIO 7* | ADC | Entrada ADC |

*GPIO 7: Puede ser usado como entrada digital/analógica

---

## 📐 ESQUEMA DE CONEXIÓN

### DS18B20 (Sensor de Temperatura)
```
     ┌─────────────┐
     │   DS18B20   │
     │  ⬜⬜⬜     │
     └─────┬───┬───┘
           │   │   
    GND ──┘    └─ VDD (3.3V)
           
       ││ (DATA a GPIO 6)
       └────[4.7kΩ]──── VDD (3.3V)
            GPIO 6
            
Conector típico:
┌─────────────┐
│ GND │DATA│VDD
│  1  │ 2  │ 3
└─────────────┘
```

**CONEXIÓN S3 CAM:**
- Pin 1 (GND)     → GND (cualquier pin GND)
- Pin 2 (DATA)    → GPIO 6 + Pull-up 4.7kΩ a 3.3V
- Pin 3 (VDD)     → 3.3V

### ACS712-5A (Sensor de Corriente)
```
GND  ───→ GND (S3 CAM)
OUT  ───→ GPIO 7 (ADC)
VCC  ───→ 3.3V (S3 CAM)
```

### Relés
```
GPIO 4 (HEATER) ──[Optoacoplador/Transistor]──→ Relé Calentador
GPIO 5 (FAN)    ──[Optoacoplador/Transistor]──→ Relé Ventilador
```

---

## 🔧 CONFIGURACIÓN DE SOFTWARE

### Arduino (C++) - main.cpp
```cpp
#define RELAY_HEATER     4     // GPIO 4
#define RELAY_FAN        5     // GPIO 5
#define TEMP_SENSOR_PIN  6     // GPIO 6
#define CURRENT_SENSOR   7     // GPIO 7 (ADC)

const char* prermi_token = "esp8266_sensor_token";
```

### PlatformIO Configuration - platformio.ini
```ini
[env:freenove_esp32_s3_wroom]
platform = espressif32
board = freenove_esp32_s3_wroom
framework = arduino
monitor_speed = 115200
```

### PHP (sensores_estado.php)
```php
// Tokens aceptados (soporta múltiples placas)
$tokens_validos = [
    'esp8266_sensor_token',      // ← ESP32-S3 CAM (ACTUAL)
    'esp32_devkit1_biomasa',     // ← DEVKIT1 alternativo
    'esp32s3_sensor_token'       // ← Alternativo S3
];

if (!isset($_SESSION['user_id']) && (!isset($_GET['token']) || !in_array($_GET['token'], $tokens_validos))) {
    echo json_encode(["status" => "error", "msg" => "No autorizado"]);
    exit;
}
```

---

## 📊 ESPECIFICACIONES DEL ESP32-S3 CAM

| Característica | Especificación |
|---|---|
| Microcontrolador | Dual-core Xtensa 32-bit LX6 |
| Frecuencia | 240 MHz |
| RAM | 8 MB |
| Flash | 4MB + PSram |
| WiFi | IEEE 802.11 b/g/n |
| Bluetooth | BLE 5.0 |
| Pines GPIO | 38 (limitados por memoria flash) |
| ADC | 12-bit, 8 canales (0-3.3V) |
| **Cámara** | OV2640 (2MPx) ⭐ |

---

## 🧪 VERIFICACIÓN E INSTALACIÓN

### Paso 1: Instalar librería de temperatura
En PlatformIO (automático):
```ini
lib_deps =
    paulstoffregen/OneWire@2.3.7
    milesburton/DallasTemperature@^4.1.0
    bblanchon/ArduinoJson@^7.4.2
```

### Paso 2: Configurar PlatformIO
```ini
[env:freenove_esp32_s3_wroom]
platform = espressif32
board = freenove_esp32_s3_wroom
framework = arduino
monitor_speed = 115200
```

### Paso 3: Verificar conexiones físicas
- [ ] DS18B20 conectado a GPIO 6 + Pull-up 4.7kΩ
- [ ] ACS712 conectado a GPIO 7
- [ ] Relés en GPIO 4 y 5
- [ ] WiFi accesible desde la S3 CAM
- [ ] Servidor PRERMI respondiendo

### Paso 4: Subir código y monitorear
```bash
platformio run --target upload
platformio device monitor --port COM3 -b 115200
```

---

## 📋 TROUBLESHOOTING

### "Sensor de temperatura NO DETECTADO"
- ✓ Verificar conexión a GPIO 6
- ✓ Verificar resistencia de 4.7kΩ entre DATA y VDD
- ✓ Verificar voltaje de 3.3V en VDD
- ✓ Probar con multímetro continuidad entre pines

### "Corriente negativa o erática"
- ✓ Verificar alimentación del ACS712 (3.3V)
- ✓ Verificar GND común entre S3 CAM y sensor
- ✓ Si no hay carga, debe leer ~0.0A (Dead Band 150mA)
- ✓ Verificar capacitores de desacoplamiento

### "Error: No autorizado"
- ✓ Verificar token: `esp8266_sensor_token` ✅
- ✓ Parámetro: `?token=esp8266_sensor_token`

### "No conecta al WiFi"
- ✓ Verificar SSID y contraseña correctos
- ✓ S3 CAM solo conecta a 2.4GHz (no 5GHz)
- ✓ Verificar que el ESP32-S3 tenga exposición a antena WiFi

---

## 🆚 LIMITACIONES S3 CAM vs DEVKIT1

| Aspecto | S3 CAM | DEVKIT1 |
|--------|--------|---------|
| Disponibilidad | Media | ⭐⭐⭐ |
| Documentación | Poca | ⭐⭐⭐ |
| Precio | $$$ | ⭐⭐ |
| Pines GPIO Libres | 10-15 | ⭐⭐⭐ 20+ |
| ADC Estable | Media | ⭐⭐⭐ |
| **Cámara** | ⭐⭐⭐ Integrada | ❌ No |
| Consumo Potencia | Bajo | Bajo |
| WiFi/BLE | ✅ | ✅ |

---

## 🔄 CÓMO CAMBIAR A DEVKIT1 EN EL FUTURO

Si necesitas cambiar a DEVKIT1:

**Archivo de referencia guardado en:** `REFERENCIA_DEVKIT1.md`

Pasos rápidos:
1. Cambiar pines en main.cpp: GPIO 4,5,6,7 → 23,22,14,35
2. Cambiar token: esp8266_sensor_token → esp32_devkit1_biomasa
3. Cambiar platformio.ini: freenove_esp32_s3_wroom → esp32doit-devkit-v1
4. Reconfigurar conexiones físicas de sensores
5. Compilar y cargar

---

## 📚 RECURSOS S3 CAM

- [Datasheet ESP32-S3](https://www.espressif.com/sites/default/files/documentation/esp32-s3_datasheet_en.pdf)
- [Pineado S3 CAM](https://github.com/Freenove/Freenove_ESP32_S3_WROOM_Board)
- [PlatformIO ESP32](https://docs.platformio.org/en/stable/boards/espressif32/freenove_esp32_s3_wroom.html)

---

## 🎯 RESUMEN

**Estado Actual:** ✅ Operativo con ESP32-S3 CAM
- Token: `esp8266_sensor_token`
- Pines: GPIO 4, 5, 6, 7
- Servidor: PRERMI en 192.168.1.106:8080
- Configuración alternativa guardada en: `REFERENCIA_DEVKIT1.md`

**Última actualización:** 22/02/2026
