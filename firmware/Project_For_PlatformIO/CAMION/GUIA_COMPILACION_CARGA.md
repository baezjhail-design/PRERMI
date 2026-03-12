# Guía: Cómo Compilar y Cargar el Código en el ESP8266

## 📋 Requisitos Previos

Necesitas tener instalado:

1. **PlatformIO** (que ya tienes en tu VS Code)
2. **Drivers USB del CH340/CH341** (para comunicación con ESP8266)
3. **El ESP8266MOD OLED** conectado a tu PC vía USB

---

## 🔧 Paso 1: Verificar la Instalación de Librerías

Abre el archivo `platformio.ini` en la carpeta del CAMION y asegúrate de que tenga:

```ini
[env:d1_mini]
platform = espressif8266
board = d1_mini
framework = arduino
monitor_speed = 115200
lib_deps = 
    ArduinoJson@6.21.0
    ESP8266WiFi
    Servo
```

Si faltan librerías:
- Click secundario en `platformio.ini`
- Selecciona "Upload to Project Dependencies"
- Busca: `ArduinoJson` (instala la última versión)

---

## 🔌 Paso 2: Conectar el ESP8266

1. Conecta el ESP8266 a tu PC con un cable USB
2. Comprueba en Administrador de Dispositivos que aparezca como "CH340" o "CH341"
3. Si no aparece, instala los drivers desde:
   - https://sparks.gogo.co.nz/ch340.html

---

## 🛠️ Paso 3: Configurar el Puerto Serial

En VS Code con PlatformIO:

1. Haz click en **PlatformIO** (ícono en la barra lateral)
2. Expande tu proyecto CAMION
3. Haz click en **Upload and Monitor**
4. PlatformIO automáticamente detectará el puerto

Si quieres hacerlo manualmente:

1. Abre la carpeta del CAMION en VS Code
2. Crea/edita `platformio.ini`
3. Agrega esta línea (remplaza COM4 con tu puerto):

```ini
[env:d1_mini]
platform = espressif8266
board = d1_mini
framework = arduino
monitor_speed = 115200
upload_port = COM4
monitor_port = COM4
```

---

## 📦 Paso 4: Compilar el Código

**Opción 1: Usando PlatformIO en VS Code**

1. Abre la carpeta `CAMION` en VS Code
2. Haz click en el icono **PlatformIO** (hormiguita) en la barra lateral
3. Expande el árbol y busca tu ambiente (ej: `d1_mini`)
4. Click en **Build** para compilar

Debería ver:
```
Building in release mode
Compiling .pio/build/d1_mini/src/main.cpp.o
Linking .pio/build/d1_mini/firmware.elf
...
=== [SUCCESS] Took X.XX seconds ===
```

**Opción 2: Desde Terminal**

Abre PowerShell en la carpeta del CAMION y ejecuta:

```powershell
platformio run --environment d1_mini
```

---

## 📥 Paso 5: Cargar (Upload) el Código en el ESP8266

**Opción 1: Usando PlatformIO en VS Code**

1. En PlatformIO, click en **Upload** (el icono de flecha hacia arriba)
2. Verás progreso:

```
Looking for upload port...
Serial port COM4
Connecting........_____....._____....._____
Hard resetting via RTS pin...
```

3. Espera a que termine (puede tomar 1-2 minutos)

**Opción 2: Desde Terminal**

```powershell
platformio run --environment d1_mini --target upload
```

---

## 🖥️ Paso 6: Monitorear la Salida Serial

Una vez que se cargue el código, verás el Monitor Serial:

```
##############################################
CAMIÓN INTELIGENTE - Sistema de Control
##############################################
Inicializando pines...
Inicializando servomotores...
Conectando a WiFi...
. . . . . .
✓ WiFi conectado!
IP: 10.0.0.123
SSID: Jhail-WIFI

Servidor HTTP iniciado en: http://10.0.0.123:80/control
✓ Sistema lista! Esperando comandos...
```

**Para ver el Monitor Serial:**
- En PlatformIO, click en **Monitoring** (ícono de gráfica)
- O presiona **Ctrl + Alt + M** en VS Code

