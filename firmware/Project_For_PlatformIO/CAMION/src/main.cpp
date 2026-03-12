// ##############################################################################
// CÓDIGO CONTROL REMOTO DE CAMIÓN DE 4 RUEDAS CON SERVOMOTORES
// ##############################################################################
// Proyecto: Control de camión de recolección de residuos vía WiFi
// Microcontrolador: ESP8266MOD OLED
// Hardware: Motor L298N, 4 motores DC, 2 servomotores SG90
// Autor: Jefe & El Más Singón
// Descripción: Este código permite controlar un camión de 4 ruedas y
//              2 servomotores desde un servidor XAMPP vía WiFi y HTTP
// ##############################################################################

// ============================================================================
// 1. LIBRERÍAS NECESARIAS
// ============================================================================
// Arduino.h: Proporciona funciones base de Arduino (digitalWrite, pinMode, etc)
#include <Arduino.h>

// ArduinoJson.h: Librería para parsear JSON (recibir comandos en formato JSON)
// Instalar: Busca "ArduinoJson by Benoit Blanchon" en el gestor de librerías
#include <ArduinoJson.h>

// ESP8266WiFi.h: Librería para conectar el ESP8266 a WiFi
// Instalar: Ya viene con la librería de ESP8266 en Arduino IDE
#include <ESP8266WiFi.h>

// WiFiClient.h: Cliente para hacer solicitudes HTTP
#include <WiFiClient.h>

// HTTPClient.h: Librería para hacer solicitudes HTTP más fácilmente
#include <ESP8266HTTPClient.h>

// Servo.h: Librería para controlar servomotores
// Instalar: Busca "Servo" en el gestor de librerías de Arduino IDE
#include <Servo.h>

// SSD1306Wire.h: Librería para pantalla OLED SSD1306 via I2C
// Instalar: Busca "ESP8266 and ESP32 OLED driver for SSD1306 displays" por ThingPulse
#include <Wire.h>
#include "SSD1306Wire.h"

// ============================================================================
// 2. DEFINICIÓN DE PINES - MAPEO FÍSICO DEL HARDWARE
// ============================================================================
// Los pines D1, D2, D3, D4, D5, D6, D7, D8 corresponden a pines GPIO del ESP8266
// D1=GPIO5, D2=GPIO4, D3=GPIO0, D4=GPIO2, D5=GPIO14, D6=GPIO12, D7=GPIO13, D8=GPIO15

// Pines de control del Motor L298N (salidas digitales para control de dirección)
#define IN1 D2  // Rueda DELANTERA IZQUIERDA (GPIO4)
#define IN2 D1  // Rueda DELANTERA DERECHA (GPIO5)
#define IN3 D7  // Rueda TRASERA IZQUIERDA (GPIO13)
#define IN4 D8  // Rueda TRASERA DERECHA (GPIO15)

// Pines PWM para control de velocidad de los motores
// El L298N necesita señales PWM para controlar la velocidad (0-255)
#define PWM_IN1 D2  // Mismo pin IN1, usamos PWM para velocidad
#define PWM_IN2 D1  // Mismo pin IN2, usamos PWM para velocidad
#define PWM_IN3 D7  // Mismo pin IN3, usamos PWM para velocidad
#define PWM_IN4 D8  // Mismo pin IN4, usamos PWM para velocidad

// Pines de los servomotores (solo necesitan un pin de control PWM)
#define ServoPin1 D4  // Servomotor 1 - Levantador de contenedor 1 (GPIO2)
#define ServoPin2 D3  // Servomotor 2 - Levantador de contenedor 2 (GPIO0)

// Pines I2C para pantalla OLED - Según pinout de la placa ESP8266 0.96" OLED
// La placa etiqueta explícitamente: OLED SDA=GPIO12(D6), OLED SDL=GPIO14(D5)
#define OLED_SDA D6   // GPIO12 - Dato I2C  (marcado "OLED SDA" en la placa)
#define OLED_SCL D5   // GPIO14 - Reloj I2C (marcado "OLED SDL" en la placa)
#define OLED_ADDR 0x3C // Dirección I2C del OLED (0x3C es la más común)

// ============================================================================
// 3. CREDENCIALES DE WiFi
// ============================================================================
// IMPORTANTE: Cambiar estos valores según tu red WiFi
const char* WIFI_SSID = "TALLER MECATRONICA";      // Nombre de tu red WiFi (SSID)
const char* WIFI_PASS = "@MECA2025.ITM";      // Contraseña de tu red WiFi

// ============================================================================
// 3.1. CONFIGURACION DE SERVIDOR LOCAL (XAMPP)
// ============================================================================
// Servidor LOCAL (XAMPP) - único servidor
const char* SERVER_HOST_LOCAL = "192.168.0.118";  // IP de tu PC con XAMPP en la red
const int SERVER_PORT_LOCAL = 80;

