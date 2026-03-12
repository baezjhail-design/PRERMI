# Sistema ESP32-S3 CAM + HX711 + Reconocimiento Facial

## Descripción General

Sistema integrado para pesaje de basura con captura de foto y reconocimiento facial:

1. **Bienvenida (10 segundos)**: Pantalla OLED muestra mensaje de bienvenida
2. **Captura de foto**: Después de 10 segundos, cámara captura la foto del usuario
3. **Guardado en servidor**: Foto se envía a servidor PRERMI y se guarda en `uploads/`
4. **Verificación facial** (próxima fase): Sistema verificará rostro contra base de datos
5. **Pesaje**: Espera carga, realiza 100 mediciones promediadas
6. **Registro en BD**: Peso se guarda en tabla `depositos` del servidor

## Flujo de Funcionamiento

### Hardware Requerido
- **ESP32-S3-CAM**: Módulo con cámara OV2640
- **HX711 + Celda de carga**: Para pesaje
- **SSD1306 OLED 128x64**: Pantalla de interfaz
- **WiFi**: Red conectada con IP `10.0.0.162` (PRERMI)

### Flujo de Datos

```
┌─────────────────────┐
│   BIENVENIDA        │
│   10 segundos       │
│   (OLED)            │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   CAPTURAR FOTO     │
│   (Cámara ESP32)    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────┐
│  ENVIAR A SERVIDOR PRERMI   │
│  POST /api/upload_photo.php │
│  (Guardado en uploads/)     │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│  VERIFICACIÓN FACIAL        │
│  (Pendiente: API Python)    │
│  POST /api/verify_face.php  │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────┐
│   ESPERAR PESO      │
│   HX711             │
│   (OLED)            │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   PROMEDIO 100      │
│   MEDICIONES        │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────┐
│  REGISTRAR EN BD            │
│  POST /api/register_weight  │
│  (Tabla: depositos)         │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────┐
│   COMPLETADO        │
│   (OLED)            │
└─────────────────────┘
```

## Archivos Modificados

### 1. `src/main.cpp` (ESP32)
- **Includes agregados**: WiFi, HTTPClient, esp_camera
- **Funciones nuevas**:
  - `initCamera()`: Inicializa cámara con pines configurables
  - `capturePhoto()`: Captura JPEG desde cámara
  - `base64Encode()`: Codifica imagen a base64 para transmisión
  - `uploadPhotoToServer()`: Envía foto a servidor PRERMI
- **Máquina de estados**:
  - `PHASE_CAPTURE`: Captura y sube foto
  - `PHASE_VERIFY`: Espera verificación facial (placeholder)
  - `PHASE_WEIGH`: Obtiene peso (100 mediciones)
  - `PHASE_DONE`: Completado
- **Comentarios extensos**: Cada línea crítica tiene explicación en español

### 2. `api/upload_photo.php` (Servidor PRERMI)
- **Endpoint nuevo**: `/PRERMI/api/upload_photo.php`
- **Entrada**: POST JSON con `photo_b64` (foto en base64)
- **Proceso**:
  1. Decodifica base64
  2. Genera nombre con timestamp
  3. Guarda en `uploads/` con permisos 0755
  4. Registra en logs_sistema
- **Salida**: JSON con `success`, `filename`, `path`, `size_bytes`

### 3. `api/verify_face.php` (Existente)
- **Proxy**: Reenvía solicitud al servicio Python de reconocimiento facial
- **Entrada**: JSON con `image_b64`
- **Destino**: `http://10.0.0.162:5000/verify` (servicio Python)
- **Salida**: Respuesta directa del servicio Python (match, user_id, etc.)

### 4. `api/register_weight.php` (Existente)
- **Inserta depósito**: Guarda peso en tabla `depositos`
- **Entrada**: JSON con `user_id`, `peso` (mínimo requerido)
- **Salida**: JSON con `success` y `deposito_id`

## Configuración

### En `main.cpp` (ESP32):

```cpp
// Líneas 9-11: Editar si es necesario
const char* WIFI_SSID = "Jhail-WIFI"; // Tu SSID
const char* WIFI_PASS = "123.02589."; // Tu contraseña
const char* SERVER_BASE = "http://10.0.0.162"; // IP del servidor PRERMI
```

### Pines de Cámara (líneas 18-42):
Si tu módulo ESP32-S3-CAM tiene pines diferentes, ajusta estas macros:
```cpp
#define XCLK_GPIO_NUM    12  // Puede variar
#define SIOD_GPIO_NUM    13  // Puede variar
#define SIOC_GPIO_NUM    14  // ...
```

