# DOCUMENTACIÓN - ENDPOINTS Y CAMPOS DE ESP32-S3 CAM

## 📋 ENDPOINTS CENTRALIZADOS

Todos los endpoints están definidos al inicio del código para fácil modificación:

```cpp
// Línea 35-39 en main.cpp
const char* serverAPI = "http://10.0.0.162:8080/PRERMI/api";
const char* ENDPOINT_REGISTRAR_DEPOSITOS = "/contenedores/registrar_depositos.php";
const char* ENDPOINT_REGISTRAR_SANCION = "/sanciones/crear_sancion_auto.php";
```

---

## 1️⃣ ENDPOINT: REGISTRAR DEPÓSITOS

**URL Completa**: `http://10.0.0.162:8080/PRERMI/api/contenedores/registrar_depositos.php`

**Localización en Código**: `sendWeightData()` función inicio línea ~340

**Método HTTP**: POST

**Content-Type**: `application/json; charset=utf-8`

### Campos Enviados:

| Campo | Tipo | Requerido | Descripción | Valor Ejemplo |
|-------|------|-----------|-------------|---------------|
| `usuario_id` | int | ✅ SÍ | ID del usuario que deposita | `4` |
| `peso` | float | ✅ SÍ | Peso en kg medido por HX711 | `2.294434` |
| `id_contenedor` | int | ✅ SÍ | ID del contenedor fijo Santiago | `1` |
| `metal_detectado` | int | ❌ NO | 0=no, 1=sí (detectó metal) | `0` |
| `kwh` | float | ❌ NO | Equivalente en pesos a reducir (se envía aquí en lugar de kWh) | `0.013881` |
| `cost_rd` | float | ❌ NO | Costo en pesos (se mantiene por compatibilidad) | `0.013881` |
| `tipo_residuo` | string | ❌ NO | "organico" o "metal", calculado según sensor | `"organico"` |
| `procesado_por` | string | ❌ NO | Siempre "Administrador" | `"Administrador"` |
| `observaciones` | string | ❌ NO | Siempre "N/A" | `"N/A"` |
| `timestamp` | long | ❌ NO | Timestamp del dispositivo en ms | `36862` |

### JSON Ejemplo:
```json
{
  "usuario_id": 4,
  "peso": 2.294434,
  "id_contenedor": 1,
  "metal_detectado": 0,
  "tipo_residuo": "organico",
  "kwh": 0.013881,
  "cost_rd": 0.013881,
  "procesado_por": "Administrador",
  "observaciones": "N/A",
  "timestamp": 36862
}
```

### Respuestas Esperadas:

**✅ HTTP 200 - Éxito**
```json
{
  "success": true,
  "deposito_id": 5,
  "usuario_id": 4,
  "peso_kg": 2.294434,
  "kwh": 0.002524,
  "costo_rd": 0.013881,
  "contenedor_id": 1,
  "ubicacion": "Santiago de los Caballeros",
  "mensaje": "Depósito registrado exitosamente"
}
```

**❌ HTTP 404 - Endpoint No Encontrado**
- Verificar que la URL sea correcta
- Verificar que PRERMI esté bien escrito (no "PREMI")

**❌ HTTP 500 - Error del Servidor**
- Verificar que usuario_id=4 existe en tabla usuarios
- Verificar que id_contenedor=1 existe en contenedores_registrados
- Revisar logs PHP del servidor

---

## 2️⃣ ENDPOINT: REGISTRAR SANCIÓN

**URL Completa**: `http://10.0.0.162:8080/PRERMI/api/sanciones/crear_sancion_auto.php`

**Localización en Código**: `sendSanction()` función inicio línea ~415

**Método HTTP**: POST

**Content-Type**: `application/json`

### Campos Enviados:

| Campo | Tipo | Requerido | Descripción | Valor Ejemplo |
|-------|------|-----------|-------------|---------------|
| `user_id` | int | ✅ SÍ | ID del usuario infractor | `4` |
| `contenedor_id` | int | ✅ SÍ | ID del contenedor asociado (Santiago=1) | `1` |
| `descripcion` | string | ✅ SÍ | Motivo / descripción de la sanción | `"Metal detectado"` |
| `peso` | float | ❌ NO | Peso del objeto detectado, si aplica | `2.29` |
| `timestamp` | long | ❌ NO | Timestamp de la infracción | `36862` |

### JSON Ejemplo:
```json
{
  "user_id": 4,
  "contenedor_id": 1,
  "descripcion": "Metal detectado",
  "peso": 2.29,
  "timestamp": 36862
}
```

### Respuesta Esperada:

