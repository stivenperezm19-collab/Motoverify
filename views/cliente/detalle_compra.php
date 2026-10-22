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
    // Si la venta no existe o no pertenece al cliente
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

// Pasos de estado para la barra de seguimiento
$estados = ['Pendiente', 'Confirmado', 'En Camino', 'Entregado'];
$currentIndex = array_search($estado_pedido, $estados);
if ($currentIndex === false) $currentIndex = 0;
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
        .order-stepper::before { content: ''; position: absolute; top: 20px; left: 10%; right: 10%; height: 4px; background: #e2e8f0; z-index: 1; }
        .stepper-step { position: relative; z-index: 2; text-align: center; flex: 1; }
        .stepper-circle { width: 42px; height: 42px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: bold; border: 3px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .stepper-step.active .stepper-circle { background: #004ba8; color: #fff; }
        .stepper-step.completed .stepper-circle { background: #16a34a; color: #fff; }
        .stepper-label { font-size: 0.82rem; font-weight: 600; color: #64748b; }
        .stepper-step.active .stepper-label { color: #004ba8; font-weight: 700; }
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

        <!-- 1. Estado de Seguimiento -->
        <div class="order-detail-card mb-4">
            <h5 class="fw-bold text-dark mb-1">Estado del Pedido</h5>
            <p class="text-muted small mb-3">Seguimiento en tiempo real de la entrega de tus repuestos.</p>

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
                        <div class="stepper-label">Pendiente</div>
                    </div>
                    <div class="stepper-step <?= $currentIndex >= 1 ? ($currentIndex > 1 ? 'completed' : 'active') : '' ?>">
                        <div class="stepper-circle">
                            <i class="fa-solid <?= $currentIndex > 1 ? 'fa-check' : 'fa-clipboard-check' ?>"></i>
                        </div>
                        <div class="stepper-label">Confirmado</div>
                    </div>
                    <div class="stepper-step <?= $currentIndex >= 2 ? ($currentIndex > 2 ? 'completed' : 'active') : '' ?>">
                        <div class="stepper-circle">
                            <i class="fa-solid <?= $currentIndex > 2 ? 'fa-check' : 'fa-motorcycle' ?>"></i>
                        </div>
                        <div class="stepper-label">En Camino</div>
                    </div>
                    <div class="stepper-step <?= $currentIndex >= 3 ? 'completed active' : '' ?>">
                        <div class="stepper-circle">
                            <i class="fa-solid fa-box-check"></i>
                        </div>
                        <div class="stepper-label">Entregado</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <!-- 2. Productos Adquiridos -->
            <div class="col-12 col-lg-8">
                <div class="order-detail-card">
                    <h5 class="fw-bold text-dark mb-3">Productos Adquiridos (<?= count($detalles) ?>)</h5>

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
                                <span>Envío:</span>
                                <span class="text-success fw-semibold">Gratis</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-bold text-dark fs-5">Total Pagado:</span>
                                <span class="fw-bold text-primary fs-4">$<?= number_format($pedido['total'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Información de Entrega y Pago -->
            <div class="col-12 col-lg-4">
                <div class="order-detail-card mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-truck-ramp-box text-primary me-2"></i>Información de Entrega</h5>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Destinatario:</span>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($pedido['cliente_nombre'] ?? '') ?></div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Teléfono de Contacto:</span>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($pedido['telefono_contacto'] ?? 'N/A') ?></div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Dirección de Envío:</span>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($pedido['direccion_envio'] ?? 'N/A') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($pedido['ciudad_envio'] ?? '') ?></div>
                    </div>

                    <?php if (!empty($pedido['notas'])): ?>
                        <div class="bg-light rounded-3 p-3 border">
                            <span class="text-muted small d-block fw-semibold mb-1">Notas del cliente:</span>
                            <div class="small text-secondary"><?= nl2br(htmlspecialchars($pedido['notas'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="order-detail-card">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-credit-card text-success me-2"></i>Método de Pago</h5>

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-circle-check text-success"></i>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($pedido['metodo_pago'] ?? 'Contra Entrega') ?></span>
                    </div>
                    <p class="text-muted small mb-0">Cancelas en el momento de la recepción física del repuesto.</p>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
