# 🔧 DIAGNÓSTICO DE SENSORES - ESP32-S3 CAM

## ⚠️ PROBLEMAS IDENTIFICADOS Y SOLUCIONADOS

### 1. **Error: "Acción inválida"** ✅ RESUELTO
- **Causa**: Arduino enviaba `"accion":"estado_completo"` pero el PHP no lo reconocía
- **Solución**: Actualizado `sensores_estado.php` para aceptar esta acción

### 2. **Sensor de Temperatura Desconectado** 🔴 VERIFICAR CONEXIÓN
- **Causa**: Pueden ser 2 problemas:
  - GPIO 6 no tiene el sensor DS18B20 conectado
  - Conexión defectuosa o inverted (GND/VDD al revés)

### 3. **Corriente Negativa (-8.9A)** ✅ PARCIALMENTE RESUELTO
- **Causa**: Fórmula de offset incorrecta y ruido ADC
- **Soluciones aplicadas**:
  - ✅ Aumentado a 20 muestras (antes 10)
  - ✅ Dead band mejorado (150mA threshold)
  - ✅ Configuración ADC con `analogSetAttenuation(ADC_11db)`

---

## 📋 CHECKLIST DE CONEXIONES

### Sensor DS18B20 (OneWire)
```
Pin 1 (GND)    ────→ GPIO GND
Pin 2 (DATA)   ────→ GPIO 6   ← CON PULL-UP 4.7kΩ a 3.3V
Pin 3 (VDD)    ────→ GPIO 3.3V
```

**VERIFY**: ¿Tienes una resistencia de 4.7kΩ entre GPIO 6 y 3.3V?

### Sensor de Corriente ACS712-5A
```
GND    ────→ GPIO GND
OUT    ────→ GPIO 7   ← Entrada ADC
VCC    ────→ GPIO 3.3V
```

**VERIFY**: ¿El sensor está en GPIO 7 (ADC1_CH6)?

---

## 🧪 PRUEBAS A REALIZAR

### Después de subir el código:

1. **Abre el Serial Monitor** (115200 baud)

2. **Revisa el diagnóstico inicial**:
   ```
   === SISTEMA BIOMASA LISTO ===
   📊 DIAGNÓSTICO DE HARDWARE:
      Sensores OneWire detectados: [número]
      Voltaje ADC (GPIO 7): [voltaje]
   ```

3. **Si "Sensores OneWire detectados" = 0**:
   - ❌ El sensor DS18B20 NO está conectado en GPIO 6
   - Verifica:
     - ¿Está en GPIO 6 específicamente?
     - ¿El pull-up de 4.7kΩ está presente?
     - ¿VDD recibe 3.3V?
     - ¿GND está conectado a tierra?

4. **Si "Voltaje ADC" está entre 1.5V y 1.8V**:
   - ✅ Sensor de corriente está correctamente polarizado
   - ✅ Cuando haya carga, debe cambiar

5. **Valores esperados de corriente**:
   - Sin carga: 0.0 A (con dead band de 150mA)
   - Con carga pequeña: +0.5 a +2.0 A
   - Nunca debe ser negativo si todo está bien

---

## 🔌 ALTERNATIVAS DE PINES (si GPIO 6 está ocupado)

```
Para TEMP_SENSOR (OneWire):  GPIO 8, 9, 15, 16, 18, 19 (evita 0,1,2)
Para CURRENT_SENSOR (ADC):   GPIO 3, 8, 9, 10, 11, 12, 13, 14
```

---

## 📊 ENVÍO DE DATOS AL SERVIDOR

Ahora el Arduino envía:
```json
{
  "accion": "estado_completo",
  "temperatura": [valor],
  "corriente": [valor],
  "energia": [valor],
  "sistema": [0|1],
  "calentador": [0|1],
  "ventilador": [0|1]
}
```

**Respuesta esperada**:
```json
{"status":"ok", "msg":"Estado actualizado", "data":{...}}
```

---

## 🚀 PRÓXIMOS PASOS

1. Sube el código actualizado
2. Abre el Serial Monitor y verifica el diagnóstico
3. Si aún fallan sensores, comenta aquí con:
   - Los valores del diagnóstico
   - Fotos de la conexión física
   - Especificación exacta de sensores usados
