# 🧪 MANUAL DE PRUEBAS Y EJEMPLOS

## ✅ Checklist de Pruebas Después de Subir el Código

### Fase 1: Booteo del Sistema

- [ ] ESP8266 se reinicia correctamente
- [ ] OLED muestra "BIOMASA SYSTEM - Inicializando..."
- [ ] Serial Monitor muestra:
  ```
  === SISTEMA BIOMASA INICIANDO ===
  ...
  === SISTEMA LISTO ===
  ```

### Fase 2: WiFi y Red

- [ ] OLED muestra IP: `192.168.4.1`
- [ ] Puedes conectarte a red `BIOMASA_SYSTEM`
- [ ] Contraseña funciona: `biomasa2026`
- [ ] Puedes pingear: `ping 192.168.4.1`

### Fase 3: Acceso a Página Web

- [ ] Abres navegador en `http://192.168.4.1`
- [ ] Aparece página SCADA completa
- [ ] Estilos CSS cargan correctamente
- [ ] Página es responsiva en móvil

### Fase 4: Datos en Tiempo Real

- [ ] Valores de temperatura se actualizan
- [ ] Valores de corriente se actualizan
- [ ] Energía acumula correctamente
- [ ] Estado del sistema se refleja en web

### Fase 5: Control Local (Botones)

- [ ] Botón START (D5) activa calentador
- [ ] Botón STOP (D6) detiene todo
- [ ] OLED muestra ✓ cuando está activo
- [ ] OLED muestra "STOPPED" cuando está parado
- [ ] Serial Monitor registra cambios

### Fase 6: Control Remoto (Web)

- [ ] Botón INICIAR en web activa el sistema
- [ ] Botón DETENER en web apaga el sistema
- [ ] Página responde sin lag
- [ ] Indicadores (luces) cambien de color

### Fase 7: Lógica Automática

- [ ] Calentar sensor sobre 30°C
- [ ] Ventilador se activa automáticamente
- [ ] Enfríar sensor bajo 30°C
- [ ] Ventilador se desactiva automáticamente

---

## 📋 Ejemplos de Prueba Básicas

### Test 1: Verificar Lectura de Temperatura

**Procedimiento:**
1. Abre Serial Monitor a 115200 baud
2. Toca el sensor DS18B20 con tu dedo
3. Deberías ver en Serial:
   ```
   Temperatura: 25.3 °C
   Temperatura: 26.1 °C  (aumenta por calor del dedo)
   ```

**Si no funciona:**
- Verifica conexión física del sensor
- Verifica resistencia pull-up de 4.7kΩ
- Lee código de troubleshooting

---

### Test 2: Verificar Sensor de Corriente

**Procedimiento:**
1. Abre Serial Monitor
2. Sin corriente, deberías ver valores cerca de 0:
   ```
   Corriente: 0.05 A
   Corriente: -0.02 A
   Corriente: 0.03 A
   ```

3. Conecta una carga (bombilla 12V) al sensor
4. Deberías ver aumento significativo:
   ```
   Corriente: 0.50 A
   Corriente: 0.52 A
   Corriente: 0.51 A
   ```

**Nota:** Estos digan variar si el sensor no está calibrado.

---

### Test 3: Probar Relé Calentador

**Procedimiento:**
1. En página web, click en **[▶️ INICIAR]**
2. Escucharás un "clic" en la placa
3. Serial Monitor mostrará:
   ```
   Calentador ACTIVADO
   ```
4. Multímetro en pin D1 debe mostrar ~3.3V
5. Click en **[⏹️ DETENER]**
6. Serial Monitor mostrará:
   ```
   Calentador DESACTIVADO
   ```
7. Multímetro en pin D1 debe mostrar ~0V

---

### Test 4: Probar Relé Ventilador

**Igual que Test 3, pero:**
- Calienta el sensor por encima de 30°C
- El ventilador debe activarse automáticamente

**Alternativa manual:**
En `main.cpp`, modifica `loop()` para activar ventilador sin condición:

```cpp
// TEMPORAL PARA TEST:
if (millis() - lastSensorRead >= 1000) {
    readTemperature();
    readCurrentSensor();
    activateFan();  // AGREGAR ESTA LÍNEA
    updateDisplay();
    lastSensorRead = millis();
}
```

---

### Test 5: Datos JSON desde API

**En terminal:**

**Windows (PowerShell):**
```powershell
$response = Invoke-WebRequest -Uri "http://192.168.4.1/api"
$response.Content | ConvertFrom-Json | Format-Table
```

