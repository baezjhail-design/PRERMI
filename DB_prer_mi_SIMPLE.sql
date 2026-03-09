-- A trimmed version of the prer_mi schema.
-- All tables are defined but no data rows are present, avoiding huge inserts
-- (images, logs, etc.) so the dump can be imported via phpMyAdmin/XAMPP.

DROP DATABASE IF EXISTS `prer_mi`;
CREATE DATABASE `prer_mi`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;
USE `prer_mi`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- -----------------------------------------------------------------
-- contenedores_registrados
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `contenedores_registrados`;
CREATE TABLE `contenedores_registrados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_contenedor` varchar(50) NOT NULL,
  `api_key` varchar(100) DEFAULT NULL,
  `nivel_basura` int DEFAULT 0,
  `ubicacion` varchar(255) DEFAULT NULL,
  `latitud` double DEFAULT NULL,
  `longitud` double DEFAULT NULL,
  `tipo_contenedor` enum('organico','reciclable','general','metal')
      DEFAULT 'general',
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `ultimo_token` varchar(255) DEFAULT NULL,
  `token_generado_en` datetime DEFAULT NULL,
  `token_expira_en` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp()
      ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- depositos
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `depositos`;
CREATE TABLE `depositos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_contenedor` int(11) NOT NULL,
  `token_usado` varchar(255) NOT NULL,
  `peso` float DEFAULT NULL,
  `tipo_residuo` varchar(100) DEFAULT NULL,
  `credito_kwh` float DEFAULT NULL,
  `metal_detectado` tinyint(1) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `procesado_por` varchar(100) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `contenedor_id` int(11) GENERATED ALWAYS AS (`id_contenedor`) VIRTUAL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- logs_sistema
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `logs_sistema`;
CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` text NOT NULL,
  `tipo` varchar(20) DEFAULT 'info',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- sensores
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `sensores`;
CREATE TABLE `sensores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `sensor_ir` tinyint(1) DEFAULT 0,
  `ruta_imagen` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- rostros
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `rostros`;
CREATE TABLE `rostros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `image` longblob DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- sanciones
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `sanciones`;
CREATE TABLE `sanciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `contenedor_id` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT 'Metal detectado',
  `peso` decimal(8,3) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `seen_by_admin` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- usuarios
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `cedula` varchar(20) NOT NULL,
  `token` varchar(80) DEFAULT NULL,
  `token_activo` tinyint(1) DEFAULT 1,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `clave` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- datos existentes de usuarios
INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `usuario`, `email`, `telefono`, `cedula`, `token`, `token_activo`, `verified`, `clave`, `creado_en`) VALUES
(4, 'Jhail', 'Baez', 'Jhail Baez', 'baezjhail@gmail.com', '809-956-8622', '031-0515704-8', NULL, 0, 1, '$2y$10$BUfMpKLEmMA2k7JEVWX50u6fWveIZ5Ght5nCm73PJzj3bn9neGzsy', '2025-12-09 03:16:08'),
(10, 'JUAN', 'PEREZ', 'Jhail', 'jhailbaezperez19@gmail.com', '809-956-8622', '80942109192042109', NULL, 0, 1, '$2y$10$6NEJEi2.r8SxVr4PzTV3x.akwZ1rHe./NlvZLCS6riOYKtSW59jqq', '2026-02-01 03:35:58'),
(11, 'adrian', 'espinal', 'espinaladrian052@gmail.com', 'espinaladrian052@gmail.com', '85893749384', 'dyuqgfd792gpf4f', '5f76ac2e-bbbe-4285-b89f-5ad9dbdf14ec', 1, 0, '$2y$10$tOP/X2YtTzG0aCtR4jjYB.b8LRPBrBV/5.95SG0kTX.HKW3DyuCx2', '2026-02-16 17:38:56');

-- -----------------------------------------------------------------
-- usuarios_admin
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios_admin`;
CREATE TABLE `usuarios_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `nombre` varchar(100) NOT NULL DEFAULT '',
  `apellido` varchar(100) NOT NULL DEFAULT '',
  `clave` varchar(255) NOT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 0,
  `rol` enum('superadmin','admin') DEFAULT 'admin',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- datos existentes de administradores
INSERT INTO `usuarios_admin` (`id`, `usuario`, `email`, `nombre`, `apellido`, `clave`, `verification_token`, `verified`, `active`, `rol`, `creado_en`) VALUES
(2, 'Jhail Baez', 'baezjhail@gmail.com', '', '', '$2y$10$OE.UgJottp35eG81uHIf4e4TJjmRxGWYrIYr2Kw4XULoxqepOgKaG', '46aa7562e481a1f904ef977b340251c1c920a8e6d7959a694b661c4b7b46f9d6', 1, 1, 'admin', '2025-11-24 11:08:14'),
(7, 'JHAIL_ADMIN_GOD', 'jhailbaezperez19@gmail.com', '', '', '$2y$10$4wf2LY01aLVwhHBboN4G6O6AnETZ7XUqY5XA4mRU6p1OZFI5FrlZe', NULL, 1, 1, 'admin', '2026-02-01 03:40:51'),
(8, 'espinaladrian052@gmail.com', 'espinaladrian052@gmail.com', '', '', '$2y$10$.BHmxxi5WUUEwSsob1Z7tOcQZfub21FG8WAu/swTrwAUJsSk/fZ0G', '85c088e53883fc85ef3297432b5dc4402a1c0798e924487bc80451cba1af5510', 1, 1, 'admin', '2026-02-20 18:23:29');

-- -----------------------------------------------------------------
-- vehiculos_registrados
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `vehiculos_registrados`;
CREATE TABLE `vehiculos_registrados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `tipo_vehiculo` varchar(50) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `ubicacion` varchar(150) DEFAULT 'Sin especificar',
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `modelo_ml` varchar(50) DEFAULT 'TinyML',
  `probabilidad` float DEFAULT 0,
  `latitud` double DEFAULT NULL,
  `longitud` double DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- mediciones_biomasa (incluye temperatura y energía)
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS `mediciones_biomasa`;
CREATE TABLE `mediciones_biomasa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `temperatura` decimal(5,2) NOT NULL,
  `energia` decimal(8,2) NOT NULL,
  `user_id` int(11) NOT NULL,
  `relay` decimal(8,2) DEFAULT 0.0,
  `ventilador` decimal(8,2) DEFAULT 0.0,
  `peltier1` decimal(8,2) DEFAULT 0.0,
  `peltier2` decimal(8,2) DEFAULT 0.0,
  `gases` decimal(8,2) DEFAULT 0.0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
