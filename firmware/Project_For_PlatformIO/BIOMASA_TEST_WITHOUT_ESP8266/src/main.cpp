#include <Arduino.h>
// Librerías para ESP32
#include <WiFi.h>
#include <HTTPClient.h>

// MODO TEST: Sin sensores físicos, solo simulación
#define TEST_MODE true
#define ENABLE_OLED false  // Cambiar a true si tu ESP32-S3 CAM tiene OLED

#if ENABLE_OLED
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#endif

// PINES PARA ESP32-S3 (ajusta según tu placa)
#if ENABLE_OLED
#define SDL 21  // SDA para ESP32-S3
#define SCL 22  // SCL para ESP32-S3
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
#endif

// PINES SIMULADOS (no se usarán físicamente en TEST_MODE)
#define PTC_CELDA_PIN 2   // GPIO2 ESP32-S3
#define FAN_PIN 4         // GPIO4 ESP32-S3

// SIMULACION: Rangos para valores aleatorios
float simTempMin = 25.0;  // Temperatura mínima simulada
float simTempMax = 50.0;  // Temperatura máxima simulada
float simCurrentMin = 0.5;  // Corriente mínima
float simCurrentMax = 4.5;  // Corriente máxima

// ============================================
// PARAMETROS BIOMASA - 6 CELDAS PELTIER
// ============================================
const float PELTIER_CELLS = 6.0;          // 6 celdas Peltier
const float PELTIER_NOMINAL_VOLTAGE = 12.0; // 12V nominal
const float PELTIER_NOMINAL_CURRENT = 5.0;  // 5A por celda nominal
const float SEEBECK_EFFICIENCY = 0.05;      // 5% eficiencia Seebeck (hipotético)
const float RESIDUE_HEAT_MULTIPLIER = 1.2;  // Factor de calor por desintegracion residuos

// Estado del sistema
volatile bool generationOn = false;      // Generacion activa (celdas PTC)
volatile bool fanOn = false;             // Ventiladores activos
float currentTemp = NAN;                 // Temperatura actual (°C)
float currentCurrent = 0.0;              // Corriente actual (A) - sensor analogico
unsigned long generationStartTime = 0;   // Tiempo inicio generacion
unsigned long lastGenerationStartTime = 0;
float totalEnergyGenerated = 0.0;        // Energia total generada (Wh)

// Estado remoto (control.json)
bool bypassTemp = false;
bool bypassFan = false;
bool bypassHeater = false;
bool bypassCurrent = false;
bool systemOff = false;
bool killTemp = false;
bool killFan = false;
bool killHeater = false;
bool killCurrent = false;
int lastCommandId = -1;
String lastAction = "none";
String lastRawCommand = "none";

// HISTERESIS PARA VENTILADOR (BIOMASA)
const float FAN_ON_T = 40.0;   // Encender ventilador a 40°C (BIOMASA)
const float FAN_OFF_T = 35.0;  // Apagar ventilador a 35°C

unsigned long lastTempRequest = 0;
const unsigned long TEMP_INTERVAL = 2000;       // Leer temp cada 2s
const unsigned long COMMAND_POLL_INTERVAL = 3000;  // Consultar comando cada 3s
const unsigned long SERVER_STATUS_INTERVAL = 3000; // Enviar estado cada 3s
const unsigned long DISPLAY_PAGE_INTERVAL = 3000;  // Rotacion OLED cada 3s
unsigned long lastCommandPoll = 0;
unsigned long lastServerStatus = 0;
unsigned long lastDisplaySwitch = 0;
uint8_t displayPage = 0;
const uint8_t DISPLAY_PAGE_COUNT = 5;  // +1 para página de eventos

// ============================================
// SISTEMA DE EVENTOS/ACCIONES USUARIO
// ============================================
struct Event {
  unsigned long timestamp;
  String action;
  String details;
};

const uint8_t MAX_EVENTS = 6;
Event eventLog[MAX_EVENTS];
uint8_t eventCount = 0;

