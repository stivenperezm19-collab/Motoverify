<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/VentaModel.php';

$id_venta = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_venta <= 0) {
    header('Location: historial_compras.php');
    exit;
}

$ventaModel = new VentaModel($conn);
$id_cliente = (int)$_SESSION['user_id'];

// Seguridad estricta: solo se obtienen datos si el pedido pertenece al cliente autenticado
$pedido = $ventaModel->getPedidoDetalleCliente($id_venta, $id_cliente);

if (!$pedido) {
    die("
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
        <title>Acceso No Autorizado</title>
    </head>
    <body class='bg-light d-flex align-items-center justify-content-center' style='height: 100vh;'>
        <div class='text-center p-5 bg-white rounded-4 shadow-sm border' style='max-width: 500px;'>
            <h3 class='text-danger fw-bold'>Acceso Denegado</h3>
            <p class='text-muted'>Este pedido no existe o no pertenece a tu cuenta de cliente.</p>
            <a href='historial_compras.php' class='btn btn-primary rounded-pill px-4'>Volver a mis pedidos</a>
        </div>
    </body>
    </html>");
}

$detalles = $pedido['detalles'] ?? [];
$estado_pedido = $pedido['estado_pedido'] ?? 'Pendiente';
$estado_pago = $pedido['estado_pago'] ?? 'Pendiente';

// Pasos de estado para la barra de seguimiento (Proceso dentro del establecimiento)
$estados = ['Pendiente', 'En Preparación', 'Listo para Pago', 'Pagado', 'Entregado'];
$currentIndex = array_search($estado_pedido, $estados);
if ($currentIndex === false) {
    if ($estado_pedido === 'Confirmado' || $estado_pedido === 'En Camino') $currentIndex = 3;
    else $currentIndex = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido #<?= str_pad($pedido['id_venta'], 5, '0', STR_PAD_LEFT) ?> - Motoverify</title>
    
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

        .order-detail-card { background: #fff; border-radius: 14px; padding: 30px; border: 1px solid #f0f1f3; box-shadow: 0 4px 12px rgba(0,0,0,0.02); margin-bottom: 25px; }
        .item-thumb { width: 65px; height: 65px; object-fit: contain; background: #f8fafc; border-radius: 10px; padding: 5px; border: 1px solid #e2e8f0; }

        /* Stepper de Seguimiento */
        .order-stepper { display: flex; justify-content: space-between; position: relative; margin: 25px 0 10px; }
        .order-stepper::before { content: ''; position: absolute; top: 20px; left: 8%; right: 8%; height: 4px; background: #e2e8f0; z-index: 1; }
        .stepper-step { position: relative; z-index: 2; text-align: center; flex: 1; }
        .stepper-circle { width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: bold; border: 3px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); font-size: 0.9rem; }
        .stepper-step.active .stepper-circle { background: #004ba8; color: #fff; box-shadow: 0 0 0 4px rgba(0, 75, 168, 0.15); }
        .stepper-step.completed .stepper-circle { background: #16a34a; color: #fff; }
        .stepper-label { font-size: 0.78rem; font-weight: 600; color: #64748b; }
        .stepper-step.active .stepper-label { color: #004ba8; font-weight: 700; }

        /* Pulse animation for ready to pay */
        .pulse-glow {
            animation: pulseGlow 2s infinite;
        }
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4); }
            70% { box-shadow: 0 0 0 12px rgba(22, 163, 74, 0); }
            100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
        }
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
                <li class="breadcrumb-item"><a href="historial_compras.php" class="text-decoration-none">Mis Pedidos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pedido #<?= str_pad($pedido['id_venta'], 5, '0', STR_PAD_LEFT) ?></li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h2 class="fw-bold mb-1 text-dark">
                    Pedido #<?= str_pad($pedido['id_venta'], 5, '0', STR_PAD_LEFT) ?>
                </h2>
                <p class="text-muted mb-0">
                    Registrado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($pedido['fecha'])) ?>
                    <?php if (!empty($pedido['vendedor_nombre'])): ?>
                        &nbsp;•&nbsp; <span class="badge bg-light text-primary border"><i class="fa-solid fa-user-check me-1"></i>Vendedor: <?= htmlspecialchars($pedido['vendedor_nombre']) ?></span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="../../public/factura.php?id=<?= $pedido['id_venta'] ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="fa-solid fa-file-invoice me-1"></i> Ver Factura Digital
                </a>
                <a href="historial_compras.php" class="btn btn-light border rounded-pill px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <!-- NOTIFICACIONES SEGÚN EL ESTADO DEL PEDIDO -->
        <?php if ($estado_pedido === 'Listo para Pago'): ?>
            <!-- BANNER DE ACCIÓN: PRODUCTO PREPARADO, PROCEDER AL PAGO EN CAJA -->
            <div class="alert alert-success border-success-subtle rounded-4 p-4 shadow-sm mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-cash-register fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-success mb-1">¡Tus repuestos ya fueron alistados por el vendedor en bodega!</h5>
                        <p class="text-dark mb-0 small">Por favor acércate a la <strong>Caja Principal del Establecimiento</strong> con tu número de pedido <strong>#<?= str_pad($pedido['id_venta'], 5, '0', STR_PAD_LEFT) ?></strong> para pagar y retirar tus repuestos.</p>
                    </div>
                </div>
                <button type="button" class="btn btn-success btn-lg rounded-pill px-4 fw-bold shadow pulse-glow d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalPago">
                    <i class="fa-solid fa-cash-register"></i> Pagar en Caja ($<?= number_format($pedido['total'], 0, ',', '.') ?>)
                </button>
            </div>
        <?php elseif ($estado_pedido === 'En Preparación'): ?>
            <div class="alert alert-info border-info-subtle rounded-4 p-3 shadow-sm mb-4 d-flex align-items-center gap-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                    <i class="fa-solid fa-boxes-stacked fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">El vendedor está alistando tus repuestos en bodega</h6>
                    <p class="text-muted small mb-0">Nuestro equipo está verificando los repuestos en bodega. Te notificaremos en cuanto estén en caja listos para tu pago y entrega inmediata.</p>
                </div>
            </div>
        <?php elseif ($estado_pedido === 'Pendiente'): ?>
            <div class="alert alert-warning border-warning-subtle rounded-4 p-3 shadow-sm mb-4 d-flex align-items-center gap-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                    <i class="fa-solid fa-hourglass-start fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Pedido recibido (Esperando alistamiento del vendedor)</h6>
                    <p class="text-muted small mb-0">Tu solicitud ha sido enviada al personal de tienda. En breve un vendedor alistará tus productos para habilitar el cobro en caja.</p>
                </div>
            </div>
        <?php elseif ($estado_pedido === 'Pagado'): ?>
            <div class="alert alert-success border-success-subtle rounded-4 p-3 shadow-sm mb-4 d-flex align-items-center gap-3">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                    <i class="fa-solid fa-circle-check fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-success mb-1">¡Pago registrado y confirmado en caja!</h6>
                    <p class="text-muted small mb-0">El pago de tu pedido ha sido registrado. Puedes retirar tus repuestos en el mostrador del establecimiento.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- NOTA DEL VENDEDOR SI EXISTE -->
        <?php if (!empty($pedido['notas_vendedor'])): ?>
            <div class="card border-primary-subtle bg-light rounded-4 p-3 mb-4 shadow-sm">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary rounded-pill px-3 py-1">
                        <i class="fa-solid fa-user-check me-1"></i> Mensaje del Vendedor <?= htmlspecialchars($pedido['vendedor_nombre'] ?? '') ?>
                    </span>
                    <span class="text-muted small">Actualización sobre tu pedido:</span>
                </div>
                <div class="text-dark small fw-semibold ps-1">
                    <?= nl2br(htmlspecialchars($pedido['notas_vendedor'])) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 1. Estado de Seguimiento -->
        <div class="order-detail-card mb-4">
            <h5 class="fw-bold text-dark mb-1">Estado del Pedido</h5>
            <p class="text-muted small mb-3">Seguimiento paso a paso del ciclo de tu compra.</p>

            <?php if ($estado_pedido === 'Cancelado'): ?>
                <div class="alert alert-danger rounded-3 text-center my-3">
                    <i class="fa-solid fa-circle-xmark fs-4 me-2"></i> Este pedido fue cancelado.
                </div>
            <?php else: ?>
                <div class="order-stepper">
                    <div class="stepper-step <?= $currentIndex >= 0 ? ($currentIndex > 0 ? 'completed' : 'active') : '' ?>">
                        <div class="stepper-circle">
                            <i class="fa-solid <?= $currentIndex > 0 ? 'fa-check' : 'fa-receipt' ?>"></i>
                        </div>
                        <div class="stepper-label">1. Solicitado</div>
                    </div>
                    <div class="stepper-step <?= $currentIndex >= 1 ? ($currentIndex > 1 ? 'completed' : 'active') : '' ?>">
                        <div class="stepper-circle">
                            <i class="fa-solid <?= $currentIndex > 1 ? 'fa-check' : 'fa-boxes-stacked' ?>"></i>
                        </div>
                        <div class="stepper-label">2. En Bodega</div>
                    </div>
                    <div class="stepper-step <?= $currentIndex >= 2 ? ($currentIndex > 2 ? 'completed' : 'active') : '' ?>">
                        <div class="stepper-circle">
                            <i class="fa-solid <?= $currentIndex > 2 ? 'fa-check' : 'fa-cash-register' ?>"></i>
                        </div>
                        <div class="stepper-label">3. Listo en Caja</div>
                    </div>
                    <div class="stepper-step <?= $currentIndex >= 3 ? ($currentIndex > 3 ? 'completed' : 'active') : '' ?>">
                        <div class="stepper-circle">
                            <i class="fa-solid <?= $currentIndex > 3 ? 'fa-check' : 'fa-receipt' ?>"></i>
                        </div>
                        <div class="stepper-label">4. Pagado</div>
                    </div>
                    <div class="stepper-step <?= $currentIndex >= 4 ? 'completed active' : '' ?>">
                        <div class="stepper-circle">
                            <i class="fa-solid fa-handshake"></i>
                        </div>
                        <div class="stepper-label">5. Entregado en Mostrador</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <!-- 2. Productos Solicitados -->
            <div class="col-12 col-lg-8">
                <div class="order-detail-card">
                    <h5 class="fw-bold text-dark mb-3">Productos del Pedido (<?= count($detalles) ?>)</h5>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Repuesto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $d): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($d['imagen_url'])): ?>
                                                    <img src="../../<?= htmlspecialchars($d['imagen_url']) ?>" alt="<?= htmlspecialchars($d['nombre']) ?>" class="item-thumb">
                                                <?php else: ?>
                                                    <div class="item-thumb d-flex align-items-center justify-content-center text-muted">
                                                        <i class="fa-solid fa-motorcycle fs-4 opacity-25"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <span class="badge bg-light text-primary border mb-1" style="font-size: 0.72rem;"><?= htmlspecialchars($d['marca_nombre'] ?? 'Genérico') ?></span>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($d['nombre']) ?></div>
                                                    <span class="text-muted small">Cód: <?= htmlspecialchars($d['codigo_interno']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <?= $d['cantidad'] ?>
                                        </td>
                                        <td class="text-end text-muted">
                                            $<?= number_format($d['precio_unitario'], 0, ',', '.') ?>
                                        </td>
                                        <td class="text-end fw-bold text-primary">
                                            $<?= number_format($d['subtotal'], 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totales -->
                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <div style="min-width: 250px;">
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>Subtotal:</span>
                                <span class="fw-semibold text-dark">$<?= number_format($pedido['subtotal'], 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>IVA (19%):</span>
                                <span class="fw-semibold text-dark">$<?= number_format($pedido['impuestos'], 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>Entrega:</span>
                                <span class="text-primary fw-semibold"><i class="fa-solid fa-store me-1"></i> Retiro en Mostrador</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-bold text-dark fs-5">Total del Pedido:</span>
                                <span class="fw-bold text-primary fs-4">$<?= number_format($pedido['total'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Información de Entrega y Pago -->
            <div class="col-12 col-lg-4">
                <div class="order-detail-card mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-store text-primary me-2"></i>Retiro en Mostrador / Tienda</h5>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Cliente que retira:</span>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($pedido['cliente_nombre'] ?? '') ?></div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Teléfono de Contacto:</span>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($pedido['telefono_contacto'] ?? 'N/A') ?></div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Punto de Entrega:</span>
                        <div class="fw-semibold text-dark"><i class="fa-solid fa-location-dot text-primary me-1"></i> Mostrador Principal / Establecimiento</div>
                        <div class="text-muted small">Atención directa en tienda física (Sin Envíos)</div>
                    </div>

                    <?php if (!empty($pedido['notas'])): ?>
                        <div class="bg-light rounded-3 p-3 border">
                            <span class="text-muted small d-block fw-semibold mb-1">Notas enviadas:</span>
                            <div class="small text-secondary"><?= nl2br(htmlspecialchars($pedido['notas'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="order-detail-card">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-credit-card text-success me-2"></i>Estado del Pago</h5>

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted small">Estado actual:</span>
                        <?php if ($estado_pago === 'Pagado'): ?>
                            <span class="badge bg-success px-3 py-1 rounded-pill">
                                <i class="fa-solid fa-check-circle me-1"></i> Pagado
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill">
                                <i class="fa-solid fa-hourglass-half me-1"></i> Pendiente de Pago
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Método:</span>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($pedido['metodo_pago'] ?? 'Contra Entrega') ?></div>
                    </div>

                    <?php if (!empty($pedido['fecha_pago'])): ?>
                        <div class="mb-3">
                            <span class="text-muted small d-block">Fecha de Pago:</span>
                            <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($pedido['fecha_pago'])) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($estado_pedido === 'Listo para Pago'): ?>
                        <button type="button" class="btn btn-success w-100 rounded-pill py-2 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 mt-2" data-bs-toggle="modal" data-bs-target="#modalPago">
                            <i class="fa-solid fa-credit-card"></i> Pagar Ahora ($<?= number_format($pedido['total'], 0, ',', '.') ?>)
                        </button>
                    <?php elseif ($estado_pedido === 'Pendiente' || $estado_pedido === 'En Preparación'): ?>
                        <div class="bg-light p-2 rounded text-center small text-muted">
                            <i class="fa-solid fa-lock me-1"></i> El pago se habilitará cuando el vendedor prepare el producto.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

    <!-- ============================================================ -->
    <!-- MODAL DE PAGO PARA EL CLIENTE EN CAJA -->
    <!-- ============================================================ -->
    <div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="modalPagoLabel">
                        <i class="fa-solid fa-cash-register text-success me-2"></i> Pago en Caja del Establecimiento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formPagarPedido">
                    <div class="modal-body p-4">
                        <div class="bg-light rounded-3 p-3 border mb-3 text-center">
                            <span class="text-muted small d-block">Total a Cancelar en Caja:</span>
                            <span class="fw-bold text-primary fs-3">$<?= number_format($pedido['total'], 0, ',', '.') ?></span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle d-block mx-auto mt-1" style="width: fit-content;">
                                <i class="fa-solid fa-check me-1"></i> Repuestos alistados en bodega
                            </span>
                        </div>

                        <div class="alert alert-warning border-warning-subtle small mb-3">
                            <i class="fa-solid fa-store me-1"></i> <strong>Instrucciones:</strong> Acércate a la caja principal del establecimiento e indica el número de pedido <strong>#<?= str_pad($pedido['id_venta'], 5, '0', STR_PAD_LEFT) ?></strong>.
                        </div>

                        <label class="form-label fw-bold text-dark small mb-2">Forma de Pago en la Caja:</label>

                        <div class="d-flex flex-column gap-2 mb-3">
                            <label class="p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer" style="cursor: pointer;" id="lblCash">
                                <input type="radio" name="pago_metodo" value="Efectivo en Caja" checked class="form-check-input mt-0">
                                <div>
                                    <strong class="text-dark d-block"><i class="fa-solid fa-money-bill-wave text-success me-1"></i> Efectivo en Caja</strong>
                                    <span class="text-muted small">Entrega el dinero en efectivo directamente al cajero.</span>
                                </div>
                            </label>

                            <label class="p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer" style="cursor: pointer;" id="lblDatafono">
                                <input type="radio" name="pago_metodo" value="Tarjeta / Datáfono en Caja" class="form-check-input mt-0">
                                <div>
                                    <strong class="text-dark d-block"><i class="fa-solid fa-credit-card text-primary me-1"></i> Tarjeta / Datáfono en Caja</strong>
                                    <span class="text-muted small">Pago con tarjeta de débito o crédito en el datáfono del local.</span>
                                </div>
                            </label>

                            <label class="p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer" style="cursor: pointer;" id="lblTransfer">
                                <input type="radio" name="pago_metodo" value="Transferencia / QR en Caja" class="form-check-input mt-0">
                                <div>
                                    <strong class="text-dark d-block"><i class="fa-solid fa-qrcode text-secondary me-1"></i> Transferencia QR en Caja</strong>
                                    <span class="text-muted small">Escaneas el código QR Nequi / Bancolombia en el mostrador.</span>
                                </div>
                            </label>
                        </div>

                        <!-- Detalles de QR / comprobante si se elige -->
                        <div id="boxTransferInfo" style="display: none;" class="bg-light p-3 rounded-3 border mb-3 small">
                            <span class="fw-bold text-dark d-block mb-1">Cuentas para transferencia en caja:</span>
                            <div class="text-muted mb-1">• <strong>Nequi:</strong> 310 123 4567 (Motoverify S.A.S)</div>
                            <div class="text-muted mb-2">• <strong>Bancolombia Ahorros:</strong> 123-456789-01</div>
                            <label class="form-label fw-semibold text-dark small mb-1">Número de comprobante / Referencia (Opcional):</label>
                            <input type="text" id="comprobanteInput" class="form-control form-control-sm" placeholder="Ej: Transacción M-98234">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold" id="btnConfirmarPago">
                            <i class="fa-solid fa-check-circle me-1"></i> Confirmar Pago en Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Toggle transfer info box
    document.querySelectorAll('input[name="pago_metodo"]').forEach(r => {
        r.addEventListener('change', function() {
            const isTransfer = this.value.includes('Transferencia');
            document.getElementById('boxTransferInfo').style.display = isTransfer ? 'block' : 'none';
        });
    });

    // Envío del pago
    document.getElementById('formPagarPedido').addEventListener('submit', function(e) {
        e.preventDefault();

        const btn = document.getElementById('btnConfirmarPago');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Registrando pago...';

        const metodo = document.querySelector('input[name="pago_metodo"]:checked').value;
        const comp = document.getElementById('comprobanteInput').value;

        fetch('../../controllers/cliente_pedido_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'pagar_pedido',
                id_venta: <?= $id_venta ?>,
                metodo_pago: metodo,
                comprobante: comp
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Pago Exitoso!',
                    text: data.message,
                    confirmButtonColor: '#004ba8'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Confirmar y Realizar Pago';
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Confirmar y Realizar Pago';
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión con el servidor.' });
        });
    });
    </script>
</body>
</html>
