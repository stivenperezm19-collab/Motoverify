<?php
/**
 * Test Suite Integral para el Módulo de Clientes y Compras en Motoverify
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ClienteModel.php';
require_once __DIR__ . '/../../models/VentaModel.php';
require_once __DIR__ . '/../../models/AdministradorModel.php';
require_once __DIR__ . '/../../models/RepuestoModel.php';

echo "========================================================\n";
echo "   INICIANDO PRUEBAS DEL MÓDULO DE CLIENTES Y COMPRAS   \n";
echo "========================================================\n\n";

$tests_passed = 0;
$tests_total = 0;

function assert_test($condition, $name) {
    global $tests_passed, $tests_total;
    $tests_total++;
    if ($condition) {
        $tests_passed++;
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
    }
}

$clienteModel = new ClienteModel($conn);
$ventaModel = new VentaModel($conn);
$adminModel = new AdministradorModel($conn);
$repModel = new RepuestoModel($conn);

// -----------------------------------------------------------------
// 1. Verificación de Roles en BD
// -----------------------------------------------------------------
echo "--- TEST 1: Esquema y Roles ---\n";
$resRol = $conn->query("SELECT * FROM ROL WHERE id_rol = 3 OR nombre_rol = 'Cliente'");
assert_test($resRol && $resRol->num_rows > 0, "Rol 'Cliente' existe en la tabla ROL con id_rol = 3");

// -----------------------------------------------------------------
// 2. Registro de Cliente
// -----------------------------------------------------------------
echo "\n--- TEST 2: Registro de Cliente y Validación de Duplicados ---\n";
$test_email = "test_cliente_" . time() . "@motoverify.com";
$test_pass = "Password123!";
$passHash = password_hash($test_pass, PASSWORD_DEFAULT);

// Crear cliente
$new_client_id = $clienteModel->create(
    "Cliente de Pruebas Unitarias",
    $test_email,
    $passHash,
    "1099887766",
    "3123456789",
    "Calle Falsa 123",
    "Neiva"
);
assert_test($new_client_id > 0, "Cliente registrado exitosamente en DB (ID: $new_client_id)");

$clienteCreado = $clienteModel->getById($new_client_id);
assert_test($clienteCreado && $clienteCreado['id_rol'] == 3, "El cliente tiene asignado estrictamente el rol 'Cliente' (id_rol = 3)");
assert_test($clienteCreado['estado'] === 'Activo', "El cliente se inicializa con estado 'Activo'");

// Verificar prevención de duplicados
$emailDuplicado = $clienteModel->emailExists($test_email);
assert_test($emailDuplicado === true, "Detección de correo duplicado funciona correctamente");

// -----------------------------------------------------------------
// 3. Autenticación y Verificación de Credenciales
// -----------------------------------------------------------------
echo "\n--- TEST 3: Autenticación de Cliente ---\n";
$authCliente = $clienteModel->getByEmail($test_email);
assert_test($authCliente !== null, "Cliente recuperado por correo exitosamente");
assert_test(password_verify($test_pass, $authCliente['clave_hash']), "Contraseña validada correctamente con password_verify()");
assert_test(!password_verify("ClaveErronea123", $authCliente['clave_hash']), "Contraseña incorrecta rechazada exitosamente");

// -----------------------------------------------------------------
// 4. Catálogo y Disponibilidad de Productos
// -----------------------------------------------------------------
echo "\n--- TEST 4: Catálogo de Productos y Stock ---\n";
$resProd = $conn->query("SELECT id_repuesto, nombre, precio, stock FROM REPUESTO WHERE stock > 0 AND (estado = 'Activo' OR estado = 'ACTIVO') LIMIT 1");
$prod = $resProd ? $resProd->fetch_assoc() : null;
assert_test($prod !== null, "Existen repuestos activos con stock en el sistema");

if ($prod) {
    $id_repuesto = (int)$prod['id_repuesto'];
    $precio_unitario = (float)$prod['precio'];
    $stock_inicial = (int)$prod['stock'];

    echo "  -> Repuesto seleccionado para compra: {$prod['nombre']} (Stock actual: $stock_inicial, Precio: $$precio_unitario)\n";

    // -----------------------------------------------------------------
    // 5. Proceso de Compra Transaccional (Checkout)
    // -----------------------------------------------------------------
    echo "\n--- TEST 5: Proceso de Compra y Transacción DB ---\n";
    $cantidad_compra = 1;
    $subtotal_compra = $precio_unitario * $cantidad_compra;
    $impuestos_compra = $subtotal_compra * 0.19;
    $total_compra = $subtotal_compra + $impuestos_compra;

    $detalles = [
        [
            'id_repuesto' => $id_repuesto,
            'cantidad' => $cantidad_compra,
            'precio' => $precio_unitario
        ]
    ];

    $envio = [
        'direccion_envio' => 'Avenida Principal #45-12',
        'ciudad_envio' => 'Neiva',
        'telefono_contacto' => '3123456789',
        'metodo_pago' => 'Contra Entrega',
        'notas' => 'Pedido de prueba automatizada'
    ];

    $id_venta = $ventaModel->crearPedidoCliente(
        $new_client_id,
        $subtotal_compra,
        $impuestos_compra,
        $total_compra,
        $detalles,
        $envio
    );

    assert_test($id_venta > 0, "Pedido registrado con éxito en transacción (ID Venta: $id_venta)");

    // Verificar datos de la venta en la BD
    $qV = $conn->query("SELECT * FROM VENTA WHERE id_venta = $id_venta");
    $ventaRow = $qV->fetch_assoc();
    assert_test($ventaRow['id_cliente'] == $new_client_id, "La venta está asociada al cliente correcto");
    assert_test($ventaRow['id_vendedor'] === null, "El id_vendedor es NULL (compra online autónoma del cliente)");
    assert_test($ventaRow['tipo_venta'] === 'Online', "El tipo de venta es 'Online'");
    assert_test($ventaRow['estado_pedido'] === 'Pendiente', "El estado inicial del pedido es 'Pendiente'");
    assert_test($ventaRow['metodo_pago'] === 'Contra Entrega', "El método de pago es 'Contra Entrega'");

    // Verificar detalle de la venta
    $qD = $conn->query("SELECT * FROM DETALLE_VENTA WHERE id_venta = $id_venta");
    $detalleRow = $qD->fetch_assoc();
    assert_test($detalleRow['id_repuesto'] == $id_repuesto, "El detalle referencia al repuesto correcto");
    assert_test($detalleRow['cantidad'] == $cantidad_compra, "La cantidad en el detalle es correcta");
    assert_test($detalleRow['precio_unitario'] == $precio_unitario, "Se conserva el precio unitario exacto al momento de la compra");

    // Verificar descuento de stock atómico
    $qS = $conn->query("SELECT stock FROM REPUESTO WHERE id_repuesto = $id_repuesto");
    $stock_final = (int)$qS->fetch_assoc()['stock'];
    assert_test($stock_final === ($stock_inicial - $cantidad_compra), "El stock del repuesto se descontó atómicamente ($stock_inicial -> $stock_final)");

    // -----------------------------------------------------------------
    // 6. Seguridad y Aislamiento entre Clientes
    // -----------------------------------------------------------------
    echo "\n--- TEST 6: Seguridad y Aislamiento de Pedidos ---\n";
    // El cliente dueño de la compra puede consultarla
    $pedidoPropio = $ventaModel->getPedidoDetalleCliente($id_venta, $new_client_id);
    assert_test($pedidoPropio !== null && $pedidoPropio['id_venta'] == $id_venta, "El cliente puede consultar su propio pedido");

    // Otro cliente (ej: ID 999999 o ID 1) NO puede consultarla
    $pedidoAjeno = $ventaModel->getPedidoDetalleCliente($id_venta, 999999);
    assert_test($pedidoAjeno === null, "Aislamiento de seguridad: Otro cliente no puede acceder a este pedido");

    // -----------------------------------------------------------------
    // 7. Historial y Estadísticas del Cliente
    // -----------------------------------------------------------------
    echo "\n--- TEST 7: Historial y Estadísticas del Cliente ---\n";
    $pedidosCliente = $ventaModel->getPedidosByCliente($new_client_id);
    assert_test(count($pedidosCliente) >= 1, "El pedido aparece en el historial del cliente");

    $stats = $clienteModel->getEstadisticas($new_client_id);
    assert_test($stats['total_pedidos'] >= 1, "Estadísticas: Conteo de pedidos correcto ({$stats['total_pedidos']})");
    assert_test($stats['total_gastado'] >= $total_compra, "Estadísticas: Total invertido correcto (\${$stats['total_gastado']})");

    // -----------------------------------------------------------------
    // 8. Actualización de Perfil del Cliente
    // -----------------------------------------------------------------
    echo "\n--- TEST 8: Actualización de Perfil ---\n";
    $updRes = $clienteModel->updateProfile(
        $new_client_id,
        "Carlos Modificado",
        "3199999999",
        "1099887766",
        "Carrera 5 #10-20",
        "Neiva"
    );
    assert_test($updRes === true, "Perfil de cliente actualizado con éxito");
    $cliUpd = $clienteModel->getById($new_client_id);
    assert_test($cliUpd['nombre'] === "Carlos Modificado", "Los datos actualizados coinciden en BD");

    // Restaurar el stock del repuesto de prueba para dejar la BD limpia
    $conn->query("UPDATE REPUESTO SET stock = $stock_inicial WHERE id_repuesto = $id_repuesto");
}

echo "\n========================================================\n";
echo "   RESULTADO DE PRUEBAS: $tests_passed de $tests_total APROBADAS\n";
echo "========================================================\n";
?>
