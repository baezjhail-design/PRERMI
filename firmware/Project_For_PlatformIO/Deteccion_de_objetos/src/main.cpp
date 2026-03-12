#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <HAND_DETECTOR_inferencing.h>
#include "esp_camera.h"
#include "fb_gfx.h"

#define LED_PIN 42

const char* WIFI_SSID = "Jhail - Habitación";
const char* WIFI_PASS = "123.02589";

WebServer server(80);

#define PWDN_GPIO_NUM     -1
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM     15
#define SIOD_GPIO_NUM     4
#define SIOC_GPIO_NUM     5
#define Y9_GPIO_NUM       16
#define Y8_GPIO_NUM       17
#define Y7_GPIO_NUM       18
#define Y6_GPIO_NUM       12
#define Y5_GPIO_NUM       10
#define Y4_GPIO_NUM       8
#define Y3_GPIO_NUM       9
#define Y2_GPIO_NUM       11
#define VSYNC_GPIO_NUM    6
#define HREF_GPIO_NUM     7
#define PCLK_GPIO_NUM     13

static uint8_t *snapshot_buf;
static camera_fb_t *frame_buf = nullptr;
static volatile bool inference_running = false;
// Buffer prelocado para RGB565 (320x240x2 = 153600 bytes)
static uint8_t rgb565_buffer[320 * 240 * 2];
String last_detection = "Ninguna";
float last_confidence = 0.0;
unsigned long last_inference_time = 0;
unsigned long last_led_on_time = 0;
bool led_is_on = false;

const char INDEX_HTML[] PROGMEM = R"HTML(
<!DOCTYPE html>
<html>
<head>
  <title>Deteccion de Objetos</title>
  <style>
    body { font-family: Arial; background: #0b1222; color: #fff; margin: 20px; }
    h1 { color: #3b82f6; }
    .container { max-width: 1000px; }
    #stream { width: 100%; max-width: 640px; margin: 20px 0; border: 2px solid #3b82f6; border-radius: 8px; }
    .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
    .stat { background: #111; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; }
    #log { background: #000; padding: 15px; border-radius: 8px; max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Monitor de Deteccion</h1>
    <img id="stream" src="/stream" alt="Camera Stream">
    
    <div class="stats">
      <div class="stat">
        <div>Deteccion: <strong id="detection">-</strong></div>
      </div>
      <div class="stat">
        <div>Confianza: <strong id="confidence">-</strong>%</div>
      </div>
    </div>
    
    <h3>Log</h3>
    <div id="log"></div>
  </div>

  <script>
    const stream = document.getElementById('stream');
    setInterval(() => {
      stream.src = '/stream?t=' + Date.now();
    }, 100);

    setInterval(async () => {
      try {
        const res = await fetch('/stats');
        const data = await res.json();
        document.getElementById('detection').textContent = data.detection;
        document.getElementById('confidence').textContent = data.confidence.toFixed(1);
      } catch(e) {}
    }, 500);
  </script>
</body>
</html>
)HTML";

void conectarWiFi() {
  Serial.print("WiFi...");
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 10000) {
    Serial.print(".");
    delay(500);
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println(" OK: " + WiFi.localIP().toString());
  } else {
    Serial.println(" FAIL");
  }
}

void handleRoot() {
  server.send_P(200, "text/html", INDEX_HTML);
}

void handleStream() {
  // Evitar conflicto con inferencia
  if (inference_running) {
    server.send(503, "text/plain", "Busy");
    return;
  }
  
  camera_fb_t* fb = esp_camera_fb_get();
  if (!fb) {
    server.send(500, "text/plain", "Error");
    return;
  }
  server.sendHeader("Content-Type", "image/jpeg");
  server.sendHeader("Cache-Control", "no-cache");
  server.send_P(200, "image/jpeg", (const char*)fb->buf, fb->len);
  esp_camera_fb_return(fb);
}

void handleStats() {
  char buf[128];
  snprintf(buf, sizeof(buf), "{\"detection\":\"%s\",\"confidence\":%.1f}", last_detection.c_str(), last_confidence);
  server.send(200, "application/json", buf);
}

bool ei_camera_init() {
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer   = LEDC_TIMER_0;
  config.pin_d0       = Y2_GPIO_NUM;
  config.pin_d1       = Y3_GPIO_NUM;
  config.pin_d2       = Y4_GPIO_NUM;
  config.pin_d3       = Y5_GPIO_NUM;
  config.pin_d4       = Y6_GPIO_NUM;
  config.pin_d5       = Y7_GPIO_NUM;
  config.pin_d6       = Y8_GPIO_NUM;
  config.pin_d7       = Y9_GPIO_NUM;
  config.pin_xclk     = XCLK_GPIO_NUM;
  config.pin_pclk     = PCLK_GPIO_NUM;
  config.pin_vsync    = VSYNC_GPIO_NUM;
  config.pin_href     = HREF_GPIO_NUM;
  config.pin_sccb_sda = SIOD_GPIO_NUM;
  config.pin_sccb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn     = PWDN_GPIO_NUM;
  config.pin_reset    = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;  // JPEG para servidor web
  config.frame_size   = FRAMESIZE_QVGA;   // 320x240
  config.jpeg_quality = 25;  // Mejor calidad (rango: 10-63, mayor = mejor)
  config.fb_count     = 2;  // 2 buffers para evitar bloqueos

  return esp_camera_init(&config) == ESP_OK;
}

void setup() {
  Serial.begin(115200);
  delay(2000);
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);

  conectarWiFi();

  if (!ei_camera_init()) {
    Serial.println("Camera init failed");
    while (1);
  }

  server.on("/", HTTP_GET, handleRoot);
  server.on("/stream", HTTP_GET, handleStream);
  server.on("/stats", HTTP_GET, handleStats);
  server.begin();
  Serial.println("Server started");
  
  // Debug info del modelo
  Serial.println("\n=== Model Info ===");
  Serial.printf("Model name: %s\n", EI_CLASSIFIER_PROJECT_NAME);
  Serial.printf("Input: %d x %d x 3 (RGB)\n", EI_CLASSIFIER_INPUT_WIDTH, EI_CLASSIFIER_INPUT_HEIGHT);
  Serial.println("===================\n");
}

