<?php
require_once 'config/database.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS CLIENTE (
        id_cliente INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        documento VARCHAR(20),
        telefono VARCHAR(20),
        correo VARCHAR(100),
        estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo'
    )",
    "INSERT IGNORE INTO CLIENTE (id_cliente, nombre, documento) VALUES (1, 'Cliente Mostrador', '000000000')",
    "CREATE TABLE IF NOT EXISTS VENTA (
        id_venta INT AUTO_INCREMENT PRIMARY KEY,
        id_vendedor INT NOT NULL,
        id_cliente INT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        subtotal DECIMAL(10,2) NOT NULL,
        impuestos DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        estado ENUM('Completada', 'Cancelada') DEFAULT 'Completada'
    )",
    "CREATE TABLE IF NOT EXISTS DETALLE_VENTA (
        id_detalle INT AUTO_INCREMENT PRIMARY KEY,
        id_venta INT NOT NULL,
        id_repuesto INT NOT NULL,
        cantidad INT NOT NULL,
        precio_unitario DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL
    )"
];

foreach ($queries as $q) {
    if (!$conn->query($q)) {
        echo "Error: " . $conn->error . "<br>";
    } else {
        echo "Exito<br>";
    }
}
?>
