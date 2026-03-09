# 📊 INFORME DETALLADO - ESTRUCTURA DEL PROYECTO PRERMI

**Fecha de generación**: Enero 2026  
**Proyecto**: PRER_MI - Sistema de Gestión Inteligente de Residuos y Vehículos  
**Estado**: En desarrollo activo con base de datos oficial integrada

---

## 🎯 PROPÓSITO DEL PROYECTO

PRER_MI es un **sistema web completo** para la gestión de:
- **Usuarios**: Registro, autenticación y perfiles
- **Contenedores inteligentes**: Monitoreo, tokens, estados
- **Depósitos de residuos**: Registro de basura depositada por usuarios
- **Vehículos registrados**: Captura y monitoreo mediante ESP32-CAM
- **Administración**: Panel de control para admins con reportes
- **Sistema de multas**: Infracciones detectadas automáticamente

**Stack tecnológico**:
- Backend: PHP 7.4+
- Base de datos: MySQL/MariaDB
- Frontend: Bootstrap 5.3, JavaScript vanilla
- API: RESTful con endpoints para operaciones
- Email: PHPMailer para notificaciones

---

## 📁 ESTRUCTURA GENERAL DE CARPETAS

```
PRERMI/
├── 📄 Archivos de configuración y documentación
├── config/              → Configuración de base de datos y email
├── api/                 → Endpoints RESTful
├── web/                 → Interfaces web (frontend)
├── scripts/             → Scripts de utilidad
├── lib/                 → Librerías externas (PHPMailer)
├── uploads/             → Almacenamiento de archivos subidos
├── logs/                → Archivos de registro del sistema
└── ejemplos/            → Código de ejemplo
```

---

## 📄 ARCHIVOS RAÍZ (DOCUMENTACIÓN Y UTILIDADES)

| Archivo | Propósito |
|---------|-----------|
| `00_LEEME_PRIMERO.txt` | Instrucciones iniciales antes de comenzar |
| `START_HERE.txt` | Punto de entrada rápido del proyecto |
| `README.md` | Documentación general del proyecto |
| `INDEX_ACCESO.txt` | URLs y puntos de acceso del sistema |
| `prer_mi.sql` | **Base de datos oficial completa** con 8 tablas |
| `update_database.sql` | Script de actualización de BD |
| `insertar_datos_prueba.sql` | Datos de prueba para testing |
| `instalar_bd.php` | **Instalador automático de la BD** |
| `verificar_bd_integridad.php` | Valida que la BD esté correctamente instalada |
| `index_herramientas.php` | Panel de control y herramientas principales |
| `test_apis.php` | Tester interactivo de endpoints API |
| `test_email.php` | Prueba de envío de correos |
| `test_system.php` | Pruebas del sistema en general |
| `testing_interactivo.php` | Tests interactivos multifunción |
| `verificacion_v2.php` | Verificación avanzada de componentes |

### Archivos de Documentación

| Archivo | Contenido |
|---------|----------|
| `DB_PRER_MI_SCHEMA.md` | Esquema completo de todas las tablas SQL |
| `INFRAESTRUCTURA_BD.txt` | Descripción de la infraestructura de BD |
| `GUIA_INSTALACION_BD.txt` | Paso a paso para instalar la BD |
| `GUIA_RAPIDA.md` | Guía rápida de funcionamiento |
| `GUIA_TESTING_COMPLETA.txt` | Testing exhaustivo del sistema |
| `CONFIG_SMTP_Y_ADMIN.md` | Configuración de SMTP y creación de admins |
| `NGROK_INSTRUCTIONS.md` | Instrucciones para usar Ngrok (tuneling) |
| `CODIGO_ACTUALIZADO_SQL.md` | Referencia de cambios SQL realizados |
| `FRONTEND_ACTUALIZADO.md` | Referencia de cambios en frontend |
| `INSTRUCCIONES_DATOS_PRUEBA.md` | Cómo insertar datos de prueba |
| `CAMBIOS_REALIZADOS.md` | Historial de cambios del proyecto |
| `RESUMEN_*.md/txt` | Resúmenes ejecutivos de implementación |
| `STATUS.txt` | Estado actual del proyecto |

---

## ⚙️ CARPETA `config/` - CONFIGURACIÓN

Contiene la configuración centralizada del sistema:

```
config/
├── app_config.php          → Configuración general de la aplicación
├── db_config.php           → Credenciales y conexión MySQL
└── mailer.php              → Configuración de PHPMailer (SMTP)
```

### Detalles:

**`db_config.php`**
- Define credenciales MySQL (host, usuario, password, base de datos)
- Exporta función `getPDO()` para conexión centralizada
- Usa PDO para inyección SQL segura

