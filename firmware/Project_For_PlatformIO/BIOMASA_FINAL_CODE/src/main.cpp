#include <Arduino.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

#ifdef ESP8266
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#else
#include <WiFi.h>
#include <HTTPClient.h>
#endif

// PINES DE LA PANTALLA OLED INTEGRADA EN LA PLACA
// Segun pinout ESP8266MOD OLED: SDA=D6(GPIO12), SCL=D5(GPIO14)
#define OLED_SDA D6  // GPIO12 - Dato I2C  (marcado "OLED SDA" en la placa)
#define OLED_SCL D5  // GPIO14 - Reloj I2C (marcado "OLED SDL" en la placa)
// PINES DE SENSORES Y ACTUADORES
#define TEMP_SENSOR_PIN D2 // Pin del sensor de temperatura (OneWire)
#define PTC_CELDA_PIN D7   // Pin de la celda PTC (activa generación)
#define FAN_PIN D8         // Pin del ventilador

// OLED
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// OneWire + Dallas
OneWire oneWire(TEMP_SENSOR_PIN);
DallasTemperature sensors(&oneWire);

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

// ============================================
// ESTADO DE COMPONENTES (DIAGNOSTICO)
// ============================================
struct ComponentStatus {
  bool oledOk;
  bool tempSensorOk;
  bool wifiOk;
  bool currentSensorOk;
  uint8_t tempSensorCount;    // Cantidad de sensores DS18B20 detectados
  int wifiRSSI;               // Intensidad de senal WiFi
  unsigned long lastTempOk;   // Ultimo millis() con lectura valida de temp
  unsigned long lastCurrentOk;// Ultimo millis() con lectura valida de corriente
  uint16_t tempFailCount;     // Lecturas fallidas consecutivas de temp
  uint16_t totalErrors;       // Errores totales acumulados
};

ComponentStatus compStatus = {false, false, false, false, 0, 0, 0, 0, 0, 0};

const unsigned long HEALTH_CHECK_INTERVAL = 5000; // Verificar salud cada 5s
unsigned long lastHealthCheck = 0;
unsigned long lastTempReinit = 0;            // Ultima reinicializacion de sensor temp
const unsigned long TEMP_REINIT_INTERVAL = 15000; // Reintentar bus DS18B20 cada 15s
uint8_t consecutiveHttpErrors = 0;           // Errores HTTP consecutivos
const uint8_t HTTP_ERR_RECONNECT_THRESHOLD = 5; // Reconectar WiFi tras 5 fallos HTTP

// Estado remoto (control.json)
bool bypassTemp = false;
bool bypassFan = false;
bool bypassHeater = false;
bool bypassCurrent = false;
bool systemOff = false;
int lastCommandId = -1;
bool firstSyncDone = false; // Evita ejecutar comandos residuales del servidor al arrancar
String lastAction = "none";
String lastRawCommand = "none";

// HISTERESIS PARA VENTILADOR (BIOMASA)
const float FAN_ON_T = 40.0;   // Encender ventilador a 40°C (BIOMASA)
const float FAN_OFF_T = 35.0;  // Apagar ventilador a 35°C

unsigned long lastTempRequest = 0;
const unsigned long TEMP_INTERVAL = 2000;              // Leer temp cada 2s
const unsigned long SERVER_SYNC_INTERVAL = 3000;       // Sincronizar servidor (envio+recepcion) cada 3s
const unsigned long DISPLAY_PAGE_INTERVAL = 3000;      // Rotacion OLED cada 3s
const unsigned long DISPLAY_REFRESH_INTERVAL = 500;    // Refrescar OLED cada 500ms
const unsigned long GENERATION_MONITOR_INTERVAL = 10000; // Monitor salud generacion cada 10s
const unsigned long WAIT_PRINT_INTERVAL = 30000;       // Imprimir "esperando" max cada 30s
unsigned long lastServerSync = 0;
unsigned long lastDisplaySwitch = 0;
unsigned long lastGenMonitor = 0;
unsigned long _lastWaitPrint = 0;
uint8_t displayPage = 0;
const uint8_t DISPLAY_PAGE_COUNT = 6;  // 6 paginas incluyendo diagnostico
unsigned long lastDisplayRefresh = 0;

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

// Tracking HTTP para monitoreo OLED
String httpRemoteStatus = "---";
String httpLocalStatus = "---";
unsigned int httpOkCount = 0;
unsigned int httpErrCount = 0;
unsigned long lastHttpOk = 0;

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

