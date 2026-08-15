CREATE TABLE `roles` (
  `id_rol` int PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(50) UNIQUE NOT NULL,
  `descripcion` varchar(255)
);

CREATE TABLE `usuarios` (
  `id_usuario` int PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_rol` int NOT NULL,
  `estado` boolean DEFAULT true,
  `fecha_creacion` datetime
);

CREATE TABLE `proveedores` (
  `id_proveedor` int PRIMARY KEY AUTO_INCREMENT,
  `nit` varchar(30) UNIQUE NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `direccion` varchar(150),
  `telefono` varchar(30),
  `email` varchar(100),
  `estado` boolean DEFAULT true
);

CREATE TABLE `categorias` (
  `id_categoria` int PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255),
  `estado` boolean DEFAULT true
);

CREATE TABLE `productos` (
  `id_producto` int PRIMARY KEY AUTO_INCREMENT,
  `codigo` varchar(50) UNIQUE NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `id_categoria` int NOT NULL,
  `descripcion` varchar(255),
  `unidad_medida` varchar(50),
  `precio_compra` decimal(12,2) NOT NULL,
  `precio_venta` decimal(12,2) NOT NULL,
  `stock_actual` decimal(12,2) DEFAULT 0,
  `stock_minimo` decimal(12,2) DEFAULT 0,
  `estado` boolean DEFAULT true
);

CREATE TABLE `clientes` (
  `id_cliente` int PRIMARY KEY AUTO_INCREMENT,
  `tipo_documento` varchar(30),
  `numero_documento` varchar(30) UNIQUE NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(30),
  `direccion` varchar(150),
  `email` varchar(100),
  `estado` boolean DEFAULT true
);

CREATE TABLE `entradas` (
  `id_entrada` int PRIMARY KEY AUTO_INCREMENT,
  `id_proveedor` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha` datetime NOT NULL,
  `numero_documento` varchar(50),
  `total` decimal(12,2) DEFAULT 0,
  `observacion` varchar(255)
);

CREATE TABLE `detalle_entradas` (
  `id_detalle_entrada` int PRIMARY KEY AUTO_INCREMENT,
  `id_entrada` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `precio_compra` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
);

CREATE TABLE `ventas` (
  `id_venta` int PRIMARY KEY AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_vendedor` int NOT NULL,
  `fecha` datetime NOT NULL,
  `numero_factura` varchar(50) UNIQUE NOT NULL,
  `subtotal` decimal(12,2) DEFAULT 0,
  `descuento` decimal(12,2) DEFAULT 0,
  `iva` decimal(12,2) DEFAULT 0,
  `total` decimal(12,2) DEFAULT 0,
  `estado` varchar(30) DEFAULT 'ACTIVA'
);

CREATE TABLE `detalle_ventas` (
  `id_detalle_venta` int PRIMARY KEY AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `costo_unitario` decimal(12,2) NOT NULL,
  `descuento` decimal(12,2) DEFAULT 0,
  `subtotal` decimal(12,2) NOT NULL
);

CREATE TABLE `salidas` (
  `id_salida` int PRIMARY KEY AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fecha` datetime NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `motivo` varchar(255),
  `total` decimal(12,2) DEFAULT 0,
  `observacion` varchar(255)
);

CREATE TABLE `detalle_salidas` (
  `id_detalle_salida` int PRIMARY KEY AUTO_INCREMENT,
  `id_salida` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `costo_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
);

CREATE TABLE `movimientos_inventario` (
  `id_movimiento` int PRIMARY KEY AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `id_usuario` int NOT NULL,
  `tipo_movimiento` varchar(30) NOT NULL,
  `id_entrada` int,
  `id_venta` int,
  `id_salida` int,
  `cantidad` decimal(12,2) NOT NULL,
  `costo_unitario` decimal(12,2),
  `precio_venta` decimal(12,2),
  `stock_anterior` decimal(12,2),
  `stock_nuevo` decimal(12,2),
  `fecha` datetime NOT NULL,
  `observacion` varchar(255)
);

CREATE TABLE `ingresos` (
  `id_ingreso` int PRIMARY KEY AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `fecha` datetime NOT NULL,
  `concepto` varchar(150),
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` varchar(50)
);

CREATE TABLE `egresos` (
  `id_egreso` int PRIMARY KEY AUTO_INCREMENT,
  `id_entrada` int,
  `fecha` datetime NOT NULL,
  `concepto` varchar(150),
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` varchar(50)
);

CREATE TABLE `bitacora_movimientos` (
  `id_bitacora` int PRIMARY KEY AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `accion` varchar(100) NOT NULL,
  `modulo` varchar(100),
  `descripcion` text,
  `fecha` datetime NOT NULL
);

ALTER TABLE `usuarios` ADD FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

ALTER TABLE `productos` ADD FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`);

ALTER TABLE `entradas` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`);

ALTER TABLE `entradas` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `detalle_entradas` ADD FOREIGN KEY (`id_entrada`) REFERENCES `entradas` (`id_entrada`);

ALTER TABLE `detalle_entradas` ADD FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

ALTER TABLE `ventas` ADD FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);

ALTER TABLE `ventas` ADD FOREIGN KEY (`id_vendedor`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `detalle_ventas` ADD FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`);

ALTER TABLE `detalle_ventas` ADD FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

ALTER TABLE `salidas` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `detalle_salidas` ADD FOREIGN KEY (`id_salida`) REFERENCES `salidas` (`id_salida`);

ALTER TABLE `detalle_salidas` ADD FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

ALTER TABLE `movimientos_inventario` ADD FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

ALTER TABLE `movimientos_inventario` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `movimientos_inventario` ADD FOREIGN KEY (`id_entrada`) REFERENCES `entradas` (`id_entrada`);

ALTER TABLE `movimientos_inventario` ADD FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`);

ALTER TABLE `movimientos_inventario` ADD FOREIGN KEY (`id_salida`) REFERENCES `salidas` (`id_salida`);

ALTER TABLE `ingresos` ADD FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`);

ALTER TABLE `egresos` ADD FOREIGN KEY (`id_entrada`) REFERENCES `entradas` (`id_entrada`);

ALTER TABLE `bitacora_movimientos` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);
