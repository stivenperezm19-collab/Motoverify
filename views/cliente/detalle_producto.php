<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../config/database.php';

$id_repuesto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_repuesto <= 0) {
    header('Location: catalogo.php');
    exit;
}

// 1. Obtener información completa del producto
$query = "SELECT r.*, m.nombre as marca_nombre, c.nombre as categoria_nombre 
          FROM REPUESTO r
          LEFT JOIN MARCA m ON r.id_marca = m.id_marca
          LEFT JOIN CATEGORIA c ON r.id_categoria = c.id_categoria
          WHERE r.id_repuesto = ? AND (r.estado = 'Activo' OR r.estado = 'ACTIVO')";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_repuesto);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header('Location: catalogo.php');
    exit;
}
$repuesto = $res->fetch_assoc();
$stmt->close();

// 2. Obtener todas las fotos asociadas al repuesto
$queryFotos = "SELECT i.ruta_archivo, ri.imagen_principal 
               FROM REPUESTO_IMAGEN ri
               JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
               WHERE ri.id_repuesto = ? 
               ORDER BY ri.imagen_principal DESC, ri.orden ASC";

$stmtFotos = $conn->prepare($queryFotos);
$stmtFotos->bind_param("i", $id_repuesto);
$stmtFotos->execute();
$resFotos = $stmtFotos->get_result();
$imagenes = [];
while ($img = $resFotos->fetch_assoc()) {
    $imagenes[] = $img['ruta_archivo'];
}
$stmtFotos->close();

$fotoPrincipal = !empty($imagenes) ? $imagenes[0] : null;

// 3. Productos relacionados (misma categoría o marca)
$relacionados = [];
$catId = (int)$repuesto['id_categoria'];
$marcaId = (int)$repuesto['id_marca'];
$queryRel = "SELECT r.id_repuesto, r.nombre, r.precio, r.stock, m.nombre as marca_nombre, i.ruta_archivo as imagen_url
             FROM REPUESTO r
             LEFT JOIN MARCA m ON r.id_marca = m.id_marca
             LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
             LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
             WHERE (r.id_categoria = $catId OR r.id_marca = $marcaId) 
               AND r.id_repuesto != $id_repuesto 
               AND (r.estado = 'Activo' OR r.estado = 'ACTIVO')
             LIMIT 4";
