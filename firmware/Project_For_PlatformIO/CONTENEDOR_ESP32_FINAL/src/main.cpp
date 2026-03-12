/********************************************
 *        ESP32-S3 CAM – PRERMI (final)
 ********************************************/

#include <WiFi.h>
#include <HTTPClient.h>
#define CAMERA_MODEL_ESP32S3_EYE  // Ajusta aquí si usas otro pinout
#include "esp_camera.h"
#include "camera_pins.h"
#include "HX711.h"
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <ArduinoJson.h>

// ================== CONFIGURACIÓN ==================
const char* WIFI_SSID = "Jhail-WIFI";
const char* WIFI_PASS = "123.02589.";

const char* SERVER_IP   = "192.168.1.106";
const int   SERVER_PORT = 8080;
String baseAPI = String("http://") + SERVER_IP + ":" + String(SERVER_PORT) + "/PRERMI/api/";
String API_VERIFY_FACE      = baseAPI + "usuarios/verificar_rostro.php";
String API_REGISTER_DEPOSIT = baseAPI + "contenedores/registrar_deposito.php";
String API_UPLOAD_PHOTO     = baseAPI + "contenedores/subir_foto_deposito.php";
String API_SANCTION         = baseAPI + "sanciones/registrar_sancion.php"; // endpoint para sanciones

// ================== HW PINS ======================
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET    -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

#define HX711_DOUT     32
#define HX711_SCK      33
HX711 scale;

#define INDUCTIVE_PIN  15

#define BUTTON_PIN      0
#define LED_GREEN_PIN  13
#define LED_RED_PIN    14

// ================== VARIABLES ====================
bool systemActive = false;
bool inductiveState = false;
bool metalDetected = false; // ahora se usa el mismo pin
float currentWeight = 0.0;
int lastDepositId = 0;

// ================= PROTOTIPOS ===================
void initWiFi();
void initOLED();
void initHX711();
void initCamera();
void initInputs();

float readWeight();
bool readInductive();

String capturePhotoBase64();

bool sendImageForVerification(String base64Img, int &userId);
bool sendDeposit(float peso, bool inductive, int userId);
bool uploadPhoto(int depositId, String base64Img);
bool sendSanction(int userId, float peso);

// ================= SETUP =========================
void setup() {
  Serial.begin(115200);

  pinMode(BUTTON_PIN, INPUT_PULLUP);
  pinMode(LED_GREEN_PIN, OUTPUT);
  pinMode(LED_RED_PIN, OUTPUT);
  pinMode(INDUCTIVE_PIN, INPUT);

  initWiFi();
  initOLED();
  initHX711();
  initCamera();

  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(WHITE);
  display.setCursor(0,0);
  display.println("PRERMI - ESP32 CAM");
  display.println("Presiona Inicio");
  display.display();
}

// ================= LOOP ==========================
void loop() {

  // Esperar botón de inicio
  if (!systemActive) {
    if (digitalRead(BUTTON_PIN) == LOW) {
      delay(50); // debounce
      systemActive = true;
      digitalWrite(LED_GREEN_PIN, HIGH);
      display.clearDisplay();
      display.println("Sistema Activo");
      display.display();
      delay(500);
    }
    return;
  }

  // Lecturas (un único sensor)
  inductiveState = readInductive();
  metalDetected = inductiveState; // mismas lecturas para metal/presencia
  currentWeight = readWeight();

  // Mostrar sistema
  display.clearDisplay();
  display.setCursor(0, 0);
  display.printf("Peso: %.2f kg\n", currentWeight);
  display.printf("Inductivo: %s\n", inductiveState ? "SI" : "NO");
  display.printf("Metal: %s\n", metalDetected ? "SI" : "NO");
  display.display();

  // Si detecta metal → sanción (no se aborta el resto del ciclo)
  if (metalDetected) {
    digitalWrite(LED_RED_PIN, HIGH);
    digitalWrite(LED_GREEN_PIN, LOW);
    sendSanction(/*usuario*/0, currentWeight);
    delay(3000);
  } else {
    digitalWrite(LED_RED_PIN, LOW);
    digitalWrite(LED_GREEN_PIN, HIGH);
  }

  // Si detecta presencia inductiva (mismo pin)
  if (inductiveState) {
    delay(1000);

    // Foto y verificación
    String photoB64 = capturePhotoBase64();
    int detectedUser = 0;

    if (sendImageForVerification(photoB64, detectedUser)) {

      // Registrar depósito
      if (sendDeposit(currentWeight, inductiveState, detectedUser)) {

        // Subir foto
        uploadPhoto(lastDepositId, photoB64);
      }
    }
    delay(3000);
  }

  delay(200);
}

