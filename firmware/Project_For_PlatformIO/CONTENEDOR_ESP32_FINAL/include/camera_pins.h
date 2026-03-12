#pragma once

// Mapa de pines para distintas cámaras.
// Selecciona el modelo definiendo el macro correspondiente ANTES de incluir este archivo.
// Ejemplo en main.cpp:
//   #define CAMERA_MODEL_ESP32S3_EYE
//   #include "camera_pins.h"

// Opción por defecto si no se define nada.
#if !defined(CAMERA_MODEL_ESP32S3_EYE) && \
    !defined(CAMERA_MODEL_ESP32S3_CAM_LCD) && \
    !defined(CAMERA_MODEL_AI_THINKER)
#define CAMERA_MODEL_ESP32S3_EYE
#endif

#if defined(CAMERA_MODEL_ESP32S3_EYE)
// Pinout tomado del ejemplo oficial de Espressif para ESP32-S3-EYE (OV2640)
#define PWDN_GPIO_NUM   -1
#define RESET_GPIO_NUM  -1
#define XCLK_GPIO_NUM   15
#define SIOD_GPIO_NUM    4
#define SIOC_GPIO_NUM    5

#define Y2_GPIO_NUM     11
#define Y3_GPIO_NUM      9
#define Y4_GPIO_NUM      8
#define Y5_GPIO_NUM     10
#define Y6_GPIO_NUM     12
#define Y7_GPIO_NUM     18
#define Y8_GPIO_NUM     17
#define Y9_GPIO_NUM     16

#define VSYNC_GPIO_NUM   6
#define HREF_GPIO_NUM    7
#define PCLK_GPIO_NUM   13

#elif defined(CAMERA_MODEL_ESP32S3_CAM_LCD)
// Pinout para placas ESP32-S3 CAM + LCD (por ejemplo ESP32-S3-Korvo-2)
#define PWDN_GPIO_NUM   -1
#define RESET_GPIO_NUM  -1
#define XCLK_GPIO_NUM   40
#define SIOD_GPIO_NUM   17
#define SIOC_GPIO_NUM   18

#define Y2_GPIO_NUM     13
#define Y3_GPIO_NUM     47
#define Y4_GPIO_NUM     14
#define Y5_GPIO_NUM      3
#define Y6_GPIO_NUM     12
#define Y7_GPIO_NUM     42
#define Y8_GPIO_NUM     41
#define Y9_GPIO_NUM     39

#define VSYNC_GPIO_NUM  21
#define HREF_GPIO_NUM   38
#define PCLK_GPIO_NUM   11

#elif defined(CAMERA_MODEL_AI_THINKER)
// Pinout típico de módulo AI-Thinker ESP32-CAM
#define PWDN_GPIO_NUM   32
#define RESET_GPIO_NUM  -1
#define XCLK_GPIO_NUM    0
#define SIOD_GPIO_NUM   26
#define SIOC_GPIO_NUM   27

#define Y2_GPIO_NUM      5
#define Y3_GPIO_NUM     18
#define Y4_GPIO_NUM     19
#define Y5_GPIO_NUM     21
#define Y6_GPIO_NUM     36
#define Y7_GPIO_NUM     39
#define Y8_GPIO_NUM     34
#define Y9_GPIO_NUM     35

#define VSYNC_GPIO_NUM  25
#define HREF_GPIO_NUM   23
#define PCLK_GPIO_NUM   22

#else
#error "Debes definir un modelo de cámara soportado."
#endif
