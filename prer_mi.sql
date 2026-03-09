-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-12-2025 a las 00:41:58
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `prer_mi`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_registrados`
--

CREATE TABLE `contenedores_registrados` (
  `id` int(11) NOT NULL,
  `id_contenedor` varchar(50) NOT NULL,
  `api_key` varchar(100) DEFAULT NULL,
  `nivel_basura` int(11) DEFAULT 0,
  `ubicacion` varchar(150) DEFAULT 'Sin especificar',
  `latitud` double DEFAULT NULL,
  `longitud` double DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `depositos`
--

CREATE TABLE `depositos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contenedor_id` int(11) NOT NULL,
  `peso` decimal(8,3) DEFAULT 0.000,
  `metal_detectado` tinyint(1) DEFAULT 0,
  `credito_kwh` decimal(10,5) DEFAULT 0.00000,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `tipo` varchar(20) DEFAULT 'info',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `multas`
--

CREATE TABLE `multas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contenedor_id` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT 'Metal detectado',
  `peso` decimal(8,3) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `seen_by_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `cedula` varchar(20) NOT NULL,
  `token` varchar(80) DEFAULT NULL,
  `token_activo` tinyint(1) DEFAULT 1,
  `verified` tinyint(1) DEFAULT 0,
  `clave` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_admin`
--

CREATE TABLE `usuarios_admin` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `nombre` varchar(100) DEFAULT '',
  `apellido` varchar(100) DEFAULT '',
  `clave` varchar(255) NOT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 0,
  `rol` enum('superadmin','admin') DEFAULT 'admin',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios_admin`
--

INSERT INTO `usuarios_admin` (`id`, `usuario`, `email`, `clave`, `verification_token`, `verified`, `active`, `rol`, `creado_en`) VALUES
(2, 'Jhail Baez', 'baezjhail@gmail.com', '$2y$10$OE.UgJottp35eG81uHIf4e4TJjmRxGWYrIYr2Kw4XULoxqepOgKaG', '46aa7562e481a1f904ef977b340251c1c920a8e6d7959a694b661c4b7b46f9d6', 1, 1, 'admin', '2025-11-24 11:08:14'),
(3, 'Jhail_ADMIN_GOD', 'jhailbaezperez19@gmail.com', '$2y$10$CZufy.t5VtIJfq5XbpD3Qe0UZq1Fdr0E0ERxHu7baVLK7FCij0m9u', '39a93db5c66d3f0ae2b9c4a3443a3117536de75729aa9cee3d2ec192988bc9eb', 1, 1, 'admin', '2025-11-25 23:26:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos_registrados`
--

CREATE TABLE `vehiculos_registrados` (
  `id` int(11) NOT NULL,
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
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vehiculos_registrados`
--

INSERT INTO `vehiculos_registrados` (`id`, `placa`, `tipo_vehiculo`, `imagen`, `ubicacion`, `fecha`, `hora`, `modelo_ml`, `probabilidad`, `latitud`, `longitud`, `creado_en`) VALUES
(1, 'DESCONOCIDA', 'civil', 'veh_DESCONOCIDA_20251124_121425_367.jpg', 'Santiago de los Caballeros', '2025-11-24', '12:14:25', 'TinyML', 0, 19.451, -70.6894, '2025-11-24 11:14:25');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `contenedores_registrados`
--
ALTER TABLE `contenedores_registrados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_contenedor` (`id_contenedor`);

--
-- Indices de la tabla `depositos`
--
ALTER TABLE `depositos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `contenedor_id` (`contenedor_id`);

--
-- Indices de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `multas`
--
ALTER TABLE `multas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `contenedor_id` (`contenedor_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indices de la tabla `usuarios_admin`
--
ALTER TABLE `usuarios_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `vehiculos_registrados`
--
ALTER TABLE `vehiculos_registrados`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `contenedores_registrados`
--
ALTER TABLE `contenedores_registrados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `depositos`
--
ALTER TABLE `depositos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `multas`
--
ALTER TABLE `multas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios_admin`
--
ALTER TABLE `usuarios_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `vehiculos_registrados`
--
ALTER TABLE `vehiculos_registrados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `depositos`
--
ALTER TABLE `depositos`
  ADD CONSTRAINT `fk_depositos_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `multas`
--
ALTER TABLE `multas`
  ADD CONSTRAINT `fk_multas_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
