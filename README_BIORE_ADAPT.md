# PRERMI — BIOMASA / BIORES Adaptación

Resumen de cambios realizados por el asistente:

- Nuevo endpoint `PRERMI/api/biores.php` — controlador bidireccional:
  - GET: entrega comando actual para el ESP32 (campo `accion` => `start|stop|emergency|none`).
  - POST: recibe JSON desde ESP32 con `temperatura`, `corriente`, `calentador`, `ventilador` y actualiza `api/status.json`.

- Nuevo endpoint `PRERMI/api/control.php` — API REST para la web:
  - GET: devuelve contenido de `control.json`.
  - POST: acepta `{ "command":"start_generacion"|"stop_generacion"|"emergency_off" }` y lo guarda en `api/control.json`.

- Nuevo endpoint `PRERMI/api/status.php` — devuelve `status.json` y `control.json` combinados.

- Archivos JSON: `api/control.json` y `api/status.json` iniciales para persistencia ligera.

- Firmware ejemplo: `PRERMI/firmware/ESP32_S3_main.cpp` (Arduino) que:
  - Conecta a Wi‑Fi, consulta periódicamente `biores.php` (GET) para recibir comandos.
  - Envía estado de sensores vía POST a `biores.php`.
  - Aplica comandos: `start` enciende calefactor, `stop` lo apaga, `emergency` apaga todo.

- UI web: `PRERMI/web/biores_ui.html` con polling a `/api/status.php` y botones que llaman `/api/control.php`.

Token / seguridad
- Si existe el archivo `PRERMI/config/token.txt` se exigirá que solicitudes incluyan `token` (GET param o en JSON POST).
- Si no existe, los endpoints aceptan peticiones sin token (modo permisivo para laboratorio). Esto simplifica pruebas y evita bloqueos innecesarios.

Integración y pruebas rápidas
- Poner el ESP32_S3 firmware en PlatformIO/Arduino y ajustar `SSID`, `PASS`, `SERVER_HOST` en `PRERMI/firmware/ESP32_S3_main.cpp`.
- Abrir UI en: http://<server>/PRERMI/web/biores_ui.html
- Control desde UI: botones envían comandos a `/PRERMI/api/control.php`.
- ESP32 al hacer GET a `/PRERMI/api/biores.php` recibirá `accion` y aplicará la orden.

Notas y mejoras sugeridas
- Para producción sustituir persistencia en JSON por una tabla en la base de datos.
- Añadir autenticación HTTP (API keys) o JWT según requisitos.
- Mejorar lectura de corriente (ADC) en el firmware y añadir reintentos exponenciales.
