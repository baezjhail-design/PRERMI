# 📖 GUÍA DE INSTALACIÓN Y PRIMEROS PASOS

## ✅ Requisitos Previos

- Visual Studio Code instalado
- PlatformIO instalado en VS Code
- Drivers USB CH340G (para NodeMCU/ESP8266)
- Cable USB para ESP8266MOD
- Hardware BIOMASA conectado

---

## 🔌 PASO 1: INSTALAR DRIVERS USB (Si es necesario)

### Windows 10/11

1. Descarga el driver CH340G:
   https://sparks.gogo.co.nz/ch340.html

2. Descomprime y ejecuta el instalador

3. Reinicia tu computadora

### Linux
```bash
sudo apt-get install ch340g
```

### macOS
```bash
brew install ch340g-macos-driver
```

---

## 📁 PASO 2: ABRIR PROYECTO EN VS CODE

1. Abre VS Code
2. File → Open Folder
3. Selecciona: `d:\Project_For_PlatformIO\BIOMASA`
4. Espera a que PlatformIO cargue el proyecto

---

## 🔌 PASO 3: CONECTAR ESP8266MOD

1. Conecta el ESP8266MOD via USB
2. En VS Code, puedes ver el puerto en la esquina inferior:
   - Windows: `COM3`, `COM4`, etc.
   - Linux: `/dev/ttyUSB0`
   - macOS: `/dev/tty.usbserial-*`

---

## 🚀 PASO 4: COMPILAR Y SUBIR CÓDIGO

### Opción 1: Usando interfaz gráfica PlatformIO

1. Abre la paleta de comandos: `Ctrl + Shift + P`
2. Escribe: `PlatformIO: Build`
3. Presiona Enter

Si compila exitosamente, verás:
```
= [SUCCESSFUL] took X.XX seconds
```

Luego:
1. `Ctrl + Shift + P` nuevamente
2. `PlatformIO: Upload`
3. Espera a que se suba el código

### Opción 2: Botones en la barra

En la barra inferior izquierda verás:
- ✓ Build (compilar)
- → Upload (subir)
- ⚡ Serial Monitor

---

## 📊 PASO 5: VERIFICAR EN SERIAL MONITOR

1. Abre Serial Monitor: Botón ⚡ en la barra inferior
2. Selecciona velocidad: `115200 baud`
3. Deberías ver:

```
=== SISTEMA BIOMASA INICIANDO ===
...
WiFi Conectado. IP: 192.168.4.1
=== SISTEMA LISTO ===
```

---

## 📱 PASO 6: CONECTAR A LA PÁGINA WEB

### En tu celular o computadora:

1. **Busca red WiFi:**
   - Nombre: `BIOMASA_SYSTEM`
   - Contraseña: `biomasa2026`

2. **Abre navegador:**
   - URL: `http://192.168.4.1`

3. Deberías ver la página SCADA

---

## 🧪 PASO 7: PRUEBAS BÁSICAS

### Prueba 1: Leer Temperatura

1. En Serial Monitor verás cada segundo:
```
Temperatura: 25.3 °C
Corriente: 0.12 A
```

2. En página web, verás valores actualizándose

### Prueba 2: Activar Calentador

En página web:
1. Click en botón **[▶️ INICIAR]**
2. En Serial Monitor verás: `Calentador ACTIVADO`
3. El relé se debe escuchar (clic)
4. El LED en página se pone verde

### Prueba 3: Activar Ventilador

1. Calienta manualmente el sensor a >30°C
2. El ventilador debe activarse automáticamente
3. Deberías ver: `Ventilador ACTIVADO`

### Prueba 4: Botones Físicos

1. Presiona el botón START (GPIO14/D5)
   - Calentador se activa
   - OLED muestra "✓"

2. Presiona el botón STOP (GPIO12/D6)
   - Todo se apaga
   - OLED muestra "STOPPED"

---

## 🔧 TROUBLESHOOTING INICIAL

### Problema: No compila

**Error típico:**
```
error: 'Adafruit_SSD1306' does not name a type
```

**Solución:**
1. Abre Terminal: Ctrl + `
2. Ejecuta:
```bash
platformio lib install "adafruit/Adafruit SSD1306"
platformio lib install "adafruit/Adafruit GFX Library"
```

---

### Problema: No encuentra puerto USB

**Solución:**
1. Desconecta y reconecta el cable USB
2. Intenta otro puerto USB
3. Si es Windows, revisa Administrador de Dispositivos
4. Verifica que los drivers estén instalados

---

### Problema: Error de subida

**Mensaje:**
```
ERROR: Failed to connect target
```

**Soluciones:**
1. Presiona botón RESET en ESP8266
2. Espera a que parpadee una vez
3. Intenta upload nuevamente

O mueve el jumper BOOT a GND:
```
Antes de upload:
GPIO0 → GND  (modo bootloader)

Después de upload:
GPIO0 → 3.3V (modo normal)
```

---

### Problema: No aparece en Serial Monitor

**Soluciones:**
1. Abre Device Manager (Ctrl+Shift+P → Device Manager)
2. Selecciona el puerto correcto
3. Verifica baud rate: 115200
4. Presiona RESET en ESP8266
5. Deberías ver líneas de boot

---

### Problema: OLED no muestra nada

**Checklist:**
- ¿SDA en D7 (GPIO13)?
- ¿SCL en D8 (GPIO15)?
- ¿Voltaje: 3.3V?
- ¿Resistencia pull-up de 4.7k?
- ¿Dirección I2C correcta (0x3C)?

**Para identificar dirección:**
1. Crea archivo `test_i2c.cpp`:
```cpp
#include <Wire.h>

