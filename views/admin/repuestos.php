<?php
session_start();
require_once '../../config/database.php';

// Fetch Marcas for the dropdown
$marcas = [];
try {
    $resMarcas = $conn->query("SELECT id, nombre FROM marcas ORDER BY nombre ASC");
    if ($resMarcas) {
        while ($m = $resMarcas->fetch_assoc()) {
            $marcas[] = $m;
        }
    }
} catch (Exception $e) {}

// Fetch Repuestos with optional filter
$repuestos = [];
try {
    $filterMarca = isset($_GET['id_marca']) ? intval($_GET['id_marca']) : 0;
    
    $query = "SELECT r.*, m.nombre as marca_nombre FROM repuestos r LEFT JOIN marcas m ON r.id_marca = m.id";
    if ($filterMarca > 0) {
        $query .= " WHERE r.id_marca = $filterMarca";
    }
    $query .= " ORDER BY r.id DESC";
    
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
        .topbar-logo img { max-height: 85px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }
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
        .hero-banner { background-color: #0640aa; color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 0; top: 20%; height: 60%; width: 4px; background-color: #3b82f6; border-radius: 0 4px 4px 0; }
        .hero-gears { position: absolute; right: 5%; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.1); pointer-events: none; }
        
        /* Cards */
        .part-card { border: 1px solid #eaeaea; border-radius: 12px; transition: all 0.2s; }
        .part-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-3px); }
        .btn-action-card { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid; cursor: pointer; transition: all 0.2s; font-size: 0.8rem; }
        .btn-edit-card { border-color: #bfdbfe; background: white; color: #2563eb; }
        .btn-edit-card:hover { background: #eff6ff; }
        .btn-delete-card { border-color: #fecaca; background: white; color: #dc2626; }
        .btn-delete-card:hover { background: #fef2f2; }
    </style>
</head>
<body>

    <!-- Topbar Global -->
    <header class="topbar-global px-4 d-flex align-items-center justify-content-between">
        <a href="dashboard.php" class="topbar-logo text-decoration-none">
            <img src="assets/logo_motoverify.png?v=<?php echo time(); ?>" alt="Motoverify">
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="search-container d-flex align-items-center" style="width: 400px;">
                <input type="text" class="form-control" placeholder="Pulsar135/180/NS200" value="Pulsar135/180/NS200">
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

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="px-3 mb-3 mt-3">
            <button class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold border" style="border-radius: 12px; padding: 12px;">
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
            <a href="repuestos.php" class="nav-item active">
                <i class="fa-solid fa-puzzle-piece"></i> Repuestos
            </a>
            <a href="catalogo.php" class="nav-item">
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

        <div class="p-4 flex-grow-1">
            
            <div class="hero-banner rounded p-4 mb-4 d-flex align-items-center justify-content-between">
                <div class="ps-3 position-relative" style="z-index: 2;">
                    <h2 class="fw-bold mb-1">Repuestos</h2>
                    <p class="mb-0 opacity-75">Encuentra y verifica repuestos de forma rápida y confiable.</p>
                </div>
                
                <button class="btn btn-light text-primary fw-bold px-4 py-2" data-bs-toggle="modal" data-bs-target="#repuestoModal" onclick="openModal('add')" style="z-index: 2; border-radius: 8px;">
                    <i class="fa-solid fa-plus me-2"></i> Nuevo
                </button>
                
                <div class="hero-gears">
                    <i class="fa-solid fa-gear" style="font-size: 120px; position: absolute; right: -20px; top: -60px;"></i>
                    <i class="fa-solid fa-gear" style="font-size: 70px; position: absolute; right: 80px; top: -30px;"></i>
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
                                <button class="btn-action-card btn-delete-card" title="Eliminar" onclick="deleteRepuesto(<?php echo $rep['id']; ?>, '<?php echo addslashes(htmlspecialchars($rep['nombre'])); ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <button class="btn-action-card btn-edit-card" title="Editar" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($rep)); ?>)">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </div>

                            <div class="row g-0 flex-grow-1 align-items-center mt-2">
                                <div class="col-7 pe-2">
                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($rep['codigo'] ?? 'N/A'); ?></div>
                                    <div class="lh-sm mb-2">
                                        <span class="text-primary fw-bold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($rep['marca_nombre'] ?? 'Sin Marca'); ?></span><br>
                                        <span class="text-secondary" style="font-size: 0.85rem;"><?php echo htmlspecialchars($rep['nombre']); ?></span>
                                    </div>
                                    <div class="text-black-50" style="font-size: 0.8rem;"><?php echo htmlspecialchars($rep['modelo'] ?? ''); ?></div>
                                </div>
                                <div class="col-5 text-center">
                                    <?php 
                                        $imgUrl = !empty($rep['imagen_url']) ? $rep['imagen_url'] : 'https://via.placeholder.com/150?text=Repuesto';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Repuesto" class="img-fluid" style="max-height: 110px; object-fit: contain;">
                                </div>
                            </div>

                            <div class="text-end mt-2">
                                <i class="fa-solid fa-arrow-right text-muted" style="font-size: 1.1rem; cursor: pointer;" title="Ver detalles"></i>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($repuestos)): ?>
            <div class="text-center mt-5 mb-3">
                <button class="btn bg-white border text-primary fw-semibold px-4 py-2" style="border-radius: 10px;">
                    Ver más repuestos <i class="fa-solid fa-chevron-down ms-1"></i>
                </button>
            </div>
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
                <form id="repuestoForm">
                    <div class="modal-body p-4">
                        <input type="hidden" id="repuesto_id" name="id">
                        <input type="hidden" id="action" name="action" value="add">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Código <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3" id="codigo" name="codigo" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Marca <span class="text-danger">*</span></label>
                                <select class="form-select rounded-3" id="id_marca" name="id_marca" required>
                                    <option value="">Seleccione una marca</option>
                                    <?php foreach ($marcas as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Nombre del Repuesto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3" id="nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Modelo Compatibilidad</label>
                                <input type="text" class="form-control rounded-3" id="modelo" name="modelo" placeholder="Ej: Xr125L">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-secondary">URL de Imagen (Opcional)</label>
                                <input type="url" class="form-control rounded-3" id="imagen_url" name="imagen_url" placeholder="https://ejemplo.com/imagen.jpg">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const repuestoModal = new bootstrap.Modal(document.getElementById('repuestoModal'));
        
        function openModal(action, data = null) {
            document.getElementById('action').value = action;
            document.getElementById('repuestoForm').reset();
            
            if (action === 'add') {
                document.getElementById('modalTitle').innerText = 'Nuevo Repuesto';
                document.getElementById('repuesto_id').value = '';
            } else if (action === 'edit' && data) {
                document.getElementById('modalTitle').innerText = 'Editar Repuesto';
                document.getElementById('repuesto_id').value = data.id;
                document.getElementById('codigo').value = data.codigo || '';
                document.getElementById('id_marca').value = data.id_marca || '';
                document.getElementById('nombre').value = data.nombre || '';
                document.getElementById('modelo').value = data.modelo || '';
                document.getElementById('imagen_url').value = data.imagen_url || '';
            }
            repuestoModal.show();
        }

        document.getElementById('repuestoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSave = document.getElementById('btnSave');
            const originalText = btnSave.innerHTML;
            btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            btnSave.disabled = true;

            try {
                const response = await fetch('../../public/repuestos_action.php', { method: 'POST', body: new FormData(this) });
                const data = await response.json();
                
                if (data.status === 'success') {
                    repuestoModal.hide();
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, confirmButtonColor: '#004ba8' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error de Validación', text: data.message, confirmButtonColor: '#004ba8' });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de red o servidor.', confirmButtonColor: '#004ba8' });
            } finally {
                btnSave.innerHTML = originalText;
                btnSave.disabled = false;
            }
        });

        function deleteRepuesto(id, nombre) {
            Swal.fire({
                title: '¿Estás seguro?', text: `Se eliminará el repuesto "${nombre}".`, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
                    try {
                        const response = await fetch('../../public/repuestos_action.php', { method: 'POST', body: fd });
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
    </script>
</body>
</html>