**`app_config.php`**
- Configuraciones globales (timezone, rutas, etc.)
- Variables de aplicación

**`mailer.php`**
- Configuración SMTP para envío de correos
- Usa PHPMailer library
- Credenciales de email del sistema

---

## 🔌 CARPETA `api/` - ENDPOINTS RESTFUL

Contiene todos los endpoints para operaciones CRUD:

```
api/
├── test.php                    → Prueba de conexión API
├── utils.php                   → Funciones utilitarias compartidas
├── admin/                      → Endpoints de administración
│   ├── loginA_submit.php       → Login de admin (POST)
│   ├── registerA_submit.php    → Registro de admin (POST)
│   ├── registerA.php           → Form de registro admin
│   ├── verifyA.php             → Verificación de email admin
│   ├── verifyEmailTokenA.php   → Validación de token de verificación
│   ├── logout.php / logoutA.php→ Cierre de sesión
│   ├── get_pending_admins.php  → Lista de admins pendientes (API)
│   ├── manage_admin_approval.php→ Aprobación de admins
│   └── obtener_admins.php      → Obtiene info de admins
├── usuarios/                   → Endpoints de usuarios normales
│   ├── login.php               → Login usuario (POST)
│   ├── register.php            → Registro usuario (POST)
│   ├── logout.php              → Cierre sesión usuario
│   ├── verifyEmail.php         → Verificación email
│   ├── obtener_usuario.php     → GET datos usuario
│   └── actualizar_usuario.php  → UPDATE perfil usuario
├── contenedores/               → Endpoints de contenedores
│   ├── validar_acceso.php      → Valida acceso a contenedor
│   ├── validar_token_rfid.php  → Valida token RFID del contenedor
│   ├── registrar_peso.php      → Registra peso de depósito
│   ├── registrar_basura.php    → Registra tipo de residuo
│   └── registrar_multa.php     → Registra infracciones
└── vehiculos/                  → Endpoints de vehículos
    ├── upload_esp32cam.php     → Recibe imágenes ESP32-CAM
    ├── obtener_capturas.php    → GET imágenes capturadas
    ├── mapa_config.php         → Configuración de mapa
    └── vehiculos_registrados.php→ Lista de vehículos
```

**Estructura de las APIs**:
- Usan **POST/GET** según operación
- Retornan JSON con estructura: `{"status": "ok", "data": {...}, "error": "..."}`
- Validación de sesión en cada endpoint
- Consultas preparadas con PDO

---

## 🌐 CARPETA `web/` - INTERFACES WEB

Contiene todas las páginas HTML/PHP que ven los usuarios:

```
web/
├── 📁 PÚBLICAS (Sin autenticación)
│   ├── index.php               → Inicio/landing page
│   ├── login.html/login.php    → Login usuario
│   ├── register.html/register.php→ Registro usuario
│   └── manifest.json / sw.js   → Progressive Web App (PWA)
│
├── 📁 admin/                   → Panel de administración
│   ├── loginA.php              → Login admin
│   ├── registerA.php           → Registro admin
│   ├── index.php               → Inicio panel admin
│   ├── dashboard.php           → 📊 Dashboard principal (estadísticas, mapas)
│   ├── admin_dashboard.php     → Dashboard con tabs
│   ├── admin_panelA.php        → Panel de control admin
│   ├── dashboardA.php          → Variante de dashboard
│   ├── panel_admin_approval.php→ Aprobación de nuevos admins
│   ├── usuarios.php            → Gestión de usuarios
│   ├── vehiculos.php           → Gestión de vehículos
│   ├── contenedores.php        → Gestión de contenedores
│   ├── reportes.php            → Generación de reportes
│   ├── configuracion.php       → Configuración del sistema
│   ├── protect.php             → Middleware de protección
│   └── validar_loginA.php      → Validación de login admin
│
├── 📁 usuarios/                → Páginas de usuarios normales
│   ├── dashboard_usuario.php   → Dashboard usuario con historial
│   ├── depositos_usuario.php   → Historial de depósitos
│   ├── multas_usuario.php      → Historial de multas
│   ├── tarjeta_usuario.php     → Tarjeta digital con QR
│   ├── perfil_usuario.php      → Perfil y datos del usuario
│   ├── perfil.php              → Variante de perfil
│   ├── login_usuario.php       → Login específico
│   ├── logout_usuario.php      → Logout
│   ├── register.php            → Registro
│   ├── index_usuario.php       → Inicio usuario
│   ├── sesion_usuario.php      → Gestión de sesión
│   └── estilos_usuario.css     → Estilos específicos del usuario
│
├── 📁 contenedores/            → Páginas de contenedores
│   └── (Sin contenido específico actualmente)
│
├── 📁 monitoreo/               → Monitoreo en tiempo real
│   ├── mapa.php                → Mapa de contenedores
│   └── vehiculos.php           → Mapa de vehículos
│
├── 📁 assets/css/              → Estilos CSS globales
│   └── style.css               → Estilos principales
│
├── 📄 template_header.php      → Header reutilizable
├── 📄 template_footer.php      → Footer reutilizable
├── 📄 validar_loginA.php       → Validación login admin
├── 📄 user-dashboard.php       → Dashboard alternativo usuario
├── 📄 contenedores.php         → Página de contenedores
└── 📄 dashboard.php            → Dashboard (anterior)
```

