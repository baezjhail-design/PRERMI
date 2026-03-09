-- ============================================================================
-- MIGRACION DE SCHEMA: Eliminación de contenedores_registrados
-- y creación de nuevas tablas especializadas
-- ============================================================================
-- FECHA: 2024
-- PROPÓSITO: Reemplazar tabla monolítica contenedores_registrados con diseño
--            más especializado usando sensores, mediciones y mediciones_biomasa
-- ============================================================================

-- 1. RESPALDO DE DATOS (opcional pero recomendado)
-- ============================================================================
-- CREATE TABLE contenedores_registrados_backup AS SELECT * FROM contenedores_registrados;
-- CREATE TABLE sanciones_backup AS SELECT * FROM sanciones;
-- CREATE TABLE depositos_backup AS SELECT * FROM depositos;

-- 2. REMOVER RESTRICCIONES DE INTEGRIDAD REFERENCIAL
-- ============================================================================
-- Listar FK constraints actuales:
-- SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
-- WHERE REFERENCED_TABLE_NAME = 'contenedores_registrados';

ALTER TABLE depositos 
  DROP FOREIGN KEY depositos_ibfk_2;

-- 3. CREAR LAS 3 NUEVAS TABLAS (si no existen)
-- ============================================================================

-- Tabla 3.1: SENSORES (para lecturas de sensores IR)
CREATE TABLE IF NOT EXISTS sensores (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  sensor_ir TINYINT DEFAULT 0 COMMENT 'Lectura del sensor infrarrojo (0-255)',
  ruta_imagen VARCHAR(255) COMMENT 'Ruta a imagen capturada (si aplica)',
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sensores_usuarios FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_sensor_user_fecha (user_id, fecha DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla 3.2: MEDICIONES (peso y sensores metálicos)
CREATE TABLE IF NOT EXISTS mediciones (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  peso DECIMAL(8,2) COMMENT 'Peso en kilogramos',
  sensor_metal TINYINT DEFAULT 0 COMMENT 'Detección de metal (0=no, 1=sí)',
  estado ENUM('disponible','lleno','mantenimiento','fuera_servicio') DEFAULT 'disponible',
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mediciones_usuarios FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_medicion_user_fecha (user_id, fecha DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla 3.3: MEDICIONES_BIOMASA (telemetría del reactor de biomasa)
CREATE TABLE IF NOT EXISTS mediciones_biomasa (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  relay DECIMAL(8,2) DEFAULT 0.0 COMMENT 'Estado del relé',
  ventilador DECIMAL(8,2) DEFAULT 0.0 COMMENT 'Velocidad del ventilador (%)',
  peltier1 DECIMAL(8,2) DEFAULT 0.0 COMMENT 'Temperatura Peltier 1',
  peltier2 DECIMAL(8,2) DEFAULT 0.0 COMMENT 'Temperatura Peltier 2',
  gases DECIMAL(8,2) DEFAULT 0.0 COMMENT 'Concentración de gases (ppm)',
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_biomasa_usuarios FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_biomasa_user_fecha (user_id, fecha DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PERMITIR NULL en columnas que referenciaban contenedores_registrados
-- ============================================================================
ALTER TABLE depositos MODIFY COLUMN id_contenedor INT DEFAULT 1;
ALTER TABLE sanciones MODIFY COLUMN contenedor_id INT DEFAULT 1;

-- 5. OPCIONALES: Eliminar tabla obsoleta
-- ============================================================================
-- PASOS PARA EJECUTAR MANUALMENTE DESPUÉS DE VALIDAR:
-- 1. Revisar que no haya más dependencias de contenedores_registrados
-- 2. Validar integridad de depositos y sanciones
-- 3. Si todo OK, descomenta la siguiente línea:
-- DROP TABLE IF EXISTS contenedores_registrados;

-- 6. VERIFICAR INTEGRIDAD POST-MIGRACION
-- ============================================================================
-- SELECT COUNT(*) as total_usuarios FROM usuarios;
-- SELECT COUNT(*) as total_depositos FROM depositos;
-- SELECT COUNT(*) as total_sanciones FROM sanciones;
-- SELECT COUNT(*) as total_sensores FROM sensores;
-- SELECT COUNT(*) as total_mediciones FROM mediciones;
-- SELECT COUNT(*) as total_biomasa FROM mediciones_biomasa;

-- 7. VALIDAR CONSTRAINTS
-- ============================================================================
-- SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
-- WHERE TABLE_NAME IN ('sensores', 'mediciones', 'mediciones_biomasa')
-- AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ============================================================================
-- FIN DE SCRIPT DE MIGRACION
-- ============================================================================
-- 
-- INSTRUCCIONES DE EJECUCIÓN:
-- 1. Ejecutar en MySQL/MariaDB client: mysql -u [user] -p [database] < MIGRACION_NUEVO_SCHEMA.sql
-- 2. Validar con queries de verificación
-- 3. Si todos los índices de éxito ✓, descomenta DROP TABLE IF EXISTS
-- 4. Re-ejecutar para limpiar tabla vieja
--
-- ROLLBACK (si es necesario):
-- 1. DROP TABLE sensores, mediciones, mediciones_biomasa
-- 2. Restaurar desde backup: INSERT INTO contenedores_registrados SELECT * FROM contenedores_registrados_backup
-- 3. Restaurar FK: ALTER TABLE depositos ADD FOREIGN KEY (id_contenedor) REFERENCES contenedores_registrados(id)
--
-- ============================================================================
