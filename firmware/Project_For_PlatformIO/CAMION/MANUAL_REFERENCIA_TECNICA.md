# Manual de Referencia Técnica - Control de Camión ESP8266

## 📚 Índice Rápido
1. [Conceptos Básicos](#conceptos-básicos)
2. [Pines GPIO del ESP8266](#pines-gpio-del-esp8266)
3. [Controlador L298N - Explicado](#controlador-l298n---explicado)
4. [PWM - Pulse Width Modulation](#pwm---pulse-width-modulation)
5. [Servomotores - Funcionamiento](#servomotores---funcionamiento)
6. [Conexiones Físicas](#conexiones-físicas)
7. [Conceptos de Arduino en el Código](#conceptos-de-arduino-en-el-código)
8. [Tabla de Referencia Rápida](#tabla-de-referencia-rápida)

---

## 🎯 Conceptos Básicos

### ¿Qué es una Señal Digital?
Una señal digital solo tiene 2 estados:
- **HIGH (1)** = 3.3V en ESP8266 (activo)
- **LOW (0)** = 0V en ESP8266 (inactivo)

### ¿Qué es un Microcontrolador?
Es una pequeña computadora que:
1. Lee señales de entrada (botones, sensores)
2. Procesa lógica (decisiones)
3. Envía señales de salida (motores, LEDs)

El ESP8266 es un microcontrolador económico con WiFi integrado.

### Conceptos en el Código
```cpp
pinMode(PIN, OUTPUT);      // Configurar pin como salida de datos
digitalWrite(PIN, HIGH);   // Enviar señal HIGH al pin
digitalWrite(PIN, LOW);    // Enviar señal LOW al pin
analogWrite(PIN, 128);     // Enviar PWM (0-255)
```

---

## 🔌 Pines GPIO del ESP8266

### Mapeo de Pines D1-D8

El ESP8266 tiene pines etiquetados como D0-D8 en placas tipo WeMos D1 Mini:

| Etiqueta | GPIO | Función |
|----------|------|---------|
| D0 | GPIO16 | ⚠ No usar para PWM - solo digital |
| D1 | GPIO5 | ✓ PWM, I2C SCL |
| D2 | GPIO4 | ✓ PWM, entrada auxiliar |
| D3 | GPIO0 | ✓ PWM, boot pin (no usar para reset) |
| D4 | GPIO2 | ✓ PWM, LED integrado |
| D5 | GPIO14 | ✓ PWM, SPI CLK |
| D6 | GPIO12 | ✓ PWM, SPI MISO |
| D7 | GPIO13 | ✓ PWM, SPI MOSI |
| D8 | GPIO15 | ✓ PWM, SPI CS |

### ¿Qué significa cada columna?

**Etiqueta:** Nombre que ves en la placa (ej: D1)
**GPIO:** Número interno usado en código C

**Función PWM:** Puede generar señales PWM (velocidad variable)
**Función especial:** Otros usos (I2C, SPI, etc.)

### En el Código
```cpp
#define IN1 D2     // Usar nombre legible = GPIO más legible
digitalWrite(IN1, HIGH);  // Equivalente a digitalWrite(4, HIGH)
```

---

## ⚡ Controlador L298N - Explicado

### ¿Qué es?
El L298N es un circuito integrado (chip electrónico) que:
- **Recibe** señales de bajo voltaje del ESP8266 (3.3V)
- **Controla** 4 motores DC de mayor voltaje (hasta 35V)
- **Amplifica** las señales para que los motores funcionen

### Pinout del L298N (Vista Superior)

```
        L298N Motor Driver
        
    [Pin 1]
    GND ←---┐
            ├─ Motor 1 Control
    [Pin 2] │ (IN1, IN2)
    ?       │
            ├─ Motor 2 Control
    [Pin 3] │ (IN3, IN4)
    ?       ├─ Motor 3 Control (opcional)
            ├─ Motor 4 Control (opcional)
    [Pin 1] │
    GND ←---┘

    +5V ← Alimentación (podría ser 12V)
    GND ← Tierra
    
    IN1, IN2, IN3, IN4 ← Señales del microcontrolador
```

### Conexiones L298N ↔ ESP8266

**Control de Motors:**
```
Entrada        Salida L298N
IN1 ← D2 ──→  Motor 1A / Motor 2A
IN2 ← D1 ──→  Motor 1B / Motor 2B
IN3 ← D7 ──→  Motor 3A / Motor 4A
IN4 ← D8 ──→  Motor 3B / Motor 4B
```

**Alimentación:**
```
+12V (batería) ────┐
                   ├─→ L298N (+12V)
GND (batería) ─────┤
                   └─→ ESP8266 GND (IMPORTANTE: debe compartir GND)
```

### ¿Por qué compartir GND?

Sin GND compartido:
```
ESP8266: Envía HIGH (3.3V)
L298N:   No entiende qué es HIGH sin referencia común
Resultado: ¡NO FUNCIONA!
```

Con GND compartido:
```
ESP8266: Envía HIGH (3.3V relativo a GND)
L298N:   Lee HIGH correctamente contra el mismo GND
Resultado: ✓ FUNCIONA
```

### Control de Dirección

El L298N controla dirección combinando IN1 e IN2:

```
IN1  IN2  Resultado
HIGH HIGH Motor parado (conflicto)
HIGH LOW  Gira en dirección A
LOW  HIGH Gira en dirección B
LOW  LOW  Motor parado (frenan)
```

En el código:
```cpp
// Motor girando adelante
digitalWrite(IN1, HIGH);
digitalWrite(IN2, LOW);

// Motor frenando
digitalWrite(IN1, LOW);
digitalWrite(IN2, LOW);

// Motor atrás
digitalWrite(IN1, LOW);
digitalWrite(IN2, HIGH);
```

---

## 📊 PWM - Pulse Width Modulation

### ¿Qué es PWM?

PWM es una técnica para controlar la **velocidad** enviando pulsos digitales rápides:

```
analogWrite(PIN, 0)       → 0% duty cycle = 0V promedio = Motor parado
│
analogWrite(PIN, 128)     → 50% duty cycle = 1.65V promedio = Velocidad media
│  ┌─────┐       ┌─────┐       ┌─────┐
│  │     │       │     │       │     │
└──┘     └───────┘     └───────┘     └─  Onda cuadrada 50%
   HIGH      LOW      HIGH      LOW      HIGH
   
analogWrite(PIN, 255)     → 100% duty cycle = 3.3V promedio = Máxima velocidad
│  ┌──────────┐      ┌──────────┐
│  │          │      │          │
└──┘          └──────┘          └──── Onda cuadrada 100%
   HIGH              LOW              HIGH
```

### Rango PWM en Arduino

**Rango completo:** 0-1023 (10 bits)
**Rango común:** 0-255 (8 bits, más común)

```cpp
analogWrite(PIN, 0);       // Mínimo = parado
analogWrite(PIN, 127);     // 50% = velocidad media
analogWrite(PIN, 255);     // Máximo = velocidad máxima
```

### Frecuencia PWM

La frecuencia por defecto en ESP8266 es **1000 Hz**, es decir:
- Cambia de estado cada **1 milisegundo**
- Ideal para motores (no audible)
- Perfecto para LEDs

---

## 🔧 Servomotores - Funcionamiento

### ¿Qué es un Servomotor?

Un servo es un motor especial que:
- Gira a un **ángulo específico** (0-180°)
- Se **detiene** en ese ángulo
- Es diferente a un motor normal

### Anatomía de un Servo

```
    ┌─────────────────┐
    │   Servo Motor   │
    └─────────────────┘
            ↓
    ┌─────────────────┐
    │  Circuitería    │ ← Controla automáticamente la posición
    │  Interna        │   Comparador de posición actual vs deseada
    └─────────────────┘
            │
    ┌───────┴────────┬──────────┐
    ↓                ↓          ↓
  3 Cables:    Rojo (5V), Negro (GND), Amarillo (Señal)
```

### Protocolo de Control del Servo

El servo entiende su posición por **ancho del pulso**:

```
Pulso de 1.0 ms  → Servo a 0°    (extrema izquierda)
Pulso de 1.5 ms  → Servo a 90°   (centro - posición neutral)
Pulso de 2.0 ms  → Servo a 180°  (extrema derecha)
```

### En Arduino/ESP8266

La librería `Servo.h` automáticamente:
1. Conecta el servo al pin PWM
2. Calcula el ancho de pulso necesario
3. Envía los pulsos correctos

```cpp
Servo myServo;
myServo.attach(D4);      // Conectar servo al pin D4
myServo.write(90);       // Mover a 90 grados
// Internamente envía pulso 1.5ms automáticamente

delay(500);              // Esperar a que se mueva
myServo.write(180);      // Mover a 180 grados
// Ahora envía pulso 2.0ms
```

### Diferencias: Motor vs Servo

| Característica | Motor DC | Servo |
|---|---|---|
| Giro | Continuo | Limitado a 0-180° |
| Control | Velocidad (PWM) | Ángulo |
| Precisión | Baja (solo on/off) | Alta (ángulo exacto) |
| Uso | Ruedas del camión | Levantadores |
| Pines | 2 (Dir + PWM) | 1 (Señal) |

---

## 🔗 Conexiones Físicas

### Diagrama General del Sistema

```
┌──────────────────────────────────────────────────────────┐
│                    Sistema Completo                      │
└──────────────────────────────────────────────────────────┘

ALIMENTACIÓN:
    ┌─── Batería 12V ───┐
    │                   │
    ├──→ L298N 12V      │
    │                   │
    ├──→ ESP8266 5V (usualmente)
    │                   │
    └── GND (COMÚN)     │
         │              │
         └──────────────┘

CONTROL:
    ┌─ ESP8266 ─────────────────────────────────┐
    │                                            │
    │ D2 ──→ L298N IN1 (Motor 1)                │
    │ D1 ──→ L298N IN2 (Motor 2)                │
    │ D7 ──→ L298N IN3 (Motor 3)                │
    │ D8 ──→ L298N IN4 (Motor 4)                │
    │                                            │
    │ D4 ──→ Servo 1 (Señal)                    │
    │ D3 ──→ Servo 2 (Señal)                    │
    │                                            │
    └────────────────────────────────────────────┘
            ↓
    ┌──────────────────────────────┐
    │      L298N - Motores        │
    │                              │
    │  OUT1A → Motor 1A            │
    │  OUT1B → Motor 1B            │
    │  OUT2A → Motor 2A            │
    │  OUT2B → Motor 2B            │
    │  OUT3A → Motor 3A            │
    │  OUT3B → Motor 3B            │
    │  OUT4A → Motor 4A            │
    │  OUT4B → Motor 4B            │
    └──────────────────────────────┘

SERVOS:
    Servo 1 y 2 (rojo, negro, amarillo)
```

### Tabla de Conexión de Cables

| De | A | Cable | Notas |
|---|---|---|---|
| Batería + | L298N 12V | Rojo grueso | 12V máximo |
| Batería + | Regulador 5V | Rojo | Para ESP8266 |
| Batería - | L298N GND | Negro | IMPORTANTE |
| Batería - | ESP8266 GND | Negro | COMÚN con L298N |
| ESP D2 | L298N IN1 | Verde | Motor 1 |
| ESP D1 | L298N IN2 | Amarillo | Motor 2 |
| ESP D7 | L298N IN3 | Naranja | Motor 3 |
| ESP D8 | L298N IN4 | Violeta | Motor 4 |
| ESP D4 | Servo1 Señal | Blanco | PWM sin GND (comparten) |
| ESP D3 | Servo2 Señal | Gris | PWM sin GND (comparten) |

---

## 🎓 Conceptos de Arduino en el Código

### pinMode() - Configurar Pin

```cpp
pinMode(PIN, OUTPUT);    // Pin como salida (envía datos)
pinMode(PIN, INPUT);     // Pin como entrada (recibe datos)
pinMode(PIN, INPUT_PULLUP);  // Entrada con resistencia interna
```

**¿Por qué es importante?**
Sin `pinMode()`, las funciones `digitalWrite()` pueden no funcionar correctly.

### digitalWrite() - Enviar HIGH/LOW

```cpp
digitalWrite(D2, HIGH);  // Enviar 3.3V al pin D2
digitalWrite(D2, LOW);   // Enviar 0V al pin D2
```

**Tiempo:** Instantáneo (microsegundos)
**Usable para:** Control de dirección de motores

### analogWrite() - Enviar PWM

```cpp
analogWrite(D2, 0);      // 0% PWM = 0V promedio = motor parado
analogWrite(D2, 127);    // 50% PWM = 1.65V promedio
analogWrite(D2, 255);    // 100% PWM = 3.3V promedio = máxima velocidad
```

**Tiempo:** 1000 Hz (1ms por ciclo)
**Usable para:** Control de velocidad de motores

### delay() - Esperar

```cpp
delay(1000);   // Esperar 1000 milisegundos (1 segundo)
delay(500);    // Esperar 500 milisegundos (0.5 segundos)
```

**Bloquea:** El código se detiene, nada más sucede

### Serial.print() - Debug/Logging

```cpp
Serial.println("Hola Mundo");      // Imprime y nueva línea
Serial.print("Valor: ");
Serial.println(myVariable);        // Imprime el valor
```

**Ver salida:** Monitor Serial (115200 baud)

### Condiciones IF

```cpp
if (command == "FORWARD") {
    // Ejecutar si comando es "FORWARD"
} else if (command == "BACKWARD") {
    // Ejecutar si comando es "BACKWARD"
} else {
    // Ejecutar si ninguna de las anteriores
}
```

### Loops FOR

```cpp
for (int i = 0; i < 4; i++) {
    // i vale 0, 1, 2, 3
    // Ejecutar 4 veces
}
```

### Switch Statement

```cpp
switch (command) {
    case "FORWARD":
        // Si command == "FORWARD"
        break;   // IMPORTANTE: salir del switch
    
    case "BACKWARD":
        // Si command == "BACKWARD"
        break;
    
    default:
        // Si ninguno de los anteriores
        break;
}
```

---

## 📋 Tabla de Referencia Rápida

### Movimiento del Camión

| Dirección | IN1 | IN2 | IN3 | IN4 | Explicación |
|---|---|---|---|---|---|
| Adelante | HIGH | HIGH | HIGH | HIGH | Todas ruedas avanzan |
| Atrás | LOW | LOW | LOW | LOW | Todas ruedas retroceden |
| Izquierda | LOW | HIGH | LOW | HIGH | Ruedas derechas avanzan, izquierdas retroceden |
| Derecha | HIGH | LOW | HIGH | LOW | Ruedas izquierdas avanzan, derechas retroceden |
| Parado | LOW | LOW | LOW | LOW | Todos motores detenidos |

### Velocidad PWM

| Valor | Porcentaje | Interpretación |
|---|---|---|
| 0 | 0% | Motor detenido |
| 64 | 25% | Velocidad muy lenta |
| 128 | 50% | Velocidad media |
| 192 | 75% | Velocidad rápida |
| 255 | 100% | Velocidad máxima |

### Ángulos de Servo

| Ángulo | Posición | Típico uso |
|---|---|---|
| 0° | Extrema izquierda | Contenedor insertado/bajo |
| 45° | Cuarto giro izquierda | Intermedio |
| 90° | Centro/Neutral | Posición de descanso |
| 135° | Cuarto giro derecha | Intermedio |
| 180° | Extrema derecha | Contenedor levantado/descargado |

### Funciones del Código

| Función | Parámetros | Qué hace |
|---|---|---|
| `moveMotors()` | 8 parámetros | Controla dirección + velocidad de 4 motores |
| `stopAllMotors()` | ninguno | Detiene todos los motores inmediatamente |
| `moveServo()` | servoNumber, angle | Mueve un servo a un ángulo específico |
| `processCommand()` | command string | Procesa los comandos recibidos |
| `initWiFi()` | ninguno | Conecta a WiFi |

---

## 🔍 Debugging: Cómo Encontrar Errores

### Error: Motor no se mueve

```
Chequear:
1. ¿Pin configurado con pinMode() ?
2. ¿Cable conectado al GND correcto?
3. ¿Alimentación llegando al L298N?
4. ¿Batería tiene carga?
5. ¿digitalWrite() enviando HIGH?
```

### Error: Servo no responde

```
Chequear:
1. ¿Servo conectado al pin PWM correcto?
2. ¿servo.attach() se ejecutó?
3. ¿Voltaje de alimentación es 5V?
4. ¿Servo tiene corriente suficiente?
5. ¿Ángulo está entre 0-180?
```

### Error: WiFi no se conecta

```
Chequear:
1. ¿SSID es exacto (mayúsculas, espacios)?
2. ¿Contraseña correcta?
3. ¿Router emite 2.4GHz? (ESP8266 no soporta 5GHz)
4. ¿Señal WiFi fuerte?
5. ¿Existe firewall bloqueando?
```

---

## 📚 Recursos Externos

- **Arduino Reference:** https://www.arduino.cc/reference/
- **ESP8266 Arduino Core:** https://github.com/esp8266/Arduino
- **L298N Datasheet:** https://datasheetspdf.com/pdf/L298N
- **Servo SG90 Spec:** https://www.allegromicro.com/en/products/motor-drivers/brushed-dc-motor-drivers/l298

---

**Creado:** Marzo 6, 2026  
**Última actualización:** Marzo 6, 2026  
**Autor:** Tu nombre aquí