// Endpoints API del Camion (carpeta CONTROL_DE_CAMIONES_A_DISTANCIA)
const char* API_CAMION_COMMAND = "/PRERMI/CONTROL_DE_CAMIONES_A_DISTANCIA/obtener_comando.php";
const char* API_CAMION_STATUS  = "/PRERMI/CONTROL_DE_CAMIONES_A_DISTANCIA/estado.php";

// Intervalos de polling
const unsigned long COMMAND_POLL_INTERVAL = 2000;
const unsigned long STATUS_SEND_INTERVAL = 3000;
unsigned long lastCommandPollTime = 0;
unsigned long lastStatusSendTime = 0;

// ============================================================================
// 4. CONFIGURACIÓN DEL SERVIDOR WEB LOCAL EN EL ESP8266
// ============================================================================
// El ESP8266 correrá su propio servidor web para recibir comandos HTTP
const uint16_t LOCAL_SERVER_PORT = 80;     // Puerto del servidor local (80=HTTP estándar)

// ============================================================================
// 5. PARÁMETROS DE CONTROL
// ============================================================================
// Velocidad máxima de los motores (0-1023 para PWM, pero usamos 0-255)
const int MAX_SPEED = 255;

// Velocidad por defecto al iniciar (alto para vencer inercia en motores 3V)
const int DEFAULT_SPEED = 220;

// Rango de ángulos para los servomotores
const int SERVO1_MIN = 0;
const int SERVO1_MAX = 180;
const int SERVO2_MIN = 0;
const int SERVO2_MAX = 180;

// ============================================================================
// 6. VARIABLES GLOBALES
// ============================================================================
// Objeto servidor WiFi para recibir peticiones HTTP
WiFiServer server(LOCAL_SERVER_PORT);

// Objetos de servomotores
Servo servo1;  // Servomotor 1 (para levantador de contenedor)
Servo servo2;  // Servomotor 2 (para levantador de contenedor)

// Variables de estado actual del camión
int currentSpeed_IN1 = 0;   // Velocidad actual rueda delantera izquierda
int currentSpeed_IN2 = 0;   // Velocidad actual rueda delantera derecha
int currentSpeed_IN3 = 0;   // Velocidad actual rueda trasera izquierda
int currentSpeed_IN4 = 0;   // Velocidad actual rueda trasera derecha
int currentAngle_Servo1 = 90;  // Ángulo actual servomotor 1
int currentAngle_Servo2 = 90;  // Ángulo actual servomotor 2

// Variable para almacenar el último comando recibido
String lastCommand = "NONE";

// Tiempo de última comunicación (para detectar desconexión)
unsigned long lastCommunicationTime = 0;

// Objeto pantalla OLED (128x64 pixeles, dirección I2C, pines SDA/SCL)
SSD1306Wire display(OLED_ADDR, OLED_SDA, OLED_SCL);

// Flag: se detectó el OLED en el bus I2C?
bool oledAvailable = false;

// Variables de la pantalla OLED
unsigned long lastDisplayUpdate = 0;
const unsigned long DISPLAY_UPDATE_INTERVAL = 500;  // Actualizar cada 500ms
int displayPage = 0;                                // Página actual
const int DISPLAY_PAGES = 3;                        // Total de páginas
unsigned long lastPageChange = 0;
const unsigned long PAGE_CHANGE_INTERVAL = 3000;    // Cambiar página cada 3s

// Variables para rastrear estado HTTP (mostrar en pantalla)
String httpLocalStatus = "---";
int httpRequestCount = 0;
int httpErrorCount = 0;
int httpLocalClientCount = 0;  // Peticiones HTTP locales recibidas

// ============================================================================
// 7. DECLARACIÓN DE FUNCIONES (forward declarations)
// ============================================================================
// Estas funciones se definen más adelante pero las declaramos aquí
void initWiFi();                          // Conectar a WiFi
void handleHTTPRequest();                 // Procesar solicitudes HTTP
void processCommand(String command);      // Procesar comandos recibidos
void moveMotors(int dir1, int dir2, int dir3, int dir4, int spd1, int spd2, int spd3, int spd4);
void moveServo(int servoNumber, int angle);
void stopAllMotors();                     // Detener todos los motores
void printSystemStatus();                 // Imprimir estado en puerto serial
void pollCommandFromServer();             // Consultar comandos del servidor
void sendStatusToServer();                // Enviar estado al servidor local
void updateDisplay();                     // Actualizar pantalla OLED

String buildURL(const char* host, int port, const char* endpoint) {
  String url = "http://" + String(host);
  if (port != 80) url += ":" + String(port);
  url += endpoint;
  return url;
}

