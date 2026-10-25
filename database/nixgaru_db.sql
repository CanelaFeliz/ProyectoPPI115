CREATE DATABASE IF NOT EXISTS nixgaru_db;
USE nixgaru_db;

CREATE TABLE Categorias (
    id_categoria INT(11) NOT NULL AUTO_INCREMENT,
    nombre_categoria VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id_categoria)
);

CREATE TABLE Mesas (
    id_mesa INT(11) NOT NULL AUTO_INCREMENT,
    numero_mesa INT(3) NOT NULL UNIQUE,
    capacidad INT(11) NOT NULL,
    estado_mesa VARCHAR(20) NOT NULL,
    PRIMARY KEY (id_mesa)
);

CREATE TABLE Roles (
    id_rol INT(11) NOT NULL AUTO_INCREMENT,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id_rol)
);

CREATE TABLE Inventarios (
    id_ingrediente INT(11) NOT NULL AUTO_INCREMENT,
    nombre_ingrediente VARCHAR(100) NOT NULL UNIQUE,
    cantidad_estimada DECIMAL(8,2) NOT NULL,
    PRIMARY KEY (id_ingrediente)
);

CREATE TABLE Usuarios (
    id_usuario INT(11) NOT NULL AUTO_INCREMENT,
    nombre_completo VARCHAR(100) NOT NULL,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    id_rol INT(11) NOT NULL,
    PRIMARY KEY (id_usuario),
    FOREIGN KEY (id_rol) REFERENCES Roles(id_rol) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Productos (
    id_producto INT(11) NOT NULL AUTO_INCREMENT,
    nombre_producto VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fotografia VARCHAR(255) NOT NULL,
    precio DECIMAL(8,2) NOT NULL,
    estado_disponibilidad VARCHAR(20) NOT NULL,
    id_categoria INT(11) NOT NULL,
    PRIMARY KEY (id_producto),
    FOREIGN KEY (id_categoria) REFERENCES Categorias(id_categoria) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Pedidos (
    id_pedido INT(11) NOT NULL AUTO_INCREMENT,
    fecha_hora DATETIME NOT NULL,
    estado_pedido VARCHAR(30) NOT NULL,
    porcentaje_propina DECIMAL(5,2) NOT NULL,
    total_pagar DECIMAL(8,2) NOT NULL,
    metodo_pago VARCHAR(30) NOT NULL,
    id_mesa INT(11) NOT NULL,
    id_usuario INT(11) NOT NULL,
    PRIMARY KEY (id_pedido),
    FOREIGN KEY (id_mesa) REFERENCES Mesas(id_mesa) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Detalle_Pedidos (
    id_detalle INT(11) NOT NULL AUTO_INCREMENT,
    cantidad INT(11) NOT NULL,
    subtotal DECIMAL(8,2) NOT NULL,
    id_pedido INT(11) NOT NULL,
    id_producto INT(11) NOT NULL,
    PRIMARY KEY (id_detalle),
    FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Recetas (
    id_receta INT(11) NOT NULL AUTO_INCREMENT,
    cantidad_necesaria DECIMAL(8,4) NOT NULL,
    id_producto INT(11) NOT NULL,
    id_ingrediente INT(11) NOT NULL,
    PRIMARY KEY (id_receta),
    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_ingrediente) REFERENCES Inventarios(id_ingrediente) ON DELETE CASCADE ON UPDATE CASCADE
);