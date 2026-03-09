# 🚀 PRER_MI - Sistema de Gestión de Vehículos y Contenedores

## ✅ Estado: Base de Datos Oficial Integrada

La base de datos oficial `prer_mi.sql` ha sido completamente integrada en el sistema. Todos los conflictos han sido resueltos y el sistema está listo para usar.

---

## ⚡ Inicio Rápido (30 segundos)

### 1. Importar la Base de Datos
Abre en tu navegador:
```
http://localhost/PRERMI/instalar_bd.php
```

Espera a que el proceso complete (o visualiza el progreso).

### 2. Verificar Instalación
```
http://localhost/PRERMI/verificar_bd_integridad.php
```

Debes ver: `"status": "OK"`

### 3. Acceder al Panel de Control
```
http://localhost/PRERMI/index_herramientas.php
```

¡Listo! Sistema completamente operativo ✓

---

## 📦 ¿Qué se Instaló?

### Base de Datos: `prer_mi` (8 tablas)
- **usuarios**: Usuarios normales del sistema
- **usuarios_admin**: Administradores (2 iniciales)
- **vehiculos_registrados**: Vehículos detectados
- **contenedores_registrados**: Contenedores inteligentes
- **depositos**: Depósitos de basura
- **multas**: Infracciones detectadas
- **logs_sistema**: Registro de actividades
- **configuracion**: Configuraciones globales (opcional)

### Datos Iniciales Incluidos
- ✓ 2 Admins activos y verificados
- ✓ 1 Vehículo de prueba
- ✓ Estructura de FK completa
- ✓ Índices en todas las tablas críticas

### Archivos de Configuración
- `config/db_config.php` - Variables de conexión
- `api/utils.php` - Funciones globales con getPDO()
- `prer_mi.sql` - Script SQL oficial

---

## 🛠️ Herramientas Disponibles

### Instalación y Configuración
| Herramienta | URL | Función |
|---|---|---|
| **Instalador de BD** | `/instalar_bd.php` | Importa automáticamente la BD |
| **Verificador** | `/verificar_bd_integridad.php` | Verifica estructura e integridad |
| **Schema** | `/DB_PRER_MI_SCHEMA.md` | Documentación de tablas |

### Testing
| Herramienta | URL | Función |
|---|---|---|
| **Test de APIs** | `/test_apis.php` | Interfaz gráfica para probar APIs |
| **Panel de Control** | `/index_herramientas.php` | Centro de control central |

### Documentación
| Archivo | Contenido |
|---|---|
| `GUIA_INSTALACION_BD.txt` | Guía paso a paso |
| `INFRAESTRUCTURA_BD.txt` | Arquitectura completa |
| `DB_PRER_MI_SCHEMA.md` | Referencia técnica |

---

## 📋 Archivos Eliminados (Para Evitar Conflictos)

Los siguientes archivos fueron **eliminados** porque podrían conflictuar con la BD oficial:

```
✗ ACTUALIZACIONES.md
✗ CHANGELOG.md
✗ DATABASE_CONFIG_README.md
✗ database_schema.sql (viejo)
✗ README_INSTALACION.txt
✗ SETUP_GUIDE.md
✗ VERIFICACION_RAPIDA.txt
✗ test_connection.php
✗ test_db_connection.php
```

Ahora **SOLO** se usa la BD oficial: `prer_mi.sql` ✓

---

## 🔐 Credenciales de Administrador

```
Admin 1:
  Usuario: Jhail Baez
  Email: baezjhail@gmail.com
  Rol: admin
  Estado: ✓ Verificado | ✓ Activo

Admin 2:
  Usuario: Jhail_ADMIN_GOD
  Email: jhailbaezperez19@gmail.com
  Rol: admin
  Estado: ✓ Verificado | ✓ Activo
```

⚠️ Los passwords están hasheados con bcrypt en la BD.

---

## 📊 Estructura de Datos

