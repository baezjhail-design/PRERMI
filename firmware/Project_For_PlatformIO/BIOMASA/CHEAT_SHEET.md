# 📌 CHEAT SHEET - GUÍA DE REFERENCIA RÁPIDA

## 🔌 REFERENCIA DE PINES

```
D0 = GPIO16  │ D4 = GPIO2   │ D8 = GPIO15
D1 = GPIO5   │ D5 = GPIO14  │ A0 = ADC0
D2 = GPIO4   │ D6 = GPIO12  │ RX = GPIO3
D3 = GPIO0   │ D7 = GPIO13  │ TX = GPIO1
```

## 📋 PINES DE BIOMASA

| Pin  | GPIO  | Componente              | Estado       |
|------|-------|-------------------------|--------------|
| D1   | 5     | Relé Calentador         | OUTPUT       |
| D2   | 4     | Relé Ventilador         | OUTPUT       |
| D4   | 2     | Sensor Temperatura      | ONEWIRE      |
| D5   | 14    | Botón START             | INPUT_PULLUP |
| D6   | 12    | Botón STOP              | INPUT_PULLUP |
| D7   | 13    | OLED SDA                | I2C          |
| D8   | 15    | OLED SCL                | I2C          |
| A0   | ADC   | Sensor Corriente        | INPUT        |

---

## 🌐 CONFIGURACIÓN WIFI

```cpp
SSID:       BIOMASA_SYSTEM
Password:   biomasa2026
Puerto:     80 (HTTP)
IP:         192.168.4.1

Serial:
  Baudrate: 115200
  Databits: 8
  Stopbits: 1
  Parity:   None
```

---

## 🔐 CREDENCIALES

```
WiFi Password:  biomasa2026
Admin Password: ADMIN_PRERMI
```

---

## 📊 UMBRALES PRINCIPALES

```
Temperatura (Ventilador):    30.0°C
Voltaje ADC Referencia:      3.3V
Sensibilidad ACS712 (5A):    0.185 V/A
Offset ACS712:               1.65V (3.3V/2)
```

---

## 💾 VARIABLES GLOBALES CLAVE

```cpp
float currentTemperature    // Temperatura actual en °C
float currentCurrent        // Corriente en Amperios
float energyGenerated       // Energía acumulada en Wh
int systemState             // 0=STOP, 1=RUNNING
int heaterState             // 0=OFF, 1=ON
int fanState                // 0=OFF, 1=ON
```

---

## 🔧 FUNCIONES PRINCIPALES

| Función | Descripción | Parámetros | Retorna |
|---------|-------------|-----------|---------|
| `readTemperature()` | Lee sensor DS18B20 | - | void |
| `readCurrentSensor()` | Lee ACS712 | - | void |
| `activateHeater()` | Enciende calentador | - | void |
| `deactivateHeater()` | Apaga calentador | - | void |
| `activateFan()` | Enciende ventilador | - | void |
| `deactivateFan()` | Apaga ventilador | - | void |
| `updateDisplay()` | Actualiza OLED | - | void |

---

## 🌐 ENDPOINTS HTTP

```
GET /              → Página HTML completa
GET /api           → JSON con datos en tiempo real
GET /control?action=start  → Iniciar sistema
GET /control?action=stop   → Parar sistema
GET /control?action=admin&password=XXX → Login admin
```

---

## 📡 FORMATO JSON /api

```json
{
  "temperature": 25.3,
  "current": 0.12,
  "energy": 5.34,
  "system": 0,
  "heater": 0,
  "fan": 0
}
```

---

## 📍 UBICACIÓN DE CONFIGURACIONES EN CODE

Busca estas líneas para configurar:

```
SSID WiFi:           main.cpp línea ~56
Contraseña WiFi:     main.cpp línea ~57
Contraseña Admin:    main.cpp línea ~58
Temp Ventilador:     main.cpp línea ~62
Sensibilidad ACS:    main.cpp línea ~66
```

---

## 🔄 CICLOS PRINCIPALES

