<?php
session_start();
$is_cliente_auth = isset($_SESSION['user_id']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'Cliente';

// Si nos llegan filtros específicos de búsqueda o repuestos por query param, redirigir a repuestos.php
if (!empty($_GET['categoria']) || !empty($_GET['search']) || !empty($_GET['marca']) || !empty($_GET['modelo'])) {
    header('Location: repuestos.php?' . http_build_query($_GET));
    exit;
}

require_once '../../config/database.php';
require_once '../../models/CategoriaModel.php';
require_once '../../models/MarcaModel.php';
require_once '../../models/RepuestoModel.php';

$catModel = new CategoriaModel($conn);
$marcaModel = new MarcaModel($conn);
$repModel = new RepuestoModel($conn);

// Traer todas las categorías con su conteo de repuestos activos
$categorias = [];
try {
    $query = "SELECT c.*, i.ruta_archivo as imagen_url,
                     (SELECT COUNT(*) FROM REPUESTO r WHERE r.id_categoria = c.id_categoria AND (r.estado = 'Activo' OR r.estado = 'ACTIVO')) as total_repuestos
              FROM CATEGORIA c 
              LEFT JOIN CATEGORIA_IMAGEN ci ON c.id_categoria = ci.id_categoria AND ci.imagen_principal = 1
              LEFT JOIN IMAGEN i ON ci.id_imagen = i.id_imagen 
              ORDER BY c.nombre ASC";
    $resCategorias = $conn->query($query);
    if ($resCategorias) {
        while ($c = $resCategorias->fetch_assoc()) {
            $categorias[] = $c;
        }
    }
} catch (Exception $e) {}

$defaultCats = [
    ['nombre' => 'Sistema de motor',         'desc' => 'Pistón, cilindro, culata, válvulas y más',         'icono' => 'fa-solid fa-gears',             'color' => '#1e40af'],
    ['nombre' => 'Sistema de transmisión',   'desc' => 'Embrague, caja de cambios, transmisión',           'icono' => 'fa-solid fa-arrows-spin',       'color' => '#059669'],
    ['nombre' => 'Sistema de combustible',   'desc' => 'Carburador, inyección, bomba de combustible',      'icono' => 'fa-solid fa-gas-pump',          'color' => '#d97706'],
    ['nombre' => 'Sistema eléctrico',        'desc' => 'Batería, encendido, luces, sensores',              'icono' => 'fa-solid fa-bolt',              'color' => '#7c3aed'],
    ['nombre' => 'Sistema de frenos',        'desc' => 'Freno delantero, freno trasero, ABS',              'icono' => 'fa-solid fa-circle-stop',       'color' => '#dc2626'],
    ['nombre' => 'Suspensión',               'desc' => 'Horquilla delantera, amortiguador trasero',        'icono' => 'fa-solid fa-arrows-up-down',    'color' => '#0891b2'],
    ['nombre' => 'Chasis y estructura',      'desc' => 'Cuadro, basculante, soportes',                    'icono' => 'fa-solid fa-screwdriver-wrench', 'color' => '#be185d'],
    ['nombre' => 'Ruedas y neumáticos',      'desc' => 'Rines, llantas, cámaras, válvulas',               'icono' => 'fa-solid fa-circle-notch',      'color' => '#4338ca'],
    ['nombre' => 'Carrocería y accesorios',  'desc' => 'Carenados, espejos, direccionales, accesorios',   'icono' => 'fa-solid fa-motorcycle',        'color' => '#9333ea'],
];

$carrito_total_items = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $it) {
        $carrito_total_items += (int)($it['cantidad'] ?? 1);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Catálogo de Categorías por Ubicación</title>
    
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

        /* Topbar Global */
        .topbar-global { height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 85px; width: auto; object-fit: contain; }
        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }

        /* Sidebar */
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.02rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.15rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }

        /* Main Content */
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); display: flex; flex-direction: column; background-color: #f8f9fc; }

        /* Hero Banner */
        .hero-banner { background: linear-gradient(135deg, #0640aa 0%, #1e5abf 100%); color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 40px; top: 25%; height: 50%; width: 4px; background-color: #60a5fa; border-radius: 4px; }
        .hero-gears { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); pointer-events: none; }

        /* Category List */
        .section-title { font-weight: 700; font-size: 1.1rem; color: #1e3a8a; margin-bottom: 20px; padding-left: 10px; border-left: 4px solid #004ba8; border-radius: 2px; }

        .cat-item {
            display: flex; align-items: center; gap: 14px; padding: 12px 14px;
            border-radius: 14px; transition: all 0.2s; cursor: pointer;
            margin-bottom: 10px; background: #fff; border: 1px solid #f1f5f9;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            text-decoration: none; color: inherit;
        }
        .cat-item:hover { background-color: #f0f4ff; border-color: #bfdbfe; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,75,168,0.08); }

        .cat-icon-box {
            width: 48px; height: 48px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-size: 1.25rem;
            color: white; flex-shrink: 0;
        }
        .cat-item-info { flex: 1; min-width: 0; }
        .cat-item-title { font-weight: 700; font-size: 0.95rem; color: #111; margin: 0; }
        .cat-item-desc { font-size: 0.8rem; color: #6b7280; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Motorcycle diagram area */
        .moto-diagram-container {
            background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px;
            padding: 24px; position: relative; min-height: 480px; box-shadow: 0 4px 14px rgba(0,0,0,0.03);
        }
        .moto-image-wrapper {
            position: relative; width: 100%; height: 420px;
            display: flex; align-items: center; justify-content: center;
        }
        .moto-image-wrapper img {
            max-width: 88%; max-height: 100%; object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.1));
        }

        /* Map labels with connector dots */
        .map-label {
            position: absolute; background: white; border: 1.5px solid #3b82f6;
            color: #1e40af; font-size: 0.76rem; font-weight: 700; padding: 6px 12px;
            border-radius: 8px; white-space: nowrap; z-index: 5;
            box-shadow: 0 3px 10px rgba(59,130,246,0.18);
            cursor: pointer; transition: all 0.2s ease;
            text-decoration: none;
        }
        .map-label:hover {
            background: #004ba8; color: white; border-color: #004ba8;
            transform: translateY(-3px) scale(1.04);
            box-shadow: 0 6px 16px rgba(0,75,168,0.25);
        }
        .map-dot {
            position: absolute; width: 10px; height: 10px; background: #3b82f6;
            border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #3b82f6;
            z-index: 6; animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { transform: scale(1.15); box-shadow: 0 0 0 6px rgba(59, 130, 246, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }

        /* Info alert */
        .info-box {
            background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 16px 20px; display: flex; align-items: flex-start; gap: 14px; margin-top: 20px;
        }
        .info-box i { color: #2563eb; font-size: 1.25rem; margin-top: 2px; flex-shrink: 0; }
        .info-box h6 { font-weight: 700; margin-bottom: 2px; font-size: 0.95rem; color: #1e3a8a; }
        /* Category Pagination */
        .cat-page-num {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; font-size: 0.82rem; font-weight: 700;
            cursor: pointer; border: 1px solid #e2e8f0; background: #fff;
            color: #4b5563; transition: all 0.15s;
        }
        .cat-page-num.active {
            background: #004ba8; color: #fff; border-color: #004ba8;
            box-shadow: 0 2px 6px rgba(0,75,168,0.25);
        }
        .cat-page-num:hover:not(.active) {
            background: #f1f5f9; color: #004ba8;
        }
        @keyframes fadeInCat {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .moto-image-wrapper { height: 320px; }
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

            <?php if (!$is_cliente_auth): ?>
                <!-- Banner informativo para usuarios invitados / sin sesión -->
                <div class="alert alert-info border-0 shadow-sm d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3 rounded-4 mb-4 gap-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="fa-solid fa-magnifying-glass fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-primary mb-1">Catálogo de Categorías Abierto para Visitantes</h6>
                            <p class="text-secondary small mb-0">Explora las partes de la motocicleta y consulta qué repuestos pertenecen a cada sistema técnico sin necesidad de iniciar sesión.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <a href="../../public/index.php?register=1" class="btn btn-primary btn-sm fw-semibold rounded-pill px-3 py-2 shadow-sm" style="background: #004ba8; border: none;">
                            <i class="fa-solid fa-user-plus me-1"></i> Registrarse
                        </a>
                        <a href="../../public/index.php?login=1" class="btn btn-outline-primary btn-sm fw-semibold rounded-pill px-3 py-2">
                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Iniciar Sesión
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Hero Banner -->
            <div class="hero-banner rounded-4 p-4 ps-5 mb-4 d-flex flex-wrap align-items-center justify-content-between shadow-sm gap-3">
                <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                    <h2 class="fw-bold mb-2 d-flex align-items-center gap-3">
                        <i class="fa-solid fa-book-open" style="font-size: 1.4rem; opacity: .9;"></i>
                        Catálogo de Repuestos
                    </h2>
                    <p class="mb-0 opacity-75" style="font-size: 1rem;">Consulta las categorías de repuestos clasificadas por su ubicación en la motocicleta.</p>
                </div>
                <div class="d-flex align-items-center gap-2" style="z-index: 2;">
                    <a href="repuestos.php" class="btn btn-light fw-bold text-primary rounded-pill px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                        <i class="fa-solid fa-puzzle-piece"></i> Ver Todos los Repuestos
                    </a>
                </div>
                <div class="hero-gears d-none d-lg-block">
                    <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                    <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
                </div>
            </div>

            <!-- Main Two-Column Layout -->
            <div class="row g-4">

                <!-- Left Column: Categories -->
                <div class="col-12 col-xl-5 col-xxl-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="section-title mb-0">Categorías por ubicación</h5>
                        <span class="badge bg-white text-muted border px-2 py-1 small" id="catTotalBadge"><?= count($categorias) ?> registradas</span>
                    </div>

                    <!-- Buscador rápido dentro de categorías -->
                    <div class="input-group input-group-sm mb-3 shadow-sm rounded-3 overflow-hidden border">
                        <span class="input-group-text bg-white border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="catFilterInput" class="form-control border-0 py-2" placeholder="Filtrar categoría rápida..." onkeyup="filterCategories()">
                        <button class="btn btn-white border-0 text-muted" type="button" onclick="document.getElementById('catFilterInput').value=''; filterCategories();" title="Limpiar"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <div id="catItemsContainer">
                        <?php if (empty($categorias)): ?>
                            <!-- Categorías iniciales por defecto cuando la BD está vacía -->
                            <?php foreach ($defaultCats as $dc): ?>
                            <div class="cat-item" onclick="searchCategory('<?= addslashes($dc['nombre']) ?>')">
                                <div class="cat-icon-box" style="background-color: <?= $dc['color']; ?>;">
                                    <i class="<?= $dc['icono']; ?>"></i>
                                </div>
                                <div class="cat-item-info">
                                    <p class="cat-item-title"><?= $dc['nombre']; ?></p>
                                    <p class="cat-item-desc"><?= $dc['desc']; ?></p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-muted opacity-50 ms-2"></i>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php
                            $palette = ['#1e40af','#059669','#d97706','#7c3aed','#dc2626','#0891b2','#be185d','#4338ca','#9333ea'];
                            $idx = 0;
                            foreach ($categorias as $cat):
                                $bgColor = $palette[$idx % count($palette)];
                                $idx++;
                                $countRep = (int)($cat['total_repuestos'] ?? 0);
                            ?>
                            <a href="repuestos.php?categoria=<?= $cat['id_categoria']; ?>" class="cat-item" title="Ver repuestos de <?= htmlspecialchars($cat['nombre']) ?>">
                                <div class="cat-icon-box" style="background-color: <?= $bgColor; ?>; overflow: hidden; padding: 6px;">
                                    <?php if(!empty($cat['imagen_url'])): ?>
                                        <img src="../../<?= htmlspecialchars($cat['imagen_url']); ?>" alt="Icono" style="width: 100%; height: 100%; object-fit: contain;">
                                    <?php else: ?>
                                        <i class="fa-solid fa-cube"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="cat-item-info ms-2">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <p class="cat-item-title mb-0"><?= htmlspecialchars($cat['nombre']); ?></p>
                                        <span class="badge rounded-pill bg-light text-secondary border small" style="font-size: 0.72rem;">
                                            <?= $countRep ?> repuesto<?= $countRep !== 1 ? 's' : '' ?>
                                        </span>
                                    </div>
                                    <p class="cat-item-desc mb-0 mt-1" title="<?= htmlspecialchars($cat['descripcion'] ?? ''); ?>">
                                        <?= htmlspecialchars($cat['descripcion'] ?? 'Repuestos de alta calidad y rendimiento.'); ?>
                                    </p>
                                </div>
                                <i class="fa-solid fa-arrow-right text-primary opacity-75 ms-2"></i>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Mensaje si no hay coincidencias con el buscador rápido -->
                    <div id="catNoMatch" class="text-center py-4 bg-white rounded-3 border d-none">
                        <i class="fa-solid fa-box-open text-muted fs-3 mb-2"></i>
                        <p class="text-muted small mb-0">No se encontraron categorías con ese criterio.</p>
                    </div>

                    <!-- Paginación de Categorías -->
                    <div class="cat-pagination-wrapper mt-3">
                        <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded-3 border shadow-sm">
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" id="catPrevBtn" onclick="changeCatPage(-1)" disabled style="font-size: 0.8rem;">
                                <i class="fa-solid fa-chevron-left me-1"></i> Anterior
                            </button>
                            <div id="catPageNumbers" class="d-flex align-items-center gap-1">
                                <!-- Botones generados por JS -->
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" id="catNextBtn" onclick="changeCatPage(1)" style="font-size: 0.8rem;">
                                Siguiente <i class="fa-solid fa-chevron-right ms-1"></i>
                            </button>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted fw-semibold" id="catPageInfo" style="font-size: 0.78rem;">Página 1 de 2</small>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Motorcycle Diagram -->
                <div class="col-12 col-xl-7 col-xxl-8">
                    <h5 class="section-title">Ubicación en la motocicleta</h5>

                    <div class="moto-diagram-container">
                        <div class="moto-image-wrapper">
                            <img src="../../public/assets/images/motorcycle_diagram.png" alt="Diagrama de motocicleta" onerror="this.src='https://via.placeholder.com/700x400?text=Motocicleta';">

                            <!-- Labels positioned around motorcycle -->
                            <div class="map-label" style="top: 8%; left: 15%;" onclick="searchCategory('Sistema de combustible')">
                                <i class="fa-solid fa-gas-pump me-1"></i> Sistema de combustible
                            </div>
                            <div class="map-dot" style="top: 28%; left: 38%;"></div>

                            <div class="map-label" style="top: 8%; right: 8%;" onclick="searchCategory('Sistema de motor')">
                                <i class="fa-solid fa-gears me-1"></i> Sistema de motor
                            </div>
                            <div class="map-dot" style="top: 32%; right: 30%;"></div>

                            <div class="map-label" style="top: 38%; left: 5%;" onclick="searchCategory('Sistema eléctrico')">
                                <i class="fa-solid fa-bolt me-1"></i> Sistema eléctrico
                            </div>
                            <div class="map-dot" style="top: 42%; left: 28%;"></div>

                            <div class="map-label" style="top: 38%; right: 0%;" onclick="searchCategory('Suspensión')">
                                <i class="fa-solid fa-arrows-up-down me-1"></i> Suspensión
                            </div>
                            <div class="map-dot" style="top: 45%; right: 18%;"></div>

                            <div class="map-label" style="top: 62%; left: 8%;" onclick="searchCategory('Sistema de frenos')">
                                <i class="fa-solid fa-circle-stop me-1"></i> Sistema de frenos
                            </div>
                            <div class="map-dot" style="top: 60%; left: 30%;"></div>

                            <div class="map-label" style="top: 62%; right: 0%;" onclick="searchCategory('Sistema de transmisión')">
                                <i class="fa-solid fa-arrows-spin me-1"></i> Sistema de transmisión
                            </div>
                            <div class="map-dot" style="top: 58%; right: 22%;"></div>

                            <div class="map-label" style="bottom: 12%; left: 15%;" onclick="searchCategory('Ruedas y neumáticos')">
                                <i class="fa-solid fa-circle-notch me-1"></i> Ruedas y neumáticos
                            </div>
                            <div class="map-dot" style="bottom: 18%; left: 32%;"></div>

                            <div class="map-label" style="bottom: 5%; left: 38%;" onclick="searchCategory('Carrocería y accesorios')">
                                <i class="fa-solid fa-motorcycle me-1"></i> Carrocería y accesorios
                            </div>

                            <div class="map-label" style="bottom: 12%; right: 5%;" onclick="searchCategory('Chasis y estructura')">
                                <i class="fa-solid fa-screwdriver-wrench me-1"></i> Chasis y estructura
                            </div>
                            <div class="map-dot" style="bottom: 22%; right: 25%;"></div>
                        </div>

                        <div class="info-box">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                <h6>Información para Clientes</h6>
                                <p>Selecciona una categoría o pulsa sobre la parte correspondiente en la motocicleta para ver los repuestos disponibles, especificaciones técnicas y adquirirlos en línea.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts necesarios -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function searchCategory(name) {
            const categoryMap = {
                <?php foreach($categorias as $c): ?>
                "<?= strtolower(trim($c['nombre'])); ?>": <?= $c['id_categoria']; ?>,
                <?php endforeach; ?>
            };
            
            let key = name.toLowerCase().trim();
            if (categoryMap[key]) {
                window.location.href = 'repuestos.php?categoria=' + categoryMap[key];
            } else {
                window.location.href = 'repuestos.php?search=' + encodeURIComponent(name);
            }
        }

        /* ===== PAGINACIÓN Y FILTRADO INTERACTIVO DE CATEGORÍAS ===== */
        const CATS_PER_PAGE = 5;
        let currentCatPage = 1;
        let filteredCatItems = [];

        function initCatPagination() {
            const allItems = Array.from(document.querySelectorAll('#catItemsContainer .cat-item'));
            filteredCatItems = allItems;
            renderCatPage();
        }

        function filterCategories() {
            const term = (document.getElementById('catFilterInput')?.value || '').toLowerCase().trim();
            const allItems = Array.from(document.querySelectorAll('#catItemsContainer .cat-item'));
            const noMatch = document.getElementById('catNoMatch');
            const pagWrapper = document.querySelector('.cat-pagination-wrapper');
            
            if (!term) {
                filteredCatItems = allItems;
            } else {
                filteredCatItems = allItems.filter(item => {
                    const title = item.querySelector('.cat-item-title')?.textContent.toLowerCase() || '';
                    const desc = item.querySelector('.cat-item-desc')?.textContent.toLowerCase() || '';
                    return title.includes(term) || desc.includes(term);
                });
            }

            if (noMatch) {
                if (filteredCatItems.length === 0) {
                    noMatch.classList.remove('d-none');
                    if (pagWrapper) pagWrapper.style.display = 'none';
                } else {
                    noMatch.classList.add('d-none');
                    if (pagWrapper) pagWrapper.style.display = 'block';
                }
            }

            currentCatPage = 1;
            renderCatPage();
        }

        function renderCatPage() {
            const allItems = Array.from(document.querySelectorAll('#catItemsContainer .cat-item'));
            allItems.forEach(el => el.style.display = 'none');
            
            const totalItems = filteredCatItems.length;
            const totalPages = Math.max(1, Math.ceil(totalItems / CATS_PER_PAGE));
            if (currentCatPage > totalPages) currentCatPage = totalPages;
            if (currentCatPage < 1) currentCatPage = 1;
            
            const startIdx = (currentCatPage - 1) * CATS_PER_PAGE;
            const endIdx = startIdx + CATS_PER_PAGE;
            
            filteredCatItems.slice(startIdx, endIdx).forEach(el => {
                el.style.display = 'flex';
                el.style.animation = 'fadeInCat 0.25s ease forwards';
            });
            
            const prevBtn = document.getElementById('catPrevBtn');
            const nextBtn = document.getElementById('catNextBtn');
            if (prevBtn) prevBtn.disabled = (currentCatPage <= 1);
            if (nextBtn) nextBtn.disabled = (currentCatPage >= totalPages);
            
            const pageInfo = document.getElementById('catPageInfo');
            if (pageInfo) {
                pageInfo.textContent = `Mostrando página ${currentCatPage} de ${totalPages} (${totalItems} categoría${totalItems !== 1 ? 's' : ''})`;
            }
            
            const pageNumbers = document.getElementById('catPageNumbers');
            if (pageNumbers) {
                pageNumbers.innerHTML = '';
                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `cat-page-num ${i === currentCatPage ? 'active' : ''}`;
                    btn.textContent = i;
                    btn.onclick = () => { currentCatPage = i; renderCatPage(); };
                    pageNumbers.appendChild(btn);
                }
            }
        }

        function changeCatPage(dir) {
            currentCatPage += dir;
            renderCatPage();
        }

        document.addEventListener('DOMContentLoaded', initCatPagination);
    </script>
</body>
</html>