void setup() {
  Serial.begin(115200);
  Wire.begin(D7, D8);
  
  for(int addr = 1; addr < 127; addr++) {
    Wire.beginTransmission(addr);
    if (Wire.endTransmission() == 0) {
      Serial.printf("I2C encontrado: 0x%02X\n", addr);
    }
  }
}

void loop() {}
```

2. Sube y verifica dirección en Serial Monitor

---

### Problema: Sensor temperatura no funciona

**Si usas DS18B20:**
- ¿Resistencia pull-up de 4.7k entre data y 3.3V?
- ¿Conexión correcta: GND-Data-VCC?
- Prueba este código:

```cpp
#include <OneWire.h>
#include <DallasTemperature.h>

OneWire oneWire(D4);
DallasTemperature sensors(&oneWire);

void setup() {
  Serial.begin(115200);
  sensors.begin();
}

void loop() {
  sensors.requestTemperatures();
  Serial.println(sensors.getTempCByIndex(0));
  delay(1000);
}
```

---

## 🌐 CONEXIÓN DESDE OTRA RED WIFI

Por defecto, el ESP8266 crea su propia red WiFi en modo **AP** (Punto de Acceso).

### Opción 1: Conectar como Cliente WiFi También

En `main.cpp`, dentro de `setupWiFi()`:

```cpp
void setupWiFi() {
  WiFi.mode(WIFI_AP_STA);  // Ya está así por defecto
  WiFi.softAP(ssid, password);
  
  // AGREGAR ESTAS LÍNEAS:
  WiFi.begin("MI_SSID_ROUTER", "MI_PASSWORD_ROUTER");
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("");
    Serial.print("Conectado a router. IP: ");
    Serial.println(WiFi.localIP());
  }
}
```

Luego puedes acceder desde:
- Misma LAN: `http://[IP_DEL_ROUTER]`
- O siempre: Conéctate a `BIOMASA_SYSTEM` y accede a `192.168.4.1`

---

## 📊 MONITOREO AVANZADO

### Ver Consumo de Memoria

En Serial Monitor, agrega cada cierto tiempo:

```cpp
void printMemory() {
  Serial.printf("Free Heap: %d bytes\n", ESP.getFreeHeap());
  Serial.printf("Heap Fragmentation: %d %%\n", ESP.getHeapFragmentation());
}
```

Llama en `loop()`:
```cpp
if (millis() % 10000 == 0) {
  printMemory();
}
```

### Ver Voltaje de Entrada

```cpp
float voltage = (analogRead(A0) / 1023.0) * VOLTAGE_REFERENCE;
Serial.printf("Voltaje ADC: %.2f V\n", voltage);
```

---

## 📱 PROBLEMAS DE CONEXIÓN WEB

### No puedo acceder a 192.168.4.1

1. Verifica estar conectado a red WiFi `BIOMASA_SYSTEM`
2. Abre navegador en URL: `http://192.168.4.1/`
3. Espera 5 segundos (el servidor puede tardar)
4. Si no aparece, revisa Serial Monitor

### La página es lenta

- Reduce velocidad de baudrate si necesitas Serial Monitor
- Aumenta intervalo de lectura de sensores
- Comprueba conexión WiFi

### No puedo enviar comandos desde web

1. Abre Consola de JavaScript (F12)
2. Verifica errores en Network tab
3. Comprueba Serial Monitor para debug

---

## 🎓 PRÓXIMOS PASOS

Una vez que el sistema esté funcionando:

1. **Calibración de sensores:**
   - Compara temperatura con termómetro real
   - Ajusta offset si hay desviación

2. **Optimización:**
   - Cambia umbrales de temperatura
   - Ajusta tiempos de muestreo

3. **Persistencia:**
   - Guarda datos en EEPROM
   - Lee documentación en `DOCUMENTACION.md`

4. **Expansión:**
   - Agrega sensores adicionales
   - Integra con otros sistemas IoT
   - Crea dashboard personalizado

---

## 📞 AYUDA RÁPIDA

| Problema | Solución |
|----------|----------|
| No compila | `platformio lib install` |
| Puerto no aparece | Reinstalar drivers |
| OLED negro | Verificar pines I2C |
| Temperatura siempre 0 | Revisar sensor OneWire |
| WiFi desconecta | Antena o fuente de poder |
| Página lenta | Reducir frecuencia lectura |
| Botones no funcionan | Verificar GPIO e PULLUP |

---

## 📚 ARCHIVOS IMPORTANTES

```
BIOMASA/
├── src/
│   └── main.cpp              ← Código principal
├── include/
│   └── (vacío)
├── lib/
│   └── (librerías instaladas)
├── platformio.ini            ← Configuración proyecto
├── DOCUMENTACION.md          ← Documentación completa
├── CAMBIOS_RAPIDOS.md        ← Guía de modificaciones
└── GUIA_INSTALACION.md       ← Este archivo
```

---

## ✨ FELICIDADES

Tu sistema BIOMASA está listo para:
- ✅ Monitorear temperatura en tiempo real
- ✅ Medir corriente y energía generada
- ✅ Controlar calentador y ventilador
- ✅ Acceder desde página web
- ✅ Mostrar datos en pantalla OLED

¡Ahora a experimentar y mejorar! 🚀