void logEvent(const String &action, const String &details = "") {
  // Desplazar eventos antiguos
  for (int i = MAX_EVENTS - 1; i > 0; i--) {
    eventLog[i] = eventLog[i - 1];
  }
  
  // Agregar nuevo evento
  eventLog[0].timestamp = millis();
  eventLog[0].action = action;
  eventLog[0].details = details;
  
  if (eventCount < MAX_EVENTS) {
    eventCount++;
  }
  
  // Log también en Serial
  Serial.print("[EVENT] ");
  Serial.print(action);
  if (details.length() > 0) {
    Serial.print(" - ");
    Serial.print(details);
  }
  Serial.println();
}

// --- CONFIGURACION DE RED Y SERVIDOR XAMPP PRERMI ---
// Edita estos valores según tu red y servidor XAMPP
const char* WIFI_SSID = "Jhail-WIFI";
const char* WIFI_PASS = "123.02589.";
const char* SERVER_HOST = "10.0.0.162";
const int SERVER_PORT = 8080;
const char* API_BIORES = "/PRERMI/BIOMASA/sensores_estado.php";
const char* API_ENERGY = "/PRERMI/BIOMASA/registrar_energia.php";
const char* API_TOKEN = "";

String apiBioresURL() {
  String url = String("http://") + SERVER_HOST;
  if (SERVER_PORT != 80) {
    url += ":" + String(SERVER_PORT);
  }
  url += API_BIORES;
  return url;
}

String apiEnergyURL() {
  String url = String("http://") + SERVER_HOST;
  if (SERVER_PORT != 80) {
    url += ":" + String(SERVER_PORT);
  }
  url += API_ENERGY;
  return url;
}

String jsonValue(const String &json, const String &key) {
  String token = String("\"") + key + "\"";
  int keyPos = json.indexOf(token);
  if (keyPos < 0) return "";

  int colon = json.indexOf(':', keyPos);
  if (colon < 0) return "";

  int i = colon + 1;
  while (i < (int)json.length() && (json[i] == ' ' || json[i] == '\n' || json[i] == '\r' || json[i] == '\t')) i++;
  if (i >= (int)json.length()) return "";

  if (json[i] == '"') {
    i++;
    int end = json.indexOf('"', i);
    if (end < 0) return "";
    return json.substring(i, end);
  }

  int end = i;
  while (end < (int)json.length() && json[end] != ',' && json[end] != '}' && json[end] != '\n' && json[end] != '\r') end++;
  String raw = json.substring(i, end);
  raw.trim();
  return raw;
}

bool jsonBool(const String &json, const String &key, bool fallback = false) {
  String v = jsonValue(json, key);
  if (v.length() == 0) return fallback;
  v.toLowerCase();
  return (v == "true" || v == "1");
}

int jsonInt(const String &json, const String &key, int fallback = 0) {
  String v = jsonValue(json, key);
  if (v.length() == 0) return fallback;
  return v.toInt();
}

// ============================================
// CALCULO DE ENERGIA GENERADA
// ============================================
float calculateEnergyGenerated(unsigned long elapsedMs, float temperatureDiff, float measuredCurrent) {
  // Energia = P * t
  // P (Potencia) = PELTIER_CELLS * PELTIER_NOMINAL_VOLTAGE * PELTIER_NOMINAL_CURRENT * SEEBECK_EFFICIENCY
  // Ajustada por diferencial de temperatura y factor de residuos
  
  if (elapsedMs == 0) return 0.0;
  
  if (measuredCurrent <= 0.05f) return 0.0;

  float safeCurrent = measuredCurrent;
  if (safeCurrent > PELTIER_NOMINAL_CURRENT) safeCurrent = PELTIER_NOMINAL_CURRENT;

  float basePower = PELTIER_CELLS * PELTIER_NOMINAL_VOLTAGE * safeCurrent * SEEBECK_EFFICIENCY;
  float tempFactor = 1.0 + ((temperatureDiff - 30.0) / 120.0);
  if (tempFactor < 0.6) tempFactor = 0.6;
  if (tempFactor > 1.4) tempFactor = 1.4;
  float adjustedPower = basePower * tempFactor * RESIDUE_HEAT_MULTIPLIER;
  
  float energyWh = (adjustedPower * (float)elapsedMs) / (1000.0 * 3600.0);
  return energyWh;
}