Busca documentación de tu módulo específico.

### Factor de Escala HX711 (setup(), línea ≈75):
```cpp
scale.set_scale(2280.f); // Hallar mediante calibración propia
```
Calibración: 
1. Coloca peso conocido (1kg = 1000g)
2. Lee el valor del serial monitor
3. Calcula: `factor = valor_leído / 1000`

## Prueba del Sistema

### Paso 1: Preparar Servidor PRERMI
```bash
# Asegúrate que XAMPP está corriendo
# Verifica que http://10.0.0.162/PRERMI/ es accesible
# Crea carpeta uploads si no existe:
mkdir -p d:\xampp\htdocs\PRERMI\uploads
chmod 755 d:\xampp\htdocs\PRERMI\uploads
```

### Paso 2: Compilar y Subir ESP32
```bash
# En VS Code / PlatformIO
platformio run --target upload
# O ver terminal: "Upload project"
```

### Paso 3: Abrir Monitor Serial
```bash
# En PlatformIO Monitor ver logs:
# [OLED] Display inicializado
# [HX711] Celda de carga inicializada
# [WIFI] Conectado! IP: ...
# [CAMERA] Iniciada correctamente
```

### Paso 4: Ejecutar Ciclo
1. Espera 10 segundos (pantalla "Bienvenido...")
2. Se capturará foto automáticamente
3. Serial mostrará: `[NETWORK] Subiendo foto a: http://...`
4. Respuesta esperada: `[NETWORK] Subiendo foto a... Respuesta: {"success":true,...}`
5. OLED mostrará: "Foto capturada y guardada en uploads"
6. Luego espera peso (OLED: "Coloque basura en la celda")
7. Al colocar basura (>10g): captura 100 mediciones y muestra resultado

### Paso 5: Verificar Foto Guardada
La foto estará en:
```
d:\xampp\htdocs\PRERMI\uploads\photo_20260225142530_5f76ac2e.jpg
```

## Próximos Pasos: Integración de Reconocimiento Facial

### Opción 1: Integración Directa
Reemplazar el placeholder `PHASE_VERIFY` en `main.cpp` con:
```cpp
if (phase == PHASE_VERIFY) {
    // Llamar a verify_face.php con foto capturada
    bool verified = verifyFaceWithServer(capturedJpeg, capturedJpegLen, userIdOut);
    if (verified) {
        phase = PHASE_WEIGH; // Proceder a pesar
    } else {
        phase = PHASE_CAPTURE; // Reintentar captura
    }
}
```

### Opción 2: Servicio Python Externo
1. Crear servicio en `http://10.0.0.162:5000/verify`
2. Debe aceptar POST JSON: `{"image_b64": "..."}`
3. Devolver JSON: `{"match": true/false, "user_id": 123}`
4. Conectar con tabla `usuarios` (campos `id`, imagen facial/embeddings)

## Debugging

### Serial Monitor:
Observar logs con `[TAG]`:
- `[OLED]`: Eventos de pantalla
- `[CAMERA]`: Eventos de cámara
- `[NETWORK]`: Conexiones WiFi/HTTP
- `[PHASE_...]`: Estado de máquina
- `[WIFI]`: Conexión WiFi

### Errores Comunes:

**SSD1306 allocation failed**:
- Verifica pines I2C (SCL_PIN=40, SDA_PIN=41)
- Comprueba dirección I2C (0x3C)

**Camera init failed**:
- Ajusta pines en defines (XCLK_GPIO_NUM, etc.)
- Verifica que esp_camera.h está disponible

**No se conecta a WiFi**:
- Verifica SSID y password exactos
- Revisa que 10.0.0.162 es alcanzable desde ESP32

**Error al subir foto**:
- Verifica que PRERMI es accesible: `http://10.0.0.162/PRERMI/`
- Comprueba permisos en `uploads/` (755 o 777)
- Revisa logs del servidor PHP en terminal

## Resumen de Comentarios en Código

Cada función crítica tiene comentarios explicativos para:
- **Inicialización**: qué configura cada línea
- **Captura**: cómo funciona la API esp_camera
- **Base64**: transformación de datos binarios
- **HTTP**: envío y recepción de datos
- **Máquina de estados**: flujo lógico de fases
- **Pantalla OLED**: mensajes y actualizaciones visuales

Todos los comentarios están **en español** para facilitar comprensión.

---

**Fecha**: 25 de febrero de 2026  
**Versión**: 1.0  
**Estado**: Fase de captura y guardado completado. Pendiente: integración de reconocimiento facial.
