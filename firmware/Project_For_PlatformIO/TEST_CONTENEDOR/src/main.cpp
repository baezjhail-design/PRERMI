#include <Arduino.h>
#include <Wire.h>
#include <Adafruit_SSD1306.h>
#include <ESP32Servo.h>
#include <HX711.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <esp_camera.h>
#include <mbedtls/base64.h>
#include <soc/io_mux_reg.h>

// ===== CONFIGURACION PANTALLA OLED =====
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1
#define SDA_PIN 41
#define SCL_PIN 40

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// ===== CONFIGURACION PINES =====
#define BUTTON_PIN 0
#define SERVO_PIN 2
#define LOAD_CELL_DT 20
#define LOAD_CELL_SCK 21
#define METAL_SENSOR_PIN 45
#define RED_LED_PIN 47
#define GREEN_LED_PIN 48

// ===== CONFIGURACION CAMARA ESP32-S3 CAM =====
// Pines corregidos segun pinout oficial del modulo ESP32-S3 CAM con OV2640.
#define PWDN_GPIO_NUM    -1
#define RESET_GPIO_NUM   -1
#define XCLK_GPIO_NUM     15
#define SIOD_GPIO_NUM      4
#define SIOC_GPIO_NUM      5
#define Y9_GPIO_NUM       16
#define Y8_GPIO_NUM       17
#define Y7_GPIO_NUM       18
#define Y6_GPIO_NUM       12
#define Y5_GPIO_NUM       10
#define Y4_GPIO_NUM        8
#define Y3_GPIO_NUM        9
#define Y2_GPIO_NUM       11
#define VSYNC_GPIO_NUM     6
#define HREF_GPIO_NUM      7
#define PCLK_GPIO_NUM     13

// ===== CONFIGURACION WIFI =====
const char* ssid = "Jhail-WIFI";
const char* password = "123.02589.";
const char* serverAPI = "http://10.0.0.203:8080/PRERMI/api";

// ===== URLs SOLO LOCALHOST (InfinityFree desactivado temporalmente) =====
// Todos los endpoints apuntan directamente al servidor local via serverAPI

// ===== ENDPOINTS LOCALES (se concatenan con serverAPI) =====
const char* ENDPOINT_REGISTRAR_DEPOSITOS = "/contenedores/registrar_depositos.php";
const char* ENDPOINT_REGISTRAR_SANCION = "/sanciones/crear_sancion_auto.php";
const char* ENDPOINT_VERIFICAR_ROSTRO = "/contenedores/verificar_rostro.php";
const char* ENDPOINT_OBTENER_USUARIO = "/usuarios/obtener_usuario.php";

// ===== OBJETOS GLOBALES =====
Servo servo;
HX711 scale;
volatile bool buttonPressed = false;
bool metalDetected = false;
float weightKg = 0.0;

int identifiedUserId = -1;
String identifiedUserName = "Usuario";
bool cameraReady = false;

struct CameraPinEntry {
  const char* label;
  int pin;
  bool optional;
};

// ===== CONFIGURACION CONSTANTES =====
const int DOOR_OPEN_ANGLE = 90;
const int DOOR_CLOSE_ANGLE = 0;
const unsigned long DOOR_OPEN_TIME = 20000;
const int LOAD_CELL_SAMPLES = 100;
const float CALIBRATION_FACTOR = -7050.0;
const float KWH_COST_RD = 5.50;
const float KG_TO_KWH = 0.0011;
// ===== CONTENEDOR FIJO EN DB =====
const int CONTAINER_ID_FIXED = 15;
const char* CONTAINER_CODE_FIXED = "CONT-PRERMI-001";
const char* CONTAINER_API_KEY_FIXED = "PRERMI_KEY_CONT_001_FIXED";
const char* CONTAINER_LOCATION_FIXED = "Santiago de los Caballeros - Zona Centro";
const double CONTAINER_LAT_FIXED = 19.4517;
const double CONTAINER_LON_FIXED = -70.6970;
const char* CONTAINER_TYPE_FIXED = "metal";
const char* CONTAINER_STATUS_FIXED = "activo";
const char* CONTAINER_TOKEN_FIXED = "TOKEN_CONT_001_FIXED";
const char* CONTAINER_TOKEN_GENERATED_AT_FIXED = "2026-03-06 08:00:00";
const char* CONTAINER_TOKEN_EXPIRES_AT_FIXED = "2026-12-31 23:59:59";
const char* CONTAINER_CREATED_AT_FIXED = "2026-03-06 08:00:00";
const char* CONTAINER_UPDATED_AT_FIXED = "2026-03-06 08:00:00";

const unsigned long BLOCK_TIME_MS = 60000;
unsigned long lockedUntilMs = 0;

// ===== INTERRUPCIONES =====
void IRAM_ATTR buttonISR() {
  buttonPressed = true;
}

// ===== FORWARD DECLARATIONS =====
bool sendSanction(int userId, const String& reason);
int httpPostJSON(const char* baseURL, const char* endpoint, const String& jsonBody, const char* apiKey = nullptr);
int httpPostFormIF(const char* fullURL, const String& jsonData, String& outResponse);

// ===== UTILIDADES OLED =====
void showOledLines(const String& l1,
                   const String& l2 = "",
                   const String& l3 = "",
                   const String& l4 = "",
                   uint8_t textSize = 1) {
  display.clearDisplay();
  display.setTextSize(textSize);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(l1);
  if (l2.length()) display.println(l2);
  if (l3.length()) display.println(l3);
  if (l4.length()) display.println(l4);
  display.display();
}