**Linux/macOS (bash):**
```bash
curl http://192.168.4.1/api | jq
```

**Respuesta esperada:**
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

### Test 6: Control Remoto vía HTTP

**Iniciar sistema:**
```
GET http://192.168.4.1/control?action=start
```

**Respuesta:**
```json
{"success": true}
```

**Detener sistema:**
```
GET http://192.168.4.1/control?action=stop
```

**Respuesta:**
```json
{"success": true}
```

---

### Test 7: Validación de Contraseña Admin

**Contraseña correcta:**
```
GET http://192.168.4.1/control?action=admin&password=ADMIN_PRERMI
```

**Respuesta:**
```json
{"success": true}
```

**Contraseña incorrecta:**
```
GET http://192.168.4.1/control?action=admin&password=WRONGPASSWORD
```

**Respuesta:**
```json
{"success": false}
```

---

## 🔧 Ejemplos de Modificación de Código

### Ejemplo 1: Agregar LED de Estado

**Objetivo:** Agregar un LED que parpadee cuando el sistema está activo.

**Paso 1:** Agregar pin en `main.cpp`
```cpp
#define STATUS_LED     D3  // GPIO0
```

**Paso 2:** Inicializar en `initializeSystem()`
```cpp
pinMode(STATUS_LED, OUTPUT);
```

**Paso 3:** Parpadear en `loop()`
```cpp
static unsigned long lastBlink = 0;
if (systemState == 1) {
    if (millis() - lastBlink >= 500) {
        digitalWrite(STATUS_LED, !digitalRead(STATUS_LED));
        lastBlink = millis();
    }
} else {
    digitalWrite(STATUS_LED, LOW);
}
```

---

### Ejemplo 2: Historial de Temperaturas

**Objetivo:** Guardar últimas 10 temperaturas para ver tendencia.

```cpp
#define HISTORY_SIZE 10
float tempHistory[HISTORY_SIZE];
int historyIndex = 0;

void addToHistory(float temp) {
    tempHistory[historyIndex] = temp;
    historyIndex = (historyIndex + 1) % HISTORY_SIZE;
}

void printTempTrend() {
    Serial.print("Historial: ");
    for (int i = 0; i < HISTORY_SIZE; i++) {
        Serial.print(tempHistory[i]);
        Serial.print(", ");
    }
    Serial.println();
}

// En readTemperature():
addToHistory(currentTemperature);
```

---

### Ejemplo 3: Alertas de Temperatura Crítica

**Objetivo:** Detener sistema si temperatura supera 50°C.

```cpp
#define CRITICAL_TEMP 50.0

void loop() {
    // ... código anterior ...
    
    if (currentTemperature > CRITICAL_TEMP) {
        Serial.println("!!! TEMPERATURA CRÍTICA !!!");
        deactivateHeater();
        deactivateFan();
        systemState = 0;
        
        // Mostrar alerta en OLED
        display.clearDisplay();
        display.setTextSize(2);
        display.setCursor(0, 0);
        display.println("ALERTA!");
        display.setTextSize(1);
        display.print("TEMP: ");
        display.println(currentTemperature);
        display.println("Sistema parado");
        display.display();
        
        delay(1000);
    }
}
```

---

### Ejemplo 4: Control por Horario

**Objetivo:** Solo funcionar entre 8 AM y 6 PM.

Primero, agrega NTP para hora precisa en `platformio.ini`:
```ini
lib_deps = 
    ...
    WifiClientSecure
    tzadjust
```

Luego:
```cpp
#include <time.h>

void setup() {
    // ... código anterior ...
    configTime(0, 0, "pool.ntp.org");
}

boolean isInWorkingHours() {
    time_t now = time(nullptr);
    struct tm* timeinfo = localtime(&now);
    int hour = timeinfo->tm_hour;
    return (hour >= 8 && hour <= 18);
}

// En handleControl():
if (action == "start") {
    if (isInWorkingHours()) {
        systemState = 1;
        activateHeater();
        success = true;
    } else {
        success = false;  // Fuera de horario
    }
}
```

---

### Ejemplo 5: Agregar Buzzer de Confirmación

```cpp
#define BUZZER_PIN D0

void setup() {
    pinMode(BUZZER_PIN, OUTPUT);
}

void beep(int times = 1) {
    for (int i = 0; i < times; i++) {
        digitalWrite(BUZZER_PIN, HIGH);
        delay(100);
        digitalWrite(BUZZER_PIN, LOW);
        delay(100);
    }
}

// En handleControl():
if (action == "start") {
    systemState = 1;
    activateHeater();
    beep(3);  // 3 beeps
    success = true;
}
```

