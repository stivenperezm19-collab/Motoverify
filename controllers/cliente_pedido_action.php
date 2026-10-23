<?php
/**
 * Controlador para la gestión del carrito de compras y registro de pedidos por Clientes
 */
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/VentaModel.php';
require_once '../models/RepuestoModel.php';

// Inicializar estructura de carrito en la sesión si no existe
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$ventaModel = new VentaModel($conn);

// Permitir solicitudes JSON y POST estándar
$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $inputData['action'] ?? ($_GET['action'] ?? '');

/**
 * Función auxiliar para obtener detalles frescos y seguros de los productos del carrito desde la BD
 */
function getDetallesCarritoDesdeBD($conn, $carritoSession) {
    $items = [];
    $subtotal = 0.00;

    if (empty($carritoSession)) {
        return [
            'items' => [],
            'subtotal' => 0.00,
            'impuestos' => 0.00,
            'total' => 0.00,
            'total_articulos' => 0
        ];
    }

    $total_articulos = 0;

    foreach ($carritoSession as $id_rep => $itemData) {
        $id = (int)$id_rep;
        $cant = (int)($itemData['cantidad'] ?? 1);
        if ($cant <= 0) continue;

        $query = "SELECT r.id_repuesto, r.nombre, r.precio, r.stock, r.codigo_interno, r.codigo_barras,
                         r.estado, m.nombre as marca_nombre, i.ruta_archivo as imagen_url
                  FROM REPUESTO r
                  LEFT JOIN MARCA m ON r.id_marca = m.id_marca
                  LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
                  LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
                  WHERE r.id_repuesto = $id";
        
        $res = $conn->query($query);
        if ($res && $res->num_rows === 1) {
            $prod = $res->fetch_assoc();
            $precio = (float)$prod['precio'];
            $stock = (int)$prod['stock'];

            // Ajustar cantidad si excede el stock disponible
            if ($cant > $stock) {
                $cant = max(1, $stock);
                $_SESSION['carrito'][$id]['cantidad'] = $cant;
            }

            $subtotal_item = $precio * $cant;
            $subtotal += $subtotal_item;
            $total_articulos += $cant;

            $items[] = [
                'id_repuesto' => $id,
                'nombre' => $prod['nombre'],
                'precio' => $precio,
                'stock' => $stock,
                'cantidad' => $cant,
                'subtotal_item' => $subtotal_item,
                'codigo_interno' => $prod['codigo_interno'],
                'marca' => $prod['marca_nombre'] ?? 'Genérico',
                'imagen_url' => $prod['imagen_url'] ?? '',
                'disponible' => ($stock > 0 && strtoupper($prod['estado']) === 'ACTIVO')
            ];
        } else {
            // Si el producto fue eliminado de la base de datos, removerlo del carrito
            unset($_SESSION['carrito'][$id]);
        }
    }

    $impuestos = $subtotal * 0.19; // IVA 19%
    $total = $subtotal + $impuestos;

    return [
        'items' => $items,
        'subtotal' => round($subtotal, 2),
        'impuestos' => round($impuestos, 2),
        'total' => round($total, 2),
        'total_articulos' => $total_articulos
    ];
}

// ==========================================
// ACCIONES GET
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'obtener_carrito') {
        $carritoCalculado = getDetallesCarritoDesdeBD($conn, $_SESSION['carrito']);
        echo json_encode([
            'status' => 'success',
            'carrito' => $carritoCalculado
        ]);
        exit;
    }

    if ($action === 'contar_items') {
        $count = 0;
        foreach ($_SESSION['carrito'] as $it) {
            $count += (int)($it['cantidad'] ?? 1);
        }
        echo json_encode(['status' => 'success', 'count' => $count]);
        exit;
    }
}