void drawSmileIcon(int centerX, int centerY, int radius) {
  display.drawCircle(centerX, centerY, radius, SSD1306_WHITE);
  display.fillCircle(centerX - 4, centerY - 3, 1, SSD1306_WHITE);
  display.fillCircle(centerX + 4, centerY - 3, 1, SSD1306_WHITE);
  display.drawLine(centerX - 4, centerY + 4, centerX + 4, centerY + 4, SSD1306_WHITE);
}

// ===== INICIALIZACION PANTALLA OLED =====
void initDisplay() {
  Wire.begin(SDA_PIN, SCL_PIN);

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println(F("SSD1306 allocation failed"));
    while (1) {
      delay(1000);
    }
  }

  showOledLines("PRERMI");
  delay(500);
}

// ===== PANTALLA BIENVENIDA =====
void showWelcomeScreen() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("Bienvenido a PRERMI"));
  display.println(F("Contenedor"));
  display.println(F("inteligente"));
  display.println();
  display.println(F("Presione el boton"));
  display.println(F("para continuar"));
  display.display();
}

bool isValidCameraPinGpio(int pin, bool optional) {
  if (optional && pin < 0) {
    return true;
  }

  if (pin < 0 || !GPIO_IS_VALID_GPIO(pin)) {
    return false;
  }

  // Some GPIO numbers do not expose IO_MUX and crash inside ll_cam_set_pin.
  if (GPIO_PIN_MUX_REG[pin] == 0) {
    return false;
  }

  return true;
}

bool validateCameraPinConfig() {
  const CameraPinEntry pins[] = {
    {"PCLK", PCLK_GPIO_NUM, false},
    {"VSYNC", VSYNC_GPIO_NUM, false},
    {"HREF", HREF_GPIO_NUM, false},
    {"XCLK", XCLK_GPIO_NUM, false},
    {"SIOD", SIOD_GPIO_NUM, false},
    {"SIOC", SIOC_GPIO_NUM, false},
    {"Y2", Y2_GPIO_NUM, false},
    {"Y3", Y3_GPIO_NUM, false},
    {"Y4", Y4_GPIO_NUM, false},
    {"Y5", Y5_GPIO_NUM, false},
    {"Y6", Y6_GPIO_NUM, false},
    {"Y7", Y7_GPIO_NUM, false},
    {"Y8", Y8_GPIO_NUM, false},
    {"Y9", Y9_GPIO_NUM, false},
    {"PWDN", PWDN_GPIO_NUM, true},
    {"RESET", RESET_GPIO_NUM, true},
  };

  bool ok = true;

  for (const auto& p : pins) {
    if (!isValidCameraPinGpio(p.pin, p.optional)) {
      Serial.printf("[CAMERA] Pin invalido para %s: GPIO %d\n", p.label, p.pin);
      ok = false;
    }
  }

  // Detect hard conflicts with non-camera peripherals.
  if (Y5_GPIO_NUM == LOAD_CELL_SCK || Y5_GPIO_NUM == LOAD_CELL_DT ||
      PCLK_GPIO_NUM == LOAD_CELL_SCK || PCLK_GPIO_NUM == LOAD_CELL_DT ||
      SIOD_GPIO_NUM == LOAD_CELL_SCK || SIOD_GPIO_NUM == LOAD_CELL_DT ||
      SIOC_GPIO_NUM == LOAD_CELL_SCK || SIOC_GPIO_NUM == LOAD_CELL_DT) {
    Serial.println("[CAMERA] Conflicto de pines: camara y HX711 comparten GPIO.");
    ok = false;
  }

  if (!ok) {
    Serial.println("[CAMERA] Pinout invalido. Corrige el mapeo segun el esquema de tu placa.");
  }

  return ok;
}

// ===== INICIALIZACION CAMARA =====
bool initCamera() {
  if (!validateCameraPinConfig()) {
    return false;
  }

  camera_config_t config = {};
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_sccb_sda = SIOD_GPIO_NUM;
  config.pin_sccb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;

  if (psramFound()) {
    Serial.printf("[CAMERA] PSRAM detectada: %u bytes libres\n", ESP.getFreePsram());
    config.frame_size = FRAMESIZE_XGA;    // 1024x768 - optima para reconocimiento facial
    config.jpeg_quality = 10;             // Buena calidad sin archivos enormes
    config.fb_count = 2;                  // Doble buffer para frames fluidos
    config.grab_mode = CAMERA_GRAB_LATEST;
    config.fb_location = CAMERA_FB_IN_PSRAM;
  } else {
    config.frame_size = FRAMESIZE_SVGA;   // 800x600 sin PSRAM
    config.jpeg_quality = 12;
    config.fb_count = 1;
  }

  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("[CAMERA] Init fallo: 0x%x\n", err);
    return false;
  }

  sensor_t* sensor = esp_camera_sensor_get();
  if (sensor) {
    sensor->set_vflip(sensor, 0);
    sensor->set_brightness(sensor, 2);    // Brillo MAXIMO para ambientes oscuros
    sensor->set_contrast(sensor, 1);      // Contraste moderado (2 puede perder detalles)
    sensor->set_saturation(sensor, 0);    // Saturacion normal
    sensor->set_sharpness(sensor, 1);     // Nitidez moderada (evita artefactos)
    sensor->set_denoise(sensor, 1);       // Reduccion de ruido para imagen mas limpia
    sensor->set_whitebal(sensor, 1);      // Balance de blancos automatico ON
    sensor->set_awb_gain(sensor, 1);      // Ganancia AWB ON
    sensor->set_wb_mode(sensor, 0);       // AWB auto (mejor adaptacion a luz)
    sensor->set_exposure_ctrl(sensor, 1); // Control de exposicion automatico ON
    sensor->set_aec2(sensor, 1);          // AEC DSP ON (mejor exposicion)
    sensor->set_ae_level(sensor, 2);      // Nivel de exposicion alto (+2)
    sensor->set_aec_value(sensor, 800);   // Exposicion manual mas alta (0-1200)
    sensor->set_gain_ctrl(sensor, 1);     // Ganancia automatica ON
    sensor->set_agc_gain(sensor, 20);     // Ganancia AGC mas alta para poca luz
    sensor->set_gainceiling(sensor, (gainceiling_t)6); // Ganancia maxima 128x
    sensor->set_bpc(sensor, 1);           // Correccion pixeles defectuosos ON
    sensor->set_wpc(sensor, 1);           // Correccion pixeles blancos ON
    sensor->set_lenc(sensor, 1);          // Correccion de lente ON
    sensor->set_raw_gma(sensor, 1);       // Correccion gamma ON
    sensor->set_dcw(sensor, 1);           // Downsize enable
    Serial.println("[CAMERA] Sensor configurado: brillo=2, exposicion=800, ganancia=20");
  }

  Serial.println("[CAMERA] Iniciada correctamente");
  return true;
}

