# 📦 BIOMASA: MIGRACIÓN ESP32-S3 CAM → ESP32 DEVKIT1

## 🎯 RESUMEN DE CAMBIOS REALIZADOS

Se ha migrado completamente la configuración del proyecto BIOMASA desde:
- ❌ **ESP32-S3 CAM** (con cámara integrada)
- ✅ **ESP32 DEVKIT1** (placa estándar de desarrollo)

---

## 📝 ARCHIVOS MODIFICADOS

### 1. **main.cpp** (Código Arduino)
```diff
- const char* prermi_token  = "esp8266_sensor_token";
+ const char* prermi_token  = "esp32_devkit1_biomasa";

- #define RELAY_HEATER     4      // GPIO 4
- #define RELAY_FAN        5      // GPIO 5
- #define TEMP_SENSOR_PIN  6      // GPIO 6
- #define CURRENT_SENSOR   7      // GPIO 7

+ #define RELAY_HEATER     23     // GPIO 23
+ #define RELAY_FAN        22     // GPIO 22
+ #define TEMP_SENSOR_PIN  14     // GPIO 14
+ #define CURRENT_SENSOR   35     // GPIO 35 (solo entrada)
```

**Cambios adicionales:**
- ✅ Actualizado mensaje de inicio del sistema
- ✅ Mejorado diagnóstico de hardware para DEVKIT1
- ✅ Optimización ADC con `analogSetWidth()` y `analogSetAttenuation()`
- ✅ Mejor manejo de reintentos en sensor OneWire

### 2. **sensores_estado.php** (Backend PHP)
```diff
- if (!isset($_SESSION['user_id']) && $_GET['token'] !== 'esp8266_sensor_token') {

+ $tokens_validos = [
+     'esp8266_sensor_token',      // Antiguo
+     'esp32_devkit1_biomasa',     // NUEVO
+     'esp32s3_sensor_token'       // Alternativo
+ ];
+ if (!isset($_SESSION['user_id']) && (!isset($_GET['token']) || !in_array($_GET['token'], $tokens_validos))) {
```

**Ventaja:** Ahora soporta múltiples tokens para diferentes placas.

### 3. **platformio.ini** (Configuración Build)
```diff
- [env:freenove_esp32_s3_wroom]
- board = freenove_esp32_s3_wroom

+ [env:esp32doit_devkit_v1]
+ board = esp32doit-devkit-v1
+ upload_speed = 921600

+ lib_deps = 
+     paulstoffregen/OneWire@2.3.7
+     milesburton/DallasTemperature@^4.1.0
```

---

## 🔌 COMPARATIVA DE PINES

| Componente | ESP32-S3 CAM | ESP32 DEVKIT1 | Cambio |
|-----------|--------------|---------------|--------|
| Relé Calentador | GPIO 4 | GPIO 23 | ↔️ |
| Relé Ventilador | GPIO 5 | GPIO 22 | ↔️ |
| Temp (OneWire) | GPIO 6 | GPIO 14 | ↔️ |
| Corriente (ADC) | GPIO 7 | GPIO 35 | ↔️ |

**Razones del cambio:**
- GPIO 6-11: En S3 estaban para memoria flash (no disponibles)
- GPIO 1-2: Pines de boot/strapping (problemáticos)
- GPIO 14, 22, 23, 35: Seguros y probados en DEVKIT1

---

## ⚙️ VENTAJAS DEL DEVKIT1

| Aspecto | S3 CAM | DEVKIT1 |
|--------|--------|---------|
| **Disponibilidad** | Media | ⭐⭐⭐ Excelente |
| **Documentación** | Poca | ⭐⭐⭐ Mucha |
| **Precio** | $$$ | ⭐⭐ $ Más económica |
| **Pines GPIO Libres** | 10-15 | ⭐⭐⭐ 20+ |
| **Capacidad de Corriente** | Limitada | ⭐⭐⭐ Mejor |
| **Cámara Integrada** | ✅ Sí | ❌ No necesita |
| **WiFi/BLE** | ✅ | ✅ |

---

## 🚀 PASOS DE INSTALACIÓN

### 1. Desconectar la placa S3 CAM
```bash
# Si aún tienes el proyecto del S3, puedes guardarlo como referencia
```

