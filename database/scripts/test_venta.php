<?php
session_start();
$_SESSION['user_id'] = 2; // Asumir vendedor
$_SESSION['rol'] = 'Vendedor';

require_once 'config/database.php';
require_once 'models/VentaModel.php';

$ventaModel = new VentaModel($conn);

$detalles = [
    [
        'id_repuesto' => 24,
        'cantidad' => 1,
        'precio' => 120.00
    ]
];

$res = $ventaModel->crearVenta(2, 1, 120.00, 22.80, 142.80, $detalles);
if ($res) {
    echo "Venta OK: ID " . $res;
} else {
    // VentaModel catch block returns false, let's look at the DB errors.
    echo "Fallo Venta: " . $conn->error;
}
?>
