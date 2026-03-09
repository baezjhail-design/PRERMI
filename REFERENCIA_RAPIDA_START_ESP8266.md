# ⚡ Referencia Rápida - Botón START / Comando ESP8266

## 🎬 Flujo Completo del Botón START

```
┌────────────────────────────────────────────────────────────┐
│              USUARIO HACE CLIC EN BOTÓN                   │
│              ▶ INICIAR GENERACIÓN                         │
│         (Envía comando START al ESP8266)                 │
└────────┬─────────────────────────────────────────────────┘
         │
         ↓
┌────────────────────────────────────────────────────────────┐
│              JAVASCRIPT (biores.php)                       │
│                                                            │
│  function iniciarSistema() {                              │
│    fetch('/PRERMI/BIOMASA/control_biomasa.php',       │
│           {body: 'accion=START'})                         │
│  }                                                         │
└────────┬─────────────────────────────────────────────────┘
         │
         ↓
┌────────────────────────────────────────────────────────────┐
│          PHP API (BIOMASA/control_biomasa.php)         │
│                                                            │
│  Recibe: accion=START                                     │
│  Valida: comando es válido (START ✓)                     │
│  Prepara: URL http://192.168.1.101/biores?cmd=START     │
│  Envía:   GET HTTP al ESP8266                           │
│  Recibe:  respuesta del ESP8266                          │
│  Devuelve: JSON con status OK/WARNING                    │
└────────┬─────────────────────────────────────────────────┘
         │
         ↓
┌────────────────────────────────────────────────────────────┐
│           ESP8266 (Recibe el Comando)                      │
│                                                            │
│  GET /biores?cmd=START                                   │
│                                                            │
│  ✓ Enciende Relé/Motor                                   │
│  ✓ Activa Sensor de Temperatura                          │
│  ✓ Activa Ventilador                                     │
│  ✓ Activa Sensor de Corriente                            │
│  ✓ Comienza a leer datos periódicamente                  │
│  ✓ Responde HTTP 200: "Sistema iniciado"                │
└────────┬─────────────────────────────────────────────────┘
         │
         ↓
┌────────────────────────────────────────────────────────────┐
│         PANEL ACTUALIZA (biores.php)                       │
│                                                            │
│  ✅ LED cambia de ROJO a VERDE                           │
│  ✅ Botones se actualizan (START deshabilitado)          │
│  ✅ Mensaje: "Sistema iniciado"                          │
│  ✅ Espera datos del ESP8266                             │
└────────┬─────────────────────────────────────────────────┘
         │
         ↓
┌────────────────────────────────────────────────────────────┐
│      ESP8266 ENVÍA DATOS CADA 5 SEGUNDOS                  │
│                                                            │
│  POST /BIOMASA/sensores_estado.php                   │
│  {accion: "temp_on", valor: "45.2"}                      │
│  {accion: "ventilador_on", valor: "85"}                  │
│  {accion: "corriente_on", valor: "12.5"}                │
└────────┬─────────────────────────────────────────────────┘
         │
         ↓
┌────────────────────────────────────────────────────────────┐
│       PANEL ACTUALIZA SENSORES CADA 3 SEGUNDOS            │
│                                                            │
│  🌡️ Temperatura: 45.2 °C  (Bombilla AMARILLA ✨)         │
│  ❄️ Ventilador: 85%       (Bombilla VERDE ✨)            │
│  ⚡ Corriente: 12.5 A     (Bombilla NARANJA 📡)          │
└────────────────────────────────────────────────────────────┘
```

## 📊 Antes y Después del START

### ANTES (Sistema Apagado)

```
🎛️ Control Total del Sistema
┌─────────────────────────────────┐
│  Estado: DETENIDO              │
│  LED: 🔴 ROJO (apagado)        │
│  Botones: START ✅ STOP ❌     │
│  Mensaje: Sistema listo          │
└─────────────────────────────────┘

📡 Sensores
┌──────────────────┬──────────────────┬──────────────────┐
│  🌡️ Temperatura  │  ❄️ Ventilador   │  ⚡ Corriente    │
│  🔴 Apagado      │  🔴 Apagado      │  🔴 Apagado      │
│  N/A             │  N/A             │  N/A             │
│  Esperando...    │  Esperando...    │  Esperando...    │
└──────────────────┴──────────────────┴──────────────────┘
```

