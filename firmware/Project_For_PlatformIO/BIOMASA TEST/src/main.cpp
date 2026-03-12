#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>

// ==================== PINES ====================
#define RELAY_HEATER     4
#define RELAY_FAN        5
#define TEMP_SENSOR_PIN  2
#define CURRENT_SENSOR   1  // GPIO1 => ADC1_CH0

// ==================== WIFI / SERVIDOR PRERMI ====================
const char* ssid = "ESTUDIANTES";
const char* password = "bbDui8n6Rt4z";
const char* prermi_server = "192.168.100.35";
const int   prermi_port   = 80;
const char* prermi_endpoint = "/PRERMI/api/biores.php";  // Endpoint central (actualizado a la ruta API)

WiFiClient wifiClient;

// ==================== SENSOR DE TEMPERATURA ====================
OneWire oneWire(TEMP_SENSOR_PIN);
DallasTemperature sensors(&oneWire);

// ==================== VARIABLES ====================
float currentTemperature = 0.0;
float currentCurrent     = 0.0;
float energyGenerated    = 0.0;
unsigned long lastEnergyUpdate = 0;

int systemState  = 0;
int heaterState  = 0;
int fanState     = 0;
bool systemStarted = false; // true when server orders start_generacion
bool bypass_temp = false;
bool bypass_fan = false;
bool bypass_heater = false;
bool bypass_current = false;
bool system_off = false;

const float TEMP_THRESHOLD   = 30.0;
const float VOLT_REF         = 3.3;
const float ACS_SENSITIVITY  = 0.185;

// ==================== PROTOTIPOS ====================
void setupWiFi();
void fetchServerCommands();
void sendSensorData();
void readTemperature();
void readCurrentSensor();
void applyCommand(const String& cmd);

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("=== SISTEMA BIOMASA (PRERMI CLIENT) ===");

  pinMode(RELAY_HEATER, OUTPUT);
  pinMode(RELAY_FAN, OUTPUT);

  digitalWrite(RELAY_HEATER, LOW);
  digitalWrite(RELAY_FAN, LOW);

  sensors.begin();

  setupWiFi();

  // Wait for server signal to start system (start_generacion)
  Serial.println("Esperando señal 'start_generacion' del servidor...");
  int waitAttempts = 0;
  while (!systemStarted) {
    fetchServerCommands();
    if (heaterState == 1) {
      systemStarted = true;
      break;
    }
    waitAttempts++;
    delay(2000);
    if (waitAttempts > 300) {
      // after long wait, break to avoid infinite loop (optional behaviour)
      Serial.println("Tiempo de espera agotado, continuando sin señal de inicio.");
      break;
    }
  }
}

void loop() {
  // Check commands first so server can change state
  fetchServerCommands();

  // If system not started by server, skip sensor read/send
  if (!systemStarted && heaterState == 0) {
    delay(2000);
    return;
  }

  // System started: read sensors and report
  readTemperature();
  readCurrentSensor();
  // Fan control based on temperature with hysteresis
  const float FAN_ON_T = 35.0;
  const float FAN_OFF_T = 33.0;
  if (!bypass_fan && !system_off) {
    if (currentTemperature >= FAN_ON_T && fanState == 0) {
      digitalWrite(RELAY_FAN, HIGH);
      fanState = 1;
    } else if (currentTemperature <= FAN_OFF_T && fanState == 1) {
      digitalWrite(RELAY_FAN, LOW);
      fanState = 0;
    }
  } else {
    // If bypassed or system off, ensure fan is off
    if (fanState == 1) {
      digitalWrite(RELAY_FAN, LOW);
      fanState = 0;
    }
  }
  sendSensorData();

  delay(5000); // esperar 5s entre ciclos
}

// ==================== FUNCIONES ====================

void setupWiFi() {
  Serial.print("Conectando WiFi: ");
  Serial.println(ssid);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("\nWiFi conectado, IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nError: WiFi no conectado");
  }
}

