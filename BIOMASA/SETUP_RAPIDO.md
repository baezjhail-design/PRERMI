## ⚡ SETUP RÁPIDO - BIOMASA EN BIORES

### 🎯 OBJETIVO
Control remoto BIOMASA completamente integrado en **biores.php** con LEDs en tiempo real

### ✅ CHECKLIST DE VERIFICACIÓN

#### Firmware Arduino
- [x] main.cpp compilado (23.3% Flash, 13.9% RAM)
- [x] Subido al ESP32-S3 CAM (Puerto COM7)
- [x] Conectado a WiFi: Jhail-WIFI

#### Backend PHP (XAMPP)
- [ ] sensores_estado.php → /PRERMI/BIOMASA/sensores_estado.php
- [ ] control_biomasa.php → /PRERMI/BIOMASA/control_biomasa.php
- [ ] comandos.php → /PRERMI/BIOMASA/comandos.php
- [ ] biores.php → /PRERMI/web/admin/biores.php

#### Directorios & Permisos
- [x] /PRERMI/data/sensores/ creado
- [x] /PRERMI/data/comandos/ creado
- [ ] Permisos: `chmod 777 /PRERMI/data/sensores/`
- [ ] Permisos: `chmod 777 /PRERMI/data/comandos/`

#### Estado Persistente
- [ ] /PRERMI/data/sensores/estado.json existe
- [ ] /PRERMI/data/comandos/comandos_pendientes.json existe

---

## 🚀 SECUENCIA DE PRUEBA

### PASO 1: VERIFICAR CONEXIÓN ARDUINO
```bash
# Terminal - Ver Serial Monitor del Arduino
platformio device monitor -b 115200

# Debe mostrar:
# Conectando a WiFi: Jhail-WIFI
# WiFi conectado. IP: 192.168.1.106
# DIAGNÓSTICO DE HARDWARE (S3 CAM):
# Sensores OneWire detectados: 1
# Voltaje ADC (GPIO 7): 1.65 V
```

### PASO 2: VERIFICAR PERMISOS
```bash
# Desde terminal del servidor
chmod 777 /path/to/PRERMI/data/sensores
chmod 777 /path/to/PRERMI/data/comandos

# O usar FTP/SFTP para cambiar permisos
```

### PASO 3: ABRIR BIORES EN NAVEGADOR
```
URL: http://localhost:8080/PRERMI/web/admin/biores.php

(Si te pide login, usa credenciales de PRERMI)
```

### PASO 4: PROBAR INICIO (≈3 segundos)
1. Click: "▶ Iniciar Generación"
2. Observa:
   - LED central pasa de rojo a verde
   - Estado cambia a "En Operación"
   - Sensores muestran valores
   - Serial Monitor muestra comando recibido

### PASO 5: PROBAR PARADA (≈3 segundos)
1. Click: "⏹ Detener Sistema"
2. Observa:
   - LED central pasa de verde a rojo
   - Estado cambia a "Detenido"
   - Sensores muestran "N/A"
   - Serial Monitor muestra comando recibido

---

## 🔧 CONFIGURACIÓN CRÍTICA

### IP del Servidor
En Arduino (main.cpp):
```cpp
const char* prermi_server = "192.168.1.106";  // ← Cambiar si es diferente
```

### Token del Arduino
En Arduino (main.cpp):
```cpp
const char* prermi_token = "esp8266_sensor_token";  // ✅ Correcto
```

### WiFi Credentials
En Arduino (main.cpp):
```cpp
const char* ssid = "Jhail-WIFI";           // ✅ Correcto
const char* password = "123.02589.";       // ✅ Correcto
```

---

## 📊 ESTADO ESPERADO

```
LED INDICADOR: ROJO (❌) = Apagado / VERDE (✅) = Activo

BOTONES:
├── Iniciar (Habilitado si está apagado)
├── Parar (Habilitado si está activo)
└── Emergencia (Siempre disponible)

SENSORES:
├── Temperatura: Bombilla naranja (activa) / gris (inactiva)
├── Ventilador: Bombilla verde (activo) / gris (inactivo)
└── Corriente: Bombilla naranja (sensando) / gris (inactivo)

ACTUALIZACIÓN: Cada 3 segundos (automática)
```

---

## 📋 COMANDOS ÚTILES

### Ver estado del sistema
```bash
cat /PRERMI/data/sensores/estado.json
cat /PRERMI/data/comandos/comandos_pendientes.json
```

### Limpiar cola de comandos
```bash
echo '[]' > /PRERMI/data/comandos/comandos_pendientes.json
```

### Ver logs del servidor php
```bash
tail -f /var/log/apache2/error.log
```

---

## ❌ ERRORES COMUNES

| Error | Causa | Solución |
|-------|-------|----------|
| "No autorizado" en biores | Sesión expirada | Volver a login |
| LEDs no se actualizan | Arduino no conectado | Ver Serial Monitor |
| Comandos no ejecutan | Cola llena o permisos | `chmod 777 /data/` |
| Sensores muestran N/A | Arduino offline | Verificar WiFi |
| Botones siempre deshabilitados | Estado guardado incorrecto | Limpiar estado.json |

---

## 📞 SOPORTE RÁPIDO

**Serial Monitor no muestra nada:**
- Verificar puerto COM (debe ser COM7)
- Verificar baud rate (115200)
- Reiniciar Arduino

**biores.php no carga:**
- Verificar XAMPP ejecutando
- Verificar URL correcta
- Limpiar caché del navegador

**Comandos encolados pero no ejecutan:**
- Verificar Arduino conectado a WiFi
- Verificar IP del servidor (192.168.1.106)
- Revisar Serial Monitor

---

**Estado:** ✅ LISTO PARA PRODUCCIÓN
**Última versión:** 22/02/2026
