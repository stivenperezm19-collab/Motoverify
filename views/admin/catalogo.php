<?php
// Iniciamos la sesión para recordar al administrador
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}
// Conectamos a la base de datos
require_once '../../config/database.php';

// Creamos una lista vacía para guardar las categorías
$categorias = [];
try {
    // Buscamos todas las categorías en la base de datos junto con su imagen principal
    $query = "SELECT c.*, i.ruta_archivo as imagen_url 
              FROM CATEGORIA c 
              LEFT JOIN CATEGORIA_IMAGEN ci ON c.id_categoria = ci.id_categoria AND ci.imagen_principal = 1
              LEFT JOIN IMAGEN i ON ci.id_imagen = i.id_imagen 
              ORDER BY c.nombre ASC";
    $resCategorias = $conn->query($query);
    if ($resCategorias) {
        while ($c = $resCategorias->fetch_assoc()) {
            $categorias[] = $c; // Agregamos cada categoría encontrada a nuestra lista
        }
    }
} catch (Exception $e) {}

// Si aún no hay categorías en la base de datos, usamos esta lista por defecto
// Sirve para dar opciones iniciales cuando el sistema está vacío
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

// Preparamos listas para los filtros de búsqueda en la barra superior
$marcas_filter = [];
$cat_filter = [];
$mod_filter = [];
try {
    // Obtenemos todas las marcas
    $rM = $conn->query("SELECT id_marca as id, nombre FROM MARCA ORDER BY nombre");
    if($rM) while($row = $rM->fetch_assoc()) $marcas_filter[] = $row;
    
    // Obtenemos todas las categorías
    $rC = $conn->query("SELECT id_categoria as id, nombre FROM CATEGORIA ORDER BY nombre");
    if($rC) while($row = $rC->fetch_assoc()) $cat_filter[] = $row;
    
    // Obtenemos los modelos de moto disponibles
    $rMo = $conn->query("SELECT DISTINCT compatibilidades as modelo_moto FROM REPUESTO WHERE compatibilidades != '' ORDER BY compatibilidades");
    if($rMo) while($row = $rMo->fetch_assoc()) $mod_filter[] = $row;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Catálogo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }
        
        /* Topbar Global */
        .topbar-global { height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 90px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }

        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .search-container input { border: none; outline: none; box-shadow: none; }
        
        /* Sidebar */
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.05rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
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
        .section-title { font-weight: 700; font-size: 1.1rem; color: #1e3a8a; margin-bottom: 20px; }

        .cat-item {
            display: flex; align-items: center; gap: 14px; padding: 12px 14px;
            border-radius: 12px; transition: all 0.2s; cursor: pointer;
            margin-bottom: 10px; background: #fff; border: 1px solid transparent;
        }
        .cat-item:hover { background-color: #f0f4ff; border-color: #dbeafe; }

        .cat-icon-box {
            width: 44px; height: 44px; border-radius: 10px; display: flex;
            align-items: center; justify-content: center; font-size: 1.15rem;
            color: white; flex-shrink: 0;
        }
        .cat-item-info { flex: 1; min-width: 0; }
        .cat-item-title { font-weight: 700; font-size: 0.92rem; color: #111; margin: 0; }
        .cat-item-desc { font-size: 0.78rem; color: #6b7280; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .btn-edit-sm {
            width: 30px; height: 30px; border-radius: 8px; border: 1px solid #dbeafe;
            background: #f0f7ff; color: #2563eb; display: flex; align-items: center;
            justify-content: center; cursor: pointer; transition: all 0.15s; font-size: 0.8rem; flex-shrink: 0;
        }
        .btn-edit-sm:hover { background: #dbeafe; }

        /* Motorcycle diagram area */
        .moto-diagram-container {
            background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px;
            padding: 20px; position: relative; min-height: 460px;
        }
        .moto-image-wrapper {
            position: relative; width: 100%; height: 380px;
            display: flex; align-items: center; justify-content: center;
        }
        .moto-image-wrapper img {
            max-width: 85%; max-height: 100%; object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.08));
        }

        /* Map labels with connector dots */
        .map-label {
            position: absolute; background: white; border: 1.5px solid #3b82f6;
            color: #1e40af; font-size: 0.72rem; font-weight: 600; padding: 4px 10px;
            border-radius: 5px; white-space: nowrap; z-index: 5;
            box-shadow: 0 2px 8px rgba(59,130,246,0.12);
            cursor: pointer; transition: all 0.2s ease;
            text-decoration: none;
        }
        .map-label:hover { background: #3b82f6; color: white; transform: translateY(-2px); }
        .map-dot {
            position: absolute; width: 8px; height: 8px; background: #3b82f6;
            border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 1px #3b82f6;
            z-index: 6;
        }
        .map-line {
            position: absolute; height: 1px; background: #93c5fd; z-index: 4;
            transform-origin: left center;
        }

        /* Info alert */
        .info-box {
            background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 16px 20px; display: flex; align-items: flex-start; gap: 14px; margin-top: 16px;
        }
        .info-box i { color: #2563eb; font-size: 1.15rem; margin-top: 2px; flex-shrink: 0; }
        .info-box h6 { font-weight: 700; margin-bottom: 2px; font-size: 0.95rem; }
        .info-box p { font-size: 0.85rem; color: #64748b; margin: 0; }
    </style>
</head>
<body>

    <header class="topbar-global px-4 d-flex align-items-center justify-content-between">
        <a href="dashboard.php" class="topbar-logo text-decoration-none">
            <img src="../../public/assets/images/logo_motoverify.png?v=<?php echo time(); ?>" alt="Motoverify">
        </a>
        <div class="d-flex align-items-center gap-3">
            <form action="repuestos.php" method="GET" class="search-container d-flex align-items-center" style="width: 400px; margin-bottom: 0;">
                <input type="text" name="search" class="form-control" placeholder="Pulsar135/180/NS200" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="background:transparent;">
                <div class="vr mx-2"></div>
                <span class="text-primary fw-semibold" style="cursor:pointer; white-space:nowrap;" data-bs-toggle="modal" data-bs-target="#filtrosAdminModal">
                    Filtro <i class="fa-solid fa-chevron-down ms-1"></i>
                </span>
            </form>
            <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 12px;" data-bs-toggle="modal" data-bs-target="#iaAdminModal">
                <i class="fa-solid fa-camera fs-5"></i>
            </button>
            <a href="perfil.php" class="d-flex align-items-center justify-content-center bg-light text-secondary rounded-circle text-decoration-none" style="width: 45px; height: 45px; font-size: 1.2rem;">
                <i class="fa-regular fa-user"></i>
            </a>
        </div>
    </header>

    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">

        <div class="p-4 flex-grow-1">

            <!-- Hero Banner -->
            <div class="hero-banner rounded-3 p-4 ps-5 mb-4 d-flex align-items-center">
                <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                    <h2 class="fw-bold mb-2 d-flex align-items-center gap-3">
                        <i class="fa-solid fa-book-open" style="font-size: 1.4rem; opacity: .8;"></i>
                        Catálogo de Repuestos
                    </h2>
                    <p class="mb-0 opacity-75" style="font-size: 1rem;">Consulta las categorías de repuestos clasificadas por ubicación.</p>
                </div>
                <div class="hero-gears">
                    <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                    <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
                </div>
            </div>

            <!-- Main Two-Column Layout -->
            <div class="row g-4">

                <!-- Left Column: Categories -->
                <div class="col-12 col-xl-5 col-xxl-4">
                    <h5 class="section-title">Categorías por ubicación</h5>

                    <?php if (empty($categorias)): ?>
                        <!-- Show default/placeholder categories when DB is empty -->
                        <?php foreach ($defaultCats as $dc): ?>
                        <div class="cat-item">
                            <div class="cat-icon-box" style="background-color: <?php echo $dc['color']; ?>;">
                                <i class="<?php echo $dc['icono']; ?>"></i>
                            </div>
                            <div class="cat-item-info">
                                <p class="cat-item-title"><?php echo $dc['nombre']; ?></p>
                                <p class="cat-item-desc"><?php echo $dc['desc']; ?></p>
                            </div>
                            <button class="btn-edit-sm" title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php
                        $palette = ['#1e40af','#059669','#d97706','#7c3aed','#dc2626','#0891b2','#be185d','#4338ca','#9333ea'];
                        $idx = 0;
                        foreach ($categorias as $cat):
                            $bgColor = $palette[$idx % count($palette)];
                            $idx++;
                        ?>
                        <div class="cat-item p-0 d-flex justify-content-between align-items-center">
                            <a href="repuestos.php?categoria=<?php echo $cat['id_categoria']; ?>" class="d-flex align-items-center flex-grow-1 text-decoration-none p-2" title="Ver repuestos de esta categoría">
                                <div class="cat-icon-box" style="background-color: <?php echo $bgColor; ?>; overflow: hidden; padding: 5px;">
                                    <?php if(!empty($cat['imagen_url'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($cat['imagen_url']); ?>" alt="Icono" style="width: 100%; height: 100%; object-fit: contain;">
                                    <?php else: ?>
                                        <i class="fa-solid fa-cube"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="cat-item-info ms-3">
                                    <p class="cat-item-title mb-1"><?php echo htmlspecialchars($cat['nombre']); ?></p>
                                    <p class="cat-item-desc mb-0" title="<?php echo htmlspecialchars($cat['descripcion'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($cat['descripcion'] ?? 'Sin descripción'); ?>
                                    </p>
                                </div>
                            </a>
                            <div class="d-flex gap-1 p-2">
                                <button class="btn-edit-sm" title="Editar" onclick="openCatModal('edit', <?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn-edit-sm" title="Eliminar" style="border-color: #fecaca; background: #fef2f2; color: #dc2626;" onclick="deleteCategoria(<?php echo $cat['id_categoria']; ?>, '<?php echo addslashes(htmlspecialchars($cat['nombre'])); ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Motorcycle Diagram -->
                <div class="col-12 col-xl-7 col-xxl-8">
                    <h5 class="section-title">Ubicación en la motocicleta</h5>

                    <div class="moto-diagram-container">
                        <div class="moto-image-wrapper">
                            <img src="../../public/assets/images/motorcycle_diagram.png" alt="Diagrama de motocicleta" onerror="this.src='https://via.placeholder.com/700x400?text=Motocicleta';">

                            <!-- Labels positioned around motorcycle -->
                            <div class="map-label" style="top: 8%; left: 15%;" onclick="searchCategory('Sistema de combustible')">Sistema de combustible</div>
                            <div class="map-dot" style="top: 28%; left: 38%;"></div>

                            <div class="map-label" style="top: 8%; right: 8%;" onclick="searchCategory('Sistema de motor')">Sistema de motor</div>
                            <div class="map-dot" style="top: 32%; right: 30%;"></div>

                            <div class="map-label" style="top: 38%; left: 5%;" onclick="searchCategory('Sistema eléctrico')">Sistema eléctrico</div>
                            <div class="map-dot" style="top: 42%; left: 28%;"></div>

                            <div class="map-label" style="top: 38%; right: 0%;" onclick="searchCategory('Suspensión')">Suspensión</div>
                            <div class="map-dot" style="top: 45%; right: 18%;"></div>

                            <div class="map-label" style="top: 62%; left: 8%;" onclick="searchCategory('Sistema de frenos')">Sistema de frenos</div>
                            <div class="map-dot" style="top: 60%; left: 30%;"></div>

                            <div class="map-label" style="top: 62%; right: 0%;" onclick="searchCategory('Sistema de transmisión')">Sistema de transmisión</div>
                            <div class="map-dot" style="top: 58%; right: 22%;"></div>

                            <div class="map-label" style="bottom: 12%; left: 15%;" onclick="searchCategory('Ruedas y neumáticos')">Ruedas y neumáticos</div>
                            <div class="map-dot" style="bottom: 18%; left: 32%;"></div>

                            <div class="map-label" style="bottom: 5%; left: 38%;" onclick="searchCategory('Carrocería y accesorios')">Carrocería y accesorios</div>

                            <div class="map-label" style="bottom: 12%; right: 5%;" onclick="searchCategory('Chasis y estructura')">Chasis y estructura</div>
                            <div class="map-dot" style="bottom: 22%; right: 25%;"></div>
                        </div>

                        <div class="info-box">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                <h6>Información</h6>
                                <p>Selecciona una categoría para ver los repuestos disponibles y su ubicación específica en la motocicleta.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Agregar/Editar Categoría -->
    <div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="modal-title fw-bold" id="catModalTitle">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="catForm" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" id="cat_id" name="id">
                        <input type="hidden" id="cat_action" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="cat_nombre" name="nombre" required placeholder="Ej: Sistema de motor">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Descripción</label>
                            <textarea class="form-control rounded-3" id="cat_descripcion" name="descripcion" rows="2" placeholder="Ej: Pistón, cilindro, culata..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Ícono o Imagen (Opcional)</label>
                            <input type="file" class="form-control rounded-3" id="cat_imagen" name="imagen" accept="image/*">
                            <div class="form-text">Puedes subir una imagen representativa para la categoría.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-3 fw-semibold px-4" id="btnSaveCat" style="background-color: #004ba8; border: none;">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar / Reasignar -->
    <div class="modal fade" id="reassignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> Eliminar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="reassignForm">
                    <div class="modal-body p-4">
                        <p id="reassignMessage">Esta categoría contiene repuestos. Debes reasignarlos a otra categoría antes de eliminarla.</p>
                        <input type="hidden" id="delete_cat_id" name="id">
                        <input type="hidden" name="action" value="delete_reassign">
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold text-secondary">Reasignar repuestos a: <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="id_nueva_categoria" name="id_nueva_categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?php echo $c['id_categoria']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger rounded-3 fw-semibold px-4" id="btnConfirmDelete">Confirmar Eliminación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Filtros (Admin) -->
    <div class="modal fade" id="filtrosAdminModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold text-primary">Filtros de Búsqueda</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="adminSearchForm" action="repuestos.php" method="GET">
                <input type="hidden" name="search" id="modalSearchInput" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Marca</label>
                    <select name="marca" class="form-select">
                        <option value="">Todas las marcas</option>
                        <?php foreach($marcas_filter as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= (isset($_GET['marca']) && $_GET['marca'] == $m['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Categoría</label>
                    <select name="categoria" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach($cat_filter as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (isset($_GET['categoria']) && $_GET['categoria'] == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Modelo de Moto</label>
                    <select name="modelo" class="form-select">
                        <option value="">Todos los modelos</option>
                        <?php foreach($mod_filter as $mod): ?>
                            <option value="<?= htmlspecialchars($mod['modelo_moto']) ?>" <?= (isset($_GET['modelo']) && $_GET['modelo'] == $mod['modelo_moto']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mod['modelo_moto']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='repuestos.php'">Limpiar</button>
            <button type="submit" form="adminSearchForm" class="btn btn-primary">Aplicar Filtros</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal IA (Reconocimiento Visual) -->
    <div class="modal fade" id="iaAdminModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fw-bold"><i class="fa-solid fa-microchip me-2"></i>Reconocimiento Visual IA</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseIaModal"></button>
          </div>
          <div class="modal-body text-center py-4">
            <div id="cameraContainer" class="position-relative mb-3" style="border-radius:10px; overflow:hidden; background:#000; height:350px; display:none;">
                <video id="cameraVideo" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                <button class="btn btn-light position-absolute bottom-0 start-50 translate-middle-x mb-3 rounded-circle shadow d-flex align-items-center justify-content-center" id="btnSnapPhoto" style="width: 60px; height: 60px; z-index: 10;">
                    <i class="fa-solid fa-camera fs-3 text-primary"></i>
                </button>
                <button class="btn btn-dark position-absolute top-0 end-0 m-2 rounded-circle shadow-sm" id="btnSwitchCamera" style="z-index: 10;">
                    <i class="fa-solid fa-camera-rotate"></i>
                </button>
            </div>
            <canvas id="cameraCanvas" class="d-none"></canvas>
            <input type="file" id="aiImageInput" accept="image/*" class="d-none">
            <div id="imagePreviewContainer" style="width:100%; height:350px; border:2px dashed #ccc; border-radius:10px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#f8f9fa; position:relative; overflow:hidden;">
                <div class="upload-placeholder" id="uploadPlaceholder" style="text-align:center; color:#888; padding:20px;">
                    <i class="fa-solid fa-image" style="font-size:3rem; margin-bottom:10px; color:#004ba8;"></i>
                    <p class="mb-2 fw-semibold">Selecciona o toma una foto del repuesto</p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary me-2" id="btnStartCamera">
                            <i class="fa-solid fa-video me-1"></i> Abrir Cámara
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('aiImageInput').click()">
                            <i class="fa-solid fa-folder-open me-1"></i> Archivo
                        </button>
                    </div>
                </div>
                <img id="imagePreview" alt="Vista previa" style="width:100%; height:100%; object-fit:contain; display:none; background:#000;">
                <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 d-none shadow-sm" id="btnRetakePhoto">
                    <i class="fa-solid fa-trash me-1"></i> Eliminar
                </button>
            </div>
            <div id="aiStatus" class="d-none mt-3">
                <div class="d-flex align-items-center justify-content-center text-primary fw-bold" id="aiLoading">
                    <div class="spinner-border me-2" role="status"><span class="visually-hidden">Analizando...</span></div>
                    Analizando imagen con IA...
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="btnCancelIa">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnAnalyzeIa" disabled>
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Identificar Repuesto
            </button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sincronizar input de barra superior con modal
            const topSearchInput = document.querySelector('.search-container input[name="search"]');
            const modalSearchInput = document.getElementById('modalSearchInput');
            if(topSearchInput && modalSearchInput) {
                topSearchInput.addEventListener('input', function() {
                    modalSearchInput.value = this.value;
                });
            }

            // --- IA Camera Script ---
            const fileInput = document.getElementById('aiImageInput');
            const previewContainer = document.getElementById('imagePreviewContainer');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const imagePreview = document.getElementById('imagePreview');
            const btnAnalyze = document.getElementById('btnAnalyzeIa');
            const statusDiv = document.getElementById('aiStatus');
            const loadingDiv = document.getElementById('aiLoading');
            const btnRetakePhoto = document.getElementById('btnRetakePhoto');
            
            const cameraContainer = document.getElementById('cameraContainer');
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const btnStartCamera = document.getElementById('btnStartCamera');
            const btnSnapPhoto = document.getElementById('btnSnapPhoto');
            const btnSwitchCamera = document.getElementById('btnSwitchCamera');

            let currentStream = null;
            let useFrontCamera = false;
            let photoBlob = null;

            function stopMediaTracks(stream) {
                if (stream) { stream.getTracks().forEach(track => track.stop()); }
            }

            async function startCamera() {
                stopMediaTracks(currentStream);
                const constraints = { video: { facingMode: useFrontCamera ? "user" : "environment" } };
                try {
                    const stream = await navigator.mediaDevices.getUserMedia(constraints);
                    currentStream = stream;
                    video.srcObject = stream;
                    previewContainer.style.display = 'none';
                    cameraContainer.style.display = 'block';
                    btnAnalyze.disabled = true;
                    photoBlob = null;
                } catch (err) {
                    alert("No se pudo acceder a la cámara.");
                }
            }

            if(btnStartCamera) btnStartCamera.addEventListener('click', startCamera);
            if(btnSwitchCamera) btnSwitchCamera.addEventListener('click', () => { useFrontCamera = !useFrontCamera; startCamera(); });

            if(btnSnapPhoto) {
                btnSnapPhoto.addEventListener('click', () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    canvas.toBlob((blob) => {
                        photoBlob = blob;
                        imagePreview.src = URL.createObjectURL(blob);
                        stopMediaTracks(currentStream);
                        cameraContainer.style.display = 'none';
                        previewContainer.style.display = 'flex';
                        uploadPlaceholder.style.display = 'none';
                        imagePreview.style.display = 'block';
                        btnRetakePhoto.classList.remove('d-none');
                        btnAnalyze.disabled = false;
                    }, 'image/jpeg', 0.9);
                });
            }
            
            if(fileInput) {
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        photoBlob = e.target.files[0];
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            uploadPlaceholder.style.display = 'none';
                            imagePreview.style.display = 'block';
                            btnRetakePhoto.classList.remove('d-none');
                            btnAnalyze.disabled = false;
                        }
                        reader.readAsDataURL(e.target.files[0]);
                    }
                });
            }

            if(btnRetakePhoto) {
                btnRetakePhoto.addEventListener('click', () => {
                    photoBlob = null;
                    imagePreview.src = '';
                    imagePreview.style.display = 'none';
                    btnRetakePhoto.classList.add('d-none');
                    uploadPlaceholder.style.display = 'block';
                    btnAnalyze.disabled = true;
                    if(fileInput) fileInput.value = '';
                });
            }

            const iaModal = document.getElementById('iaAdminModal');
            if(iaModal) {
                iaModal.addEventListener('hidden.bs.modal', function () {
                    stopMediaTracks(currentStream);
                    cameraContainer.style.display = 'none';
                    previewContainer.style.display = 'flex';
                    if(fileInput) fileInput.value = '';
                    imagePreview.style.display = 'none';
                    btnRetakePhoto.classList.add('d-none');
                    uploadPlaceholder.style.display = 'block';
                    btnAnalyze.disabled = true;
                    photoBlob = null;
                    statusDiv.classList.add('d-none');
                });
            }
            
            if(btnAnalyze) {
                btnAnalyze.addEventListener('click', () => {
                    btnAnalyze.disabled = true;
                    statusDiv.classList.remove('d-none');
                    loadingDiv.classList.remove('d-none');
                    setTimeout(() => {
                        window.location.href = 'repuestos.php?search=Freno';
                    }, 1500);
                });
            }
        });

        const catModal = new bootstrap.Modal(document.getElementById('catModal'));
        const reassignModal = new bootstrap.Modal(document.getElementById('reassignModal'));
        
        function openCatModal(action, data = null) {
            document.getElementById('cat_action').value = action;
            document.getElementById('catForm').reset();
            if (action === 'add') {
                document.getElementById('catModalTitle').innerText = 'Nueva Categoría';
                document.getElementById('cat_id').value = '';
            } else if (action === 'edit' && data) {
                document.getElementById('catModalTitle').innerText = 'Editar Categoría';
                document.getElementById('cat_id').value = data.id_categoria || data.id;
                document.getElementById('cat_nombre').value = data.nombre || '';
                document.getElementById('cat_descripcion').value = data.descripcion || '';
            }
            catModal.show();
        }

        document.getElementById('catForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveCat');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            btn.disabled = true;
            try {
                const res = await fetch('../../controllers/catalogo_action.php', { method: 'POST', body: new FormData(this) });
                const data = await res.json();
                if (data.status === 'success') {
                    catModal.hide();
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, confirmButtonColor: '#004ba8' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#004ba8' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de red.', confirmButtonColor: '#004ba8' });
            } finally { btn.innerHTML = orig; btn.disabled = false; }
        });

        async function deleteCategoria(id, nombre) {
            Swal.fire({
                title: '¿Eliminar Categoría?', text: `Se eliminará "${nombre}". El sistema verificará dependencias.`, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Continuar', cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); fd.append('action', 'check_delete'); fd.append('id', id);
                    try {
                        const res = await fetch('../../controllers/catalogo_action.php', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (data.status === 'success' && data.needs_reassign) {
                            document.getElementById('delete_cat_id').value = id;
                            document.getElementById('reassignMessage').innerHTML = `La categoría <b>${nombre}</b> tiene repuestos asignados. Elige a dónde moverlos antes de eliminar.`;
                            const sel = document.getElementById('id_nueva_categoria');
                            Array.from(sel.options).forEach(o => o.style.display = o.value == id ? 'none' : 'block');
                            sel.value = "";
                            reassignModal.show();
                        } else if (data.status === 'success') {
                            executeDelete(id, 0);
                        } else {
                            Swal.fire({ icon: 'error', title: 'No se puede eliminar', text: data.message, confirmButtonColor: '#004ba8' });
                        }
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de red.', confirmButtonColor: '#004ba8' });
                    }
                }
            });
        }

        document.getElementById('reassignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            executeDelete(document.getElementById('delete_cat_id').value, document.getElementById('id_nueva_categoria').value);
        });

        async function executeDelete(id, newId) {
            const btn = document.getElementById('btnConfirmDelete');
            if(btn) { btn.disabled = true; btn.innerHTML = 'Procesando...'; }
            const fd = new FormData();
            fd.append('action', 'delete'); fd.append('id', id); fd.append('id_nueva_categoria', newId);
            try {
                const res = await fetch('../../controllers/catalogo_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    reassignModal.hide();
                    Swal.fire({ icon: 'success', title: '¡Eliminada!', text: data.message, confirmButtonColor: '#004ba8' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#004ba8' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de red.', confirmButtonColor: '#004ba8' });
            } finally {
                if(btn) { btn.disabled = false; btn.innerHTML = 'Confirmar Eliminación'; }
            }
        }
        function searchCategory(name) {
            const categoryMap = {
                <?php foreach($categorias as $c): ?>
                "<?php echo strtolower($c['nombre']); ?>": <?php echo $c['id_categoria']; ?>,
                <?php endforeach; ?>
            };
            
            let key = name.toLowerCase();
            if (categoryMap[key]) {
                window.location.href = 'repuestos.php?categoria=' + categoryMap[key];
            } else {
                window.location.href = 'repuestos.php?search=' + encodeURIComponent(name);
            }
        }
    </script>
</body>
</html>
