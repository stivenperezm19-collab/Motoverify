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
                <li class="breadcrumb-item active" aria-current="page">Confirmación del Pedido</li>
            </ol>
        </nav>

        <h2 class="fw-bold text-dark mb-4">Finalizar Compra</h2>

        <form id="checkoutForm">
            <div class="row g-4">
                <!-- Columna Formulario de Envío y Pago -->
                <div class="col-12 col-lg-7">
                    
                    <!-- 1. Datos de Entrega -->
                    <div class="checkout-card">
                        <h4 class="checkout-title">
                            <span class="badge bg-primary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">1</span>
                            Datos de Entrega y Envío
                        </h4>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Nombre del Destinatario *</label>
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
                                <label class="form-label small fw-semibold text-muted">Ciudad / Municipio *</label>
                                <input type="text" name="ciudad_envio" class="form-control form-control-custom" placeholder="Ej: Neiva, Huila" value="<?= htmlspecialchars($cliente['ciudad'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Dirección Completa de Entrega *</label>
                                <input type="text" name="direccion_envio" class="form-control form-control-custom" placeholder="Calle / Carrera / Número / Barrio o Punto de Referencia" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Instrucciones o Notas para la Entrega (Opcional)</label>
                                <textarea name="notas" class="form-control form-control-custom" rows="2" placeholder="Ej: Dejar en portería, timbre 201, entregar en horas de la tarde..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Método de Pago -->
                    <div class="checkout-card">
                        <h4 class="checkout-title">
                            <span class="badge bg-primary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">2</span>
                            Método de Pago
                        </h4>

                        <p class="text-muted small mb-3">Selecciona cómo deseas cancelar tu pedido. De acuerdo a la infraestructura actual, no se solicitan datos bancarios directos.</p>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="payment-option-card selected" id="optCash">
                                    <input type="radio" name="metodo_pago" value="Contra Entrega" checked class="form-check-input mt-0 me-2">
                                    <div>
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Pago Contra Entrega (Efectivo al recibir)</div>
                                        <span class="text-muted small">Pagas en efectivo al transportador cuando el repuesto llegue a tu dirección.</span>
                                    </div>
                                </label>
                            </div>
                            <div class="col-12">
                                <label class="payment-option-card" id="optTransfer">
                                    <input type="radio" name="metodo_pago" value="Transferencia Contraentrega" class="form-check-input mt-0 me-2">
                                    <div>
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-mobile-screen-button text-primary me-2"></i>Transferencia Digital al recibir (Nequi / Bancolombia)</div>
                                        <span class="text-muted small">Puedes transferir por Nequi o Bancolombia en el momento exacto en que te entreguen el paquete.</span>
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
                                <span>Envío:</span>
                                <span class="text-success fw-semibold">Gratis</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-bold text-dark fs-5">Total a Pagar:</span>
                                <span class="fw-bold text-primary fs-4">$<?= number_format($total, 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <!-- Botón de Confirmación -->
                        <button type="submit" id="btnConfirmOrder" class="btn btn-success btn-lg w-100 rounded-pill py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm mb-3">
                            <i class="fa-solid fa-check-circle"></i> Confirmar y Realizar Pedido
                        </button>

                        <div class="text-center text-muted small">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Transacción segura y protegida en Motoverify
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
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando pedido...';

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
                    title: '¡Pedido Registrado!',
                    text: resData.message,
                    confirmButtonColor: '#004ba8',
                    confirmButtonText: 'Ver Detalle del Pedido',
                    allowOutsideClick: false
                }).then(() => {
                    const targetUrl = resData.id_venta ? ('detalle_compra.php?id=' + resData.id_venta) : (resData.redirect || 'historial_compras.php');
                    window.location.href = targetUrl;
                });
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Confirmar y Realizar Pedido';
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo procesar',
                    text: resData.message
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Confirmar y Realizar Pedido';
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión con el servidor.' });
        });
    });
    </script>
</body>
</html>
