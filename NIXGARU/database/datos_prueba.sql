USE `nixgaru_db`;

-- 1. Categorías del menú
INSERT INTO `categorias` (`id_categoria`, `nombre_categoria`) VALUES
(1, 'Bebidas calientes'),
(2, 'Bebidas frías'),
(3, 'Postres'),
(4, 'Platos');

-- 2. Roles del sistema
INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Administrador'),
(2, 'Cajero'),
(3, 'Mesero'),
(4, 'Barista'),
(5, 'Cocina');

-- 3. Usuarios operativos (Contraseña: 1234 en formato bcrypt nativo de PHP)
INSERT INTO `usuarios` (`nombre_completo`, `usuario`, `contrasenha`, `id_rol`, `estado`) VALUES
('María López', 'admin', '$2y$10$.DcVMBtAGNxsnm.rWG/.y.TwZn52vqDMlJBCtF8d.dMqcr/cU6yLG', 1, 1),
('Carlos Méndez', 'cmendez', '$2y$10$.DcVMBtAGNxsnm.rWG/.y.TwZn52vqDMlJBCtF8d.dMqcr/cU6yLG', 2, 1),
('Ana Ruiz', 'aruiz', '$2y$10$.DcVMBtAGNxsnm.rWG/.y.TwZn52vqDMlJBCtF8d.dMqcr/cU6yLG', 3, 1),
('Luis Gil', 'lgil', '$2y$10$.DcVMBtAGNxsnm.rWG/.y.TwZn52vqDMlJBCtF8d.dMqcr/cU6yLG', 3, 1),
('Sofía Castro', 'scastro', '$2y$10$.DcVMBtAGNxsnm.rWG/.y.TwZn52vqDMlJBCtF8d.dMqcr/cU6yLG', 4, 1),
('Jorge Díaz', 'jdiaz', '$2y$10$.DcVMBtAGNxsnm.rWG/.y.TwZn52vqDMlJBCtF8d.dMqcr/cU6yLG', 5, 1),
('Elena Torres', 'etorres', '$2y$10$.DcVMBtAGNxsnm.rWG/.y.TwZn52vqDMlJBCtF8d.dMqcr/cU6yLG', 5, 1);

-- 4. Mesas físicas (Total: 24 personas)
INSERT INTO `mesas` (`id_mesa`, `numero_mesa`, `capacidad`, `estado`) VALUES
(1, 'M-01', 2, 'Disponible'),
(2, 'M-02', 2, 'Ocupada'),
(3, 'M-03', 4, 'Disponible'),
(4, 'M-04', 4, 'Disponible'),
(5, 'M-05', 4, 'Disponible'),
(6, 'M-06', 2, 'Disponible'),
(7, 'M-07', 2, 'Disponible'),
(8, 'M-08', 4, 'Disponible');

-- 5. Inventario de Ingredientes (Muestra inicial)
INSERT INTO `ingredientes` (`codigo_ingrediente`, `nombre`, `unidad`, `cantidad`, `stock_minimo`, `estado`) VALUES
('I01', 'Café molido', 'lb', 15.50, 5.00, 'Suficiente'),
('I03', 'Leche', 'L', 22.00, 10.00, 'Suficiente'),
('I13', 'Huevo', 'unidad', 30.00, 12.00, 'Suficiente'),
('I18', 'Pan', 'unidad', 15.00, 5.00, 'Suficiente'),
('I24', 'Croissant', 'unidad', 12.00, 5.00, 'Suficiente');

-- 6. Catálogo de Productos
INSERT INTO `productos` (`codigo_producto`, `nombre`, `fotografia`, `descripcion`, `precio`, `id_categoria`, `disponibilidad`) VALUES
('P001', 'Café americano', 'cafe-americano.jpg', 'Bebida clásica a base de espresso', 2.00, 1, 1),
('P003', 'Cappuccino', 'cappuccino.jpg', 'Espresso con leche vaporizada y espuma', 3.00, 1, 1),
('P008', 'Frappé de café', 'frappe-cafe.jpg', 'Mezcla fría con hielo y crema', 3.50, 2, 1),
('P013', 'Cheesecake', 'cheesecake.jpg', 'Tarta de queso con base de galleta', 4.25, 3, 1),
('P014', 'Brownie', 'brownie.jpg', 'Bizcocho de chocolate denso', 2.50, 3, 1),
('P018', 'Desayuno NIXGARU', 'desayuno.jpg', 'Frijoles, huevo, plátano y pan', 5.50, 4, 1),
('P020', 'Sándwich de pollo', 'sandwich-pollo.jpg', 'Sándwich de pollo con aderezos', 3.75, 4, 1),
('P022', 'Croissant de jamón y queso', 'croissant.jpg', 'Croissant relleno y horneado', 3.50, 4, 1);

-- 7. Recetas (Relación entre productos e ingredientes)
INSERT INTO `recetas` (`codigo_producto`, `codigo_ingrediente`, `cantidad_necesaria`) VALUES
('P001', 'I01', 0.05),
('P003', 'I01', 0.05),
('P003', 'I03', 0.20),
('P018', 'I13', 2.00),
('P018', 'I18', 2.00),
('P022', 'I24', 1.00);

-- 8. Pedidos (Ordenes de prueba)
INSERT INTO `pedidos` (`id_pedido`, `id_mesa`, `id_mesero`, `estado_pedido`, `fecha_hora`) VALUES
(1, 2, 3, 'En preparación', '2026-10-18 10:30:00'),
(2, 4, 4, 'Pagado', '2026-10-18 09:15:00');

-- 9. Detalles de los pedidos
INSERT INTO `detalles_pedido` (`id_pedido`, `codigo_producto`, `cantidadPed`, `precio_unitario`) VALUES
(1, 'P001', 2, 2.00),
(1, 'P014', 1, 2.50),
(2, 'P018', 2, 5.50),
(2, 'P003', 2, 3.00);

-- 10. Cuentas finalizadas para reportes y dashboard
INSERT INTO `cuentas` (`id_pedido`, `subtotal`, `propina`, `total`, `efectivo_recibido`, `cambio`, `fecha_hora_pago`, `id_cajero`) VALUES
(2, 17.00, 1.70, 18.70, 20.00, 1.30, '2026-10-18 10:05:00', 2);