// ============================================
// SIMULACION: LECTURA DE SENSORES
// ============================================
float readTemperatureSensor() {
#if TEST_MODE
  // Generar temperatura aleatoria entre simTempMin y simTempMax
  float temp = simTempMin + (random(0, 1000) / 1000.0) * (simTempMax - simTempMin);
  
  // Simular incremento gradual si generación está activa
  if (generationOn) {
    unsigned long elapsed = (millis() - generationStartTime) / 1000;
    temp += (elapsed * 0.1); // Aumenta 0.1°C por segundo
    if (temp > simTempMax) temp = simTempMax;
  }
  
  return temp;
#else
  // Código real con sensor DS18B20 iría aquí
  return 25.0;
#endif
}

float readCurrentSensor() {
#if TEST_MODE
  // Generar corriente aleatoria si generación está activa
  if (!generationOn) return 0.0;
  
  float current = simCurrentMin + (random(0, 1000) / 1000.0) * (simCurrentMax - simCurrentMin);
  return current;
#else
  // Código real con ACS712 iría aquí
  int rawValue = analogRead(A0);
  float voltage = (rawValue / 1023.0) * 5.0;
  float current = (voltage - 2.5) / 0.185;
  if (current < 0) current = 0.0;
  return current;
#endif
}

void applyOutputs() {
  static bool lastPtc = false;
  static bool lastFan = false;

  if (systemOff) {
    generationOn = false;
    fanOn = false;
  }

  if (killHeater) generationOn = false;
  if (killFan) fanOn = false;

#if !TEST_MODE
  // Solo activar pines físicos si NO estamos en TEST_MODE
  digitalWrite(PTC_CELDA_PIN, generationOn ? HIGH : LOW);
  digitalWrite(FAN_PIN, fanOn ? HIGH : LOW);
#endif
  
  if (generationOn != lastPtc || fanOn != lastFan) {
    Serial.printf("[OUTPUT] PTC:%s FAN:%s\n", generationOn ? "ON" : "OFF", fanOn ? "ON" : "OFF");
    lastPtc = generationOn;
    lastFan = fanOn;
  }
}