| Acción | Intervalo | Función |
|--------|-----------|---------|
| Lectura Sensores | 1000ms | readTemperature(), readCurrentSensor() |
| Actualizar OLED | 1000ms | updateDisplay() |
| Evaluar Lógica | 1000ms | Chequear temperatura > 30°C |
| Manejar Botones | Continuo | digitalRead() en loop |
| Peticiones HTTP | Bajo demanda | server.handleClient() |

---

## 🎛️ CAMBIOS RÁPIDOS

### Cambiar Temperatura Ventilador
```cpp
const float TEMP_THRESHOLD = 25.0;  // De 30.0 a 25.0
```

### Cambiar SSID
```cpp
const char* ssid = "MI_RED";  // Cambiar nombre
```

### Cambiar Contraseña Wifi
```cpp
const char* password = "nueva_pass";
```

### Cambiar Contraseña Admin
```cpp
const char* admin_password = "NUEVA_ADMIN";
```

### Cambiar Sensor ACS712
```
5A:   0.185
20A:  0.100
30A:  0.066
```

---

## 🧰 LIBRERÍAS REQUERIDAS

```
Adafruit SSD1306 (OLED)
Adafruit GFX Library
OneWire (Temperatura)
DallasTemperature (Temperatura)
ESP8266WiFi (WiFi)
ESP8266WebServer (Servidor HTTP)
LittleFS (Sistema de archivos)
```

---

## 🐛 ERRORES COMUNES

| Error | Causa | Solución |
|-------|-------|----------|
| `'Adafruit_SSD1306' not found` | Librería no instalada | `platformio lib install adafruit/Adafruit SSD1306` |
| `Compilation error` | Sintaxis incorrecta | Ver línea señalada en compilador |
| `COM port not found` | Driver CH340 no instalado | Instalar driver usb |
| `OLED negro` | Pines I2C incorrectos | Verificar pines SDA/SCL |
| `Falsa lectura temperatura` | Sensor desconectado | Verificar conexión OneWire |
| `WiFi desconecta` | Fuente de poder insuficiente | Usar fuente 5V/2A+ |

---

## 💻 COMANDOS PLATFORMIO

```bash
pio build              # Compilar
pio upload             # Subir código
pio device monitor     # Monitor serial
pio lib install <lib>  # Instalar librería
pio run -e <env>       # Compilar para ambiente
```

---

## 🔌 DIAGRAMA I2C OLED

```
ESP8266 ──[4.7kΩ]─ 3.3V
   D7  ─────────── OLED SDA
   D8  ─────────── OLED SCL
   GND ─────────── OLED GND
3.3V    ─────────── OLED VCC
```

---

## 🌡️ DIAGRAMA SENSOR TEMPERATURA

```
           4.7kΩ
3.3V ────‖─── D4
          ├─ DS18B20 Data
GND ──────┴─ DS18B20 GND
```

---

## 📊 DIAGRAMA SENSOR CORRIENTE

```
ESP8266          ACS712
A0  ──── OUT (con 100nF a GND)
3.3V ──── VCC
GND  ──── GND
```

---

## 🖨️ SALIDA SERIAL ESPERADA

```
=== SISTEMA BIOMASA INICIANDO ===
Conectando a WiFi: BIOMASA_SYSTEM
WiFi Conectado. IP: 192.168.4.1
IP AP: 192.168.4.1
Servidor web iniciado
Temperatura: 25.3 °C
Corriente: 0.12 A
=== SISTEMA LISTO ===
```

---

## 📱 URLS ÚTILES

```
Página Principal:     http://192.168.4.1/
API JSON:            http://192.168.4.1/api
Iniciar Sistema:     http://192.168.4.1/control?action=start
Parar Sistema:       http://192.168.4.1/control?action=stop
Login Admin:         http://192.168.4.1/control?action=admin&password=ADMIN_PRERMI
```

---

## ⏱️ TIEMPOS CRÍTICOS

```
Lectura Temperatura: ~750ms (DS18B20)
Ciclo Loop:          ~10ms
Lectura ADC:         <1ms
Actualización OLED:  ~100ms
Debounce Botones:    50ms
```

---

## 🎯 FLUJO LÓGICO SISTEMA