void loop() {
  // Siempre maneja las solicitudes del servidor (NO BLOQUEANTE)
  server.handleClient();

  // Control del LED (no-bloqueante)
  if (led_is_on && millis() - last_led_on_time > 500) {
    digitalWrite(LED_PIN, LOW);
    led_is_on = false;
  }

  // Procesamiento de cámara cada 1500ms (no-bloqueante)
  if (millis() - last_inference_time < 1500) {
    return; // Espera sin bloquear
  }

  last_inference_time = millis();

  inference_running = true;  // Lock
  frame_buf = esp_camera_fb_get();
  if (!frame_buf) {
    inference_running = false;
    return;
  }

  ei_impulse_result_t result = { 0 };
  signal_t signal;
  signal.total_length = EI_CLASSIFIER_INPUT_WIDTH * EI_CLASSIFIER_INPUT_HEIGHT * 3;
  
  // Decodificar JPEG a RGB565 una sola vez
  if (!jpg2rgb565((uint8_t *)frame_buf->buf, frame_buf->len, rgb565_buffer, JPG_SCALE_NONE)) {
    Serial.println("ERROR: JPEG decode failed");
    esp_camera_fb_return(frame_buf);
    frame_buf = nullptr;
    inference_running = false;
    return;
  }
  
  Serial.println("\n=== INFERENCE START ===");
  Serial.printf("Input size: %d x %d x 3\n", EI_CLASSIFIER_INPUT_WIDTH, EI_CLASSIFIER_INPUT_HEIGHT);
  
  // Callback para convertir RGB565 a float
  signal.get_data = [](size_t offset, size_t length, float *out_ptr) {
    if (!rgb565_buffer) return 1;
    
    size_t px_count = length / 3;  // Pixeles (3 canales por píxel)
    
    for (size_t i = 0; i < px_count; i++) {
      // RGB565: formato big-endian (RRRRRGG GGGBBBBB)
      uint8_t byte1 = rgb565_buffer[i*2];
      uint8_t byte2 = rgb565_buffer[i*2+1];
      uint16_t px = (byte1 << 8) | byte2;
      
      // Extraer RGB565 (5 bits R, 6 bits G, 5 bits B)
      uint8_t r = (px >> 11) & 0x1F;  // bits 15-11
      uint8_t g = (px >> 5) & 0x3F;   // bits 10-5
      uint8_t b = px & 0x1F;          // bits 4-0
      
      // Expandir de 5/6 bits a 8 bits
      r = (r << 3) | (r >> 2);  // Expande 5 bits a 8
      g = (g << 2) | (g >> 4);  // Expande 6 bits a 8
      b = (b << 3) | (b >> 2);  // Expande 5 bits a 8
      
      // El modelo de Edge Impulse normalmente espera float [0..1]
      // Pero vamos a intentar con [0..255] y ver si funciona mejor
      out_ptr[i*3]   = (float)r;      // R [0..255]
      out_ptr[i*3+1] = (float)g;      // G [0..255]
      out_ptr[i*3+2] = (float)b;      // B [0..255]
    }
    return 0;
  };

  if (run_classifier(&signal, &result, false) == EI_IMPULSE_OK) {
    Serial.printf("Classifier executed successfully\n");
    Serial.printf("Bounding boxes count: %d\n", result.bounding_boxes_count);
    
    bool detected = false;
    
    // Mostrar TODAS las detecciones (incluyendo las de baja confianza)
    for (size_t ix = 0; ix < result.bounding_boxes_count; ix++) {
      Serial.printf("  Box %d: %s (confidence: %.4f)\n", 
        ix, 
        result.bounding_boxes[ix].label,
        result.bounding_boxes[ix].value);
    }
    
    // Umbral de detección: primero 0.5, luego 0.8
    float threshold = 0.3;  // Muy bajo para detectar cualquier cosita (debug)
    
    for (size_t ix = 0; ix < result.bounding_boxes_count; ix++) {
      if (result.bounding_boxes[ix].value > threshold) {
        last_detection = String(result.bounding_boxes[ix].label);
        last_confidence = result.bounding_boxes[ix].value * 100;
        digitalWrite(LED_PIN, HIGH);
        led_is_on = true;
        last_led_on_time = millis();
        detected = true;
        Serial.printf("✓ DETECTED: %s (%.1f%%)\n", last_detection.c_str(), last_confidence);
      }
    }
    if (!detected) {
      last_detection = "Ninguna";
      last_confidence = 0.0;
      Serial.println("No objects detected (confidence > 0.5)");
    }
  } else {
    Serial.println("ERROR: Classifier failed");
  }
  
  Serial.println("=== INFERENCE END ===\n");

  esp_camera_fb_return(frame_buf);
  frame_buf = nullptr;
  inference_running = false;  // Liberar lock
}
