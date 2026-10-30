-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS nixgaru_db;
USE nixgaru_db;

-- 1. Tabla: Categorias
CREATE TABLE Categorias (
    id_categoria INT(11) AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(50) NOT NULL
);

-- 2. Tabla: Productos
CREATE TABLE Productos (
    id_producto INT(11) AUTO_INCREMENT PRIMARY KEY,
    nombre_producto VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    fotografia VARCHAR(255),
    precio DECIMAL(8,2) NOT NULL,
    estado_disponibilidad VARCHAR(20) NOT NULL,
    id_categoria INT(11),
    FOREIGN KEY (id_categoria) REFERENCES Categorias(id_categoria)
);

-- 3. Tabla: Mesas
CREATE TABLE Mesas (
    id_mesa INT(11) AUTO_INCREMENT PRIMARY KEY,
    numero_mesa INT(3) NOT NULL,
    estado_mesa VARCHAR(20) NOT NULL,
    capacidad INT(3) NOT NULL
);

-- 4. Tabla: Roles
CREATE TABLE Roles (
    id_rol INT(11) AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL
);

-- 5. Tabla: Usuarios
CREATE TABLE Usuarios (
    id_usuario INT(11) AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    nombre_usuario VARCHAR(50) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    id_rol INT(11),
    FOREIGN KEY (id_rol) REFERENCES Roles(id_rol)
);

-- 6. Tabla: Pedidos (MODIFICADA)
CREATE TABLE Pedidos (
    id_pedido INT(11) AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL,
    estado_pedido VARCHAR(30) NOT NULL,
    porcentaje_propina DECIMAL(5,2) NOT NULL,
    total_pagar DECIMAL(8,2) NOT NULL,
    metodo_pago VARCHAR(30) NOT NULL,
    id_mesa INT(11) NULL, -- MODIFICACIÓN: Se permite NULL para órdenes web a domicilio sin espacio físico
    id_usuario INT(11) NULL, -- Se permite NULL temporalmente si la orden ingresa sola desde la aplicación web
    
    -- NUEVOS CAMPOS: Gestión de clientes web y logística de entrega
    nombre_cliente VARCHAR(100),
    apellido_cliente VARCHAR(100),
    telefono_cliente VARCHAR(20),
    correo_cliente VARCHAR(100),
    tipo_orden VARCHAR(30), -- Ej. 'Envío a domicilio' o 'Retiro en el local'
    municipio VARCHAR(100), -- Ej. 'San Salvador'
    direccion VARCHAR(255),
    punto_referencia VARCHAR(255),
    
    -- NUEVOS CAMPOS: Adaptación para saldos y productos adicionales en el local
    monto_abonado DECIMAL(8,2) DEFAULT 0.00,
    saldo_pendiente DECIMAL(8,2) DEFAULT 0.00,
    
    FOREIGN KEY (id_mesa) REFERENCES Mesas(id_mesa),
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario)
);

-- 7. Tabla: Detalle_Pedidos
CREATE TABLE Detalle_Pedidos (
    id_detalle INT(11) AUTO_INCREMENT PRIMARY KEY,
    cantidad INT(11) NOT NULL,
    subtotal DECIMAL(8,2) NOT NULL,
    id_pedido INT(11),
    id_producto INT(11),
    FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido),
    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto)
);

-- 8. Tabla: Inventarios
CREATE TABLE Inventarios (
    id_ingrediente INT(11) AUTO_INCREMENT PRIMARY KEY,
    nombre_ingrediente VARCHAR(100) NOT NULL,
    cantidad_estimada DECIMAL(8,2) NOT NULL
);

-- 9. Tabla: Recetas
CREATE TABLE Recetas (
    id_receta INT(11) AUTO_INCREMENT PRIMARY KEY,
    cantidad_necesaria DECIMAL(8,4) NOT NULL,
    id_producto INT(11),
    id_ingrediente INT(11),
    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto),
    FOREIGN KEY (id_ingrediente) REFERENCES Inventarios(id_ingrediente)
);