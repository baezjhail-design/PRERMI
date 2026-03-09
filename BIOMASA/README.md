# BIOMASA Sistema Integrado - README

## ✅ Estado Actual

El sistema BIOMASA está **100% integrado y funcional**. Todos los cambios han sido implementados correctamente.

## 🚀 Inicio Rápido

### 1. Verificar que todo funcione
```bash
# Accede a la herramienta de testing:
http://localhost/PRERMI/BIOMASA/test_biomasa.php
```

### 2. Ver el Dashboard
```
http://localhost/PRERMI/web/admin/biores.php
```

### 3. Compilar firmware ESP8266
- Abre PlatformIO o Arduino IDE
- Carga: `BIOMASA_FINAL_CODE/src/main.cpp`
- Configura WiFi en main.cpp
- Compila y carga

## 📁 Archivos Clave

| Archivo | Propósito |
|---------|-----------|
| `sensores_estado.php` | API GET/POST para sensores |
| `control_biomasa.php` | API para comandos START/STOP |
| `biores.php` | Dashboard en tiempo real |
| `test_biomasa.php` | Herramienta de testing |
| `../api/status.json` | Estado actual del sistema |
| `../api/control.json` | Comandos pendientes |
| `../api/mediciones_biomasa.json` | Histórico de mediciones |

## 🔄 Flujo de Funcionamiento

1. **Usuario hace click START** en biores.php
2. → Se envía comando al `control_biomasa.php`
3. → Se guarda en `control.json`
4. **ESP8266 lee el comando** cada 5 segundos
5. → Activa PTC y comienza a medir sensores
6. **ESP8266 envía datos** POST a `sensores_estado.php` cada 3 segundos
7. → Se actualiza `status.json`
8. → Se guarda histórico en `mediciones_biomasa.json`
9. **Dashboard biores.php** actualiza cada 3 segundos
10. → Muestra temperatura, corriente, energía en tiempo real

## 🧪 Tests Disponibles

Accede a: `http://localhost/PRERMI/BIOMASA/test_biomasa.php`

Tests incluidos:
- ✓ Verificar archivos existen
- ✓ Validar JSON files
- ✓ Probar endpoints API
- ✓ Simular ESP8266 POST
- ✓ Verificar base de datos
- ✓ Mostrar últimos errores

## 📊 Datos en Tiempo Real

### Temperatura
- Display: 0-100°C
- Umbral1: > 40°C = Ventilador ON
- Umbral2: < 35°C = Ventilador OFF

### Corriente
- Display: 0-10 Amperios
- Sensor: ACS712 en pin A0

### Energía Generada
- Cálculo: 6 Peltier × Voltaje × Corriente × Eficiencia × Temp Factor
- Base: 18W en condiciones normales
- Display: En Wh (dashboard convierte a kWh)

### Ventilador
- Automático: Se activa cuando temp > 40°C
- Se desactiva automáticamente cuando temp < 35°C

## 🎛️ Controles del Dashboard

| Botón | Función |
|-------|---------|
| ▶ Iniciar Generación | Envía START al ESP8266 |
| ⏹ Detener Sistema | Envía STOP, calcula energía total |
| Apagado Sistema Completo | Apaga todo inmediatamente |
| ⚠️ Apagado Emergencia | Apaga sensores específicos |

## 📝 Documentación Completa

Para detalles técnicos, lee:
- `GUIA_INTEGRACION_COMPLETA.md` - Guía detallada de arquitectura y troubleshooting
- `CAMBIOS.md` - Lista completa de cambios realizados
- `SETUP_RAPIDO.md` - Configuración inicial (si existe)

## 🔗 API Endpoints

### GET /PRERMI/BIOMASA/sensores_estado.php
Lee estado actual del sistema
```json
{
  "status": "ok",
  "data": {
    "sistema_activo": 1,
    "temperatura": {"estado":"activo", "valor":45.5, "timestamp":"..."},
    "ventilador": {"estado":"activo", "valor":1, "timestamp":"..."},
    "corriente": {"estado":"sensando", "valor":2.3, "timestamp":"..."},
    "energia_generada": 18.5
  }
}
```

### POST /PRERMI/BIOMASA/sensores_estado.php
Envía datos desde ESP8266
```json
{
  "temperatura": 45.5,
  "corriente": 2.3,
  "ventilador": 1,
  "calentador": 1,
  "energia_generada": 18.5,
  "sistema_activo": 1
}
```

### POST /PRERMI/BIOMASA/control_biomasa.php?accion=START
Envía comando al sistema
```
accion = START | STOP | EMERGENCY | SYSTEM_OFF
```

## ⚙️ Configuración del ESP8266

En `main.cpp`, estos valores son ajustables:

```cpp
// WiFi
const char* ssid = "TU_RED";
const char* password = "TU_PASSWORD";
const char* serverAddress = "192.168.1.100";

// Temperaturas (°C)
#define TEMP_THRESHOLD_FAN_ON 40.0
#define TEMP_THRESHOLD_FAN_OFF 35.0

// Peltier
#define PELTIER_CELLS 6
#define PELTIER_VOLTAGE 12.0
#define SEEBECK_EFFICIENCY 0.05
#define RESIDUE_HEAT_MULTIPLIER 1.2

// Pines
#define TEMP_PIN D2      // DS18B20
#define HEATER_PIN D3    // PTC
#define FAN_PIN D4       // Ventilador
#define CURRENT_PIN A0   // ACS712
```

## 🐛 Troubleshooting Rápido

**Dashboard muestra "N/A" en sensores:**
- Verifica ESP8266 está conectado WiFi
- Revisa consola: F12 → Console en biores.php
- Accede a test_biomasa.php y prueba endpoints

**Ventilador no se enciende:**
- Verifica temperatura > 40°C en display
- Confirma relay está conectado pin D4
- Revisa main.cpp sección de hysteresis

**Energía no incrementa:**
- Verifica ACS712 está leyendo corriente (> 0.1A)
- Revisa cálculo en calculateEnergyGenerated()
- Confirma sistema_activo = 1

**Errores JSON:**
- Verifica archivos JSON son válidos: test_biomasa.php
- Revisa permisos de escritura en /api/

## 📞 Soporte

Para ver logs detallados:
1. Serial Monitor ESP8266: 115200 baud
2. Console navegador: F12 en biores.php
3. XAMPP logs: `C:\xampp\apache\logs\error.log`
4. Ver archivo: `test_biomasa.php` (mostrador de errores)

---

**Última actualización:** 24 Febrero 2026  
**Versión:** 1.0 FINAL  
**Status:** ✅ PRODUCTION READY

