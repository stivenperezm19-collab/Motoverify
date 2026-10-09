<?php
// Iniciamos la sesión para poder identificar al administrador
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}
// Conectamos a la base de datos
require_once '../../config/database.php';
require_once '../../models/MarcaModel.php';
require_once '../../models/CategoriaModel.php';
require_once '../../models/RepuestoModel.php';

$marcaModel = new MarcaModel($conn);
$catModel = new CategoriaModel($conn);
$repModel = new RepuestoModel($conn);


// --- OBTENER DATOS PARA LOS MENÚS DESPLEGABLES ---
// 1. Buscamos todas las marcas disponibles para poder seleccionarlas al crear un repuesto
$marcas = $marcaModel->getAll();

// 2. Buscamos todas las categorías disponibles para poder seleccionarlas al crear un repuesto
$categorias = $catModel->getAll();

// --- OBTENER DATOS PARA LOS FILTROS DE BÚSQUEDA ---
// Estas listas sirven para llenar las opciones del menú "Filtro" en la barra de arriba
$marcas_filter = $marcas;
$cat_filter = $categorias;
$mod_filter = $repModel->getModelos();

// --- BUSCAR Y FILTRAR LOS REPUESTOS A MOSTRAR ---
$repuestos = [];
// Revisamos si el usuario escribió algo en la barra de búsqueda o seleccionó algún filtro
$filterSearch = $_GET['search'] ?? '';
$filterMarca = isset($_GET['marca']) ? intval($_GET['marca']) : (isset($_GET['id_marca']) ? intval($_GET['id_marca']) : 0);
$filterCategoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

// Configuración de la paginación
$repuestosPorPagina = 12; // Cantidad de repuestos a mostrar por página
$paginaActual = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($paginaActual < 1) $paginaActual = 1;
$offset = ($paginaActual - 1) * $repuestosPorPagina;

$totalRepuestos = 0;
$totalPaginas = 1;