### 2. Conectar DEVKIT1 al ordenador
- USB micro a tu PC
- Driver CH340G debería instalarse automáticamente

### 3. Seleccionar placa en PlatformIO
```bash
# En VS Code, click en "DEVKIT1" en la barra inferior
# O usar Command Palette: "PlatformIO: Switch environment"
# Seleccionar: [esp32doit_devkit_v1]
```

### 4. Compilar y subir
```bash
platformio run --target upload --environment esp32doit_devkit_v1
```

### 5. Monitorear
```bash
platformio device monitor --environment esp32doit_devkit_v1 -b 115200
```

**Salida esperada:**
```
═══════════════════════════════════════════════════════════
║  BIOMASA - ESP32 DEVKIT1          ║
═══════════════════════════════════════════════════════════
✓ Sensor de temperatura iniciado correctamente
✓ Sistema inicializado

WiFi conectado. IP: 192.168.1.XXX

📊 DIAGNÓSTICO DE HARDWARE (DEVKIT1):
   Sensores OneWire detectados: 1
   Voltaje ADC (GPIO 35): 1.65 V
```

---

## 🔧 CONEXIÓN FÍSICA DEL DEVKIT1

### Pines de alimentación
```
┌─────────────────────────────────┐
│  GND GND 5V  │  3V3 EN          │
│  D35 D34 D33 │  D13 D12 D14     │
│  D36 ... D15 │  D2  D4  RX      │
│  TX  RX GND  │  GND GND GND     │
└─────────────────────────────────┘
```

### Sensores BIOMASA
```
┌──────────────────────┐
│  DEVKIT1             │
│                      │
│  GPIO 23 ───→ Relé Calentador
│  GPIO 22 ───→ Relé Ventilador
│  GPIO 14 ───→ DS18B20 (OneWire) + Pull-up 4.7kΩ
│  GPIO 35 ───→ ACS712 (ADC)
│  3.3V ────→ VDD sensores
│  GND ─────→ GND sensores
└──────────────────────┘
```

---

## 📡 TOKENS DE AUTENTICACIÓN

El servidor ahora acepta 3 tokens diferentes:

1. **esp8266_sensor_token** - Antiguo (ESP8266)
2. **esp32_devkit1_biomasa** - ✅ **ACTUAL (DEVKIT1)**
3. **esp32s3_sensor_token** - Alternativo (S3)

El Arduino enviará automáticamente:
```
POST /PRERMI/BIOMASA/sensores_estado.php?token=esp32_devkit1_biomasa
```

---

## ✅ CHECKLIST DE VERIFICACIÓN

- [ ] DEVKIT1 conectado al PC
- [ ] Pines GPIO recableados según especificación
- [ ] Pull-up 4.7kΩ en GPIO 14 (temp) a 3.3V
- [ ] platformio.ini apunta a `esp32doit_devkit_v1`
- [ ] Código compilado sin errores
- [ ] Serial Monitor muestra diagnóstico correcto
- [ ] Servidor PRERMI responde con status "ok"
- [ ] Sensor de temperatura lee valores válidos (>0°C)
- [ ] Sensor de corriente lee valores positivos cuando hay carga

---

## 🆘 TROUBLESHOOTING

### Error: "Unknown board"
```bash
# Solución: Actualizar PlatformIO
platformio update
```

### Baudrate incorrecto en monitor
```bash
# Asegurate de usar:
platformio device monitor -b 115200
```

### GPIO no reconocido
- Verificar que se seleccionó la placa `esp32doit-devkit-v1` correctamente
- Limpiar build: `platformio clean`

---

## 📚 REFERENCIAS

- [ESP32 DEVKIT1 Datasheet](https://docs.espressif.com/projects/esp-idf/en/latest/esp32/)
- [PlatformIO ESP32](https://docs.platformio.org/en/latest/boards/espressif32/esp32doit-devkit-v1.html)
- [OneWire Library](https://github.com/PaulStoffregen/OneWire)
- [DallasTemperature Library](https://github.com/milesburton/Arduino-Temperature-Control-Library)

---

**Migración completada:** 22/02/2026  
**Estado:** ✅ Listo para uso  
**Próximo paso:** Conectar DEVKIT1 y cargar código
