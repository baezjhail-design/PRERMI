## ⚡ GUÍA RÁPIDA - PROBAR EL SISTEMA ACTUALIZADO

### 🎯 Objetivo
Verificar que el firmware actualizado funciona correctamente con biores.php

### ✅ CHECKLIST PRE-PRUEBA

- [x] Firmware compilado exitosamente
- [x] Firmware subido a ESP32-S3 CAM
- [ ] Serial Monitor listo (115200 baud)
- [ ] biores.php accesible en http://localhost:8080/PRERMI/web/admin/biores.php
- [ ] WiFi Jhail-WIFI disponible

---

## 🚀 PASOS DE PRUEBA

### PASO 1: Abrir Serial Monitor
```bash
platformio device monitor -b 115200
```

**Esperado ver:**
```
╔════════════════════════════════════╗
║  BIOMASA - ESP32-S3 CAM           ║
║  Integrado con biores.php         ║
╚════════════════════════════════════╝

🔗 Conectando a WiFi: Jhail-WIFI
........
✅ WiFi conectado. IP: 192.168.1.106
   RSSI: -45 dBm

📊 DIAGNÓSTICO DE HARDWARE (S3 CAM):
   ✓ Sensores OneWire detectados: 1
   ✓ Voltaje ADC (GPIO 7): 1.65 V
```

### PASO 2: Acceder a biores.php
```
URL: http://localhost:8080/PRERMI/web/admin/biores.php
```

**Esperado ver:**
- LED indicador en ROJO (apagado)
- Estado: "Detenido"
- 3 tarjetas de sensores (Temperatura, Ventilador, Corriente)
- 2 botones: "▶ Iniciar Generación" y "⏹ Detener Sistema"

### PASO 3: Clic en "▶ Iniciar Generación"

**En biores.php:**
- Botón se deshabilita
- Mensaje "📡 Iniciando sistema..."
- Después 3-4 segundos el mensaje cambia a "✅ Comando enviado"

**En Serial Monitor (≈3 segundos):**
```
╔═══════════════════════════════════════════════════════════╗
║ 📩 COMANDO RECIBIDO DESDE BIORES.PHP
╠═══════════════════════════════════════════════════════════╣
║ Tipo: inicio
║ Descripción: Iniciar sistema BIOMASA
╚═══════════════════════════════════════════════════════════╝

✅ Sistema INICIADO desde biores.php
💨 Ventilador ENCENDIDO
🔥 Calentador ENCENDIDO
```

**En biores.php (después de 3 seg):**
- LED pasa a VERDE ✅
- Estado: "En Operación"
- Bombillas se encienden
- Sensores muestran valores

### PASO 4: Observar envío de datos

**En Serial Monitor (cada 5 segundos):**
```
📤 Datos enviados a biores.php (HTTP 200)
```

**En biores.php:**
- Sensores se actualizan automáticamente
- Gráficos comienzan a mostrar datos históricos

### PASO 5: Clic en "⏹ Detener Sistema"

**En biores.php:**
- Botón se deshabilita
- Mensaje "🛑 Deteniendo..."
- Después 3-4 segundos: "✅ Comando enviado"

**En Serial Monitor:**
```
╔═══════════════════════════════════════════════════════════╗
║ 📩 COMANDO RECIBIDO DESDE BIORES.PHP
╠═══════════════════════════════════════════════════════════╣
║ Tipo: parada
║ Descripción: Detener sistema BIOMASA
╚═══════════════════════════════════════════════════════════╝

✅ Sistema DETENIDO desde biores.php
🛑 Ventilador APAGADO
❄️  Calentador APAGADO
```

**En biores.php:**
- LED pasa a ROJO ❌
- Estado: "Detenido"
- Bombillas se apagan
- Sensores muestran "N/A"

---

## 🔍 VERIFICACIONES ADICIONALES

### Test: Botones de Emergencia
1. Sistema debe estar ACTIVO (LED verde)
2. Clic en "⚠️ Apagado Emergencia" de cualquier sensor
3. Debe pedirte confirmación
4. Sistema debe apagar (LED rojo)

### Test: Polling cada 3 segundos
1. En Serial Monitor NO deberías ver mensajes de polling
2. Solo si hay comando encolado
3. Si ves muchos mensajes de error → WiFi conectivididad

### Test: Envío de datos cada 5 segundos
1. En Serial Monitor debes ver:
```
🌡️  Temperatura: 28.45 °C
⚡ Corriente: 0.05 A
📤 Datos enviados a biores.php (HTTP 200)
```

---

## ⚠️ TROUBLESHOOTING

### Serial Monitor Vacío
**Problema:** No ves nada en Serial Monitor
**Solución:**
- [ ] Verificar puerto COM ¿Es COM7?
- [ ] Verificar baud rate ¿Es 115200?
- [ ] Desconectar/reconectar USB
- [ ] Reiniciar PC

### LEDs no se actualizan
**Problema:** LEDs en biores.php no cambian
**Solución:**
- [ ] Verificar que Arduino está conectado a WiFi (Serial Monitor)
- [ ] Verificar que URL está correcta
- [ ] Limpiar caché del navegador (Ctrl+F5)

### Comandos no se ejecutan
**Problema:** Click en botón pero nada pasa
**Solución:**
- [ ] Verificar Serial Monitor → ¿Hay mensajes de error?
- [ ] ¿Arduino está conectado a WiFi?
- [ ] Revisar permisos de `/PRERMI/data/comandos/`
- [ ] ¿Cola de comandos llena?

### WiFi desconectado
**Problema:** Serial Monitor muestra "WiFi desconectado"
**Solución:**
- [ ] Verificar SSID en main.cpp line 18: "Jhail-WIFI"
- [ ] Verificar password en main.cpp line 19: "123.02589."
- [ ] ¿Router está funcionando?
- [ ] ¿Arduino está en rango?

---

## 📊 DIAGNÓSTICO MANUAL

### Ver estado de sensores
```bash
# Terminal en servidor
cat /PRERMI/data/sensores/estado.json
```

### Ver cola de comandos
```bash
# Terminal en servidor
cat /PRERMI/data/comandos/comandos_pendientes.json
```

### Limpiar cola
```bash
echo '[]' > /PRERMI/data/comandos/comandos_pendientes.json
```

---

## ✅ RESULTADO ESPERADO FINAL

Si todo funciona correctamente deberías ver:

**Serial Monitor:**
- ✅ WiFi conectado
- ✅ Diagnóstico OK
- ✅ Comandos recibidos en formato visual
- ✅ Relés encendiendo/apagando
- ✅ Datos enviados HTTP 200

**biores.php:**
- ✅ LEDs indicadores cambiando (ROJO ↔ VERDE)
- ✅ Bombillas animadas prendiendo/apagando
- ✅ Sensores actualizándose
- ✅ Gráficos mostrando datos históricos
- ✅ Botones respondiendo

**Tiempo total:**
- Click a inicio → LED verde en 3-4 segundos
- Click a parada → LED rojo en 3-4 segundos

---

## 🎉 ¡LISTO!

Si pasas todas las verificaciones, el sistema está **100% funcional** y listo para usar en producción.

**Próximos pasos:**
- Calibración de sensores (si aplica)
- Tests de carga
- Documentación de procedimientos operacionales
- Capacitación de usuarios

---

**Fecha:** 22/02/2026
**Versión firmware:** 2.0
**Estado:** ✅ LISTO PARA PRODUCCIÓN
