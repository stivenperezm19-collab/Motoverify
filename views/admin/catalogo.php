<?php
session_start();
require_once '../../config/database.php';

$categorias = [];
try {
    $resCategorias = $conn->query("SELECT * FROM categorias ORDER BY nombre ASC");
    if ($resCategorias) {
        while ($c = $resCategorias->fetch_assoc()) {
            $categorias[] = $c;
        }
    }
} catch (Exception $e) {}

// Default categories for the motorcycle diagram (used when DB categories exist)
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
        
        /* Sidebar */
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 0; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 20px 24px; display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .sidebar-brand .logo-icon { position: relative; width: 40px; height: 35px; }
        .sidebar-brand .logo-icon .fa-gear { font-size: 28px; color: #666; position: absolute; left: 0; top: 0; }
        .sidebar-brand .logo-icon .fa-wrench { font-size: 18px; color: #111; position: absolute; left: 10px; top: 5px; transform: rotate(45deg); }
        .sidebar-brand .logo-icon .fa-circle { font-size: 10px; color: #ff0000; position: absolute; left: 22px; top: 12px; }
        .sidebar-brand .logo-text { font-size: 1.8rem; font-weight: 800; font-style: italic; letter-spacing: -1px; line-height: 1; }
        
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.05rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }
        
        /* Main Content */
        .main-content { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; background-color: #f8f9fc; }
        
        /* Topbar */
        .topbar { height: 80px; background-color: #ffffff; border-bottom: 1px solid #eaeaea; }
        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .search-container input { border: none; outline: none; box-shadow: none; }
        
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
        }
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

    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="logo-icon">
                <i class="fa-solid fa-gear"></i>
                <i class="fa-solid fa-wrench"></i>
                <i class="fa-solid fa-circle"></i>
            </div>
            <div class="logo-text">
                <span style="color:#111">moto</span><span style="color:#004ba8">verify</span>
            </div>
        </a>

        <div class="px-3 mb-4 mt-2">
            <button class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold border" style="border-radius: 12px; padding: 12px;" onclick="openCatModal('add')">
                <i class="fa-solid fa-plus text-primary"></i> Nuevo
            </button>
        </div>

        <nav class="flex-grow-1">
            <a href="dashboard.php" class="nav-item">
                <i class="fa-solid fa-house"></i> Inicio
            </a>
            <a href="marcas.php" class="nav-item">
                <i class="fa-regular fa-star"></i> Marcas
            </a>
            <a href="repuestos.php" class="nav-item">
                <i class="fa-solid fa-puzzle-piece"></i> Repuestos
            </a>
            <a href="catalogo.php" class="nav-item active">
                <i class="fa-solid fa-book"></i> Catálogo
            </a>
        </nav>

        <div class="mt-auto border-top pt-3 pb-3">
            <a href="#" class="nav-item">
                <i class="fa-regular fa-circle-question"></i> Ayuda
            </a>
            <a href="perfil.php" class="nav-item">
                <i class="fa-solid fa-gear"></i> Configuración
            </a>
        </div>
    </aside>

    <main class="main-content">
        
        <!-- Topbar -->
        <header class="topbar px-4 d-flex align-items-center justify-content-between">
            <div class="flex-grow-1"></div>
            <div class="d-flex align-items-center gap-3">
                <div class="search-container d-flex align-items-center me-3" style="width: 350px;">
                    <input type="text" class="form-control" placeholder="Pulsar135/180/NS200">
                    <div class="vr mx-2"></div>
                    <span class="text-primary fw-semibold" style="cursor:pointer; white-space:nowrap;">
                        Filtro <i class="fa-solid fa-chevron-down ms-1"></i>
                    </span>
                </div>
                <button class="btn btn-primary d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 12px;">
                    <i class="fa-solid fa-camera fs-5"></i>
                </button>
                <a href="perfil.php" class="d-flex align-items-center justify-content-center bg-light text-secondary rounded-circle text-decoration-none" style="width: 45px; height: 45px; font-size: 1.2rem;">
                    <i class="fa-regular fa-user"></i>
                </a>
            </div>
        </header>

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
                            $icon = !empty($cat['icono']) ? $cat['icono'] : 'fa-solid fa-cube';
                            $idx++;
                        ?>
                        <div class="cat-item">
                            <div class="cat-icon-box" style="background-color: <?php echo $bgColor; ?>;">
                                <i class="<?php echo htmlspecialchars($icon); ?>"></i>
                            </div>
                            <div class="cat-item-info">
                                <p class="cat-item-title"><?php echo htmlspecialchars($cat['nombre']); ?></p>
                                <p class="cat-item-desc" title="<?php echo htmlspecialchars($cat['descripcion'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($cat['descripcion'] ?? 'Sin descripción'); ?>
                                </p>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="btn-edit-sm" title="Editar" onclick="openCatModal('edit', <?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn-edit-sm" title="Eliminar" style="border-color: #fecaca; background: #fef2f2; color: #dc2626;" onclick="deleteCategoria(<?php echo $cat['id']; ?>, '<?php echo addslashes(htmlspecialchars($cat['nombre'])); ?>')">
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
                            <img src="assets/motorcycle_diagram.png" alt="Diagrama de motocicleta" onerror="this.src='https://via.placeholder.com/700x400?text=Motocicleta';">

                            <!-- Labels positioned around motorcycle -->
                            <div class="map-label" style="top: 8%; left: 15%;">Sistema de combustible</div>
                            <div class="map-dot" style="top: 28%; left: 38%;"></div>

                            <div class="map-label" style="top: 8%; right: 8%;">Sistema de motor</div>
                            <div class="map-dot" style="top: 32%; right: 30%;"></div>

                            <div class="map-label" style="top: 38%; left: 5%;">Sistema eléctrico</div>
                            <div class="map-dot" style="top: 42%; left: 28%;"></div>

                            <div class="map-label" style="top: 38%; right: 0%;">Suspensión</div>
                            <div class="map-dot" style="top: 45%; right: 18%;"></div>

                            <div class="map-label" style="top: 62%; left: 8%;">Sistema de frenos</div>
                            <div class="map-dot" style="top: 60%; left: 30%;"></div>

                            <div class="map-label" style="top: 62%; right: 0%;">Sistema de transmisión</div>
                            <div class="map-dot" style="top: 58%; right: 22%;"></div>

                            <div class="map-label" style="bottom: 12%; left: 15%;">Ruedas y neumáticos</div>
                            <div class="map-dot" style="bottom: 18%; left: 32%;"></div>

                            <div class="map-label" style="bottom: 5%; left: 38%;">Carrocería y accesorios</div>

                            <div class="map-label" style="bottom: 12%; right: 5%;">Chasis y estructura</div>
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
                <form id="catForm">
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
                            <label class="form-label fw-semibold text-secondary">Ícono (FontAwesome)</label>
                            <input type="text" class="form-control rounded-3" id="cat_icono" name="icono" placeholder="Ej: fa-solid fa-gears">
                            <div class="form-text">Copia la clase del ícono de <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com</a>.</div>
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
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
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
                document.getElementById('cat_id').value = data.id;
                document.getElementById('cat_nombre').value = data.nombre || '';
                document.getElementById('cat_descripcion').value = data.descripcion || '';
                document.getElementById('cat_icono').value = data.icono || '';
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
                const res = await fetch('../../public/catalogo_action.php', { method: 'POST', body: new FormData(this) });
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
                        const res = await fetch('../../public/catalogo_action.php', { method: 'POST', body: fd });
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
                const res = await fetch('../../public/catalogo_action.php', { method: 'POST', body: fd });
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
    </script>
</body>
</html>
