-- 1. Tabla de ROLES
CREATE TABLE IF NOT EXISTS ROL (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL
);

-- Insertar roles por defecto si no existen
INSERT INTO ROL (nombre_rol) 
SELECT 'Administrador' WHERE NOT EXISTS (SELECT 1 FROM ROL WHERE nombre_rol = 'Administrador');
INSERT INTO ROL (nombre_rol) 
SELECT 'Vendedor' WHERE NOT EXISTS (SELECT 1 FROM ROL WHERE nombre_rol = 'Vendedor');

-- 2. Modificar tabla ADMINISTRADOR para soportar roles
-- Se asume que no existe la columna id_rol, de lo contrario esto dará error (se debe manejar en PHP si se requiere)
ALTER TABLE ADMINISTRADOR ADD COLUMN id_rol INT DEFAULT 1;
ALTER TABLE ADMINISTRADOR ADD CONSTRAINT fk_admin_rol FOREIGN KEY (id_rol) REFERENCES ROL(id_rol);

-- 3. Modificar tabla REPUESTO para ventas
ALTER TABLE REPUESTO ADD COLUMN precio DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE REPUESTO ADD COLUMN stock INT DEFAULT 0;

-- 4. Tabla CLIENTE
CREATE TABLE IF NOT EXISTS CLIENTE (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(20),
    telefono VARCHAR(20),
    correo VARCHAR(100),
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo'
);

-- Cliente por defecto para ventas de mostrador
INSERT INTO CLIENTE (nombre, documento) 
SELECT 'Cliente Mostrador', '000000000' WHERE NOT EXISTS (SELECT 1 FROM CLIENTE WHERE nombre = 'Cliente Mostrador');

-- 5. Tabla VENTA
CREATE TABLE IF NOT EXISTS VENTA (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_vendedor INT NOT NULL,
    id_cliente INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('Completada', 'Cancelada') DEFAULT 'Completada',
    FOREIGN KEY (id_vendedor) REFERENCES ADMINISTRADOR(id_administrador),
    FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
);

-- 6. Tabla DETALLE_VENTA
CREATE TABLE IF NOT EXISTS DETALLE_VENTA (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_repuesto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES VENTA(id_venta),
    FOREIGN KEY (id_repuesto) REFERENCES REPUESTO(id_repuesto)
);
