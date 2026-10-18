-- 1. DATOS DE CATEGORÍAS Y ROLES

INSERT INTO `categorias` (`id_categoria`, `nombre_categoria`) VALUES 
(1, 'Bebidas calientes'),
(2, 'Bebidas frías'),
(3, 'Postres'),
(4, 'Platos');

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES 
(1, 'Administrador'),
(2, 'Cajero'),
(3, 'Mesero'),
(4, 'Barista'),
(5, 'Cocina');

-- 2. DATOS DE MESAS

INSERT INTO `mesas` (`id_mesa`, `numero_mesa`, `capacidad`, `estado`) VALUES 
(1, 'M-01', 2, 'Disponible'),
(2, 'M-02', 2, 'Disponible'),
(3, 'M-03', 4, 'Disponible'),
(4, 'M-04', 4, 'Disponible'),
(5, 'M-05', 4, 'Disponible'),
(6, 'M-06', 2, 'Disponible'),
(7, 'M-07', 2, 'Disponible'),
(8, 'M-08', 4, 'Disponible');

-- 3. DATOS DE USUARIOS (Con marcador de contraseña cifrada)

INSERT INTO `usuarios` (`id_usuario`, `nombre_completo`, `usuario`, `contrasenha`, `id_rol`, `estado`) VALUES 
(1, 'Administrador General', 'admin', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92]', 1, 1),
(2, 'Cajero Principal', 'cajero1', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92]', 2, 1),
(3, 'Juan Pérez', 'mesero1', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92]', 3, 1),
(4, 'María Gómez', 'mesero2', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92]', 3, 1),
(5, 'Carlos Ruiz', 'barista1', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92]', 4, 1),
(6, 'Ana López', 'cocinero1', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92]', 5, 1),
(7, 'Luis Silva', 'auxiliar1', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92]', 5, 1);

-- 4. DATOS DE PRODUCTOS

INSERT INTO `productos` (`codigo_producto`, `nombre`, `fotografia`, `descripcion`, `precio`, `id_categoria`, `disponibilidad`) VALUES 
('P001', 'Café americano', 'cafe-americano.jpg', 'Bebida clásica a base de espresso', 2.00, 1, 1),
('P002', 'Café con leche', 'cafe-con-leche.jpg', 'Espresso con leche texturizada', 2.50, 1, 1),
('P003', 'Cappuccino', 'cappuccino.jpg', 'Espresso con leche vaporizada y espuma', 3.00, 1, 1),
('P004', 'Latte', 'latte.jpg', 'Espresso con leche al vapor', 3.00, 1, 1),
('P005', 'Chocolate caliente', 'chocolate-caliente.jpg', 'Bebida de chocolate y leche', 2.75, 1, 1),
('P006', 'Té caliente', 'te-caliente.jpg', 'Infusión caliente a elección', 1.75, 1, 1),
('P007', 'Iced Coffee', 'iced-coffee.jpg', 'Café servido sobre hielo', 2.50, 2, 1),
('P008', 'Frappé de café', 'frappe-cafe.jpg', 'Mezcla fría con hielo y crema', 3.50, 2, 1),
('P009', 'Frappé de chocolate', 'frappe-chocolate.jpg', 'Bebida de chocolate con hielo y crema', 3.75, 2, 1),
('P010', 'Limonada', 'limonada.jpg', 'Bebida refrescante de limón', 2.00, 2, 1),
('P011', 'Té frío', 'te-frio.jpg', 'Té helado tradicional', 2.00, 2, 1),
('P012', 'Smoothie de fresa', 'smoothie-fresa.jpg', 'Batido espeso y frío de fresas', 3.50, 2, 1),
('P013', 'Cheesecake', 'cheesecake.jpg', 'Tarta de queso con base de galleta', 4.25, 3, 1),
('P014', 'Brownie', 'brownie.jpg', 'Bizcocho de chocolate denso', 2.50, 3, 1),
('P015', 'Pastel de chocolate', 'pastel-chocolate.jpg', 'Rebanada de pastel esponjoso', 3.50, 3, 1),
('P016', 'Galletas con chispas', 'galletas.jpg', 'Galleta con chispas de chocolate', 1.50, 3, 1),
('P017', 'Muffin de vainilla', 'muffin.jpg', 'Panquecito esponjoso sabor vainilla', 2.00, 3, 1),
('P018', 'Desayuno NIXGARU', 'desayuno.jpg', 'Frijoles, huevo, plátano y pan', 5.50, 4, 1),
('P019', 'Sándwich de jamón y queso', 'sandwich-jamon.jpg', 'Clásico sándwich tostado', 3.00, 4, 1),
('P020', 'Sándwich de pollo', 'sandwich-pollo.jpg', 'Sándwich de pollo con aderezos', 3.75, 4, 1),
('P021', 'Panini de pollo', 'panini.jpg', 'Panini prensado con pollo y queso', 4.50, 4, 1),
('P022', 'Croissant de jamón y queso', 'croissant.jpg', 'Croissant relleno y horneado', 3.50, 4, 1);

-- 5. DATOS DE INGREDIENTES E INVENTARIO

INSERT INTO `ingredientes` (`codigo_ingrediente`, `nombre`, `unidad`, `cantidad`, `stock_minimo`, `estado`) VALUES 
('I01', 'Café molido', 'lb', 15.00, 3.00, 'Suficiente'),
('I02', 'Agua', 'L', 50.00, 10.00, 'Suficiente'),
('I03', 'Leche', 'L', 30.00, 8.00, 'Suficiente'),
('I04', 'Azúcar', 'lb', 20.00, 5.00, 'Suficiente'),
('I05', 'Canela', 'lb', 2.00, 0.50, 'Suficiente'),
('I06', 'Chocolate en polvo', 'lb', 8.00, 2.00, 'Suficiente'),
('I07', 'Hielo', 'lb', 40.00, 15.00, 'Suficiente'),
('I08', 'Crema batida', 'L', 10.00, 3.00, 'Suficiente'),
('I09', 'Limón', 'unidad', 80.00, 20.00, 'Suficiente'),
('I10', 'Fresa', 'lb', 12.00, 4.00, 'Suficiente'),
('I11', 'Queso crema', 'lb', 10.00, 3.00, 'Suficiente'),
('I12', 'Galleta', 'lb', 8.00, 2.00, 'Suficiente'),
('I13', 'Huevo', 'unidad', 90.00, 30.00, 'Suficiente'),
('I14', 'Vainilla', 'ml', 1000.00, 200.00, 'Suficiente'),
('I15', 'Harina', 'lb', 30.00, 10.00, 'Suficiente'),
('I16', 'Chocolate', 'lb', 15.00, 4.00, 'Suficiente'),
('I17', 'Mantequilla', 'lb', 18.00, 5.00, 'Suficiente'),
('I18', 'Pan', 'unidad', 60.00, 15.00, 'Suficiente'),
('I19', 'Frijoles', 'lb', 25.00, 5.00, 'Suficiente'),
('I20', 'Queso', 'lb', 15.00, 4.00, 'Suficiente'),
('I21', 'Jamón', 'lb', 12.00, 3.00, 'Suficiente'),
('I22', 'Pollo', 'lb', 20.00, 6.00, 'Suficiente'),
('I23', 'Pan para panini', 'unidad', 30.00, 10.00, 'Suficiente'),
('I24', 'Croissant', 'unidad', 40.00, 12.00, 'Suficiente');