---

## ⚙️ Paso 7: Configurar variables según tu Red WiFi

Si cambias de red WiFi o dirección IP:

1. Abre `main.cpp`
2. Busca la sección "CREDENCIALES DE WiFi":

```cpp
const char* WIFI_SSID = "Jhail-WIFI";      // Cambia aquí
const char* WIFI_PASS = "123.02589.";      // Cambia aquí
```

3. Reemplaza con tus credenciales reales
4. Guarda el archivo
5. **Recompila y carga el código** (repite pasos 4-5)

---

## 🧪 Paso 8: Probar la Conexión

Una vez cargado:

1. **Abre el Monitor Serial** (como en paso 6)
2. **Busca la IP** que aparece (ej: 10.0.0.123)
3. **Abre PowerShell** y prueba:

```powershell
ping 10.0.0.123
```

Deberías ver:
```
Respuesta desde 10.0.0.123: bytes=32 tiempo=45ms TTL=64
```

Si dice "tiempo de espera agotado", el ESP8266 no está en la red.

---

## 📝 Paso 9: Probar la Interfaz Web

1. **Abre navegador web**
2. Ve a: `http://localhost/PRERMI/CONTROL_DE_CAMIONES_A_DISTANCIA/INTERFAZ_DE_CONTROL.php`
3. Verifica que diga "Conectado" en la interfaz
4. Intenta presionar "ADELANTE"
5. Deberías ver en Monitor Serial:
   ```
   === Solicitud HTTP Recibida ===
   Headers: POST /control HTTP/1.1...
   Body: {"command":"FORWARD",...}
   Comando: FORWARD
   ...
   ```

---

## 🔄 Paso 10: Troubleshooting

### "Error: No matching protocol"
- Asegúrate de que el ESP8266 esté conectado por USB
- Intenta otro cable USB
- Reinicia vs code

### "Upload Failed"
- El ESP8266 puede estar en modo de bootloader
- Presiona el botón RESET del ESP8266 durante la carga
- Espera a que termine completamente

### "WiFi conectado! IP: 255.255.255.255"
- El ESP8266 no se conectó a WiFi correctamente
- Verifica SSID y contraseña en main.cpp
- Asegúrate que el router está a 2.4GHz (no 5GHz)

### "No se pudo contactar al ESP8266"
- En interfaz web, confírma IP en config.php
- Usa `ping 10.0.0.X` para verificar conectividad
- ESP8266 podría estar reiniciándose constantemente

---

## 📋 Resumen de Comandos PlatformIO

| Comando | Descripción |
|---------|------------|
| `platformio run` | Compilar |
| `platformio run --target upload` | Compilar y cargar |
| `platformio run --target clean` | Limpiar build |
| `platformio device monitor` | Monitor serial |
| `platformio run --target uploadfs` | Cargar SPIFFS (filesystem) |

---

## 🎯 Flujo Completo de Desarrollo

```
1. Editar código en main.cpp
   ↓
2. Guardar archivo (Ctrl+S)
   ↓
3. PlatformIO > Build (Ctrl+Shift+B)
   ↓
4. Si compila: PlatformIO > Upload
   ↓
5. Monitor Serial muestra salida
   ↓
6. Prueba en navegador web
   ↓
7. Ver logs en Monitor Serial
   ↓
8. Si hay error: volver a paso 1 y editar
```

---

## 💡 Tips Útiles

### Aumentar velocidad de compilación
En `platformio.ini`:
```ini
build_flags = -j4
```

### Ver más detalles en compilación
En `platformio.ini`:
```ini
build_flags = -v
```

### Resetear ESP8266 completamente
```powershell
platformio run --environment d1_mini --target erase
platformio run --environment d1_mini --target upload
```

---

## 📞 Contacto y Preguntas

Si algo no funciona:

1. Revisa los logs en Monitor Serial
2. Busca el error específico en Google
3. Consulta la documentación:
   - https://github.com/esp8266/Arduino
   - https://platformio.org/platforms/espressif8266

---

**Última actualización:** Marzo 6, 2026