void processRemoteCommand(const String &payload) {
  String accion = jsonValue(payload, "accion");
  String raw = jsonValue(payload, "raw");
  int commandId = jsonInt(payload, "command_id", lastCommandId);

  if (accion.length() > 0) lastAction = accion;
  if (raw.length() > 0) lastRawCommand = raw;

  bypassTemp = jsonBool(payload, "bypass_temp", bypassTemp);
  bypassFan = jsonBool(payload, "bypass_fan", bypassFan);
  bypassHeater = jsonBool(payload, "bypass_heater", bypassHeater);
  bypassCurrent = jsonBool(payload, "bypass_current", bypassCurrent);
  systemOff = jsonBool(payload, "system_off", systemOff);
  killTemp = jsonBool(payload, "kill_temp", killTemp);
  killFan = jsonBool(payload, "kill_fan", killFan);
  killHeater = jsonBool(payload, "kill_heater", killHeater);
  killCurrent = jsonBool(payload, "kill_current", killCurrent);

  if (commandId != lastCommandId) {
    lastCommandId = commandId;

    if (accion == "start" || raw == "start_generacion") {
      if (!generationOn) {
        systemOff = false;
        killTemp = false;
        killFan = false;
        killHeater = false;
        killCurrent = false;
        bypassTemp = false;
        bypassFan = false;
        bypassHeater = false;
        bypassCurrent = false;
        generationOn = true;
        generationStartTime = millis();
        lastGenerationStartTime = generationStartTime;
        Serial.println("[CMD] START GENERACION - Celdas PTC activadas");
        logEvent("INICIO", "PTC activadas");
      }
    } else if (accion == "stop" || raw == "stop_generacion") {
      if (generationOn) {
        if (!bypassCurrent && !killCurrent) {
          currentCurrent = readCurrentSensor();
        }
        unsigned long elapsedMs = millis() - generationStartTime;
        float energyThisCycle = calculateEnergyGenerated(elapsedMs, currentTemp, currentCurrent);
        totalEnergyGenerated += energyThisCycle;
        Serial.printf("[ENERGY] Ciclo finalizado: %.2f Wh. Total: %.2f Wh\n", energyThisCycle, totalEnergyGenerated);
        logEvent("PARADA", String(energyThisCycle, 1) + "Wh");
      }
      generationOn = false;
      Serial.println("[CMD] STOP GENERACION");
    } else if (accion == "emergency" || raw == "emergency_off") {
      generationOn = false;
      fanOn = false;
      systemOff = true;
      killTemp = true;
      killFan = true;
      killHeater = true;
      killCurrent = true;
      Serial.println("[EMERGENCY] Sistema detenido por EMERGENCIA");
      logEvent("EMERGENCIA", "Sistema OFF");
    } else if (raw == "temp_off") {
      killTemp = true;
      bypassTemp = true;
      logEvent("EMERG TEMP", "Sensor temp OFF");
    } else if (raw == "ventilador_off") {
      killFan = true;
      bypassFan = true;
      fanOn = false;
      logEvent("EMERG FAN", "Ventilador OFF");
    } else if (raw == "corriente_off") {
      killCurrent = true;
      bypassCurrent = true;
      currentCurrent = 0.0;
      logEvent("EMERG CORR", "Sensor corriente OFF");
    } else if (raw == "calentador_off") {
      killHeater = true;
      bypassHeater = true;
      generationOn = false;
      logEvent("EMERG PTC", "Calentador OFF");
    }
  }

  if (systemOff) {
    generationOn = false;
    fanOn = false;
    Serial.println("[SYSTEM] SYSTEM_OFF activo");
    logEvent("SYSTEM_OFF", "Apagado total");
  }

  if (bypassHeater) {
    generationOn = false;
  }

  if (killTemp) bypassTemp = true;
  if (killFan) {
    bypassFan = true;
    fanOn = false;
  }
  if (killHeater) {
    bypassHeater = true;
    generationOn = false;
  }
  if (killCurrent) {
    bypassCurrent = true;
    currentCurrent = 0.0;
  }

  applyOutputs();
}

// ============================================
// CONSULTAR COMANDO DEL SERVIDOR
// ============================================
void pollCommandFromServer() {
  if (WiFi.status() != WL_CONNECTED) return;

  String url = apiBioresURL();
  if (String(API_TOKEN).length() > 0) {
    url += "?token=" + String(API_TOKEN);
  }

  WiFiClient wifiClient;
  HTTPClient http;
  http.begin(wifiClient, url);
  http.addHeader("Content-Type", "application/json");
  
  int code = http.POST("{\"accion\":\"get_command\"}");
  
  if (code == 200) {
    String payload = http.getString();
    int newCmdId = jsonInt(payload, "command_id", lastCommandId);
    if (newCmdId != lastCommandId) {
      Serial.printf("[CMD] Nuevo comando (ID:%d)\n", newCmdId);
      processRemoteCommand(payload);
    }
  } else if (code != 200) {
    Serial.printf("[CMD] Error %d\n", code);
  }
  http.end();
}

