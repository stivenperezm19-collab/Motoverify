<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../config/database.php';

// Inicializar y cargar carrito calculado directamente desde la base de datos
$carritoSession = $_SESSION['carrito'] ?? [];
$items = [];
$subtotal = 0.00;
$total_articulos = 0;

if (!empty($carritoSession)) {
    foreach ($carritoSession as $id_rep => $itemData) {
        $id = (int)$id_rep;
        $cant = max(1, (int)($itemData['cantidad'] ?? 1));

        $query = "SELECT r.id_repuesto, r.nombre, r.precio, r.stock, r.codigo_interno, r.estado,
                         m.nombre as marca_nombre, i.ruta_archivo as imagen_url
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
            unset($_SESSION['carrito'][$id]);
        }
    }
}

$impuestos = $subtotal * 0.19; // IVA 19%
$total = $subtotal + $impuestos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito de Compras - Motoverify</title>
    
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

        .card-custom { background: #fff; border-radius: 14px; padding: 25px; border: 1px solid #f0f1f3; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .cart-item-img { width: 75px; height: 75px; object-fit: contain; background: #f8fafc; border-radius: 10px; padding: 6px; border: 1px solid #e2e8f0; }

        .qty-control { width: 110px; }
        .qty-btn-sm { width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .summary-box { background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; }
        .summary-total { font-size: 1.5rem; font-weight: 800; color: #004ba8; }
    </style>
</head>
<body>

    <?php require_once 'topbar.php'; ?>
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1 text-dark">Mi Carrito de Compras</h2>
                <p class="text-muted mb-0">Revisa los repuestos seleccionados antes de proceder a la confirmación del pedido.</p>
            </div>
            <?php if (!empty($items)): ?>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="vaciarCarrito()">
                    <i class="fa-solid fa-trash me-1"></i> Vaciar Carrito
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($items)): ?>
            <!-- Estado Carrito Vacío -->
            <div class="card-custom text-center py-5">
                <div class="py-4">
                    <i class="fa-solid fa-cart-arrow-down fs-1 text-primary opacity-50 mb-3" style="font-size: 4rem !important;"></i>
                    <h3 class="fw-bold text-dark">Tu carrito de compras está vacío</h3>
                    <p class="text-muted mb-4">Parece que aún no has agregado repuestos a tu compra.</p>
                    <a href="catalogo.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-semibold shadow-sm">
                        <i class="fa-solid fa-book-open me-2"></i> Explorar Catálogo de Repuestos
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Columna Tabla de Productos -->
                <div class="col-12 col-lg-8">
                    <div class="card-custom">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Repuesto</th>
                                        <th>Precio Unit.</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr id="cartRow-<?= $item['id_repuesto'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <?php if (!empty($item['imagen_url'])): ?>
                                                        <img src="../../<?= htmlspecialchars($item['imagen_url']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>" class="cart-item-img">
                                                    <?php else: ?>
                                                        <div class="cart-item-img d-flex align-items-center justify-content-center text-muted">
                                                            <i class="fa-solid fa-motorcycle fs-3 opacity-25"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <span class="badge bg-light text-primary border mb-1" style="font-size: 0.72rem;"><?= htmlspecialchars($item['marca']) ?></span>
                                                        <a href="detalle_producto.php?id=<?= $item['id_repuesto'] ?>" class="text-dark fw-semibold text-decoration-none d-block" style="font-size: 0.95rem;">
                                                            <?= htmlspecialchars($item['nombre']) ?>
                                                        </a>
                                                        <span class="text-muted small">Cód: <?= htmlspecialchars($item['codigo_interno']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="fw-semibold text-dark">
                                                $<?= number_format($item['precio'], 0, ',', '.') ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="input-group qty-control mx-auto">
                                                    <button class="btn btn-outline-secondary qty-btn-sm" type="button" onclick="updateItemQty(<?= $item['id_repuesto'] ?>, <?= $item['cantidad'] - 1 ?>)">-</button>
                                                    <input type="text" class="form-control form-control-sm text-center fw-bold bg-white" value="<?= $item['cantidad'] ?>" readonly>
                                                    <button class="btn btn-outline-secondary qty-btn-sm" type="button" onclick="updateItemQty(<?= $item['id_repuesto'] ?>, <?= $item['cantidad'] + 1 ?>)" <?= $item['cantidad'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                                                </div>
                                                <span class="text-muted" style="font-size: 0.72rem;">Disp: <?= $item['stock'] ?></span>
                                            </td>
                                            <td class="text-end fw-bold text-primary" style="font-size: 1.05rem;">
                                                $<?= number_format($item['subtotal_item'], 0, ',', '.') ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-light text-danger rounded-circle" onclick="removeItem(<?= $item['id_repuesto'] ?>)" title="Eliminar del carrito">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                            <a href="catalogo.php" class="btn btn-outline-primary rounded-pill px-4">
                                <i class="fa-solid fa-arrow-left me-1"></i> Seguir Comprando
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Columna Resumen de Pedido -->
                <div class="col-12 col-lg-4">
                    <div class="card-custom">
                        <h4 class="fw-bold text-dark mb-3">Resumen de Compra</h4>

                        <div class="summary-box mb-4">
                            <div class="d-flex justify-content-between text-muted mb-2">
                                <span>Subtotal (<?= $total_articulos ?> unid.)</span>
                                <span class="fw-semibold text-dark">$<?= number_format($subtotal, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted mb-2">
                                <span>IVA estimado (19%)</span>
                                <span class="fw-semibold text-dark">$<?= number_format($impuestos, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted mb-3 pb-3 border-bottom">
                                <span>Envío</span>
                                <span class="text-success fw-semibold">Gratis a domicilio</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark fs-5">Total a Pagar:</span>
                                <span class="summary-total">$<?= number_format($total, 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <a href="checkout.php" class="btn btn-success btn-lg w-100 rounded-pill py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm mb-3">
                            <i class="fa-solid fa-shield-check"></i> Proceder al Pago
                        </a>

                        <div class="text-center text-muted small">
                            <p class="mb-1"><i class="fa-solid fa-truck-fast text-primary me-1"></i> Envío rápido con pago Contra Entrega</p>
                            <p class="mb-0"><i class="fa-solid fa-lock text-success me-1"></i> Compra segura y garantizada</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateItemQty(id, newQty) {
        fetch('../../controllers/cliente_pedido_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'actualizar_carrito', id_repuesto: id, cantidad: newQty })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                Swal.fire({ icon: 'warning', title: 'Atención', text: data.message });
            }
        });
    }

    function removeItem(id) {
        Swal.fire({
            title: '¿Quitar repuesto?',
            text: '¿Deseas retirar este artículo del carrito?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, quitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../../controllers/cliente_pedido_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'eliminar_carrito', id_repuesto: id })
                })
                .then(res => res.json())
                .then(data => {
                    window.location.reload();
                });
            }
        });
    }

    function vaciarCarrito() {
        Swal.fire({
            title: '¿Vaciar carrito?',
            text: 'Se eliminarán todos los artículos agregados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, vaciar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../../controllers/cliente_pedido_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'vaciar_carrito' })
                })
                .then(res => res.json())
                .then(data => {
                    window.location.reload();
                });
            }
        });
    }
    </script>
</body>
</html>