String base64EncodeBuffer(const uint8_t* data, size_t len) {
  if (data == nullptr || len == 0) {
    return "";
  }

  size_t outLen = 0;
  int ret = mbedtls_base64_encode(nullptr, 0, &outLen, data, len);
  if (ret != MBEDTLS_ERR_BASE64_BUFFER_TOO_SMALL || outLen == 0) {
    return "";
  }

  // Usar PSRAM para el buffer base64 (imagenes UXGA pueden ser >200KB)
  char* outBuffer;
  if (psramFound()) {
    outBuffer = (char*)ps_malloc(outLen + 1);
  } else {
    outBuffer = new char[outLen + 1];
  }
  if (!outBuffer) {
    Serial.println("[B64] Error asignando memoria para base64");
    return "";
  }

  ret = mbedtls_base64_encode(reinterpret_cast<unsigned char*>(outBuffer), outLen, &outLen, data, len);
  if (ret != 0) {
    free(outBuffer);
    return "";
  }

  outBuffer[outLen] = '\0';
  String encoded(outBuffer);
  free(outBuffer);
  return encoded;
}

bool fetchUserName(int userId, String& outName) {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }

  // Solo localhost activo (InfinityFree desactivado temporalmente)
  String urls[] = {
    String(serverAPI) + ENDPOINT_OBTENER_USUARIO + "?id=" + String(userId)
  };
  const char* labels[] = {"localhost"};

  for (int s = 0; s < 1; s++) {
    HTTPClient http;
    http.begin(urls[s]);
    http.setTimeout(10000);

    int code = http.GET();
    if (code <= 0) {
      Serial.printf("[USER] %s fallo: %d\n", labels[s], code);
      http.end();
      continue;
    }

    String response = http.getString();
    http.end();

    StaticJsonDocument<512> doc;
    DeserializationError err = deserializeJson(doc, response);
    if (err || !doc["success"].as<bool>()) {
      Serial.printf("[USER] %s JSON invalido o error\n", labels[s]);
      continue;
    }

    JsonObject user = doc["user"];
    String nombre = user["nombre"] | "Usuario";
    String apellido = user["apellido"] | "";
    outName = nombre;
    if (apellido.length() > 0) {
      outName += " ";
      outName += apellido;
    }
    Serial.printf("[USER] Nombre obtenido de %s\n", labels[s]);
    return true;
  }
  return false;
}

