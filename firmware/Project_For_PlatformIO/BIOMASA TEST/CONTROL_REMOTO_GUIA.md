# 📡 SISTEMA DE CONTROL REMOTO BIOMASA

**Fecha de implementación:** 22/02/2026  
**Funcionalidad:** Control remoto del ESP32-S3 CAM desde la interfaz web de PRERMI

---

## 🎯 ¿QUÉ HACE?

Ahora puedes enviar comandos desde la web de PRERMI y verlos ejecutados en el ESP32-S3 CAM en **tiempo real** a través del Serial Monitor.

### Flujo de Comunicación:
```
┌─────────────┐        ┌──────────────┐        ┌──────────────┐
│ PRERMI Web  │        │  Servidor    │        │  ESP32-S3    │
│   HTML      │──POST→ │  comandos    │←─GET─  │  Arduino     │
│  (Botones)  │        │  .php        │        │  (Sensores)  │
└─────────────┘        └──────────────┘        └──────────────┘
                              ↓
                     Archivo JSON con
                     comandos pendientes
```

---

## 🚀 CÓMO USAR

### 1. **Acceder a Control Remoto**
```
http://localhost/PRERMI/BIOMASA/control_remoto.php
```

### 2. **Botones Rápidos Disponibles**

| Botón | Comando | Efecto |
|-------|---------|--------|
| ▶️ **Iniciar** | `inicio` | Activa calentador y ventilador |
| ⏹️ **Parar** | `parada` | Apaga calentador y ventilador |
| 🔍 **Diagnóstico** | `diagnostico` | Muestra estado de sensores |
| 🔄 **Reset** | `reset` | Reinicia el ESP32-S3 |

### 3. **Comandos Personalizados**
También puedes seleccionar comandos del dropdown y agregar descripción personalizada

---

## 📝 ARCHIVOS CREADOS/MODIFICADOS

### ✅ Nuevos archivos PHP:
- **`/PREBMI/BIOMASA/comandos.php`** - Maneja envío/recepción de comandos
  - GET: Arduino consulta comandos pendientes
  - POST: PRERMI web envía comandos

- **`/PRERMI/BIOMASA/control_remoto.php`** - Interfaz web con botones
  - Interfaz moderna y responsiva
  - Botones rápidos
  - Dropdown de comandos

### ✅ Modificado Arduino (main.cpp):
- **Nueva función `checkPendingCommands()`** - Consulta servidor cada 3 segundos
- **Nueva función `processCommand()`** - Procesa comandos recibidos
- **Loop actualizado** - Agrega verificación de comandos

---

## 🔧 FUNCIONAMIENTO DETALLADO

### En el LADO DEL SERVIDOR:

**Archivo: comandos.php**
```php
GET /PRERMI/BIOMASA/comandos.php?token=esp8266_sensor_token
  → Retorna el primer comando de la cola JSON
  → Si no hay comandos: {"status":"sin_comandos"}

POST /PRERMI/BIOMASA/comandos.php
  → Agrega nuevo comando a la cola JSON
  → Admin web → comando en JSON file → Arduino lo consulta
```

**Almacenamiento:**
```
/PRERMI/data/comandos/comandos_pendientes.json
```

### En el LADO DEL ARDUINO:

**Loop Principal:**
```cpp
Cada 3 segundos:
  1. Verifica si hay WiFi conectado
  2. Consulta: GET /PRERMI/BIOMASA/comandos.php?token=...
  3. Si respuesta tiene "comando":
     a. Extrae tipo y descripción
     b. Llama processCommand()
     c. Muestra en Serial Monitor
```

**Procesamiento:**
```cpp
if (comando == "inicio")       → activateFan() + activateHeater()
if (comando == "parada")       → deactivateFan() + deactivateHeater()
if (comando == "diagnostico")  → Muestra temperatura, corriente, etc.
if (comando == "reset")        → ESP.restart()
```

---

## 📊 SALIDA ESPERADA EN SERIAL MONITOR

Cuando presionas un botón en PRERMI, verás:
```
1500264 [COMANDO RECIBIDO DESDE PRERMI]
═══════════════════════════════════════════════════════════
  📩 Comando: inicio
  📝 Descripción: Iniciar sistema BIOMASA
═══════════════════════════════════════════════════════════

✓ Sistema INICIADO desde PRERMI Web
>> Ventilador ENCENDIDO
>> Calentador ENCENDIDO
```

Ejemplo con diagnóstico:
```
1923456 [COMANDO RECIBIDO DESDE PRERMI]
═══════════════════════════════════════════════════════════
  📩 Comando: diagnostico
  📝 Descripción: Ejecutar diagnóstico
═══════════════════════════════════════════════════════════

✓ Ejecutando diagnóstico...
   Temperatura actual: 28.50 °C
   Corriente actual: 2.30 A
   Energía generada: 125.40 Wh
   Estado sistema: Activo
```

