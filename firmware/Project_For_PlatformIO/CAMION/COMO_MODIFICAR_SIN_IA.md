# Cómo Modificar el Código SIN IA - Guía Práctica

## 📝 Introducción

Este archivo te enseña **cómo cambiar el código por tu cuenta**, sin depender de una IA, para casos comunes como:
- Cambiar la red WiFi
- Agregar nuevos comandos
- Ajustar velocidades
- Cambiar pines
- Agregar más servomotores

---

## 1️⃣ Cambiar la Red WiFi

### Paso 1: Identificar dónde están las credenciales

Abre `main.cpp` y busca esta sección (está cerca del principio):

```cpp
const char* WIFI_SSID = "Jhail-WIFI";      // Nombre de tu red WiFi (SSID)
const char* WIFI_PASS = "123.02589.";      // Contraseña de tu red WiFi
```

### Paso 2: Reemplazar valores

**Opción 1: Si tienes una red nueva**

Busca:
```cpp
const char* WIFI_SSID = "Jhail-WIFI";
```

Reemplaza `"Jhail-WIFI"` con tu nombre de red, por ejemplo:
```cpp
const char* WIFI_SSID = "Mi-Red-Hogar";
```

Luego busca:
```cpp
const char* WIFI_PASS = "123.02589.";
```

Reemplaza con tu contraseña:
```cpp
const char* WIFI_PASS = "mi_contraseña_segura";
```

### Paso 3: Guardar y compilar

1. Presiona **Ctrl+S** para guardar
2. En PlatformIO, click **Build** para compilar
3. Si no hay errores rojos, luego **Upload**

### ✓ Listo

El ESP8266 se conectará a tu nueva red.

---

## 2️⃣ Cambiar un Pin

### Caso: Quiero usar D5 en lugar de D2 para IN1

### Paso 1: Encontrar la definición del pin

Busca en main.cpp:
```cpp
#define IN1 D2  // Rueda DELANTERA IZQUIERDA
```

### Paso 2: Cambiar a nuevo pin

Reemplaza `D2` con `D5`:
```cpp
#define IN1 D5  // Rueda DELANTERA IZQUIERDA
```

### Paso 3: Buscar todas las referencias

⚠️ **IMPORTANTE:** Algunos pines se usan dos veces

Busca en el documento:
```cpp
#define PWM_IN1 D2  // También está aquí
```

Reemplaza también:
```cpp
#define PWM_IN1 D5  // Cambiar a D5 también
```

### Paso 4: Verificar conexión física

Asegúrate de que el motor esté físicamente conectado a D5 (GPIO4), no a D2.

### Paso 5: Compilar y cargar

```
Ctrl+S → Build → Upload
```

---

## 3️⃣ Agregar un Nuevo Comando

### Caso: Quiero un comando "ZIGZAG" que haga un patrón

### Paso 1: Entender la estructura actual

Busca en `processCommand()`:

```cpp
if (command == "FORWARD") {
    Serial.println("> Moviendo ADELANTE");
    moveMotors(HIGH, HIGH, HIGH, HIGH, ...);
}
```

Este es el patrón para cada comando:
1. Buscar si comando coincide con un string
2. Hacer log con `Serial.println()`
3. Llamar función `moveMotors()` con parámetros

### Paso 2: Agregar nuevo comando

Busca la línea:
```cpp
else if (command == "EMERGENCY_STOP") {
```

Justo ANTES de esa línea, agrega:

```cpp
else if (command == "ZIGZAG") {
    Serial.println("> Patrón ZIGZAG");
    // Derecha 500ms
    moveMotors(HIGH, LOW, HIGH, LOW, currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
    delay(500);
    // Adelante 300ms
    moveMotors(HIGH, HIGH, HIGH, HIGH, currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
    delay(300);
    // Izquierda 500ms
    moveMotors(LOW, HIGH, LOW, HIGH, currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
    delay(500);
    // Adelante 300ms
    moveMotors(HIGH, HIGH, HIGH, HIGH, currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
    delay(300);
    // Detener
    stopAllMotors();
}
```

### Paso 3: También agregar en control_api.php

Abre `control_api.php` y busca:

```php
switch ($command) {
    case 'FORWARD':
        // ... código ...
        break;
```

Agregar antes del `default`:

```php
case 'ZIGZAG':
    $commandData['pattern'] = 'ZIGZAG';
    if (sendToESP8266('ZIGZAG', $commandData)) {
        respondJSON(true, 'Patrón ZIGZAG ejecutándose', $commandData);
    } else {
        respondJSON(false, 'No se pudo contactar al ESP8266');
    }
    break;
```