// ============================================================================
// 8. FUNCIÓN SETUP() - SE EJECUTA UNA VEZ AL INICIAR
// ============================================================================
void setup() {
  // 8.1: Inicializar comunicación serial a 115200 baud
  // (Velocidad estándar para ESP8266, se puede ver en el Monitor Serial del IDE)
  Serial.begin(115200);
  analogWriteRange(255);  // Fijar rango PWM a 0-255 (ESP8266 usa 0-1023 por defecto)
  delay(100);  // Esperar a que se estabilice la comunicación serial
  
  // 8.2: Imprimir encabezado en el Monitor Serial
  Serial.println("\n\n");
  Serial.println("##############################################");
  Serial.println("CAMIÓN INTELIGENTE - Sistema de Control");
  Serial.println("##############################################");
  Serial.println("Inicializando pines...");

  // 8.2b: Inicializar pantalla OLED (con detección segura)
  Serial.println("Detectando pantalla OLED...");
  Wire.begin(OLED_SDA, OLED_SCL);  // SDA=D6(GPIO12), SCL=D5(GPIO14) según pinout
  delay(100);
  Wire.beginTransmission(OLED_ADDR);
  byte i2cError = Wire.endTransmission();
  if (i2cError == 0) {
    oledAvailable = true;
    Serial.println("OLED detectado en 0x" + String(OLED_ADDR, HEX) + " - Inicializando...");
    display.init();
    display.flipScreenVertically();
    display.setFont(ArialMT_Plain_10);
    display.setTextAlignment(TEXT_ALIGN_CENTER);
    display.clear();
    display.drawString(64, 10, "CAMION INTELIGENTE");
    display.drawString(64, 30, "Iniciando...");
    display.display();
  } else {
    oledAvailable = false;
    Serial.println("OLED NO detectado en pines SDA=D6(GPIO12), SCL=D5(GPIO14) (error=" + String(i2cError) + ")");
    Serial.println("Si tu placa tiene OLED integrado, revisa los pines I2C correctos.");
    Serial.println("Continuando SIN pantalla OLED...");
  }

  // 8.3: Configurar pines como SALIDAS (OUTPUT)
  // digitalWrite() solo funciona correctamente si el pin está configurado como OUTPUT
  // Pines de motores: configurados para enviar señales PWM
  pinMode(IN1, OUTPUT);      // Rueda delantera izquierda
  pinMode(IN2, OUTPUT);      // Rueda delantera derecha
  pinMode(IN3, OUTPUT);      // Rueda trasera izquierda
  pinMode(IN4, OUTPUT);      // Rueda trasera derecha

  // Pines de servomotores: también OUTPUT para PWM
  pinMode(ServoPin1, OUTPUT);  // Servomotor 1
  pinMode(ServoPin2, OUTPUT);  // Servomotor 2

  // 8.4: Inicializar pines en estado BAJO (LOW = 0V = sin movimiento)
  // LOW hace que los motores estén detenidos
  digitalWrite(IN1, LOW);
  digitalWrite(IN2, LOW);
  digitalWrite(IN3, LOW);
  digitalWrite(IN4, LOW);

  // 8.5: Inicializar servomotores a 90 grados (centro/posición neutral)
  Serial.println("Inicializando servomotores...");
  servo1.attach(ServoPin1);  // Conectar servomotor 1 al pin D4
  servo2.attach(ServoPin2);  // Conectar servomotor 2 al pin D3
  servo1.write(90);          // Mover servo1 a 90 grados
  servo2.write(90);          // Mover servo2 a 90 grados
  delay(500);                // Esperar a que los servos se posicionen

  // 8.6: Conectar a WiFi
  Serial.println("Conectando a WiFi...");
  initWiFi();  // LLamar función que conecta a WiFi

  // 8.7: Iniciar servidor HTTP en el ESP8266
  // Este servidor escuchará solicitudes HTTP en el puerto 80
  server.begin();
  Serial.print("Servidor HTTP iniciado en: http://");
  Serial.print(WiFi.localIP());  // Imprimir IP asignada por el router
  Serial.println(":80/control");

  // 8.8: Imprimir estado del sistema
  Serial.println("\n✓ Sistema lista! Esperando comandos...\n");
  printSystemStatus();

  // 8.9: Mostrar pantalla inicial con IP
  if (oledAvailable) {
    display.clear();
    display.setFont(ArialMT_Plain_10);
    display.setTextAlignment(TEXT_ALIGN_CENTER);
    display.drawString(64, 0, "=== LISTO ===");
    display.setTextAlignment(TEXT_ALIGN_LEFT);
    display.drawString(0, 14, "WiFi: OK");
    display.drawString(0, 28, "IP: " + WiFi.localIP().toString());
    display.drawString(0, 42, "Esperando comandos...");
    display.display();
  }
}