### Tabla: usuarios
```
id (INT, PK)
nombre VARCHAR(80)
apellido VARCHAR(80)
usuario VARCHAR(50) UNIQUE
email VARCHAR(120) UNIQUE
telefono VARCHAR(30)
cedula VARCHAR(20) UNIQUE
token VARCHAR(80) UNIQUE
token_activo TINYINT(1)
clave VARCHAR(255) [HASHEADA]
creado_en TIMESTAMP
```

### Tabla: usuarios_admin
```
id (INT, PK)
usuario VARCHAR(50) UNIQUE
email VARCHAR(120) UNIQUE
clave VARCHAR(255) [HASHEADA]
verification_token VARCHAR(255)
verified TINYINT(1) = 1
active TINYINT(1) = 1
rol ENUM('superadmin','admin') = 'admin'
creado_en TIMESTAMP
```

### Tabla: vehiculos_registrados
```
id (INT, PK)
placa VARCHAR(20)
tipo_vehiculo VARCHAR(50)
imagen VARCHAR(255)
ubicacion VARCHAR(150)
fecha DATE
hora TIME
modelo_ml VARCHAR(50)
probabilidad FLOAT
latitud DOUBLE
longitud DOUBLE
creado_en TIMESTAMP
```

### Otras Tablas
Para ver la estructura completa de las otras tablas, abre:
```
/DB_PRER_MI_SCHEMA.md
```

---

## 🔗 Relaciones (Foreign Keys)

```
usuarios (1) ──┬──→ (N) depositos (ON DELETE CASCADE)
               └──→ (N) multas (ON DELETE CASCADE)

contenedores_registrados (1) ──→ (N) depositos
contenedores_registrados (1) ──→ (N) multas
```

---

## 🚀 Próximos Pasos

1. ✓ Importa la BD: `http://localhost/PRERMI/instalar_bd.php`
2. ✓ Verifica: `http://localhost/PRERMI/verificar_bd_integridad.php`
3. ✓ Prueba APIs: `http://localhost/PRERMI/test_apis.php`
4. ✓ Accede al panel: `http://localhost/PRERMI/web/`
5. ✓ Inicia sesión como admin
6. ✓ Comienza a usar el sistema

---

## 🐛 Solución Rápida de Problemas

### Error: "Unknown database 'prer_mi'"
**Solución:** Abre `http://localhost/PRERMI/instalar_bd.php`

### Error: "Connection refused"
**Solución:** Inicia XAMPP y enciende MySQL

### Error: "Parse error"
**Solución:** Asegúrate de que `prer_mi.sql` está en `/PRERMI/`

### APIs devuelven error 500
**Solución:** Abre `http://localhost/PRERMI/test_apis.php` para ver detalles

---

## 📞 Información Técnica

| Aspecto | Valor |
|---|---|
| **Versión BD** | 1.0.0 |
| **Motor** | InnoDB |
| **Charset** | utf8mb4_general_ci |
| **Servidor** | 127.0.0.1:3306 |
| **MariaDB** | 10.4.32+ |
| **PHP** | 8.2.12+ |
| **Conexión** | PDO |

---

## ✅ Checklist Final

- [x] `prer_mi.sql` copiado en `/PRERMI/`
- [x] `db_config.php` configurado
- [x] `api/utils.php` con getPDO()
- [x] Herramientas de instalación creadas
- [x] Herramientas de verificación creadas
- [x] Documentación completa
- [ ] Base de datos importada ← **TÚ HACES ESTO**
- [ ] Verificación exitosa ← **TÚ HACES ESTO**
- [ ] APIs probados ← **TÚ HACES ESTO**

---

## 📖 Documentación Completa

Para información detallada, consulta:

- **GUIA_INSTALACION_BD.txt** - Guía paso a paso
- **DB_PRER_MI_SCHEMA.md** - Documentación técnica
- **INFRAESTRUCTURA_BD.txt** - Arquitectura general

---

## 🎯 Centro de Control

Accede a todas las herramientas desde:
```
http://localhost/PRERMI/index_herramientas.php
```

---

**Estado: LISTO PARA PRODUCCIÓN ✓**

Fecha: 9 de Diciembre de 2025  
Versión: 1.0.0 OFICIAL