bool verifyIdentityWithFace(int& outUserId, String& outUserName) {
  outUserId = -1;
  outUserName = "Usuario";

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[FACE] WiFi no conectado");
    return false;
  }

  if (!cameraReady) {
    Serial.println("[FACE] Camara no disponible");
    return false;
  }

  // Cuenta regresiva de 15 segundos para que el usuario se posicione
  Serial.println("[FACE] Iniciando cuenta regresiva de 15s...");
  for (int i = 15; i > 0; i--) {
    display.clearDisplay();
    display.setTextSize(1);
    display.setCursor(0, 0);
    display.println(F("Mire a la camara"));
    display.println(F("Preparando..."));
    display.println();
    display.setTextSize(2);
    display.setCursor(40, 35);
    display.print(i);
    display.setTextSize(1);
    display.print(" s");
    display.display();
    delay(1000);
  }

  showOledLines("Capturando imagen...");

  // Descartar primeros frames para que auto-exposicion se estabilice
  for (int i = 0; i < 8; i++) {
    camera_fb_t* discard = esp_camera_fb_get();
    if (discard) esp_camera_fb_return(discard);
    delay(150);
  }

  camera_fb_t* fb = esp_camera_fb_get();
  if (!fb) {
    Serial.println("[FACE] No se pudo capturar foto");
    return false;
  }

  Serial.printf("[FACE] Imagen capturada: %dx%d, %u bytes\n", fb->width, fb->height, fb->len);

  // Codificar base64 en PSRAM
  size_t b64Len = 0;
  mbedtls_base64_encode(nullptr, 0, &b64Len, fb->buf, fb->len);
  if (b64Len == 0) {
    esp_camera_fb_return(fb);
    Serial.println("[FACE] Error calculando tamano base64");
    return false;
  }

  // Prefijo y sufijo JSON
  const char jsonPrefix[] = "{\"imagen_base64\":\"";
  const char jsonSuffix[] = "\"}";
  size_t prefixLen = strlen(jsonPrefix);
  size_t suffixLen = strlen(jsonSuffix);
  size_t totalLen = prefixLen + b64Len + suffixLen;

  // Asignar todo el buffer JSON en PSRAM
  char* jsonBuf = (char*)ps_malloc(totalLen + 1);
  if (!jsonBuf) {
    esp_camera_fb_return(fb);
    Serial.println("[FACE] Error asignando PSRAM para JSON");
    return false;
  }

  // Escribir prefijo
  memcpy(jsonBuf, jsonPrefix, prefixLen);

  // Codificar base64 directamente en el buffer JSON (en PSRAM)
  size_t actualB64Len = 0;
  int ret = mbedtls_base64_encode(
    reinterpret_cast<unsigned char*>(jsonBuf + prefixLen),
    b64Len, &actualB64Len, fb->buf, fb->len
  );
  esp_camera_fb_return(fb);  // Liberar frame buffer inmediatamente

  if (ret != 0) {
    free(jsonBuf);
    Serial.println("[FACE] Error codificando base64");
    return false;
  }

  // Escribir sufijo
  memcpy(jsonBuf + prefixLen + actualB64Len, jsonSuffix, suffixLen);
  totalLen = prefixLen + actualB64Len + suffixLen;
  jsonBuf[totalLen] = '\0';

  Serial.printf("[FACE] Enviando imagen (%u bytes JSON, desde PSRAM)...\n", totalLen);
  Serial.printf("[FACE] Heap libre: %u, PSRAM libre: %u\n", ESP.getFreeHeap(), ESP.getFreePsram());

  // Intentar enviar solo a localhost (InfinityFree desactivado temporalmente)
  struct ServerTarget {
    const char* host;
    uint16_t port;
    const char* path;
    const char* label;
    bool isHTTPS;
  };
  const ServerTarget targets[] = {
    {"10.0.0.203", 8080, "/PRERMI/api/contenedores/verificar_rostro.php", "localhost", false}
  };

  int code = 0;
  String response = "";
  bool serverConnected = false;

  for (int s = 0; s < 1; s++) {
    Serial.printf("[FACE] Intentando %s (%s:%u)...\n", targets[s].label, targets[s].host, targets[s].port);

    code = 0;
    response = "";

    if (false) {
      // HTTPS block desactivado (InfinityFree en pausa)
      WiFiClientSecure sslClient;
      sslClient.setInsecure();
      sslClient.setTimeout(30000);

      if (!sslClient.connect(targets[s].host, targets[s].port)) {
        Serial.printf("[FACE] No se pudo conectar a %s\n", targets[s].label);
        continue;
      }

      String boundary = "----PRERMI" + String(millis());
      String partHeader = "--" + boundary + "\r\n";
      partHeader += "Content-Disposition: form-data; name=\"data\"\r\n\r\n";
      String partFooter = "\r\n--" + boundary + "--\r\n";
      size_t multipartTotal = partHeader.length() + totalLen + partFooter.length();

      // Unificar todos los headers HTTP en un solo write SSL para evitar
      // multiples registros TLS pequenos que causan CONN_RESET (-0x0050)
      String httpHeaders;
      httpHeaders.reserve(512);
      httpHeaders += "POST ";
      httpHeaders += targets[s].path;
      httpHeaders += " HTTP/1.1\r\n";
      httpHeaders += "Host: ";
      httpHeaders += targets[s].host;
      httpHeaders += "\r\n";
      httpHeaders += "Content-Type: multipart/form-data; boundary=";
      httpHeaders += boundary;
      httpHeaders += "\r\n";
      httpHeaders += "Content-Length: ";
      httpHeaders += String(multipartTotal);
      httpHeaders += "\r\n";
      httpHeaders += "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n";
      httpHeaders += "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
      httpHeaders += "Referer: https://";
      httpHeaders += targets[s].host;
      httpHeaders += "/PRERMI/\r\n";
      httpHeaders += "Connection: close\r\n";
      httpHeaders += "\r\n";
      httpHeaders += partHeader;

      size_t hdrWritten = sslClient.write((const uint8_t*)httpHeaders.c_str(), httpHeaders.length());
      if (hdrWritten == 0) {
        Serial.printf("[FACE] Error enviando headers a %s\n", targets[s].label);
        sslClient.stop();
        continue;
      }

      const size_t CHUNK_SIZE = 4096;
      size_t sent = 0;
      bool sendError = false;
      while (sent < totalLen) {
        if (!sslClient.connected()) {
          Serial.printf("[FACE] Conexion perdida tras %u/%u bytes\n", sent, totalLen);
          sendError = true;
          break;
        }
        size_t toSend = totalLen - sent;
        if (toSend > CHUNK_SIZE) toSend = CHUNK_SIZE;
        size_t written = sslClient.write((const uint8_t*)(jsonBuf + sent), toSend);
        if (written == 0) {
          Serial.printf("[FACE] Write retorno 0 en byte %u/%u\n", sent, totalLen);
          sendError = true;
          break;
        }
        sent += written;
        delay(1);  // Yield al stack TCP para procesar ACKs
      }

      if (sendError) { sslClient.stop(); continue; }
      sslClient.print(partFooter);
      Serial.printf("[FACE] Enviados %u bytes OK a %s\n", sent, targets[s].label);

      unsigned long timeout = millis() + 30000;
      while (!sslClient.available() && millis() < timeout) delay(100);

      code = 0;
      response = "";
      if (sslClient.available()) {
        String statusLine = sslClient.readStringUntil('\n');
        Serial.println("[FACE] " + statusLine);
        int spaceIdx = statusLine.indexOf(' ');
        if (spaceIdx > 0) code = statusLine.substring(spaceIdx + 1, spaceIdx + 4).toInt();
        while (sslClient.available()) {
          String hdr = sslClient.readStringUntil('\n'); hdr.trim();
          if (hdr.length() == 0) break;
        }
        while (sslClient.available()) response += (char)sslClient.read();
      }
      sslClient.stop();

    } else {
      // ===== localhost via HTTP normal =====
      WiFiClient client;
      client.setTimeout(15000);
      if (!client.connect(targets[s].host, targets[s].port)) {
        Serial.printf("[FACE] No se pudo conectar a %s\n", targets[s].label);
        continue;
      }

      client.printf("POST %s HTTP/1.1\r\n", targets[s].path);
      client.printf("Host: %s:%u\r\n", targets[s].host, targets[s].port);
      client.println("Content-Type: application/json; charset=utf-8");
      client.printf("Content-Length: %u\r\n", totalLen);
      client.println("Connection: close");
      client.println();

      const size_t CHUNK_SIZE = 4096;
      size_t sent = 0;
      bool sendError = false;
      while (sent < totalLen) {
        size_t toSend = totalLen - sent;
        if (toSend > CHUNK_SIZE) toSend = CHUNK_SIZE;
        size_t written = client.write((const uint8_t*)(jsonBuf + sent), toSend);
        if (written == 0) { sendError = true; break; }
        sent += written;
      }
      if (sendError) { client.stop(); continue; }
      Serial.printf("[FACE] Enviados %u bytes OK a %s\n", sent, targets[s].label);

      unsigned long timeout = millis() + 30000;
      while (!client.available() && millis() < timeout) delay(100);

      if (client.available()) {
        String statusLine = client.readStringUntil('\n');
        Serial.println("[FACE] " + statusLine);
        int spaceIdx = statusLine.indexOf(' ');
        if (spaceIdx > 0) code = statusLine.substring(spaceIdx + 1, spaceIdx + 4).toInt();
        while (client.available()) {
          String headerLine = client.readStringUntil('\n'); headerLine.trim();
          if (headerLine.length() == 0) break;
        }
        while (client.available()) response += (char)client.read();
      }
      client.stop();
    }

    if (code >= 200 && code < 300) {
      Serial.printf("[FACE] Respuesta exitosa de %s\n", targets[s].label);
      serverConnected = true;
      break;
    }
    Serial.printf("[FACE] %s respondio HTTP %d, intentando siguiente...\n", targets[s].label, code);
  }

  free(jsonBuf);  // Liberar PSRAM del JSON

  if (!serverConnected && code == 0) {
    Serial.println("[FACE] No se pudo conectar a ningun servidor");
    return false;
  }

  Serial.printf("[FACE] HTTP %d\n", code);
  Serial.println("[FACE] Respuesta: " + response);

  StaticJsonDocument<1024> doc;
  DeserializationError err = deserializeJson(doc, response);
  if (err) {
    Serial.println("[FACE] JSON invalido de verificacion");
    return false;
  }

  bool success = doc["success"] | false;
  int userId = doc["user_id"] | -1;

  if (!success || userId <= 0) {
    Serial.println("[FACE] Rostro no reconocido");
    return false;
  }

  outUserId = userId;
  if (!fetchUserName(userId, outUserName)) {
    outUserName = "Usuario " + String(userId);
  }

  return true;
}

