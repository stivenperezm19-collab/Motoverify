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
        $id_cliente = intval($data['id_cliente'] ?? 1); // 1 = Cliente Mostrador
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
}
?>