// --- CONFIGURACION DE RED ---
const char* WIFI_SSID = "TALLER MECATRONICA";
const char* WIFI_PASS = "@MECA2025.ITM";

// Servidor LOCAL (XAMPP) - unico servidor activo
// InfinityFree desactivado temporalmente
const char* SERVER_HOST_LOCAL = "192.168.0.120"; // Cambiar por IP local del servidor XAMPP
const int SERVER_PORT_LOCAL = 80;

// Endpoints API
const char* API_BIORES = "/PRERMI/BIOMASA/sensores_estado.php";
const char* API_ENERGY = "/PRERMI/BIOMASA/registrar_energia.php";
const char* API_TOKEN = "";

String buildURL(const char* host, int port, const char* endpoint) {
  String url = "http://" + String(host);
  if (port != 80) url += ":" + String(port);
  url += endpoint;
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
float calculateEnergyGenerated(unsigned long elapsedMs, float temperatureDiff) {
  // Energia = P * t
  // P (Potencia) = PELTIER_CELLS * PELTIER_NOMINAL_VOLTAGE * PELTIER_NOMINAL_CURRENT * SEEBECK_EFFICIENCY
  // Ajustada por diferencial de temperatura y factor de residuos
  
  if (elapsedMs == 0) return 0.0;
  
  float basePower = PELTIER_CELLS * PELTIER_NOMINAL_VOLTAGE * PELTIER_NOMINAL_CURRENT * SEEBECK_EFFICIENCY;
  float tempFactor = 1.0 + ((temperatureDiff - 30.0) / 100.0); // Aumenta con temp
  float adjustedPower = basePower * tempFactor * RESIDUE_HEAT_MULTIPLIER;
  
  float energyWh = (adjustedPower * (float)elapsedMs) / (1000.0 * 3600.0);
  return energyWh;
}

// ============================================
// LECTURA DE CORRIENTE (SENSOR ANALOGICO)
// ============================================
float readCurrentSensor() {
  // Leer ADC (0-1023) y convertir a Amperios
  // Suponiendo rango 0-5V = 0-30A (sensor típico ACS712)
  int rawValue = analogRead(A0);
  float voltage = (rawValue / 1023.0) * 5.0;
  float current = (voltage - 2.5) / 0.185; // Offset 2.5V, sensibilidad 185mV/A
  if (current < 0) current = 0.0;

  // Verificar si el sensor de corriente esta conectado
  // Un valor ADC de 0 o 1023 constante indica desconexion
  compStatus.currentSensorOk = (rawValue > 5 && rawValue < 1018);
  if (compStatus.currentSensorOk) {
    compStatus.lastCurrentOk = millis();
  }

  return current;
}

// ============================================
// VERIFICACION DE SALUD DE COMPONENTES
// ============================================
void checkComponentHealth() {
  // WiFi
  compStatus.wifiOk = (WiFi.status() == WL_CONNECTED);
  if (compStatus.wifiOk) {
    compStatus.wifiRSSI = WiFi.RSSI();
  } else {
    compStatus.wifiRSSI = 0;
  }

  // Sensor de temperatura: verificar si responde
  compStatus.tempSensorCount = sensors.getDeviceCount();
  if (compStatus.tempSensorCount > 0 && !isnan(currentTemp) && currentTemp > -50.0 && currentTemp < 150.0) {
    compStatus.tempSensorOk = true;
    compStatus.tempFailCount = 0;
    compStatus.lastTempOk = millis();
  } else {
    compStatus.tempFailCount++;
    if (compStatus.tempFailCount >= 3) {
      compStatus.tempSensorOk = false;
      compStatus.totalErrors++;
    }
    // Reintentar inicializacion del bus OneWire cada TEMP_REINIT_INTERVAL
    if (millis() - lastTempReinit >= TEMP_REINIT_INTERVAL) {
      lastTempReinit = millis();
      sensors.begin();
      compStatus.tempSensorCount = sensors.getDeviceCount();
      if (compStatus.tempSensorCount > 0) {
        compStatus.tempSensorOk = true;
        compStatus.tempFailCount = 0;
        Serial.println("[TEMP] Sensor DS18B20 reinicializado OK");
        logEvent("TEMP REINIT", "OK");
      } else {
        Serial.println("[TEMP] Reinicio fallido - verificar cableado y resistencia pull-up 4.7k");
      }
    }
  }

  // Sensor de corriente: revisar si tuvo lectura reciente valida
  if (millis() - compStatus.lastCurrentOk > 10000 && generationOn) {
    compStatus.currentSensorOk = false;
  }
}

// ============================================
// BARRA DE ESTADO INFERIOR (ICONOS)
// ============================================
void drawStatusBar() {
  // Linea separadora
  display.drawLine(0, 54, 127, 54, SSD1306_WHITE);

  int x = 0;
  int y = 56;

  // WiFi: W con check/cross
  display.setCursor(x, y);
  if (compStatus.wifiOk) {
    display.print("W");
    // Barras de senal
    int bars = 0;
    if (compStatus.wifiRSSI > -50) bars = 4;
    else if (compStatus.wifiRSSI > -60) bars = 3;
    else if (compStatus.wifiRSSI > -70) bars = 2;
    else bars = 1;
    for (int i = 0; i < bars; i++) {
      display.drawPixel(x + 8 + i * 2, y + 6 - i, SSD1306_WHITE);
      display.drawPixel(x + 8 + i * 2, y + 7 - i, SSD1306_WHITE);
    }
  } else {
    display.print("W");
    display.drawLine(x + 7, y, x + 14, y + 7, SSD1306_WHITE); // X
    display.drawLine(x + 14, y, x + 7, y + 7, SSD1306_WHITE);
  }

  // Temp sensor: T
  x = 22;
  display.setCursor(x, y);
  display.print("T");
  display.setCursor(x + 7, y);
  display.print(compStatus.tempSensorOk ? "+" : "x");

  // Corriente sensor: A
  x = 40;
  display.setCursor(x, y);
  display.print("A");
  display.setCursor(x + 7, y);
  display.print(compStatus.currentSensorOk ? "+" : "x");

  // HTTP status
  x = 58;
  display.setCursor(x, y);
  if (lastHttpOk > 0 && (millis() - lastHttpOk) < 15000) {
    display.print("H+");
  } else {
    display.print("Hx");
  }

  // Generacion activa
  x = 76;
  display.setCursor(x, y);
  if (generationOn) {
    display.print("GEN");
  } else if (systemOff) {
    display.print("OFF");
  } else {
    display.print("SBY");
  }

  // Pagina actual
  display.setCursor(110, y);
  display.print(displayPage + 1);
  display.print("/");
  display.print(DISPLAY_PAGE_COUNT);
}

// ============================================
// PANTALLA DE BOOT / DIAGNOSTICO
// ============================================
void drawBootScreen() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println("  BIOMASA v2.0");
  display.println("  Diagnostico");
  display.println("----------------");

  // OLED
  display.print("[+] Pantalla OLED");
  display.println();

  // DS18B20
  sensors.begin();
  compStatus.tempSensorCount = sensors.getDeviceCount();
  compStatus.tempSensorOk = (compStatus.tempSensorCount > 0);
  display.print(compStatus.tempSensorOk ? "[+]" : "[X]");
  display.print(" DS18B20 (");
  display.print(compStatus.tempSensorCount);
  display.println(")");

  // WiFi
  display.print(compStatus.wifiOk ? "[+]" : "[X]");
  display.println(" WiFi");

  // Sensor corriente
  int rawADC = analogRead(A0);
  compStatus.currentSensorOk = (rawADC > 5 && rawADC < 1018);
  display.print(compStatus.currentSensorOk ? "[+]" : "[~]");
  display.println(" Sensor corriente");

  // Resumen
  uint8_t okCount = (compStatus.oledOk ? 1 : 0) + (compStatus.tempSensorOk ? 1 : 0) +
                    (compStatus.wifiOk ? 1 : 0) + (compStatus.currentSensorOk ? 1 : 0);
  display.println();
  display.print("Sistema: ");
  display.print(okCount);
  display.print("/4 OK");

  display.display();
  delay(3000); // Mostrar diagnostico 3 segundos
}

