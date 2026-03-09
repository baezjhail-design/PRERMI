═══════════════════════════════════════════════════════════════════════════════
              CONFIGURACIÓN SMTP Y SISTEMA DE VERIFICACIÓN DE ADMINS
═══════════════════════════════════════════════════════════════════════════════

📧 CONFIGURACIÓN DE EMAIL (SMTP)
═══════════════════════════════════════════════════════════════════════════════

El sistema PRERMI requiere SMTP para enviar:
- Confirmación de registro de usuarios
- Tokens de verificación de administradores
- Notificaciones de multas y depositos

## OPCIÓN 1: Mailtrap (RECOMENDADO - GRATIS para pruebas)
═══════════════════════════════════════════════════════════════════════════════

Pasos:
1. Ir a https://mailtrap.io
2. Registrarse con email y contraseña
3. Crear proyecto "PRERMI"
4. Ir a Integrations → PHP/PHPMailer
5. Copiar credenciales:

   SMTP Server: live.smtp.mailtrap.io
   Port: 587
   Username: api
   Password: (Token de tu cuenta - se muestra en el panel)

6. Actualizar /config/mailer.php:

   ```php
   $mail->Host = 'live.smtp.mailtrap.io';
   $mail->Username = 'api';
   $mail->Password = 'tu_token_aqui';  // Reemplaza con tu token
   ```

VENTAJAS:
✓ 500 emails/mes gratis
✓ No necesita dominio propio
✓ Pruebas sin enviar reales
✓ Inbox ficticio para ver todos los emails

DESVENTAJA:
✗ Los emails no llegan a usuarios reales (se capturan en Mailtrap)
✗ Solo para desarrollo


## OPCIÓN 2: Gmail (Producción real)
═══════════════════════════════════════════════════════════════════════════════

Pasos:
1. Acceder a https://myaccount.google.com/
2. Ir a Seguridad → Contraseñas de aplicaciones
   (Requiere 2FA activado)
3. Seleccionar "Correo" y "Windows Computer"
4. Google genera contraseña de 16 caracteres
5. Actualizar /config/mailer.php:

   ```php
   $mail->Host = 'smtp.gmail.com';
   $mail->Port = 465;
   $mail->SMTPSecure = 'ssl';
   $mail->Username = 'tu_email@gmail.com';
   $mail->Password = 'contraseña_de_16_caracteres';
   $mail->setFrom('tu_email@gmail.com', 'PRERMI Sistema');
   ```

VENTAJAS:
✓ Emails reales a usuarios
✓ Servidor confiable (Google)
✓ SMTP seguro (SSL/TLS)

DESVENTAJA:
✗ Límite de 500 emails/día
✗ Requiere 2FA habilitado


## OPCIÓN 3: SendGrid (Profesional)
═══════════════════════════════════════════════════════════════════════════════

Pasos:
1. Registrarse en https://sendgrid.com
2. Crear API key (Settings → API Keys)
3. Actualizar /config/mailer.php:

   ```php
   $mail->Host = 'smtp.sendgrid.net';
   $mail->Port = 587;
   $mail->SMTPSecure = 'tls';
   $mail->Username = 'apikey';  // Literal "apikey"
   $mail->Password = 'SG.tu_api_key_aqui';
   ```

VENTAJAS:
✓ 100 emails/día gratis
✓ Análisis y reportes
✓ IP dedicada en plan profesional
✓ Soporte 24/7

DESVENTAJA:
✗ Plan gratuito limitado


═══════════════════════════════════════════════════════════════════════════════
                    🔧 NUEVO SISTEMA DE VERIFICACIÓN DE ADMIN
═══════════════════════════════════════════════════════════════════════════════

FLUJO DE REGISTRO Y VERIFICACIÓN
═══════════════════════════════════════════════════════════════════════════════

1. REGISTRO DE ADMIN
   Usuario accede a: /web/admin/register.php
   ↓
   Ingresa: usuario, email, nombre, apellido, contraseña
   ↓
   POST a: /api/admin/registerA.php
   ↓
   Sistema:
   - Valida datos (usuario único, email válido, contraseña fuerte)
   - Hash contraseña con bcrypt
   - Genera token de verificación (32 bytes hexadecimal)
   - Insertar en BD: usuarios_admin (verified=0, active=0)
   - Envía email con enlace de verificación