**✅ HTTP 200 - Éxito**
```json
{
  "success": true,
  "sancion_id": 12,
  "user_id": 4,
  "mensaje": "Sanción registrada correctamente"
}
```

---

## 🔧 CÓMO MODIFICAR ENDPOINTS

### Opción 1: Cambiar la IP del Servidor
```cpp
// Línea 34
const char* serverAPI = "http://10.0.0.162:8080/PRERMI/api";
//Cambiar a:
const char* serverAPI = "http://10.0.0.200:8080/PRERMI/api"; // Nueva IP
```

### Opción 2: Cambiar el Path de un Endpoint
```cpp
// Línea 37
const char* ENDPOINT_REGISTRAR_DEPOSITOS = "/contenedores/registrar_depositos.php";
// Cambiar a:
const char* ENDPOINT_REGISTRAR_DEPOSITOS = "/api/v2/depositos.php"; // Nuevo path
```

### Opción 3: Agregar Nuevo Endpoint
```cpp
// Línea 39 (agregar después del último endpoint)
const char* ENDPOINT_VERIFICAR_USUARIO = "/usuarios/verificar.php";
const char* ENDPOINT_UPLOAD_FOTO = "/contenedores/subir_foto.php";
```

**NOTA**: NO es necesario modificar las funciones `sendWeightData()` o `sendSanction()`. Solo modificar las constantes de endpoints al inicio.

---

## 📊 FLUJO DE DATOS COMPLETO

```
┌─────────────────────────────────────┐
│ ESP32-S3 CAM CICLO DE DEPOSITO      │
└─────────────────────────────────────┘
         │
         ├─→ [1] Abrir Compuerta
         │        (Servo: 0° → 90°)
         │
         ├─→ [2] Medir Peso
         │        (HX711: 100 muestras en 20s)
         │        Resultado: 2.29 kg
         │
         ├─→ [3] Cerrar Compuerta
         │        (Servo: 90° → 0°)
         │
         ├─→ [4] Detectar Metal (activo, sensor inductivo)
         │        - Sensor con salida 0 (LOW) sólo al detectar metal; se usa pull-up
         │          para mantenerlo en 1 y evitar falsos positivos cuando está abierto.
         │        - Si se detecta metal (lógico 0) se genera sanción y no se registra depósito
         │        - El campo `tipo_residuo` se determina según este sensor
         │
         ├─→ [5] Convertir Datos
         │        peso (kg) → kwh → cost_rd
         │        2.29 kg → 0.00252 kwh → 0.0139 RD
         │
         ├─→ [6] ENVIAR DEPOSITO ⭐
         │        POST /contenedores/registrar_depositos.php
         │        JSON:{usuario_id, peso, id_contenedor, kwh, cost_rd, ...}
         │        ↓
         │        ✅ HTTP 200 - Guardado en BD
         │        │ tabla: depositos
         │        │ id_usuario=4, id_contenedor=1, peso=2.29, ...
         │
         ├─→ [7] Mostrar Éxito
         │        "GRACIAS POR ELEGIR PRERMI"
         │        LED verde (3 segundos)
         │
         └─→ [8] Reiniciar
                  ESP.restart()
```

---

## ✅ VERIFICACIÓN EN phpMyAdmin

### Base de Datos
```
Servidor: 127.0.0.1
Base de Datos: prer_mi
Usuario: root
```

### Tabla: `depositos`
```sql
SELECT * FROM depositos 
WHERE id_usuario = 4 
AND id_contenedor = 1 
ORDER BY fecha_hora DESC 
LIMIT 10;
```

Debería mostrar:
- `id_usuario` = 4
- `id_contenedor` = 1
- `peso` = 2.29 (aproximadamente)
- `metal_detectado` = 0
- `fecha_hora` = Fecha y hora del depósito
- `creado_en` = Timestamp exacto

---

## 🐛 TROUBLESHOOTING

| Error | Causa | Solución |
|-------|-------|----------|
| HTTP 404 | Ruta incorrecta | Verificar ENDPOINT y serverAPI |
| HTTP 500 | FK constraint | Verificar usuario_id y id_contenedor existan |
| Sin respuesta | Conexión WiFi | Verificar que IP servidor sea correcta |
| Datos incompletos | JSON inválido | Verificar serializeJson en ESP32 |

---

## 📝 ÚLTIMAS MODIFICACIONES

**Fecha**: 2026-03-02
**Cambios**:
- ✅ Endpoints centralizados en líneas 35-39
- ✅ Documentación de campos en cada función
- ✅ JSON completo con todos los parámetros
- ✅ Respuestas HTTP mejoradas con diagnósticos

**Estado**: ✅ FUNCIONAL - Depositando correctamente en BD
