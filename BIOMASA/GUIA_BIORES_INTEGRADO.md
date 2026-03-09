## 🎛️ GUÍA DE PRUEBA - BIOMASA INTEGRADO EN BIORES.PHP

### ✅ Estado Actual
- ✓ Firmware compilado y subido al ESP32-S3 CAM
- ✓ Sistema de colas de comandos configurado
- ✓ biores.php integrado completamente
- ✓ sensores_estado.php con estado persistente

### 🚀 PASOS PARA PROBAR

#### 1️⃣ CONECTAR DISPOSITIVOS
```
- ESP32-S3 CAM debe estar conectado a: Jhail-WIFI (192.168.1.106)
- XAMPP ejecutándose con Apache
- Puerto 8080 activo en PRERMI
```

#### 2️⃣ ABRIR SERIAL MONITOR
```
En VS Code > Terminal > New Terminal
Luego: platformio device monitor -b 115200
```

#### 3️⃣ ACCEDER A BIORES.PHP
```
URL: http://localhost:8080/PRERMI/web/admin/biores.php
(O si necesita login, inicia sesión primero)
```

#### 4️⃣ PROBAR BOTONES DE CONTROL

**Botón "▶ Iniciar Generación":**
- Click en botón → Sistema entra en carga
- Espera 3 segundos
- Arduino recibe comando "inicio"
- Serial Monitor muestra:
  ```
  [COMANDO RECIBIDO DESDE PRERMI]
  📩 Comando: inicio
  ✓ Sistema INICIADO desde PRERMI Web
  >> Ventilador ENCENDIDO
  >> Calentador ENCENDIDO
  ```
- LED indicador pasa de ROJO (❌) a VERDE (✅)
- Estado muestra: "En Operación"

**Botón "⏹ Detener Sistema":**
- Click en botón → Sistema entra en carga
- Espera 3 segundos
- Arduino recibe comando "parada"
- Serial Monitor muestra:
  ```
  [COMANDO RECIBIDO DESDE PRERMI]
  📩 Comando: parada
  ✓ Sistema PAUSADO desde PRERMI Web
  >> Ventilador APAGADO
  >> Calentador APAGADO
  ```
- LED indicador pasa de VERDE (✅) a ROJO (❌)
- Estado muestra: "Detenido"

### 📊 SENSORES EN TIEMPO REAL

Las tarjetas de sensores en biores.php se actualizan cada 3 segundos:

**Temperatura:**
- Muestra valor en °C
- Bombilla se enciende en amarillo cuando está activa
- Estado: "Activo" o "Apagado"

**Ventilador:**
- Muestra estado: "Activo" o "Inactivo"
- Bombilla se enciende con animación
- Estado: "Activo" o "Apagado"

**Corriente:**
- Muestra valor en Amperios (A)
- Bombilla naranja cuando hay corriente
- Estado: "Sensando" o "Apagado"

### 🔍 DIAGRAMA DE FLUJO

```
biores.php (Click botón)
    ↓
control_biomasa.php (encola comando)
    ↓
/PRERMI/data/comandos/comandos_pendientes.json
    ↓
Arduino polls cada 3 segundos
    ↓
checkPendingCommands() lee JSON
    ↓
processCommand() ejecuta acción
    ↓
Arduino envía estado a sensores_estado.php
    ↓
biores.php refresca LEDs y sensores cada 3 segundos
```

### 📁 ARCHIVOS CLAVE

```
/PRERMI/BIOMASA/
├── control_biomasa.php      (Encola comandos desde biores.php)
├── comandos.php              (FIFO command queue)
├── sensores_estado.php       (Estados persistentes de sensores)
├── control_remoto.php        (Interfaz alternativa - no se usa en biores)

/PRERMI/data/
├── sensores/estado.json      (Estado actual del sistema)
├── comandos/
│   └── comandos_pendientes.json  (Cola de comandos)
```

### ⚠️ TROUBLESHOOTING

**Problema:** LEDs no se actualizan
- Solución: Verificar que Arduino esté conectado a WiFi (ver Serial Monitor)
- Solución: Verificar IP en Serial Monitor coincida con 192.168.1.106

**Problema:** Comandos no se ejecutan
- Solución: Verificar permisos de /PRERMI/data/comandos/
- Comando: `chmod 777 /PRERMI/data/comandos/`
- Solución: Revisar Serial Monitor para mensajes de error del Arduino

**Problema:** Serial Monitor muestra "WiFi no conectado"
- Solución: Verificar credenciales en main.cpp:
  - SSID: Jhail-WIFI
  - Password: 123.02589.
- Solución: Recompilar y subir firmware con credenciales correctas

**Problema:** Botones deshabilitados después de click
- Normal: Se habilitan cuando el Arduino procesa el comando (después de 3 seg)
- Verificar Serial Monitor que el comando fue recibido

### 🎯 COMPORTAMIENTO ESPERADO

1. **Al iniciar:**
   - Todos los botones habilitados
   - Todos los LEDs en ROJO (apagado)
   - Sensores muestran "N/A"
   - Estado: "Detenido"

2. **Al hacer click en "Iniciar":**
   - Botones se deshabilitan
   - Mensaje de carga
   - Después de 3 segundos: LED pasa a VERDE
   - Bombillas se encienden
   - Sensores se actualizan

3. **Al hacer click en "Detener":**
   - Botones se deshabilitan
   - Mensaje de carga
   - Después de 3 segundos: LED pasa a ROJO
   - Bombillas se apagan
   - Sensores muestran "N/A"

### ✨ CARACTERÍSTICAS COMPLETADAS

✅ Control central en biores.php
✅ LEDs indicadores con animaciones
✅ Sistema de comandos con colas JSON
✅ Polling cada 3 segundos desde Arduino
✅ Actualización automática de sensores en biores.php
✅ Estado persistente en archivos JSON
✅ Botones de parada de emergencia por sensor
✅ Histórico de temperatura y energía
✅ Interfaz responsive y moderna
✅ Autenticación con sesión

---

**Última actualización:** 22/02/2026
**Versión:** 1.0 - Sistema completamente integrado