2. VERIFICACIÓN DE EMAIL
   Usuario recibe email con enlace:
   /api/admin/verifyEmailTokenA.php?token=xxxxx
   ↓
   Usuario hace clic
   ↓
   Sistema:
   - Busca admin con ese token
   - Actualiza: verified=1, limpia token
   - Muestra página de "Verificación Exitosa"
   - Estado: Esperando aprobación de superadmin

3. APROBACIÓN DE SUPERADMIN
   Superadmin accede a: /web/admin/panel_admin_approval.php
   ↓
   Ve tabla con admins pendientes (verified=1, active=0)
   ↓
   Opciones:
   - ✓ APROBAR: active=1 → Acceso concedido
   - ✗ RECHAZAR: Eliminan del sistema
   ↓
   Admin aprobado puede hacer login normalmente

═══════════════════════════════════════════════════════════════════════════════
                           CAMBIOS EN LA BASE DE DATOS
═══════════════════════════════════════════════════════════════════════════════

Ejecutar este SQL para agregar campos:

```sql
ALTER TABLE `usuarios_admin` 
ADD COLUMN `nombre` varchar(100) DEFAULT '' AFTER `usuario`,
ADD COLUMN `apellido` varchar(100) DEFAULT '' AFTER `nombre`;

UPDATE `usuarios_admin` SET 
    `nombre` = 'Jhail',
    `apellido` = 'Baez'
WHERE `id` = 2;
```

CAMPOS ACTUALES:
- id: int (PK)
- usuario: varchar(50) UNIQUE
- nombre: varchar(100) [NUEVO]
- apellido: varchar(100) [NUEVO]
- email: varchar(120) UNIQUE
- clave: varchar(255)
- verification_token: varchar(255) [Token para verificar email]
- verified: tinyint (0=no verificado, 1=verificado)
- active: tinyint (0=no aprobado, 1=aprobado por superadmin)
- rol: enum(superadmin, admin)
- creado_en: timestamp

═══════════════════════════════════════════════════════════════════════════════
                         🔐 SISTEMA DE TOKEN RFID
═══════════════════════════════════════════════════════════════════════════════

EL SISTEMA AHORA SOPORTA LECTURA DE TOKENS RFID/NFC

FLUJO:
1. Usuario registrado recibe ÚNICO token (UUIDv4)
   - Ej: "550e8400-e29b-41d4-a716-446655440000"
   - Se muestra en dashboard usuario
   - Se puede imprimir en tarjeta RFID

2. Contenedor tiene lector NFC (PN532 o similar)
   - Detecta cuando usuario acerca tarjeta/teléfono
   - Lee el UID o token NDEF de la tarjeta

3. ESP32 envía POST a:
   /api/contenedores/validar_token_rfid.php
   
   Datos:
   {
     "token": "550e8400-e29b-41d4-a716-446655440000",
     "contenedor_id": 1
   }

4. Sistema valida:
   - ¿Existe usuario con este token?
   - ¿Token está activo (token_activo=1)?
   - ¿Contenedor existe?
   ↓
   Respuesta JSON:
   {
     "success": true,
     "user_id": 5,
     "user_name": "Juan Pérez",
     "contenedor_id": 1,
     "ubicacion": "Av. Principal"
   }

5. ESP32 actúa según respuesta:
   - ✓ Token válido: LED verde, buzzer corto
   - ✗ Token inválido: LED rojo, buzzer largo

═══════════════════════════════════════════════════════════════════════════════
                    IMPLEMENTACIÓN CON ESP32 Y RFID
═══════════════════════════════════════════════════════════════════════════════

Ver archivo: /ejemplos/esp32_rfid_example.ino

COMPONENTES REQUERIDOS:
├── ESP32 (NodeMCU-32S o similar)
├── Lector NFC PN532 (I2C mode)
├── LED verde (GPIO 21)
├── LED rojo (GPIO 22)
├── Buzzer (GPIO 23)
├── Fuente 5V 2A
└── Cables jumper y protoboard

