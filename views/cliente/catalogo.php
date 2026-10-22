<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/MarcaModel.php';
require_once '../../models/CategoriaModel.php';
require_once '../../models/RepuestoModel.php';

$marcaModel = new MarcaModel($conn);
$catModel = new CategoriaModel($conn);
$repModel = new RepuestoModel($conn);

$marcas = $marcaModel->getAll();
$categorias = $catModel->getAll();
$modelos = $repModel->getModelos();

// Filtros
$search = trim($_GET['search'] ?? '');
$filterMarca = isset($_GET['marca']) ? (int)$_GET['marca'] : 0;
$filterCategoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$filterModelo = trim($_GET['modelo'] ?? '');
$filterStock = isset($_GET['solo_stock']) ? (int)$_GET['solo_stock'] : 0;

// Paginación
$porPagina = 12;
$paginaActual = max(1, (int)($_GET['page'] ?? 1));
$offset = ($paginaActual - 1) * $porPagina;

// Construir consulta base
$where = "WHERE (r.estado = 'Activo' OR r.estado = 'ACTIVO')";
$params = [];
$types = "";

if (!empty($search)) {
    $searchTerms = array_filter(explode(' ', $search));
    foreach ($searchTerms as $term) {
        $where .= " AND (r.nombre LIKE ? OR r.codigo_barras LIKE ? OR r.codigo_interno LIKE ? OR r.compatibilidades LIKE ? OR r.referencia_fabricante LIKE ? OR m.nombre LIKE ?)";
        $like = "%$term%";
        array_push($params, $like, $like, $like, $like, $like, $like);
        $types .= "ssssss";
    }
}

if ($filterMarca > 0) {
    $where .= " AND r.id_marca = ?";
    $params[] = $filterMarca;
    $types .= "i";
}

if ($filterCategoria > 0) {
    $where .= " AND r.id_categoria = ?";
    $params[] = $filterCategoria;
    $types .= "i";
}

if (!empty($filterModelo)) {
    $where .= " AND r.compatibilidades LIKE ?";
    $params[] = "%$filterModelo%";
    $types .= "s";
}

if ($filterStock === 1) {
    $where .= " AND r.stock > 0";
}

// 1. Contar total para paginación
$countQuery = "SELECT COUNT(r.id_repuesto) as total 
               FROM REPUESTO r 
               LEFT JOIN MARCA m ON r.id_marca = m.id_marca 
               $where";

$stmtCount = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalRepuestos = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
$stmtCount->close();

$totalPaginas = max(1, ceil($totalRepuestos / $porPagina));

// 2. Consulta de productos paginados
$query = "SELECT r.*, m.nombre as marca_nombre, c.nombre as categoria_nombre, 
                 i.ruta_archivo as imagen_url 
          FROM REPUESTO r 
          LEFT JOIN MARCA m ON r.id_marca = m.id_marca 
          LEFT JOIN CATEGORIA c ON r.id_categoria = c.id_categoria 
          LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
          LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen 
          $where 
          ORDER BY r.stock > 0 DESC, r.id_repuesto DESC 
          LIMIT ? OFFSET ?";

$paramsPagination = $params;
$typesPagination = $types . "ii";
$paramsPagination[] = $porPagina;
$paramsPagination[] = $offset;