// ============================================
// ENVIAR ESTADO AL SERVIDOR
// ============================================
void sendStatusToServer() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[SEND] ✗ WiFi no conectado, saltando envío");
    return;
  }

  String url = apiBioresURL();
  WiFiClient wifiClient;
  HTTPClient http;
  http.begin(wifiClient, url);
  http.addHeader("Content-Type", "application/json");

  // Leer corriente si generacion activa
  if (generationOn && !bypassCurrent) {
    currentCurrent = readCurrentSensor();
  } else {
    currentCurrent = 0.0;
  }

  // Calcular energia si esta funcionando
  float currentEnergyRate = 0.0;
  if (generationOn && !isnan(currentTemp)) {
    unsigned long elapsedMs = millis() - generationStartTime;
    currentEnergyRate = calculateEnergyGenerated(elapsedMs, currentTemp, currentCurrent);
  }

  float totalEnergy = totalEnergyGenerated + currentEnergyRate;

  String body = "{";
  body += "\"temperatura\":" + String(currentTemp, 2) + ",";
  body += "\"corriente\":" + String(bypassCurrent ? 0.0 : currentCurrent, 2) + ",";
  body += "\"ventilador\":" + String(fanOn ? 1 : 0) + ",";
  body += "\"calentador\":" + String(generationOn ? 1 : 0) + ",";
  body += "\"energia_generada\":" + String(totalEnergy, 2) + ",";
  body += "\"sistema_activo\":" + String(generationOn ? 1 : 0) + ",";
  body += "\"timestamp\":\"" + String(millis()) + "\"";
  if (String(API_TOKEN).length() > 0) {
    body += ",\"token\":\"" + String(API_TOKEN) + "\"";
  }
  body += "}";

  int code = http.POST(body);
  
  if (code == 200) {
    Serial.printf("[SRV] Estado enviado - Temp:%.1f E:%.1f Wh\n", currentTemp, totalEnergy);
  } else if (code > 0) {
    Serial.printf("[SRV] Error HTTP %d\n", code);
  } else {
    Serial.printf("[SRV] Error conexión: %s\n", http.errorToString(code).c_str());
  }
  http.end();
}