// ============================================================================
// 9. FUNCIÓN LOOP() - SE EJECUTA CONSTANTEMENTE
// ============================================================================
void loop() {
  // 9.1: Verificar si WiFi sigue conectado
  // Si se desconecta, reiniciar automáticamente
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi desconectado! Reconectando...");
    initWiFi();  // Reconectar
  }

  // 9.2: Verificar si hay cliente conectado
  // Esto revisa si alguien está enviando una solicitud HTTP
  WiFiClient client = server.accept();
  if (client) {
    Serial.println("Cliente conectado!");
    httpLocalClientCount++;  // Contar peticiones HTTP locales recibidas
    
    // 9.3: Leer la solicitud HTTP line by line
    String request = "";
    boolean readingBody = false;
    String jsonBody = "";
    
    while (client.connected()) {
      // Leer línea de la solicitud HTTP
      String line = client.readStringUntil('\n');
      request += line;
      
      // El cuerpo JSON viene después de una línea vacía
      if (line == "\r") {
        readingBody = true;
        continue;
      }
      
      if (readingBody && line != "") {
        jsonBody = line;
        break;
      }
      
      if (!client.available()) break;
    }

    // 9.4: Log de la solicitud recibida
    Serial.println("=== Solicitud HTTP Recibida ===");
    Serial.println("Headers: " + request.substring(0, 100));
    Serial.println("Body: " + jsonBody);

    // 9.5: Procesar JSON y ejecutar comando
    if (jsonBody.length() > 0) {
      // Crear un documento JSON para parsear
      // JsonDocument asigna memoria dinamicamente (ArduinoJson v7)
      JsonDocument doc;
      DeserializationError error = deserializeJson(doc, jsonBody);
      
      // 9.6: Verificar si hubo error al parsear JSON
      if (error) {
        Serial.print("Error JSON: ");
        Serial.println(error.f_str());
        
        // Enviar respuesta de error al cliente
        client.println("HTTP/1.1 400 Bad Request");
        client.println("Content-Type: application/json");
        client.println("Connection: close");
        client.println();
        client.println("{\"success\":false,\"message\":\"JSON inválido\"}");
      } else {
        // 9.7: Extraer comando del JSON
        String command = doc["command"] | "NONE";
        int in1 = doc["data"]["in1"] | DEFAULT_SPEED;
        int in2 = doc["data"]["in2"] | DEFAULT_SPEED;
        int in3 = doc["data"]["in3"] | DEFAULT_SPEED;
        int in4 = doc["data"]["in4"] | DEFAULT_SPEED;
        int servo1Angle = doc["data"]["servo1"] | 90;
        int servo2Angle = doc["data"]["servo2"] | 90;
        
        // Guardar último comando para debugging
        lastCommand = command;
        lastCommunicationTime = millis();
        
        // 9.8: Log del comando procesado
        Serial.println("Comando: " + command);
        Serial.println("Velocidades: IN1=" + String(in1) + " IN2=" + String(in2) 
                     + " IN3=" + String(in3) + " IN4=" + String(in4));
        Serial.println("Servos: Servo1=" + String(servo1Angle) + "° Servo2=" + String(servo2Angle) + "°");

        // 9.9: Ejecutar comando recibido
        processCommand(command);
        
        // 9.10: Actualizar velocidades y ángulos
        currentSpeed_IN1 = constrain(in1, 0, 255);  // Limitar entre 0-255
        currentSpeed_IN2 = constrain(in2, 0, 255);
        currentSpeed_IN3 = constrain(in3, 0, 255);
        currentSpeed_IN4 = constrain(in4, 0, 255);
        currentAngle_Servo1 = constrain(servo1Angle, SERVO1_MIN, SERVO1_MAX);
        currentAngle_Servo2 = constrain(servo2Angle, SERVO2_MIN, SERVO2_MAX);
        
        // 9.11: Mover servomotores a ángulos especificados
        servo1.write(currentAngle_Servo1);
        servo2.write(currentAngle_Servo2);
        
        // 9.12: Enviar respuesta HTTP al cliente
        // Primero enviar headers HTTP
        client.println("HTTP/1.1 200 OK");
        client.println("Content-Type: application/json");
        client.println("Connection: close");
        client.println();
        
        // Luego enviar body JSON con confirmación
        client.println("{");
        client.println("  \"success\": true,");
        client.println("  \"message\": \"Comando procesado correctamente\",");
        client.println("  \"command\": \"" + command + "\",");
        client.println("  \"status\": {");
        client.println("    \"in1\": " + String(currentSpeed_IN1) + ",");
        client.println("    \"in2\": " + String(currentSpeed_IN2) + ",");
        client.println("    \"in3\": " + String(currentSpeed_IN3) + ",");
        client.println("    \"in4\": " + String(currentSpeed_IN4) + ",");
        client.println("    \"servo1\": " + String(currentAngle_Servo1) + ",");
        client.println("    \"servo2\": " + String(currentAngle_Servo2));
        client.println("  }");
        client.println("}");
      }
    } else {
      // Si no hay body, enviar error
      client.println("HTTP/1.1 400 Bad Request");
      client.println("Content-Type: application/json");
      client.println("Connection: close");
      client.println();
      client.println("{\"success\":false,\"message\":\"No se recibió cuerpo JSON\"}");
    }

    // 9.13: Cerrar conexión con el cliente
    delay(10);
    client.stop();
    Serial.println("Cliente desconectado\n");
  }

  // 9.14: Verificar timeout de comunicación (seguridad)
  // Si no hay comunicación por más de 5 segundos, detener motores
  if (lastCommunicationTime > 0 && (millis() - lastCommunicationTime > 5000)) {
    if (lastCommand != "STOP" && lastCommand != "NONE") {
      Serial.println("⚠ TIMEOUT: Sin comunicación, deteniendo motores...");
      stopAllMotors();
      lastCommunicationTime = 0;
    }
  }

  // 9.15: Consultar comandos del servidor local (XAMPP)
  unsigned long now = millis();
  if (WiFi.status() == WL_CONNECTED && now - lastCommandPollTime >= COMMAND_POLL_INTERVAL) {
    lastCommandPollTime = now;
    pollCommandFromServer();
  }

  // 9.16: Enviar estado al servidor local
  if (WiFi.status() == WL_CONNECTED && now - lastStatusSendTime >= STATUS_SEND_INTERVAL) {
    lastStatusSendTime = now;
    sendStatusToServer();
  }

  // 9.17: Actualizar pantalla OLED periódicamente (solo si fue detectada)
  if (oledAvailable && now - lastDisplayUpdate >= DISPLAY_UPDATE_INTERVAL) {
    lastDisplayUpdate = now;
    // Auto-rotar páginas
    if (now - lastPageChange >= PAGE_CHANGE_INTERVAL) {
      lastPageChange = now;
      displayPage = (displayPage + 1) % DISPLAY_PAGES;
    }
    updateDisplay();
  }
}

