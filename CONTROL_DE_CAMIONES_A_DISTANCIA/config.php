<?php
/**
 * ============================================================================
 * CONFIG.php
 * ============================================================================
 * Archivo de configuración para el sistema de control del camión
 * Contiene la información de conexión y parámetros del sistema
 */

// ============================================================================
// CONFIGURACIÓN DE CONEXIÓN AL ESP8266
// ============================================================================

// Dirección IP del ESP8266 en tu red WiFi
define('ESP8266_IP', '10.0.0.1');

// Puerto donde corre el servidor web en el ESP8266
define('ESP8266_PORT', 8080);

// Nombre de la red WiFi del ESP8266
define('CAMION_SSID', 'Jhail-WIFI');

// Contraseña de la red WiFi
define('CAMION_PASSWORD', '123.02589.');

// ============================================================================
// CONFIGURACIÓN DE PINES DEL ESP8266MOD OLED
// ============================================================================

// Pines de control de movimiento (Motor L298N)
define('PIN_IN1', 'D2');  // Rueda delantera izquierda
define('PIN_IN2', 'D1');  // Rueda delantera derecha
define('PIN_IN3', 'D7');  // Rueda trasera izquierda
define('PIN_IN4', 'D8');  // Rueda trasera derecha

// Pines de los servomotores
define('PIN_SERVO1', 'D4');  // Servomotor 1 (Levantador de contenedor)
define('PIN_SERVO2', 'D3');  // Servomotor 2 (Levantador de contenedor)

// ============================================================================
// PARÁMETROS DE MOVIMIENTO
// ============================================================================

// Velocidad máxima (0-255, donde 255 es máxima velocidad)
define('MAX_SPEED', 255);

// Velocidad por defecto
define('DEFAULT_SPEED', 150);

// Tiempo mínimo entre comandos (en milisegundos)
define('MIN_COMMAND_INTERVAL', 50);

// ============================================================================
// PARÁMETROS DE SERVOMOTORES
// ============================================================================

// Ángulos mínimo y máximo para Servo1
define('SERVO1_MIN_ANGLE', 0);
define('SERVO1_MAX_ANGLE', 180);
define('SERVO1_DEFAULT_ANGLE', 90);

// Ángulos mínimo y máximo para Servo2
define('SERVO2_MIN_ANGLE', 0);
define('SERVO2_MAX_ANGLE', 180);
define('SERVO2_DEFAULT_ANGLE', 90);

// ============================================================================
// DESCRIPCIÓN DEL SISTEMA
// ============================================================================
/*
 * CONFIGURACIÓN FÍSICA DEL CAMIÓN:
 * 
 * 1. MOTORES DE RUEDAS (L298N):
 *    - IN1 (D2): Rueda Delantera Izquierda
 *    - IN2 (D1): Rueda Delantera Derecha
 *    - IN3 (D7): Rueda Trasera Izquierda
 *    - IN4 (D8): Rueda Trasera Derecha
 * 
 * 2. SERVOMOTORES:
 *    - Servo Pin 1 (D4): Levantador de contenedor (rango: 0-180°)
 *    - Servo Pin 2 (D3): Levantador de contenedor (rango: 0-180°)
 * 
 * 3. MOVIMIENTO:
 *    - Adelante: IN1, IN2, IN3, IN4 = 1 (HIGH)
 *    - Atrás: IN1, IN2, IN3, IN4 = 0 (LOW)
 *    - Izquierda: IN1, IN3 = 0 (LOW), IN2, IN4 = 1 (HIGH)
 *    - Derecha: IN1, IN3 = 1 (HIGH), IN2, IN4 = 0 (LOW)
 * 
 * 4. VELOCIDAD:
 *    - Se controla mediante PWM (0-255)
 *    - 0 = Detenido
 *    - 255 = Máxima velocidad
 * 
 * 5. SERVOMOTORES:
 *    - Rango: 0-180 grados
 *    - 0° = Posición baja (contenedor dentro del camión)
 *    - 90° = Posición neutral/intermedia
 *    - 180° = Posición elevada (contenedor levantado para descarga)
 */

// ============================================================================
// FUNCIÓN AUXILIAR PARA OBTENER ESTADO DEL SISTEMA
// ============================================================================

function getSystemStatus() {
    return array(
        'esp8266_ip' => ESP8266_IP,
        'esp8266_port' => ESP8266_PORT,
        'wifi_ssid' => CAMION_SSID,
        'pins' => array(
            'motor_in1' => PIN_IN1,
            'motor_in2' => PIN_IN2,
            'motor_in3' => PIN_IN3,
            'motor_in4' => PIN_IN4,
            'servo1' => PIN_SERVO1,
            'servo2' => PIN_SERVO2
        ),
        'parameters' => array(
            'max_speed' => MAX_SPEED,
            'default_speed' => DEFAULT_SPEED,
            'servo1_range' => SERVO1_MIN_ANGLE . '-' . SERVO1_MAX_ANGLE,
            'servo2_range' => SERVO2_MIN_ANGLE . '-' . SERVO2_MAX_ANGLE
        )
    );
}

?>
