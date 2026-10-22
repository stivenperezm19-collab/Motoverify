<?php
session_start();
// Control de acceso en servidor: solo Clientes autenticados
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/ClienteModel.php';
require_once '../../models/VentaModel.php';
require_once '../../models/RepuestoModel.php';
require_once '../../models/MarcaModel.php';

$clienteModel = new ClienteModel($conn);
$ventaModel = new VentaModel($conn);
$repModel = new RepuestoModel($conn);
$marcaModel = new MarcaModel($conn);

$id_cliente = (int)$_SESSION['user_id'];
$cliente = $clienteModel->getById($id_cliente);
$stats = $clienteModel->getEstadisticas($id_cliente);

// Obtener los 4 pedidos más recientes
$pedidos = $ventaModel->getPedidosByCliente($id_cliente);
$pedidosRecientes = array_slice($pedidos, 0, 4);

// Obtener marcas destacadas
$marcas_dashboard = [];
try {
    $query_marcas = "SELECT m.*, i.ruta_archivo as imagen_url 
                     FROM MARCA m 
                     INNER JOIN MARCA_IMAGEN mi ON m.id_marca = mi.id_marca AND mi.imagen_principal = 1
                     INNER JOIN IMAGEN i ON mi.id_imagen = i.id_imagen 
                     WHERE m.estado = 'ACTIVO' AND i.ruta_archivo IS NOT NULL AND i.ruta_archivo != ''
                     ORDER BY m.id_marca DESC LIMIT 7";
    $resMarcas = $conn->query($query_marcas);
    if ($resMarcas) {
        while ($m = $resMarcas->fetch_assoc()) {
            $marcas_dashboard[] = $m;
        }
    }
} catch (Exception $e) {}

// Obtener 6 repuestos destacados disponibles para la venta
$repuestos = [];
try {
    $query_rep = "SELECT r.*, m.nombre as marca_nombre, c.nombre as categoria_nombre, i.ruta_archivo as imagen_url 
                  FROM REPUESTO r 
                  LEFT JOIN MARCA m ON r.id_marca = m.id_marca 
                  LEFT JOIN CATEGORIA c ON r.id_categoria = c.id_categoria
                  LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
                  LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
                  WHERE (r.estado = 'Activo' OR r.estado = 'ACTIVO') AND r.stock > 0
                  ORDER BY r.id_repuesto DESC LIMIT 6";
    $resRep = $conn->query($query_rep);
    if ($resRep) {
        while ($r = $resRep->fetch_assoc()) {
            $repuestos[] = $r;
        }
    }
} catch (Exception $e) {}

$articulosCarrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $it) {
        $articulosCarrito += (int)($it['cantidad'] ?? 1);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Mi Panel de Cliente</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }

        /* ===== TOPBAR ===== */
        .topbar-global {
            height: 110px;
            background: #ffffff;
            border-bottom: 1px solid #eaeaea;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1050;
        }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 85px; width: auto; object-fit: contain; object-position: left center; }
        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }

        /* ===== SIDEBAR ===== */
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.02rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.15rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }

        /* ===== MAIN CONTENT ===== */
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); display: flex; flex-direction: column; background-color: #f8f9fc; }

        /* Hero Banner */
        .hero-banner { background: linear-gradient(135deg, #004ba8 0%, #1e5abf 100%); color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 40px; top: 25%; height: 50%; width: 4px; background-color: #60a5fa; border-radius: 4px; }
        .hero-gears { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); pointer-events: none; }

        /* Stats Cards */
        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 20px 24px;
            border: 1px solid #f0f1f3;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        /* Section Titles */
        .section-title { font-weight: 700; font-size: 1.15rem; color: #1f2937; margin-bottom: 18px; }

        /* Brands */
        .brands-card { background: #fff; border: 1px solid #f0f1f3; border-radius: 14px; padding: 18px 28px; margin-bottom: 30px; }
        .brand-item { font-weight: 800; font-size: 1.1rem; font-style: italic; color: #4b5563; cursor: pointer; transition: opacity 0.2s; }
        .btn-ver-todas { border: 1px solid #bfdbfe; color: #2563eb; background: #fff; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; text-decoration: none; transition: background 0.2s; }
        .btn-ver-todas:hover { background: #eff6ff; color: #1d4ed8; }

        /* Product Cards */
        .part-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid #f0f1f3;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s, transform 0.2s;
            height: 100%;
        }
        .part-card:hover { box-shadow: 0 10px 25px rgba(0,0,0,0.06); transform: translateY(-3px); }
        .part-img { width: 100%; height: 140px; object-fit: contain; margin-bottom: 12px; }
        .part-code { font-weight: 700; color: #111; font-size: 0.95rem; margin-bottom: 2px; }
        .part-name { font-size: 0.9rem; color: #4b5563; font-weight: 500; line-height: 1.35; margin-bottom: 6px; min-height: 38px; }
        .part-brand { color: #2563eb; font-weight: 600; font-size: 0.85rem; }
        .part-price { font-size: 1.25rem; font-weight: 800; color: #004ba8; margin-top: auto; }
        .stock-badge { font-size: 0.75rem; padding: 3px 8px; border-radius: 6px; font-weight: 600; }

        /* Tables */
        .table-custom { background: #fff; border-radius: 14px; overflow: hidden; border: 1px solid #f0f1f3; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.88rem; padding: 14px 18px; border-bottom: 1px solid #e2e8f0; }
        .table-custom td { padding: 14px 18px; vertical-align: middle; font-size: 0.9rem; border-bottom: 1px solid #f1f5f9; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <!-- TOPBAR COMPONENT -->
    <?php require_once 'topbar.php'; ?>

    <!-- SIDEBAR COMPONENT -->
    <?php require_once 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="p-4 flex-grow-1">

            <!-- Hero Banner -->
            <div class="hero-banner rounded-3 p-4 ps-5 mb-4 d-flex align-items-center justify-content-between shadow-sm">
                <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                    <span class="badge bg-light text-primary fw-semibold px-3 py-1 mb-2 rounded-pill">Panel de Cliente</span>
                    <h2 class="fw-bold mb-1">¡Hola, <?= htmlspecialchars($cliente['nombre'] ?? 'Cliente') ?>!</h2>
                    <p class="mb-0 opacity-75" style="font-size: 1rem;">Bienvenido a tu portal de compras en Motoverify. Encuentra repuestos originales y haz seguimiento a tus pedidos.</p>
                </div>
                <div class="hero-gears d-none d-md-block">
                    <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                    <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
                </div>
            </div>

            <!-- Stats Widgets -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Pedidos Realizados</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= $stats['total_pedidos'] ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="fa-solid fa-box-archive"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Pedidos en Curso</span>
                            <h3 class="fw-bold text-warning mt-1 mb-0"><?= $stats['pedidos_pendientes'] ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="fa-solid fa-truck-fast"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Total Comprado</span>
                            <h3 class="fw-bold text-success mt-1 mb-0">$<?= number_format($stats['total_gastado'], 0, ',', '.') ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">En Mi Carrito</span>
                            <h3 class="fw-bold text-primary mt-1 mb-0 cart-badge-count"><?= $articulosCarrito ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marcas Destacadas -->
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="section-title mb-0">Marcas disponibles</h3>
                <a href="catalogo.php" class="btn-ver-todas d-inline-flex align-items-center gap-2">
                    Ver catálogo completo <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
                </a>
            </div>
            <div class="brands-card d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center flex-wrap" style="gap: 40px;">
                    <?php if (empty($marcas_dashboard)): ?>
                        <div class="text-muted small">Cargando marcas registradas...</div>
                    <?php else: ?>
                        <?php foreach ($marcas_dashboard as $m): ?>
                            <a href="catalogo.php?marca=<?= $m['id_marca']; ?>" style="text-decoration: none;" title="Ver repuestos de <?= htmlspecialchars($m['nombre']); ?>">
                                <?php if (!empty($m['imagen_url'])): ?>
                                    <img src="../../<?= htmlspecialchars($m['imagen_url']); ?>" alt="<?= htmlspecialchars($m['nombre']); ?>" style="height: 65px; width: auto; max-width: 140px; object-fit: contain; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)';" onmouseout="this.style.transform='scale(1)';">
                                <?php else: ?>
                                    <div class="brand-item text-dark fw-bold"><?= htmlspecialchars(strtoupper($m['nombre'])); ?></div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Repuestos Destacados para Comprar -->
            <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
                <h3 class="section-title mb-0">Repuestos recomendados para ti</h3>
                <a href="catalogo.php" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3">
                    Explorar Todos <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row g-4 mb-5">
                <?php if (empty($repuestos)): ?>
                    <div class="col-12 text-center py-4 text-muted">
                        <i class="fa-solid fa-box-open fs-2 mb-2"></i>
                        <p>No hay repuestos disponibles en este momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($repuestos as $r): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="part-card shadow-sm">
                                <div class="text-center p-2 mb-2 bg-light rounded-3">
                                    <?php if (!empty($r['imagen_url'])): ?>
                                        <img src="../../<?= htmlspecialchars($r['imagen_url']); ?>" alt="<?= htmlspecialchars($r['nombre']); ?>" class="part-img">
                                    <?php else: ?>
                                        <div class="part-img d-flex align-items-center justify-content-center text-secondary">
                                            <i class="fa-solid fa-motorcycle fs-1 opacity-25"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="part-brand"><?= htmlspecialchars($r['marca_nombre'] ?? 'Genérico') ?></span>
                                    <span class="badge bg-success-subtle text-success stock-badge">
                                        <i class="fa-solid fa-circle-check me-1"></i> Stock: <?= $r['stock'] ?>
                                    </span>
                                </div>

                                <div class="part-code"><?= htmlspecialchars($r['codigo_interno']) ?></div>
                                <div class="part-name" title="<?= htmlspecialchars($r['nombre']) ?>">
                                    <?= htmlspecialchars($r['nombre']) ?>
                                </div>

                                <?php if (!empty($r['compatibilidades'])): ?>
                                    <div class="text-muted small mb-2 text-truncate" style="font-size: 0.8rem;">
                                        <i class="fa-solid fa-wrench me-1"></i> <?= htmlspecialchars($r['compatibilidades']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                                    <div class="part-price">
                                        $<?= number_format($r['precio'], 0, ',', '.') ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="detalle_producto.php?id=<?= $r['id_repuesto'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2" title="Ver ficha técnica">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 d-flex align-items-center gap-1 btn-add-cart" data-id="<?= $r['id_repuesto'] ?>">
                                            <i class="fa-solid fa-cart-plus"></i> Añadir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Últimos Pedidos Realizados -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="section-title mb-0">Mis pedidos recientes</h3>
                <a href="historial_compras.php" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3">
                    Ver todos mis pedidos <i class="fa-solid fa-clock-rotate-left ms-1"></i>
                </a>
            </div>

            <div class="table-custom mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>N° Pedido</th>
                                <th>Fecha</th>
                                <th>Artículos</th>
                                <th>Método de Pago</th>
                                <th>Estado del Pedido</th>
                                <th>Total</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidosRecientes)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-bag-shopping fs-1 mb-2 text-primary opacity-50"></i>
                                        <p class="mb-2 fw-semibold">Aún no has realizado ninguna compra.</p>
                                        <a href="catalogo.php" class="btn btn-sm btn-primary rounded-pill px-4">
                                            Explorar catálogo y hacer mi primer pedido
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pedidosRecientes as $p): 
                                    $badgeClass = 'bg-secondary';
                                    if ($p['estado_pedido'] === 'Pendiente') $badgeClass = 'bg-warning text-dark';
                                    elseif ($p['estado_pedido'] === 'Confirmado') $badgeClass = 'bg-info text-white';
                                    elseif ($p['estado_pedido'] === 'En Camino') $badgeClass = 'bg-primary text-white';
                                    elseif ($p['estado_pedido'] === 'Entregado') $badgeClass = 'bg-success text-white';
                                    elseif ($p['estado_pedido'] === 'Cancelado') $badgeClass = 'bg-danger text-white';
                                ?>
                                    <tr>
                                        <td class="fw-bold text-primary">#<?= str_pad($p['id_venta'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td class="text-muted"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                                        <td><?= $p['total_items'] ?> unid.</td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['metodo_pago'] ?? 'Contra Entrega') ?></span></td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?> px-3 py-1 rounded-pill">
                                                <?= htmlspecialchars($p['estado_pedido'] ?? 'Pendiente') ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark">$<?= number_format($p['total'], 0, ',', '.') ?></td>
                                        <td class="text-end">
                                            <a href="detalle_compra.php?id=<?= $p['id_venta'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="fa-solid fa-circle-info me-1"></i> Detalle
                                            </a>
                                            <a href="../../public/factura.php?id=<?= $p['id_venta'] ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2" title="Ver Factura">
                                                <i class="fa-solid fa-file-invoice text-secondary"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script para añadir al carrito de forma asíncrona -->
    <script>
    document.querySelectorAll('.btn-add-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const originalHTML = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('../../controllers/cliente_pedido_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'agregar_carrito', id_repuesto: id, cantidad: 1 })
            })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                this.innerHTML = originalHTML;

                if (data.status === 'success') {
                    // Actualizar badges del carrito en toda la pantalla
                    document.querySelectorAll('.cart-badge-count').forEach(el => {
                        el.textContent = data.total_articulos;
                        el.classList.remove('d-none');
                    });

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message,
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: data.message
                    });
                }
            })
            .catch(err => {
                this.disabled = false;
                this.innerHTML = originalHTML;
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo comunicar con el servidor.' });
            });
        });
    });
    </script>
</body>
</html>