// ================= FUNCIONES ===================

void initWiFi() {
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("WiFi conectado!");
}

void initOLED() {
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("OLED falló!");
    while (true);
  }
  display.clearDisplay();
}

void initHX711() {
  scale.begin(HX711_DOUT, HX711_SCK);
  scale.set_scale(2280.f);
  scale.tare();
}

void initCamera() {
  camera_config_t config;
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
  config.frame_size = FRAMESIZE_QVGA;
  config.jpeg_quality = 12;
  config.fb_count = 1;
  esp_camera_init(&config);
}

float readWeight() {
  if (scale.is_ready()) return scale.get_units(5);
  return 0.0;
}

bool readInductive() {
  return digitalRead(INDUCTIVE_PIN) == HIGH;
}

String capturePhotoBase64() {
  camera_fb_t* fb = esp_camera_fb_get();
  if (!fb) return "";
  String b64 = base64::encode(fb->buf, fb->len);
  esp_camera_fb_return(fb);
  return b64;
}

bool sendImageForVerification(String base64Img, int &userId) {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  http.begin(API_VERIFY_FACE);
  http.addHeader("Content-Type","application/json");
  String body = "{\"imagen_base64\":\"" + base64Img + "\"}";
  int code = http.POST(body);
  if (code == 200) {
    String resp = http.getString();
    DynamicJsonDocument doc(256);
    deserializeJson(doc, resp);
    if (doc["success"]) {
      userId = doc["user_id"];
      http.end();
      return true;
    }
  }
  http.end();
  return false;
}

bool sendDeposit(float peso, bool inductive, int userId) {
  if (WiFi.status()!=WL_CONNECTED) return false;
  HTTPClient http;
  http.begin(API_REGISTER_DEPOSIT);
  http.addHeader("Content-Type","application/json");
  String body = "{";
  body += "\"usuario_id\":" + String(userId) + ",";
  body += "\"id_contenedor\":1,";
  body += "\"peso\":" + String(peso,2) + ",";
  body += "\"tipo_residuo\":\"general\",";
  body += "\"inductivo\":" + String(inductive?1:0);
  body += "}";
  int code = http.POST(body);
  if (code==200) {
    String resp = http.getString();
    DynamicJsonDocument doc(256);
    deserializeJson(doc,resp);
    if (doc["success"]) {
      lastDepositId = doc["deposito_id"];
      http.end();
      return true;
    }
  }
  http.end();
  return false;
}

bool uploadPhoto(int depositId, String base64Img) {
  if (WiFi.status()!=WL_CONNECTED) return false;
  HTTPClient http;
  http.begin(API_UPLOAD_PHOTO);
  http.addHeader("Content-Type","application/json");
  String body = "{";
  body += "\"deposito_id\":" + String(depositId) + ",";
  body += "\"imagen_base64\":\"" + base64Img + "\"";
  body += "}";
  int code = http.POST(body);
  http.end();
  return (code==200);
}

bool sendSanction(int userId, float peso) {
  if (WiFi.status()!=WL_CONNECTED) return false;
  HTTPClient http;
  http.begin(API_SANCTION);
  http.addHeader("Content-Type","application/json");
  String body = "{";
  body += "\"user_id\":" + String(userId)+",";
  body += "\"peso\":" + String(peso,2);
  body += "}";
  int code = http.POST(body);
  http.end();
  return (code==200);
}
