-- Script SQL para actualizar tabla usuarios_admin
-- Agregar campos nombre y apellido si no existen

ALTER TABLE `usuarios_admin` 
ADD COLUMN `nombre` varchar(100) DEFAULT '' AFTER `usuario`,
ADD COLUMN `apellido` varchar(100) DEFAULT '' AFTER `nombre`;

-- Actualizar registros existentes
UPDATE `usuarios_admin` SET 
    `nombre` = 'Jhail',
    `apellido` = 'Baez'
WHERE `id` = 2;

UPDATE `usuarios_admin` SET 
    `nombre` = 'Jhail',
    `apellido` = 'Admin God'
WHERE `id` = 3;

-- =========================================================
-- Endurecimiento de tabla usuarios (registro seguro)
-- =========================================================

-- 1) Ajustar longitudes realistas
ALTER TABLE `usuarios`
    MODIFY `telefono` VARCHAR(12) NULL,
    MODIFY `cedula` VARCHAR(13) NOT NULL,
    MODIFY `usuario` VARCHAR(30) NOT NULL,
    MODIFY `email` VARCHAR(120) NOT NULL;

-- 2) Normalizar formato básico de cédula y teléfono (solo filas existentes)
-- Nota: revisar manualmente antes de ejecutar en producción si hay formatos especiales.
UPDATE `usuarios`
SET `cedula` = CONCAT(
    SUBSTRING(REGEXP_REPLACE(`cedula`, '[^0-9]', ''), 1, 3), '-',
    SUBSTRING(REGEXP_REPLACE(`cedula`, '[^0-9]', ''), 4, 7), '-',
    SUBSTRING(REGEXP_REPLACE(`cedula`, '[^0-9]', ''), 11, 1)
)
WHERE CHAR_LENGTH(REGEXP_REPLACE(`cedula`, '[^0-9]', '')) = 11;

UPDATE `usuarios`
SET `telefono` = CONCAT(
    SUBSTRING(REGEXP_REPLACE(`telefono`, '[^0-9]', ''), 1, 3), '-',
    SUBSTRING(REGEXP_REPLACE(`telefono`, '[^0-9]', ''), 4, 3), '-',
    SUBSTRING(REGEXP_REPLACE(`telefono`, '[^0-9]', ''), 7, 4)
)
WHERE `telefono` IS NOT NULL
  AND CHAR_LENGTH(REGEXP_REPLACE(`telefono`, '[^0-9]', '')) = 10;

-- 3) Revisar duplicados antes de crear índices únicos
SELECT 'usuario' AS campo, `usuario` AS valor, COUNT(*) AS total
FROM `usuarios`
GROUP BY `usuario`
HAVING COUNT(*) > 1;

SELECT 'email' AS campo, `email` AS valor, COUNT(*) AS total
FROM `usuarios`
GROUP BY `email`
HAVING COUNT(*) > 1;

SELECT 'cedula' AS campo, `cedula` AS valor, COUNT(*) AS total
FROM `usuarios`
GROUP BY `cedula`
HAVING COUNT(*) > 1;

SELECT 'telefono' AS campo, `telefono` AS valor, COUNT(*) AS total
FROM `usuarios`
WHERE `telefono` IS NOT NULL AND `telefono` <> ''
GROUP BY `telefono`
HAVING COUNT(*) > 1;

-- 4) Crear índices únicos (ejecutar luego de resolver duplicados)
-- Si ya existen, omitir la sentencia correspondiente.
ALTER TABLE `usuarios` ADD UNIQUE KEY `uq_usuarios_usuario` (`usuario`);
ALTER TABLE `usuarios` ADD UNIQUE KEY `uq_usuarios_email` (`email`);
ALTER TABLE `usuarios` ADD UNIQUE KEY `uq_usuarios_cedula` (`cedula`);
ALTER TABLE `usuarios` ADD UNIQUE KEY `uq_usuarios_telefono` (`telefono`);
