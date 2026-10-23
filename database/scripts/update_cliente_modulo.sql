-- Actualización para el módulo de Clientes y Compras en Motoverify
-- Asegura que el rol Cliente exista
INSERT INTO ROL (id_rol, nombre_rol) 
SELECT 3, 'Cliente' 
WHERE NOT EXISTS (SELECT 1 FROM ROL WHERE nombre_rol = 'Cliente' OR id_rol = 3);

-- Asegurar que la tabla CLIENTE tenga los campos necesarios
CREATE TABLE IF NOT EXISTS CLIENTE (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(20),
    telefono VARCHAR(20),
    correo VARCHAR(100),
    clave_hash VARCHAR(255),
    direccion VARCHAR(255),
    ciudad VARCHAR(100),
    id_rol INT DEFAULT 3,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo'
);

-- Si la tabla cliente ya existía, asegurar columnas
-- Limpiar correos vacíos a NULL para que no interfieran con el índice UNIQUE
UPDATE CLIENTE SET correo = NULL WHERE correo = '';

-- Agregar índice único en correo si no existe
SET @exist_idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'CLIENTE' 
                  AND INDEX_NAME = 'uq_cliente_correo');

SET @sql_idx = IF(@exist_idx = 0, 'ALTER TABLE CLIENTE ADD UNIQUE INDEX uq_cliente_correo (correo)', 'SELECT 1');
PREPARE stmt FROM @sql_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Asegurar que VENTA permita id_vendedor NULL (para compras online de clientes)
ALTER TABLE VENTA MODIFY COLUMN id_vendedor SMALLINT NULL;

-- Asegurar que id_cliente en VENTA esté indexado
SET @exist_fk_cli = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'VENTA' 
                     AND CONSTRAINT_NAME = 'venta_ibfk_2');

-- Asegurar columnas para compras online en VENTA
SET @exist_tipo = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'tipo_venta');
SET @sql_tipo = IF(@exist_tipo = 0, "ALTER TABLE VENTA ADD COLUMN tipo_venta ENUM('Tienda','Online') DEFAULT 'Online'", "SELECT 1");
PREPARE stmt2 FROM @sql_tipo;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @exist_metodo = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'metodo_pago');
SET @sql_metodo = IF(@exist_metodo = 0, "ALTER TABLE VENTA ADD COLUMN metodo_pago VARCHAR(50) DEFAULT 'Contra Entrega'", "SELECT 1");
PREPARE stmt3 FROM @sql_metodo;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

SET @exist_dir = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'direccion_envio');
SET @sql_dir = IF(@exist_dir = 0, "ALTER TABLE VENTA ADD COLUMN direccion_envio VARCHAR(255) NULL", "SELECT 1");
PREPARE stmt4 FROM @sql_dir;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

SET @exist_ciu = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'ciudad_envio');
SET @sql_ciu = IF(@exist_ciu = 0, "ALTER TABLE VENTA ADD COLUMN ciudad_envio VARCHAR(100) NULL", "SELECT 1");
PREPARE stmt5 FROM @sql_ciu;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

SET @exist_tel = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'telefono_contacto');
SET @sql_tel = IF(@exist_tel = 0, "ALTER TABLE VENTA ADD COLUMN telefono_contacto VARCHAR(20) NULL", "SELECT 1");
PREPARE stmt6 FROM @sql_tel;
EXECUTE stmt6;
DEALLOCATE PREPARE stmt6;

SET @exist_est_ped = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'estado_pedido');
SET @sql_est_ped = IF(@exist_est_ped = 0, "ALTER TABLE VENTA ADD COLUMN estado_pedido ENUM('Pendiente','Confirmado','En Camino','Entregado','Cancelado') DEFAULT 'Pendiente'", "SELECT 1");
PREPARE stmt7 FROM @sql_est_ped;
EXECUTE stmt7;
DEALLOCATE PREPARE stmt7;

SET @exist_notas = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'notas');
SET @sql_notas = IF(@exist_notas = 0, "ALTER TABLE VENTA ADD COLUMN notas TEXT NULL", "SELECT 1");
PREPARE stmt8 FROM @sql_notas;
EXECUTE stmt8;
DEALLOCATE PREPARE stmt8;
