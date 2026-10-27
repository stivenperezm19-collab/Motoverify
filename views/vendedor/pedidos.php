<?php
session_start();
// Validación estricta de Vendedor (o Administrador)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['Vendedor', 'Administrador'])) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/VentaModel.php';

$ventaModel = new VentaModel($conn);
$id_vendedor_actual = (int)$_SESSION['user_id'];
$nombre_vendedor_actual = ucfirst(explode('@', $_SESSION['email'] ?? 'Vendedor')[0]);

// Métricas de conteo
$conteo = $ventaModel->contarPedidosPendientesVendedor();

// Filtros GET
$filtro_estado = $_GET['estado'] ?? 'Todos';
$filtro_search = $_GET['search'] ?? '';

$pedidos = $ventaModel->getPedidosClientes([
    'estado_pedido' => $filtro_estado,
    'search' => $filtro_search
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión y Preparación de Pedidos - Motoverify</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }

        .topbar-global { height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 90px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }

        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.02rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.15rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }

        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); padding: 35px 30px; background-color: #f8f9fc; }

        /* Hero Banner */
        .hero-banner { background: linear-gradient(135deg, #0640aa 0%, #1e5abf 100%); color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 40px; top: 25%; height: 50%; width: 4px; background-color: #60a5fa; border-radius: 4px; }
        .hero-gears { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); pointer-events: none; }

        /* KPI Cards */
        .kpi-card { background: #fff; border-radius: 14px; padding: 22px; border: 1px solid #f0f1f3; box-shadow: 0 3px 10px rgba(0,0,0,0.02); transition: transform 0.2s, box-shadow 0.2s; }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.05); }
        .kpi-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }

        /* Content Card */
        .content-card { background: #fff; border-radius: 14px; padding: 25px; border: 1px solid #f0f1f3; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }

        /* Filter Tabs */
        .filter-tab { border: none; background: transparent; padding: 8px 16px; font-weight: 600; color: #64748b; border-radius: 20px; transition: all 0.2s; font-size: 0.92rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .filter-tab:hover { background-color: #f1f5f9; color: #1e293b; }
        .filter-tab.active { background-color: #004ba8; color: #fff; }

        /* Table Thumb */
        .table-thumb { width: 48px; height: 48px; object-fit: contain; background: #f8fafc; border-radius: 8px; padding: 3px; border: 1px solid #e2e8f0; }

        /* Stepper en Modal */
        .modal-stepper { display: flex; justify-content: space-between; position: relative; margin: 20px 0 25px; }
        .modal-stepper::before { content: ''; position: absolute; top: 16px; left: 8%; right: 8%; height: 3px; background: #e2e8f0; z-index: 1; }
        .m-step { position: relative; z-index: 2; text-align: center; flex: 1; }
        .m-step-circle { width: 34px; height: 34px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; font-weight: bold; border: 2px solid #fff; font-size: 0.85rem; }
        .m-step.active .m-step-circle { background: #004ba8; color: #fff; box-shadow: 0 0 0 3px rgba(0, 75, 168, 0.2); }
        .m-step.completed .m-step-circle { background: #16a34a; color: #fff; }
        .m-step-label { font-size: 0.75rem; font-weight: 600; color: #64748b; }
        .m-step.active .m-step-label { color: #004ba8; font-weight: 700; }

        .pulse-badge { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
    </style>
</head>
<body>

    <!-- Topbar Global -->
    <header class="topbar-global px-4 d-flex align-items-center justify-content-between">
        <a href="dashboard.php" class="topbar-logo text-decoration-none">
            <img src="../../public/assets/images/logo_motoverify.png" alt="Motoverify">
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="perfil.php" class="d-flex align-items-center me-3 bg-light rounded-pill px-3 py-1 border border-secondary-subtle text-decoration-none" style="transition: all 0.2s; cursor: pointer;">
                <i class="fa-solid fa-circle-user text-primary fs-4 me-2"></i>
                <span class="text-dark fw-bold" style="font-size: 0.95rem;"><?= $nombre_vendedor_actual ?></span>
            </a>
            <a href="../../controllers/logout.php" class="btn btn-outline-danger btn-sm fw-bold"><i class="fa fa-sign-out me-2"></i>Salir</a>
        </div>
    </header>

    <!-- Sidebar -->
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Hero Banner -->
        <div class="hero-banner rounded-3 p-4 ps-5 mb-4 d-flex align-items-center justify-content-between">
            <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                <h2 class="fw-bold mb-2 d-flex align-items-center gap-3">
                    <i class="fa-solid fa-boxes-packing" style="font-size: 1.4rem; opacity: .9;"></i>
                    Gestión y Preparación de Pedidos
                </h2>
                <p class="mb-0 opacity-75" style="font-size: 1rem;">Revisa las órdenes enviadas por los clientes, verifica el stock en bodega y prepara los repuestos para habilitar el pago.</p>
            </div>
            <div class="hero-gears">
                <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
            </div>
        </div>

        <!-- 1. Tarjetas de Métricas (KPIs) -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">Pendientes de Revisión</span>
                        <h3 class="fw-bold mb-0 text-warning"><?= $conteo['pendientes'] ?></h3>
                        <span class="text-muted" style="font-size: 0.78rem;">Requieren atención</span>
                    </div>
                    <div class="kpi-icon bg-warning-subtle text-warning">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">En Preparación</span>
                        <h3 class="fw-bold mb-0 text-info"><?= $conteo['en_preparacion'] ?></h3>
                        <span class="text-muted" style="font-size: 0.78rem;">Alistando repuestos</span>
                    </div>
                    <div class="kpi-icon bg-info-subtle text-info">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">Listos para Pago</span>
                        <h3 class="fw-bold mb-0 text-primary"><?= $conteo['listos_pago'] ?></h3>
                        <span class="text-muted" style="font-size: 0.78rem;">Esperando pago cliente</span>
                    </div>
                    <div class="kpi-icon bg-primary-subtle text-primary">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">Pagados / Para Retiro</span>
                        <h3 class="fw-bold mb-0 text-success"><?= $conteo['pagados'] ?></h3>
                        <span class="text-muted" style="font-size: 0.78rem;">Listos para entrega en mostrador</span>
                    </div>
                    <div class="kpi-icon bg-success-subtle text-success">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Filtros y Búsqueda -->
        <div class="content-card mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <!-- Pestañas de estado -->
                <div class="d-flex flex-wrap gap-1">
                    <a href="pedidos.php?estado=Todos" class="filter-tab <?= $filtro_estado === 'Todos' ? 'active' : '' ?>">
                        Todos <span class="badge bg-light text-dark ms-1"><?= count($pedidos) ?></span>
                    </a>
                    <a href="pedidos.php?estado=Pendiente" class="filter-tab <?= $filtro_estado === 'Pendiente' ? 'active' : '' ?>">
                        <i class="fa-solid fa-circle text-warning fs-6"></i> Pendientes
                    </a>
                    <a href="pedidos.php?estado=En Preparación" class="filter-tab <?= $filtro_estado === 'En Preparación' ? 'active' : '' ?>">
                        <i class="fa-solid fa-circle text-info fs-6"></i> En Preparación
                    </a>
                    <a href="pedidos.php?estado=Listo para Pago" class="filter-tab <?= $filtro_estado === 'Listo para Pago' ? 'active' : '' ?>">
                        <i class="fa-solid fa-circle text-primary fs-6"></i> Listos para Pago
                    </a>
                    <a href="pedidos.php?estado=Pagado" class="filter-tab <?= $filtro_estado === 'Pagado' ? 'active' : '' ?>">
                        <i class="fa-solid fa-circle text-success fs-6"></i> Pagados
                    </a>
                    <a href="pedidos.php?estado=Entregado" class="filter-tab <?= $filtro_estado === 'Entregado' ? 'active' : '' ?>">
                        <i class="fa-solid fa-circle text-dark fs-6"></i> Entregados
                    </a>
                    <a href="pedidos.php?estado=Cancelado" class="filter-tab <?= $filtro_estado === 'Cancelado' ? 'active' : '' ?>">
                        <i class="fa-solid fa-circle text-danger fs-6"></i> Cancelados
                    </a>
                </div>

                <!-- Buscador rápido -->
                <form method="GET" action="pedidos.php" class="d-flex gap-2">
                    <input type="hidden" name="estado" value="<?= htmlspecialchars($filtro_estado) ?>">
                    <div class="input-group" style="width: 280px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Buscar pedido, cliente, doc..." value="<?= htmlspecialchars($filtro_search) ?>">
                    </div>
                    <?php if (!empty($filtro_search)): ?>
                        <a href="pedidos.php?estado=<?= urlencode($filtro_estado) ?>" class="btn btn-outline-secondary" title="Limpiar"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- 3. Tabla de Pedidos -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Pedido</th>
                            <th>Cliente</th>
                            <th>Modalidad / Retiro</th>
                            <th class="text-center">Artículos</th>
                            <th>Estado del Pedido</th>
                            <th>Estado Pago</th>
                            <th>Total</th>
                            <th>Vendedor</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-box-open fs-1 mb-2 opacity-50"></i>
                                    <p class="mb-0 fw-semibold">No se encontraron pedidos con los filtros aplicados.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $p): 
                                // Clases de badge para estado de pedido
                                $badgePedido = 'bg-secondary text-white';
                                if ($p['estado_pedido'] === 'Pendiente') $badgePedido = 'bg-warning text-dark pulse-badge';
                                elseif ($p['estado_pedido'] === 'En Preparación') $badgePedido = 'bg-info text-dark fw-bold';
                                elseif ($p['estado_pedido'] === 'Listo para Pago') $badgePedido = 'bg-primary text-white fw-bold';
                                elseif ($p['estado_pedido'] === 'Pagado') $badgePedido = 'bg-success text-white fw-bold';
                                elseif ($p['estado_pedido'] === 'En Camino') $badgePedido = 'bg-dark text-white';
                                elseif ($p['estado_pedido'] === 'Entregado') $badgePedido = 'bg-success-subtle text-success border border-success-subtle';
                                elseif ($p['estado_pedido'] === 'Cancelado') $badgePedido = 'bg-danger text-white';

                                // Badge de pago
                                $badgePago = ($p['estado_pago'] === 'Pagado') ? 'bg-success text-white' : 'bg-secondary-subtle text-secondary';
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary">#<?= str_pad($p['id_venta'], 5, '0', STR_PAD_LEFT) ?></div>
                                        <span class="text-muted" style="font-size: 0.78rem;">
                                            <?= date('d/m/Y H:i', strtotime($p['fecha'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['cliente_nombre'] ?? 'Cliente') ?></div>
                                        <div class="text-muted small">
                                            <i class="fa-solid fa-phone me-1 text-secondary" style="font-size: 0.75rem;"></i><?= htmlspecialchars($p['telefono_contacto'] ?? $p['cliente_telefono'] ?? 'Sin tel.') ?>
                                        </div>
                                    </td>
                                    <td class="small" style="max-width: 220px;">
                                        <div class="fw-semibold text-dark">
                                            <i class="fa-solid fa-store text-primary me-1"></i>Retiro en Mostrador
                                        </div>
                                        <span class="text-muted">En Establecimiento</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            <?= $p['total_items'] ?> unid.
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgePedido ?> px-3 py-1 rounded-pill">
                                            <?= htmlspecialchars($p['estado_pedido'] ?? 'Pendiente') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgePago ?> px-2 py-1 rounded-pill" style="font-size: 0.75rem;">
                                            <i class="fa-solid <?= $p['estado_pago'] === 'Pagado' ? 'fa-check-circle' : 'fa-hourglass' ?> me-1"></i>
                                            <?= htmlspecialchars($p['estado_pago'] ?? 'Pendiente') ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark">
                                        $<?= number_format($p['total'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['vendedor_usuario'])): ?>
                                            <span class="badge bg-light text-secondary border">
                                                <i class="fa-solid fa-user-check me-1 text-primary"></i><?= htmlspecialchars($p['vendedor_usuario']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold shadow-sm" onclick="abrirModalRevision(<?= $p['id_venta'] ?>)">
                                            <i class="fa-solid fa-box-open me-1"></i> Revisar / Preparar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- ============================================================ -->
    <!-- MODAL DE REVISIÓN Y PREPARACIÓN DE PEDIDO -->
    <!-- ============================================================ -->
    <div class="modal fade" id="modalRevisar" tabindex="-1" aria-labelledby="modalRevisarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom">
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="modalRevisarLabel">
                            <i class="fa-solid fa-boxes-packing text-primary me-2"></i>
                            Revisión y Preparación de Pedido <span id="mIdPedido" class="text-primary"></span>
                        </h5>
                        <p class="text-muted small mb-0" id="mFechaPedido"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Stepper de estado actual -->
                    <div class="modal-stepper" id="modalStepper">
                        <!-- Generado dinámicamente -->
                    </div>

                    <!-- Datos del Cliente y Envío -->
                    <div class="card border bg-light rounded-3 p-3 mb-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <span class="text-muted small d-block">Cliente:</span>
                                <strong id="mClienteNombre" class="text-dark"></strong>
                                <div class="text-muted small" id="mClienteDoc"></div>
                            </div>
                            <div class="col-12 col-md-4">
                                <span class="text-muted small d-block">Contacto:</span>
                                <strong id="mClienteTel" class="text-dark"></strong>
                                <div class="text-muted small text-truncate" id="mClienteEmail"></div>
                            </div>
                            <div class="col-12 col-md-4">
                                <span class="text-muted small d-block">Destino de Entrega:</span>
                                <strong id="mClienteDir" class="text-dark"></strong>
                                <div class="text-muted small" id="mClienteCiudad"></div>
                            </div>
                            <div class="col-12" id="mContNotas" style="display: none;">
                                <div class="bg-white p-2 rounded border small">
                                    <span class="text-muted fw-bold d-block">Instrucciones del Cliente:</span>
                                    <span id="mClienteNotas" class="text-secondary"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Repuestos Solicitados y Verificación de Stock -->
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                        <span><i class="fa-solid fa-puzzle-piece text-primary me-2"></i>Repuestos a Preparar</span>
                        <span class="badge bg-light text-muted border small" id="mCountArticulos">0 artículos</span>
                    </h6>
                    <div class="table-responsive border rounded-3 mb-4">
                        <table class="table align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Repuesto</th>
                                    <th class="text-center">Cant. Pedida</th>
                                    <th class="text-center">Stock Bodega</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="mDetallesTbody">
                                <!-- Filas de productos inyectadas por JS -->
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total del Pedido:</td>
                                    <td class="text-end fw-bold text-primary fs-5" id="mTotalPedido">$0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Notas de Preparación del Vendedor -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Notas de Preparación / Alistamiento (Visibles para el cliente):</label>
                        <textarea class="form-control" id="mNotasVendedor" rows="2" placeholder="Ej: Repuestos verificados en bodega, empacados y protegidos con plástico de burbujas..."></textarea>
                    </div>

                    <!-- Información de Pago -->
                    <div class="p-3 rounded-3 border d-flex justify-content-between align-items-center" id="mPagoInfoBox">
                        <div>
                            <span class="text-muted small d-block">Estado del Pago:</span>
                            <span class="fw-bold" id="mEstadoPagoBadge"></span>
                            <span class="text-muted small ms-2" id="mMetodoPagoText"></span>
                        </div>
                        <div id="mFechaPagoText" class="text-muted small"></div>
                    </div>

                </div>

                <div class="modal-footer bg-light border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" id="btnModalCancelarPedido" onclick="cancelarPedidoModal()">
                        <i class="fa-solid fa-ban me-1"></i> Rechazar / Cancelar Pedido
                    </button>

                    <div class="d-flex gap-2" id="mBotonesAccion">
                        <!-- Botones de transición según estado actual inyectados dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentPedido = null;
    const modalRevisar = new bootstrap.Modal(document.getElementById('modalRevisar'));

    // Abre el modal y carga los datos frescos del pedido
    function abrirModalRevision(idVenta) {
        Swal.fire({
            title: 'Cargando información del pedido...',
            didOpen: () => Swal.showLoading(),
            allowOutsideClick: false,
            showConfirmButton: false
        });

        fetch(`../../controllers/vendedor_pedido_action.php?action=obtener_detalle&id_venta=${idVenta}`)
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.status === 'success' && data.pedido) {
                    currentPedido = data.pedido;
                    renderizarModal(data.pedido);
                    modalRevisar.show();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo cargar el pedido.' });
                }
            })
            .catch(err => {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo comunicar con el servidor.' });
            });
    }

    // Renderiza la vista del pedido en el modal
    function renderizarModal(p) {
        document.getElementById('mIdPedido').textContent = `#${String(p.id_venta).padStart(5, '0')}`;
        document.getElementById('mFechaPedido').textContent = `Recibido el ${new Date(p.fecha).toLocaleString('es-CO')}`;

        document.getElementById('mClienteNombre').textContent = p.cliente_nombre || 'Cliente';
        document.getElementById('mClienteDoc').textContent = p.cliente_doc ? `Doc: ${p.cliente_doc}` : '';
        document.getElementById('mClienteTel').textContent = p.telefono_contacto || p.cliente_telefono || 'N/A';
        document.getElementById('mClienteEmail').textContent = p.cliente_correo || '';
        document.getElementById('mClienteDir').textContent = 'Mostrador Principal / Establecimiento';
        document.getElementById('mClienteCiudad').textContent = 'Atención directa en tienda física (Sin envíos)';

        if (p.notas && p.notas.trim() !== '') {
            document.getElementById('mContNotas').style.display = 'block';
            document.getElementById('mClienteNotas').textContent = p.notas;
        } else {
            document.getElementById('mContNotas').style.display = 'none';
        }

        document.getElementById('mNotasVendedor').value = p.notas_vendedor || '';

        // Stepper
        const pasos = ['Pendiente', 'En Preparación', 'Listo para Pago', 'Pagado', 'Entregado'];
        let idx = pasos.indexOf(p.estado_pedido);
        if (idx === -1) {
            if (p.estado_pedido === 'En Camino' || p.estado_pedido === 'Confirmado') idx = 3;
            else idx = 0;
        }

        let stepperHtml = '';
        pasos.forEach((paso, i) => {
            let cls = '';
            if (i < idx) cls = 'completed';
            else if (i === idx) cls = 'active';

            stepperHtml += `
                <div class="m-step ${cls}">
                    <div class="m-step-circle">
                        <i class="fa-solid ${i < idx ? 'fa-check' : (i === idx ? 'fa-dot-circle' : 'fa-circle')}"></i>
                    </div>
                    <div class="m-step-label">${paso}</div>
                </div>
            `;
        });
        document.getElementById('modalStepper').innerHTML = stepperHtml;

        // Renderizar items de la tabla
        let tbodyHtml = '';
        let countItems = 0;
        (p.detalles || []).forEach(item => {
            countItems += parseInt(item.cantidad);
            const stockActual = parseInt(item.stock_actual || 0);
            const cantPedida = parseInt(item.cantidad);
            const stockSuficiente = stockActual >= 0; // Recuerda que ya se descontó atómicamente o está disponible

            const stockBadge = stockActual >= 0 
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i>${stockActual} en bodega</span>`
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-triangle-exclamation me-1"></i>Stock crítico (${stockActual})</span>`;

            tbodyHtml += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${item.imagen_url ? `<img src="../../${item.imagen_url}" class="table-thumb">` : `<div class="table-thumb d-flex align-items-center justify-content-center text-muted"><i class="fa-solid fa-motorcycle"></i></div>`}
                            <div>
                                <span class="badge bg-light text-primary border" style="font-size: 0.68rem;">${item.marca_nombre || 'Genérico'}</span>
                                <div class="fw-semibold text-dark small">${item.nombre}</div>
                                <span class="text-muted" style="font-size: 0.72rem;">Cód: ${item.codigo_interno || 'N/A'}</span>
                            </div>
                        </div>
                    </td>
                    <td class="text-center fw-bold fs-6">${cantPedida}</td>
                    <td class="text-center">${stockBadge}</td>
                    <td class="text-end text-muted small">$${Number(item.precio_unitario).toLocaleString('es-CO')}</td>
                    <td class="text-end fw-bold text-dark">$${Number(item.subtotal).toLocaleString('es-CO')}</td>
                </tr>
            `;
        });
        document.getElementById('mDetallesTbody').innerHTML = tbodyHtml;
        document.getElementById('mCountArticulos').textContent = `${countItems} artículos solicitados`;
        document.getElementById('mTotalPedido').textContent = `$${Number(p.total).toLocaleString('es-CO')}`;

        // Info de Pago
        const isPagado = (p.estado_pago === 'Pagado');
        document.getElementById('mEstadoPagoBadge').innerHTML = isPagado 
            ? '<span class="badge bg-success"><i class="fa-solid fa-check-circle me-1"></i>Pagado</span>'
            : '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Pendiente de Pago</span>';
        
        document.getElementById('mMetodoPagoText').textContent = `Método: ${p.metodo_pago || 'Contra Entrega'}`;
        document.getElementById('mFechaPagoText').textContent = p.fecha_pago ? `Fecha pago: ${new Date(p.fecha_pago).toLocaleString('es-CO')}` : '';

        // Botones de acción según el estado actual
        const contBotones = document.getElementById('mBotonesAccion');
        contBotones.innerHTML = '';

        if (p.estado_pedido === 'Pendiente') {
            contBotones.innerHTML = `
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="iniciarPreparacionModal(${p.id_venta})">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> Iniciar Preparación en Bodega
                </button>
            `;
        } else if (p.estado_pedido === 'En Preparación') {
            contBotones.innerHTML = `
                <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" onclick="marcarListoPagoModal(${p.id_venta})">
                    <i class="fa-solid fa-check-double me-2"></i> Marcar Listo para Pago en Caja
                </button>
            `;
        } else if (p.estado_pedido === 'Listo para Pago') {
            contBotones.innerHTML = `
                <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" onclick="abrirCobroCajaModal(${p.id_venta})">
                    <i class="fa-solid fa-cash-register me-2"></i> Cobrar en Caja
                </button>
                <button type="button" class="btn btn-outline-primary rounded-pill px-3 fw-semibold" onclick="entregarModal(${p.id_venta})" title="Entregar repuestos">
                    <i class="fa-solid fa-handshake me-1"></i> Entregar en Mostrador
                </button>
            `;
        } else if (p.estado_pedido === 'Pagado' || p.estado_pedido === 'En Camino') {
            contBotones.innerHTML = `
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="entregarModal(${p.id_venta})">
                    <i class="fa-solid fa-handshake me-2"></i> Entregar Repuestos al Cliente
                </button>
            `;
        } else if (p.estado_pedido === 'Entregado') {
            contBotones.innerHTML = `
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="fa-solid fa-circle-check me-1"></i> Pedido Entregado en Mostrador
                </span>
            `;
        } else if (p.estado_pedido === 'Cancelado') {
            contBotones.innerHTML = `
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="fa-solid fa-circle-xmark me-1"></i> Pedido Cancelado
                </span>
            `;
        }

        // Si ya está entregado o cancelado, deshabilitar botón de cancelación
        document.getElementById('btnModalCancelarPedido').style.display = (p.estado_pedido === 'Entregado' || p.estado_pedido === 'Cancelado') ? 'none' : 'inline-block';
    }

    // Acción 1: Iniciar preparación
    function iniciarPreparacionModal(idVenta) {
        const notas = document.getElementById('mNotasVendedor').value;

        Swal.fire({
            title: '¿Iniciar preparación de este pedido?',
            text: 'Asignarás este pedido a tu usuario y quedará en estado "En Preparación" para alistar los repuestos en bodega.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004ba8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-boxes-stacked me-1"></i> Sí, iniciar preparación',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                ejecutarAccionVendedor('iniciar_preparacion', { id_venta: idVenta, notas_vendedor: notas });
            }
        });
    }

    // Acción 2: Marcar listo para pago
    function marcarListoPagoModal(idVenta) {
        const notas = document.getElementById('mNotasVendedor').value;

        Swal.fire({
            title: '¿Marcar como Listo para Pago?',
            text: 'Confirmarás que los repuestos están listos y alistados en caja. El cliente podrá acercarse a cancelar su pedido.',
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Sí, producto alistado',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                ejecutarAccionVendedor('marcar_listo_pago', { id_venta: idVenta, notas_vendedor: notas });
            }
        });
    }

    // Acción 3: Cobrar en Caja
    function abrirCobroCajaModal(idVenta) {
        Swal.fire({
            title: 'Registrar Cobro en Caja',
            html: `
                <div class="text-start mb-2">
                    <p class="text-muted small mb-3">El cliente se encuentra en la caja del establecimiento para cancelar su pedido.</p>
                    <label class="form-label fw-bold small">Medio de Pago:</label>
                    <select id="swalMetodoPago" class="form-select mb-3">
                        <option value="Efectivo en Caja" selected>Efectivo en Caja</option>
                        <option value="Tarjeta / Datáfono en Caja">Tarjeta / Datáfono en Caja</option>
                        <option value="Transferencia / QR en Caja">Transferencia / QR en Caja</option>
                    </select>
                    <label class="form-label fw-bold small">Comprobante / Referencia (Opcional):</label>
                    <input id="swalComprobante" class="form-control" placeholder="Ej: REC-00912 / Datáfono Lote 45">
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-cash-register me-1"></i> Confirmar Cobro',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                return {
                    metodo_pago: document.getElementById('swalMetodoPago').value,
                    comprobante: document.getElementById('swalComprobante').value
                };
            }
        }).then(result => {
            if (result.isConfirmed) {
                const data = {
                    id_venta: idVenta,
                    metodo_pago: result.value.metodo_pago,
                    comprobante: result.value.comprobante,
                    notas_vendedor: document.getElementById('mNotasVendedor').value
                };
                ejecutarAccionVendedor('registrar_pago_caja', data);
            }
        });
    }

    // Acción 4: Confirmar entrega
    function entregarModal(idVenta) {
        Swal.fire({
            title: '¿Confirmar entrega del pedido?',
            text: 'El pedido se marcará como "Entregado" a satisfacción del cliente.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, confirmar entrega',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                ejecutarAccionVendedor('entregar_pedido', { id_venta: idVenta });
            }
        });
    }

    // Acción 5: Cancelar / Rechazar pedido
    function cancelarPedidoModal() {
        if (!currentPedido) return;
        const idVenta = currentPedido.id_venta;

        Swal.fire({
            title: 'Rechazar o Cancelar Pedido',
            input: 'textarea',
            inputLabel: 'Motivo de la cancelación (se notificará al cliente):',
            inputPlaceholder: 'Ej: Repuesto con avería en bodega, cliente solicitó anulación...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Cancelar Pedido y Devolver Stock',
            cancelButtonText: 'Volver',
            inputValidator: (value) => {
                if (!value || value.trim() === '') {
                    return 'Debes ingresar un motivo de cancelación.';
                }
            }
        }).then(result => {
            if (result.isConfirmed) {
                ejecutarAccionVendedor('cancelar_pedido', { id_venta: idVenta, motivo: result.value });
            }
        });
    }

    // Función auxiliar para enviar peticiones POST al controlador
    function ejecutarAccionVendedor(action, data) {
        data.action = action;

        Swal.fire({
            title: 'Procesando...',
            didOpen: () => Swal.showLoading(),
            allowOutsideClick: false,
            showConfirmButton: false
        });

        fetch('../../controllers/vendedor_pedido_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Operación Exitosa!',
                    text: resData.message,
                    confirmButtonColor: '#004ba8'
                }).then(() => {
                    modalRevisar.hide();
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: resData.message || 'No se pudo completar la operación.'
                });
            }
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
        });
    }
    </script>
</body>
</html>
