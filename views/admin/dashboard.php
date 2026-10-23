<?php
// Iniciamos la sesión para poder recordar quién es el usuario que entró al sistema
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}
// Incluimos la configuración para conectarnos a la base de datos
require_once '../../config/database.php';
require_once '../../models/MarcaModel.php';
require_once '../../models/CategoriaModel.php';
require_once '../../models/RepuestoModel.php';

$marcaModel = new MarcaModel($conn);
$catModel = new CategoriaModel($conn);
$repModel = new RepuestoModel($conn);


// Preparamos listas vacías donde guardaremos las opciones para los filtros de búsqueda
$marcas_filter = [];
$cat_filter = [];
$mod_filter = [];

// Intentamos realizar las siguientes acciones en la base de datos
try {
    // 1. Buscamos todas las marcas de repuestos ordenadas por su nombre
    $rM = $conn->query("SELECT id_marca as id, nombre FROM MARCA ORDER BY nombre");
    if($rM) while($row = $rM->fetch_assoc()) $marcas_filter[] = $row; // Guardamos cada marca en nuestra lista
    
    // 2. Buscamos todas las categorías de repuestos ordenadas por nombre
    $rC = $conn->query("SELECT id_categoria as id, nombre FROM CATEGORIA ORDER BY nombre");
    if($rC) while($row = $rC->fetch_assoc()) $cat_filter[] = $row; // Guardamos cada categoría en la lista

    // 3. Vamos a buscar los 6 repuestos más recientes para mostrarlos en la página principal
    $repuestos = [];
    $query = "SELECT r.*, m.nombre as marca_nombre, i.ruta_archivo as imagen_url 
              FROM REPUESTO r 
              LEFT JOIN MARCA m ON r.id_marca = m.id_marca 
              LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
              LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
              ORDER BY r.id_repuesto DESC LIMIT 6"; // "LIMIT 6" significa que solo queremos 6 resultados
    $resRepuestos = $conn->query($query);
    if ($resRepuestos) {
        while ($r = $resRepuestos->fetch_assoc()) {
            $repuestos[] = $r; // Añadimos cada repuesto encontrado a nuestra lista
        }
    }
    
    // 4. Vamos a buscar las 7 marcas principales (que estén activas y tengan una imagen) para mostrarlas de forma destacada
    $marcas_dashboard = [];
    $query_marcas = "SELECT m.*, i.ruta_archivo as imagen_url 
                     FROM MARCA m 
                     INNER JOIN MARCA_IMAGEN mi ON m.id_marca = mi.id_marca AND mi.imagen_principal = 1
                     INNER JOIN IMAGEN i ON mi.id_imagen = i.id_imagen 
                     WHERE m.estado = 'ACTIVO' AND i.ruta_archivo IS NOT NULL AND i.ruta_archivo != ''
                     ORDER BY m.id_marca DESC LIMIT 7";
    $resMarcasDash = $conn->query($query_marcas);
    if ($resMarcasDash) {
        while ($m = $resMarcasDash->fetch_assoc()) {
            $marcas_dashboard[] = $m;
        }
    }
    
    // 5. Buscamos todos los modelos de moto que existan para poder filtrar por modelo
    $rMo = $conn->query("SELECT DISTINCT compatibilidades as modelo_moto FROM REPUESTO WHERE compatibilidades != '' ORDER BY compatibilidades");
    if($rMo) while($row = $rMo->fetch_assoc()) $mod_filter[] = $row;
} catch (Exception $e) {
    // Si algo sale mal al buscar en la base de datos, lo ignoramos por ahora
}