void applyOutputs() {
  digitalWrite(PTC_CELDA_PIN, generationOn ? HIGH : LOW);
  digitalWrite(FAN_PIN, fanOn ? HIGH : LOW);
}

// Declaracion adelantada — definicion completa mas abajo
void registrarCicloEnergia(float energia_wh, unsigned long duracion_seg, float temp_prom, float corriente_prom);

void processRemoteCommand(const String &payload) {
  // Soporta formato ESP-friendly (accion/raw) y formato web-UI (last_command/kill_*)
  String accion = jsonValue(payload, "accion");
  if (accion.length() == 0) accion = jsonValue(payload, "last_command");
  String raw = jsonValue(payload, "raw");
  int commandId = jsonInt(payload, "command_id", lastCommandId);

  // Primera sincronizacion: capturar command_id del servidor como linea base
  // para evitar que comandos de sesiones anteriores activen la PTC al arrancar.
  if (!firstSyncDone) {
    firstSyncDone = true;
    lastCommandId = commandId;
    Serial.printf("[INIT] Baseline command_id=%d capturado. PTC/FAN permanecen OFF.\n", commandId);
    applyOutputs(); // Garantizar salidas a 0
    return;
  }

  if (accion.length() > 0) lastAction = accion;
  if (raw.length() > 0) lastRawCommand = raw;

  bypassTemp = jsonBool(payload, "bypass_temp", jsonBool(payload, "kill_temp", bypassTemp));
  bypassFan = jsonBool(payload, "bypass_fan", jsonBool(payload, "kill_fan", bypassFan));
  bypassHeater = jsonBool(payload, "bypass_heater", jsonBool(payload, "kill_heater", bypassHeater));
  bypassCurrent = jsonBool(payload, "bypass_current", jsonBool(payload, "kill_current", bypassCurrent));
  systemOff = jsonBool(payload, "system_off", systemOff);

  if (commandId != lastCommandId) {
    lastCommandId = commandId;

    // Banner claro en Serial cuando llega un nuevo comando desde la web
    Serial.println();
    Serial.println("==============================================");
    Serial.print(">>> COMANDO WEB [ID:");
    Serial.print(commandId);
    Serial.print("] accion=");
    Serial.println(accion.length() > 0 ? accion : raw);
    Serial.println("==============================================");

    if (accion == "start" || accion == "start_generacion" || raw == "start_generacion") {
      if (!generationOn) {
        generationOn = true;
        generationStartTime = millis();
        lastGenerationStartTime = generationStartTime;
        Serial.println("[CMD] START GENERACION - Celdas PTC activadas");
        logEvent("INICIO", "PTC activadas");
      }
    } else if (accion == "stop" || accion == "stop_generacion" || raw == "stop_generacion") {
      if (generationOn) {
        // Calcular energia antes de parar
        unsigned long elapsedMs = millis() - generationStartTime;
        unsigned long elapsedSeg = elapsedMs / 1000;
        float energyThisCycle = calculateEnergyGenerated(elapsedMs, currentTemp);
        totalEnergyGenerated += energyThisCycle;
        Serial.printf("[ENERGY] Ciclo finalizado: %.4f Wh en %lus. Total acum: %.4f Wh\n",
                      energyThisCycle, elapsedSeg, totalEnergyGenerated);
        logEvent("PARADA", String(energyThisCycle, 2) + "Wh");
        // Registrar ciclo en servidor
        registrarCicloEnergia(energyThisCycle, elapsedSeg, currentTemp, currentCurrent);
      }
      generationOn = false;
      fanOn = false; // Apagar ventilador al detener generacion
      Serial.println("[CMD] STOP GENERACION");
    } else if (accion == "emergency" || accion == "emergency_off" || raw == "emergency_off") {
      if (generationOn) {
        unsigned long elapsedMs = millis() - generationStartTime;
        unsigned long elapsedSeg = elapsedMs / 1000;
        float energyThisCycle = calculateEnergyGenerated(elapsedMs, currentTemp);
        totalEnergyGenerated += energyThisCycle;
        Serial.printf("[ENERGY] Ciclo (emergencia): %.4f Wh en %lus. Total acum: %.4f Wh\n",
                      energyThisCycle, elapsedSeg, totalEnergyGenerated);
        logEvent("EMERGEN.", String(energyThisCycle, 2) + "Wh");
        registrarCicloEnergia(energyThisCycle, elapsedSeg, currentTemp, currentCurrent);
      }
      generationOn = false;
      fanOn = false;
      Serial.println("[EMERGENCY] Sistema detenido por EMERGENCIA");
      logEvent("EMERGENCIA", "Sistema OFF");
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
    fanOn = false; // Apagar ventilador si PTC fue desactivada por bypass
    logEvent("BYPASS", "Calentador");
  }

  applyOutputs();
}

// ============================================
// REGISTRAR CICLO DE ENERGIA AL SERVIDOR
// ============================================
void registrarCicloEnergia(float energia_wh, unsigned long duracion_seg, float temp_prom, float corriente_prom) {
  if (WiFi.status() != WL_CONNECTED || energia_wh <= 0) return;

  String params = "?esp=1&accion=registrar";
  params += "&energia_wh=" + String(energia_wh, 4);
  params += "&duracion_seg=" + String(duracion_seg);
  if (!isnan(temp_prom) && temp_prom > -50)
    params += "&temp_promedio=" + String(temp_prom, 2);
  if (corriente_prom > 0)
    params += "&corriente_promedio=" + String(corriente_prom, 3);
  params += "&timestamp_inicio=" + String(generationStartTime / 1000);

  String url = buildURL(SERVER_HOST_LOCAL, SERVER_PORT_LOCAL, API_ENERGY) + params;
  WiFiClient wc;
  HTTPClient http;
  http.begin(wc, url);
  http.setTimeout(5000);
  int code = http.GET();
  if (code == 200) {
    Serial.println("[ENERGIA] Ciclo registrado en servidor OK");
  } else {
    Serial.print("[ENERGIA] Error al registrar ciclo: HTTP ");
    Serial.println(code);
  }
  http.end();
}
// ============================================
void printGenerationHealth() {
  if (!generationOn) return;

  bool tempOk = compStatus.tempSensorOk && !isnan(currentTemp);
  bool fanExpected = !isnan(currentTemp) && currentTemp >= FAN_ON_T;

  unsigned long elapsed = (millis() - generationStartTime) / 1000;
  float energyNow = 0.0;
  if (!isnan(currentTemp)) {
    energyNow = calculateEnergyGenerated(millis() - generationStartTime, currentTemp);
  }

  Serial.print("[MONITOR] PTC:");
  Serial.print(digitalRead(PTC_CELDA_PIN) ? "ON" : "!OFF!");
  Serial.print(" | Temp:");
  if (tempOk) {
    Serial.print(currentTemp, 1);
    Serial.print("C");
  } else {
    Serial.print("!ERROR!");
  }
  Serial.print(" | FAN:");
  Serial.print(fanOn ? "ON" : "OFF");
  Serial.print(" | Corriente:");
  if (compStatus.currentSensorOk) {
    Serial.print(currentCurrent, 2);
    Serial.print("A");
  } else {
    Serial.print("!SENSOR?");
  }
  Serial.print(" | Energia:");
  Serial.print(totalEnergyGenerated + energyNow, 2);
  Serial.print("Wh | T:");
  if (elapsed >= 3600) {
    Serial.print(elapsed / 3600); Serial.print("h");
    Serial.print((elapsed % 3600) / 60); Serial.print("m");
  } else if (elapsed >= 60) {
    Serial.print(elapsed / 60); Serial.print("m");
    Serial.print(elapsed % 60); Serial.print("s");
  } else {
    Serial.print(elapsed); Serial.print("s");
  }
  Serial.println();

  // Alertas de fallo activo
  if (!tempOk)
    Serial.println("[ALERTA] Sensor temperatura sin respuesta!");
  if (fanExpected && !fanOn)
    Serial.println("[ALERTA] Temp alta pero ventilador apagado!");
  if (!digitalRead(PTC_CELDA_PIN))
    Serial.println("[ALERTA] Celda PTC deberia estar activa!");
}

// ============================================
// SINCRONIZACION CON SERVIDOR (envio + recepcion en una sola peticion)
// ============================================
void syncWithServer() {
  if (WiFi.status() != WL_CONNECTED) return;

  // Leer corriente si generacion activa
  if (generationOn && !bypassCurrent) {
    currentCurrent = readCurrentSensor();
  }

  // Energia acumulada en este ciclo
  float energyRate = 0.0;
  if (generationOn && !isnan(currentTemp)) {
    energyRate = calculateEnergyGenerated(millis() - generationStartTime, currentTemp);
  }

  // Construir URL con datos de sensores (GET con ?esp=1 + parametros)
  String params = "?esp=1";
  params += "&temperatura=" + String(isnan(currentTemp) ? 0.0f : currentTemp, 2);
  params += "&corriente=" + String(bypassCurrent ? 0.0f : currentCurrent, 2);
  params += "&ventilador=" + String(fanOn ? 1 : 0);
  params += "&calentador=" + String(generationOn ? 1 : 0);
  params += "&energia_generada=" + String(totalEnergyGenerated + energyRate, 2);
  params += "&sistema_activo=" + String(generationOn ? 1 : 0);
  if (String(API_TOKEN).length() > 0) params += "&token=" + String(API_TOKEN);

  String url = buildURL(SERVER_HOST_LOCAL, SERVER_PORT_LOCAL, API_BIORES) + params;

  WiFiClient wifiClient;
  HTTPClient http;
  http.begin(wifiClient, url);
  http.setTimeout(5000);

  int prevCommandId = lastCommandId;
  int code = http.GET();

  if (code == 200) {
    String payload = http.getString();
    processRemoteCommand(payload);
    httpLocalStatus = "OK";
    httpRemoteStatus = "OFF";
    httpOkCount++;
    consecutiveHttpErrors = 0; // Resetear contador de errores al recibir OK
    lastHttpOk = millis();

    // Solo imprimir si NO hay nuevo comando (el banner ya lo imprime processRemoteCommand)
    if (lastCommandId == prevCommandId) {
      // En generacion el monitor cada 10s es suficiente; en idle, mensaje periodico
      if (!generationOn && (millis() - _lastWaitPrint >= WAIT_PRINT_INTERVAL)) {
        Serial.println("[SERVER] En espera de comandos...");
        _lastWaitPrint = millis();
      }
    }
  } else {
    httpLocalStatus = "E:" + String(code);
    httpRemoteStatus = "OFF";
    httpErrCount++;
    consecutiveHttpErrors++;
    Serial.print("[SERVER] Error HTTP: ");
    Serial.print(code);
    if (code == -1) Serial.print(" (conexion fallida - verificar IP/XAMPP)");
    Serial.println();
    // Si acumula varios fallos consecutivos, forzar reconexion WiFi
    if (consecutiveHttpErrors >= HTTP_ERR_RECONNECT_THRESHOLD) {
      consecutiveHttpErrors = 0;
      Serial.println("[WIFI] Reconectando por fallos HTTP consecutivos...");
      WiFi.disconnect();
      delay(300);
      WiFi.begin(WIFI_SSID, WIFI_PASS);
      logEvent("WIFI RECONECT", "HTTP-" + String(HTTP_ERR_RECONNECT_THRESHOLD));
    }
  }
  http.end();
}

// ============================================
// DIBUJAR ESTADO EN OLED
// ============================================
void drawStatusOnOLED() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);

  if (displayPage == 0) {
    // Pagina 1: SENSORES
    display.println("BIOMASA - SENSORES");
    display.drawLine(0, 9, 127, 9, SSD1306_WHITE);
    display.setCursor(0, 12);
    display.print("Temp: ");
    if (!compStatus.tempSensorOk) {
      display.println("!SENSOR!");
    } else if (isnan(currentTemp) || bypassTemp) {
      display.println("-- C");
    } else {
      display.println(String(currentTemp, 1) + " C");
    }
    display.print("Corriente: ");
    if (!compStatus.currentSensorOk && generationOn) {
      display.println("!SENSOR!");
    } else if (bypassCurrent) {
      display.println("-- A");
    } else {
      display.println(String(currentCurrent, 2) + " A");
    }
    display.print("PTC: "); display.print(generationOn ? "ON " : "OFF");
    display.print(" FAN: "); display.println(fanOn ? "ON" : "OFF");
    display.print("Energia: ");
    if (generationOn && !isnan(currentTemp)) {
      unsigned long elapsedMs = millis() - generationStartTime;
      float currentRate = calculateEnergyGenerated(elapsedMs, currentTemp);
      display.println(String(totalEnergyGenerated + currentRate, 2) + "Wh");
    } else {
      display.println(String(totalEnergyGenerated, 2) + " Wh");
    }
  } else if (displayPage == 1) {
    // Pagina 2: RED/HTTP - Monitoreo de conexiones
    display.println("BIOMASA - RED/HTTP");
    display.drawLine(0, 9, 127, 9, SSD1306_WHITE);
    display.setCursor(0, 12);
    display.print("WiFi:");
    if (compStatus.wifiOk) {
      display.print("OK ");
      display.print(compStatus.wifiRSSI);
      display.println("dBm");
    } else {
      display.println("DESCONECTADO!");
    }
    if (compStatus.wifiOk) {
      display.print("IP:"); display.println(WiFi.localIP());
    } else {
      display.println("IP: ---");
    }
    display.print("R:"); display.print(httpRemoteStatus);
    display.print(" L:"); display.println(httpLocalStatus);
    display.print("OK:"); display.print(httpOkCount);
    display.print(" ERR:"); display.println(httpErrCount);
    if (lastHttpOk > 0) {
      unsigned long ago = (millis() - lastHttpOk) / 1000;
      display.print("Sync:"); display.print(ago); display.println("s atras");
    } else {
      display.println("Sync: nunca");
    }
  } else if (displayPage == 2) {
    // Pagina 3: CONTROL
    display.println("BIOMASA - CONTROL");
    display.drawLine(0, 9, 127, 9, SSD1306_WHITE);
    display.setCursor(0, 12);
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
  } else if (displayPage == 3) {
    // Pagina 4: INFO SISTEMA
    display.println("BIOMASA - INFO");
    display.drawLine(0, 9, 127, 9, SSD1306_WHITE);
    display.setCursor(0, 12);
    display.println("6 Celdas Peltier");
    display.println("2 ramas x 3 series");
    display.print("Tiempo activo: ");
    if (generationOn) {
      unsigned long secs = (millis() - generationStartTime) / 1000;
      if (secs >= 3600) {
        display.print(secs / 3600); display.print("h");
        display.print((secs % 3600) / 60); display.println("m");
      } else if (secs >= 60) {
        display.print(secs / 60); display.print("m");
        display.print(secs % 60); display.println("s");
      } else {
        display.println(String(secs) + "s");
      }
    } else {
      display.println("0s");
    }
    display.print("Fan: "); display.print(FAN_ON_T, 0);
    display.print("C on / "); display.print(FAN_OFF_T, 0);
    display.println("C off");
  } else if (displayPage == 4) {
    // Pagina 5: HISTORICO DE ACCIONES
    display.println("ACCIONES USUARIO");
    display.drawLine(0, 9, 127, 9, SSD1306_WHITE);
    display.setCursor(0, 12);
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
  } else if (displayPage == 5) {
    // Pagina 6: DIAGNOSTICO EN VIVO
    display.println("DIAGNOSTICO");
    display.drawLine(0, 9, 127, 9, SSD1306_WHITE);
    display.setCursor(0, 12);

    // OLED
    display.println("[+] OLED OK");

    // Sensor temp
    display.print(compStatus.tempSensorOk ? "[+]" : "[X]");
    display.print(" DS18B20 (");
    display.print(compStatus.tempSensorCount);
    display.println(")");

    // WiFi
    display.print(compStatus.wifiOk ? "[+]" : "[X]");
    display.print(" WiFi ");
    if (compStatus.wifiOk) {
      display.print(compStatus.wifiRSSI);
      display.println("dBm");
    } else {
      display.println("DISC");
    }

    // Corriente
    display.print(compStatus.currentSensorOk ? "[+]" : "[~]");
    display.println(" Corriente");

    // Errores
    display.print("Errores: ");
    display.println(compStatus.totalErrors);
  }

  // Barra de estado inferior en todas las paginas
  drawStatusBar();
  display.display();
}

