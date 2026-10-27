<?php
/**
 * Test integral del nuevo flujo de Pedido Cliente en Tienda:
 * Solicitud de Pedido -> Alistamiento en Bodega -> Listo en Caja -> Pago en Caja -> Entrega en Mostrador
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/VentaModel.php';
require_once __DIR__ . '/../../models/RepuestoModel.php';

function assert_test($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        exit(1);
    }
}

echo "====================================================\n";
echo " INICIANDO PRUEBA DEL FLUJO EN TIENDA (PAGO EN CAJA Y RETIRO)\n";
echo "====================================================\n";

$ventaModel = new VentaModel($conn);

// 1. Obtener un cliente de prueba
$resCli = $conn->query("SELECT id_cliente, nombre FROM CLIENTE LIMIT 1");
if (!$resCli || $resCli->num_rows === 0) {
    die("No hay clientes en la BD para probar.\n");
}
$cliente = $resCli->fetch_assoc();
$id_cliente = (int)$cliente['id_cliente'];
echo "Cliente de prueba: ID {$id_cliente} - {$cliente['nombre']}\n";

// 2. Obtener un vendedor de prueba
$resVen = $conn->query("SELECT id_administrador, usuario FROM ADMINISTRADOR WHERE id_rol = 2 LIMIT 1");
if (!$resVen || $resVen->num_rows === 0) {
    $resVen = $conn->query("SELECT id_administrador, usuario FROM ADMINISTRADOR LIMIT 1");
}
$vendedor = $resVen->fetch_assoc();
$id_vendedor = (int)$vendedor['id_administrador'];
echo "Vendedor de prueba: ID {$id_vendedor} - {$vendedor['usuario']}\n";

// 3. Obtener un repuesto con stock para la prueba
$resRep = $conn->query("SELECT id_repuesto, nombre, precio, stock FROM REPUESTO WHERE stock >= 5 LIMIT 1");
if (!$resRep || $resRep->num_rows === 0) {
    die("No hay repuestos con stock >= 5 para la prueba.\n");
}
$repuesto = $resRep->fetch_assoc();
$id_repuesto = (int)$repuesto['id_repuesto'];
$stock_inicial = (int)$repuesto['stock'];
echo "Repuesto de prueba: ID {$id_repuesto} - {$repuesto['nombre']} (Stock actual: {$stock_inicial})\n";

// PASO 1: Cliente realiza el pedido en el establecimiento (sin envíos)
echo "\n--- PASO 1: CLIENTE SOLICITA PEDIDO EN TIENDA ---\n";
$cantPedida = 2;
$precioUnit = (float)$repuesto['precio'];
$subtotal = $cantPedida * $precioUnit;
$impuestos = $subtotal * 0.19;
$total = $subtotal + $impuestos;

$detalles = [
    [
        'id_repuesto' => $id_repuesto,
        'cantidad' => $cantPedida,
        'precio' => $precioUnit
    ]
];

$envio = [
    'direccion_envio' => 'Retiro en Mostrador / Tienda Física',
    'ciudad_envio' => 'En Establecimiento',
    'telefono_contacto' => '3159876543',
    'metodo_pago' => 'Efectivo en Caja',
    'notas' => 'Cliente retira en el establecimiento'
];

$id_pedido = $ventaModel->crearPedidoCliente($id_cliente, $subtotal, $impuestos, $total, $detalles, $envio);
assert_test($id_pedido > 0, "El pedido en tienda fue creado exitosamente con ID #$id_pedido");

// Verificar estado inicial en BD
$qCheck = $conn->query("SELECT * FROM VENTA WHERE id_venta = $id_pedido");
$pedRow = $qCheck->fetch_assoc();
assert_test($pedRow['estado_pedido'] === 'Pendiente', "El estado del pedido es 'Pendiente' de alistamiento");
assert_test($pedRow['estado_pago'] === 'Pendiente', "El estado del pago es 'Pendiente' (cliente pagará en caja)");
assert_test($pedRow['direccion_envio'] === 'Retiro en Mostrador / Tienda Física', "La dirección es Retiro en Mostrador (sin envíos)");

// Verificar descuento / reserva de stock
$qStock = $conn->query("SELECT stock FROM REPUESTO WHERE id_repuesto = $id_repuesto");
$stock_despues = (int)$qStock->fetch_assoc()['stock'];
assert_test($stock_despues === ($stock_inicial - $cantPedida), "El stock fue reservado/descontado correctamente ($stock_inicial -> $stock_despues)");

// PASO 2: Vendedor inicia alistamiento en bodega
echo "\n--- PASO 2: VENDEDOR INICIA ALISTAMIENTO EN BODEGA ---\n";
$detalleVendedor = $ventaModel->getPedidoCompletoVendedor($id_pedido);
assert_test(!empty($detalleVendedor) && count($detalleVendedor['detalles']) === 1, "Vendedor consulta el detalle del pedido");

$okPrep = $ventaModel->iniciarPreparacionPedido($id_pedido, $id_vendedor, "Repuestos en alistamiento físico en bodega.");
assert_test($okPrep, "Vendedor inició la preparación exitosamente");

$qCheck2 = $conn->query("SELECT estado_pedido, id_vendedor FROM VENTA WHERE id_venta = $id_pedido");
$pedRow2 = $qCheck2->fetch_assoc();
assert_test($pedRow2['estado_pedido'] === 'En Preparación', "El estado del pedido cambió a 'En Preparación'");
assert_test((int)$pedRow2['id_vendedor'] === $id_vendedor, "El pedido fue asignado al vendedor ID $id_vendedor");

// PASO 3: Vendedor termina preparación y lo pasa a caja listo para pago
echo "\n--- PASO 3: VENDEDOR MARCA LISTO PARA PAGO EN CAJA ---\n";
$okListo = $ventaModel->marcarListoParaPago($id_pedido, $id_vendedor, "Repuestos alistados y ubicados en el módulo de caja.");
assert_test($okListo, "Vendedor marcó el pedido como Listo para Pago");

$qCheck3 = $conn->query("SELECT estado_pedido FROM VENTA WHERE id_venta = $id_pedido");
$pedRow3 = $qCheck3->fetch_assoc();
assert_test($pedRow3['estado_pedido'] === 'Listo para Pago', "El estado del pedido cambió a 'Listo para Pago'");

// PASO 4: Cobro en Caja (El cliente cancela en caja mediante efectivo, tarjeta o QR)
echo "\n--- PASO 4: COBRO EN LA CAJA DEL ESTABLECIMIENTO ---\n";
$okCobro = $ventaModel->registrarPagoCaja($id_pedido, $id_vendedor, 'Efectivo en Caja', 'REC-TIENDA-001', 'Pago recibido en efectivo en caja principal');
assert_test($okCobro, "El pago en caja fue registrado exitosamente");

$qCheck4 = $conn->query("SELECT estado_pedido, estado_pago, fecha_pago, metodo_pago FROM VENTA WHERE id_venta = $id_pedido");
$pedRow4 = $qCheck4->fetch_assoc();
assert_test($pedRow4['estado_pedido'] === 'Pagado', "El estado del pedido cambió a 'Pagado'");
assert_test($pedRow4['estado_pago'] === 'Pagado', "El estado de pago cambió a 'Pagado'");
assert_test(!empty($pedRow4['fecha_pago']), "Se registró la fecha y hora del pago en caja: " . $pedRow4['fecha_pago']);

// PASO 5: Entrega presencial en mostrador
echo "\n--- PASO 5: ENTREGA DIRECTA EN EL MOSTRADOR ---\n";
$okEntrega = $ventaModel->entregarPedido($id_pedido, $id_vendedor);
assert_test($okEntrega, "Se confirmó la entrega de repuestos en el mostrador del establecimiento");

$qCheck5 = $conn->query("SELECT estado_pedido FROM VENTA WHERE id_venta = $id_pedido");
$pedRow5 = $qCheck5->fetch_assoc();
assert_test($pedRow5['estado_pedido'] === 'Entregado', "El estado final es 'Entregado'");

// PASO 6: Prueba de Cancelación con Reversión de Stock en Tienda
echo "\n--- PASO 6: PRUEBA DE CANCELACIÓN Y RESTAURACIÓN DE INVENTARIO ---\n";
$id_pedido2 = $ventaModel->crearPedidoCliente($id_cliente, $subtotal, $impuestos, $total, $detalles, $envio);
$stock_antes_cancel = (int)$conn->query("SELECT stock FROM REPUESTO WHERE id_repuesto = $id_repuesto")->fetch_assoc()['stock'];

$okCancel = $ventaModel->cancelarPedidoConReversion($id_pedido2, "Cliente canceló pedido en mostrador");
assert_test($okCancel, "Pedido #$id_pedido2 cancelado exitosamente");

$stock_despues_cancel = (int)$conn->query("SELECT stock FROM REPUESTO WHERE id_repuesto = $id_repuesto")->fetch_assoc()['stock'];
assert_test($stock_despues_cancel === ($stock_antes_cancel + $cantPedida), "El stock fue reintegrado al inventario correctamente tras la cancelación");

$qCheckCan = $conn->query("SELECT estado, estado_pedido FROM VENTA WHERE id_venta = $id_pedido2");
$rowCan = $qCheckCan->fetch_assoc();
assert_test($rowCan['estado'] === 'Cancelada' && $rowCan['estado_pedido'] === 'Cancelado', "El pedido quedó registrado como Cancelado");

echo "\n====================================================\n";
echo " ¡TODAS LAS PRUEBAS DEL FLUJO EN TIENDA PASARON EXITOSAMENTE!\n";
echo "====================================================\n";