// ============================================
// MOSTRAR ESTADO (OLED O SERIAL)
// ============================================
void drawStatusOnOLED() {
#if ENABLE_OLED
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
#endif

  if (displayPage == 0) {
    // Pagina 1: SENSORES
#if ENABLE_OLED
    display.println("BIOMASA - SENSORES");
    display.println("==================");
    display.print("Temp: ");
    if (isnan(currentTemp) || bypassTemp) display.println("-- C");
    else display.println(String(currentTemp, 1) + " C");
    display.print("Corriente: ");
    if (bypassCurrent) display.println("-- A");
    else display.println(String(currentCurrent, 2) + " A");
    display.print("PTC: "); display.print(generationOn ? "ON" : "OFF");
    display.print(" FAN: "); display.println(fanOn ? "ON" : "OFF");
    display.println("");
    display.print("Energia: ");
    if (generationOn && !isnan(currentTemp)) {
      unsigned long elapsedMs = millis() - generationStartTime;
      float currentRate = calculateEnergyGenerated(elapsedMs, currentTemp, currentCurrent);
      display.println(String(totalEnergyGenerated + currentRate, 2) + " Wh");
    } else {
      display.println(String(totalEnergyGenerated, 2) + " Wh");
    }
#else
    Serial.println("--- SENSORES ---");
    Serial.printf("Temperatura: %.2f C %s\n", currentTemp, bypassTemp ? "(bypass)" : "");
    Serial.printf("Corriente: %.2f A %s\n", currentCurrent, bypassCurrent ? "(bypass)" : "");
    Serial.printf("PTC: %s\n", generationOn ? "ON" : "OFF");
    Serial.printf("Ventilador: %s\n", fanOn ? "ON" : "OFF");
    if (generationOn && !isnan(currentTemp)) {
      unsigned long elapsedMs = millis() - generationStartTime;
      float currentRate = calculateEnergyGenerated(elapsedMs, currentTemp, currentCurrent);
      Serial.printf("Energía: %.2f Wh (Total: %.2f Wh)\n", currentRate, totalEnergyGenerated + currentRate);
    } else {
      Serial.printf("Energía Total: %.2f Wh\n", totalEnergyGenerated);
    }
#endif
  } else if (displayPage == 1) {
    // Pagina 2: RED
#if ENABLE_OLED
    display.println("BIOMASA - RED/API");
    display.println("==================");
    display.print("WiFi: "); display.println(WiFi.status() == WL_CONNECTED ? "OK" : "OFF");
    display.print("IP: ");
    if (WiFi.status() == WL_CONNECTED) display.println(WiFi.localIP());
    else display.println("N/A");
    display.print("Host: ");
    display.println(String(SERVER_HOST) + ":" + String(SERVER_PORT));
    display.print("API: sensores");
#else
    Serial.println("--- RED/API ---");
    Serial.printf("WiFi: %s\n", WiFi.status() == WL_CONNECTED ? "CONECTADO" : "DESCONECTADO");
    if (WiFi.status() == WL_CONNECTED) {
      Serial.printf("IP Local: %s\n", WiFi.localIP().toString().c_str());
      Serial.printf("RSSI: %d dBm\n", WiFi.RSSI());
    }
    Serial.printf("Servidor: %s:%d\n", SERVER_HOST, SERVER_PORT);
    Serial.printf("Endpoint: %s\n", API_BIORES);
#endif
  } else if (displayPage == 2) {
    // Pagina 3: CONTROL
#if ENABLE_OLED
    display.println("BIOMASA - CONTROL");
    display.println("==================");
    display.print("Accion: "); display.println(lastAction);
    display.print("CmdId: "); display.println(lastCommandId);
    display.print("BypT/F/H/C: ");
    display.print(bypassTemp ? "1" : "0");
    display.print("/");
    display.print(bypassFan ? "1" : "0");
    display.print("/");
    display.print(bypassHeater ? "1" : "0");
    display.print("/");
    display.println(bypassCurrent ? "1" : "0");
    display.print("SysOff: "); display.println(systemOff ? "ON" : "OFF");
#else
    Serial.println("--- CONTROL ---");
    Serial.printf("Última Acción: %s\n", lastAction.c_str());
    Serial.printf("Comando ID: %d\n", lastCommandId);
    Serial.printf("Raw: %s\n", lastRawCommand.c_str());
    Serial.println("Bypass Flags:");
    Serial.printf("  - Temperatura: %s\n", bypassTemp ? "SI" : "NO");
    Serial.printf("  - Ventilador: %s\n", bypassFan ? "SI" : "NO");
    Serial.printf("  - Calentador: %s\n", bypassHeater ? "SI" : "NO");
    Serial.printf("  - Corriente: %s\n", bypassCurrent ? "SI" : "NO");
    Serial.printf("System OFF: %s\n", systemOff ? "ACTIVO" : "NO");
#endif
  } else if (displayPage == 3) {
    // Pagina 4: INFO SISTEMA
#if ENABLE_OLED
    display.println("BIOMASA - INFO");
    display.println("==================");
    display.println("6 Celdas Peltier");
    display.println("2 ramas x 3 series");
    display.print("Tiempo activo: ");
    if (generationOn) {
      unsigned long secs = (millis() - generationStartTime) / 1000;
      display.println(String(secs) + "s");
    } else {
      display.println("0s");
    }
    display.print("Temp Ref: 40C fan");
    display.print(" 35C apag");
#else
    Serial.println("--- INFO SISTEMA ---");
    Serial.println("Configuración Peltier: 6 celdas");
    Serial.println("Topología: 2 ramas x 3 en serie");
    Serial.printf("Voltaje nominal: %.1f V\n", PELTIER_NOMINAL_VOLTAGE);
    Serial.printf("Corriente nominal: %.1f A\n", PELTIER_NOMINAL_CURRENT);
    Serial.printf("Eficiencia Seebeck: %.1f%%\n", SEEBECK_EFFICIENCY * 100);
    if (generationOn) {
      unsigned long secs = (millis() - generationStartTime) / 1000;
      Serial.printf("Tiempo activo: %lu segundos\n", secs);
    } else {
      Serial.println("Tiempo activo: 0s (detenido)");
    }
    Serial.printf("Umbrales: Fan ON=%.1f°C, Fan OFF=%.1f°C\n", FAN_ON_T, FAN_OFF_T);
#endif
  } else if (displayPage == 4) {
    // Pagina 5: HISTORICO DE ACCIONES
#if ENABLE_OLED
    display.println("ACCIONES USUARIO");
    display.println("==================");
    if (eventCount == 0) {
      display.println("Sin eventos");
    } else {
      uint8_t maxDisplay = (eventCount > 4) ? 4 : eventCount;
      for (uint8_t i = 0; i < maxDisplay; i++) {
        unsigned long elapsed = (millis() - eventLog[i].timestamp) / 1000;
        String timeStr = (elapsed < 60) ? String(elapsed) + "s" : String(elapsed / 60) + "m";
        String action = eventLog[i].action;
        if (action.length() > 10) action = action.substring(0, 10);
        display.print(action);
        display.print(" ");
        if (eventLog[i].details.length() > 0) {
          String details = eventLog[i].details;
          if (details.length() > 8) details = details.substring(0, 8);
          display.print(details);
          display.print(" ");
        }
        display.println(timeStr);
      }
    }
#else
    Serial.println("--- ACCIONES USUARIO ---");
    if (eventCount == 0) {
      Serial.println("Sin eventos registrados");
    } else {
      for (uint8_t i = 0; i < eventCount && i < 6; i++) {
        unsigned long elapsed = (millis() - eventLog[i].timestamp) / 1000;
        Serial.printf("%d. [%lus] %s", i+1, elapsed, eventLog[i].action.c_str());
        if (eventLog[i].details.length() > 0) {
          Serial.printf(" - %s", eventLog[i].details.c_str());
        }
        Serial.println();
      }
    }
#endif
  }

#if ENABLE_OLED
  // Indicador pagina
  display.setCursor(110, 56);
  display.print(displayPage + 1);
  display.print("/");
  display.print(DISPLAY_PAGE_COUNT);
  display.display();
#endif
}