void fetchServerCommands() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = String("http://") + prermi_server + ":" + prermi_port + prermi_endpoint;
  http.begin(wifiClient, url);
  int httpCode = http.GET();
  if (httpCode == 200) {
    String payload = http.getString();
    DynamicJsonDocument doc(512);
    DeserializationError error = deserializeJson(doc, payload);
    if (!error) {
      String cmd = doc["accion"] | "";
      // read bypass flags if present
      if (doc.containsKey("bypass_temp")) bypass_temp = doc["bypass_temp"]; 
      if (doc.containsKey("bypass_fan")) bypass_fan = doc["bypass_fan"]; 
      if (doc.containsKey("bypass_heater")) bypass_heater = doc["bypass_heater"]; 
      if (doc.containsKey("bypass_current")) bypass_current = doc["bypass_current"]; 
      if (doc.containsKey("system_off")) system_off = doc["system_off"]; 

      if (system_off) {
        // immediate full shutdown
        digitalWrite(RELAY_HEATER, LOW);
        digitalWrite(RELAY_FAN, LOW);
        heaterState = 0; fanState = 0; systemStarted = false;
      }

      if (cmd.length() > 0) applyCommand(cmd);
    }
  }
  http.end();
}

void sendSensorData() {
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }

  HTTPClient http;
  String url = String("http://") + prermi_server + ":" + prermi_port + prermi_endpoint;
  (void)url;

  DynamicJsonDocument doc(256);
  doc["temperatura"] = currentTemperature;
  doc["corriente"]   = currentCurrent;
  doc["calentador"]  = heaterState;
  doc["ventilador"]  = fanState;
  doc["sistema"] = systemStarted ? 1 : 0;
  doc["bypass_temp"] = bypass_temp;
  doc["bypass_fan"] = bypass_fan;
  doc["bypass_heater"] = bypass_heater;
  doc["bypass_current"] = bypass_current;

  String payload;
  serializeJson(doc, payload);

  http.begin(wifiClient, url);
  http.addHeader("Content-Type", "application/json");

  int httpCode = http.POST(payload);
  (void)httpCode;
  http.end();
}

void readTemperature() {
  sensors.requestTemperatures();
  float temp = sensors.getTempCByIndex(0);
  if (temp == DEVICE_DISCONNECTED_C) {
    currentTemperature = 0.0;
  } else {
    currentTemperature = temp;
  }
}

void readCurrentSensor() {
  if (bypass_current) {
    currentCurrent = 0.0;
    return;
  }

  const int samples = 60;
  long sum = 0;
  for (int i = 0; i < samples; i++) {
    sum += analogRead(CURRENT_SENSOR);
    delay(2);
  }
  float avgRaw = sum / (float)samples;
  float voltage = (avgRaw / 4095.0) * VOLT_REF;
  float offsetVoltage = VOLT_REF / 2.0; // sensor Vout at 0A (Vcc/2)
  float measured = (voltage - offsetVoltage) / ACS_SENSITIVITY;
  float rectified = fabs(measured);
  // small-signal noise floor
  if (rectified < 0.05) rectified = 0.0;
  currentCurrent = rectified;

}

void applyCommand(const String& cmd) {
  if (cmd == "start_generacion" || cmd == "start") {
    if (!bypass_heater && !system_off) {
      digitalWrite(RELAY_HEATER, HIGH);
      heaterState = 1;
      systemStarted = true;
    }
    return;
  }
  else if (cmd == "stop_generacion") {
    digitalWrite(RELAY_HEATER, LOW);
    heaterState = 0;
  }
  else if (cmd == "stop" || cmd == "stop_generacion") {
    digitalWrite(RELAY_HEATER, LOW);
    heaterState = 0;
    return;
  }
  else if (cmd == "emergency_off" || cmd == "emergency") {
    digitalWrite(RELAY_HEATER, LOW);
    digitalWrite(RELAY_FAN,    LOW);
    heaterState = fanState = 0;
    systemStarted = false;
  }
}