### DESPUÉS (Haciendo Clic en START)

```
🎛️ Control Total del Sistema
┌─────────────────────────────────┐
│  Estado: EN OPERACIÓN           │
│  LED: 🟢 VERDE (brillando)      │
│  Botones: START ❌ STOP ✅      │
│  Mensaje: Sistema iniciado       │
│           Esperando datos        │
└─────────────────────────────────┘

📡 Sensores (Actualizados en tiempo real)
┌──────────────────┬──────────────────┬──────────────────┐
│  🌡️ Temperatura  │  ❄️ Ventilador   │  ⚡ Corriente    │
│  🟡 Activo       │  🟢 Activo       │  🟠 Sensando     │
│  45.2 °C         │  85 %            │  12.5 A          │
│  14:30:15        │  14:30:15        │  14:30:15        │
└──────────────────┴──────────────────┴──────────────────┘
```

## 🔧 Configuración del ESP8266

### Paso 1: Cambiar IP en PHP

Edita: [BIOMASA/control_biomasa.php](../BIOMASA/control_biomasa.php)

```php
Línea 18:
$esp_ip = "192.168.1.101";  // ← CAMBIAR POR TU IP
```

### Paso 2: Código Mínimo del ESP8266

```cpp
#include <WiFi.h>
#include <WebServer.h>

#define RELAY_PIN 12

WebServer server(80);

void handleBiores() {
    String cmd = server.arg("cmd");
    
    if (cmd == "START") {
        digitalWrite(RELAY_PIN, HIGH);
        server.send(200, "text/plain", "Sistema iniciado");
    }
    else if (cmd == "STOP") {
        digitalWrite(RELAY_PIN, LOW);
        server.send(200, "text/plain", "Sistema detenido");
    }
}

void setup() {
    pinMode(RELAY_PIN, OUTPUT);
    WiFi.begin("SSID", "PASSWORD");
    
    server.on("/biores", handleBiores);
    server.begin();
}

void loop() {
    server.handleClient();
}
```

## 📡 URLs de Referencia

### Comando START
```
GET http://192.168.1.101/biores?cmd=START
```
Respuesta esperada: `Sistema iniciado`

### Comando STOP
```
GET http://192.168.1.101/biores?cmd=STOP
```
Respuesta esperada: `Sistema detenido`

### Ver Estado del Panel
```
http://localhost:8080/PRERMI/web/admin/biores.php
```

## ✅ Verificación

### En la Consola del Navegador (F12)

Deberías ver:
```javascript
📡 Enviando comando START al ESP8266...
✅ Comando START enviado al ESP8266: {
  "status": "ok",
  "msg": "Comando START enviado exitosamente al ESP8266",
  "accion": "START",
  "timestamp": "2026-02-22 14:30:15"
}
```

### En los Logs del PHP

Busca en `php_errors.log`:
```
Control BIORES: Intento de envío de comando START a 192.168.1.101
Control BIORES: Comando START enviado exitosamente
```

## 🆘 Si No Funciona

| Síntoma | Causa | Solución |
|---------|-------|----------|
| Botón no responde | Usuario no autenticado | Inicia sesión como admin |
| LED no cambia color | JavaScript deshabilitado | Habilita JS en el navegador |
| ESP no recibe comando | IP incorrecta | Verifica `/BIOMASA/control_biomasa.php` línea 18 |
| No hay datos de sensores | ESP no envía datos | Implementa POST en el ESP8266 |
| Error 404 en console | Archivo falta/borrado | Verifica que exista `/BIOMASA/sensores_estado.php` |

---

**Estado**: ✅ Configurado y Listo  
**Botón START Envía**: `accion=START` → `GET http://ESP_IP/biores?cmd=START`  
**Última actualización**: 22/02/2026
