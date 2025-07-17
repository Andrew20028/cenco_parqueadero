-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-07-2025 a las 21:32:57
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `parqueadero`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupos`
--

CREATE TABLE `cupos` (
  `pisos` int(11) NOT NULL,
  `id_puesto` int(11) NOT NULL,
  `nomenclatura` varchar(10) NOT NULL,
  `id` int(11) NOT NULL,
  `estado` int(11) NOT NULL DEFAULT 0,
  `vehiculo` enum('C','M') NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `placa` varchar(10) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `motivo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cupos`
--

INSERT INTO `cupos` (`pisos`, `id_puesto`, `nomenclatura`, `id`, `estado`, `vehiculo`, `nombre`, `placa`, `contacto`, `motivo`) VALUES
(1, 1, 'C1', 228, 0, 'C', NULL, NULL, NULL, NULL),
(1, 2, 'C1', 229, 0, 'C', NULL, NULL, NULL, NULL),
(1, 3, 'C1', 230, 0, 'C', NULL, NULL, NULL, NULL),
(1, 4, 'C1', 231, 0, 'C', NULL, NULL, NULL, NULL),
(1, 5, 'C1', 232, 0, 'C', NULL, NULL, NULL, NULL),
(1, 6, 'C1', 233, 0, 'C', NULL, NULL, NULL, NULL),
(1, 7, 'C1', 234, 0, 'C', NULL, NULL, NULL, NULL),
(1, 8, 'C1', 235, 0, 'C', NULL, NULL, NULL, NULL),
(1, 9, 'C1', 236, 0, 'C', NULL, NULL, NULL, NULL),
(1, 10, 'C1', 237, 0, 'C', NULL, NULL, NULL, NULL),
(1, 11, 'C1', 238, 0, 'C', NULL, NULL, NULL, NULL),
(1, 12, 'C1', 239, 0, 'C', NULL, NULL, NULL, NULL),
(1, 13, 'C1', 240, 0, 'C', NULL, NULL, NULL, NULL),
(1, 14, 'C1', 241, 0, 'C', NULL, NULL, NULL, NULL),
(1, 15, 'C1', 242, 0, 'C', NULL, NULL, NULL, NULL),
(1, 16, 'C1', 243, 0, 'C', NULL, NULL, NULL, NULL),
(1, 17, 'C1', 244, 0, 'C', NULL, NULL, NULL, NULL),
(1, 18, 'C1', 245, 0, 'C', NULL, NULL, NULL, NULL),
(1, 19, 'C1', 246, 0, 'C', NULL, NULL, NULL, NULL),
(1, 20, 'C1', 247, 0, 'C', NULL, NULL, NULL, NULL),
(0, 22, 'C1', 248, 0, 'C', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `placas`
--

CREATE TABLE `placas` (
  `id_placa` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `tipo_vehiculo` enum('carro','moto') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `placas`
--

INSERT INTO `placas` (`id_placa`, `usuario_id`, `placa`, `tipo_vehiculo`) VALUES
(1, 1, 'abc123', 'carro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombreRol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombreRol`) VALUES
(1, 'Admin'),
(2, 'Usuario'),
(3, 'Portero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `Nombre_apellido` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `Contraseña` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `Correo_Electronico` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `Nombre_apellido`, `usuario`, `Contraseña`, `rol_id`, `reset_token`, `reset_expires`, `Correo_Electronico`) VALUES
(1, 'Andres', 'Andres', '$2y$10$uQUz9RJ09GtQvnGj4Ec3aerEFepEF0VeP0mZID1uROfGO3lHYmQQ2', 1, '13cbc0600d6e7da1cfe122e1df4c9b81b7bb476f0006f862ae6970e3952405da', '2025-06-18 21:27:13', 'andreslopez20028@gmail.com');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cupos`
--
ALTER TABLE `cupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomenclatura` (`nomenclatura`,`id_puesto`);

--
-- Indices de la tabla `placas`
--
ALTER TABLE `placas`
  ADD PRIMARY KEY (`id_placa`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cupos`
--
ALTER TABLE `cupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT de la tabla `placas`
--
ALTER TABLE `placas`
  MODIFY `id_placa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `placas`
--
ALTER TABLE `placas`
  ADD CONSTRAINT `placas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
