<?php
require_once __DIR__ . '/../../config/database.php';

echo "Running migration for Pedidos Vendedor / Cliente flow...\n";

// 1. Modificar columna estado_pedido para soportar el ciclo completo
$sql1 = "ALTER TABLE VENTA MODIFY COLUMN estado_pedido ENUM('Pendiente','En Preparación','Listo para Pago','Pagado','En Camino','Entregado','Cancelado') DEFAULT 'Pendiente'";
if ($conn->query($sql1)) {
    echo "1. estado_pedido enum updated successfully.\n";
} else {
    echo "1. Error updating estado_pedido: " . $conn->error . "\n";
}

// 2. Agregar estado_pago si no existe
$checkCol = $conn->query("SHOW COLUMNS FROM VENTA LIKE 'estado_pago'");
if ($checkCol->num_rows === 0) {
    $sql2 = "ALTER TABLE VENTA ADD COLUMN estado_pago ENUM('Pendiente','Pagado') DEFAULT 'Pendiente' AFTER estado_pedido";
    if ($conn->query($sql2)) {
        echo "2. Column estado_pago added successfully.\n";
    } else {
        echo "2. Error adding estado_pago: " . $conn->error . "\n";
    }
} else {
    echo "2. Column estado_pago already exists.\n";
}

// 3. Agregar fecha_pago si no existe
$checkCol = $conn->query("SHOW COLUMNS FROM VENTA LIKE 'fecha_pago'");
if ($checkCol->num_rows === 0) {
    $sql3 = "ALTER TABLE VENTA ADD COLUMN fecha_pago DATETIME NULL AFTER estado_pago";
    if ($conn->query($sql3)) {
        echo "3. Column fecha_pago added successfully.\n";
    } else {
        echo "3. Error adding fecha_pago: " . $conn->error . "\n";
    }
} else {
    echo "3. Column fecha_pago already exists.\n";
}

// 4. Agregar notas_vendedor si no existe
$checkCol = $conn->query("SHOW COLUMNS FROM VENTA LIKE 'notas_vendedor'");
if ($checkCol->num_rows === 0) {
    $sql4 = "ALTER TABLE VENTA ADD COLUMN notas_vendedor TEXT NULL AFTER notas";
    if ($conn->query($sql4)) {
        echo "4. Column notas_vendedor added successfully.\n";
    } else {
        echo "4. Error adding notas_vendedor: " . $conn->error . "\n";
    }
} else {
    echo "4. Column notas_vendedor already exists.\n";
}

// 5. Agregar comprobante_pago si no existe
$checkCol = $conn->query("SHOW COLUMNS FROM VENTA LIKE 'comprobante_pago'");
if ($checkCol->num_rows === 0) {
    $sql5 = "ALTER TABLE VENTA ADD COLUMN comprobante_pago VARCHAR(255) NULL AFTER fecha_pago";
    if ($conn->query($sql5)) {
        echo "5. Column comprobante_pago added successfully.\n";
    } else {
        echo "5. Error adding comprobante_pago: " . $conn->error . "\n";
    }
} else {
    echo "5. Column comprobante_pago already exists.\n";
}

echo "Migration finished.\n";