bool isContainerLocked() {
  if (lockedUntilMs == 0) {
    return false;
  }
  long remaining = static_cast<long>(lockedUntilMs - millis());
  return remaining > 0;
}

void showLockScreen() {
  long remainingMs = static_cast<long>(lockedUntilMs - millis());
  if (remainingMs < 0) {
    remainingMs = 0;
  }
  int remainingSec = static_cast<int>((remainingMs + 999) / 1000);

  showOledLines(
    "Contenedor bloqueado",
    "por seguridad",
    "Espere: " + String(remainingSec) + "s"
  );
}

void handleIdentityFailure() {
  lockedUntilMs = millis() + BLOCK_TIME_MS;

  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println(F("Identidad no"));
  display.println(F("confirmada"));
  display.println();
  display.println(F("Contenedor"));
  display.println(F("bloqueado 1 minuto"));
  display.display();

  digitalWrite(RED_LED_PIN, HIGH);
  delay(2000);
  digitalWrite(RED_LED_PIN, LOW);
}

void showIdentityCheckingMessage() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println(F("Solicitud recibida"));
  display.println(F("correctamente,"));
  display.println(F("comprobando"));
  display.println(F("indentidad"));
  display.display();
}

void showIdentityConfirmedMessage(const String& userName) {
  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println(F("Usuario confirmado"));
  display.println(F("correctamente"));
  display.println();
  display.println(F("Bienvenido:"));

  String shortName = userName;
  if (shortName.length() > 20) {
    shortName = shortName.substring(0, 20);
  }
  display.println(shortName);

  drawSmileIcon(116, 10, 9);
  display.display();
}

// ===== INICIALIZACION COMPONENTES =====
void initComponents() {
  servo.attach(SERVO_PIN, 1000, 2000);
  servo.write(DOOR_CLOSE_ANGLE);

  scale.begin(LOAD_CELL_DT, LOAD_CELL_SCK);
  scale.set_scale(CALIBRATION_FACTOR);
  // Tare con timeout para evitar bloqueo si HX711 no esta conectado
  unsigned long tareStart = millis();
  bool hx711Ready = false;
  while (millis() - tareStart < 3000) {
    if (scale.is_ready()) {
      hx711Ready = true;
      break;
    }
    delay(50);
  }
  if (hx711Ready) {
    scale.tare();
    Serial.println("[HX711] Celda de carga lista");
  } else {
    Serial.println("[HX711] No detectada - continuando sin balanza");
  }

  pinMode(RED_LED_PIN, OUTPUT);
  pinMode(GREEN_LED_PIN, OUTPUT);
  digitalWrite(RED_LED_PIN, LOW);
  digitalWrite(GREEN_LED_PIN, LOW);

  pinMode(BUTTON_PIN, INPUT_PULLUP);
  attachInterrupt(digitalPinToInterrupt(BUTTON_PIN), buttonISR, FALLING);

  // Sensor PNP: asumimos HIGH cuando detecta metal.
  pinMode(METAL_SENSOR_PIN, INPUT_PULLDOWN);

  Serial.println("Componentes inicializados");
}