$stmt = $conn->prepare($query);
$stmt->bind_param($typesPagination, ...$paramsPagination);
$stmt->execute();
$resRep = $stmt->get_result();
$repuestos = [];
while ($r = $resRep->fetch_assoc()) {
    $repuestos[] = $r;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Repuestos - Motoverify</title>
    
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

        .filter-card { background: #ffffff; border-radius: 14px; padding: 22px; border: 1px solid #f0f1f3; box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 25px; }

        .part-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            border: 1px solid #f0f1f3;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s, transform 0.2s;
            height: 100%;
        }
        .part-card:hover { box-shadow: 0 10px 24px rgba(0,0,0,0.06); transform: translateY(-3px); }
        .part-img { width: 100%; height: 160px; object-fit: contain; margin-bottom: 12px; }
        .part-code { font-weight: 700; color: #111; font-size: 0.95rem; }
        .part-name { font-size: 0.92rem; color: #374151; font-weight: 500; line-height: 1.35; min-height: 42px; margin-bottom: 6px; }
        .part-brand { color: #2563eb; font-weight: 600; font-size: 0.85rem; }
        .part-price { font-size: 1.3rem; font-weight: 800; color: #004ba8; }
        
        .stock-badge-in { background-color: #dcfce7; color: #15803d; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px; font-weight: 600; }
        .stock-badge-out { background-color: #fee2e2; color: #b91c1c; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px; font-weight: 600; }

        .pagination-container .page-link { color: #004ba8; border-radius: 8px; margin: 0 3px; font-weight: 600; }
        .pagination-container .page-item.active .page-link { background-color: #004ba8; border-color: #004ba8; color: white; }
    </style>
</head>
<body>

    <?php require_once 'topbar.php'; ?>
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Título y breadcrumb -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h2 class="fw-bold mb-1 text-dark">Catálogo de Repuestos</h2>
                <p class="text-muted mb-0">Explora todos los repuestos originales disponibles para tu motocicleta.</p>
            </div>
            <a href="carrito.php" class="btn btn-outline-primary fw-semibold rounded-pill px-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-cart-shopping"></i> Ver Mi Carrito 
                <span class="badge bg-primary rounded-pill cart-badge-count <?= $carrito_total_items > 0 ? '' : 'd-none' ?>">
                    <?= $carrito_total_items ?>
                </span>
            </a>
        </div>

        <!-- Filtros Interactivos -->
        <div class="filter-card">
            <form method="GET" action="catalogo.php" id="filterForm">
                <div class="row g-3 align-items-end">
                    <!-- Búsqueda textual -->
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-muted">Buscar producto o referencia</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Ej: Bujía, Pastillas, Pulsar 200..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>

                    <!-- Filtro Marca -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Marca</label>
                        <select name="marca" class="form-select">
                            <option value="0">Todas las marcas</option>
                            <?php foreach ($marcas as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $filterMarca == $m['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro Categoría -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="0">Todas las categorías</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $filterCategoria == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro Modelo Moto -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Modelo de Moto</label>
                        <select name="modelo" class="form-select">
                            <option value="">Todos los modelos</option>
                            <?php foreach ($modelos as $mo): ?>
                                <option value="<?= htmlspecialchars($mo['modelo_moto']) ?>" <?= $filterModelo === $mo['modelo_moto'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mo['modelo_moto']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold rounded-3 d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>
                        <a href="catalogo.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </div>

                <!-- Checkbox solo con stock -->
                <div class="mt-3 d-flex align-items-center gap-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="switchStock" name="solo_stock" value="1" <?= $filterStock === 1 ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()">
                        <label class="form-check-label small fw-semibold text-muted" for="switchStock">Mostrar únicamente productos con stock disponible</label>
                    </div>
                    <span class="text-muted small ms-auto">Mostrando <strong><?= count($repuestos) ?></strong> de <strong><?= $totalRepuestos ?></strong> resultados</span>
                </div>
            </form>
        </div>

        <!-- Grid de Productos -->
        <div class="row g-4 mb-4">
            <?php if (empty($repuestos)): ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-white rounded-4 p-5 border text-center">
                        <i class="fa-solid fa-magnifying-glass fs-1 text-primary opacity-50 mb-3"></i>
                        <h4 class="fw-bold text-dark">No se encontraron repuestos</h4>
                        <p class="text-muted">Intenta ajustando los términos de búsqueda o limpiando los filtros seleccionados.</p>
                        <a href="catalogo.php" class="btn btn-primary rounded-pill px-4 mt-2">
                            <i class="fa-solid fa-rotate-left me-1"></i> Restablecer Catálogo
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($repuestos as $r): 
                    $tieneStock = ($r['stock'] > 0);
                ?>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="part-card shadow-sm">
                            <!-- Foto del producto -->
                            <div class="text-center p-3 mb-2 bg-light rounded-3 position-relative overflow-hidden">
                                <?php if (!empty($r['imagen_url'])): ?>
                                    <img src="../../<?= htmlspecialchars($r['imagen_url']); ?>" alt="<?= htmlspecialchars($r['nombre']); ?>" class="part-img">
                                <?php else: ?>
                                    <div class="part-img d-flex align-items-center justify-content-center text-secondary">
                                        <i class="fa-solid fa-motorcycle fs-1 opacity-25"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($r['categoria_nombre'])): ?>
                                    <span class="position-absolute top-0 start-0 m-2 badge bg-white text-dark shadow-sm border" style="font-size: 0.72rem;">
                                        <?= htmlspecialchars($r['categoria_nombre']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Cabecera de tarjeta -->
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="part-brand"><?= htmlspecialchars($r['marca_nombre'] ?? 'Genérico') ?></span>
                                <?php if ($tieneStock): ?>
                                    <span class="stock-badge-in">
                                        <i class="fa-solid fa-circle-check me-1"></i> Stock: <?= $r['stock'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="stock-badge-out">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> Agotado
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="part-code"><?= htmlspecialchars($r['codigo_interno']) ?></div>
                            <h5 class="part-name" title="<?= htmlspecialchars($r['nombre']) ?>">
                                <?= htmlspecialchars($r['nombre']) ?>
                            </h5>

                            <?php if (!empty($r['compatibilidades'])): ?>
                                <p class="text-muted small mb-3 text-truncate" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-wrench me-1"></i> <?= htmlspecialchars($r['compatibilidades']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Footer de tarjeta con Precio y Botón de Carrito -->
                            <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                                <div class="part-price">
                                    $<?= number_format($r['precio'], 0, ',', '.') ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="detalle_producto.php?id=<?= $r['id_repuesto'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2" title="Ver ficha técnica completa">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </a>
                                    <?php if ($tieneStock): ?>
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 d-flex align-items-center gap-1 btn-add-cart" data-id="<?= $r['id_repuesto'] ?>">
                                            <i class="fa-solid fa-cart-plus"></i> Añadir
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3 opacity-50" disabled>
                                            Agotado
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
            <div class="d-flex justify-content-center my-4 pagination-container">
                <nav>
                    <ul class="pagination">
                        <?php if ($paginaActual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $paginaActual - 1])) ?>">
                                    <i class="fa-solid fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPaginas || ($i >= $paginaActual - 2 && $i <= $paginaActual + 2)): ?>
                                <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php elseif ($i == $paginaActual - 3 || $i == $paginaActual + 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($paginaActual < $totalPaginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $paginaActual + 1])) ?>">
                                    Siguiente <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
                this.disabled = false;
                this.innerHTML = originalHTML;
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
            });
        });
    });
    </script>
</body>
</html>
