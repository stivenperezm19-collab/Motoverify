<?php
/**
 * Controlador para la gestión y preparación de pedidos de clientes por parte del rol Vendedor (y Administrador)
 */
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/VentaModel.php';

// Validar que el usuario tenga rol de Vendedor o Administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Vendedor', 'Administrador'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Se requiere rol de Vendedor.']);
    exit;
}

$id_vendedor = (int)$_SESSION['user_id'];
$ventaModel = new VentaModel($conn);

// Permitir solicitudes JSON y POST estándar
$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $inputData['action'] ?? ($_GET['action'] ?? '');

// ==========================================
// PETICIONES GET
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. Obtener conteo de pedidos pendientes / en atención
    if ($action === 'contar_pendientes') {
        $conteo = $ventaModel->contarPedidosPendientesVendedor();
        echo json_encode(['status' => 'success', 'conteo' => $conteo]);
        exit;
    }

    // 2. Obtener detalle completo de un pedido con verificación de inventario
    if ($action === 'obtener_detalle') {
        $id_venta = (int)($_GET['id_venta'] ?? 0);
        if ($id_venta <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de pedido no válido.']);
            exit;
        }

        $pedido = $ventaModel->getPedidoCompletoVendedor($id_venta);
        if (!$pedido) {
            echo json_encode(['status' => 'error', 'message' => 'El pedido solicitado no fue encontrado.']);
            exit;
        }

        echo json_encode(['status' => 'success', 'pedido' => $pedido]);
        exit;
    }

    // 3. Listar pedidos con filtros
    if ($action === 'listar') {
        $filtros = [
            'estado_pedido' => trim($_GET['estado'] ?? ''),
            'search' => trim($_GET['search'] ?? '')
        ];
        $pedidos = $ventaModel->getPedidosClientes($filtros);
        echo json_encode(['status' => 'success', 'pedidos' => $pedidos]);
        exit;
    }
}

// ==========================================
// PETICIONES POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_venta = (int)($inputData['id_venta'] ?? 0);
    $notas = trim($inputData['notas_vendedor'] ?? '');

    if ($id_venta <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Identificador de pedido requerido.']);
        exit;
    }

    // 1. INICIAR PREPARACIÓN DEL PRODUCTO
    if ($action === 'iniciar_preparacion') {
        $ok = $ventaModel->iniciarPreparacionPedido($id_venta, $id_vendedor, $notas);
        if ($ok) {
            echo json_encode([
                'status' => 'success',
                'message' => '¡Has tomado el pedido! El estado cambió a "En Preparación". Ahora puedes revisar y alistar los repuestos.'
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo iniciar la preparación. Es posible que el pedido ya no esté en estado "Pendiente".'
            ]);
            exit;
        }
    }

    // 2. MARCAR COMO LISTO PARA PAGO (PRODUCTO PREPARADO)
    if ($action === 'marcar_listo_pago') {
        $ok = $ventaModel->marcarListoParaPago($id_venta, $id_vendedor, $notas);
        if ($ok) {
            echo json_encode([
                'status' => 'success',
                'message' => '¡Repuestos preparados con éxito! El pedido quedó en estado "Listo para Pago". El cliente ya puede proceder a cancelar el pedido.'
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo marcar como listo para pago. Verifica el estado actual del pedido.'
            ]);
            exit;
        }
    }

    // 3. DESPACHAR PEDIDO (EN CAMINO)
    if ($action === 'despachar_pedido') {
        $ok = $ventaModel->despacharPedido($id_venta, $id_vendedor, $notas);
        if ($ok) {
            echo json_encode([
                'status' => 'success',
                'message' => '¡Pedido despachado exitosamente! El estado ahora es "En Camino".'
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo despachar el pedido. Verifica que esté en estado Pagado o Listo para Pago.'
            ]);
            exit;
        }
    }

    // 3. REGISTRAR COBRO / PAGO EN CAJA POR EL VENDEDOR O CAJERO
    if ($action === 'registrar_pago_caja') {
        $metodo_pago = trim($inputData['metodo_pago'] ?? 'Efectivo en Caja');
        $comprobante = trim($inputData['comprobante'] ?? '');
        $ok = $ventaModel->registrarPagoCaja($id_venta, $id_vendedor, $metodo_pago, $comprobante, $notas);
        if ($ok) {
            echo json_encode([
                'status' => 'success',
                'message' => '¡Pago en caja registrado exitosamente! El cliente ya puede retirar sus repuestos.'
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo registrar el cobro en caja. Verifica el estado del pedido.'
            ]);
            exit;
        }
    }

    // 4. CONFIRMAR ENTREGA EN MOSTRADOR (TIENDA FÍSICA)
    if ($action === 'entregar_pedido') {
        $ok = $ventaModel->entregarPedido($id_venta, $id_vendedor);
        if ($ok) {
            echo json_encode([
                'status' => 'success',
                'message' => '¡Repuestos entregados al cliente en mostrador exitosamente!'
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo registrar la entrega en mostrador. Verifica el estado del pedido.'
            ]);
            exit;
        }
    }

    // 5. RECHAZAR O CANCELAR PEDIDO (RESTAURANDO STOCK)
    if ($action === 'cancelar_pedido') {
        $motivo = trim($inputData['motivo'] ?? 'Cancelado por el vendedor en revisión');
        $ok = $ventaModel->cancelarPedidoConReversion($id_venta, $motivo);
        if ($ok) {
            echo json_encode([
                'status' => 'success',
                'message' => 'El pedido ha sido cancelado y los repuestos han sido reintegrados al inventario de bodega.'
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo cancelar el pedido. Verifica que no haya sido entregado previamente.'
            ]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida.']);
exit;
