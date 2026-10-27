<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/ClienteModel.php';

$clienteModel = new ClienteModel($conn);
$id_cliente = (int)$_SESSION['user_id'];
$cliente = $clienteModel->getById($id_cliente);

// Verificar carrito
$carritoSession = $_SESSION['carrito'] ?? [];
if (empty($carritoSession)) {
    header('Location: carrito.php');
    exit;
}

// Calcular items y totales frescos desde la base de datos
$items = [];
$subtotal = 0.00;
$total_articulos = 0;

foreach ($carritoSession as $id_rep => $itemData) {
    $id = (int)$id_rep;
    $cant = max(1, (int)($itemData['cantidad'] ?? 1));

    $query = "SELECT r.id_repuesto, r.nombre, r.precio, r.stock, r.codigo_interno, m.nombre as marca_nombre, i.ruta_archivo as imagen_url 
              FROM REPUESTO r
              LEFT JOIN MARCA m ON r.id_marca = m.id_marca
              LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
              LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
              WHERE r.id_repuesto = $id AND (r.estado = 'Activo' OR r.estado = 'ACTIVO')";
    $res = $conn->query($query);
    if ($res && $res->num_rows === 1) {
        $prod = $res->fetch_assoc();
        $precio = (float)$prod['precio'];
        $stock = (int)$prod['stock'];

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
            'cantidad' => $cant,
            'subtotal_item' => $subtotal_item,
            'codigo_interno' => $prod['codigo_interno'],
            'marca' => $prod['marca_nombre'] ?? 'Genérico',
            'imagen_url' => $prod['imagen_url'] ?? ''
        ];
    }
}

if (empty($items)) {
    header('Location: carrito.php');
    exit;
}

