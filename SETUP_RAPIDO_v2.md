═══════════════════════════════════════════════════════════════════════════════
                   🚀 GUÍA RÁPIDA - PONER EN MARCHA v2.0
═══════════════════════════════════════════════════════════════════════════════

⚠️  REQUISITOS ANTES DE COMENZAR:
   1. XAMPP corriendo (Apache + MySQL)
   2. Base de datos prer_mi importada
   3. PHP 8.2+

═══════════════════════════════════════════════════════════════════════════════
                        1️⃣ CONFIGURAR SMTP (5 MIN)
═══════════════════════════════════════════════════════════════════════════════

OPCIÓN A: Mailtrap (RECOMENDADO para pruebas)
───────────────────────────────────────────────
1. Ir a https://mailtrap.io/signin
2. Registrarse GRATIS
3. Ir a Dashboard → Buscar "Integration" o tu proyecto
4. Copiar datos (Host, User, Password)
5. Editar: d:\xampp\htdocs\PRERMI\config\mailer.php
   
   Cambiar línea 13 (Password):
   $mail->Password = 'AQUI_PEGA_TU_PASSWORD_DE_MAILTRAP';

6. ✓ HECHO - Los emails se capturan en Mailtrap (no llegan reales)

OPCIÓN B: Gmail (para envíos reales)
────────────────────────────────────
1. Abrir: https://myaccount.google.com/
2. Buscar "Seguridad" en el menú
3. Activar 2FA (si no está)
4. Ir a "Contraseñas de aplicaciones"
5. Generar contraseña para "Correo"
6. Copiar los 16 caracteres
7. Editar: d:\xampp\htdocs\PRERMI\config\mailer.php
   
   Descomenta las líneas de Gmail (líneas 18-24):
   
   $mail->Host = 'smtp.gmail.com';
   $mail->Port = 465;
   $mail->SMTPSecure = 'ssl';
   $mail->Username = 'tu_email@gmail.com';
   $mail->Password = 'LOS_16_CARACTERES_AQUI';

8. ✓ HECHO - Los usuarios reciben REALES los emails

═══════════════════════════════════════════════════════════════════════════════
                     2️⃣ ACTUALIZAR BASE DE DATOS (2 MIN)
═══════════════════════════════════════════════════════════════════════════════

Abrir PhpMyAdmin:
1. http://localhost/phpmyadmin
2. Seleccionar BD "prer_mi"
3. Tab "SQL"
4. Copiar y pegar este SQL:

───────────────────────────────────────────────
ALTER TABLE `usuarios_admin` 
ADD COLUMN `nombre` varchar(100) DEFAULT '' AFTER `usuario`,
ADD COLUMN `apellido` varchar(100) DEFAULT '' AFTER `nombre`;

UPDATE `usuarios_admin` SET 
    `nombre` = 'Jhail',
    `apellido` = 'Baez'
WHERE `id` = 2;

UPDATE `usuarios_admin` SET 
    `nombre` = 'Jhail',
    `apellido` = 'Admin'
WHERE `id` = 3;
───────────────────────────────────────────────

5. Clic "Ejecutar"
6. ✓ HECHO - Tabla actualizada con campos nombre y apellido

═══════════════════════════════════════════════════════════════════════════════
                    3️⃣ PROBAR FLUJO DE REGISTRO (10 MIN)
═══════════════════════════════════════════════════════════════════════════════

PASO 1: Abrir página de registro
├─ URL: http://localhost:8080/PRERMI/web/admin/loginA.php
├─ Clic en "Solicitar acceso"
└─ Se abre: /web/admin/register.php

PASO 2: Llenar formulario
├─ Usuario: test_admin_nuevo
├─ Email: (IMPORTANTE: usa tu email REAL si es Gmail, o cualquiera si es Mailtrap)
├─ Nombre: Juan
├─ Apellido: Pérez
├─ Contraseña: TestPass123!
│  (Mínimo 8 caracteres, con mayúsculas, minúsculas, números, símbolos)
└─ Clic "Crear Cuenta de Admin"

PASO 3: Verificar email
├─ Si MAILTRAP:
│  └─ Ir a: https://mailtrap.io → Inbox
│     └─ Ver email con botón "Verificar Correo"
│     └─ Hacer clic en el botón
│
├─ Si GMAIL:
│  └─ Abrir bandeja de entrada (tu email)
│  └─ Buscar email de PRERMI
│  └─ Hacer clic en "Verificar Correo"
│
└─ Verás: "✓ ¡Verificación Exitosa!"

PASO 4: Aprobar como superadmin
├─ Login como: Jhail Baez (usuario existente)
│  └─ Usuario: Jhail Baez
│  └─ Contraseña: (la del sistema actual)
├─ Ir a Dashboard
├─ Tab "Administradores"
└─ Ver nuevo admin → Clic "Aprobar"

PASO 5: Probar login del nuevo admin
├─ Logout (arriba a la derecha)
├─ Login con las credenciales nuevas:
│  └─ Usuario: test_admin_nuevo
│  └─ Contraseña: TestPass123!
├─ ✓ DEBE ENTRAR AL DASHBOARD
└─ ¡ÉXITO!

═══════════════════════════════════════════════════════════════════════════════
                    4️⃣ PROBAR SISTEMA RFID (OPCIONAL)
═══════════════════════════════════════════════════════════════════════════════

SIN HARDWARE - Usar curl en PowerShell:

1. Abrir PowerShell
2. Copiar y ejecutar este comando:

Invoke-WebRequest -Uri "http://localhost:8080/PRERMI/api/contenedores/validar_token_rfid.php" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body (@{
    token="550e8400-e29b-41d4-a716-446655440000"
    contenedor_id=1
  } | ConvertTo-Json)

3. Verás respuesta JSON:
   {
     "success": false,
     "message": "Token no válido"
   }
   
   (Es normal, el token es ejemplo. Con un token real de BD sería "success": true)

4. Probar con token REAL:
   - Ir a /web/user-dashboard.php (login como usuario)
   - Copiar el token mostrado en la caja roja
   - Reemplazar en el comando anterior
   - Ahora debería responder: "success": true

CON HARDWARE ESP32 + RFID:
├─ Ver archivo: /ejemplos/esp32_rfid_example.ino
├─ Cambiar WiFi y serverURL
├─ Subir al ESP32 con Arduino IDE
└─ Acercar tarjeta RFID → LED + Buzzer responden

═══════════════════════════════════════════════════════════════════════════════
                       5️⃣ ARCHIVOS IMPORTANTES (REFERENCIA)
═══════════════════════════════════════════════════════════════════════════════

CONFIG:
├─ /config/mailer.php              ← EDITAR: Credenciales SMTP

PÁGINAS:
├─ /web/admin/loginA.php           ← Ahora con enlace a registro
├─ /web/admin/register.php         ← NUEVA: Registro de admin
├─ /web/admin/dashboard.php        ← NUEVA TAB: Administradores
├─ /web/admin/panel_admin_approval.php ← NUEVA: Gestión de admins

API:
├─ /api/admin/registerA.php        ← NUEVA: Registrar admin
├─ /api/admin/verifyEmailTokenA.php ← NUEVA: Verificar email
├─ /api/admin/manage_admin_approval.php ← NUEVA: Aprobar/rechazar
├─ /api/admin/get_pending_admins.php ← NUEVA: Listar pendientes
├─ /api/contenedores/validar_token_rfid.php ← NUEVA: Validar RFID

EJEMPLOS:
├─ /ejemplos/esp32_rfid_example.ino ← NUEVA: Código Arduino

DOCUMENTACIÓN:
├─ /STATUS.txt                     ← Estado general (este archivo)
├─ /CONFIG_SMTP_Y_ADMIN.md        ← Doc detallada v2.0
├─ /CAMBIOS_REALIZADOS.md         ← Cambios v1.0
├─ /GUIA_RAPIDA.md                ← Guía v1.0

═══════════════════════════════════════════════════════════════════════════════
                    🆘 TROUBLESHOOTING RÁPIDO
═══════════════════════════════════════════════════════════════════════════════

❌ "Error enviando email"
   → Verificar credenciales SMTP en /config/mailer.php
   → Probar con Mailtrap primero
   → Ver logs en /logs/email_errors.log

❌ "Token no encontrado"
   → Ejecutar SQL en PhpMyAdmin
   → Verificar que columnas `nombre` y `apellido` existan
   → Hacer REFRESH en navegador

❌ "Página blanca"
   → Ver errores en: http://localhost/phpmyadmin
   → O en navegador: Ctrl+F12 → Tab "Console"
   → Verificar que BD esté en línea

❌ "Admin no aparece en aprobación"
   → Verificar en BD: SELECT * FROM usuarios_admin
   → Comprobar que verified=1 y active=0
   → Ser SUPERADMIN (rol="superadmin")

❌ "RFID no funciona"
   → Primero probar sin hardware (ver sección 4)
   → Compilar Arduino con cambios de WiFi
   → Revisar conexión I2C del PN532
   → Ver serial monitor en Arduino IDE

═══════════════════════════════════════════════════════════════════════════════
                        ✅ CHECKLIST FINAL
═══════════════════════════════════════════════════════════════════════════════

Antes de considerar "listo para producción":

□ SMTP configurado y probado (email enviado exitosamente)
□ Base de datos actualizada (columnas nombre/apellido agregadas)
□ Registro de admin funciona (usuario puede registrarse)
□ Email de verificación llega
□ Token de verificación funciona (enlace en email)
□ Superadmin puede aprobar admins
□ Nuevo admin puede hacer login
□ Panel de administradores muestra estadísticas correctas
□ RFID API responde JSON correctamente (con o sin hardware)
□ Logs en /logs/email_errors.log están limpios (sin errores recientes)
□ /test_system.php muestra TODO ✓ (excepto si no hay datos)

═══════════════════════════════════════════════════════════════════════════════
                        📞 SOPORTE Y PRÓXIMOS PASOS
═══════════════════════════════════════════════════════════════════════════════

ESTÁ LISTO:
✓ Sistema de registro y verificación de usuarios (v1.0)
✓ Sistema de registro y verificación de admins (v2.0 - NUEVO)
✓ Dashboards con gráficos y mapas
✓ Validación de tokens RFID en contenedores
✓ Documentación completa

PRÓXIMOS PASOS (OPCIONALES):
□ Implementar recuperación de contraseña
□ Agregar 2FA (códigos SMS o Google Authenticator)
□ API REST sin sesiones (JWT tokens) para móvil
□ Módulo de reportes en PDF
□ Integración con PLC para abrir contenedores
□ Notificaciones push cuando hay multas
□ Dashboard público/análisis de datos

═══════════════════════════════════════════════════════════════════════════════

                    🎉 ¡PROYECTO LISTO PARA USAR!

        Configura SMTP, actualiza BD, prueba flujos y ¡está listo!

═══════════════════════════════════════════════════════════════════════════════

Guía actualizada: Diciembre 9, 2025
Versión: 2.0
Tiempo de setup: ~30 minutos (incluye SMTP config)