// ============================================
// SETUP
// ============================================
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n\n" + String("=").substring(0, 50));
  Serial.println("===  BIOMASA TEST MODE - ESP32-S3 CAM  ===");
  Serial.println(String("=").substring(0, 50));
  Serial.println();
  
#if TEST_MODE
  Serial.println("[MODE] TEST MODE ACTIVO");
  Serial.println("[MODE] Usando valores SIMULADOS (sin sensores)");
  Serial.println("[MODE] Pines GPIO no se activarán físicamente");
  Serial.println();
#endif

  // Pines (solo configurar en modo no-test)
#if !TEST_MODE
  pinMode(PTC_CELDA_PIN, OUTPUT);
  pinMode(FAN_PIN, OUTPUT);
  digitalWrite(PTC_CELDA_PIN, LOW);
  digitalWrite(FAN_PIN, LOW);
  Serial.println("[INIT] GPIO configurados");
#else
  Serial.println("[INIT] GPIO skipped (TEST_MODE)");
#endif

  // I2C for OLED (opcional)
#if ENABLE_OLED
  Wire.begin(SDL, SCL);
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("[WARN] SSD1306 no detectado (continuando sin OLED)");
  } else {
    Serial.println("[INIT] OLED OK");
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(0, 0);
    display.println("BIOMASA TEST");
    display.println("ESP32-S3 CAM");
    display.display();
  }
#else
  Serial.println("[INIT] OLED deshabilitado (usando solo Serial)");