---

## 📊 Ejemplo: Monitoreo en Tiempo Real

**Script Python para monitorear datos:**

```python
import requests
import json
from datetime import datetime

url = "http://192.168.4.1/api"

while True:
    try:
        response = requests.get(url, timeout=2)
        data = response.json()
        
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}]")
        print(f"Temperatura: {data['temperature']:.1f}°C")
        print(f"Corriente: {data['current']:.2f}A")
        print(f"Energía: {data['energy']:.1f}Wh")
        print(f"Sistema: {'RUNNING' if data['system'] == 1 else 'STOPPED'}")
        print(f"Calentador: {'ON' if data['heater'] == 1 else 'OFF'}")
        print(f"Ventilador: {'ON' if data['fan'] == 1 else 'OFF'}")
        
    except Exception as e:
        print(f"Error: {e}")
    
    time.sleep(1)
```

---

## 🧪 Prueba de Carga HTTP

**Test de rendimiento (con Apache Bench):**

```bash
# Instalar: apt-get install apache2-utils (Linux)
#           choco install apache-bench (Windows)

# Hacer 100 requests en paralelo
ab -n 100 -c 10 http://192.168.4.1/api

# Resultado esperado:
# - Requests per second: ~10-50
# - Latency: 10-100ms
```

---

## 📱 Pruebas en Diferentes Dispositivos

### Navegadores Probados

- ✅ Chrome/Chromium (Desktop)
- ✅ Firefox (Desktop)
- ✅ Safari (Desktop)
- ✅ Chrome (Móvil Android)
- ✅ Safari (Móvil iOS)
- ✅ Edge (Desktop)

### Resoluciones Testadas

- ✅ 1920×1080 (Desktop)
- ✅ 1366×768 (Laptop)
- ✅ 768×1024 (iPad)
- ✅ 375×667 (iPhone)
- ✅ 360×720 (Android)

---

## 🔍 Debug Avanzado

### Conectar Debugger Serial

```cpp
// Agregar al setup():
Serial.println("Iniciando en 3... 2... 1...");

// Ver valores en tiempo real:
void printDebugInfo() {
    Serial.printf("TEMP: %.2f | CURR: %.2f | EN: %.1f | SYS: %d | HEAP: %d\n",
                  currentTemperature,
                  currentCurrent,
                  energyGenerated,
                  systemState,
                  ESP.getFreeHeap());
}

// Llamar en loop cada 5 segundos:
static unsigned long lastDebug = 0;
if (millis() - lastDebug >= 5000) {
    printDebugInfo();
    lastDebug = millis();
}
```

### Monitor de Memoria

```cpp
void checkMemory() {
    uint32_t freeHeap = ESP.getFreeHeap();
    uint32_t maxFreeBlockSize = ESP.getMaxFreeBlockSize();
    uint8_t fragmentation = ESP.getHeapFragmentation();
    
    Serial.printf("Free Heap: %u bytes\n", freeHeap);
    Serial.printf("Max Free Block: %u bytes\n", maxFreeBlockSize);
    Serial.printf("Fragmentation: %u%%\n", fragmentation);
    
    if (freeHeap < 5000) {
        Serial.println("ADVERTENCIA: Memoria baja");
    }
}
```

---

## 🎯 Plan de Pruebas Completo (2-3 horas)

```
1. INSTALACIÓN Y BOOTEO (15 min)
   - Subir código
   - Ver serial output
   - Conectarse a WiFi

2. SENSORES (20 min)
   - Probar temperatura
   - Probar corriente
   - Ver valores en Serial

3. RELÉS (15 min)
   - Activar calentador
   - Activar ventilador
   - Verificar señales

4. OLED (10 min)
   - Verificar visualización
   - Probar estados
   - Verificar actualización

5. PÁGINA WEB (30 min)
   - Cargar página
   - Ver datos en tiempo real
   - Probr botones

6. LÓGICA AUTOMÁTICA (15 min)
   - Activar ventilador por temperatura
   - Verificar en página

7. ROBUSTEZ (30 min)
   - Presionar botones rápidamente
   - Acceso simultáneo desde web y botones
   - Ciclos ON/OFF repetidos

8. DOCUMENTACIÓN (15 min)
   - Leer y entender changelog
   - Revisar ejemplos
   - Planear modificaciones futuras
```

---

**¡Ahora estás listo para probar tu sistema BIOMASA! 🚀**

