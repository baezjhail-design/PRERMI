# 🚀 GUÍA RÁPIDA DE INICIO - PRERMI

## ✅ Sistema Completamente Implementado

Se han completado **TODOS** los requisitos solicitados:

### 1. **✅ Registro de Usuario con Email de Confirmación**
- Los usuarios se registran en `/web/register.php`
- Los datos se guardan automáticamente en la BD (tabla `usuarios`)
- Se envía un email HTML profesional con:
  - ✅ Token único del usuario
  - ✅ Bienvenida personalizada
  - ✅ Enlace para acceder al sistema

**Pruébalo:**
```
http://localhost:8080/PRERMI/web/register.php
```

---

### 2. **✅ Dashboard de Admin - Monitoreo y Gestión Completa**

**URL:** `http://localhost:8080/PRERMI/web/admin/dashboard.php`

**Acceso:**
- Usuario: `Jhail Baez` (o cualquier admin en BD con `active=1`)
- Contraseña: (la que registraste)

**4 Secciones Principales con TABS:**

#### **PESTAÑA 1: Monitoreo ESP32-CAM**
- 🗺️ Mapa interactivo con todas las capturas
- 📊 Tabla con: placa, tipo, ubicación, fecha, confianza (%)
- 🖼️ Modal para ver imágenes en grande

#### **PESTAÑA 2: Contenedores**
- 🗺️ Mapa con contenedores registrados
- 🎨 Código de color por nivel de basura:
  - Verde: < 50%
  - Amarillo: 50-80%
  - Rojo: > 80%
- 📊 Tabla con ubicación, nivel, coordenadas

#### **PESTAÑA 3: Multas**
- 📋 Tabla de todas las multas registradas
- Usuario afectado
- Descripción y peso

#### **PESTAÑA 4: Logs del Sistema**
- 📝 Historial de eventos
- Clasificados por tipo (error, warning, info)
- Timestamp detallado

---

### 3. **✅ Dashboard de Usuario - Panel Personal Completo**

**URL:** `http://localhost:8080/PRERMI/web/user-dashboard.php`

**Acceso automático después de login:**
```
1. Ir a http://localhost:8080/PRERMI/web/login.php
2. Ingresar usuario y contraseña creados en registro
3. Se abre el dashboard automáticamente
```

**Componentes:**

#### **🔑 Token Único (Prominente)**
- Muestra el UUIDv4 único del usuario
- Botón "Copiar al portapapeles"
- Este token es para desbloquear contenedores

#### **📊 Estadísticas en Grid**
```
- Total de Depósitos
- Peso Total Reciclado (kg)
- Crédito Acumulado (kWh)
- Multas Activas
```

#### **📈 Gráficos Interactivos (Chart.js)**
```
- Gráfico de Barras: Depósitos por Mes
- Gráfico de Pastel: Distribución de Peso por Contenedor
```

#### **📋 Tabla de Historial**
```
Columnas:
- Contenedor
- Ubicación
- Peso (kg)
- Metal Detectado (sí/no)
- Crédito ganado (kWh)
- Fecha y Hora
```

---

## 🧪 Pruebas Rápidas

### **Test #1: Verificar Sistema**
```
http://localhost:8080/PRERMI/test_system.php
```
Muestra el estado de:
- ✅ Conexión BD
- ✅ Tablas
- ✅ Archivos
- ✅ PHPMailer
- ✅ Admins activos

### **Test #2: Instalar BD**
```
http://localhost:8080/PRERMI/instalar_bd.php
```
(Si no existe `prer_mi`)

### **Test #3: APIs**
```
http://localhost:8080/PRERMI/test_apis.php
```
Prueba todos los endpoints

---

## 📁 Estructura de Archivos

```
PRERMI/
├── web/
│   ├── register.php          ← Registro de usuario (AJAX)
│   ├── login.php             ← Login de usuario (AJAX)
│   ├── user-dashboard.php    ← Dashboard de usuario (NUEVO)
│   ├── admin/
│   │   ├── loginA.php        ← Login admin
│   │   └── dashboard.php     ← Dashboard admin (NUEVO)
│
├── api/
│   ├── usuarios/
│   │   ├── register.php      ← Endpoint: registrar usuario
│   │   ├── login.php         ← Endpoint: login usuario
│   │   └── logout.php        ← Endpoint: logout
│   ├── admin/
│   │   ├── loginA_submit.php ← Endpoint: login admin
│   │   └── logout.php        ← Endpoint: logout (NUEVO)
│   └── utils.php             ← Funciones compartidas
│
├── config/
│   ├── db_config.php         ← Credenciales BD
│   └── mailer.php            ← Config de email
│
├── prer_mi.sql               ← Base de datos
├── test_system.php           ← Test visual
└── CAMBIOS_REALIZADOS.md     ← Documentación completa
```

---

## 🔑 Flujos Principales