// Esto es para comprobar si el usuario inició sesión correctamente. 
// Cuando el sistema esté listo, quitaremos los comentarios de aquí abajo:
/*
if (!isset($_SESSION['user_id'])) {
    // Si no ha iniciado sesión, lo devolvemos a la página principal
    header('Location: ../../public/index.php');
    exit;
}
*/
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }

        /* ===== TOPBAR GLOBAL ===== */
        .topbar-global {
            height: 110px;
            background: #ffffff;
            border-bottom: 1px solid #eaeaea;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1050;
        }
        .topbar-logo { display: inline-flex; align-items: center; height: 150%; text-decoration: none; }
        .topbar-logo img { max-height: 90px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }

        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .search-container input { border: none; outline: none; box-shadow: none; }
        
        /* ===== SIDEBAR ===== */
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.05rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }
        
        /* ===== MAIN CONTENT ===== */
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); display: flex; flex-direction: column; background-color: #f8f9fc; }
        
        /* Hero Banner */
        .hero-banner { background: linear-gradient(135deg, #0640aa 0%, #1e5abf 100%); color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 40px; top: 25%; height: 50%; width: 4px; background-color: #60a5fa; border-radius: 4px; }
        .hero-gears { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); pointer-events: none; }

        /* Section Title */
        .section-title { font-weight: 700; font-size: 1.1rem; color: #1f2937; margin-bottom: 18px; }

        /* Brands */
        .brands-card { background: #fff; border: 1px solid #f0f1f3; border-radius: 14px; padding: 18px 28px; margin-bottom: 32px; }
        .brand-item { font-weight: 800; font-size: 1.1rem; font-style: italic; color: #4b5563; cursor: pointer; transition: opacity 0.2s; }
        .brand-item:hover { opacity: 0.7; }
        .brand-bajaj { color: #004ba8; }
        .brand-tvs { color: #111; }
        .brand-tvs .star { color: #dc2626; }
        .brand-yamaha { color: #dc2626; }
        .brand-honda { color: #dc2626; }
        .brand-suzuki { color: #004ba8; }
        .brand-hero { color: #111; }
        .brand-hero .accent { color: #dc2626; }
        .brand-ktm { color: #ea580c; }
        .brand-akt { color: #111; }
        .btn-ver-todas { border: 1px solid #bfdbfe; color: #2563eb; background: #fff; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; text-decoration: none; transition: background 0.2s; }
        .btn-ver-todas:hover { background: #eff6ff; color: #1d4ed8; }

        /* Parts */
        .part-card { background: #fff; border-radius: 14px; padding: 18px 20px; border: 1px solid #f0f1f3; position: relative; transition: box-shadow 0.2s, transform 0.2s; }
        .part-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.06); transform: translateY(-2px); }
        .part-card .btn-edit { position: absolute; top: 16px; right: 16px; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #bfdbfe; background: #fff; color: #2563eb; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; font-size: 0.8rem; }
        .part-card .btn-edit:hover { background: #eff6ff; }
        .part-code { font-weight: 700; color: #111; font-size: 1rem; margin-bottom: 4px; }
        .part-name { font-size: 0.88rem; color: #4b5563; line-height: 1.35; margin-bottom: 2px; }
        .part-brand { color: #2563eb; font-weight: 600; }
        .part-model { font-size: 0.82rem; color: #9ca3af; font-style: italic; }
        .part-img { width: 120px; height: 100px; object-fit: contain; flex-shrink: 0; }
        .part-card .btn-arrow { position: absolute; bottom: 16px; right: 16px; color: #c4c9d4; font-size: 1rem; text-decoration: none; transition: color 0.2s; }
        .part-card .btn-arrow:hover { color: #2563eb; }

        /* Load More */
        .btn-load-more { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #bfdbfe; color: #2563eb; font-weight: 600; padding: 10px 22px; border-radius: 10px; text-decoration: none; transition: background 0.2s; }
        .btn-load-more:hover { background: #eff6ff; }
    </style>
</head>
<body>

    <!-- ===== TOPBAR GLOBAL (full-width con logo) ===== -->
    <header class="topbar-global px-4 d-flex align-items-center justify-content-between">
        <!-- Logo -->
        <a href="dashboard.php" class="topbar-logo text-decoration-none">
            <img src="../../public/assets/images/logo_motoverify.png?v=<?php echo time(); ?>" alt="Motoverify">
        </a>

        <!-- Search + Actions -->
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

    <!-- ===== SIDEBAR (debajo del topbar) ===== -->
    <?php require_once 'sidebar.php'; ?>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">

        <div class="p-4 flex-grow-1">

            <!-- Hero Banner -->
            <div class="hero-banner rounded-3 p-4 ps-5 mb-4 d-flex align-items-center">
                <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                    <h2 class="fw-bold mb-2">Bienvenido a Motoverify</h2>
                    <p class="mb-0 opacity-75" style="font-size: 1rem;">Encuentra y verifica repuestos de forma rápida y confiable.</p>
                </div>
                <div class="hero-gears">
                    <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                    <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
                </div>
            </div>

            <!-- Marcas Destacadas -->
            <h3 class="section-title">Marcas destacadas</h3>
            <div class="brands-card d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center flex-wrap" style="gap: 60px;">
                    <?php if (empty($marcas_dashboard)): ?>
                        <div class="text-muted">No hay marcas registradas.</div>
                    <?php else: ?>
                        <?php foreach ($marcas_dashboard as $m): ?>
                            <a href="repuestos.php?marca=<?php echo $m['id_marca']; ?>" style="text-decoration: none;" title="Ver repuestos de <?php echo htmlspecialchars($m['nombre']); ?>">
                                <?php if (!empty($m['imagen_url'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($m['imagen_url']); ?>" alt="<?php echo htmlspecialchars($m['nombre']); ?>" style="height: 85px; width: auto; max-width: 200px; object-fit: contain; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                                <?php else: ?>
                                    <div class="brand-item text-dark fw-bold" style="font-size: 1.1rem; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#004ba8'" onmouseout="this.style.color=''"><?php echo htmlspecialchars(strtoupper($m['nombre'])); ?></div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="marcas.php" class="btn-ver-todas d-inline-flex align-items-center gap-2">
                    Ver todas <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
                </a>
            </div>

            <!-- Repuestos -->
            <h3 class="section-title">Repuestos</h3>
            <div class="row g-4 mb-4">

                <?php if (empty($repuestos)): ?>
                    <div class="col-12 text-center text-muted py-5">No hay repuestos registrados recientes.</div>
                <?php else: ?>
                    <?php foreach ($repuestos as $rep): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="part-card h-100">
                            <a href="repuestos.php" class="btn-edit text-decoration-none" title="Ver/Editar"><i class="fa-solid fa-pen"></i></a>
                            <div class="d-flex align-items-center gap-3 mt-2">
                                <div class="flex-grow-1" style="word-break: break-word;">
                                    <?php $codigo_mostrar = !empty($rep['codigo_barras']) ? $rep['codigo_barras'] : ($rep['codigo_interno'] ?? 'N/A'); ?>
                                    <div class="part-code"><?php echo htmlspecialchars($codigo_mostrar); ?></div>
                                    <div class="part-name">
                                        <span class="part-brand"><?php echo htmlspecialchars($rep['marca_nombre'] ?? 'Sin Marca'); ?></span> 
                                        <?php echo htmlspecialchars($rep['nombre']); ?>
                                    </div>
                                    <div class="part-model"><?php echo htmlspecialchars($rep['compatibilidades'] ?? ''); ?></div>
                                    <div class="mt-2 d-flex gap-2">
                                        <span class="badge bg-success" style="font-size: 0.85rem;">$<?php echo number_format($rep['precio'] ?? 0, 2); ?></span>
                                        <span class="badge <?php echo ($rep['stock'] ?? 0) > 0 ? 'bg-primary' : 'bg-danger'; ?>" style="font-size: 0.85rem;">Stock: <?php echo htmlspecialchars($rep['stock'] ?? 0); ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($rep['imagen_url'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($rep['imagen_url']); ?>" alt="Repuesto" class="part-img" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="part-img d-flex flex-column align-items-center justify-content-center text-muted" style="background-color: #f0f0f0; opacity: 0.7;">
                                        <i class="fa-solid fa-image" style="font-size: 1.2rem;"></i>
                                        <span style="font-size: 0.6rem; margin-top: 4px;">Sin foto</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="../../public/ficha_tecnica.php?id=<?php echo $rep['id_repuesto']; ?>" class="btn-arrow" title="Ver Ficha Técnica"><i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

            <!-- Load More -->
            <div class="text-center">
                <a href="catalogo.php" class="btn-load-more">Ver más repuestos <i class="fa-solid fa-chevron-right" style="font-size:0.75rem;"></i></a>
            </div>

        </div>
    </main>

    <!-- Modal de Filtros (Admin) -->
    <div class="modal fade" id="filtrosAdminModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold text-primary">Filtros de Búsqueda</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Usamos un form independiente que envía a repuestos.php -->
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

    <!-- Modal IA (Reconocimiento Visual y Código de Barras) -->
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Html5Qrcode para escaneo de códigos de barras -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    
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

            // --- IA Camera + Barcode Scanner Script (igual al de index.php) ---
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
            let html5QrCode = null;

            // Función para detener la cámara/escáner
            function stopMediaTracks() {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then((ignore) => {
                        html5QrCode.clear();
                    }).catch((err) => {
                        console.error("Failed to stop QR scanner", err);
                    });
                }
            }

            // Función para iniciar la cámara con escáner de código de barras
            function startCamera() {
                stopMediaTracks();
                
                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode("qrReader");
                }
                
                previewContainer.style.display = 'none';
                cameraContainer.style.display = 'block';
                if(btnAnalyze) btnAnalyze.disabled = true;
                photoBlob = null;
                if(statusDiv) statusDiv.classList.add('d-none');
                if(errorMsg) errorMsg.classList.add('d-none');
                if(successMsg) successMsg.classList.add('d-none');
                
                const facingMode = useFrontCamera ? "user" : "environment";
                
                html5QrCode.start(
                    { facingMode: facingMode },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    (decodedText, decodedResult) => {
                        // CÓDIGO ENCONTRADO
                        stopMediaTracks();
                        searchBarcode(decodedText);
                    },
                    (errorMessage) => {
                        // ignorar
                    })
                .catch((err) => {
                    console.error("No se pudo iniciar la cámara", err);
                    alert("Error al acceder a la cámara. Asegúrate de dar los permisos.");
                });
            }

            // Función reutilizable para buscar código de barras en la BD
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

            // Eventos de Cámara
            if(btnStartCamera) btnStartCamera.addEventListener('click', startCamera);
            if(btnSwitchCamera) btnSwitchCamera.addEventListener('click', () => { useFrontCamera = !useFrontCamera; startCamera(); });

            // Tomar la foto manual (para IA)
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

            // Resetear el modal al cerrar
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

            // Botón para descartar foto y volver al inicio del modal
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

            // Función reutilizable para manejar el archivo de imagen (intenta leer código de barras primero)
            function handleImageFile(file) {
                if (file) {
                    if (!html5QrCode) {
                        html5QrCode = new Html5Qrcode("qrReader");
                    }
                    
                    if(statusDiv) statusDiv.classList.remove('d-none');
                    if(loadingDiv) {
                        loadingDiv.classList.remove('d-none');
                        loadingDiv.innerHTML = '<div class="spinner-border me-2" role="status"></div> Buscando código de barras en la imagen...';
                    }
                    if(errorMsg) errorMsg.classList.add('d-none');
                    if(successMsg) successMsg.classList.add('d-none');
                    if(btnAnalyze) btnAnalyze.disabled = true;

                    // 1. Intentar leer un código de barras de la imagen localmente
                    html5QrCode.scanFile(file, false)
                        .then(decodedText => {
                            // Encontró un código de barras, buscarlo
                            searchBarcode(decodedText);
                        })
                        .catch(err => {
                            // No encontró código, proceder a mostrar imagen para IA
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

            // Manejar selección de archivo
            if(fileInput) {
                fileInput.addEventListener('change', function() {
                    handleImageFile(this.files[0]);
                });
            }

            // --- DRAG AND DROP ---
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

            // --- PASTE ---
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

            // Manejar análisis de IA (llamada real al backend)
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
    </script>
</body>
</html>