---

## 🔌 REQUISITOS

### Hardware:
- ✅ ESP32-S3 CAM en red WiFi
- ✅ Conectado al servidor PRERMI (192.168.1.106:8080)
- ✅ Token configurado: `esp8266_sensor_token`

### Software:
- ✅ Arduino con código actualizado (main.cpp)
- ✅ PHP 7.4+ en servidor
- ✅ Permisos de escritura en `/PRERMI/data/comandos/`

---

## 🧪 PRUEBAS

### Test Básico desde Terminal:

**1. Verificar que no hay comandos:**
```bash
curl "http://192.168.1.106:8080/PRERMI/BIOMASA/comandos.php?token=esp8266_sensor_token"
# Resultado: {"status":"sin_comandos","msg":"No hay comandos pendientes"}
```

**2. Enviar comando (requiere sesión):**
```bash
# Esto se hace desde la interfaz web, pero en terminal:
curl -X POST http://192.168.1.106:8080/PRERMI/BIOMASA/comandos.php \
  -H "Content-Type: application/json" \
  -d '{"comando":"inicio","descripcion":"Prueba desde curl"}'
# Resultado: {"status":"ok","msg":"Comando enviado al Arduino: inicio",...}
```

**3. Arduino consulta y ejecuta:**
```
Verás en Serial Monitor: ✓ Sistema INICIADO desde PRERMI Web
```

---

## 🔄 CICLO COMPLETO

```
[T=0s] Usuario presiona "Iniciar" en PRERMI web
        ↓
[T=0.1s] PRERMI POST → /comandos.php
        ↓
[T=0.2s] Servidor guarda en JSON: {"tipo":"inicio",...}
        ↓
[T=3s] Arduino ejecuta checkPendingCommands()
        ↓
[T=3.1s] GET /comandos.php → recibe comando
        ↓
[T=3.2s] processCommand("inicio", "...")
        ↓
[T=3.3s] Activa relés, muestra Serial
        ↓
[T=3.4s] Servidor elimina comando de JSON
```

---

## 📋 COMANDOS DISPONIBLES

| Comando | Descripción | Efecto |
|---------|-------------|--------|
| `inicio` | Iniciar sistema | Activa ventilador + calentador |
| `parada` | Detener sistema | Apaga ventilador + calentador |
| `diagnostico` | Diagnóstico | Lee sensores y muestra valores |
| `reset` | Reinicio | Reinicia el ESP32-S3 |
| `calibracion` | Calibración | (Pendiente de implementar) |

---

## 🛠️ AGREGAR NUEVOS COMANDOS

Para agregar un nuevo comando, edita `main.cpp`:

```cpp
void processCommand(String command, String description) {
  // ... código existente ...
  
  else if (command == "mi_nuevo_comando") {
    Serial.println("✓ Ejecutando mi nuevo comando");
    // Aquí va tu código
  }
}
```

Luego agrega la opción al dropdown en `control_remoto.php`:
```php
<option value="mi_nuevo_comando">Mi Nuevo Comando</option>
```

---

## ⚠️ NOTAS IMPORTANTES

1. **El Arduino consulta cada 3 segundos** - No esperes respuesta instantánea
2. **Requiere WiFi conectado** - Sin WiFi no hay comandos
3. **Token debe ser correcto** - `esp8266_sensor_token` para S3 CAM
4. **Un comando a la vez** - Se procesa el primero de la cola
5. **Serial Monitor debe estar abierto** - Para ver los mensajes

---

## 🚀 CÓMO COMPILAR Y CARGAR

```bash
# 1. Cierra el Serial Monitor
# 2. Ejecuta:
cd "D:\Project_For_PlatformIO\BIOMASA TEST"
platformio run --target clean
platformio run --target upload

# 3. Abre Serial Monitor:
platformio device monitor -b 115200
```

---

## 📞 TROUBLESHOOTING

### "Comando no aparece en Serial"
- ✓ Verifica que Arduino tenga WiFi conectado
- ✓ Verifica que el token sea correcto
- ✓ Espera 3+ segundos (tiempo de consulta)
- ✓ Revisa el archivo JSON en `/PRERMI/data/comandos/`

### "Error de permisos al guardar comando"
- ✓ Verifica permisos de `/PRERMI/data/comandos/`
- ✓ Ejecuta: `chmod 777 /PRERMI/data/comandos/`

### "POST devuelve error 'Debe estar logueado'"
- ✓ Inicia sesión en PRERMI primero
- ✓ Accede desde el navegador de tu sesión

---

**Última actualización:** 22/02/2026  
**Estado:** ✅ Operativo  
**Versión:** 1.0 - Control Remoto Básico
