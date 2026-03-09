-- Script para insertar datos de prueba en contenedores_registrados y depositos
-- Basado en la estructura de tablas mostrada en phpMyAdmin

-- Limpiar datos existentes (opcional)
-- DELETE FROM depositos;
-- DELETE FROM contenedores_registrados;

-- =====================================================
-- INSERTAR CONTENEDORES DE PRUEBA
-- =====================================================

INSERT INTO `contenedores_registrados` 
(`codigo_contenedor`, `ubicacion`, `tipo_contenedor`, `estado`, `ultimo_token`, `token_generado_en`, `token_expira_en`, `creado_en`, `actualizado_en`) 
VALUES 
('CONT-001', 'Zona Centro, Av. Principal', 'plastico', 'activo', '550e8400-e29b-41d4-a716-446655440000', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW()),
('CONT-002', 'Zona Norte, Calle 5', 'vidrio', 'activo', '550e8400-e29b-41d4-a716-446655440001', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW()),
('CONT-003', 'Zona Sur, Parque Central', 'metal', 'activo', '550e8400-e29b-41d4-a716-446655440002', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW()),
('CONT-004', 'Zona Este, Terminal de Transporte', 'papel', 'activo', '550e8400-e29b-41d4-a716-446655440003', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW()),
('CONT-005', 'Zona Oeste, Centro Comercial', 'organico', 'inactivo', '550e8400-e29b-41d4-a716-446655440004', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW());

-- =====================================================
-- INSERTAR DEPÓSITOS DE PRUEBA
-- (Asumiendo que el usuario con id=1 existe en tabla usuarios)
-- =====================================================

INSERT INTO `depositos` 
(`id_usuario`, `id_contenedor`, `token_usado`, `peso`, `tipo_residuo`, `credito_kwh`, `metal_detectado`, `fecha_hora`, `procesado_por`, `observaciones`, `creado_en`) 
VALUES 
(1, 1, '550e8400-e29b-41d4-a716-446655440000', 12.5, 'plastico', 6.25, 0, '2025-01-26 10:30:00', 'sistema', 'Depósito normal', NOW()),
(1, 1, '550e8400-e29b-41d4-a716-446655440000', 8.3, 'plastico', 4.15, 0, '2025-01-26 11:45:00', 'sistema', 'Depósito normal', NOW()),
(1, 2, '550e8400-e29b-41d4-a716-446655440001', 5.2, 'vidrio', 2.60, 0, '2025-01-26 13:20:00', 'sistema', 'Depósito normal', NOW()),
(1, 3, '550e8400-e29b-41d4-a716-446655440002', 3.8, 'metal', 1.90, 1, '2025-01-26 14:15:00', 'sistema', 'Metal detectado en depósito', NOW()),
(1, 4, '550e8400-e29b-41d4-a716-446655440003', 15.6, 'papel', 7.80, 0, '2025-01-26 15:30:00', 'sistema', 'Depósito normal', NOW()),
(1, 2, '550e8400-e29b-41d4-a716-446655440001', 9.1, 'vidrio', 4.55, 0, '2025-01-25 10:00:00', 'sistema', 'Depósito normal', NOW());

-- =====================================================
-- VERIFICAR INSERCIONES
-- =====================================================

SELECT 'Contenedores insertados:' as mensaje;
SELECT COUNT(*) as total FROM contenedores_registrados;

SELECT 'Depósitos insertados:' as mensaje;
SELECT COUNT(*) as total FROM depositos;

SELECT 'Últimos depósitos:' as mensaje;
SELECT * FROM depositos ORDER BY fecha_hora DESC LIMIT 5;