### Características principales:

- **Responsivo**: Bootstrap 5.3 para mobile-first design
- **Autenticación**: Session-based con validación
- **Mapas**: Leaflet.js para visualización geográfica
- **Charts**: Gráficos de estadísticas
- **PWA**: Progressive Web App (offline capability)
- **QR**: Códigos QR generados con qrserver.com API

---

## 🔧 CARPETA `scripts/` - UTILIDADES

Scripts de línea de comandos y utilidades:

```
scripts/
├── crear_adminA.php    → Crear admin desde CLI
├── test_mail.php       → Prueba de emails
└── test_mailer_cli.php → Prueba avanzada de PHPMailer
```

---

## 📚 CARPETA `lib/` - LIBRERÍAS EXTERNAS

```
lib/
└── PHPMailer/          → Librería PHPMailer para emails
    └── src/            → Código fuente de PHPMailer
```

**PHPMailer**: Popular librería PHP para envío seguro de correos con SMTP.

---

## 📤 CARPETA `uploads/` - ALMACENAMIENTO

```
uploads/
├── usuarios/           → Fotos/documentos de usuarios
├── vehiculos/          → Imágenes capturadas por ESP32-CAM
└── contenedores/       → Imágenes de contenedores
```

Los archivos se guardan organizados por tipo para fácil gestión.

---

## 📋 CARPETA `logs/` - REGISTROS

Contiene logs de:
- Errores del sistema
- Actividades de usuarios
- Intentos de acceso
- Operaciones de BD

---

## 📖 CARPETA `ejemplos/` - CÓDIGO DE EJEMPLO

Contiene ejemplos de:
- Integración de APIs
- Uso de librerías
- Patrones de código

---

## 🗄️ BASE DE DATOS - TABLAS PRINCIPALES

```sql
-- 8 Tablas en total

1. usuarios
   - Usuarios normales del sistema
   - Campos: id, nombre, apellido, usuario, email, cedula, token, clave
   - Relaciones: FK en depositos, multas

2. usuarios_admin
   - Administradores del sistema
   - Campos: id, usuario, email, clave, verification_token, verified, active, rol
   - Estados: activo/inactivo, verificado/no verificado

3. contenedores_registrados
   - Contenedores inteligentes
   - Campos: id, codigo_contenedor, ubicacion, tipo_contenedor, estado, ultimo_token
   - Nuevos campos: token_generado_en, token_expira_en, creado_en, actualizado_en
   - Estados: activo, inactivo, mantenimiento

4. vehiculos_registrados
   - Vehículos capturados por cámara
   - Campos: id, placa, tipo_vehiculo, imagen, ubicacion, fecha, hora, probabilidad
   - Coordenadas: latitud, longitud

5. depositos
   - Registros de basura depositada
   - Campos: id, id_usuario, id_contenedor, peso, tipo_residuo, credito_kwh, fecha_hora, token_usado
   - Relaciones: FK a usuarios y contenedores_registrados

6. multas
   - Infracciones detectadas
   - Campos: id, id_usuario, id_contenedor, descripcion, peso, creado_en
   - Relaciones: FK a usuarios y contenedores

7. logs_sistema
   - Auditoría de actividades
   - Campos: id, usuario_id, accion, descripcion, tipo, fecha_hora

8. configuracion (opcional)
   - Configuraciones globales
   - Campos: clave, valor
```

---

## 🔄 FLUJO DE FUNCIONAMIENTO

### 1. INSTALACIÓN INICIAL

```
1. Abrir: http://localhost/PRERMI/instalar_bd.php
2. Se ejecuta: prer_mi.sql
3. Se crean: 8 tablas con datos iniciales
4. Verificar: http://localhost/PRERMI/verificar_bd_integridad.php
```

### 2. REGISTRO DE USUARIO

