-- 1. Agregar columna estado a ADMINISTRADOR si no existe
ALTER TABLE ADMINISTRADOR ADD COLUMN estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo';

-- 2. Asegurarnos que exista la tabla ROL y los roles básicos (repetir si no se ejecutó el de ventas)
CREATE TABLE IF NOT EXISTS ROL (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL
);

INSERT INTO ROL (nombre_rol) 
SELECT 'Administrador' WHERE NOT EXISTS (SELECT 1 FROM ROL WHERE nombre_rol = 'Administrador');
INSERT INTO ROL (nombre_rol) 
SELECT 'Vendedor' WHERE NOT EXISTS (SELECT 1 FROM ROL WHERE nombre_rol = 'Vendedor');

-- Agregar id_rol si no existe
-- ALTER TABLE ADMINISTRADOR ADD COLUMN id_rol INT DEFAULT 1;
-- ALTER TABLE ADMINISTRADOR ADD CONSTRAINT fk_admin_rol FOREIGN KEY (id_rol) REFERENCES ROL(id_rol);
