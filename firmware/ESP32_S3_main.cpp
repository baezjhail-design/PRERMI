#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>

// --- CONFIG ---
const char* SSID = "Jhail-WIFI"; // actualizar
const char* PASS = "123.02589."; // actualizar
const char* SERVER_HOST = "192.168.1.106"; // PRERMI server IP
const int   SERVER_PORT = 8080;
const char* BIORES_ENDPOINT = "/PRERMI/api/biores.php";

// Pins (ajustar a ESP32-S3 CAM)
#define RELAY_HEATER 4
#define RELAY_FAN 5
#define ONEWIRE_PIN 2

OneWire oneWire(ONEWIRE_PIN);
DallasTemperature sensors(&oneWire);

WiFiClient client;

float currentTemperature = 0.0;
float currentCurrent = 0.0;
int heaterState = 0;
int fanState = 0;

unsigned long lastLoop = 0;
const unsigned long LOOP_INTERVAL = 5000; // 5s

void setupWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(SSID, PASS);
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    attempts++;
  }
}

void applyCommand(const String &accion) {
  if (accion == "start") {
    digitalWrite(RELAY_HEATER, HIGH);
    heaterState = 1;
  } else if (accion == "stop") {
    digitalWrite(RELAY_HEATER, LOW);
    heaterState = 0;
  } else if (accion == "emergency") {
    digitalWrite(RELAY_HEATER, LOW);
    digitalWrite(RELAY_FAN, LOW);
    heaterState = 0; fanState = 0;
  }
}

void readSensors() {
  sensors.requestTemperatures();
  float t = sensors.getTempCByIndex(0);
  if (t != DEVICE_DISCONNECTED_C) currentTemperature = t;

  // Current sensor placeholder: implement reading ADC if needed
  currentCurrent = 0.0;
}

void postStatus() {
  if (WiFi.status() != WL_CONNECTED) { setupWiFi(); if (WiFi.status()!=WL_CONNECTED) return; }

  HTTPClient http;
  String url = String("http://") + SERVER_HOST + ":" + SERVER_PORT + BIORES_ENDPOINT;
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<256> doc;
  doc["temperatura"] = currentTemperature;
  doc["corriente"] = currentCurrent;
  doc["calentador"] = heaterState;
  doc["ventilador"] = fanState;

  String payload;
  serializeJson(doc, payload);

  int code = http.POST(payload);
  if (code <= 0) {
    // error, try reconnect
    http.end();
    setupWiFi();
    return;
  }
  http.end();
}

void getCommandAndApply() {
  if (WiFi.status() != WL_CONNECTED) { setupWiFi(); if (WiFi.status()!=WL_CONNECTED) return; }
  HTTPClient http;
  String url = String("http://") + SERVER_HOST + ":" + SERVER_PORT + BIORES_ENDPOINT;
  http.begin(client, url);
  int code = http.GET();
  if (code == 200) {
    String body = http.getString();
    StaticJsonDocument<256> doc;
    DeserializationError err = deserializeJson(doc, body);
    if (!err) {
      String accion = doc["accion"] | "none";
      applyCommand(accion);
    }
  }
  http.end();
}

void setup() {
  Serial.begin(115200);
  pinMode(RELAY_HEATER, OUTPUT);
  pinMode(RELAY_FAN, OUTPUT);
  digitalWrite(RELAY_HEATER, LOW);
  digitalWrite(RELAY_FAN, LOW);
  sensors.begin();
  setupWiFi();
}

void loop() {
  unsigned long now = millis();
  if (now - lastLoop < LOOP_INTERVAL) return;
  lastLoop = now;

  readSensors();
  postStatus();
  getCommandAndApply();
}
