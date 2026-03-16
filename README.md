# PRERMI — Sistema de Gestión de Residuos con IoT

> **Proyecto académico** — Plataforma web + firmware IoT para la gestión inteligente de residuos mediante reconocimiento facial, monitoreo de biomasa y control remoto.

---

## Tabla de Contenidos
1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Stack Tecnológico](#stack-tecnológico)
4. [Estructura del Proyecto](#estructura-del-proyecto)
5. [Seguridad Implementada](#seguridad-implementada)
6. [Firmware MCU](#firmware-mcu)
7. [Despliegue en Producción](#despliegue-en-producción)
8. [Endpoints de la API](#endpoints-de-la-api)
9. [Notas de Desarrollo](#notas-de-desarrollo)

---

## Descripción General

PRERMI integra dos sistemas físicos basados en microcontroladores con una plataforma web centralizada:

- **Contenedor inteligente** (ESP32-S3 CAM): identifica usuarios por reconocimiento facial, mide el peso del residuo depositado y registra depósitos automáticamente.
- **Módulo Biomasa** (ESP8266MOD OLED): monitorea la generación de energía a través de celdas Peltier, temperatura, corriente y ciclos de generación.
- **Panel web**: gestión de usuarios, sanciones, reportes QR, estado de sensores y control remoto de dispositivos.

---

## Arquitectura del Sistema

```
[ESP32-S3 CAM]  ──┐
                  │  HTTP/HTTPS  ┌─────────────────────────────┐
[ESP8266 OLED]  ──┼────────────►│  VPS Ubuntu 22.04 LTS       │
                  │              │  Apache 2.4 + PHP 8.2        │
[Navegadores]  ───┘              │  MySQL + Python 3.12         │
                                 │  prermi.duckdns.org          │
                                 └─────────────────────────────┘
```

---

## Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Servidor | Ubuntu 22.04 LTS, Apache 2.4.52 |
| Backend | PHP 8.2 |
| Base de datos | MySQL 8 |
| Visión artificial | Python 3.12, OpenCV (LBPH) |
| Email | PHPMailer + SMTP Gmail |
| Firmware contenedor | ESP32-S3 CAM (PlatformIO / Arduino) |
| Firmware biomasa | ESP8266MOD OLED (PlatformIO / Arduino) |
| Dominio | DuckDNS (prermi.duckdns.org) |

---

## Estructura del Proyecto

```
PRERMI/
├── api/
│   ├── security.php              ← Capa de seguridad central (MCU + Dev Auth)
│   ├── contenedores/             ← Endpoints del contenedor inteligente
│   ├── sensores/                 ← Registro de datos de sensores
│   ├── sanciones/                ← Creación automática de sanciones
│   ├── usuarios/                 ← Verificación y registro de usuarios
│   └── admin/                    ← Endpoints de administración
├── config/
│   ├── db_config.php             ← Configuración de base de datos
│   └── mailer.php                ← Configuración SMTP
├── python/
│   └── face_verify.py            ← Motor de reconocimiento facial (LBPH)
├── QRV/                          ← Sistema de códigos QR
├── BIOMASA/                      ← Panel de control biomasa
├── firmware/
│   ├── TEST_CONTENEDOR/          ← Firmware ESP32-S3 CAM
│   └── BIOMASA_FINAL_CODE/       ← Firmware ESP8266MOD OLED
└── logs/
    └── security.log              ← Registro de intentos de acceso no autorizados
```

---

## Seguridad Implementada

### 1. Autenticación de Dispositivos MCU (IoT)

Se implementó una capa de autenticación basada en cabeceras HTTP personalizadas. **Cada microcontrolador posee un identificador único y una clave secreta** que debe enviar en cada solicitud a la API.

```
X-MCU-ID:  <identificador del dispositivo>
X-MCU-KEY: <clave secreta del dispositivo>
```

La validación se centraliza en `api/security.php` mediante la función `requireMCUAccess()`. Cualquier solicitud que no incluya cabeceras válidas recibe una respuesta `403 Forbidden` inmediata, sin revelar información sobre el esquema de autenticación.

**Dispositivos registrados en el sistema:**
| Dispositivo | Rol |
|-------------|-----|
| ESP32-S3 CAM | Contenedor inteligente (reconocimiento facial, pesos, depósitos) |
| ESP8266MOD OLED | Módulo biomasa (temperatura, corriente, ciclos de energía) |

### 2. Control de Acceso para Desarrolladores

Los endpoints de administración y diagnóstico están protegidos mediante autenticación HTTP Basic Auth (`requireDevAccess()`). Solo los miembros del equipo de desarrollo autorizados pueden acceder a estos recursos.

### 3. Registro de Intentos No Autorizados

Todos los accesos rechazados se escriben en `logs/security.log` con:
- Timestamp del intento
- IP de origen
- Cabeceras MCU presentadas (si las hay)
- Endpoint solicitado

Esto permite auditar intentos de acceso indebido sin exponer información sensible al solicitante.

### 4. Protección de Directorios

El archivo `.htaccess` aplica:
- `Options -Indexes` — desactiva el listado de directorios
- Protección de archivos de configuración sensibles (`.env`, `db_config.php`, `*.sql`)
- Cabeceras de seguridad HTTP (`X-Frame-Options`, `X-Content-Type-Options`)

### 5. Separación de Credenciales

Las credenciales de base de datos, claves SMTP y tokens de dispositivos **no están expuestos en el código fuente público**. Se consumen desde archivos de configuración con permisos restringidos en el servidor.

### 6. Variables de Entorno para Python

El módulo de reconocimiento facial (`face_verify.py`) utiliza `PRERMI_BASE_PATH` como variable de entorno para determinar la ruta de trabajo, evitando rutas absolutas hardcodeadas y facilitando el despliegue en distintos entornos.

---

## Firmware MCU

### Cambios de Seguridad en Firmware (v2.0 — Producción VPS)

Ambos firmwares fueron actualizados para el despliegue en VPS con dominio público.

#### ESP32-S3 CAM (`TEST_CONTENEDOR`)
- `serverAPI` actualizado a dominio VPS (`prermi.duckdns.org`)
- Añadidas constantes `MCU_DEVICE_ID` y `MCU_DEVICE_KEY`
- Cabeceras `X-MCU-ID` y `X-MCU-KEY` inyectadas en:
  - `httpPostJSON()` — envío de datos JSON (depósitos, sanciones, pesos)
  - `fetchUserName()` — consulta de nombre de usuario por ID
  - `verifyIdentityWithFace()` — envío de imagen base64 para reconocimiento facial (raw TCP)
- Endpoint de verificación facial actualizado al servidor VPS

#### ESP8266MOD OLED (`BIOMASA_FINAL_CODE`)
- `SERVER_HOST_LOCAL` actualizado a dominio VPS
- Añadidas constantes `MCU_DEVICE_ID` y `MCU_DEVICE_KEY`
- Cabeceras `X-MCU-ID` y `X-MCU-KEY` inyectadas en:
  - `syncWithServer()` — sincronización de sensores y recepción de comandos remotos
  - `registrarCicloEnergia()` — registro de ciclos de generación de energía

#### Dependencias de Firmware (PlatformIO)
```
ESP32-S3:  ESP32Servo, Adafruit SSD1306, HX711, ArduinoJson, esp_camera
ESP8266:   ESP8266WiFi, ESP8266HTTPClient, DallasTemperature, Adafruit SSD1306
```

---

## Despliegue en Producción

### Requisitos del Servidor
- Ubuntu 22.04 LTS
- Apache 2.4 con `mod_rewrite` y `mod_headers` habilitados
- PHP 8.2 (`ppa:ondrej/php`)
- MySQL 8
- Python 3.12 (`ppa:deadsnakes/ppa`) + `opencv-contrib-python-headless`
- Certbot (snap) para certificado SSL de Let's Encrypt

### Base de Datos
```
Base de datos : prer_mi
Tablas        : 14 (usuarios, contenedores, depositos, multas, sanciones, sensores, ...)
```

### Variable de Entorno Recomendada
```bash
PRERMI_BASE_PATH=/var/www/html/PRERMI
```

### Certificado SSL
```bash
snap install --classic certbot
certbot --apache -d prermi.duckdns.org
```

---

## Endpoints de la API

Todos los endpoints bajo `/api/contenedores/`, `/api/sensores/`, `/api/sanciones/` y `/api/usuarios/verificar_rostro.php` requieren autenticación MCU válida mediante cabeceras `X-MCU-ID` y `X-MCU-KEY`.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/contenedores/registrar_depositos.php` | Registra un depósito de residuo |
| POST | `/api/contenedores/verificar_rostro.php` | Verifica identidad por imagen facial |
| POST | `/api/contenedores/validar_acceso.php` | Valida acceso al contenedor |
| POST | `/api/contenedores/registrar_peso.php` | Registra medición de peso |
| POST | `/api/contenedores/registrar_basura.php` | Registra tipo de basura |
| POST | `/api/contenedores/registrar_multa.php` | Registra multa por uso incorrecto |
| POST | `/api/sanciones/crear_sancion_auto.php` | Crea sanción automática |
| GET  | `/api/sensores/registrar.php` | Registra lectura de sensores (biomasa) |
| POST | `/api/usuarios/verificar_rostro.php` | Verificación facial desde panel web |

---

## Notas de Desarrollo

- El módulo de InfinityFree (hosting gratuito alternativo) fue **desactivado temporalmente** durante la migración al VPS. El código mantiene comentarios para reactivarlo si fuera necesario.
- El bloque HTTPS raw en `verifyIdentityWithFace()` (ESP32-S3) está condicionado con `if (false)` y puede reactivarse con `WiFiClientSecure` cuando el certificado SSL esté completamente activo.
- La función `buildURL()` en el firmware ESP8266 omite el puerto si es el 80 estándar, manteniendo URLs limpias.
- Los logs de seguridad se gestionan manualmente; se recomienda configurar `logrotate` en producción para evitar archivos de gran tamaño.
- El reconocimiento facial utiliza el método LBPH (Local Binary Pattern Histograms) de OpenCV, entrenado con muestras locales por usuario.

---

*Desarrollado por Jhail Baez & Adrian Espinal — 2026*