// ===== CONEXION WIFI =====
// Intenta conectar una vez (con limpieza previa). Retorna true si conecta.
bool wifiConnectOnce(int timeoutSec) {
  WiFi.disconnect(true);  // Desconectar limpio y borrar credenciales previas
  delay(200);
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);    // Reconexion automatica del driver
  WiFi.persistent(false);         // No guardar en flash (evita desgaste)

  Serial.printf("[WIFI] Conectando a '%s'...\n", ssid);
  WiFi.begin(ssid, password);

  unsigned long start = millis();
  unsigned long timeoutMs = (unsigned long)timeoutSec * 1000UL;
  while (WiFi.status() != WL_CONNECTED && (millis() - start) < timeoutMs) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();

  return WiFi.status() == WL_CONNECTED;
}

// Conexion robusta con multiples reintentos y reset de radio si falla
void connectToWiFi() {
  showOledLines("Conectando WiFi...");

  const int MAX_RETRIES = 3;
  bool connected = false;

  for (int retry = 0; retry < MAX_RETRIES && !connected; retry++) {
    if (retry > 0) {
      Serial.printf("[WIFI] Reintento %d/%d...\n", retry + 1, MAX_RETRIES);
      showOledLines("WiFi reintento", String(retry + 1) + "/" + String(MAX_RETRIES));
      // Reset completo de la radio WiFi entre reintentos
      WiFi.disconnect(true);
      WiFi.mode(WIFI_OFF);
      delay(1000);
    }
    connected = wifiConnectOnce(15);  // 15 seg por intento
  }

  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 0);

  if (connected) {
    Serial.println("[WIFI] Conectado OK");
    Serial.printf("[WIFI] IP: %s  RSSI: %d dBm\n",
                  WiFi.localIP().toString().c_str(), WiFi.RSSI());
    display.println(F("WiFi: OK"));
    display.print(F("IP: "));
    display.println(WiFi.localIP());
    display.print(F("Senal: "));
    display.print(WiFi.RSSI());
    display.println(F(" dBm"));
  } else {
    Serial.println("[WIFI] Fallo tras todos los reintentos");
    display.println(F("WiFi: Sin conexion"));
    display.println(F("Sistema continua..."));
    display.println(F("Reintentara despues"));
  }
  display.display();
  delay(2000);
}

// Verificar WiFi y reconectar si se perdio (llamar periodicamente)
void ensureWiFiConnected() {
  if (WiFi.status() == WL_CONNECTED) return;

  Serial.println("[WIFI] Conexion perdida, reconectando...");
  // Intento rapido primero (el driver puede reconectar solo)
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - start) < 5000) {
    delay(300);
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[WIFI] Reconectado automaticamente");
    return;
  }
  // Si no, hacer reconexion completa (1 intento de 10s)
  wifiConnectOnce(10);
  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("[WIFI] Reconectado: IP=%s RSSI=%d\n",
                  WiFi.localIP().toString().c_str(), WiFi.RSSI());
  } else {
    Serial.println("[WIFI] Reconexion fallida");
  }
}

// ===== ABRIR COMPUERTA LENTAMENTE =====
void openDoor() {
  showOledLines("Abriendo compuerta...");

  for (int angle = DOOR_CLOSE_ANGLE; angle <= DOOR_OPEN_ANGLE; angle++) {
    servo.write(angle);
    delay(20);
  }

  digitalWrite(GREEN_LED_PIN, HIGH);
  Serial.println("Compuerta abierta a 90 grados");
  delay(500);
}

// ===== CERRAR COMPUERTA LENTAMENTE =====
void closeDoorSlowly() {
  showOledLines("Cerrando compuerta...");

  for (int angle = DOOR_OPEN_ANGLE; angle >= DOOR_CLOSE_ANGLE; angle--) {
    servo.write(angle);
    delay(50);
  }
  digitalWrite(GREEN_LED_PIN, LOW);
  Serial.println("Compuerta cerrada");
}

// ===== DETECCION METAL (PNP) =====
bool detectMetal() {
  int metalReadings = 0;

  showOledLines("Verificando la", "presencia de", "metales...");

  for (int i = 0; i < 20; i++) {
    if (digitalRead(METAL_SENSOR_PIN) == HIGH) {
      metalReadings++;
    }
    delay(50);
  }

  return (metalReadings > 10);
}

// ===== PROCESAMIENTO PARA METAL DETECTADO =====
void handleMetalDetected(int userId) {
  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println(F("METAL DETECTADO,"));
  display.println(F("SANCION"));
  display.println(F("REGISTRADA Y"));
  display.println(F("ENVIADA"));
  display.display();

  digitalWrite(RED_LED_PIN, HIGH);
  servo.write(DOOR_OPEN_ANGLE);
  delay(2000);
  closeDoorSlowly();

  sendSanction(userId, "Metal detectado en contenedor");

  delay(2000);
  digitalWrite(RED_LED_PIN, LOW);
  ESP.restart();
}

