# Guía de Control Remoto del Camión - SISTEMA COMPLETO

## 📋 Índice
1. [Estructura del Sistema](#estructura-del-sistema)
2. [Configuración del Servidor Web (XAMPP/PHP)](#configuración-del-servidor-web)
3. [Configuración del ESP8266](#configuración-del-esp8266)
4. [Cómo Usar la Interfaz](#cómo-usar-la-interfaz)
5. [Flujo de Comunicación](#flujo-de-comunicación)
6. [Solución de Problemas](#solución-de-problemas)

---

## 🔧 Estructura del Sistema

```
Cliente (Navegador Web)
        ↓ (HTTP POST)
XAMPP/PHP (control_api.php)
        ↓ (HTTP POST + JSON)
ESP8266MOD OLED (Servidor Web)
        ↓
Controlador L298N
        ├── Motor 1 (IN1) - Rueda Delantera Izquierda
        ├── Motor 2 (IN2) - Rueda Delantera Derecha
        ├── Motor 3 (IN3) - Rueda Trasera Izquierda
        └── Motor 4 (IN4) - Rueda Trasera Derecha

Servomotores
        ├── Servo Pin 1 (D4) - Levantador de Contenedor
        └── Servo Pin 2 (D3) - Levantador de Contenedor
```

---

## 🌐 Configuración del Servidor Web (XAMPP/PHP)

### Archivos Creados:

1. **INTERFAZ_DE_CONTROL.php**
   - Interfaz web moderna y responsive
   - Controles de movimiento, velocidad y servomotores
   - Monitoreo en tiempo real
   - Ubicación: `d:\xampp\htdocs\PRERMI\CONTROL_DE_CAMIONES_A_DISTANCIA\`

2. **control_api.php**
   - API que recibe comandos de la interfaz
   - Envía solicitudes HTTP al ESP8266
   - Registra todos los eventos en un log
   - Ubicación: Misma carpeta

3. **config.php**
   - Configuración centralizada
   - Parámetros de conexión y pines
   - Ubicación: Misma carpeta

### Pasos para Activar:

1. **Abre XAMPP Control Panel**
   - Inicia los servicios: Apache y MySQL

2. **Acceder a la Interfaz**
   - Abre tu navegador web
   - Ve a: `http://localhost:8080/PRERMI/CONTROL_DE_CAMIONES_A_DISTANCIA/INTERFAZ_DE_CONTROL.php`
   - O si está en otra máquina: `http://[TU_IP_XAMPP]:80/PRERMI/CONTROL_DE_CAMIONES_A_DISTANCIA/INTERFAZ_DE_CONTROL.php`

3. **Modifica config.php según tu red**
   - IP del ESP8266: `10.0.0.1` (MODIFICAR si es diferente)
   - Puerto: `8080`
   - SSID WiFi: `Jhail-WIFI`
   - Contraseña: `123.02589.`

---

## 🤖 Configuración del ESP8266

### Hardware Necesario:

1. **ESP8266MOD OLED**
   - Placa microcontroladora WiFi

2. **Motor L298N**
   - Controlador de motores DC
   - 4 salidas para las ruedas

3. **4 Motores DC** (pequeños)
   - Para las 4 ruedas del camión

4. **2 Servomotores SG90 o similar**
   - Para levantamiento de contenedores

5. **Cables y Componentes**
   - Cables de conexión
   - Alimentación (batería o fuente)

### Conexiones de Pines:

#### Motor L298N ↔ ESP8266:
```
IN1 (Motor 1) ← D2
IN2 (Motor 2) ← D1
IN3 (Motor 3) ← D7
IN4 (Motor 4) ← D8
GND ← GND
```

#### Servomotores ↔ ESP8266:
```
Servo 1 Señal ← D4
Servo 2 Señal ← D3
Servo GND ← GND
Servo VCC ← 5V
```

### Código del ESP8266 (main.cpp):

El código principal ya está en el archivo `main.cpp` con comentarios detallados sobre cada línea. Ver sección siguiente.

**Puntos Clave:**
1. Conectar a WiFi con las credenciales configuradas
2. Crear un servidor web en el puerto 8080
3. Escuchar solicitudes POST en `/control`
4. Procesar comandos JSON recibidos
5. Controlar los pines PWM para motores
6. Mover servomotores a los ángulos especificados

---

## 🎮 Cómo Usar la Interfaz

### Controles Principales:

1. **Botones Direccionales (Arriba/Abajo/Izquierda/Derecha)**
   - Mueve el camión en diferentes direcciones
   - Mantén presionado para movimiento continuo
   - Soporta toque táctil para móviles

2. **Control de Velocidad Individual**
   - 4 sliders: uno para cada rueda (IN1, IN2, IN3, IN4)
   - Rango: 0-255 (0 = detenido, 255 = máxima velocidad)
   - Útil para ajustes finos de movimiento

3. **Control de Servomotores**
   - Botones "Levantador" y "Bajar" para movimiento rápido
   - Sliders manuales para ángulos precisos (0-180°)
   - Visualización en tiempo real del ángulo actual

4. **Botón de Emergencia**
   - Detiene TODOS los motores de inmediato
   - Úsalo si el camión se descontrola

5. **Monitor de Sistema**
   - Muestra estado de conexión
   - Última actualización
   - Log de eventos

---

## 🔄 Flujo de Comunicación

### Ejemplo: Enviar Comando "Adelante"

```
1. Usuario presiona botón "ARRIBA" en interfaz
   ↓
2. JavaScript envía POST a control_api.php:
   {
     "command": "FORWARD",
     "in1": 150,
     "in2": 150,
     "in3": 150,
     "in4": 150,
     "servo1": 90,
     "servo2": 90
   }
   ↓
3. control_api.php recibe y loguea el evento
   ↓
4. control_api.php envía HTTP POST a ESP8266 (10.0.0.1:8080/control):
   {
     "command": "FORWARD",
     "direction": "FORWARD",
     "data": {
       "in1": 150,
       "in2": 150,
       "in3": 150,
       "in4": 150,
       "servo1": 90,
       "servo2": 90
     },
     "timestamp": "2026-03-06 15:30:45"
   }
   ↓
5. ESP8266 recibe el comando en su endpoint /control
   ↓
6. ESP8266 configura los pines:
   - digitalWrite(IN1, HIGH)
   - digitalWrite(IN2, HIGH)
   - digitalWrite(IN3, HIGH)
   - digitalWrite(IN4, HIGH)
   - analogWrite(pwm_pins, velocidad) para PWM
   ↓
7. Motores giran las 4 ruedas haciendo avanzar el camión
   ↓
8. ESP8266 responde:
   {
     "success": true,
     "message": "FORWARD motor activo"
   }
   ↓
9. Interfaz web muestra:
   - Estado: "Conectado"
   - Log: "Comando: FORWARD"
```

---

## 🛠️ Solución de Problemas

### Problema: "La interfaz carga pero dice Desconectado"

**Causa:** El ESP8266 no está en la red WiFi o no está accesible

**Solución:**
1. Verifica que el ESP8266 esté programado correctamente
2. Confirma la IP en config.php (Cambiar 10.0.0.1 si es diferente)
3. Asegúrate que ESP8266 y XAMPP están en la misma red
4. Comprueba con: `ping 10.0.0.1` (en CMD/Terminal)

### Problema: "Error al decodificar JSON" en los logs

**Causa:** El formato JSON enviado es incorrecto

**Solución:**
1. Abre la consola del navegador (F12)
2. Revisa los errores de JavaScript
3. Verifica que control_api.php esté en la carpeta correcta

### Problema: "No se pudo contactar al ESP8266"

**Causa:** ESP8266 no está respondiendo a las solicitudes HTTP

**Solución:**
1. Verifica que el servidor web del ESP8266 esté corriendo
2. Revisa que los pines estén configurados correctamente
3. Comprueba que la alimentación sea suficiente (especialmente servos)
4. Aumenta el TIMEOUT en control_api.php si la red es lenta

### Problema: Los motores no se mueven

**Causa:** Pines conectados incorrectamente o código ESP8266 con errores

**Solución:**
1. Revisa todas las conexiones físicas (especialmente GND)
2. Verifica que los pines D1-D8 no estén en uso para otra función
3. Recarga el firmware del ESP8266
4. Comprueba la alimentación de los motores

### Problema: Los servos no responden

**Causa:** Librería Servo.h no compilada o pines incorrectos

**Solución:**
1. Asegúrate de incluir `#include <Servo.h>` en main.cpp
2. Verifica que D4 y D3 sean pines PWM capaces
3. Confirma que los servos reciban los 5V necesarios
4. Prueba manualmente moviendo los servos con ángulos fijos (90°, 0°, 180°)

---

## 📝 Resumen de Comandos

| Comando | Descripción |
|---------|-------------|
| FORWARD | Mueve el camión hacia adelante |
| BACKWARD | Mueve el camión hacia atrás |
| LEFT | Gira el camión a la izquierda |
| RIGHT | Gira el camión a la derecha |
| STOP | Detiene todos los motores |
| SERVO1_MOVE | Mueve el servomotor 1 al ángulo especificado |
| SERVO2_MOVE | Mueve el servomotor 2 al ángulo especificado |
| EMERGENCY_STOP | Parada de emergencia (detiene todo) |

---

## 🔐 Seguridad

- La interfaz no tiene autenticación (es interna, como mencionaste)
- Se recomienda usar solo en redes locales privadas
- Los logs se guardan en `control_log.txt` para auditoría
- El timeout de conexión es de 5 segundos (ajustable en config.php)

---

## 📞 Soporte

Para problemas adicionales:
1. Revisa el archivo `control_log.txt` en la carpeta
2. Comprueba la consola serial del ESP8266 (a 115200 baud)
3. Utiliza el monitor de red (Wireshark) para ver solicitudes HTTP
4. Prueba con herramientas como Postman para enviar comandos manuales

---

**Última actualización:** March 6, 2026
