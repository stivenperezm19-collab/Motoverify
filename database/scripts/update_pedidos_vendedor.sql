-- Actualización para el flujo de Pedidos Cliente -> Vendedor -> Pago
-- Modificar estados del pedido
ALTER TABLE VENTA MODIFY COLUMN estado_pedido ENUM('Pendiente','En Preparación','Listo para Pago','Pagado','En Camino','Entregado','Cancelado') DEFAULT 'Pendiente';

-- Agregar estado_pago
SET @exist_pago = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'estado_pago');
SET @sql_pago = IF(@exist_pago = 0, "ALTER TABLE VENTA ADD COLUMN estado_pago ENUM('Pendiente','Pagado') DEFAULT 'Pendiente' AFTER estado_pedido", "SELECT 1");
PREPARE stmt_pago FROM @sql_pago;
EXECUTE stmt_pago;
DEALLOCATE PREPARE stmt_pago;

-- Agregar fecha_pago
SET @exist_fpago = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'fecha_pago');
SET @sql_fpago = IF(@exist_fpago = 0, "ALTER TABLE VENTA ADD COLUMN fecha_pago DATETIME NULL AFTER estado_pago", "SELECT 1");
PREPARE stmt_fpago FROM @sql_fpago;
EXECUTE stmt_fpago;
DEALLOCATE PREPARE stmt_fpago;

-- Agregar comprobante_pago
SET @exist_comp = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'comprobante_pago');
SET @sql_comp = IF(@exist_comp = 0, "ALTER TABLE VENTA ADD COLUMN comprobante_pago VARCHAR(255) NULL AFTER fecha_pago", "SELECT 1");
PREPARE stmt_comp FROM @sql_comp;
EXECUTE stmt_comp;
DEALLOCATE PREPARE stmt_comp;

-- Agregar notas_vendedor
SET @exist_nvend = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VENTA' AND COLUMN_NAME = 'notas_vendedor');
SET @sql_nvend = IF(@exist_nvend = 0, "ALTER TABLE VENTA ADD COLUMN notas_vendedor TEXT NULL AFTER notas", "SELECT 1");
PREPARE stmt_nvend FROM @sql_nvend;
EXECUTE stmt_nvend;
DEALLOCATE PREPARE stmt_nvend;
