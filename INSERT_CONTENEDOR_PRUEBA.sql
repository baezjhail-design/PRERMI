-- ============================================================
-- INSERTAR CONTENEDOR DE PRUEBA PARA ESP32-S3 CAM
-- ============================================================
-- Este script crea el contenedor con ID 1 que el ESP32 necesita
-- para poder registrar depósitos

INSERT INTO `contenedores_registrados` 
(`id`, `codigo_contenedor`, `ubicacion`, `tipo_contenedor`, `estado`, `creado_en`)
VALUES 
(1, 'CONTENEDOR_ESP32_001', 'Laboratorio Prueba', 'general', 'activo', NOW());

-- Verificar que se insertó correctamente
SELECT * FROM `contenedores_registrados` WHERE `id` = 1;

-- Si necesitas más contenedores, puedes agregar:
-- INSERT INTO `contenedores_registrados` 
-- (`id`, `codigo_contenedor`, `ubicacion`, `tipo_contenedor`, `estado`, `creado_en`)
-- VALUES 
-- (2, 'CONTENEDOR_ESP32_002', 'Ubicación 2', 'organico', 'activo', NOW()),
-- (3, 'CONTENEDOR_ESP32_003', 'Ubicación 3', 'reciclable', 'activo', NOW()),
-- (4, 'CONTENEDOR_ESP32_004', 'Ubicación 4', 'metal', 'activo', NOW());