// ===== MEDIR PESO =====
float measureWeight() {
  showOledLines("Midiendo peso...");

  scale.tare();
  float totalWeight = 0.0;
  int validReadings = 0;
  unsigned long startTime = millis();
  int timePerSample = DOOR_OPEN_TIME / LOAD_CELL_SAMPLES;

  while (validReadings < LOAD_CELL_SAMPLES) {
    if (scale.is_ready()) {
      float reading = scale.get_units(1);

      if (reading < -1000.0 || reading > 1000.0) {
        Serial.println("Lectura fuera de rango descartada: " + String(reading));
        continue;
      }

      if (reading < 0) {
        reading = -reading;
      }

      totalWeight += reading;
      validReadings++;

      if (validReadings % 10 == 0) {
        display.clearDisplay();
        display.setTextSize(1);
        display.setCursor(0, 0);
        display.println(F("Pesando..."));
        display.print(F("Muestras: "));
        display.println(String(validReadings) + "/100");
        display.print(F("Peso: "));
        display.println(reading, 2);
        display.print(F("Tiempo: "));
        display.print((millis() - startTime) / 1000);
        display.println(F("s"));
        display.display();
      }

      Serial.println("Lectura " + String(validReadings) + ": " + String(reading, 3) + " kg");
    }

    delay(timePerSample);

    if (millis() - startTime > (DOOR_OPEN_TIME + 1000)) {
      Serial.println("Timeout de seguridad alcanzado");
      break;
    }
  }

  weightKg = validReadings > 0 ? totalWeight / validReadings : 0.0;
  if (weightKg < 0.01) {
    weightKg = 0.0;
  }

  Serial.println("Peso promedio final: " + String(weightKg, 3) + " kg (" + String(validReadings) + " muestras)");
  return weightKg;
}

float convertKgToKWh(float kg) {
  return kg * KG_TO_KWH;
}

float calculateCostRD(float kwh) {
  return kwh * KWH_COST_RD;
}

// ===== URL ENCODE PARA FORM DATA =====
String urlEncode(const String& str) {
  String encoded = "";
  char c;
  char code0;
  char code1;
  for (unsigned int i = 0; i < str.length(); i++) {
    c = str.charAt(i);
    if (c == ' ') {
      encoded += '+';
    } else if (isalnum(c) || c == '-' || c == '_' || c == '.' || c == '~') {
      encoded += c;
    } else {
      code1 = (c & 0xf) + '0';
      if ((c & 0xf) > 9) code1 = (c & 0xf) - 10 + 'A';
      c = (c >> 4) & 0xf;
      code0 = c + '0';
      if (c > 9) code0 = c - 10 + 'A';
      encoded += '%';
      encoded += code0;
      encoded += code1;
    }
  }
  return encoded;
}

// ===== ENVIO HTTP GENERICO =====
int httpPostJSON(const char* baseURL, const char* endpoint, const String& jsonBody, const char* apiKey) {
  HTTPClient http;
  String url = String(baseURL) + endpoint;

  http.begin(url);
  http.addHeader("Content-Type", "application/json; charset=utf-8");
  if (apiKey) {
    http.addHeader("X-API-KEY", apiKey);
  }
  http.setTimeout(15000);

  int code = http.POST(jsonBody);
  if (code > 0) {
    String response = http.getString();
    Serial.printf("[HTTP] %s -> %d: %s\n", url.c_str(), code, response.c_str());
  } else {
    Serial.printf("[HTTP] %s -> Error: %d\n", url.c_str(), code);
  }
  http.end();
  return code;
}

// ===== ENVIO DIRECTO A INFINITYFREE - DESACTIVADO TEMPORALMENTE =====
// Esta funcion no se usa mientras InfinityFree este en pausa.
int httpPostFormIF(const char* fullURL, const String& jsonData, String& outResponse) {
  // InfinityFree desactivado. Retorna -2 para indicar que no se intento.
  outResponse = "";
  return -2;
}

bool sendWeightData(int userId, float measuredWeightKg, float kwh, float costRD) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi no conectado - no se enviaran datos");
    return false;
  }

  StaticJsonDocument<512> doc;
  doc["id_usuario"] = userId;
  doc["id_contenedor"] = CONTAINER_ID_FIXED;
  doc["token_usado"] = CONTAINER_TOKEN_FIXED;
  doc["peso"] = measuredWeightKg;
  doc["tipo_residuo"] = metalDetected ? "metal" : "organico";
  doc["credito_kwh"] = kwh;
  doc["metal_detectado"] = metalDetected ? 1 : 0;
  doc["procesado_por"] = "Administrador";
  doc["observaciones"] = "N/A";
  doc["contenedor_id"] = CONTAINER_ID_FIXED;

  String jsonString;
  serializeJson(doc, jsonString);

  Serial.println("=== ENVIANDO DEPOSITO ===");
  Serial.println("JSON: " + jsonString);

  bool anySuccess = false;

  // Solo localhost activo (InfinityFree desactivado temporalmente)
  Serial.println("[DEPOSIT] Enviando a localhost...");
  int localCode = httpPostJSON(serverAPI, ENDPOINT_REGISTRAR_DEPOSITOS, jsonString, CONTAINER_API_KEY_FIXED);
  if (localCode >= 200 && localCode < 300) {
    Serial.println("[DEPOSIT] localhost OK");
    anySuccess = true;
  }

  return anySuccess;
}

