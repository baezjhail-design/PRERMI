# 🎛️ Control Total del Sistema BIORES - Guía de Configuración

## Descripción General

Se ha implementado un apartado de **Control Total del Sistema** en el panel BIORES (`web/admin/biores.php`) que permite controlar el ESP8266 desde la interfaz web. El sistema cuenta con:

- ✅ **Botón Iniciar Generación**: Enciende el sistema bioprotector
- ✅ **Botón Detener Sistema**: Apaga el sistema de forma segura
- ✅ **Indicador LED Visual**: Muestra el estado actual del sistema en tiempo real
- ✅ **Indicador de Estado**: Muestra "En Operación" o "Detenido"
- ✅ **Mensajes de confirmación**: Registra las acciones realizadas con timestamp

## Archivos Modificados/Creados

### 1. **web/admin/biores.php** (MODIFICADO)
- Se agregó la sección "Control Total del Sistema" 
- Incluye indicador LED animado
- Botones START/STOP con estilos mejorados
- JavaScript para comunicación con la API
- Interfaz responsive para móviles y escritorio

### 2. **BIOMASA/control_biomasa.php** (NUEVO)
- Endpoint que recibe comandos START y STOP
- Comunica con el ESP8266 vía HTTP GET
- Maneja timeouts y errores de conexión
- Registra timestamp de cada acción
- Validación de sesión para seguridad

## Configuración Necesaria

### 1. Configurar IP del ESP8266

Edita el archivo **`BIOMASA/control_biomasa.php`** en la línea 18:

```php
$esp_ip = "192.168.1.101";  // ← CAMBIAR POR LA IP REAL DEL ESP8266
```

Obtén la IP del ESP8266:
- Accede al panel web del ESP8266 en tu navegador
- Revisa los logs del ESP8266 al bootear
- Usa una herramienta como Angry IP Scanner para encontrarla

### 2. Configurar Endpoint en el ESP8266

El ESP8266 debe tener un endpoint que escuche comandos en esta URL:

```
http://192.168.1.101/biores?cmd=START
http://192.168.1.101/biores?cmd=STOP
```

**Ejemplo de código Arduino/MicroPython para el ESP8266:**

```cpp
#include <WiFi.h>
#include <WebServer.h>

#define RELAY_PIN 12  // Pin del relé

WebServer server(80);

void handleBiores() {
    if (server.hasArg("cmd")) {
        String comando = server.arg("cmd");
        
        if (comando == "START") {
            digitalWrite(RELAY_PIN, HIGH);
            server.send(200, "text/plain", "Sistema iniciado");
        } 
        else if (comando == "STOP") {
            digitalWrite(RELAY_PIN, LOW);
            server.send(200, "text/plain", "Sistema detenido");
        }
        else {
            server.send(400, "text/plain", "Comando inválido");
        }
    }
}

void setup() {
    pinMode(RELAY_PIN, OUTPUT);
    pinMode(RELAY_PIN, LOW);
    
    // Conectar WiFi
    WiFi.begin("SSID", "PASSWORD");
    
    // Configurar ruta
    server.on("/biores", handleBiores);
    
    server.begin();
}

void loop() {
    server.handleClient();
}
```

## Características Funcionales

### Panel de Control
```
┌─────────────────────────────────────┐
│  🎛️ Control Total del Sistema      │
├─────────────────────────────────────┤
│                                      │
│  Estado del Sistema    Iniciar       │
│  ●● ◯◯◯◯◯◯◯           Generación   │
│  En Operación            STOP       │
│                                      │
│  ✅ Sistema iniciado a las 10:30:45 │
└─────────────────────────────────────┘
```

### Estados del Indicador LED

- **LED ROJO (Apagado)**: Sistema detenido - Botón START habilitado
- **LED VERDE (Pulsante)**: Sistema en operación - Botón STOP habilitado

### Mensajes de Estado

- ✅ **Verde (Éxito)**: Comando ejecutado exitosamente
- ⚠️ **Amarillo (Advertencia)**: Comando registrado pero ESP8266 no responde
- ❌ **Rojo (Error)**: Error de conexión o comando inválido

## Flujo de Operación

1. **Admin accede a web/admin/biores.php**
2. **Panel carga con indicador LED en ROJO (Detenido)**
3. **Admin hace clic en "Iniciar Generación"**
4. **JavaScript envía POST a BIOMASA/control_biomasa.php**
5. **API envía GET a http://ESP_IP/biores?cmd=START**
6. **ESP8266 enciende relé y responde**
7. **LED cambia a VERDE** indicando operación activa
8. **Timestamp se registra en mensaje de confirmación**

## Seguridad

✅ Las acciones requieren sesión autenticada (`$_SESSION['user_id']`)
✅ Solo se permiten comandos válidos (START, STOP)
✅ Timeout de conexión configurado a 5 segundos para evitar bloqueos
✅ Manejo de errores sin exponer detalles del sistema

## Troubleshooting

### El LED no cambia de color
- Verifica que JavaScript esté habilitado en el navegador
- Revisa la consola del navegador (F12) para errores

### Los botones no responden
- Verifica la IP del ESP8266 en `control_biores.php`
- Asegúrate de que el ESP8266 esté conectado a la misma red WiFi
- Prueba la conectividad: `ping 192.168.1.101` (ajustar a tu IP)

### El ESP8266 no recibe comandos
- Verifica que el endpoint `/biores` esté configurado en el ESP8266
- Prueba manualmente en el navegador: `http://192.168.1.101/biores?cmd=START`
- Revisa los logs del ESP8266

### Error "No autorizado"
- Inicia sesión como administrador
- Verifica que `session_start()` esté activo
- Limpia las cookies del navegador si hay problemas de sesión

## API Reference

### POST `/PRERMI/BIOMASA/control_biomasa.php`

**Parámetros:**
- `accion` (string): `START` o `STOP`

**Respuestas:**

**Éxito:**
```json
{
    "status": "ok",
    "msg": "Comando enviado exitosamente",
    "accion": "START",
    "respuesta_esp": "Sistema iniciado",
    "timestamp": "2026-02-22 10:30:45"
}
```

**Advertencia (ESP no responde):**
```json
{
    "status": "warning",
    "msg": "Comando registrado (ESP8266 no disponible)",
    "accion": "START",
    "timestamp": "2026-02-22 10:30:45"
}
```

**Error:**
```json
{
    "status": "error",
    "msg": "No autorizado"
}
```

## Próximas Mejoras Opcionales

- [ ] Historial de comandos enviados
- [ ] Confirmación de estado antes de ejecutar
- [ ] Control de múltiples dispositivos ESP8266
- [ ] Estadísticas de tiempo operativo
- [ ] Alertas automáticas por inactividad prolongada
- [ ] AutoON/AutoOFF programados

---

**Estado**: ✅ Implementado y listo para usar
**Última actualización**: 22/02/2026
