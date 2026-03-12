# QUICK START - Cómo Activar el Sistema en 10 Pasos

## ⚡ Resumen Ejecutivo

Este archivo te proporciona los **10 pasos esenciales** para tener tu camión funcionando HOY.

**Tiempo total:** ~30 minutos

---

## 🎯 LOS 10 PASOS

### PASO 1: Verificar Hardware (2 min)

**Checklist:**
- [ ] ESP8266MOD OLED conectado a PC por USB
- [ ] Aparece en Administrador de Dispositivos como CH340 o CH341
- [ ] Motores y servos conectados físicamente

```
No aparece en Administrador de Dispositivos?
→ Instala driver: https://sparks.gogo.co.nz/ch340.html
```

---

### PASO 2: Modificar main.cpp con tu WiFi (3 min)

**Abre:** `c:\Users\Jhail Baez\OneDrive\Documentos\Project_For_PlatformIO\CAMION\src\main.cpp`

**Busca (presiona Ctrl+F):**
```cpp
const char* WIFI_SSID = "Jhail-WIFI";
const char* WIFI_PASS = "123.02589.";
```

**Reemplaza con tu WiFi (ejemplo):**
```cpp
const char* WIFI_SSID = "Mi-Red";
const char* WIFI_PASS = "mi_contraseña_123";
```

**Guarda:** Ctrl+S

✓ **Listo**

---

### PASO 3: Compilar el Código ESP8266 (5 min)

**En VS Code:**

1. Asegúrate de estar en la carpeta CAMION
2. Click en el icono **PlatformIO** (hormiguita) en la barra izquierda
3. Expande "CAMION"
4. Click en **Build**

**Esperes a ver:**
```
========== [SUCCESS] Took X seconds ==========
```

❌ **Si falla:**
- Revisa que Arduino.h y librerías estén instaladas
- Lee el error rojo abajo de la pantalla
- Busca el error en Google

✓ **Listo**

---

### PASO 4: Cargar el Código en el ESP8266 (3 min)

**En PlatformIO:**

1. En el mismo menú, click en **Upload**
2. Verás progreso:
   ```
   Looking for upload port...
   Serial port COM4
   Connecting........_____......
   Hard resetting via RTS pin...
   ```
3. Espera a que termine (~1-2 minutos)

❌ **Si falla:**
- Asegúrate que el ESP8266 esté conectado a USB
- Intenta presionar el botón RESET en el ESP8266

**Cuando termina correctamente verás:**
```
=== [SUCCESS] Took X seconds ===
```

✓ **Listo**

---

### PASO 5: Verificar que ESP8266 se conectó a WiFi (2 min)

**En PlatformIO:**

1. Click en **Monitor** (muestra Monitor Serial)
2. Espera a este mensaje:

```
##############################################
CAMIÓN INTELIGENTE - Sistema de Control
##############################################
...
✓ WiFi conectado!
IP: 10.0.0.123
Servidor HTTP iniciado en: http://10.0.0.123:80/control
✓ Sistema lista! Esperando comandos...
```

⚠️ **Si dice "Error conectando a WiFi!":**
- Verifica SSID y PASS en main.cpp
- Asegúrate que el router emite 2.4GHz (NO 5GHz)
- Intenta de nuevo el PASO 4

✓ **NOTA LA IP** (ej: 10.0.0.123)

✓ **Listo**

---

### PASO 6: Modificar config.php con IP del ESP8266 (2 min)

**Abre:** `d:\xampp\htdocs\PRERMI\CONTROL_DE_CAMIONES_A_DISTANCIA\config.php`

**Busca:**
```php
define('ESP8266_IP', '10.0.0.1');
```

**Reemplaza con la IP que viste en PASO 5** (ejemplo):
```php
define('ESP8266_IP', '10.0.0.123');
```

**Guarda:** Ctrl+S

✓ **Listo**

---

### PASO 7: Iniciar XAMPP (1 min)

1. Abre **XAMPP Control Panel**
2. Click en **Start** para Apache
3. Espera a que el botón cambie a **Stop**

```
Apache: Running ✓
MySQL: Running ✓ (opcional)
```

✓ **Listo**

---

### PASO 8: Probar Conexión (2 min)

**En PowerShell:**
```powershell
ping 10.0.0.123
```

(Reemplaza con la IP de tu ESP8266)

**Deberías ver:**
```
Respuesta desde 10.0.0.123: bytes=32 tiempo=45ms TTL=64
```

❌ **Si falla "tiempo de espera agotado":**
- Verifica que ESP8266 y PC estén en la misma red WiFi
- Comprueba que firewall no bloquea
- Reinicia el ESP8266

✓ **Listo**

---

### PASO 9: Abrir Interfaz Web (1 min)

**Abre tu navegador web** (Chrome, Edge, Firefox, etc.)

**Ve a:**
```
http://localhost/PRERMI/CONTROL_DE_CAMIONES_A_DISTANCIA/INTERFAZ_DE_CONTROL.php
```