bool sendSanction(int userId, const String& reason) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi no conectado - Sancion no enviada");
    return false;
  }

  StaticJsonDocument<256> doc;
  doc["user_id"] = userId;
  doc["contenedor_id"] = CONTAINER_ID_FIXED;
  doc["descripcion"] = reason;
  doc["peso"] = weightKg;
  doc["token_usado"] = CONTAINER_TOKEN_FIXED;
  doc["codigo_contenedor"] = CONTAINER_CODE_FIXED;
  doc["ubicacion"] = CONTAINER_LOCATION_FIXED;
  doc["timestamp"] = millis();

  String jsonString;
  serializeJson(doc, jsonString);

  Serial.println("=== ENVIANDO SANCION ===");
  Serial.println("JSON: " + jsonString);

  // Solo localhost activo (InfinityFree desactivado temporalmente)
  Serial.println("[SANCION] Enviando a localhost...");
  int localCode = httpPostJSON(serverAPI, ENDPOINT_REGISTRAR_SANCION, jsonString, CONTAINER_API_KEY_FIXED);
  if (localCode >= 200 && localCode < 300) {
    Serial.println("[SANCION] localhost OK");
  }

  return (localCode >= 200 && localCode < 300);
}

void showSuccessScreen(const String& userName) {
  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println(F("Deposito registrado"));
  display.println(F("correctamente"));
  display.println();
  display.println(F("Gracias por usar"));
  display.println(F("PRERMI"));

  String shortName = userName;
  if (shortName.length() > 20) {
    shortName = shortName.substring(0, 20);
  }
  display.println(shortName);
  display.display();

  digitalWrite(GREEN_LED_PIN, HIGH);
  delay(3000);
  digitalWrite(GREEN_LED_PIN, LOW);
}

void showErrorScreen(const String& error) {
  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println(F("ERROR:"));
  display.println(error);
  display.display();

  digitalWrite(RED_LED_PIN, HIGH);
  delay(3000);
  digitalWrite(RED_LED_PIN, LOW);
}

// ===== SETUP =====
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n\nIniciando PRERMI Container System...");

  initDisplay();
  initComponents();
  connectToWiFi();

  Serial.println("[CONTENEDOR FIJO]");
  Serial.println("ID: " + String(CONTAINER_ID_FIXED));
  Serial.println("Codigo: " + String(CONTAINER_CODE_FIXED));
  Serial.println("API Key: " + String(CONTAINER_API_KEY_FIXED));
  Serial.println("Token: " + String(CONTAINER_TOKEN_FIXED));
  Serial.println("Ubicacion: " + String(CONTAINER_LOCATION_FIXED));
  Serial.println("Lat/Lon: " + String(CONTAINER_LAT_FIXED, 4) + ", " + String(CONTAINER_LON_FIXED, 3));

  cameraReady = initCamera();
  if (!cameraReady) {
    showErrorScreen("Camara no disponible");
  }

  showWelcomeScreen();
  Serial.println("Sistema listo. Esperando boton...");
}

// ===== LOOP PRINCIPAL =====
static unsigned long lastWiFiCheck = 0;

void loop() {
  if (!buttonPressed) {
    if (isContainerLocked()) {
      showLockScreen();
      delay(250);
    } else {
      // Verificar WiFi cada 30 segundos mientras esta idle
      if (millis() - lastWiFiCheck > 30000) {
        lastWiFiCheck = millis();
        ensureWiFiConnected();
      }
      delay(100);
    }
    return;
  }

  buttonPressed = false;

  if (isContainerLocked()) {
    showLockScreen();
    delay(1500);
    return;
  }

  Serial.println("\n=== CICLO DE DEPOSITO INICIADO ===");

  // Asegurar WiFi antes de operar
  ensureWiFiConnected();

  // Paso 1: Verificacion facial real
  showIdentityCheckingMessage();

  int userId = -1;
  String userName = "Usuario";
  bool verified = verifyIdentityWithFace(userId, userName);

  if (!verified) {
    Serial.println("[FACE] Identidad no confirmada, contenedor bloqueado 1 minuto");
    handleIdentityFailure();
    delay(1500);
    showWelcomeScreen();
    return;
  }

  identifiedUserId = userId;
  identifiedUserName = userName;

  Serial.println("[FACE] Usuario confirmado: " + String(identifiedUserId) + " - " + identifiedUserName);
  showIdentityConfirmedMessage(identifiedUserName);
  delay(2500);

  // Paso 2: Abrir compuerta y medir peso
  openDoor();
  Serial.println("Compuerta abierta - Iniciando medicion de 20 segundos con 100 muestras");

  unsigned long doorOpenStart = millis();
  weightKg = measureWeight();
  unsigned long measureTime = millis() - doorOpenStart;

  Serial.println("Medicion completada en " + String(measureTime) + "ms");

  // Paso 3: Cerrar compuerta
  closeDoorSlowly();
  delay(700);

  // Paso 4: Verificar metal
  metalDetected = detectMetal();

  if (metalDetected) {
    Serial.println("METAL DETECTADO - Registrando sancion y bloqueando ciclo");
    handleMetalDetected(identifiedUserId);
    return;
  }

  showOledLines(
    "No se encontro nada,",
    "procediendo con",
    "el deposito"
  );
  delay(1800);

  // Paso 5: Convertir y registrar deposito
  float kwh = convertKgToKWh(weightKg);
  float costRD = calculateCostRD(kwh);

  Serial.println("Peso: " + String(weightKg) + " kg");
  Serial.println("kWh: " + String(kwh));
  Serial.println("Costo RD: " + String(costRD));

  showOledLines("Guardando datos...");

  bool success = sendWeightData(identifiedUserId, weightKg, kwh, costRD);

  if (!success) {
    showErrorScreen("Error al guardar");
    delay(1200);
    ESP.restart();
    return;
  }

  // Paso 6: Exito
  showSuccessScreen(identifiedUserName);

  delay(1200);
  ESP.restart();
}