CONEXIONES:
PN532_SCL  → ESP32 GPIO 22
PN532_SDA  → ESP32 GPIO 21
PN532_VCC  → ESP32 5V (o 3.3V con regulador)
PN532_GND  → ESP32 GND

LIBRERÍAS ARDUINO:
- Adafruit PN532
- ArduinoJson 6.x
- WiFi (builtin)
- HTTPClient (builtin)

PASOS:
1. Instalar librerías en Arduino IDE
2. Copiar código del ejemplo
3. Cambiar SSID y PASSWORD de WiFi
4. Cambiar serverURL a tu IP/dominio
5. Cambiar CONTENEDOR_ID
6. Cambiar GPIOs según tu conexión
7. Subir al ESP32

PRUEBA:
- Acerca tarjeta RFID (debe tener UID o URL NDEF)
- Monitor serial muestra: Token y respuesta del servidor
- LED indica resultado

═══════════════════════════════════════════════════════════════════════════════
                        📋 LISTA DE NUEVOS ARCHIVOS
═══════════════════════════════════════════════════════════════════════════════

CREADOS:
✓ /web/admin/register.php          - Formulario de registro admin
✓ /web/admin/panel_admin_approval.php - Panel de gestión de admins
✓ /api/admin/registerA.php         - Endpoint de registro admin
✓ /api/admin/verifyEmailTokenA.php - Verificación de email
✓ /api/admin/manage_admin_approval.php - Aprobar/rechazar admins
✓ /api/admin/get_pending_admins.php - Obtener admins pendientes
✓ /api/contenedores/validar_token_rfid.php - Validar token RFID
✓ /ejemplos/esp32_rfid_example.ino - Código Arduino para ESP32
✓ /config/mailer.php               - Actualizado con Mailtrap
✓ /web/admin/loginA.php            - Actualizado con enlace registro
✓ /web/admin/dashboard.php         - Actualizado con tab de admins

═══════════════════════════════════════════════════════════════════════════════
                         🧪 PRUEBAS Y VERIFICACIÓN
═══════════════════════════════════════════════════════════════════════════════

PROBAR REGISTRO DE ADMIN:
1. Abrir: http://localhost:8080/PRERMI/web/admin/loginA.php
2. Hacer clic en "Solicitar acceso"
3. Llenar formulario con:
   - Usuario: test_admin
   - Email: tu_email@mailtrap.io (o Gmail)
   - Nombre: Test
   - Apellido: Admin
   - Contraseña: TestPass123!
4. Enviar
5. Ir a Mailtrap inbox
6. Hacer clic en enlace de verificación
7. Mensaje: "Verificación Exitosa - Esperando aprobación"

PROBAR APROBACIÓN:
1. Login como superadmin (usuario: Jhail_ADMIN_GOD)
2. Ir al Dashboard
3. Hacer clic en tab "Administradores"
4. Ver nuevo admin en "Pendientes"
5. Hacer clic "Aprobar"
6. Confirmación en pantalla
7. Ahora el nuevo admin puede hacer login

PROBAR RFID (SIN HARDWARE):
1. curl -X POST http://localhost:8080/PRERMI/api/contenedores/validar_token_rfid.php \
   -H "Content-Type: application/json" \
   -d '{"token": "550e8400-e29b-41d4-a716-446655440000", "contenedor_id": 1}'
2. Respuesta esperada:
   {
     "success": true,
     "user_id": 1,
     "user_name": "Usuario Nombre",
     ...
   }

═══════════════════════════════════════════════════════════════════════════════
                            🚀 PRÓXIMOS PASOS
═══════════════════════════════════════════════════════════════════════════════

1. CONFIGURAR SMTP según tu preferencia (Mailtrap, Gmail o SendGrid)
2. EJECUTAR script SQL para actualizar tabla usuarios_admin
3. PROBAR flujo completo de registro y verificación
4. COMPILAR código ESP32 si tienes hardware RFID
5. VERIFICAR logs en /logs/email_errors.log si algo falla
6. MONITOREAR en /test_system.php

═══════════════════════════════════════════════════════════════════════════════

Documentación actualizada: Diciembre 9, 2025
Versión: 2.0 (Con sistema de verificación y RFID)
