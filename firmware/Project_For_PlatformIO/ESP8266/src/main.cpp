#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

const char* ssid = "Jhail - Habitación";
const char* password = "123.02589";

ESP8266WebServer server(80);

// ===== VARIABLES DE ESTADO =====
String estadoMovimiento = "DETENIDO";
String estadoBrazo = "IDLE";
String ultimoComando = "";
unsigned long ultimoTiempo = 0;
unsigned long tiempoActualizacion = 500; // Actualizar OLED cada 500ms

// ===== MOTORES MOVIMIENTO =====
#define IN1 D5
#define IN2 D6
#define IN3 D7
#define IN4 D8

// ===== BRAZO HIDRAULICO =====
#define BRAZO_UP D3
#define BRAZO_DOWN D4

// ================= OLED =================
void mostrarOLED() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(WHITE);

  // Mostrar estado de conexión WiFi
  display.setCursor(0, 0);
  if (WiFi.status() == WL_CONNECTED) {
    display.println("WiFi: OK");
  } else {
    display.println("WiFi: CONECTANDO...");
  }

  // Mostrar IP
  display.setCursor(0, 8);
  display.println(WiFi.localIP().toString());

  // Mostrar último comando recibido
  display.setCursor(0, 18);
  display.println("CMD: " + ultimoComando);

  // Mostrar estado de movimiento
  display.setCursor(0, 28);
  display.println("MOV: " + estadoMovimiento);

  // Mostrar estado del brazo
  display.setCursor(0, 38);
  display.println("BRAZO: " + estadoBrazo);

  // Mostrar información de conexiones
  display.setCursor(0, 48);
  display.println("Clientes: " + String(server.client().available()));

  display.setCursor(0, 56);
  display.setTextSize(1);
  display.println("ROBOT ACTIVO");

  display.display();
}

// ================= MOVIMIENTO =================
void detenerMovimiento() {
  digitalWrite(IN1, LOW);
  digitalWrite(IN2, LOW);
  digitalWrite(IN3, LOW);
  digitalWrite(IN4, LOW);
}

void manejarMovimiento(String comando) {

  detenerMovimiento();

  if (comando == "UP") {
    digitalWrite(IN1, HIGH);
    digitalWrite(IN2, LOW);
    estadoMovimiento = "ADELANTE";
    ultimoComando = "ADELANTE";
  }
  else if (comando == "DOWN") {
    digitalWrite(IN1, LOW);
    digitalWrite(IN2, HIGH);
    estadoMovimiento = "ATRAS";
    ultimoComando = "ATRAS";
  }
  else if (comando == "LEFT") {
    digitalWrite(IN3, HIGH);
    digitalWrite(IN4, LOW);
    estadoMovimiento = "IZQUIERDA";
    ultimoComando = "IZQUIERDA";
  }
  else if (comando == "RIGHT") {
    digitalWrite(IN3, LOW);
    digitalWrite(IN4, HIGH);
    estadoMovimiento = "DERECHA";
    ultimoComando = "DERECHA";
  }
  else if (comando == "STOP") {
    estadoMovimiento = "DETENIDO";
    ultimoComando = "STOP";
  }
}

// ================= BRAZO =================
void detenerBrazo() {
  digitalWrite(BRAZO_UP, LOW);
  digitalWrite(BRAZO_DOWN, LOW);
}

void manejarBrazo(String comando) {

  detenerBrazo();

  if (comando == "ARM_UP") {
    digitalWrite(BRAZO_UP, HIGH);
    estadoBrazo = "SUBIENDO";
    ultimoComando = "ARM_UP";
  }
  else if (comando == "ARM_DOWN") {
    digitalWrite(BRAZO_DOWN, HIGH);
    estadoBrazo = "BAJANDO";
    ultimoComando = "ARM_DOWN";
  }
  else if (comando == "ARM_STOP") {
    estadoBrazo = "STOP";
    ultimoComando = "ARM_STOP";
  }
}

// ================= API COMANDO =================
void manejarComando() {

  String comando = "";

  // Compatible con GET ?btn=
  if (server.hasArg("btn")) {
    comando = server.arg("btn");
  }

  // Compatible con POST comando=
  if (server.hasArg("comando")) {
    comando = server.arg("comando");
  }

  if (comando != "") {

    manejarMovimiento(comando);
    manejarBrazo(comando);
    
    ultimoTiempo = millis(); // Actualizar tiempo para mostrar en pantalla
    mostrarOLED();

    server.send(200, "application/json",
      "{\"status\":\"ok\",\"comando\":\"" + comando +
      "\",\"movimiento\":\"" + estadoMovimiento +
      "\",\"brazo\":\"" + estadoBrazo + "\"}"
    );
  }
  else {
    server.send(400, "application/json",
      "{\"status\":\"error\",\"msg\":\"Comando no recibido\"}"
    );
  }
}

// ================= API ESTADO =================
void obtenerEstado() {

  server.send(200, "application/json",
    "{\"movimiento\":\"" + estadoMovimiento +
    "\",\"brazo\":\"" + estadoBrazo + "\"}"
  );
}

// ================= SETUP =================
void setup() {

  pinMode(IN1, OUTPUT);
  pinMode(IN2, OUTPUT);
  pinMode(IN3, OUTPUT);
  pinMode(IN4, OUTPUT);

  pinMode(BRAZO_UP, OUTPUT);
  pinMode(BRAZO_DOWN, OUTPUT);

  detenerMovimiento();
  detenerBrazo();

  Serial.begin(115200);

  // Configurar pines I2C para pantalla OLED (SDA=12, SCL=14)
  Wire.begin(12, 14);

  WiFi.begin(ssid, password);
  Serial.print("Conectando");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nConectado!");
  Serial.println("IP:");
  Serial.println(WiFi.localIP());

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    while (true);
  }

  mostrarOLED();

  // Endpoints
  server.on("/comando", HTTP_ANY, manejarComando);
  server.on("/estado", HTTP_GET, obtenerEstado);

  server.begin();
}

void loop() {
  server.handleClient();
  
  // Actualizar pantalla OLED constantemente
  if (millis() - ultimoTiempo >= tiempoActualizacion) {
    mostrarOLED();
    ultimoTiempo = millis();
  }
}