**Deberías ver:**
- Título "Control Remoto del Camión"
- 2 paneles: "Movimiento del Camión" y "Control de Servomotores"
- Botones de dirección, controles de velocidad
- Estado: "Desconectado" (por ahora)

✓ **Listo**

---

### PASO 10: ¡Controla tu Camión! (2 min)

**En la interfaz:**

1. Presiona **ADELANTE** (botón arriba)
2. En Monitor Serial deberías ver:
   ```
   === Solicitud HTTP Recibida ===
   Headers: POST /control HTTP/1.1
   Comando: FORWARD
   > Moviendo ADELANTE
   ```

3. **Los motores deberían girar!** (o se escucharían zumbidos)

4. Experimenta con:
   - Botones de dirección (arriba, abajo, izquierda, derecha)
   - Sliders de velocidad
   - Botones de servomotores (levantador/bajar)

✓ **¡SISTEMA FUNCIONAL!**

---

## 🎉 ¿Si Todo Funciona?

Felicidades! Ya tienes:
✓ Interfaz web en XAMPP  
✓ Comunicación ESP8266 → Servidor PHP  
✓ Control de 4 motores  
✓ Control de 2 servomotores  
✓ Seguridad: botón de emergencia  

---

## ❌ Si Algo No Funciona

### "No se conecta a WiFi"
```
→ PASO 5: Revisa Monitor Serial
→ Verifica SSID y contraseña exactos
→ Prueba sin caracteres especiales
```

### "La interfaz dice Desconectado"
```
→ PASO 6: Verifica IP en config.php
→ PASO 8: Prueba ping al ESP8266
→ Revisa que XAMPP esté corriendo
```

### "Interfaz no carga"
```
→ PASO 7: Verifica que Apache esté "Running"
→ Intenta: http://127.0.0.1 (localhost)
→ Si no funciona, reinicia XAMPP
```

### "Los motores no se mueven"
```
→ Revisa conexión física a L298N
→ Verifica voltaje de batería
→ Asegúrate de compartir GND
→ Monitor Serial debe mostrar "Adelante" sin errores
```

### "Servos no responden"
```
→ Verifica alimentación 5V
→ Revisa que pines sean D3 y D4
→ Intenta mover servo a 0°, 90°, 180° desde interfaz
```

---

## 📁 Resumen de Archivos Creados

| Archivo | Ubicación | Propósito |
|---------|-----------|----------|
| INTERFAZ_DE_CONTROL.php | PRERMI/CONTROL... | Página web bonita |
| control_api.php | PRERMI/CONTROL... | Recibe comandos de web, envía a ESP8266 |
| config.php | PRERMI/CONTROL... | Configuración centralizada |
| main.cpp | CAMION/src/ | Código ESP8266 (COMPLETAMENTE comentado) |
| README.md | PRERMI/CONTROL... | Guía completa del sistema |
| GUIA_COMPILACION_CARGA.md | CAMION/ | Cómo compilar y cargar código |
| MANUAL_REFERENCIA_TECNICA.md | CAMION/ | Conceptos técnicos explicados |
| COMO_MODIFICAR_SIN_IA.md | CAMION/ | Cómo cambiar cosas tú mismo |

---

## 🔧 Próximos Pasos (Después de que Funcione)

### Nivel 1: Customización Básica
- [ ] Cambiar colores de interfaz
- [ ] Agregar más botones
- [ ] Cambiar velocidades por defecto

### Nivel 2: Funcionalidad Extendida
- [ ] Agregar nuevo comando (ej: BLINK)
- [ ] Agregar tercer servo
- [ ] Grabar movimientos en secuencias

### Nivel 3: Automatización
- [ ] Sensor de obstáculos
- [ ] Autónoma con línea negra
- [ ] Control automático de hora/fecha

---

## 📞 Debug Rápido

Si algo no funciona y no sabes qué es:

1. **Abre Monitor Serial** (PASO 5)
2. **Copia el error exacto**
3. **Busca en Google:** [ERROR DE AQUÍ]
4. **Si está en Arduino/ESP8266:** https://github.com/esp8266/Arduino/issues

---

## ✅ Validación Final

Antes de dar por completado:

- [ ] XAMPP está corriendo
- [ ] ESP8266 muestra "WiFi conectado" en Monitor Serial
- [ ] Página web carga sin errores
- [ ] Botón ADELANTE mueve motores
- [ ] Servos responden a controles
- [ ] Botón EMERGENCIA detiene todo

Si todo está ✓, **¡LISTO!**

---

## 📖 Documentación Adicional

Para entender mejor cada parte:

- **MANUAL_REFERENCIA_TECNICA.md** - Aprende PWM, GPIO, servos, etc.
- **COMO_MODIFICAR_SIN_IA.md** - Cómo cambiar código sin AI
- **README.md** - Documentación completa del proyecto
- **main.cpp** - Código 100% comentado línea por línea

---

**¡Buena suerte! 🚀**

Si necesitas ayuda, revisa los comentarios en main.cpp y los archivos de guía.

---

**Creado:** Marzo 6, 2026  
**Actualizado:** Marzo 6, 2026  
**Estado:** LISTO PARA USAR ✓
