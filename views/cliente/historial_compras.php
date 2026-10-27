<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/VentaModel.php';

$ventaModel = new VentaModel($conn);
$id_cliente = (int)$_SESSION['user_id'];
$pedidos = $ventaModel->getPedidosByCliente($id_cliente);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Motoverify</title>
    
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

        .orders-card { background: #fff; border-radius: 14px; padding: 25px; border: 1px solid #f0f1f3; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.88rem; padding: 14px 18px; border-bottom: 1px solid #e2e8f0; }
        .table-custom td { padding: 14px 18px; vertical-align: middle; font-size: 0.92rem; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>

    <?php require_once 'topbar.php'; ?>
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h2 class="fw-bold mb-1 text-dark">Historial de Mis Pedidos</h2>
                <p class="text-muted mb-0">Consulta el detalle, estado y facturas de todas tus compras en Motoverify.</p>
            </div>
            <a href="catalogo.php" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                <i class="fa-solid fa-cart-plus me-1"></i> Nueva Compra
            </a>
        </div>

        <div class="orders-card">
            <?php if (empty($pedidos)): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-box-open fs-1 text-primary opacity-50 mb-3" style="font-size: 3.5rem !important;"></i>
                    <h4 class="fw-bold text-dark">No tienes compras registradas</h4>
                    <p class="text-muted mb-4">Cuando realices compras en nuestra tienda, podrás ver el seguimiento en esta sección.</p>
                    <a href="catalogo.php" class="btn btn-outline-primary rounded-pill px-4">
                        Explorar Catálogo de Productos
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>N° Pedido</th>
                                <th>Fecha de Compra</th>
                                <th>Artículos</th>
                                <th>Modalidad / Retiro</th>
                                <th>Método de Pago</th>
                                <th>Estado del Pedido</th>
                                <th>Total</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): 
                                $badgeClass = 'bg-secondary text-white';
                                if ($p['estado_pedido'] === 'Pendiente') $badgeClass = 'bg-warning text-dark';
                                elseif ($p['estado_pedido'] === 'En Preparación') $badgeClass = 'bg-info text-dark fw-semibold';
                                elseif ($p['estado_pedido'] === 'Listo para Pago') $badgeClass = 'bg-primary text-white fw-bold';
                                elseif ($p['estado_pedido'] === 'Pagado') $badgeClass = 'bg-success text-white fw-bold';
                                elseif ($p['estado_pedido'] === 'Confirmado') $badgeClass = 'bg-info text-white';
                                elseif ($p['estado_pedido'] === 'En Camino') $badgeClass = 'bg-dark text-white';
                                elseif ($p['estado_pedido'] === 'Entregado') $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                elseif ($p['estado_pedido'] === 'Cancelado') $badgeClass = 'bg-danger text-white';
                            ?>
                                <tr>
                                    <td class="fw-bold text-primary">#<?= str_pad($p['id_venta'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td class="text-muted small">
                                        <i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y', strtotime($p['fecha'])) ?>
                                        <span class="d-block text-secondary" style="font-size: 0.75rem;"><?= date('H:i A', strtotime($p['fecha'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            <?= $p['total_items'] ?> unid.
                                        </span>
                                    </td>
                                    <td class="small" style="max-width: 200px;">
                                        <div class="fw-semibold text-dark"><i class="fa-solid fa-store text-primary me-1"></i>Retiro en Mostrador</div>
                                        <span class="text-muted" style="font-size: 0.75rem;">En Establecimiento</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">
                                            <?= htmlspecialchars($p['metodo_pago'] ?? 'Pago en Caja') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?> px-3 py-1 rounded-pill">
                                            <?= htmlspecialchars($p['estado_pedido'] ?? 'Pendiente') ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark fs-6">
                                        $<?= number_format($p['total'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <?php if ($p['estado_pedido'] === 'Listo para Pago'): ?>
                                                <a href="detalle_compra.php?id=<?= $p['id_venta'] ?>" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm" title="El vendedor ya preparó tu pedido. Haz clic para pagar en caja.">
                                                    <i class="fa-solid fa-cash-register me-1"></i> Pagar en Caja
                                                </a>
                                            <?php endif; ?>
                                            <a href="detalle_compra.php?id=<?= $p['id_venta'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" title="Ver detalle del pedido">
                                                <i class="fa-solid fa-circle-info me-1"></i> Detalle
                                            </a>
                                            <a href="../../public/factura.php?id=<?= $p['id_venta'] ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2" title="Descargar Factura">
                                                <i class="fa-solid fa-file-invoice text-secondary"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
