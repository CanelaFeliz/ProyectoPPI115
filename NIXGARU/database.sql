-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: nixgaru_db
-- ------------------------------------------------------
-- Server version	8.0.39

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `nixgaru_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `nixgaru_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `nixgaru_db`;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Bebidas calientes'),(2,'Bebidas frías'),(3,'Postres'),(4,'Platos');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas`
--

DROP TABLE IF EXISTS `cuentas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas`
--

LOCK TABLES `cuentas` WRITE;
/*!40000 ALTER TABLE `cuentas` DISABLE KEYS */;
/*!40000 ALTER TABLE `cuentas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalles_pedido`
--

DROP TABLE IF EXISTS `detalles_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalles_pedido`
--

LOCK TABLES `detalles_pedido` WRITE;
/*!40000 ALTER TABLE `detalles_pedido` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalles_pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredientes`
--

DROP TABLE IF EXISTS `ingredientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingredientes` (
  `codigo_ingrediente` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_minimo` decimal(10,2) NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Suficiente',
  PRIMARY KEY (`codigo_ingrediente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredientes`
--

LOCK TABLES `ingredientes` WRITE;
/*!40000 ALTER TABLE `ingredientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ingredientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesas`
--

DROP TABLE IF EXISTS `mesas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mesas` (
  `id_mesa` int NOT NULL AUTO_INCREMENT,
  `numero_mesa` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidad` int NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Disponible',
  PRIMARY KEY (`id_mesa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesas`
--

LOCK TABLES `mesas` WRITE;
/*!40000 ALTER TABLE `mesas` DISABLE KEYS */;
/*!40000 ALTER TABLE `mesas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES ('P001','Café americano','cafe-americano.jpg','Bebida clásica a base de espresso',2.00,1,1),('P002','Café con leche','cafe-con-leche.jpg','Espresso con leche texturizada',2.50,1,1),('P003','Cappuccino','cappuccino.jpg','Espresso con leche vaporizada y espuma',3.00,1,1),('P004','Latte','latte.jpg','Espresso con leche al vapor',3.00,1,1),('P005','Chocolate caliente','chocolate-caliente.jpg','Bebida de chocolate y leche',2.75,1,1),('P006','Té caliente','te-caliente.jpg','Infusión caliente a elección',1.75,1,1),('P007','Iced Coffee','iced-coffee.jpg','Café servido sobre hielo',2.50,2,1),('P008','Frappé de café','frappe-cafe.jpg','Mezcla fría con hielo y crema',3.50,2,1),('P009','Frappé de chocolate','frappe-chocolate.jpg','Bebida de chocolate con hielo y crema',3.75,2,1),('P010','Limonada','limonada.jpg','Bebida refrescante de limón',2.00,2,1),('P011','Té frío','te-frio.jpg','Té helado tradicional',2.00,2,1),('P012','Smoothie de fresa','smoothie-fresa.jpg','Batido espeso y frío de fresas',3.50,2,1),('P013','Cheesecake','cheesecake.jpg','Tarta de queso con base de galleta',4.25,3,1),('P014','Brownie','brownie.jpg','Bizcocho de chocolate denso',2.50,3,1),('P015','Pastel de chocolate','pastel-chocolate.jpg','Rebanada de pastel esponjoso',3.50,3,1),('P016','Galletas con chispas','galletas.jpg','Galleta con chispas de chocolate',1.50,3,1),('P017','Muffin de vainilla','muffin.jpg','Panquecito esponjoso sabor vainilla',2.00,3,1),('P018','Desayuno NIXGARU','desayuno.jpg','Frijoles, huevo, plátano y pan',5.50,4,1),('P019','Sándwich de jamón y queso','sandwich-jamon.jpg','Clásico sándwich tostado',3.00,4,1),('P020','Sándwich de pollo','sandwich-pollo.jpg','Sándwich de pollo con aderezos',3.75,4,1),('P021','Panini de pollo','panini.jpg','Panini prensado con pollo y queso',4.50,4,1),('P022','Croissant de jamón y queso','croissant.jpg','Croissant relleno y horneado',3.50,4,1);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recetas`
--

DROP TABLE IF EXISTS `recetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recetas`
--

LOCK TABLES `recetas` WRITE;
/*!40000 ALTER TABLE `recetas` DISABLE KEYS */;
/*!40000 ALTER TABLE `recetas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador'),(2,'Cajero'),(3,'Mesero'),(4,'Barista'),(5,'Cocina');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-16 12:08:27