```
┌─ Lectura Temperatura
├─ Lectura Corriente
├─ ¿systemState == 1?
│  ├─ SÍ: ¿Temp > 30°C?
│  │  ├─ SÍ: Ventilador ON
│  │  └─ NO: Ventilador OFF
│  └─ NO: Todo OFF
├─ Actualizar OLED
├─ Chequear Botones
└─ Procesar HTTP requests
```

---

## 🔐 NIVELES DE ACCESO

```
Anónimo:        Lectura de datos (/api)
Usuario:        Control básico (botones start/stop)
Administrador:  Funciones avanzadas (contraseña)
```

---

## 📊 VOLTAJES EN EL SISTEMA

```
Fuente ESP8266:     5V USB
Salida GPIO:        3.3V (tolerancia 3.0-3.6V)
Sensores:           3.3V
Relés/Carga:        12V (separada)
Optocopladores:     3.3V entrada, 12V salida
```

---

## 💡 CONSUMO DE CORRIENTE APROXIMADO

```
ESP8266 idle:       10-50mA
ESP8266 WiFi TX:    100-200mA
OLED:               5-10mA
Calentador ON:      1.5A @ 12V
Ventilador ON:      0.3A @ 12V
```

---

## 📈 ALMACENAMIENTO DATOS

```
RAM (variables):      ~80KB disponible
SPIFFS/LittleFS:      ~2MB disponible
EEPROM:               4KB disponible
```

---

## 🔍 DEBUG SERIALES

```cpp
Serial.print()        // Imprimir sin salto
Serial.println()      // Imprimir con salto
Serial.printf()       // Imprimir con formato
Serial.write()        // Escribir byte
```

Ej: `Serial.printf("Temp: %.2f\n", currentTemperature);`

---

## 🎨 COLORES HTML PÁGINA

```css
Púrpura primario:   #667eea
Púrpura secundario: #764ba2
Verde éxito:        #4CAF50
Rojo error:         #f44336
Gris fondo:         #f5f5f5
Blanco:             #ffffff
```

---

## 📌 REGLAS DE SEGURIDAD

```
✓ Cambiar contraseñas por defecto
✓ Usar HTTPS si es posible en futuro
✓ Validar input del usuario
✓ No guardar datos sensibles en código
✓ Revisar conexiones eléctricas
✓ Usar cablería apropiada para corriente
✓ Testear con multímetro
```

---

## 🚀 ATAJOS TECLADO VS CODE

```
Ctrl + B        Compilar (PlatformIO)
Ctrl + Shift + P Upload (PlatformIO)
Ctrl + `        Terminal
Ctrl + /        Comentar/Descomentar
Ctrl + H        Buscar y Reemplazar
F12             DevTools (en navegador)
Ctrl + Alt + J  Device Monitor (PlatformIO)
```

---

## 📚 ARCHIVOS GENERADOS

```
src/main.cpp          Código principal
platformio.ini        Configuración
DOCUMENTACION.md      Manual completo
CAMBIOS_RAPIDOS.md    Referencia de cambios
GUIA_INSTALACION.md   Setup
ARQUITECTURA.md       Diagramas
PRUEBAS_EJEMPLOS.md   Test case
CHEAT_SHEET.md        Este archivo
```

---

## ✅ CHECKLIST ANTES DE IR A PRODUCCIÓN

- [ ] Cambiar SSID y contraseña WiFi
- [ ] Cambiar contraseña admin
- [ ] Probar todos los sensores
- [ ] Verificar conexiones eléctricas
- [ ] Calibrar sensor de corriente
- [ ] Testear bajo carga
- [ ] Revisar consumo de memoria
- [ ] Documentar cambios realizados
- [ ] Crear respaldo del código
- [ ] Etiquetar componentes

---

## 🔗 REFERENCIAS EXTERNAS

```
ESP8266 Pinout:      https://electronics.stackexchange.com
DS18B20 Datasheet:   https://datasheets.maximintegrated.com
ACS712 Datasheet:    https://www.allegromicro.com
Adafruit SSD1306:    https://github.com/adafruit/Adafruit_SSD1306
PlatformIO Docs:     https://docs.platformio.org
```

---

**Version 1.0 - Febrero 2026**
**Última actualización: [Hoy]**

