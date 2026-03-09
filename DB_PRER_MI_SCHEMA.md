# Base de Datos PRER_MI - Estructura Oficial

## Información General
- **Base de Datos:** prer_mi
- **Servidor:** 127.0.0.1 (MariaDB 10.4.32)
- **Charset:** utf8mb4
- **Última Actualización:** 09-12-2025

## Tablas (8 Total)

### 1. `usuarios`
**Usuarios normales del sistema**
```sql
CREATE TABLE usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  usuario VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  telefono VARCHAR(30),
  cedula VARCHAR(20) UNIQUE NOT NULL,
  token VARCHAR(80) UNIQUE,
  token_activo TINYINT(1) DEFAULT 1,
  clave VARCHAR(255) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

**Relaciones:**
- Referenciada por `depositos.user_id` (FK)
- Referenciada por `multas.user_id` (FK)

---

### 2. `usuarios_admin`
**Administradores del sistema**
```sql
CREATE TABLE usuarios_admin (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  clave VARCHAR(255) NOT NULL,
  verification_token VARCHAR(255),
  verified TINYINT(1) DEFAULT 0,
  active TINYINT(1) DEFAULT 0,
  rol ENUM('superadmin','admin') DEFAULT 'admin',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

**Datos Iniciales:**
- Usuario: `Jhail Baez` | Email: `baezjhail@gmail.com` | Rol: admin
- Usuario: `Jhail_ADMIN_GOD` | Email: `jhailbaezperez19@gmail.com` | Rol: admin

---

### 3. `vehiculos_registrados`
**Vehículos capturados por cámara ESP32**
```sql
CREATE TABLE vehiculos_registrados (
  id INT PRIMARY KEY AUTO_INCREMENT,
  placa VARCHAR(20) NOT NULL,
  tipo_vehiculo VARCHAR(50) NOT NULL,
  imagen VARCHAR(255) NOT NULL,
  ubicacion VARCHAR(150) DEFAULT 'Sin especificar',
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  modelo_ml VARCHAR(50) DEFAULT 'TinyML',
  probabilidad FLOAT DEFAULT 0,
  latitud DOUBLE,
  longitud DOUBLE,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

**Campos Clave:**
- `placa`: Identificación del vehículo
- `tipo_vehiculo`: civil, commercial, police, etc.
- `imagen`: Ruta del archivo de imagen capturada
- `modelo_ml`: Modelo de ML usado (ej: TinyML, YOLO)
- `probabilidad`: Confianza de la detección (0-100)
- `latitud/longitud`: Ubicación GPS donde fue capturado

---

### 4. `contenedores_registrados`
**Contenedores de reciclaje inteligentes**
```sql
CREATE TABLE contenedores_registrados (
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_contenedor VARCHAR(50) UNIQUE NOT NULL,
  api_key VARCHAR(100),
  nivel_basura INT DEFAULT 0,
  ubicacion VARCHAR(150) DEFAULT 'Sin especificar',
  latitud DOUBLE,
  longitud DOUBLE,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

**Campos Clave:**
- `id_contenedor`: Identificador único del contenedor (MAC address o ID)
- `api_key`: Token para autenticación de API
- `nivel_basura`: Porcentaje de llenado (0-100)
- `latitud/longitud`: Ubicación del contenedor

---

### 5. `depositos`
**Depósitos de basura en contenedores**
```sql
CREATE TABLE depositos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  contenedor_id INT NOT NULL,
  peso DECIMAL(8,3) DEFAULT 0.000,
  metal_detectado TINYINT(1) DEFAULT 0,
  credito_kwh DECIMAL(10,5) DEFAULT 0.00000,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
)
```

**Campos Clave:**
- `user_id`: Referencia al usuario que hizo el depósito
- `contenedor_id`: Referencia al contenedor donde se depositó
- `peso`: Peso en kg del material depositado
- `metal_detectado`: Boolean si contenía metal
- `credito_kwh`: Crédito o recompensa en kWh

---

### 6. `multas`
**Infracciones detectadas (metal en lugar incorrecto, etc.)**
```sql
CREATE TABLE multas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  contenedor_id INT NOT NULL,
  descripcion VARCHAR(255) DEFAULT 'Metal detectado',
  peso DECIMAL(8,3),
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  seen_by_admin TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
)
```

**Campos Clave:**
- `user_id`: Usuario que cometió la infracción
- `contenedor_id`: Contenedor donde ocurrió
- `descripcion`: Tipo de infracción
- `seen_by_admin`: Si el admin ya vio la multa

---

### 7. `logs_sistema`
**Registro de actividades del sistema**
```sql
CREATE TABLE logs_sistema (
  id INT PRIMARY KEY AUTO_INCREMENT,
  descripcion TEXT NOT NULL,
  tipo VARCHAR(20) DEFAULT 'info',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

**Tipos de Log:**
- `info`: Información general
- `warning`: Advertencias
- `error`: Errores
- `audit`: Auditoría

---

### 8. `configuracion`
**Configuraciones globales del sistema**
```sql
CREATE TABLE configuracion (
  id INT PRIMARY KEY AUTO_INCREMENT,
  clave VARCHAR(100) UNIQUE NOT NULL,
  valor TEXT,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

**Ejemplos de Configuraciones:**
- `sistema_nombre`: "PRER_MI"
- `version`: "1.0.0"
- `credito_kwh_por_kg`: "0.5"
- `multa_metal_no_separado`: "100"

---

## Relaciones y Foreign Keys

```
usuarios (1) ──┬──→ (N) depositos
               └──→ (N) multas

contenedores_registrados (1) ──→ (N) depositos
contenedores_registrados (1) ──→ (N) multas
```

---

## Información de Indexación

| Tabla | Índices Únicos | Índices Regulares |
|-------|---|---|
| `usuarios` | usuario, email, cedula, token | user_id |
| `usuarios_admin` | usuario, email | - |
| `vehiculos_registrados` | id | - |
| `contenedores_registrados` | id_contenedor | - |
| `depositos` | - | user_id, contenedor_id |
| `multas` | - | user_id, contenedor_id |
| `logs_sistema` | - | - |
| `configuracion` | clave | - |

---

## Scripts Iniciales (INSERT)

### Admin por Defecto
```sql
INSERT INTO usuarios_admin (usuario, email, clave, verified, active, rol) VALUES
('Jhail Baez', 'baezjhail@gmail.com', '$2y$10$...', 1, 1, 'admin'),
('Jhail_ADMIN_GOD', 'jhailbaezperez19@gmail.com', '$2y$10$...', 1, 1, 'admin');
```

### Vehículo de Prueba
```sql
INSERT INTO vehiculos_registrados (placa, tipo_vehiculo, imagen, ubicacion, fecha, hora, modelo_ml) VALUES
('DESCONOCIDA', 'civil', 'veh_DESCONOCIDA_20251124_121425_367.jpg', 'Santiago de los Caballeros', '2025-11-24', '12:14:25', 'TinyML');
```

---

## Archivos de Configuración Relacionados

- **db_config.php**: Credenciales de conexión PDO
- **utils.php**: Funciones globales de utilidad
- **prer_mi.sql**: Script SQL completo (en descargas)

---

## Notas Importantes

1. **Charset**: Todos los campos de texto están en `utf8mb4` para soporte Unicode completo
2. **Timestamps**: Todos los creado_en/actualizado_en son TIMESTAMP con DEFAULT CURRENT_TIMESTAMP
3. **Foreign Keys**: Las tablas depositos y multas tienen ON DELETE CASCADE para integridad referencial
4. **Seguridad**: Las contraseñas están hasheadas con bcrypt (password_hash PHP)
5. **Tokens**: Los usuarios normales pueden tener tokens de sesión únicos

---

## Cómo Importar la Base de Datos

```bash
# Opción 1: Desde línea de comandos
mysql -u root -p prer_mi < prer_mi.sql

# Opción 2: En phpMyAdmin
# 1. Ir a Importar
# 2. Seleccionar prer_mi.sql
# 3. Ejecutar
```

---

**Última Actualización:** 8 de Diciembre de 2025