$resRel = $conn->query($queryRel);
if ($resRel) {
    while ($rel = $resRel->fetch_assoc()) {
        $relacionados[] = $rel;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($repuesto['nombre']) ?> - Motoverify</title>
    
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

        .product-card-main { background: #fff; border-radius: 16px; padding: 35px; border: 1px solid #f0f1f3; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 30px; }
        .gallery-main-img { width: 100%; height: 380px; object-fit: contain; background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; }
        .gallery-thumb { width: 70px; height: 70px; object-fit: contain; border-radius: 8px; padding: 5px; border: 2px solid #e2e8f0; cursor: pointer; transition: all 0.2s; background: #fff; }
        .gallery-thumb.active { border-color: #004ba8; }

        .price-badge-lg { font-size: 2.2rem; font-weight: 800; color: #004ba8; line-height: 1; }
        .qty-input-group { max-width: 140px; }
        .qty-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .nav-tabs-custom .nav-link { color: #64748b; font-weight: 600; border: none; border-bottom: 3px solid transparent; padding: 12px 20px; }
        .nav-tabs-custom .nav-link.active { color: #004ba8; border-bottom-color: #004ba8; background: transparent; }

        .part-card-rel { background: #fff; border-radius: 12px; padding: 16px; border: 1px solid #f0f1f3; transition: all 0.2s; height: 100%; display: flex; flex-direction: column; }
        .part-card-rel:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
    </style>
</head>
<body>

    <?php require_once 'topbar.php'; ?>
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Breadcrumb de navegación -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Inicio</a></li>
                <li class="breadcrumb-item"><a href="catalogo.php" class="text-decoration-none">Catálogo</a></li>
                <?php if (!empty($repuesto['categoria_nombre'])): ?>
                    <li class="breadcrumb-item"><a href="catalogo.php?categoria=<?= $repuesto['id_categoria'] ?>" class="text-decoration-none"><?= htmlspecialchars($repuesto['categoria_nombre']) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($repuesto['nombre']) ?></li>
            </ol>
        </nav>

        <!-- Tarjeta Principal del Producto -->
        <div class="product-card-main">
            <div class="row g-5">
                <!-- Columna Galería de Fotos -->
                <div class="col-12 col-lg-6">
                    <div class="text-center mb-3">
                        <?php if ($fotoPrincipal): ?>
                            <img id="mainGalleryImg" src="../../<?= htmlspecialchars($fotoPrincipal) ?>" alt="<?= htmlspecialchars($repuesto['nombre']) ?>" class="gallery-main-img">
                        <?php else: ?>
                            <div class="gallery-main-img d-flex align-items-center justify-content-center text-muted">
                                <i class="fa-solid fa-motorcycle fs-1 opacity-25"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($imagenes) > 1): ?>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <?php foreach ($imagenes as $idx => $img): ?>
                                <img src="../../<?= htmlspecialchars($img) ?>" class="gallery-thumb <?= $idx === 0 ? 'active' : '' ?>" onclick="changeMainImage(this, '../../<?= htmlspecialchars($img) ?>')">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Columna Datos y Compra -->
                <div class="col-12 col-lg-6 d-flex flex-direction-column">
                    <div>
                        <!-- Badges de marca y stock -->
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 rounded-pill" style="font-size: 0.85rem;">
                                <?= htmlspecialchars($repuesto['marca_nombre'] ?? 'Genérico') ?>
                            </span>
                            <?php if ($repuesto['stock'] > 0): ?>
                                <span class="badge bg-success-subtle text-success fw-bold px-3 py-1 rounded-pill" style="font-size: 0.85rem;">
                                    <i class="fa-solid fa-circle-check me-1"></i> Stock Disponible (<?= $repuesto['stock'] ?> unidades)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger fw-bold px-3 py-1 rounded-pill" style="font-size: 0.85rem;">
                                    <i class="fa-solid fa-circle-xmark me-1"></i> Sin Stock
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Título del repuesto -->
                        <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($repuesto['nombre']) ?></h2>

                        <!-- Identificadores de referencia -->
                        <div class="d-flex flex-wrap gap-3 text-muted small mb-3 pb-3 border-bottom">
                            <div><strong>Cód. Interno:</strong> <?= htmlspecialchars($repuesto['codigo_interno']) ?></div>
                            <?php if (!empty($repuesto['codigo_barras'])): ?>
                                <div><strong>Cód. Barras:</strong> <?= htmlspecialchars($repuesto['codigo_barras']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($repuesto['referencia_fabricante'])): ?>
                                <div><strong>Ref. Fabricante:</strong> <?= htmlspecialchars($repuesto['referencia_fabricante']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Precio -->
                        <div class="mb-4">
                            <span class="text-muted small d-block mb-1">Precio Unitario (IVA incluido):</span>
                            <div class="price-badge-lg">$<?= number_format($repuesto['precio'], 0, ',', '.') ?> <span class="fs-6 text-muted fw-normal">COP</span></div>
                        </div>

                        <!-- Selector de Cantidad y Botones de Compra -->
                        <?php if ($repuesto['stock'] > 0): ?>
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <label class="fw-semibold text-dark mb-0">Cantidad:</label>
                                <div class="input-group qty-input-group">
                                    <button class="btn btn-outline-secondary qty-btn" type="button" onclick="decrementQty()">-</button>
                                    <input type="number" id="buyQuantity" class="form-control text-center fw-bold" value="1" min="1" max="<?= $repuesto['stock'] ?>" readonly>
                                    <button class="btn btn-outline-secondary qty-btn" type="button" onclick="incrementQty(<?= $repuesto['stock'] ?>)">+</button>
                                </div>
                                <span class="text-muted small">Máx. <?= $repuesto['stock'] ?></span>
                            </div>

                            <div class="d-flex flex-wrap gap-3 mb-4">
                                <button type="button" class="btn btn-primary btn-lg rounded-pill px-4 d-flex align-items-center gap-2 fw-semibold shadow-sm" id="btnAddToCart">
                                    <i class="fa-solid fa-cart-plus"></i> Añadir al Carrito
                                </button>
                                <button type="button" class="btn btn-success btn-lg rounded-pill px-4 d-flex align-items-center gap-2 fw-semibold shadow-sm" id="btnBuyNow">
                                    <i class="fa-solid fa-bolt"></i> Comprar Ahora
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning rounded-3 mb-4">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                Este repuesto está temporalmente agotado. Puedes consultar otros modelos compatibles abajo.
                            </div>
                        <?php endif; ?>

                        <!-- Ventajas de compra directa -->
                        <div class="bg-light rounded-3 p-3 mt-4 border">
                            <div class="row g-2 text-muted small">
                                <div class="col-6 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-shield-halved text-primary fs-5"></i>
                                    <span>Garantía de originalidad Motoverify</span>
                                </div>
                                <div class="col-6 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-truck text-primary fs-5"></i>
                                    <span>Entrega directa y pago contraentrega</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestañas de Especificaciones Detalladas -->
            <div class="mt-5 pt-4 border-top">
                <ul class="nav nav-tabs nav-tabs-custom mb-4" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc-pane" type="button" role="tab">Descripción</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="compat-tab" data-bs-toggle="tab" data-bs-target="#compat-pane" type="button" role="tab">Compatibilidad de Motos</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="caract-tab" data-bs-toggle="tab" data-bs-target="#caract-pane" type="button" role="tab">Características Técnicas</button>
                    </li>
                </ul>

                <div class="tab-content" id="productTabsContent">
                    <!-- Tab Descripción -->
                    <div class="tab-pane fade show active p-2" id="desc-pane" role="tabpanel">
                        <?php if (!empty($repuesto['descripcion'])): ?>
                            <p class="text-secondary" style="line-height: 1.7;"><?= nl2br(htmlspecialchars($repuesto['descripcion'])) ?></p>
                        <?php else: ?>
                            <p class="text-muted fst-italic">No hay descripción adicional registrada para este repuesto.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Tab Compatibilidad -->
                    <div class="tab-pane fade p-2" id="compat-pane" role="tabpanel">
                        <?php if (!empty($repuesto['compatibilidades'])): ?>
                            <div class="alert alert-info border-0 rounded-3 mb-3">
                                <i class="fa-solid fa-circle-info me-1"></i> Este repuesto ha sido verificado como compatible con los siguientes modelos:
                            </div>
                            <p class="fw-semibold text-dark fs-6"><?= htmlspecialchars($repuesto['compatibilidades']) ?></p>
                        <?php else: ?>
                            <p class="text-muted fst-italic">No se han registrado compatibilidades específicas.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Tab Características -->
                    <div class="tab-pane fade p-2" id="caract-pane" role="tabpanel">
                        <?php if (!empty($repuesto['caracteristicas'])): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm w-auto">
                                    <tbody class="text-secondary">
                                        <tr>
                                            <th class="bg-light px-3 py-2">Detalles:</th>
                                            <td class="px-3 py-2"><?= nl2br(htmlspecialchars($repuesto['caracteristicas'])) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted fst-italic">No hay características técnicas tabuladas para este repuesto.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos Relacionados -->
        <?php if (!empty($relacionados)): ?>
            <div class="mb-4">
                <h4 class="fw-bold text-dark mb-3">Productos relacionados</h4>
                <div class="row g-3">
                    <?php foreach ($relacionados as $rel): ?>
                        <div class="col-6 col-md-3">
                            <div class="part-card-rel">
                                <div class="text-center p-2 bg-light rounded-3 mb-2">
                                    <?php if (!empty($rel['imagen_url'])): ?>
                                        <img src="../../<?= htmlspecialchars($rel['imagen_url']) ?>" alt="<?= htmlspecialchars($rel['nombre']) ?>" style="height: 110px; width: 100%; object-fit: contain;">
                                    <?php else: ?>
                                        <div style="height: 110px;" class="d-flex align-items-center justify-content-center text-muted">
                                            <i class="fa-solid fa-motorcycle fs-2 opacity-25"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-light text-primary border mb-1" style="width: fit-content; font-size: 0.72rem;"><?= htmlspecialchars($rel['marca_nombre'] ?? '') ?></span>
                                <a href="detalle_producto.php?id=<?= $rel['id_repuesto'] ?>" class="text-dark fw-semibold text-decoration-none small text-truncate d-block mb-2" title="<?= htmlspecialchars($rel['nombre']) ?>">
                                    <?= htmlspecialchars($rel['nombre']) ?>
                                </a>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="fw-bold text-primary">$<?= number_format($rel['precio'], 0, ',', '.') ?></span>
                                    <a href="detalle_producto.php?id=<?= $rel['id_repuesto'] ?>" class="btn btn-sm btn-outline-primary rounded-circle" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function changeMainImage(thumb, src) {
        document.getElementById('mainGalleryImg').src = src;
        document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
    }

    function decrementQty() {
        const inp = document.getElementById('buyQuantity');
        let val = parseInt(inp.value) || 1;
        if (val > 1) {
            inp.value = val - 1;
        }
    }

    function incrementQty(max) {
        const inp = document.getElementById('buyQuantity');
        let val = parseInt(inp.value) || 1;
        if (val < max) {
            inp.value = val + 1;
        }
    }

    const idRepuesto = <?= $id_repuesto ?>;

    document.getElementById('btnAddToCart')?.addEventListener('click', function() {
        const qty = parseInt(document.getElementById('buyQuantity').value) || 1;
        const btn = this;
        btn.disabled = true;

        fetch('../../controllers/cliente_pedido_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'agregar_carrito', id_repuesto: idRepuesto, cantidad: qty })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.status === 'success') {
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
                Swal.fire({ icon: 'warning', title: 'Atención', text: data.message });
            }
        })
        .catch(err => {
            btn.disabled = false;
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
        });
    });

    document.getElementById('btnBuyNow')?.addEventListener('click', function() {
        const qty = parseInt(document.getElementById('buyQuantity').value) || 1;
        const btn = this;
        btn.disabled = true;

        fetch('../../controllers/cliente_pedido_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'agregar_carrito', id_repuesto: idRepuesto, cantidad: qty })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'checkout.php';
            } else {
                btn.disabled = false;
                Swal.fire({ icon: 'warning', title: 'Atención', text: data.message });
            }
        })
        .catch(err => {
            btn.disabled = false;
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
        });
    });
    </script>
</body>
</html>