```
1. Usuario accede: /web/register.html
2. Envía datos a: /api/usuarios/register.php
3. Se valida email y se envía verificación
4. Usuario confirma: /api/usuarios/verifyEmail.php
5. Perfil activo en: /web/usuarios/
```

### 3. LOGIN DE USUARIO

```
1. Usuario accede: /web/login.php
2. Credentials a: /api/usuarios/login.php
3. Sesión creada: $_SESSION['user_id']
4. Redirige a: /web/user-dashboard.php
```

### 4. DEPÓSITO DE RESIDUO

```
1. Usuario lee QR en contenedor (muestra: usuario_id)
2. Contenedor escanea QR y obtiene: usuario_id
3. Contenedor registra vía: /api/contenedores/registrar_basura.php
4. Se crea registro en: tabla 'depositos'
5. Usuario ve historial en: /web/usuarios/depositos_usuario.php
```

### 5. ADMINISTRACIÓN

```
1. Admin login: /web/admin/loginA.php
2. Dashboard: /web/admin/dashboard.php
3. Visualiza: usuarios, contenedores, vehículos, reportes
4. Gestión: usuarios.php, vehiculos.php, contenedores.php
5. Reportes: reportes.php
```

---

## 📊 CAMBIOS RECIENTES (Enero 2026)

### Migraciones de Base de Datos
- ✅ Columna `contenedor_id` → `id_contenedor`
- ✅ Nuevos campos en `contenedores_registrados`: `codigo_contenedor`, `tipo_contenedor`, `estado`, `ultimo_token`, `token_generado_en`, `token_expira_en`
- ✅ Nuevos campos en `depositos`: `tipo_residuo`, `credito_kwh`, `token_usado`
- ✅ Renombrado en `multas`: `user_id` → corrección de FK

### Cambios en Frontend
- ✅ Eliminación de sección de token en dashboard de usuario
- ✅ Implementación de QR basado en `usuario_id` en tarjeta de usuario
- ✅ Actualización de consultas SQL en todos los dashboards
- ✅ Corrección de referencias de columnas en 8 archivos

### Archivos Modificados Recientemente
- `web/user-dashboard.php` → Eliminadas secciones de token
- `web/usuarios/tarjeta_usuario.php` → QR basado en usuario_id
- `web/usuarios/depositos_usuario.php` → Consultas PDO corregidas
- `web/usuarios/multas_usuario.php` → Consultas PDO corregidas
- `web/usuarios/perfil_usuario.php` → Eliminada visualización de token
- `web/admin/dashboard.php` → Actualización de columnas y consultas

---

## 🚀 ESTADO ACTUAL

✅ **Base de datos oficial integrada**
✅ **Instalador automático funcional**
✅ **Autenticación usuario y admin implementada**
✅ **APIs de usuarios operativas**
✅ **APIs de contenedores en progreso**
✅ **Dashboard admin con estadísticas**
✅ **Sistema de QR implementado**
✅ **Históricos de depósitos y multas**
⏳ **Testing en progreso**

---

## 🔍 CÓMO NAVEGAR EL PROYECTO

### Para Entender Rápidamente
1. Lee: `START_HERE.txt`
2. Lee: `README.md`
3. Ejecuta: `index_herramientas.php`

### Para Entender la BD
1. Lee: `DB_PRER_MI_SCHEMA.md`
2. Lee: `prer_mi.sql`
3. Verifica: `verificar_bd_integridad.php`

### Para Entender el Código
1. Backend: Comienza con `/api/usuarios/login.php`
2. Frontend: Comienza con `/web/index.php` y `/web/usuarios/`
3. Admin: Comienza con `/web/admin/dashboard.php`

### Para Testing
1. Ejecuta: `test_system.php`
2. Ejecuta: `testing_interactivo.php`
3. Usa: `test_apis.php`
4. Verifica email: `test_email.php`

---

## 📝 RESUMEN EJECUTIVO

**PRERMI** es un **sistema completo de gestión de residuos inteligentes** con:

- **8 tablas MySQL** interconectadas
- **2 niveles de usuarios**: normales y administradores
- **API RESTful** para todas las operaciones
- **Interfaz web responsive** con Bootstrap 5.3
- **Sistema de QR** para identificación de usuarios
- **Mapas interactivos** con Leaflet.js
- **Históricos y reportes** de actividades
- **Email notifications** con PHPMailer
- **Auditoría completa** de operaciones

El proyecto está en **estado funcional avanzado** con actualizaciones recientes en estructura de BD y UI para mejorar la experiencia del usuario.

---

*Generado por sistema de análisis - Enero 2026*
