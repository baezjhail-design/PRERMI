#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include "esp_camera.h"

const char* WIFI_SSID = "Jhail - Habitación";
const char* WIFI_PASS = "123.02589";

// Servidor HTTP
WebServer server(80);

// Variables de configuración de cámara
uint8_t jpeg_quality = 12;
framesize_t frame_size = FRAMESIZE_QVGA;

// Pinout Freenove ESP32-S3 CAM
#define PWDN_GPIO_NUM    -1
#define RESET_GPIO_NUM   -1
#define XCLK_GPIO_NUM    15
#define SIOD_GPIO_NUM     4
#define SIOC_GPIO_NUM     5
#define Y9_GPIO_NUM      16
#define Y8_GPIO_NUM      17
#define Y7_GPIO_NUM      18
#define Y6_GPIO_NUM      12
#define Y5_GPIO_NUM      10
#define Y4_GPIO_NUM       8
#define Y3_GPIO_NUM       9
#define Y2_GPIO_NUM      11
#define VSYNC_GPIO_NUM    6
#define HREF_GPIO_NUM     7
#define PCLK_GPIO_NUM    13

const char INDEX_HTML[] PROGMEM = R"HTML(
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>ESP32-S3 CAM - Capturas</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #0b1222; color: #e7ecf5; }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    h1 { margin-bottom: 8px; }
    .preview-section { background: #111827; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
    .video-container { position: relative; width: 100%; background: #000; border-radius: 6px; overflow: hidden; margin-bottom: 12px; }
    #stream { width: 100%; height: auto; display: block; }
    .controls-section { background: #111827; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
    label { display: block; margin: 12px 0 6px; font-weight: bold; }
    input, button { font-size: 16px; padding: 10px; margin-right: 8px; }
    button { cursor: pointer; background: #3b82f6; color: white; border: none; border-radius: 4px; }
    button:hover { background: #2563eb; }
    button:disabled { background: #1f2937; cursor: not-allowed; }
    .button-group { margin-top: 12px; }
    #log { margin-top: 16px; white-space: pre-line; font-family: monospace; background: #0f172a; padding: 10px; border-radius: 4px; max-height: 100px; overflow-y: auto; }
    #grid { margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
    .card { background: #111827; padding: 8px; border-radius: 6px; }
    .card img { width: 100%; border-radius: 4px; }
    .card div { font-size: 12px; margin-top: 6px; word-break: break-all; }
  </style>
</head>
<body>
  <div class="container">
    <h1>📷 ESP32-S3 CAM - Captura en Tiempo Real</h1>
    <p>Visualiza la cámara en vivo y captura fotos para Edge Impulse.</p>

    <div class="preview-section">
      <h2 style="margin-bottom: 12px;">Vista Previa en Vivo</h2>
      <div class="video-container">
        <img id="stream" src="/stream" alt="Stream en vivo">
      </div>
      <p style="font-size: 12px; color: #9ca3af;">Actualiza en tiempo real • Ajusta la posición y captura en el mejor ángulo</p>
      
      <div style="margin-top: 16px; background: #0f172a; padding: 12px; border-radius: 6px;">
        <h3 style="font-size: 14px; margin-bottom: 12px;">⚙️ Ajustes de Transmisión</h3>
        
        <label style="margin: 8px 0;">Calidad JPEG: <strong id="qualityValue">12</strong></label>
        <input id="quality" type="range" min="10" max="63" value="12" style="width: 100%; cursor: pointer;">
        <p style="font-size: 11px; color: #9ca3af; margin-top: 4px;">Menor = más detalle pero más lento | Mayor = más rápido pero menos detalle</p>
        
        <label style="margin: 12px 0 8px;">Resolución: <strong id="sizeValue">QVGA (320x240)</strong></label>
        <select id="framesize" style="width: 100%; padding: 8px; margin-top: 4px;">
          <option value="7">QVGA (320x240) - Rápido</option>
          <option value="8">VGA (640x480) - Equilibrado</option>
          <option value="9">SVGA (800x600) - Detallado</option>
          <option value="10">XGA (1024x768) - Alta resolución</option>
        </select>
        
        <label style="margin: 12px 0 8px;">Velocidad Stream: <strong id="fpsValue">10</strong> fps</label>
        <input id="fps" type="range" min="1" max="20" value="10" style="width: 100%; cursor: pointer;">
        <p style="font-size: 11px; color: #9ca3af; margin-top: 4px;">Menos fps = menor uso de ancho de banda | Más fps = más fluidez</p>
      </div>
    </div>

    <div class="controls-section">
      <h2 style="margin-bottom: 12px;">Captura de Fotos</h2>
      <label>Cantidad de fotos</label>
      <input id="count" type="number" min="1" max="50" value="5">
      
      <label>Etiqueta (opcional)</label>
      <input id="label" type="text" placeholder="ej. vehículo, persona, objeto">
      
      <div class="button-group">
        <button id="start">🎬 Tomar fotos</button>
        <button id="singleShot">📷 Una foto rápida</button>
      </div>
      
      <div id="log"></div>
    </div>

    <h2 style="margin: 20px 0 12px;">Fotos Capturadas</h2>
    <div id="grid"></div>
  </div>

  <script>
    const btn = document.getElementById('start');
    const singleBtn = document.getElementById('singleShot');
    const log = document.getElementById('log');
    const grid = document.getElementById('grid');
    const stream = document.getElementById('stream');
    const qualityInput = document.getElementById('quality');
    const framesizeInput = document.getElementById('framesize');
    const fpsInput = document.getElementById('fps');
    
    let streamRefreshRate = 100; // ms
    
    function addLog(msg) { 
      log.textContent = new Date().toLocaleTimeString() + ': ' + msg + '\n' + log.textContent; 
    }

    // Eventos para cambios en tiempo real
    qualityInput.addEventListener('input', async (e) => {
      const value = e.target.value;
      document.getElementById('qualityValue').textContent = value;
      await fetch(`/setquality?q=${value}`);
      addLog(`📊 Calidad: ${value}`);
    });

    framesizeInput.addEventListener('change', async (e) => {
      const labels = ['QVGA (320x240)', 'VGA (640x480)', 'SVGA (800x600)', 'XGA (1024x768)'];
      const value = e.target.value;
      const label = labels[parseInt(value) - 7];
      document.getElementById('sizeValue').textContent = label;
      await fetch(`/setsize?s=${value}`);
      addLog(`📐 Resolución: ${label}`);
    });

    fpsInput.addEventListener('input', (e) => {
      const value = e.target.value;
      document.getElementById('fpsValue').textContent = value;
      streamRefreshRate = Math.round(1000 / parseInt(value));
      addLog(`⚡ FPS: ${value} (${streamRefreshRate}ms)`);
    });

    // Mantener el stream activo
    setInterval(() => {
      stream.src = '/stream?t=' + Date.now();
    }, streamRefreshRate);

    btn.onclick = async () => {
      const total = parseInt(document.getElementById('count').value) || 1;
      const label = document.getElementById('label').value.trim();
      btn.disabled = true;
      singleBtn.disabled = true;
      for (let i = 1; i <= total; i++) {
        addLog(`Capturando ${i} / ${total}...`);
        await new Promise(resolve => setTimeout(resolve, 300));
        const res = await fetch(`/capture?i=${i}&label=${encodeURIComponent(label)}`);
        if (!res.ok) { addLog(`Error HTTP ${res.status}`); break; }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const name = res.headers.get('X-Filename') || `img_${i}.jpg`;
        const a = document.createElement('a');
        a.href = url; a.download = name; a.style.display = 'none';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `<img src="${url}"><div>${name}</div>`;
        grid.appendChild(card);
      }
      addLog('✓ Listo');
      btn.disabled = false;
      singleBtn.disabled = false;
    };

    singleBtn.onclick = async () => {
      const label = document.getElementById('label').value.trim();
      singleBtn.disabled = true;
      addLog('Capturando...');
      const res = await fetch(`/capture?i=0&label=${encodeURIComponent(label)}`);
      if (res.ok) {
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const name = res.headers.get('X-Filename') || `foto.jpg`;
        const a = document.createElement('a');
        a.href = url; a.download = name; a.style.display = 'none';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `<img src="${url}"><div>${name}</div>`;
        grid.appendChild(card);
        addLog('✓ Foto guardada');
      } else {
        addLog('✗ Error al capturar');
      }
      singleBtn.disabled = false;
    };
  </script>
</body>
</html>
)HTML";

bool wifi_ok = false;
unsigned long lastReconnect = 0;

void conectarWiFi() {
  Serial.print("Conectando a Wi-Fi...");
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 10000) {
    Serial.print(".");
    delay(500);
  }
  if (WiFi.status() == WL_CONNECTED) {
    wifi_ok = true;
    Serial.println("\nWiFi conectado: " + WiFi.localIP().toString());
  } else {
    wifi_ok = false;
    Serial.println("\nFallo conexión WiFi");
  }
}

bool iniciarCamara() {
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer   = LEDC_TIMER_0;
  config.pin_pwdn     = PWDN_GPIO_NUM;
  config.pin_reset    = RESET_GPIO_NUM;
  config.pin_xclk     = XCLK_GPIO_NUM;
  config.pin_sccb_sda = SIOD_GPIO_NUM;
  config.pin_sccb_scl = SIOC_GPIO_NUM;
  config.pin_d7       = Y9_GPIO_NUM;
  config.pin_d6       = Y8_GPIO_NUM;
  config.pin_d5       = Y7_GPIO_NUM;
  config.pin_d4       = Y6_GPIO_NUM;
  config.pin_d3       = Y5_GPIO_NUM;
  config.pin_d2       = Y4_GPIO_NUM;
  config.pin_d1       = Y3_GPIO_NUM;
  config.pin_d0       = Y2_GPIO_NUM;
  config.pin_vsync    = VSYNC_GPIO_NUM;
  config.pin_href     = HREF_GPIO_NUM;
  config.pin_pclk     = PCLK_GPIO_NUM;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;
  config.frame_size   = FRAMESIZE_QVGA; // tamaño pequeño para ML
  config.jpeg_quality = 12;
  config.fb_count     = 1;

  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("Error cámara: 0x%x\n", err);
    return false;
  }
  Serial.println("Cámara inicializada OK!");
  return true;
}

void handleRoot() {
  server.send_P(200, "text/html", INDEX_HTML);
}

void handleStream() {
  camera_fb_t* fb = esp_camera_fb_get();
  if (!fb || !fb->buf || fb->len == 0) {
    server.send(500, "text/plain", "Error al capturar");
    if (fb) esp_camera_fb_return(fb);
    return;
  }
  server.sendHeader("Content-Type", "image/jpeg");
  server.sendHeader("Cache-Control", "no-cache, no-store, must-revalidate");
  server.send_P(200, "image/jpeg", (const char*)fb->buf, fb->len);
  esp_camera_fb_return(fb);
}

void handleCapture() {
  if (!wifi_ok) { server.send(503, "text/plain", "WiFi no disponible"); return; }
  camera_fb_t* fb = esp_camera_fb_get();
  if (!fb || !fb->buf || fb->len == 0) {
    server.send(500, "text/plain", "Error al capturar");
    if (fb) esp_camera_fb_return(fb);
    return;
  }

  String label = server.hasArg("label") ? server.arg("label") : "";
  int i = server.hasArg("i") ? server.arg("i").toInt() : 0;
  unsigned long ts = millis();
  char fname[64];
  if (label.length() > 0) {
    snprintf(fname, sizeof(fname), "%s_%lu_%d.jpg", label.c_str(), ts, i);
  } else {
    snprintf(fname, sizeof(fname), "img_%lu_%d.jpg", ts, i);
  }

  server.sendHeader("Content-Type", "image/jpeg");
  server.sendHeader("Content-Disposition", String("attachment; filename=\"") + fname + "\"");
  server.sendHeader("X-Filename", fname);
  server.send_P(200, "image/jpeg", (const char*)fb->buf, fb->len);
  esp_camera_fb_return(fb);
}

void handleSetQuality() {
  if (server.hasArg("q")) {
    uint8_t q = constrain(server.arg("q").toInt(), 10, 63);
    jpeg_quality = q;
    sensor_t *s = esp_camera_sensor_get();
    if (s) s->set_quality(s, q);
    server.send(200, "text/plain", String("Calidad: ") + q);
    Serial.printf("Calidad JPEG: %d\n", q);
  } else {
    server.send(400, "text/plain", "Argumento q requerido");
  }
}

void handleSetSize() {
  if (server.hasArg("s")) {
    uint8_t s = server.arg("s").toInt();
    if (s >= 7 && s <= 10) {
      frame_size = (framesize_t)s;
      sensor_t *sensor = esp_camera_sensor_get();
      if (sensor) sensor->set_framesize(sensor, frame_size);
      server.send(200, "text/plain", String("Tamaño: ") + s);
      Serial.printf("Frame size: %d\n", s);
    } else {
      server.send(400, "text/plain", "Tamaño inválido (7-10)");
    }
  } else {
    server.send(400, "text/plain", "Argumento s requerido");
  }
}

void setup() {
  Serial.begin(115200);
  delay(500);
  conectarWiFi();
  if (!iniciarCamara()) {
    Serial.println("ERROR cámara. Verifica pinout/PSRAM");
    while (true) delay(1000);
  }
  server.on("/", HTTP_GET, handleRoot);
  server.on("/stream", HTTP_GET, handleStream);
  server.on("/capture", HTTP_GET, handleCapture);
  server.on("/setquality", HTTP_GET, handleSetQuality);
  server.on("/setsize", HTTP_GET, handleSetSize);
  server.begin();
  Serial.println("Servidor HTTP listo en puerto 80");
}

void loop() {
  if (WiFi.status() != WL_CONNECTED && millis() - lastReconnect > 5000) {
    conectarWiFi();
    lastReconnect = millis();
  }
  server.handleClient();
}