// ============================================
// SETUP
// ============================================
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n\n=== BIOMASA SYSTEM INIT ===");

  // Pines
  pinMode(PTC_CELDA_PIN, OUTPUT);
  pinMode(FAN_PIN, OUTPUT);
  digitalWrite(PTC_CELDA_PIN, LOW);
  digitalWrite(FAN_PIN, LOW);
  Serial.println("[INIT] GPIO configurados");

  // I2C for OLED (SDA=D6/GPIO12, SCL=D5/GPIO14 segun pinout ESP8266MOD OLED)
  Wire.begin(OLED_SDA, OLED_SCL);
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("ERROR: SSD1306 no detectado");
  } else {
    Serial.println("[INIT] OLED OK");
  }
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println("BIOMASA");
  display.println("Inicializando...");
  display.display();

  // Sensor de temperatura
  sensors.begin();
  compStatus.tempSensorCount = sensors.getDeviceCount();
  compStatus.tempSensorOk = (compStatus.tempSensorCount > 0);
  Serial.print("[INIT] DS18B20: ");
  Serial.print(compStatus.tempSensorCount);
  Serial.println(" sensor(es) detectado(s)");
  if (!compStatus.tempSensorOk) {
    Serial.println("[WARN] No se detecto sensor de temperatura!");
  }

  // WiFi
  Serial.print("Conectando WiFi "); Serial.println(WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  unsigned long start = millis();
  const unsigned long WIFI_TIMEOUT = 15000;
  while (WiFi.status() != WL_CONNECTED && (millis() - start) < WIFI_TIMEOUT) {
    delay(200);
    Serial.print('.');
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println();
    Serial.print("Conectado: "); Serial.println(WiFi.localIP());
    Serial.println("[INIT] WiFi OK");
    compStatus.wifiOk = true;
    compStatus.wifiRSSI = WiFi.RSSI();
  } else {
    Serial.println();
    Serial.println("WiFi STA fallo, iniciando AP fallback");
    const char* apName = "BIOMASA_AP";
    WiFi.softAP(apName);
    IPAddress myIP = WiFi.softAPIP();
    Serial.print("AP IP: "); Serial.println(myIP);
  }

  // Lectura inicial
  sensors.requestTemperatures();
  currentTemp = sensors.getTempCByIndex(0);
  currentCurrent = readCurrentSensor();
  applyOutputs();
  
  // Pantalla de diagnostico inicial
  compStatus.oledOk = true;
  drawBootScreen();
  
  // Iniciar display normal
  drawStatusOnOLED();
  
  Serial.println("=== BIOMASA SYSTEM READY ===\n");
  Serial.printf("[DIAG] OLED:%s TEMP:%s WIFI:%s CURR:%s\n",
    compStatus.oledOk ? "OK" : "FAIL",
    compStatus.tempSensorOk ? "OK" : "FAIL",
    compStatus.wifiOk ? "OK" : "FAIL",
    compStatus.currentSensorOk ? "OK" : "~");
}