### Paso 4: Agregar botón en la interfaz

Abre `INTERFAZ_DE_CONTROL.php` y busca:

```html
<button class="btn direction" id="btn-stop" title="Detener">
```

Después de ese botón, agrega:

```html
<button class="btn primary" id="btn-zigzag" onclick="sendCommand('ZIGZAG')" style="width: 100%; margin-top: 10px;">
    <i class="fas fa-retweet"></i> ZIGZAG
</button>
```

---

## 4️⃣ Ajustar Velocidad Máxima

### Caso: Quiero que la velocidad máxima sea más lenta (200 en lugar de 255)

### Paso 1: Encontrar MAX_SPEED

Abre `main.cpp` y busca:

```cpp
const int MAX_SPEED = 255;
```

### Paso 2: Cambiar valor

Reemplaza 255 con 200:

```cpp
const int MAX_SPEED = 200;
```

### Paso 3: Compilar

Los motores ahora nunca superarán 200/255 de velocidad.

---

## 5️⃣ Agregar un Tercer Servomotor

### Paso 1: Agregar definición de pin

Busca:
```cpp
#define ServoPin1 D4  // Servomotor 1
#define ServoPin2 D3  // Servomotor 2
```

Agrega après:
```cpp
#define ServoPin3 D5  // Servomotor 3 - CAMBIAR A UN PIN LIBRE
```

### Paso 2: Crear objeto Servo

Busca:
```cpp
Servo servo1;
Servo servo2;
```

Agrega:
```cpp
Servo servo3;
```

### Paso 3: Agregar variables de estado

Busca:
```cpp
int currentAngle_Servo1 = 90;
int currentAngle_Servo2 = 90;
```

Agrega:
```cpp
int currentAngle_Servo3 = 90;
```

### Paso 4: Inicializar en setup()

Busca:
```cpp
servo1.attach(ServoPin1);
servo2.attach(ServoPin2);
servo1.write(90);
servo2.write(90);
```

Agrega:
```cpp
servo3.attach(ServoPin3);
servo3.write(90);
```

### Paso 5: Agregar comando en processCommand()

Busca:
```cpp
else if (command == "SERVO2_MOVE") {
```

Agrega después:
```cpp
else if (command == "SERVO3_MOVE") {
    Serial.println("> Moviendo SERVO3 a " + String(currentAngle_Servo3) + "°");
    moveServo(3, currentAngle_Servo3);
}
```

### Paso 6: Actualizar función moveServo()

Busca la función `moveServo()` y agrega antes del último `else`:

```cpp
else if (servoNumber == 3) {
    angle = constrain(angle, 0, 180);  // Rango 0-180
    servo3.write(angle);
    currentAngle_Servo3 = angle;
    Serial.print("  Servo3 movido a ");
    Serial.print(angle);
    Serial.println("°");
}
```

### Paso 7: En loop(), procesar servo3

Busca:
```cpp
currentAngle_Servo1 = constrain(servo1Angle, SERVO1_MIN, SERVO1_MAX);
currentAngle_Servo2 = constrain(servo2Angle, SERVO2_MIN, SERVO2_MAX);
```

Agrega:
```cpp
// Asumir que servo3Angle viene en el JSON
int servo3Angle = doc["data"]["servo3"] | 90;
currentAngle_Servo3 = constrain(servo3Angle, 0, 180);
servo3.write(currentAngle_Servo3);
```

---

## 6️⃣ Cambiar Velocidad Predeterminada

### Paso 1: Encontrar DEFAULT_SPEED

Busca en main.cpp:
```cpp
const int DEFAULT_SPEED = 150;
```

### Paso 2: Cambiar valor

Si quieres que sea más rápido:
```cpp
const int DEFAULT_SPEED = 200;
```

Si más lento:
```cpp
const int DEFAULT_SPEED = 100;
```

---

## 7️⃣ Cambiar Timeout de Seguridad

### Caso: Quiero que el camión se detenga después de 10 segundos sin comunicación (en lugar de 5)

### Paso 1: Encontrar el timeout

Busca en loop():
```cpp
if (lastCommunicationTime > 0 && (millis() - lastCommunicationTime > 5000)) {
```

### Paso 2: Cambiar 5000 a 10000

```cpp
if (lastCommunicationTime > 0 && (millis() - lastCommunicationTime > 10000)) {
```

5000 ms = 5 segundos
10000 ms = 10 segundos

---

## 8️⃣ Cambiar Puerto del Servidor

### Caso: Quiero que el servidor corra en puerto 8080 en lugar de 80