// ==========================================
// ACCIONES POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. AGREGAR PRODUCTO AL CARRITO
    if ($action === 'agregar_carrito') {
        $id_repuesto = (int)($inputData['id_repuesto'] ?? 0);
        $cantidad = max(1, (int)($inputData['cantidad'] ?? 1));

        if ($id_repuesto <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Producto no válido.']);
            exit;
        }

        // Consultar el repuesto y verificar estado y stock real en el servidor
        $query = "SELECT id_repuesto, nombre, precio, stock, estado FROM REPUESTO WHERE id_repuesto = $id_repuesto";
        $res = $conn->query($query);
        if (!$res || $res->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'El repuesto no existe en el catálogo.']);
            exit;
        }

        $prod = $res->fetch_assoc();
        if (strtoupper($prod['estado']) !== 'ACTIVO') {
            echo json_encode(['status' => 'error', 'message' => 'Este producto no está disponible para la venta actualmente.']);
            exit;
        }

        $stockDisponible = (int)$prod['stock'];
        if ($stockDisponible <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Producto sin stock disponible.']);
            exit;
        }

        // Cantidad actual en el carrito
        $cantActual = isset($_SESSION['carrito'][$id_repuesto]) ? (int)$_SESSION['carrito'][$id_repuesto]['cantidad'] : 0;
        $nuevaCant = $cantActual + $cantidad;

        if ($nuevaCant > $stockDisponible) {
            $nuevaCant = $stockDisponible;
            $msg = "Se ajustó al máximo disponible ({$stockDisponible} unid).";
        } else {
            $msg = "¡{$prod['nombre']} añadido al carrito!";
        }

        $_SESSION['carrito'][$id_repuesto] = [
            'id_repuesto' => $id_repuesto,
            'cantidad' => $nuevaCant,
            'agregado_en' => time()
        ];

        $carritoActual = getDetallesCarritoDesdeBD($conn, $_SESSION['carrito']);

        echo json_encode([
            'status' => 'success',
            'message' => $msg,
            'total_articulos' => $carritoActual['total_articulos'],
            'total' => $carritoActual['total']
        ]);
        exit;
    }

    // 2. ACTUALIZAR CANTIDAD EN EL CARRITO
    if ($action === 'actualizar_carrito') {
        $id_repuesto = (int)($inputData['id_repuesto'] ?? 0);
        $cantidad = (int)($inputData['cantidad'] ?? 1);

        if ($id_repuesto <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }

        if ($cantidad <= 0) {
            unset($_SESSION['carrito'][$id_repuesto]);
            $carritoActual = getDetallesCarritoDesdeBD($conn, $_SESSION['carrito']);
            echo json_encode([
                'status' => 'success',
                'message' => 'Producto eliminado del carrito.',
                'carrito' => $carritoActual
            ]);
            exit;
        }

        // Validar stock disponible
        $res = $conn->query("SELECT stock FROM REPUESTO WHERE id_repuesto = $id_repuesto");
        if ($res && $row = $res->fetch_assoc()) {
            $stock = (int)$row['stock'];
            if ($cantidad > $stock) {
                $cantidad = $stock;
            }
        }

        $_SESSION['carrito'][$id_repuesto]['cantidad'] = $cantidad;
        $carritoActual = getDetallesCarritoDesdeBD($conn, $_SESSION['carrito']);

        echo json_encode([
            'status' => 'success',
            'message' => 'Cantidad actualizada.',
            'carrito' => $carritoActual
        ]);
        exit;
    }

    // 3. ELIMINAR ARTÍCULO DEL CARRITO
    if ($action === 'eliminar_carrito') {
        $id_repuesto = (int)($inputData['id_repuesto'] ?? 0);
        if (isset($_SESSION['carrito'][$id_repuesto])) {
            unset($_SESSION['carrito'][$id_repuesto]);
        }
        $carritoActual = getDetallesCarritoDesdeBD($conn, $_SESSION['carrito']);

        echo json_encode([
            'status' => 'success',
            'message' => 'Artículo eliminado del carrito.',
            'carrito' => $carritoActual
        ]);
        exit;
    }

    // 4. VACIAR CARRITO
    if ($action === 'vaciar_carrito') {
        $_SESSION['carrito'] = [];
        echo json_encode([
            'status' => 'success',
            'message' => 'Carrito vaciado exitosamente.',
            'carrito' => getDetallesCarritoDesdeBD($conn, [])
        ]);
        exit;
    }

    // 5. CONFIRMAR Y REALIZAR PEDIDO (CHECKOUT)
    if ($action === 'realizar_pedido') {
        // Verificar que el usuario sea un Cliente autenticado
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
            echo json_encode([
                'status' => 'error',
                'auth_required' => true,
                'message' => 'Debes iniciar sesión con tu cuenta de Cliente para finalizar la compra.'
            ]);
            exit;
        }

        $id_cliente = (int)$_SESSION['user_id'];

        if (empty($_SESSION['carrito'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tu carrito de compras está vacío. Agrega productos antes de confirmar.'
            ]);
            exit;
        }

        // Validar datos de envío
        $direccion = trim($inputData['direccion_envio'] ?? '');
        $ciudad = trim($inputData['ciudad_envio'] ?? '');
        $telefono = trim($inputData['telefono_contacto'] ?? '');
        $metodo_pago = trim($inputData['metodo_pago'] ?? 'Contra Entrega');
        $notas = trim($inputData['notas'] ?? '');

        if (empty($direccion) || empty($ciudad) || empty($telefono)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Por favor completa todos los campos de entrega obligatorios (Dirección, Ciudad y Teléfono).'
            ]);
            exit;
        }

        // Recalcular y validar todos los items estrictamente desde la base de datos
        $carritoCalculado = getDetallesCarritoDesdeBD($conn, $_SESSION['carrito']);
        if (empty($carritoCalculado['items'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No hay productos disponibles para procesar en tu carrito.'
            ]);
            exit;
        }

        $detallesCompra = [];
        foreach ($carritoCalculado['items'] as $item) {
            if (!$item['disponible'] || $item['stock'] < $item['cantidad']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => "El producto {$item['nombre']} ya no cuenta con stock suficiente ({$item['stock']} disponibles). Ajusta tu carrito."
                ]);
                exit;
            }

            $detallesCompra[] = [
                'id_repuesto' => $item['id_repuesto'],
                'cantidad' => $item['cantidad'],
                'precio' => $item['precio'] // Precio real unitario al momento de la compra
            ];
        }

        $envio = [
            'direccion_envio' => $direccion,
            'ciudad_envio' => $ciudad,
            'telefono_contacto' => $telefono,
            'metodo_pago' => $metodo_pago,
            'notas' => $notas
        ];

        // Ejecutar compra transaccional en el modelo
        $id_venta = $ventaModel->crearPedidoCliente(
            $id_cliente,
            $carritoCalculado['subtotal'],
            $carritoCalculado['impuestos'],
            $carritoCalculado['total'],
            $detallesCompra,
            $envio
        );

        if ($id_venta) {
            // Vaciar carrito tras compra exitosa
            $_SESSION['carrito'] = [];

            // También actualizar teléfono y dirección en el perfil del cliente si estaban vacíos
            $conn->query("UPDATE CLIENTE 
                          SET direccion = IF(direccion IS NULL OR direccion = '', '$direccion', direccion),
                              ciudad = IF(ciudad IS NULL OR ciudad = '', '$ciudad', ciudad),
                              telefono = IF(telefono IS NULL OR telefono = '', '$telefono', telefono)
                          WHERE id_cliente = $id_cliente");

            echo json_encode([
                'status' => 'success',
                'message' => '¡Tu pedido ha sido confirmado y registrado con éxito!',
                'id_venta' => $id_venta,
                'redirect' => 'detalle_compra.php?id=' . $id_venta
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo completar el pedido. Es posible que el stock haya cambiado. Por favor revisa tu carrito.'
            ]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Petición no reconocida.']);
exit;
?>
