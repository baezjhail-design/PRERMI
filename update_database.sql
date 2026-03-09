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
