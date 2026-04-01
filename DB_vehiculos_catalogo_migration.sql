-- ============================================================
-- MIGRACIÓN: Tabla vehiculos_catalogo
-- Ejecutar en phpMyAdmin o en MySQL del VPS una sola vez.
-- Es segura para ejecutar varias veces (usa IF NOT EXISTS / IF NOT EXISTS checks).
-- ============================================================

CREATE TABLE IF NOT EXISTS `vehiculos_catalogo` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tipo_vehiculo` VARCHAR(40) NOT NULL,
    `etiqueta` VARCHAR(120) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `ruta_archivo` VARCHAR(255) NOT NULL,
    `photo_base64` LONGTEXT NULL,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'activo',
    `created_at` DATETIME NOT NULL,
    INDEX `idx_tipo_estado` (`tipo_vehiculo`, `estado`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar columnas opcionales si no existen
-- (cada ALTER es independiente: si falla alguno porque ya existe, no afecta los demás)

ALTER TABLE `vehiculos_catalogo` ADD COLUMN IF NOT EXISTS `marca`             VARCHAR(80) NULL AFTER `descripcion`;
ALTER TABLE `vehiculos_catalogo` ADD COLUMN IF NOT EXISTS `modelo`            VARCHAR(80) NULL AFTER `marca`;
ALTER TABLE `vehiculos_catalogo` ADD COLUMN IF NOT EXISTS `anio`              VARCHAR(10) NULL AFTER `modelo`;
ALTER TABLE `vehiculos_catalogo` ADD COLUMN IF NOT EXISTS `color`             VARCHAR(40) NULL AFTER `anio`;
ALTER TABLE `vehiculos_catalogo` ADD COLUMN IF NOT EXISTS `placa_referencia`  VARCHAR(32) NULL AFTER `color`;
ALTER TABLE `vehiculos_catalogo` ADD COLUMN IF NOT EXISTS `bbox_json`         TEXT        NULL AFTER `placa_referencia`;

-- Verificar que la tabla quedó bien
DESCRIBE `vehiculos_catalogo`;