### **FLUJO 1: Registrar Usuario**
```
1. Ir a /web/register.php
2. Llenar formulario:
   - Nombre, Apellido
   - Usuario (único)
   - Cédula (única)
   - Email (único)
   - Teléfono (opcional)
   - Contraseña (mín. 6 caracteres)
   - Confirmar contraseña
3. Click "Crear Cuenta"
4. AJAX POST a /api/usuarios/register.php
5. ✅ Usuario guardado en BD
6. ✅ Email de confirmación enviado
7. ✅ Redirección automática a login
```

### **FLUJO 2: Login Usuario**
```
1. Ir a /web/login.php
2. Ingresar:
   - Usuario (el creado en registro)
   - Contraseña
3. Click "Iniciar Sesión"
4. AJAX POST a /api/usuarios/login.php
5. ✅ Sesión creada ($_SESSION['user_id'])
6. ✅ Redirección automática a /web/user-dashboard.php
```

### **FLUJO 3: Admin Dashboard**
```
1. Ir a /web/admin/loginA.php
2. Ingresar credenciales admin
3. Click "Acceder al Panel"
4. ✅ Redirección a /web/admin/dashboard.php
5. Visualizar 4 tabs:
   - Monitoreo ESP32
   - Contenedores
   - Multas
   - Logs
```

### **FLUJO 4: Logout**
```
Usuario: Click en "Salir" → Redirige a /web/login.php
Admin:   Click en "Salir" → Redirige a /web/admin/loginA.php
```

---

## 🎨 Diseño Visual

### **Colores**
- **Usuario**: Azul-Púrpura (#667eea → #764ba2)
- **Admin**: Rojo (#ff6b6b → #ff5252)

### **Componentes**
- ✅ Navbars modernas con glassmorphism
- ✅ Tarjetas con efecto hover
- ✅ Tablas interactivas
- ✅ Mapas con Leaflet
- ✅ Gráficos con Chart.js
- ✅ Badges de estado
- ✅ Responsive (móvil, tablet, desktop)

---

## 🔒 Seguridad Implementada

✅ Contraseñas con bcrypt
✅ Token UUIDv4 único
✅ Sesiones server-side
✅ SQL Injection Protection (prepared statements)
✅ CSRF Protection
✅ Validación servidor-side
✅ Headers sanitizados

---

## 📧 Email de Bienvenida

El email que recibe el usuario al registrarse contiene:

```html
¡Bienvenido a PRERMI!

Hola [Nombre Completo],

Tu cuenta ha sido creada exitosamente.
Tu nombre de usuario es: [usuario]

Tu token único para desbloquear contenedores es:
[token UUID]

[Botón: Ir al Login]

Si no creaste esta cuenta, ignora este correo.

PRERMI - Plataforma de Reciclaje y Recolección de Materiales Inteligente
© 2025 Todos los derechos reservados.
```

---

## 🛠️ Mantenimiento

### **Agregar nuevo admin en BD:**
```sql
INSERT INTO usuarios_admin (usuario, email, clave, verified, active, rol)
VALUES ('nuevo_admin', 'email@test.com', '$2y$10$...', 1, 1, 'admin');
```

### **Ver tokens de usuarios:**
```sql
SELECT usuario, token FROM usuarios;
```

### **Ver depósitos de un usuario:**
```sql
SELECT * FROM depositos WHERE user_id = [user_id] ORDER BY creado_en DESC;
```

---

## ⚠️ Notas Importantes

1. **Email Configuration:**
   - El sistema intenta enviar emails con PHPMailer
   - Configura SMTP en `/config/mailer.php` para producción
   - En desarrollo, los errores de email se loguean pero no rompen el registro

2. **Base de Datos:**
   - Usa charset `utf8mb4` (soporta emojis y caracteres especiales)
   - InnoDB para foreign keys
   - `prer_mi` es la BD única (como solicitaste)

3. **Sesiones:**
   - Se almacenan server-side (seguro)
   - Usuario: `$_SESSION['user_id']`, `$_SESSION['usuario']`
   - Admin: `$_SESSION['admin_id']`, `$_SESSION['rol']`

4. **Tokens:**
   - Generados con UUIDv4 (estándar, 36 caracteres)
   - Almacenados en columna `token` de tabla `usuarios`
   - Únicos e irrepetibles

---

## 🚀 Próxima Vez

Para mejorar el sistema:
- [ ] Recuperación de contraseña
- [ ] 2FA (Two-Factor Authentication)
- [ ] Notificaciones push
- [ ] API para móvil
- [ ] Integración IoT ESP32
- [ ] Dashboard estadístico global
- [ ] Exportar reportes PDF

---

## 📞 Soporte Rápido

**¿El registro no envía emails?**
→ Revisa `/config/mailer.php` y configura SMTP

**¿Login no funciona?**
→ Verifica sesiones en `php.ini` (session.save_path)

**¿Dashboard vacío?**
→ Ve a `test_system.php` para verificar datos en BD

**¿Mapas no cargan?**
→ Asegúrate de conexión a internet (Leaflet necesita OSM)

---

**✨ Sistema 100% funcional y listo para usar.**

**Creado:** Diciembre 2025
**Versión:** 1.0 (Producción)
**Estado:** ✅ COMPLETADO
