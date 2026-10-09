<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/VentaModel.php';

// Verificar permisos
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autenticado.']);
    exit;
}

$ventaModel = new VentaModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'get_detalle') {
        $id_venta = intval($_GET['id_venta'] ?? 0);
        if ($id_venta > 0) {
            $query = "SELECT vd.id_repuesto, vd.cantidad, vd.precio_unitario, r.nombre, r.codigo_barras, r.codigo_interno 
                      FROM DETALLE_VENTA vd 
                      JOIN REPUESTO r ON vd.id_repuesto = r.id_repuesto 
                      WHERE vd.id_venta = $id_venta";
            $res = $conn->query($query);
            $detalle = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $detalle[] = $row;
                }
            }
            echo json_encode(['status' => 'success', 'detalle' => $detalle]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID de venta inválido.']);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'guardar_venta') {
        // Datos enviados desde el carrito vía JSON o form normal
        // Usaremos JSON raw para facilitar arrays complejos desde el frontend
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data && isset($_POST['carrito'])) {
            $data = $_POST;
        }

        $carrito = $data['carrito'] ?? [];
        $cliente_doc = trim($data['cliente_doc'] ?? '');
        $cliente_nombre = trim($data['cliente_nombre'] ?? '');
        $id_cliente = 1; // 1 = Cliente Mostrador por defecto

        if (!empty($cliente_doc)) {
            $docEsc = $conn->real_escape_string($cliente_doc);
            $qCli = $conn->query("SELECT id_cliente FROM CLIENTE WHERE documento = '$docEsc'");
            if ($qCli && $qCli->num_rows > 0) {
                $id_cliente = $qCli->fetch_assoc()['id_cliente'];
            } else {
                // Crear nuevo cliente
                $nomEsc = $conn->real_escape_string(!empty($cliente_nombre) ? $cliente_nombre : 'Cliente ' . $cliente_doc);
                $conn->query("INSERT INTO CLIENTE (documento, nombre) VALUES ('$docEsc', '$nomEsc')");
                $id_cliente = $conn->insert_id;
            }
        }

        $id_vendedor = $_SESSION['user_id'];

        if (empty($carrito)) {
            echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío.']);
            exit;
        }

        // Calcular totales en backend (Nunca confiar en el frontend)
        $subtotal = 0;
        $detalles = [];

        foreach ($carrito as $item) {
            $id_repuesto = intval($item['id_repuesto']);
            $cantidad = intval($item['cantidad']);
            
            if ($cantidad <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Cantidad inválida para el repuesto ID: ' . $id_repuesto]);
                exit;
            }

            // Consultar el precio real y stock en BD
            $q = $conn->query("SELECT precio, stock FROM REPUESTO WHERE id_repuesto = " . $id_repuesto);
            if ($q && $q->num_rows > 0) {
                $row = $q->fetch_assoc();
                $precio_real = floatval($row['precio']);
                $stock_actual = intval($row['stock']);

                if ($stock_actual < $cantidad) {
                    echo json_encode(['status' => 'error', 'message' => 'Stock insuficiente. Solicitado: ' . $cantidad . ', Disponible: ' . $stock_actual]);
                    exit;
                }

                $subtotal += ($precio_real * $cantidad);
                $detalles[] = [
                    'id_repuesto' => $id_repuesto,
                    'cantidad' => $cantidad,
                    'precio' => $precio_real
                ];
            } else {
                echo json_encode(['status' => 'error', 'message' => 'El repuesto ID: ' . $id_repuesto . ' no existe.']);
                exit;
            }
        }

        $impuestos = $subtotal * 0.19; // Ejemplo: 19% IVA
        $total = $subtotal + $impuestos;

        $id_venta = $ventaModel->crearVenta($id_vendedor, $id_cliente, $subtotal, $impuestos, $total, $detalles);

        if ($id_venta) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Venta registrada exitosamente.',
                'id_venta' => $id_venta
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al registrar la venta. Posible falta de stock.']);
        }
    }

    if ($action === 'anular_venta') {
        if ($_SESSION['rol'] !== 'Administrador') {
            echo json_encode(['status' => 'error', 'message' => 'Solo el administrador puede anular ventas.']);
            exit;
        }

        $id_venta = intval($_POST['id_venta'] ?? 0);
        if ($id_venta > 0) {
            if ($ventaModel->anularVenta($id_venta)) {
                echo json_encode(['status' => 'success', 'message' => 'La venta ha sido anulada y el stock restaurado.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al anular la venta.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID de venta inválido.']);
        }
    }

    if ($action === 'modificar_venta') {
        if ($_SESSION['rol'] !== 'Administrador') {
            echo json_encode(['status' => 'error', 'message' => 'Solo el administrador puede modificar ventas.']);
            exit;
        }

        $id_venta = intval($_POST['id_venta'] ?? 0);
        $cantidades = $_POST['cantidades'] ?? [];

        if ($id_venta > 0 && !empty($cantidades)) {
            if ($ventaModel->modificarVenta($id_venta, $cantidades)) {
                echo json_encode(['status' => 'success', 'message' => 'Venta modificada exitosamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar la venta. Verifique el stock o el estado.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
        }
    }
}
?>
