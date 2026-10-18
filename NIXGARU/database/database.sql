-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS `nixgaru_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nixgaru_db`;

-- 1. Tabla: categorias (Entidad independiente)
CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla: roles (Entidad independiente)
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla: ingredientes (Entidad independiente)
CREATE TABLE `ingredientes` (
  `codigo_ingrediente` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_minimo` decimal(10,2) NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Suficiente',
  PRIMARY KEY (`codigo_ingrediente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla: mesas (Entidad independiente)
CREATE TABLE `mesas` (
  `id_mesa` int NOT NULL AUTO_INCREMENT,
  `numero_mesa` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidad` int NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Disponible',
  PRIMARY KEY (`id_mesa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla: usuarios (Depende de roles)
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrasenha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_rol` int NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla: productos (Depende de categorias)
CREATE TABLE `productos` (
  `codigo_producto` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fotografia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `precio` decimal(10,2) NOT NULL,
  `id_categoria` int NOT NULL,
  `disponibilidad` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`codigo_producto`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla: recetas (Depende de productos e ingredientes)
CREATE TABLE `recetas` (
  `id_receta` int NOT NULL AUTO_INCREMENT,
  `codigo_producto` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_ingrediente` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad_necesaria` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_receta`),
  KEY `codigo_producto` (`codigo_producto`),
  KEY `codigo_ingrediente` (`codigo_ingrediente`),
  CONSTRAINT `recetas_ibfk_1` FOREIGN KEY (`codigo_producto`) REFERENCES `productos` (`codigo_producto`),
  CONSTRAINT `recetas_ibfk_2` FOREIGN KEY (`codigo_ingrediente`) REFERENCES `ingredientes` (`codigo_ingrediente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla: pedidos (Depende de mesas y usuarios)
CREATE TABLE `pedidos` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `id_mesa` int NOT NULL,
  `id_mesero` int NOT NULL,
  `estado_pedido` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'Recibido',
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pedido`),
  KEY `id_mesa` (`id_mesa`),
  KEY `id_mesero` (`id_mesero`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_mesa`) REFERENCES `mesas` (`id_mesa`),
  CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`id_mesero`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabla: detalles_pedido (Depende de pedidos y productos)
CREATE TABLE `detalles_pedido` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `codigo_producto` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidadPed` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_pedido` (`id_pedido`),
  KEY `codigo_producto` (`codigo_producto`),
  CONSTRAINT `detalles_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  CONSTRAINT `detalles_pedido_ibfk_2` FOREIGN KEY (`codigo_producto`) REFERENCES `productos` (`codigo_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabla: cuentas (Depende de pedidos y usuarios)
CREATE TABLE `cuentas` (
  `id_cuenta` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `propina` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `efectivo_recibido` decimal(10,2) NOT NULL,
  `cambio` decimal(10,2) NOT NULL,
  `fecha_hora_pago` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_cajero` int NOT NULL,
  PRIMARY KEY (`id_cuenta`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_cajero` (`id_cajero`),
  CONSTRAINT `cuentas_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  CONSTRAINT `cuentas_ibfk_2` FOREIGN KEY (`id_cajero`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;