// ============================================================================
// 10. FUNCIÓN initWiFi() - CONECTAR A RED WiFi
// ============================================================================
void initWiFi() {
  // Modo WiFi como cliente (Station mode)
  // En este modo el ESP8266 se conecta a un router WiFi existente
  WiFi.mode(WIFI_STA);

  // Comenzar conexión WiFi con SSID y contraseña
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  // Counter para evitar loop infinito
  int attempts = 0;
  
  // Loopeo hasta conectar (máximo 20 intentos = 10 segundos)
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);  // Esperar 500ms entre intentos
    Serial.print(".");
    attempts++;
  }

  // Verificar o respuesta
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✓ WiFi conectado!");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());  // Imprimir IP asignada (ej: 192.168.1.100)
    Serial.print("SSID: ");
    Serial.println(WiFi.SSID());
  } else {
    Serial.println("\n✗ Error conectando a WiFi! Reiniciando...");
    delay(1000);
    ESP.restart();  // Reiniciar el microcontrolador
  }
}

// ============================================================================
// 11. FUNCIÓN processCommand() - PROCESAR COMANDOS RECIBIDOS
// ============================================================================
void processCommand(String command) {
  // Convertir comando a mayúsculas para comparación consistente
  command.toUpperCase();

  // 11.0: Comando START (Arrancar todos los motores a velocidad por defecto)
  // Pone todas las ruedas a DEFAULT_SPEED y arranca hacia adelante
  // IN1=PWM, IN2=0, IN3=PWM, IN4=0 → Motor A adelante + Motor B adelante
  if (command == "START") {
    Serial.println("> ARRANQUE: Motores a velocidad por defecto");
    currentSpeed_IN1 = DEFAULT_SPEED;
    currentSpeed_IN2 = DEFAULT_SPEED;
    currentSpeed_IN3 = DEFAULT_SPEED;
    currentSpeed_IN4 = DEFAULT_SPEED;
    moveMotors(HIGH, LOW, HIGH, LOW,
               currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
  }

  // 11.1: Comando FORWARD (Adelante)
  // Motor A adelante: IN1=PWM, IN2=0
  // Motor B adelante: IN3=PWM, IN4=0
  else if (command == "FORWARD") {
    Serial.println("> Moviendo ADELANTE");
    moveMotors(HIGH, LOW, HIGH, LOW,
               currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
  }

  // 11.2: Comando BACKWARD (Atrás)
  // Motor A atrás: IN1=0, IN2=PWM
  // Motor B atrás: IN3=0, IN4=PWM
  else if (command == "BACKWARD") {
    Serial.println("> Moviendo ATR\u00c1S");
    moveMotors(LOW, HIGH, LOW, HIGH,
               currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
  }

  // 11.3: Comando LEFT (Izquierda)
  // Motor A (izquierdo) atrás: IN1=0, IN2=PWM
  // Motor B (derecho) adelante: IN3=PWM, IN4=0
  else if (command == "LEFT") {
    Serial.println("> Girando IZQUIERDA");
    moveMotors(LOW, HIGH, HIGH, LOW,
               currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
  }

  // 11.4: Comando RIGHT (Derecha)
  // Motor A (izquierdo) adelante: IN1=PWM, IN2=0
  // Motor B (derecho) atrás: IN3=0, IN4=PWM
  else if (command == "RIGHT") {
    Serial.println("> Girando DERECHA");
    moveMotors(HIGH, LOW, LOW, HIGH,
               currentSpeed_IN1, currentSpeed_IN2, currentSpeed_IN3, currentSpeed_IN4);
  }

  // 11.5: Comando STOP (Detener)
  // Detener todos los motores inmediatamente
  else if (command == "STOP") {
    Serial.println("> Deteniendo todos los motores");
    stopAllMotors();
  }

  // 11.6: Comando SERVO1_MOVE (Mover Servomotor 1)
  // El ángulo ya está en currentAngle_Servo1 (actualizado en loop())
  else if (command == "SERVO1_MOVE") {
    Serial.println("> Moviendo SERVO1 a " + String(currentAngle_Servo1) + "°");
    moveServo(1, currentAngle_Servo1);
  }

  // 11.7: Comando SERVO2_MOVE (Mover Servomotor 2)
  else if (command == "SERVO2_MOVE") {
    Serial.println("> Moviendo SERVO2 a " + String(currentAngle_Servo2) + "°");
    moveServo(2, currentAngle_Servo2);
  }

  // 11.8: Comando EMERGENCY_STOP (Parada de Emergencia)
  // Detener todo lo posible para evitar daños
  else if (command == "EMERGENCY_STOP") {
    Serial.println("⚠⚠⚠ ¡PARADA DE EMERGENCIA! ⚠⚠⚠");
    stopAllMotors();
    // Opcional: puedes agregar aquí sonidos de alerta, LED, etc.
  }

  // 11.9: Comando desconocido
  else {
    Serial.println("⚠ Comando desconocido: " + command);
  }
}

// ============================================================================
// 12. FUNCIÓN moveMotors() - CONTROL DE MOTORES CON VELOCIDAD
// ============================================================================
void moveMotors(int dir1, int dir2, int dir3, int dir4, 
                int speed1, int speed2, int speed3, int speed4) {
  // 12.1: Control L298N mediante analogWrite por par de pines
  // Motor A (izquierdo) = IN1 + IN2:  IN1=PWM → adelante | IN2=PWM → atrás
  // Motor B (derecho)   = IN3 + IN4:  IN3=PWM → adelante | IN4=PWM → atrás
  // Si dir==HIGH el pin recibe PWM, si dir==LOW el pin va a 0 (apagado).
  // Nunca deben tener PWM los dos pines del mismo motor simultáneamente
  // (causaría freno / brake en el L298N).
  analogWrite(IN1, dir1 == HIGH ? speed1 : 0);
  analogWrite(IN2, dir2 == HIGH ? speed2 : 0);
  analogWrite(IN3, dir3 == HIGH ? speed3 : 0);
  analogWrite(IN4, dir4 == HIGH ? speed4 : 0);

  // 12.3: Log de estado
  Serial.print("  Dir[");
  Serial.print(dir1); Serial.print(",");
  Serial.print(dir2); Serial.print(",");
  Serial.print(dir3); Serial.print(",");
  Serial.print(dir4);
  Serial.print("] Speed[");
  Serial.print(speed1); Serial.print(",");
  Serial.print(speed2); Serial.print(",");
  Serial.print(speed3); Serial.print(",");
  Serial.print(speed4);
  Serial.println("]");
}

// ============================================================================
// 13. FUNCIÓN moveServo() - CONTROL DE SERVOMOTORES
// ============================================================================
void moveServo(int servoNumber, int angle) {
  // 13.1: Validar que el ángulo esté en rango válido
  // constrain() limita un valor entre un mínimo y máximo
  // Ejemplo: constrain(200, 0, 180) devuelve 180
  if (servoNumber == 1) {
    angle = constrain(angle, SERVO1_MIN, SERVO1_MAX);
    servo1.write(angle);  // Enviar comando al servo
    currentAngle_Servo1 = angle;
    Serial.print("  Servo1 movido a ");
    Serial.print(angle);
    Serial.println("°");
  } 
  else if (servoNumber == 2) {
    angle = constrain(angle, SERVO2_MIN, SERVO2_MAX);
    servo2.write(angle);  // Enviar comando al servo
    currentAngle_Servo2 = angle;
    Serial.print("  Servo2 movido a ");
    Serial.print(angle);
    Serial.println("°");
  }
  else {
    Serial.println("  ✗ Número de servo inválido (debe ser 1 o 2)");
  }
}

// ============================================================================
// 14. FUNCIÓN stopAllMotors() - DETENER TODO DE INMEDIATO
// ============================================================================
void stopAllMotors() {
  // 14.1: Detener motores DC (velocidad = 0)
  analogWrite(PWM_IN1, 0);   // Detener IN1
  analogWrite(PWM_IN2, 0);   // Detener IN2
  analogWrite(PWM_IN3, 0);   // Detener IN3
  analogWrite(PWM_IN4, 0);   // Detener IN4

  // 14.2: Actualizar variables de estado
  currentSpeed_IN1 = 0;
  currentSpeed_IN2 = 0;
  currentSpeed_IN3 = 0;
  currentSpeed_IN4 = 0;

  // 14.3: Log
  Serial.println("  ✓ Todos los motores detenidos");
}

// ============================================================================
// 15. FUNCIÓN printSystemStatus() - IMPRIMIR ESTADO DEL SISTEMA EN SERIAL
// ============================================================================
void printSystemStatus() {
  Serial.println("\n========== ESTADO DEL SISTEMA ==========");
  Serial.print("WiFi SSID: ");
  Serial.println(WIFI_SSID);
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
  Serial.print("Puerto Servidor: ");
  Serial.println(LOCAL_SERVER_PORT);
  Serial.println("Servidores remotos:");
  Serial.print("  Local:  "); Serial.print(SERVER_HOST_LOCAL); Serial.print(":"); Serial.println(SERVER_PORT_LOCAL);
  Serial.println("\nPines Configurados:");
  Serial.println("  IN1 (Motor 1) = D2");
  Serial.println("  IN2 (Motor 2) = D1");
  Serial.println("  IN3 (Motor 3) = D7");
  Serial.println("  IN4 (Motor 4) = D8");
  Serial.println("  Servo1 = D4");
  Serial.println("  Servo2 = D3");
  Serial.println("\nRangos de Control:");
  Serial.println("  Motor Speed: 0-255 PWM");
  Serial.println("  Servo1 Angle: 0-180°");
  Serial.println("  Servo2 Angle: 0-180°");
  Serial.println("=========================================\n");
}

// ============================================================================
// 16. FUNCIÓN pollCommandFromServer() - CONSULTAR COMANDOS DEL SERVIDOR LOCAL
// ============================================================================
void pollCommandFromServer() {
  String url = buildURL(SERVER_HOST_LOCAL, SERVER_PORT_LOCAL, API_CAMION_COMMAND);
  WiFiClient wifiClient;
  HTTPClient http;
  http.begin(wifiClient, url);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(5000);

  int code = http.GET();
  if (code == 200) {
    httpLocalStatus = "OK (200)";
    httpRequestCount++;
    String payload = http.getString();
    JsonDocument doc;
    DeserializationError error = deserializeJson(doc, payload);
    if (!error) {
      String command = doc["command"] | "NONE";
      if (command != "NONE") {
        int in1 = doc["data"]["in1"] | DEFAULT_SPEED;
        int in2 = doc["data"]["in2"] | DEFAULT_SPEED;
        int in3 = doc["data"]["in3"] | DEFAULT_SPEED;
        int in4 = doc["data"]["in4"] | DEFAULT_SPEED;
        int s1 = doc["data"]["servo1"] | currentAngle_Servo1;
        int s2 = doc["data"]["servo2"] | currentAngle_Servo2;

        lastCommand = command;
        lastCommunicationTime = millis();
        currentSpeed_IN1 = constrain(in1, 0, 255);
        currentSpeed_IN2 = constrain(in2, 0, 255);
        currentSpeed_IN3 = constrain(in3, 0, 255);
        currentSpeed_IN4 = constrain(in4, 0, 255);
        currentAngle_Servo1 = constrain(s1, SERVO1_MIN, SERVO1_MAX);
        currentAngle_Servo2 = constrain(s2, SERVO2_MIN, SERVO2_MAX);

        processCommand(command);
        servo1.write(currentAngle_Servo1);
        servo2.write(currentAngle_Servo2);
        Serial.println("[POLL] Comando de localhost: " + command);
      }
    }
  } else {
    httpLocalStatus = "ERR (" + String(code) + ")";
    httpErrorCount++;
    Serial.printf("[POLL] localhost HTTP code: %d\n", code);
  }
  http.end();
}

// ============================================================================
// 17. FUNCIÓN sendStatusToServer() - ENVIAR ESTADO AL SERVIDOR LOCAL
// ============================================================================
void sendStatusToServer() {
  String body = "{";
  body += "\"command\":\"" + lastCommand + "\",";
  body += "\"in1\":" + String(currentSpeed_IN1) + ",";
  body += "\"in2\":" + String(currentSpeed_IN2) + ",";
  body += "\"in3\":" + String(currentSpeed_IN3) + ",";
  body += "\"in4\":" + String(currentSpeed_IN4) + ",";
  body += "\"servo1\":" + String(currentAngle_Servo1) + ",";
  body += "\"servo2\":" + String(currentAngle_Servo2) + ",";
  body += "\"ip\":\"" + WiFi.localIP().toString() + "\",";
  body += "\"uptime\":" + String(millis() / 1000);
  body += "}";

  // Enviar a localhost
  String url = buildURL(SERVER_HOST_LOCAL, SERVER_PORT_LOCAL, API_CAMION_STATUS);
  WiFiClient wifiClient;
  HTTPClient http;
  http.begin(wifiClient, url);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(5000);
  int code = http.POST(body);
  if (code == 200) {
    httpLocalStatus = "OK (200)";
    Serial.println("[STATUS] Enviado a localhost OK");
  } else {
    httpLocalStatus = "ERR (" + String(code) + ")";
    httpErrorCount++;
    Serial.printf("[STATUS] localhost error: %d\n", code);
  }
  http.end();
}

// ============================================================================
// 18. FUNCIÓN updateDisplay() - ACTUALIZAR PANTALLA OLED
// ============================================================================
void updateDisplay() {
  display.clear();

  switch (displayPage) {
    case 0: {
      // PÁGINA 0: Resumen general del sistema
      display.setFont(ArialMT_Plain_10);
      display.setTextAlignment(TEXT_ALIGN_CENTER);
      display.drawString(64, 0, "=== CAMION ===");
      display.setTextAlignment(TEXT_ALIGN_LEFT);

      // Estado WiFi
      if (WiFi.status() == WL_CONNECTED) {
        display.drawString(0, 13, "WiFi: OK  RSSI:" + String(WiFi.RSSI()));
        display.drawString(0, 25, "IP: " + WiFi.localIP().toString());
      } else {
        display.drawString(0, 13, "WiFi: DESCONECTADO!");
        display.drawString(0, 25, "Reconectando...");
      }

      // Ultimo comando recibido
      display.drawString(0, 37, "CMD: " + lastCommand);

      // Uptime
      unsigned long upSec = millis() / 1000;
      int h = upSec / 3600;
      int m = (upSec % 3600) / 60;
      int s = upSec % 60;
      char buf[20];
      snprintf(buf, sizeof(buf), "Up: %02d:%02d:%02d", h, m, s);
      display.drawString(0, 49, String(buf));
      break;
    }

    case 1: {
      // PÁGINA 1: Estado de conexiones HTTP
      display.setFont(ArialMT_Plain_10);
      display.setTextAlignment(TEXT_ALIGN_CENTER);
      display.drawString(64, 0, "=== HTTP ===");
      display.setTextAlignment(TEXT_ALIGN_LEFT);

      display.drawString(0, 13, "Local: " + httpLocalStatus);
      display.drawString(0, 25, "Req:" + String(httpRequestCount) +
                                " Err:" + String(httpErrorCount) +
                                " Cli:" + String(httpLocalClientCount));

      // Tiempo desde ultima comunicacion
      if (lastCommunicationTime > 0) {
        unsigned long ago = (millis() - lastCommunicationTime) / 1000;
        display.drawString(0, 37, "Ult.com: " + String(ago) + "s atras");
      } else {
        display.drawString(0, 37, "Ult.com: ninguna");
      }
      break;
    }

    case 2: {
      // PÁGINA 2: Estado de motores y servos
      display.setFont(ArialMT_Plain_10);
      display.setTextAlignment(TEXT_ALIGN_CENTER);
      display.drawString(64, 0, "=== MOTORES ===");
      display.setTextAlignment(TEXT_ALIGN_LEFT);

      display.drawString(0, 13, "IN1:" + String(currentSpeed_IN1) +
                                "  IN2:" + String(currentSpeed_IN2));
      display.drawString(0, 25, "IN3:" + String(currentSpeed_IN3) +
                                "  IN4:" + String(currentSpeed_IN4));
      display.drawString(0, 37, "Servo1: " + String(currentAngle_Servo1) + " grd");
      display.drawString(0, 49, "Servo2: " + String(currentAngle_Servo2) + " grd");
      break;
    }
  }

  // Indicador de página en esquina inferior derecha
  display.setTextAlignment(TEXT_ALIGN_RIGHT);
  display.setFont(ArialMT_Plain_10);
  display.drawString(128, 54, String(displayPage + 1) + "/" + String(DISPLAY_PAGES));

  display.display();
}

// ##############################################################################
// FIN DEL CÓDIGO
// ##############################################################################
// 
// NOTAS IMPORTANTES PARA HACER SIN IA:
// 
// 1. PINES: Cada pin GPIO está mapeado a un nombre (D1, D2, etc.)
//    Consulta: https://github.com/esp8266/Arduino/blob/master/variants/generic/pins_arduino.h
//
// 2. WiFi: WiFi.begin() conecta a un router.
//    WiFi.localIP() obtiene la IP asignada automáticamente.
//
// 3. Servidor HTTP: WiFiServer crea un servidor que escucha en un puerto.
//    Se comprueba con server.available() si hay un cliente conectado.
//
// 4. JSON: ArduinoJson parsea automáticamente strings JSON.
//    Para cambiar comandos, solo modifica el switch() en processCommand().
//
// 5. PWM: analogWrite() envía señal PWM para controlar velocidad.
//    Rango: 0-1023 en ESP8266, pero se puede usar 0-255 también.
//
// 6. Servos: servo.write(angle) mueve el servo a un ángulo específico.
//    Rango típico: 0-180 grados.
//
// 7. Debugging: Usa Serial.println() para ver qué está pasando.
//    Monitor Serial: Herramientas > Monitor Serial (115200 baud)
//
// ##############################################################################