try {
    // --- 1. Consulta para saber el total de repuestos (para calcular las páginas) ---
    $countQuery = "SELECT COUNT(r.id_repuesto) as total 
                   FROM REPUESTO r 
                   LEFT JOIN MARCA m ON r.id_marca = m.id_marca 
                   WHERE 1=1";
                   
    // --- 2. Consulta para traer los datos reales de los repuestos ---
    $query = "SELECT r.*, m.nombre as marca_nombre, i.ruta_archivo as imagen_url 
              FROM REPUESTO r 
              LEFT JOIN MARCA m ON r.id_marca = m.id_marca 
              LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
              LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
              WHERE 1=1"; // WHERE 1=1 permite ir sumando filtros dinámicamente

    $whereFiltros = ""; // Aquí iremos guardando los filtros para aplicarlos a ambas consultas
    
    // Si escribió algo en el buscador de texto...
    if (!empty($filterSearch)) {
        // Buscamos palabra por palabra en varios campos (nombre, código interno, barras, compatibilidades, etc.)
        $searchTerms = array_filter(explode(' ', trim($filterSearch)));
        foreach ($searchTerms as $term) {
            $searchTerm = $conn->real_escape_string($term);
            $whereFiltros .= " AND (r.nombre LIKE '%$searchTerm%' OR r.codigo_interno LIKE '%$searchTerm%' OR r.codigo_barras LIKE '%$searchTerm%' OR r.compatibilidades LIKE '%$searchTerm%' OR r.referencia_fabricante LIKE '%$searchTerm%' OR m.nombre LIKE '%$searchTerm%')";
        }
    }
    // Si filtró por una marca específica...
    if ($filterMarca > 0) {
        $whereFiltros .= " AND r.id_marca = $filterMarca";
    }
    // Si filtró por una categoría específica...
    if ($filterCategoria > 0) {
        $whereFiltros .= " AND r.id_categoria = $filterCategoria";
    }
    
    // Agregamos los filtros a ambas consultas
    $countQuery .= $whereFiltros;
    $query .= $whereFiltros;

    // Ejecutamos la consulta de conteo para saber cuántas páginas habrá
    $resCount = $conn->query($countQuery);
    if ($resCount) {
        $totalRepuestos = $resCount->fetch_assoc()['total'];
        $totalPaginas = ceil($totalRepuestos / $repuestosPorPagina); // Redondea hacia arriba
    }
    
    // Ordenamos para que aparezcan primero los agregados más recientemente y aplicamos la paginación (LIMIT y OFFSET)
    $query .= " ORDER BY r.id_repuesto DESC LIMIT $repuestosPorPagina OFFSET $offset";
    
    // Ejecutamos la búsqueda final y guardamos los resultados
    $resRepuestos = $conn->query($query);
    if ($resRepuestos) {
        while ($r = $resRepuestos->fetch_assoc()) {
            $repuestos[] = $r;
        }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Repuestos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; color: #333; overflow-x: hidden; }
        
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
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); display: flex; flex-direction: column; background-color: #fbfbfd; }
        
        /* Hero Banner */
        
        
        .hero-gears { position: absolute; right: 5%; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.1); pointer-events: none; }
        
        /* Cards */
        .part-card { border: 1px solid #eaeaea; border-radius: 12px; transition: all 0.2s; }
        .part-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-3px); }
        .btn-action-card { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid; cursor: pointer; transition: all 0.2s; font-size: 0.8rem; }
        .btn-edit-card { border-color: #bfdbfe; background: white; color: #2563eb; }
        .btn-edit-card:hover { background: #eff6ff; }
        .btn-delete-card { border-color: #fecaca; background: white; color: #dc2626; }
        .btn-delete-card:hover { background: #fef2f2; }
            /* Hero Banner */
        .hero-banner { background: linear-gradient(135deg, #0640aa 0%, #1e5abf 100%); color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 40px; top: 25%; height: 50%; width: 4px; background-color: #60a5fa; border-radius: 4px; }
        .hero-gears { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); pointer-events: none; }
    </style>
</head>
<body>

    <!-- Topbar Global -->
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

    <!-- Sidebar -->
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">

        <div class="p-4 flex-grow-1">
            
                        <!-- Hero Banner -->
            <div class="hero-banner rounded-3 p-4 ps-5 mb-4 d-flex align-items-center justify-content-between">
                <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                    <h2 class="fw-bold mb-2 d-flex align-items-center gap-3">
                        <i class="fa-solid fa-puzzle-piece" style="font-size: 1.4rem; opacity: .8;"></i>
                        Gestión de Repuestos
                    </h2>
                    <p class="mb-0 opacity-75" style="font-size: 1rem;">Inventario, referencias, compatibilidades y fichas técnicas.</p>
                </div>
                <div class="d-flex align-items-center" style="z-index: 2; position: relative;">
                    <button class="btn btn-light text-primary fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#repuestoModal" onclick="openModal('add')" style="border-radius: 8px;"><i class="fa-solid fa-plus me-2"></i> Nuevo Repuesto</button>
                </div>
                <div class="hero-gears">
                    <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                    <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
                </div>
            </div>

            <?php if ($filterMarca > 0): ?>
                <div class="alert alert-info d-flex align-items-center justify-content-between" role="alert">
                    <span>Mostrando repuestos filtrados por marca.</span>
                    <a href="repuestos.php" class="btn btn-sm btn-outline-info">Ver todos</a>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php if (empty($repuestos)): ?>
                    <div class="col-12 text-center text-muted py-5">No hay repuestos registrados.</div>
                <?php else: ?>
                    <?php foreach ($repuestos as $rep): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="part-card bg-white p-3 d-flex flex-column h-100 position-relative">
                            
                            <!-- Botones Flotantes -->
                            <div class="position-absolute top-0 end-0 p-3 d-flex gap-2" style="z-index: 10;">
                                <button class="btn-action-card btn-delete-card" title="Eliminar" onclick="deleteRepuesto(<?php echo $rep['id_repuesto']; ?>, '<?php echo addslashes(htmlspecialchars($rep['nombre'])); ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <button class="btn-action-card btn-edit-card" title="Editar" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($rep)); ?>)">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </div>

                            <div class="row g-0 flex-grow-1 mt-1">
                                <div class="col-7 pe-2 d-flex flex-column justify-content-center">
                                    <?php $codigo_mostrar = !empty($rep['codigo_barras']) ? $rep['codigo_barras'] : ($rep['codigo_interno'] ?? 'N/A'); ?>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 1.1rem;"><?php echo htmlspecialchars($codigo_mostrar); ?></div>
                                    <div class="lh-sm mb-1">
                                        <span class="text-primary fw-bold" style="font-size: 0.95rem;"><?php echo htmlspecialchars($rep['marca_nombre'] ?? 'Sin Marca'); ?></span>
                                        <span class="text-secondary" style="font-size: 0.95rem;"><?php echo htmlspecialchars($rep['nombre']); ?></span>
                                    </div>
                                    <?php if(!empty($rep['compatibilidades'])): ?>
                                    <div class="text-black-50 fst-italic mt-1" style="font-size: 0.85rem;"><?php echo htmlspecialchars($rep['compatibilidades']); ?></div>
                                    <?php endif; ?>
                                    <div class="mt-auto pt-2 d-flex gap-2">
                                        <span class="badge bg-success" style="font-size: 0.85rem;">$<?php echo number_format($rep['precio'] ?? 0, 2); ?></span>
                                        <span class="badge <?php echo ($rep['stock'] ?? 0) > 0 ? 'bg-primary' : 'bg-danger'; ?>" style="font-size: 0.85rem;">Stock: <?php echo htmlspecialchars($rep['stock'] ?? 0); ?></span>
                                    </div>
                                </div>
                                <div class="col-5 d-flex align-items-center justify-content-center position-relative" style="background-color: #f0f0f0; border-radius: 8px; overflow: hidden; min-height: 90px;">
                                    <?php if (!empty($rep['imagen_url'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($rep['imagen_url']); ?>" alt="Repuesto" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="text-muted d-flex flex-column align-items-center justify-content-center" style="opacity: 0.5;">
                                            <i class="fa-solid fa-image" style="font-size: 1.5rem;"></i>
                                            <span style="font-size: 0.7rem; margin-top: 5px;">Sin foto</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <a href="../../public/ficha_tecnica.php?id=<?php echo $rep['id_repuesto']; ?>" class="position-absolute bottom-0 end-0 p-1 text-decoration-none" title="Ver Ficha Técnica">
                                        <i class="fa-solid fa-arrow-right text-muted" style="font-size: 1rem; cursor: pointer; opacity: 0.7;"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($totalPaginas > 1): ?>
            <nav aria-label="Navegación de páginas" class="mt-5 mb-3">
                <ul class="pagination justify-content-center">
                    <?php 
                    // Conservamos los filtros actuales en la URL (como búsquedas o marcas seleccionadas)
                    $parametrosUrl = $_GET;
                    
                    // --- Botón Anterior ---
                    if ($paginaActual > 1): 
                        $parametrosUrl['page'] = $paginaActual - 1;
                        $urlAnterior = '?' . http_build_query($parametrosUrl);
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo htmlspecialchars($urlAnterior); ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo; Anterior</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">&laquo; Anterior</span>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    // --- Números de página ---
                    // Calculamos qué números de página mostrar (máximo 5 páginas visibles)
                    $inicioPagina = max(1, $paginaActual - 2);
                    $finPagina = min($totalPaginas, $paginaActual + 2);
                    
                    for ($i = $inicioPagina; $i <= $finPagina; $i++): 
                        $parametrosUrl['page'] = $i;
                        $urlPagina = '?' . http_build_query($parametrosUrl);
                    ?>
                        <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($urlPagina); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php 
                    // --- Botón Siguiente ---
                    if ($paginaActual < $totalPaginas): 
                        $parametrosUrl['page'] = $paginaActual + 1;
                        $urlSiguiente = '?' . http_build_query($parametrosUrl);
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo htmlspecialchars($urlSiguiente); ?>" aria-label="Siguiente">
                                <span aria-hidden="true">Siguiente &raquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">Siguiente &raquo;</span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </main>

    <!-- Modal Repuesto -->
    <div class="modal fade" id="repuestoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Repuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="repuestoForm" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" id="repuesto_id" name="id">
                        <input type="hidden" id="action" name="action" value="add">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Código Interno <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3" id="codigo_interno" name="codigo_interno" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Marca <span class="text-danger">*</span></label>
                                <select class="form-select rounded-3" id="marca_id" name="marca_id" required>
                                    <option value="">Seleccione una marca</option>
                                    <?php foreach ($marcas as $m): ?>
                                        <option value="<?php echo $m['id_marca']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select rounded-3" id="categoria_id" name="categoria_id" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?php echo $c['id_categoria']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Nombre del Repuesto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3" id="nombre" name="nombre" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-secondary">Descripción</label>
                                <textarea class="form-control rounded-3" id="descripcion" name="descripcion" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Referencia Fabricante</label>
                                <input type="text" class="form-control rounded-3" id="referencia_fabricante" name="referencia_fabricante">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Observaciones</label>
                                <input type="text" class="form-control rounded-3" id="observaciones" name="observaciones">
                            </div>
                            
                            <!-- Campos de Precio y Stock -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Precio de Venta ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control rounded-3" id="precio" name="precio" required min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Stock <span class="text-danger">*</span></label>
                                <input type="number" class="form-control rounded-3" id="stock" name="stock" required min="0">
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-secondary">Características</label>
                                <textarea class="form-control rounded-3" id="caracteristicas" name="caracteristicas" rows="2" placeholder="Ej: Material: Aluminio, Color: Negro"></textarea>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-secondary">Compatibilidades</label>
                                <textarea class="form-control rounded-3" id="compatibilidades" name="compatibilidades" rows="2" placeholder="Ej: Yamaha MT-09, FZ-09"></textarea>
                            </div>
                            
                            <!-- Código de Barras -->
                            <div class="col-12">
                                <label class="form-label fw-semibold text-secondary">Código de Barras</label>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" placeholder="Escanea o genera un código" oninput="updateBarcodePreview()">
                                    <button class="btn btn-outline-primary" type="button" id="btnGenerateBarcode" title="Generar interno" onclick="generateBarcode()">
                                        <i class="fa-solid fa-barcode"></i> Generar
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="btnScanBarcode" title="Escanear" onclick="startBarcodeScanner()">
                                        <i class="fa-solid fa-camera"></i>
                                    </button>
                                </div>
                                
                                <div id="barcodeScannerContainer" style="display: none; width: 100%; max-width: 400px; margin: 0 auto 10px auto;">
                                    <!-- Lector de código de barras -->
                                    <div id="barcode-reader" style="width:100%;"></div>
                                    <button type="button" class="btn btn-sm btn-danger mt-2 w-100" id="btnCancelScan" onclick="stopBarcodeScanner()">Cancelar Escaneo</button>
                                </div>
                                
                                <div class="text-center mt-2 border rounded p-2 bg-light" id="barcodePreviewContainer" style="display: none;">
                                    <svg id="barcodePreview"></svg>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-semibold text-secondary">Imagen del Repuesto (Opcional)</label>
                                <input type="file" class="form-control rounded-3" id="imagen" name="imagen" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-3 fw-semibold px-4" id="btnSave" style="background-color: #004ba8; border: none;">Guardar</button>
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
            <h5 class="modal-title fw-bold"><i class="fa-solid fa-microchip me-2"></i>Reconocimiento Visual y Barras</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseIaModal"></button>
          </div>
          <div class="modal-body text-center py-4">
            <!-- Contenedor Cámara en Vivo -->
            <div id="cameraContainer" class="position-relative mb-3" style="display: none;">
                <div id="qrReader" style="width: 100%; min-height: 300px;"></div>
                <button class="btn btn-light position-absolute bottom-0 start-50 translate-middle-x mb-3 rounded-circle shadow d-flex align-items-center justify-content-center" id="btnSnapPhoto" style="width: 60px; height: 60px; z-index: 10;">
                    <i class="fa-solid fa-camera fs-3 text-primary"></i>
                </button>
                <button class="btn btn-dark position-absolute top-0 end-0 m-2 rounded-circle shadow-sm" id="btnSwitchCamera" style="z-index: 10;" title="Cambiar cámara">
                    <i class="fa-solid fa-camera-rotate"></i>
                </button>
            </div>

            <!-- Canvas oculto para capturar el frame -->
            <canvas id="cameraCanvas" class="d-none"></canvas>

            <!-- Input de archivo (fallback) -->
            <input type="file" id="aiImageInput" accept="image/*" class="d-none">
            
            <!-- Contenedor Preview -->
            <div id="imagePreviewContainer" style="width:100%; height:350px; border:2px dashed #ccc; border-radius:10px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#f8f9fa; position:relative; overflow:hidden;">
                <div class="upload-placeholder" id="uploadPlaceholder" style="text-align:center; color:#888; padding:20px;">
                    <i class="fa-solid fa-barcode mb-2" style="font-size: 2rem; color:#004ba8;"></i>
                    <i class="fa-solid fa-image ms-2 mb-2" style="font-size: 2rem; color:#004ba8;"></i>
                    <p class="mb-2 fw-semibold">Escanea un código de barras o toma una foto</p>
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
                <div class="alert alert-danger mt-3 d-none" id="aiErrorMsg"></div>
                <div class="alert alert-success mt-3 d-none" id="aiSuccessMsg"></div>
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

    <!-- Modal Importación con IA -->
    <div class="modal fade" id="aiImportModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
          <div class="modal-header border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Carga Masiva con IA</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <!-- Zona de Carga -->
            <div id="aiUploadZone" class="text-center p-5 mb-4" style="border: 2px dashed #198754; border-radius: 12px; background: #f8fff9; transition: all 0.3s; cursor: pointer;" onclick="document.getElementById('aiBulkInput').click()">
                <i class="fa-solid fa-file-arrow-up text-success mb-3" style="font-size: 3rem;"></i>
                <h4 class="fw-bold text-dark">Sube tu archivo (PDF, CSV o Imagen)</h4>
                <p class="text-muted mb-0">Haz clic aquí para seleccionar tu archivo desestructurado.</p>
                <input type="file" id="aiBulkInput" accept=".txt,.csv,.jpg,.jpeg,.png,.pdf" class="d-none">
            </div>
            
            <!-- Loader -->
            <div id="aiBulkLoading" class="text-center p-5 d-none">
                <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                <h5 class="fw-bold text-dark">La Inteligencia Artificial está analizando tu documento...</h5>
                <p class="text-muted">Esto puede tomar unos segundos. Extraeremos nombres, marcas y códigos.</p>
            </div>
            
            <!-- Vista Previa de Datos -->
            <div id="aiBulkPreview" class="d-none">
                <div class="alert alert-info d-flex align-items-center mb-3">
                    <i class="fa-solid fa-circle-info me-2"></i> Revisa y corrige los datos antes de guardarlos. Los campos resaltados tienen baja confianza.
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Código/Barras</th>
                                <th>Nombre del Repuesto</th>
                                <th>Marca</th>
                                <th>Categoría</th>
                                <th>Modelo</th>
                                <th>Confianza</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="aiBulkTableBody">
                            <!-- JS inserta filas aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
          </div>
          <div class="modal-footer border-top-0 pb-4 px-4">
            <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success rounded-3 fw-semibold px-4 d-none" id="btnConfirmBulkSave">Confirmar y Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        // Variables para el escáner de códigos de barras
        let html5QrcodeScanner = null;
        
        function updateBarcodePreview() {
            const code = document.getElementById('codigo_barras').value.trim();
            const container = document.getElementById('barcodePreviewContainer');
            if (code) {
                container.style.display = 'block';
                try {
                    JsBarcode("#barcodePreview", code, {
                        format: "CODE128",
                        lineColor: "#000",
                        width: 2,
                        height: 40,
                        displayValue: true
                    });
                } catch(e) {
                    console.log("Formato no soportado por JsBarcode, mostrando de todos modos", e);
                }
            } else {
                container.style.display = 'none';
            }
        }
        // Funciones Globales para Código de Barras
        function generateBarcode() {
            const randomCode = 'MTO-' + Math.floor(100000000 + Math.random() * 900000000);
            document.getElementById('codigo_barras').value = randomCode;
            updateBarcodePreview();
        }

        function startBarcodeScanner() {
            const scannerContainer = document.getElementById('barcodeScannerContainer');
            scannerContainer.style.display = 'block';
            
            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner(
                    "barcode-reader", { fps: 10, qrbox: {width: 250, height: 100} }, false);
                    
                html5QrcodeScanner.render(function(decodedText, decodedResult) {
                    // Exito al leer
                    document.getElementById('codigo_barras').value = decodedText;
                    updateBarcodePreview();
                    stopBarcodeScanner();
                }, function(errorMessage) {
                    // Ignorar errores en tiempo real
                });
            }
        }

        function stopBarcodeScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }
            document.getElementById('barcodeScannerContainer').style.display = 'none';
        }
        
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
            const errorMsg = document.getElementById('aiErrorMsg');
            const successMsg = document.getElementById('aiSuccessMsg');
            const btnClose = document.getElementById('btnCloseIaModal');
            const btnCancel = document.getElementById('btnCancelIa');
            const btnRetakePhoto = document.getElementById('btnRetakePhoto');
            
            const cameraContainer = document.getElementById('cameraContainer');
            const canvas = document.getElementById('cameraCanvas');
            const btnStartCamera = document.getElementById('btnStartCamera');
            const btnSnapPhoto = document.getElementById('btnSnapPhoto');
            const btnSwitchCamera = document.getElementById('btnSwitchCamera');

            let currentStream = null;
            let useFrontCamera = false;
            let photoBlob = null; 

            const qrReaderElement = document.getElementById('qrReader');
            let iaHtml5QrCode = null;

            function stopMediaTracks() {
                if (iaHtml5QrCode && iaHtml5QrCode.isScanning) {
                    iaHtml5QrCode.stop().then((ignore) => {
                        iaHtml5QrCode.clear();
                    }).catch((err) => {
                        console.error("Failed to stop QR scanner", err);
                    });
                }
            }

            function startCamera() {
                stopMediaTracks();
                
                if (!iaHtml5QrCode) {
                    iaHtml5QrCode = new Html5Qrcode("qrReader");
                }
                
                previewContainer.style.display = 'none';
                cameraContainer.style.display = 'block';
                if(btnAnalyze) btnAnalyze.disabled = true;
                photoBlob = null;
                if(statusDiv) statusDiv.classList.add('d-none');
                if(errorMsg) errorMsg.classList.add('d-none');
                if(successMsg) successMsg.classList.add('d-none');
                
                const facingMode = useFrontCamera ? "user" : "environment";
                
                iaHtml5QrCode.start(
                    { facingMode: facingMode },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    (decodedText, decodedResult) => {
                        stopMediaTracks();
                        searchBarcode(decodedText);
                    },
                    (errorMessage) => {
                        // ignorar
                    })
                .catch((err) => {
                    console.error("No se pudo iniciar la cámara", err);
                    alert("Error al acceder a la cámara.");
                });
            }

            function searchBarcode(decodedText) {
                if(statusDiv) statusDiv.classList.remove('d-none');
                if(loadingDiv) {
                    loadingDiv.classList.remove('d-none');
                    loadingDiv.innerHTML = '<div class="spinner-border me-2" role="status"></div> Buscando código en la base de datos...';
                }
                if(errorMsg) errorMsg.classList.add('d-none');
                if(successMsg) successMsg.classList.add('d-none');
                
                const formData = new FormData();
                formData.append('qr_text', decodedText);
                
                fetch('../../controllers/qr_search.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(loadingDiv) loadingDiv.classList.add('d-none');
                    if (data.status === 'success') {
                        if(successMsg) {
                            successMsg.textContent = '¡Código válido! Redirigiendo a ' + data.repuesto_nombre;
                            successMsg.classList.remove('d-none');
                        }
                        setTimeout(() => {
                            window.location.href = 'repuestos.php?search=' + encodeURIComponent(data.repuesto_nombre);
                        }, 1500);
                    } else {
                        window.location.href = 'repuestos.php?search=' + encodeURIComponent(decodedText);
                    }
                })
                .catch(err => {
                    if(loadingDiv) loadingDiv.classList.add('d-none');
                    window.location.href = 'repuestos.php?search=' + encodeURIComponent(decodedText);
                });
            }

            if(btnStartCamera) btnStartCamera.addEventListener('click', startCamera);
            if(btnSwitchCamera) btnSwitchCamera.addEventListener('click', () => { useFrontCamera = !useFrontCamera; startCamera(); });

            if(btnSnapPhoto) {
                btnSnapPhoto.addEventListener('click', () => {
                    const videoElement = document.querySelector('#qrReader video');
                    if (!videoElement) return;
                    
                    canvas.width = videoElement.videoWidth;
                    canvas.height = videoElement.videoHeight;
                    canvas.getContext('2d').drawImage(videoElement, 0, 0);
                    
                    canvas.toBlob((blob) => {
                        photoBlob = blob;
                        const imageUrl = URL.createObjectURL(blob);
                        
                        if(imagePreview) imagePreview.src = imageUrl;
                        
                        stopMediaTracks();
                        if(cameraContainer) cameraContainer.style.display = 'none';
                        if(previewContainer) previewContainer.style.display = 'flex';
                        if(uploadPlaceholder) uploadPlaceholder.style.display = 'none';
                        if(imagePreview) imagePreview.style.display = 'block';
                        
                        if(btnRetakePhoto) btnRetakePhoto.classList.remove('d-none');
                        if(btnAnalyze) btnAnalyze.disabled = false;
                    }, 'image/jpeg', 0.9);
                });
            }

            const iaModal = document.getElementById('iaAdminModal');
            if(iaModal) {
                iaModal.addEventListener('hidden.bs.modal', function () {
                    stopMediaTracks();
                    if(cameraContainer) cameraContainer.style.display = 'none';
                    if(previewContainer) previewContainer.style.display = 'flex';
                    
                    if(fileInput) fileInput.value = '';
                    if(imagePreview) imagePreview.style.display = 'none';
                    if(btnRetakePhoto) btnRetakePhoto.classList.add('d-none');
                    if(uploadPlaceholder) uploadPlaceholder.style.display = 'block';
                    
                    if(btnAnalyze) btnAnalyze.disabled = true;
                    photoBlob = null;
                    
                    if(statusDiv) statusDiv.classList.add('d-none');
                    if(errorMsg) errorMsg.classList.add('d-none');
                    if(successMsg) successMsg.classList.add('d-none');
                    if(loadingDiv) loadingDiv.classList.add('d-none');
                });
            }

            if(btnRetakePhoto) {
                btnRetakePhoto.addEventListener('click', () => {
                    if(imagePreview) imagePreview.style.display = 'none';
                    btnRetakePhoto.classList.add('d-none');
                    if(uploadPlaceholder) uploadPlaceholder.style.display = 'block';
                    if(btnAnalyze) btnAnalyze.disabled = true;
                    photoBlob = null;
                    if(fileInput) fileInput.value = '';
                });
            }

            function handleImageFile(file) {
                if (file) {
                    if (!iaHtml5QrCode) {
                        iaHtml5QrCode = new Html5Qrcode("qrReader");
                    }
                    
                    if(statusDiv) statusDiv.classList.remove('d-none');
                    if(loadingDiv) {
                        loadingDiv.classList.remove('d-none');
                        loadingDiv.innerHTML = '<div class="spinner-border me-2" role="status"></div> Buscando código de barras en la imagen...';
                    }
                    if(errorMsg) errorMsg.classList.add('d-none');
                    if(successMsg) successMsg.classList.add('d-none');
                    if(btnAnalyze) btnAnalyze.disabled = true;

                    iaHtml5QrCode.scanFile(file, false)
                        .then(decodedText => {
                            searchBarcode(decodedText);
                        })
                        .catch(err => {
                            if(loadingDiv) loadingDiv.classList.add('d-none');
                            if(statusDiv) statusDiv.classList.add('d-none');
                            
                            photoBlob = file;
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                if(imagePreview) imagePreview.src = e.target.result;
                                if(uploadPlaceholder) uploadPlaceholder.style.display = 'none';
                                if(imagePreview) imagePreview.style.display = 'block';
                                if(btnRetakePhoto) btnRetakePhoto.classList.remove('d-none');
                                if(btnAnalyze) btnAnalyze.disabled = false;
                            }
                            reader.readAsDataURL(file);
                        });
                }
            }

            if(fileInput) {
                fileInput.addEventListener('change', function() {
                    handleImageFile(this.files[0]);
                });
            }

            if(uploadPlaceholder) {
                uploadPlaceholder.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadPlaceholder.style.border = '2px dashed #0d6efd';
                    uploadPlaceholder.style.backgroundColor = '#f8f9fa';
                });
                uploadPlaceholder.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    uploadPlaceholder.style.border = 'none';
                    uploadPlaceholder.style.backgroundColor = 'transparent';
                });
                uploadPlaceholder.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadPlaceholder.style.border = 'none';
                    uploadPlaceholder.style.backgroundColor = 'transparent';
                    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        const file = e.dataTransfer.files[0];
                        if (file.type.startsWith('image/')) {
                            handleImageFile(file);
                        }
                    }
                });
            }

            document.addEventListener('paste', (e) => {
                if (iaModal && iaModal.classList.contains('show') && uploadPlaceholder && uploadPlaceholder.style.display !== 'none') {
                    if (e.clipboardData && e.clipboardData.files && e.clipboardData.files.length > 0) {
                        const file = e.clipboardData.files[0];
                        if (file.type.startsWith('image/')) {
                            handleImageFile(file);
                        }
                    }
                }
            });

            if(btnAnalyze) {
                btnAnalyze.addEventListener('click', function() {
                    if (!photoBlob) return;
                    
                    btnAnalyze.disabled = true;
                    if(btnRetakePhoto) btnRetakePhoto.classList.add('d-none');
                    if(statusDiv) statusDiv.classList.remove('d-none');
                    if(loadingDiv) {
                        loadingDiv.classList.remove('d-none');
                        loadingDiv.innerHTML = '<div class="spinner-border me-2" role="status"><span class="visually-hidden">Analizando...</span></div> Analizando imagen con IA...';
                    }
                    if(errorMsg) errorMsg.classList.add('d-none');
                    if(successMsg) successMsg.classList.add('d-none');
                    
                    const formData = new FormData();
                    formData.append('image', photoBlob, 'photo.jpg');
                    
                    fetch('../../controllers/ai_analyze.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(loadingDiv) loadingDiv.classList.add('d-none');
                        if (data.status === 'success') {
                            if(successMsg) {
                                let msg = 'IA detectó: "' + data.ai_detected + '" → Repuesto: "' + data.repuesto_nombre + '"';
                                successMsg.innerHTML = '<strong>¡Repuesto identificado!</strong><br><small>' + msg + '</small><br><small class="text-muted">Redirigiendo...</small>';
                                successMsg.classList.remove('d-none');
                            }
                            setTimeout(() => {
                                window.location.href = 'repuestos.php?search=' + encodeURIComponent(data.repuesto_nombre);
                            }, 2500);
                        } else {
                            if(errorMsg) {
                                errorMsg.textContent = data.message;
                                errorMsg.classList.remove('d-none');
                            }
                            btnAnalyze.disabled = false;
                            if(btnRetakePhoto) btnRetakePhoto.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        if(loadingDiv) loadingDiv.classList.add('d-none');
                        if(errorMsg) {
                            errorMsg.textContent = 'Error de conexión con el sistema de IA.';
                            errorMsg.classList.remove('d-none');
                        }
                        btnAnalyze.disabled = false;
                        if(btnRetakePhoto) btnRetakePhoto.classList.remove('d-none');
                    });
                });
            }
        });

        const repuestoModal = new bootstrap.Modal(document.getElementById('repuestoModal'));
        
        function openModal(action, data = null) {
            document.getElementById('action').value = action;
            document.getElementById('repuestoForm').reset();
            document.getElementById('barcodePreviewContainer').style.display = 'none';
            
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }
            document.getElementById('barcodeScannerContainer').style.display = 'none';
            
            if (action === 'add') {
                document.getElementById('modalTitle').innerText = 'Nuevo Repuesto';
                document.getElementById('repuesto_id').value = '';
            } else if (action === 'edit' && data) {
                document.getElementById('modalTitle').innerText = 'Editar Repuesto';
                document.getElementById('repuesto_id').value = data.id_repuesto || data.id || '';
                document.getElementById('codigo_interno').value = data.codigo_interno || '';
                document.getElementById('marca_id').value = data.id_marca || data.marca_id || '';
                document.getElementById('categoria_id').value = data.id_categoria || data.categoria_id || '';
                document.getElementById('nombre').value = data.nombre || '';
                document.getElementById('descripcion').value = data.descripcion || '';
                document.getElementById('referencia_fabricante').value = data.referencia_fabricante || '';
                document.getElementById('observaciones').value = data.observaciones || '';
                document.getElementById('precio').value = data.precio || '0';
                document.getElementById('stock').value = data.stock || '0';
                document.getElementById('caracteristicas').value = data.caracteristicas || '';
                document.getElementById('compatibilidades').value = data.compatibilidades || '';
                document.getElementById('codigo_barras').value = data.codigo_barras || '';
                
                updateBarcodePreview();
            }
            repuestoModal.show();
        }

        document.getElementById('repuestoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSave');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

            const formData = new FormData(this);

            fetch('../../controllers/repuestos_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    repuestoModal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data.message,
                        confirmButtonColor: '#004ba8'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Hubo un problema de conexión.', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = 'Guardar';
            });
        });

        function deleteRepuesto(id, nombre) {
            Swal.fire({
                title: '¿Estás seguro?', text: `Se eliminará el repuesto "${nombre}".`, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id_repuesto', id);
                    try {
                        const response = await fetch('../../controllers/repuestos_action.php', { method: 'POST', body: fd });
                        const data = await response.json();
                        if (data.status === 'success') {
                            Swal.fire({ icon: 'success', title: '¡Eliminado!', text: data.message, confirmButtonColor: '#004ba8' }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#004ba8' });
                        }
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de red.', confirmButtonColor: '#004ba8' });
                    }
                }
            });
        }
        
        // --- Lógica Importación con IA ---
        document.addEventListener('DOMContentLoaded', function() {
            // Auto open modal si viene '?ai_import=true'
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('ai_import') === 'true') {
                const aiModal = new bootstrap.Modal(document.getElementById('aiImportModal'));
                aiModal.show();
                window.history.replaceState({}, document.title, 'repuestos.php'); // Clean URL
            }

            const aiBulkInput = document.getElementById('aiBulkInput');
            const aiUploadZone = document.getElementById('aiUploadZone');
            const aiBulkLoading = document.getElementById('aiBulkLoading');
            const aiBulkPreview = document.getElementById('aiBulkPreview');
            const aiBulkTableBody = document.getElementById('aiBulkTableBody');
            const btnConfirmBulkSave = document.getElementById('btnConfirmBulkSave');
            let extractedData = [];

            if (aiBulkInput) {
                aiBulkInput.addEventListener('change', function(e) {
                    if(e.target.files.length > 0) {
                        processAiFile(e.target.files[0]);
                    }
                });
            }

            function processAiFile(file) {
                aiUploadZone.classList.add('d-none');
                aiBulkLoading.classList.remove('d-none');
                
                const formData = new FormData();
                formData.append('file', file);
                
                fetch('../../controllers/ai_import_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    aiBulkLoading.classList.add('d-none');
                    if (data.status === 'success') {
                        extractedData = data.data;
                        renderAiBulkTable(extractedData);
                        aiBulkPreview.classList.remove('d-none');
                        btnConfirmBulkSave.classList.remove('d-none');
                    } else {
                        aiUploadZone.classList.remove('d-none');
                        Swal.fire('Error IA', data.message || 'Error al procesar el archivo.', 'error');
                        aiBulkInput.value = '';
                    }
                })
                .catch(error => {
                    aiBulkLoading.classList.add('d-none');
                    aiUploadZone.classList.remove('d-none');
                    Swal.fire('Error de red', 'No se pudo conectar con el servicio de IA.', 'error');
                    console.error(error);
                });
            }

            function renderAiBulkTable(dataArray) {
                aiBulkTableBody.innerHTML = '';
                dataArray.forEach((item, index) => {
                    const isLowConfidence = item.confianza < 80 || !item.id_marca || item.id_marca === '';
                    const rowClass = isLowConfidence ? 'table-warning' : '';
                    
                    const tr = document.createElement('tr');
                    tr.className = rowClass;
                    tr.innerHTML = `
                        <td><input type="text" class="form-control form-control-sm" value="${item.codigo_barras || item.codigo || ''}" onchange="updateExtractedData(${index}, 'codigo_barras', this.value)"></td>
                        <td><input type="text" class="form-control form-control-sm" value="${item.nombre || ''}" onchange="updateExtractedData(${index}, 'nombre', this.value)"></td>
                        <td>
                            <select class="form-select form-select-sm" onchange="updateExtractedData(${index}, 'id_marca', this.value)">
                                <option value="">Seleccionar...</option>
                                <?php foreach($marcas as $m): ?>
                                    <option value="<?= $m['id_marca'] ?>" ${item.id_marca == <?= $m['id_marca'] ?> ? 'selected' : ''}><?= htmlspecialchars($m['nombre']) ?></option>
                                <?php endforeach; ?>
                                ${(!item.id_marca && item.marca_texto) ? `<option value="" class="text-success fw-bold" selected>✨ Crear: ${item.marca_texto}</option>` : ''}
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm" onchange="updateExtractedData(${index}, 'id_categoria', this.value)">
                                <option value="">Seleccionar...</option>
                                <?php foreach($categorias as $c): ?>
                                    <option value="<?= $c['id_categoria'] ?>" ${item.id_categoria == <?= $c['id_categoria'] ?> ? 'selected' : ''}><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                                ${(!item.id_categoria && item.categoria_texto) ? `<option value="" class="text-success fw-bold" selected>✨ Crear: ${item.categoria_texto}</option>` : ''}
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" value="${item.modelo || ''}" onchange="updateExtractedData(${index}, 'modelo', this.value)"></td>
                        <td class="text-center fw-bold ${item.confianza >= 80 ? 'text-success' : 'text-danger'}">${item.confianza || 0}%</td>
                        <td class="text-center"><button class="btn btn-sm btn-outline-danger" onclick="removeAiRow(${index})"><i class="fa-solid fa-trash"></i></button></td>
                    `;
                    aiBulkTableBody.appendChild(tr);
                });
            }

            window.updateExtractedData = function(index, field, value) {
                extractedData[index][field] = value;
            };

            window.removeAiRow = function(index) {
                extractedData.splice(index, 1);
                renderAiBulkTable(extractedData);
                if (extractedData.length === 0) {
                    btnConfirmBulkSave.classList.add('d-none');
                }
            };

            if (btnConfirmBulkSave) {
                btnConfirmBulkSave.addEventListener('click', function() {
                    // Validar mínimos: Debe tener nombre y (o id_marca o marca_texto)
                    const invalid = extractedData.some(item => !item.nombre || (!item.id_marca && !item.marca_texto));
                    if (invalid) {
                        Swal.fire('Atención', 'Todos los repuestos deben tener al menos un Nombre y una Marca (o texto de marca detectado).', 'warning');
                        return;
                    }

                    btnConfirmBulkSave.disabled = true;
                    btnConfirmBulkSave.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

                    fetch('../../controllers/bulk_insert_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ repuestos: extractedData })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Éxito', data.message, 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            btnConfirmBulkSave.disabled = false;
                            btnConfirmBulkSave.innerHTML = 'Confirmar y Guardar';
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        btnConfirmBulkSave.disabled = false;
                        btnConfirmBulkSave.innerHTML = 'Confirmar y Guardar';
                        Swal.fire('Error de red', 'Problema al guardar masivamente.', 'error');
                    });
                });
            }
            
            const importModalEl = document.getElementById('aiImportModal');
            if (importModalEl) {
                importModalEl.addEventListener('hidden.bs.modal', function () {
                    aiUploadZone.classList.remove('d-none');
                    aiBulkLoading.classList.add('d-none');
                    aiBulkPreview.classList.add('d-none');
                    btnConfirmBulkSave.classList.add('d-none');
                    if (aiBulkInput) aiBulkInput.value = '';
                    extractedData = [];
                });
            }
        });
    </script>
</body>
</html>