$impuestos = $subtotal * 0.19;
$total = $subtotal + $impuestos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - Motoverify</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }

        .topbar-global {
            height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050;
        }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 85px; width: auto; object-fit: contain; }
        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }

        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.02rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.15rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }

        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); padding: 30px; background-color: #f8f9fc; }

        .checkout-card { background: #fff; border-radius: 14px; padding: 30px; border: 1px solid #f0f1f3; box-shadow: 0 4px 12px rgba(0,0,0,0.02); margin-bottom: 25px; }
        .checkout-title { font-size: 1.15rem; font-weight: 700; color: #1f2937; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .form-control-custom { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; font-size: 0.95rem; }
        .form-control-custom:focus { border-color: #004ba8; box-shadow: 0 0 0 3px rgba(0, 75, 168, 0.1); }

        .payment-option-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .payment-option-card:hover { border-color: #004ba8; background-color: #f8fafc; }
        .payment-option-card.selected { border-color: #004ba8; background-color: #eff6ff; }

        .summary-sticky { position: sticky; top: 140px; }
        .checkout-thumb { width: 50px; height: 50px; object-fit: contain; background: #f8fafc; border-radius: 8px; padding: 4px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

    <?php require_once 'topbar.php'; ?>
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Inicio</a></li>
                <li class="breadcrumb-item"><a href="carrito.php" class="text-decoration-none">Carrito</a></li>
                <li class="breadcrumb-item active" aria-current="page">Solicitar Pedido</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Solicitud de Pedido en Establecimiento</h2>
                <p class="text-muted mb-0">Envía tu pedido para que el vendedor aliste tus repuestos en bodega y puedas pagarlos y retirarlos en la caja.</p>
            </div>
        </div>

        <!-- Banner Explicativo del Flujo en Tienda -->
        <div class="alert alert-primary border-primary-subtle rounded-4 p-3 mb-4 shadow-sm" style="background: linear-gradient(135deg, #f0f7ff 0%, #e0effe 100%);">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-store fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-primary mb-1">¿Cómo funciona tu pedido dentro del establecimiento?</h6>
                    <div class="text-dark small">
                        <strong>1. Solicitas el pedido:</strong> Confirmas los repuestos que necesitas. &nbsp;|&nbsp; 
                        <strong>2. Preparación en bodega:</strong> El vendedor busca y alista tus productos. &nbsp;|&nbsp; 
                        <strong>3. Pago y entrega en caja:</strong> Te acercas a la caja con tu código de pedido, pagas y recibes tus repuestos en mano.
                    </div>
                </div>
            </div>
        </div>

        <form id="checkoutForm">
            <input type="hidden" name="ciudad_envio" value="En Establecimiento">
            <input type="hidden" name="direccion_envio" value="Retiro en Mostrador / Tienda Física">

            <div class="row g-4">
                <!-- Columna Formulario de Retiro y Pago en Caja -->
                <div class="col-12 col-lg-7">
                    
                    <!-- 1. Datos del Cliente y Retiro -->
                    <div class="checkout-card">
                        <h4 class="checkout-title">
                            <span class="badge bg-primary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">1</span>
                            Datos del Cliente y Retiro en Mostrador
                        </h4>

                        <div class="alert alert-light border rounded-3 d-flex align-items-center gap-3 mb-3 py-2 px-3">
                            <i class="fa-solid fa-location-dot text-primary fs-4"></i>
                            <div>
                                <strong class="text-dark small d-block">Punto de Entrega: Mostrador Principal / Establecimiento</strong>
                                <span class="text-muted" style="font-size: 0.8rem;">Todo el proceso se realiza presencialmente en la tienda. No se efectúan envíos por encomienda ni transporte externo.</span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Nombre del Cliente *</label>
                                <input type="text" name="nombre_receptor" class="form-control form-control-custom" value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Documento / Cédula *</label>
                                <input type="text" name="documento" class="form-control form-control-custom" placeholder="Ej: 1083456789" value="<?= htmlspecialchars($cliente['documento'] ?? '') ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Teléfono de Contacto *</label>
                                <input type="text" name="telefono_contacto" class="form-control form-control-custom" placeholder="Ej: 3101234567" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Lugar de Retiro</label>
                                <input type="text" class="form-control form-control-custom bg-light text-muted" value="Mostrador de Tienda Física" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Observaciones o Instrucciones para el Vendedor (Opcional)</label>
                                <textarea name="notas" class="form-control form-control-custom" rows="2" placeholder="Ej: Confirmar compatibilidad con modelo 2022, alistar en caja 2..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Forma de Pago en Caja -->
                    <div class="checkout-card">
                        <h4 class="checkout-title">
                            <span class="badge bg-primary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">2</span>
                            Forma de Pago en la Caja del Establecimiento
                        </h4>

                        <p class="text-muted small mb-3">Indica cómo prefieres pagar al momento de acercarte a la caja:</p>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="payment-option-card selected" id="optCash">
                                    <input type="radio" name="metodo_pago" value="Efectivo en Caja" checked class="form-check-input mt-0 me-2">
                                    <div>
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Pago en Efectivo en Caja</div>
                                        <span class="text-muted small">Pagas en efectivo en la caja principal de la tienda al momento de retirar tus repuestos.</span>
                                    </div>
                                </label>
                            </div>
                            <div class="col-12">
                                <label class="payment-option-card" id="optDatafono">
                                    <input type="radio" name="metodo_pago" value="Tarjeta / Datáfono en Caja" class="form-check-input mt-0 me-2">
                                    <div>
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-credit-card text-primary me-2"></i>Tarjeta Débito o Crédito (Datáfono en Caja)</div>
                                        <span class="text-muted small">Pagas con tu tarjeta en el datáfono físico de la caja del establecimiento.</span>
                                    </div>
                                </label>
                            </div>
                            <div class="col-12">
                                <label class="payment-option-card" id="optTransfer">
                                    <input type="radio" name="metodo_pago" value="Transferencia / QR en Caja" class="form-check-input mt-0 me-2">
                                    <div>
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-qrcode text-secondary me-2"></i>Transferencia o QR en Caja (Nequi / Bancolombia)</div>
                                        <span class="text-muted small">Escaneas el código QR oficial de la tienda en el punto de pago.</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Columna Resumen del Pedido (Sticky) -->
                <div class="col-12 col-lg-5">
                    <div class="checkout-card summary-sticky">
                        <h4 class="checkout-title mb-3">Resumen de Tu Pedido</h4>

                        <!-- Lista de productos -->
                        <div class="mb-3" style="max-height: 240px; overflow-y: auto;">
                            <?php foreach ($items as $it): ?>
                                <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                                    <?php if (!empty($it['imagen_url'])): ?>
                                        <img src="../../<?= htmlspecialchars($it['imagen_url']) ?>" alt="<?= htmlspecialchars($it['nombre']) ?>" class="checkout-thumb">
                                    <?php else: ?>
                                        <div class="checkout-thumb d-flex align-items-center justify-content-center text-muted">
                                            <i class="fa-solid fa-motorcycle fs-5 opacity-25"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 text-truncate">
                                        <div class="fw-semibold text-dark small text-truncate"><?= htmlspecialchars($it['nombre']) ?></div>
                                        <span class="text-muted" style="font-size: 0.8rem;"><?= $it['cantidad'] ?> unid. × $<?= number_format($it['precio'], 0, ',', '.') ?></span>
                                    </div>
                                    <div class="fw-bold text-dark small">
                                        $<?= number_format($it['subtotal_item'], 0, ',', '.') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Desglose de costos -->
                        <div class="bg-light rounded-3 p-3 mb-3 border">
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>Subtotal:</span>
                                <span class="fw-semibold text-dark">$<?= number_format($subtotal, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>IVA (19%):</span>
                                <span class="fw-semibold text-dark">$<?= number_format($impuestos, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>Entrega:</span>
                                <span class="text-primary fw-semibold"><i class="fa-solid fa-store me-1"></i> Retiro en Mostrador</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-bold text-dark fs-5">Total a Pagar en Caja:</span>
                                <span class="fw-bold text-primary fs-4">$<?= number_format($total, 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <!-- Botón de Confirmación -->
                        <button type="submit" id="btnConfirmOrder" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm mb-3" style="background: #004ba8; border: none;">
                            <i class="fa-solid fa-paper-plane"></i> Confirmar Pedido y Alistar en Bodega
                        </button>

                        <div class="text-center text-muted small">
                            <i class="fa-solid fa-cash-register text-success me-1"></i> Pago y retiro presencial directo en el establecimiento
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Radio button styling
    document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-option-card').forEach(c => c.classList.remove('selected'));
            this.closest('.payment-option-card').classList.add('selected');
        });
    });

    // Envío del formulario de compra
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const btn = document.getElementById('btnConfirmOrder');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando pedido al vendedor...';

        const formData = new FormData(this);
        const data = {
            action: 'realizar_pedido',
            direccion_envio: formData.get('direccion_envio'),
            ciudad_envio: formData.get('ciudad_envio'),
            telefono_contacto: formData.get('telefono_contacto'),
            metodo_pago: formData.get('metodo_pago'),
            notas: formData.get('notas')
        };

        fetch('../../controllers/cliente_pedido_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Pedido Enviado al Vendedor!',
                    text: resData.message,
                    confirmButtonColor: '#004ba8',
                    confirmButtonText: 'Ver Estado del Pedido',
                    allowOutsideClick: false
                }).then(() => {
                    const targetUrl = resData.id_venta ? ('detalle_compra.php?id=' + resData.id_venta) : (resData.redirect || 'historial_compras.php');
                    window.location.href = targetUrl;
                });
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Confirmar y Enviar Pedido al Vendedor';
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo procesar',
                    text: resData.message
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Confirmar y Enviar Pedido al Vendedor';
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión con el servidor.' });
        });
    });
    </script>
</body>
</html>
