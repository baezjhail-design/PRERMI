# 🔥 BIOMASA - Sistema de Control Inteligente

## 📋 Descripción General

Sistema de control automático para biomasa que monitorea:
- 🌡️ **Temperatura** usando sensor DS18B20 (OneWire)
- ⚡ **Corriente** usando ACS712-5A (Sensor Hall)
- 🔥 **Calentador** (Relé controlable)
- 💨 **Ventilador** (Relé controlable)

---

## 🖥️ HARDWARE ACTUAL

### Microcontrolador
- **Placa:** ESP32-S3 CAM (freenove_esp32_s3_wroom)
- **Procesador:** Dual-core Xtensa 32-bit LX6
- **Frecuencia:** 240 MHz
- **RAM:** 8 MB
- **Flash:** 4 MB + cámara integrada

### Sensores
| Componente | Modelo | GPIO | Protocolo |
|-----------|--------|------|-----------|
| Temperatura | DS18B20 | 6 | OneWire |
| Corriente | ACS712-5A | 7 | ADC (Analógico) |
| Relé Calentador | Genérico | 4 | Digital OUT |
| Relé Ventilador | Genérico | 5 | Digital OUT |

---

## 🚀 INICIO RÁPIDO

### 1. Instalación de dependencias
```bash
cd "BIOMASA TEST"
platformio lib install
```

### 2. Conectar hardware
Ver documentación en `CONFIGURACION_S3_CAM.md`

### 3. Compilar y subir
```bash
platformio run --target upload
```

### 4. Monitorear
```bash
platformio device monitor -b 115200
```

---

## 📊 PROTOCOLO DE COMUNICACIÓN

### Envío a Servidor
```json
{
  "accion": "estado_completo",
  "temperatura": 28.5,
  "corriente": 2.3,
  "energia": 125.4,
  "sistema": 0,
  "calentador": 0,
  "ventilador": 0
}
```

### Autenticación
```
POST /PRERMI/BIOMASA/sensores_estado.php?token=esp8266_sensor_token
```

---

## 📂 ESTRUCTURA DEL PROYECTO

```
BIOMASA TEST/
├── src/
│   └── main.cpp                  ← Código principal Arduino
├── platformio.ini                ← Configuración PlatformIO (S3 CAM)
├── CONFIGURACION_S3_CAM.md       ← Pinout y especificaciones
├── MIGRACION_DEVKIT1.md          ← Cambios S3→DEVKIT1 (referencia)
├── REFERENCIA_DEVKIT1.md         ← Config DEVKIT1 guardada
├── DIAGNOSTICO_SENSORES.md       ← Troubleshooting
└── README.md                     ← Este archivo
```

---

## 🔌 CONEXIÓN RÁPIDA

```
ESP32-S3 CAM
├── GPIO 4 ──→ Relé Calentador
├── GPIO 5 ──→ Relé Ventilador
├── GPIO 6 ──→ DS18B20 (+ Pull-up 4.7kΩ)
├── GPIO 7 ──→ ACS712 OUT
├── 3.3V ────→ Sensores VDD
└── GND ─────→ Sensores GND
```

---

## 📈 CARACTERÍSTICAS

- ✅ Lectura de temperatura en tiempo real
- ✅ Monitoreo de corriente y energía generada
- ✅ Control automático de relés
- ✅ Conexión WiFi a servidor PRERMI
- ✅ Umbral automático de temperatura (30°C)
- ✅ Diagnóstico de hardware al iniciar
- ✅ Tolerancia a fallos de sensores
- ✅ Muestreo multiuestra para estabilidad

---

## 🧪 PRUEBAS

### Test Básico
1. Conectar S3 CAM
2. Abrir Serial Monitor (115200)
3. Verificar mensaje inicial
4. Verificar diagnóstico de sensores

### Test de Conectividad
```curl
curl "http://192.168.1.106:8080/PRERMI/BIOMASA/sensores_estado.php?token=esp8266_sensor_token&accion=estado_completo"
```

---

## 📚 DOCUMENTACIÓN

- **CONFIGURACION_S3_CAM.md** - Especificaciones técnicas y pinout
- **REFERENCIA_DEVKIT1.md** - Configuración alternativa DEVKIT1 (guardada)
- **MIGRACION_DEVKIT1.md** - Pasos para cambiar a DEVKIT1
- **DIAGNOSTICO_SENSORES.md** - Troubleshooting y verificación

---

## 🔄 CAMBIO DE HARDWARE

### Para cambiar a ESP32 DEVKIT1:
Ver documento [REFERENCIA_DEVKIT1.md](REFERENCIA_DEVKIT1.md) que tiene toda la configuración guardada.

---

## 🔧 CAMBIOS RECIENTES

### v2.1 (22/02/2026) - Vuelta a S3 CAM
- ✅ Revertido a ESP32-S3 CAM original
- ✅ Token: `esp8266_sensor_token` 
- ✅ Configuración guardada para DEVKIT1 en REFERENCIA_DEVKIT1.md
- ✅ Logger PlatformIO actualizado

### v2.0 (22/02/2026) - Migración a DEVKIT1
- Temporalmente migrado a ESP32 DEVKIT1
- Configuración guardada para referencia

### v1.0 - Inicial
- Configuración ESP32-S3 CAM

---

## ⚠️ LIMITACIONES S3 CAM

- GPIO 6-11: Usados para memoria flash (limitación física)
- Pines GPIO limitados (~10-15 disponibles)
- Precio más elevado que DEVKIT1

---

## 🤝 Soporte

Para problemas:
1. Revisar `DIAGNOSTICO_SENSORES.md`
2. Verificar conexiones en esta página
3. Consultar logs del Serial Monitor

---

**Última actualización:** 22/02/2026  
**Hardware Actual:** ESP32-S3 CAM  
**Hardware Alternativo:** ESP32 DEVKIT1 (config guardada)  
**Estado:** ✅ Operativo

