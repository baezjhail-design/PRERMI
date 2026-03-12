# 🔧 CONFIGURACIÓN BIOMASA - ESP32 DEVKIT1

## ✅ CAMBIOS REALIZADOS

### Hardware: ESP32-S3 CAM → **ESP32 DEVKIT1**

La placa DEVKIT1 es la versión estándar del ESP32 con mejor disponibilidad de pines y circuitos de soporte más robustos.

---

## 🔌 DISTRIBUCIÓN DE PINES (DEVKIT1)

### Pines Usados en BIOMASA:

| Componente | GPIO | Función | Tipo |
|-----------|------|---------|------|
| **Relé Calentador** | GPIO 23 | OUTPUT | Digital |
| **Relé Ventilador** | GPIO 22 | OUTPUT | Digital |
| **Sensor Temperatura** | GPIO 14 | OneWire | Digital (SDA-compatible) |
| **Sensor Corriente** | GPIO 35* | ADC1_CH7 | Entrada ADC |

*GPIO 35 es pin **SOLO ENTRADA** ⚠️ (no se puede usar como salida)

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
           
       ││ (DATA a GPIO 14)
       └────[4.7kΩ]──── VDD (3.3V)
            GPIO 14
            
Conector típico:
┌─────────────┐
│ GND │DATA│VDD
│  1  │ 2  │ 3
└─────────────┘
```

**CONEXIÓN DEVKIT1:**
- Pin 1 (GND)     → GND (cualquier pin GND)
- Pin 2 (DATA)    → GPIO 14 + Pull-up 4.7kΩ a 3.3V
- Pin 3 (VDD)     → 3.3V

### ACS712-5A (Sensor de Corriente)
```
GND  ───→ GND (DEVKIT1)
OUT  ───→ GPIO 35 (ADC)
VCC  ───→ 3.3V (DEVKIT1)
```

### Relés
```
GPIO 23 (HEATER) ──[Optoacoplador/Transistor]──→ Relé Calentador
GPIO 22 (FAN)    ──[Optoacoplador/Transistor]──→ Relé Ventilador
```

---

## 🔧 CONFIGURACIÓN DE SOFTWARE

### Arduino (C++)
```cpp
#define RELAY_HEATER     23    // GPIO 23
#define RELAY_FAN        22    // GPIO 22
#define TEMP_SENSOR_PIN  14    // GPIO 14
#define CURRENT_SENSOR   35    // GPIO 35 (ADC solo entrada)

const char* prermi_token  = "esp32_devkit1_biomasa";
```

### PHP (sensores_estado.php)
```php
// Tokens válidos aceptados
$tokens_validos = [
    'esp8266_sensor_token',      // Antiguo
    'esp32_devkit1_biomasa',     // 🆕 NUEVO DEVKIT1
    'esp32s3_sensor_token'       // Alternativo S3
];
```

---

## 📊 ESPECIFICACIONES DEL ESP32 DEVKIT1

| Característica | Especificación |
|---|---|
| Microcontrolador | Dual-core Xtensa 32-bit LX6 |
| Frecuencia | 160/240 MHz |
| RAM | 520 KB |
| Flash | 4MB |
| WiFi | IEEE 802.11 b/g/n |
| Bluetooth | BLE 4.2 |
| Pines GPIO | 36 (2 son solo entrada) |
| ADC | 12-bit, 8 canales (0-3.3V) |

---

## 🧪 VERIFICACIÓN E INSTALACIÓN

### Paso 1: Instalar librería de temperatura
En PlatformIO:
```ini
lib_deps =
    OneWire
    DallasTemperature
    WiFi
    HTTPClient
```

### Paso 2: Configurar PlatformIO
```ini
[env:esp32dev]
platform = espressif32
board = esp32doit-devkit-v1
framework = arduino
monitor_speed = 115200
upload_speed = 921600
```

### Paso 3: Verificar conexiones físicas
- [ ] DS18B20 conectado a GPIO 14 + Pull-up 4.7kΩ
- [ ] ACS712 conectado a GPIO 35
- [ ] Relés en GPIO 22 y 23
- [ ] WiFi accesible desde el DEVKIT1
- [ ] Servidor PRERMI respondiendo

### Paso 4: Subir código y monitorear
```bash
platformio run -e esp32dev --upload-port COM3
platformio device monitor --port COM3 -b 115200
```

---

## 📋 TROUBLESHOOTING

### "Sensor de temperatura NO DETECTADO"
- ✓ Verificar conexión a GPIO 14
- ✓ Verificar resistencia de 4.7kΩ entre DATA y VDD
- ✓ Verificar voltaje de 3.3V en VDD
- ✓ Probar con multímetro continuidad entre pines

### "Corriente negativa o erática"
- ✓ Verificar alimentación del ACS712 (3.3V)
- ✓ Verificar GND común entre ESP32 y sensor
- ✓ Si no hay carga, debe leer ~0.0A (Dead Band 150mA)

### "Error: No autorizado"
- ✓ Verificar token: `esp32_devkit1_biomasa` ✅
- ✓ Parámetro: `?token=esp32_devkit1_biomasa`

---

## 🆚 CAMBIOS CON RESPECTO AL ESP32-S3 CAM

| Aspecto | S3 CAM | DEVKIT1 |
|--------|--------|---------|
| GPIO Temp | 6 | 14 |
| GPIO Corriente | 7 | 35 |
| GPIO Relé 1 | 4 | 23 |
| GPIO Relé 2 | 5 | 22 |
| Atenuación ADC | Sí (especial) | Opcional |
| WiFi | Integrado | Módulo externo |

---

## 📝 LIBRERIAS REQUERIDAS

```
OneWire@2.3.7
DallasTemperature@3.9.1
```

Instaladas vía PlatformIO `lib_deps`

---

## 🔐 SECURITY NOTES

- El token está en hardcode: considera usar EEPROM o archivo de configuración para producción
- GPIO 35 no tiene pull-up interno (considerar agregar si es necesario)
- Relés deben estar protegidos con optoacopladores si están conectados a cargas de alto voltaje

---

**Última actualización:** 22/02/2026
**Compatibilidad:** ESP32 DEVKIT1 (esp32doit-devkit-v1)