#endif

  // Inicializar generador aleatorio
  randomSeed(analogRead(0));
  Serial.println("[INIT] Generador aleatorio inicializado");

  // WiFi
  Serial.println();
  Serial.printf("[WIFI] Conectando a: %s\n", WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  unsigned long start = millis();
  const unsigned long WIFI_TIMEOUT = 20000;  // 20 segundos para ESP32
  int dots = 0;
  while (WiFi.status() != WL_CONNECTED && (millis() - start) < WIFI_TIMEOUT) {
    delay(500);
    Serial.print('.');
    dots++;
    if (dots % 40 == 0) Serial.println();
  }
  Serial.println();
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[WIFI] ✓ CONECTADO");
    Serial.printf("[WIFI] IP Local: %s\n", WiFi.localIP().toString().c_str());
    Serial.printf("[WIFI] Gateway: %s\n", WiFi.gatewayIP().toString().c_str());
    Serial.printf("[WIFI] RSSI: %d dBm\n", WiFi.RSSI());
    Serial.printf("[WIFI] MAC: %s\n", WiFi.macAddress().c_str());
  } else {
    Serial.println("[WIFI] ✗ FALLO al conectar");
    Serial.println("[WIFI] Iniciando AP fallback...");
    const char* apName = "BIOMASA_TEST_AP";
    WiFi.softAP(apName, "12345678");
    IPAddress myIP = WiFi.softAPIP();
    Serial.printf("[WIFI] AP IP: %s\n", myIP.toString().c_str());
    Serial.printf("[WIFI] AP Name: %s\n", apName);
  }

  Serial.println();
  Serial.printf("[SERVER] Host: %s:%d\n", SERVER_HOST, SERVER_PORT);
  Serial.printf("[SERVER] API Endpoint: %s\n", API_BIORES);
  Serial.println();

  // Lectura inicial simulada
  currentTemp = readTemperatureSensor();
  currentCurrent = readCurrentSensor();
  applyOutputs();
  
  // Log evento inicial
  logEvent("SISTEMA", "Iniciado");
  
  // Mostrar estado inicial
  Serial.println("\n" + String("=").substring(0, 50));
  Serial.println("===     SISTEMA LISTO PARA PRUEBAS      ===");
  Serial.println(String("=").substring(0, 50));
  Serial.println();
  Serial.println("Esperando comandos del servidor...");
  Serial.println("Abre biores.php y haz clic en INICIAR");
  Serial.println();
}

// ============================================
// LOOP PRINCIPAL
// ============================================
void loop() {
  unsigned long now = millis();

  // Consultar comando
  if (WiFi.status() == WL_CONNECTED && now - lastCommandPoll >= COMMAND_POLL_INTERVAL) {
    lastCommandPoll = now;
    pollCommandFromServer();
  }

  // Leer sensores (simulados en TEST_MODE)
  if (now - lastTempRequest >= TEMP_INTERVAL) {
    lastTempRequest = now;

    if (!bypassTemp && !killTemp && !systemOff) {
      currentTemp = readTemperatureSensor();
    }

    // Control de ventilador basado en temperatura (BIOMASA: 40°C encendido)
    if (!bypassFan && !killFan && !systemOff && !isnan(currentTemp)) {
      if (!fanOn && currentTemp >= FAN_ON_T) {
        fanOn = true;
        Serial.printf("[FAN] ✓ Encendido (%.1f°C)\n", currentTemp);
        logEvent("VENTILADOR ON", String(currentTemp, 1) + "C");
      } else if (fanOn && currentTemp <= FAN_OFF_T) {
        fanOn = false;
        Serial.printf("[FAN] ✗ Apagado (%.1f°C)\n", currentTemp);
        logEvent("VENTILADOR OFF", String(currentTemp, 1) + "C");
      }
    }

    applyOutputs();
  }

  // Enviar estado al servidor
  if (WiFi.status() == WL_CONNECTED && now - lastServerStatus >= SERVER_STATUS_INTERVAL) {
    lastServerStatus = now;
    sendStatusToServer();
  }
  
  delay(10);
}