// ============================================
// LOOP PRINCIPAL
// ============================================
void loop() {
  unsigned long now = millis();

  // Rotacion de pagina OLED (solo cambia pagina, no dibuja)
  if (now - lastDisplaySwitch >= DISPLAY_PAGE_INTERVAL) {
    lastDisplaySwitch = now;
    displayPage = (displayPage + 1) % DISPLAY_PAGE_COUNT;
  }

  // Consultar comando Y enviar estado en una sola peticion
  if (WiFi.status() == WL_CONNECTED && now - lastServerSync >= SERVER_SYNC_INTERVAL) {
    lastServerSync = now;
    syncWithServer();
  }

  // Leer sensores
  if (now - lastTempRequest >= TEMP_INTERVAL) {
    lastTempRequest = now;

    if (!bypassTemp) {
      sensors.requestTemperatures();
      float t = sensors.getTempCByIndex(0);
      if (t != DEVICE_DISCONNECTED_C) {
        currentTemp = t;
      }
    }

    // Control de ventilador basado en temperatura (solo actua si generacion esta ON)
    if (!bypassFan) {
      if (generationOn && !isnan(currentTemp)) {
        if (!fanOn && currentTemp >= FAN_ON_T) {
          fanOn = true;
          Serial.printf("VENTILADOR ENCENDIDO (%.1f C >= %.1f C)\n", currentTemp, FAN_ON_T);
          logEvent("VENTILADOR ON", String(currentTemp, 1) + "C");
        } else if (fanOn && currentTemp <= FAN_OFF_T) {
          fanOn = false;
          Serial.printf("VENTILADOR APAGADO (%.1f C <= %.1f C)\n", currentTemp, FAN_OFF_T);
          logEvent("VENTILADOR OFF", String(currentTemp, 1) + "C");
        }
      } else if (!generationOn && fanOn) {
        // Generacion detenida: apagar ventilador incondicionalmente
        fanOn = false;
        Serial.println("VENTILADOR APAGADO (generacion detenida)");
        logEvent("VENTILADOR OFF", "GEN OFF");
      }
    }

    applyOutputs();
  }

  // Verificar salud de componentes
  if (now - lastHealthCheck >= HEALTH_CHECK_INTERVAL) {
    lastHealthCheck = now;
    checkComponentHealth();
  }

  // Refrescar OLED constantemente (cada 500ms)
  if (now - lastDisplayRefresh >= DISPLAY_REFRESH_INTERVAL) {
    lastDisplayRefresh = now;
    drawStatusOnOLED();
  }

  // Monitor de salud durante generacion activa (cada 10s)
  if (generationOn && now - lastGenMonitor >= GENERATION_MONITOR_INTERVAL) {
    lastGenMonitor = now;
    printGenerationHealth();
  }
  // Al iniciar idle: resetear timer para que "En espera" aparezca pronto
  if (!generationOn) {
    lastGenMonitor = 0;
  }

  delay(10);
}