### Paso 1: En main.cpp

Busca:
```cpp
const uint16_t LOCAL_SERVER_PORT = 80;
```

Reemplaza:
```cpp
const uint16_t LOCAL_SERVER_PORT = 8080;
```

### Paso 2: En config.php (XAMPP side)

Busca:
```php
define('ESP8266_PORT', 8080);
```

Reemplaza:
```php
define('ESP8266_PORT', 8080);  // Ya está configurado así
```

---

## 9️⃣ Agregar Punto de Giro de 45 Grados

### Caso: Quiero agregar diagonal arriba-derecha

### Paso 1: En main.cpp, agregar comando

```cpp
else if (command == "FORWARD_RIGHT_45") {
    Serial.println("> Moviendo 45° a la derecha");
    // IN2 y IN4 más lento que IN1 e IN3
    moveMotors(HIGH, HIGH, HIGH, HIGH,
               currentSpeed_IN1, 75, currentSpeed_IN3, 75);
}
```

### Paso 2: En control_api.php

```php
case 'FORWARD_RIGHT_45':
    $commandData['direction'] = 'FORWARD_RIGHT_45';
    if (sendToESP8266('FORWARD_RIGHT_45', $commandData)) {
        respondJSON(true, 'Moviendo 45° derecha', $commandData);
    } else {
        respondJSON(false, 'No se pudo contactar al ESP8266');
    }
    break;
```

---

## 🔟 Patrón General para Cualquier Cambio

### Cambiar un **número** (pin, velocidad, ángulo):

1. Buscar el número actual: **Ctrl+F**
2. Reemplazar con nuevo número: **Ctrl+H**
3. Compilar y probar

### Cambiar un **comando**:

1. Copiar patrón `if (command == "X") { ... }`
2. Cambiar `"X"` a nuevo nombre
3. Cambiar código dentro de llaves `{}`
4. Agregar en los 3 archivos (main.cpp, control_api.php, INTERFAZ_DE_CONTROL.php)

### Agregar un **servo nuevo**:

1. Agregar `#define ServoPin3 D5;`
2. Agregar `Servo servo3;`
3. En setup(): `servo3.attach(ServoPin3);`
4. En loop(): `servo3.write(angle);`
5. En moveServo(): agregar `else if (servoNumber == 3) { ... }`

---

## 🔍 Errores Comunes

### Error: "unknown error"
Probablemente hay un typo en el código.
- Busca paréntesis sin cerrar: `(` y `)`
- Busca llaves sin cerrar: `{` y `}`
- Busca point-and-comma: `;` al final de líneas

### Error: "Serial undefined"
Olvidaste `Serial.begin(115200)` en setup().

### Error: Variable no existe
Probablemente olvidaste declarar `int motorSpeed = 0;`

### Compilador: "break statement not within switch"
Pusiste un `break;` fuera de un switch/case.

---

## 📚 Recursos para Aprender Más

### Sintaxis de C++:
- https://cplusplus.com/doc/tutorial/
- https://www.tutorialspoint.com/cplusplus/

### Arduino específico:
- https://www.arduino.cc/en/Reference/
- https://github.com/esp8266/Arduino/wiki

### Debugging:
- Abre "Monitor Serial" (Ctrl+Alt+M) para ver qué está pasando
- Agrega `Serial.println()` donde necesites debugging

---

## ✅ Checklist Antes de Compilar

Antes de presionar "Build":

- [ ] Revisé el código para llaves `{}` sin cerrar
- [ ] Todas las líneas importantestienen `;` al final
- [ ] No hay caracteres especiales españoles en strings
- [ ] Los pines que uso existen (D0-D8)
- [ ] Los números están en rangos válidos (0-255 para PWM, 0-180 para servo)
- [ ] Si edité JSON, lo revisé en https://jsonlint.com/

---

## 🎯 Práctica Recomendada

Intenta estas modificaciones en orden de dificultad:

1. **Fácil:** Cambiar RED_SSID y RED_PASS
2. **Fácil:** Cambiar DEFAULT_SPEED de 150 a 200
3. **Medio:** Cambiar un pin (ej: IN1 de D2 a D5)
4. **Medio:** Agregar un comando nuevo (BLINK_LIGHTS)
5. **Difícil:** Agregar un tercer servo
6. **Difícil:** Cambiar velocidades de ruedas solo en curvas

Cada cambio que hagas correctamente te enseña cómo hacerlo de nuevo sin AI.

---

**Última actualización:** Marzo 6, 2026  
**Dificultad progresiva:** Iniciante → Intermedio → Avanzado
