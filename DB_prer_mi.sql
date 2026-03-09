-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-02-2026 a las 22:07:34
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
  `codigo_contenedor` varchar(50) NOT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `tipo_contenedor` enum('organico','reciclable','general','metal') DEFAULT 'general',
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `ultimo_token` varchar(255) DEFAULT NULL,
  `token_generado_en` datetime DEFAULT NULL,
  `token_expira_en` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `depositos`
--

CREATE TABLE `depositos` (
  `id` int(11) NOT NULL,
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
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
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

--
-- Volcado de datos para la tabla `logs_sistema`
--

INSERT INTO `logs_sistema` (`id`, `descripcion`, `tipo`, `creado_en`) VALUES
(1, 'Error enviando email de bienvenida a baezjhail@gmail.com: SMTP Error: Could not connect to SMTP host. Failed to connect to server', 'warning', '2025-12-09 00:43:11'),
(2, 'Nuevo administrador registrado: Jhail_ADMIN_GOD (jhailbaezperez19@gmail.com)', 'info', '2025-12-09 02:46:20'),
(3, 'Email verificado para admin: Jhail_ADMIN_GOD', 'info', '2025-12-09 02:46:26'),
(4, 'Email verificado para usuario: Jhail Baez', 'info', '2025-12-09 03:16:20'),
(5, 'Email verificado para usuario: JUAN PEREZ', 'info', '2026-01-27 11:05:23'),
(6, 'Nuevo administrador registrado: JUAN PEREZ (chavezemelychaves@gmail.com)', 'info', '2026-01-27 11:05:59'),
(7, 'Email verificado para admin: JUAN PEREZ', 'info', '2026-01-27 11:06:16'),
(8, 'Nuevo administrador registrado: JUAN PEREZ (jhailbaezperez19@gmail.com)', 'info', '2026-02-01 03:21:59'),
(9, 'Email verificado para usuario: Jhail', 'info', '2026-02-01 03:36:25'),
(10, 'Nuevo administrador registrado: JHAIL_ADMIN_GOD (jhailbaezperez19@gmail.com)', 'info', '2026-02-01 03:40:53'),
(11, 'Email verificado para admin: JHAIL_ADMIN_GOD', 'info', '2026-02-01 03:41:27'),
(12, 'Nuevo administrador registrado: espinaladrian052@gmail.com (espinaladrian052@gmail.com)', 'info', '2026-02-20 18:23:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mediciones`
--

CREATE TABLE `mediciones` (
  `id` int(11) NOT NULL,
  `temperatura` decimal(5,2) NOT NULL,
  `energia` decimal(8,2) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
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
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `clave` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `usuario`, `email`, `telefono`, `cedula`, `token`, `token_activo`, `verified`, `clave`, `creado_en`) VALUES
(4, 'Jhail', 'Baez', 'Jhail Baez', 'baezjhail@gmail.com', '809-956-8622', '031-0515704-8', NULL, 0, 1, '$2y$10$BUfMpKLEmMA2k7JEVWX50u6fWveIZ5Ght5nCm73PJzj3bn9neGzsy', '2025-12-09 03:16:08'),
(10, 'JUAN', 'PEREZ', 'Jhail', 'jhailbaezperez19@gmail.com', '809-956-8622', '80942109192042109', NULL, 0, 1, '$2y$10$6NEJEi2.r8SxVr4PzTV3x.akwZ1rHe./NlvZLCS6riOYKtSW59jqq', '2026-02-01 03:35:58'),
(11, 'adrian', 'espinal', 'espinaladrian052@gmail.com', 'espinaladrian052@gmail.com', '85893749384', 'dyuqgfd792gpf4f', '5f76ac2e-bbbe-4285-b89f-5ad9dbdf14ec', 1, 0, '$2y$10$tOP/X2YtTzG0aCtR4jjYB.b8LRPBrBV/5.95SG0kTX.HKW3DyuCx2', '2026-02-16 17:38:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_admin`
--

CREATE TABLE `usuarios_admin` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `nombre` varchar(100) NOT NULL DEFAULT '',
  `apellido` varchar(100) NOT NULL DEFAULT '',
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

INSERT INTO `usuarios_admin` (`id`, `usuario`, `email`, `nombre`, `apellido`, `clave`, `verification_token`, `verified`, `active`, `rol`, `creado_en`) VALUES
(2, 'Jhail Baez', 'baezjhail@gmail.com', '', '', '$2y$10$OE.UgJottp35eG81uHIf4e4TJjmRxGWYrIYr2Kw4XULoxqepOgKaG', '46aa7562e481a1f904ef977b340251c1c920a8e6d7959a694b661c4b7b46f9d6', 1, 1, 'admin', '2025-11-24 11:08:14'),
(7, 'JHAIL_ADMIN_GOD', 'jhailbaezperez19@gmail.com', '', '', '$2y$10$4wf2LY01aLVwhHBboN4G6O6AnETZ7XUqY5XA4mRU6p1OZFI5FrlZe', NULL, 1, 1, 'admin', '2026-02-01 03:40:51'),
(8, 'espinaladrian052@gmail.com', 'espinaladrian052@gmail.com', '', '', '$2y$10$.BHmxxi5WUUEwSsob1Z7tOcQZfub21FG8WAu/swTrwAUJsSk/fZ0G', '85c088e53883fc85ef3297432b5dc4402a1c0798e924487bc80451cba1af5510', 1, 1, 'admin', '2026-02-20 18:23:29');

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
(1, 'DESCONOCIDA', 'civil', 'veh_DESCONOCIDA_20251124_121425_367.jpg', 'Santiago de los Caballeros', '2025-11-24', '12:14:25', 'TinyML', 0, 19.451, -70.6894, '2025-11-24 11:14:25'),
(2, 'DESCONOCIDA', 'civil', 'veh_DESCONOCIDA_20260126_203539_150.jpg', 'Santiago de los Caballeros', '2026-01-26', '20:35:39', 'TinyML', 0, 19.451, -70.6894, '2026-01-26 19:35:39'),
(3, 'DESCONOCIDA', 'civil', 'veh_DESCONOCIDA_20260126_203610_255.jpg', 'Santiago de los Caballeros', '2026-01-26', '20:36:10', 'TinyML', 0, 19.451, -70.6894, '2026-01-26 19:36:10'),
(4, 'DESCONOCIDA', 'civil', 'veh_DESCONOCIDA_20260126_203651_111.jpg', 'Santiago de los Caballeros', '2026-01-26', '20:36:51', 'TinyML', 0, 19.451, -70.6894, '2026-01-26 19:36:51'),
(5, 'DESCONOCIDA', 'civil', 'veh_DESCONOCIDA_20260127_005634_523.jpg', 'Santiago de los Caballeros', '2026-01-27', '00:56:34', 'TinyML', 0, 19.451, -70.6894, '2026-01-26 23:56:34');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `contenedores_registrados`
--
ALTER TABLE `contenedores_registrados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_contenedor` (`codigo_contenedor`);

--
-- Indices de la tabla `depositos`
--
ALTER TABLE `depositos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token_usado` (`token_usado`),
  ADD KEY `id_contenedor` (`id_contenedor`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mediciones`
--
ALTER TABLE `mediciones`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `mediciones`
--
ALTER TABLE `mediciones`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios_admin`
--
ALTER TABLE `usuarios_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `vehiculos_registrados`
--
ALTER TABLE `vehiculos_registrados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `depositos`
--
ALTER TABLE `depositos`
  ADD CONSTRAINT `depositos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `depositos_ibfk_2` FOREIGN KEY (`id_contenedor`) REFERENCES `contenedores_registrados` (`id`);

--
-- Filtros para la tabla `multas`
--
ALTER TABLE `multas`
  ADD CONSTRAINT `fk_multas_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
