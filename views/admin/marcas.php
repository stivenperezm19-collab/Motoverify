<?php
session_start();
require_once '../../config/database.php';

$marcas = [];
try {
    $result = $conn->query("SELECT * FROM marcas ORDER BY nombre ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $marcas[] = $row;
        }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Marcas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; color: #333; overflow-x: hidden; }
        
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
        
        /* Main Content */
        .main-content { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; background-color: #fbfbfd; }
        
        /* Topbar */
        .topbar { height: 80px; background-color: #ffffff; border-bottom: 1px solid #eaeaea; }
        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .search-container input { border: none; outline: none; box-shadow: none; }
        
        /* Hero Banner */
        .hero-banner { background-color: #0640aa; color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 0; top: 20%; height: 60%; width: 4px; background-color: #3b82f6; border-radius: 0 4px 4px 0; }
        .hero-gears { position: absolute; right: 5%; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.1); }
        .hero-gears i { position: absolute; }
        
        /* Cards */
        .brand-card { border: 1px solid #eaeaea; border-radius: 12px; min-height: 120px; transition: all 0.2s; position: relative; }
        .brand-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.05); transform: translateY(-2px); }
        .brand-name { font-size: 1.8rem; font-weight: 900; font-style: italic; color: #004ba8; }
        
        .action-btns { position: absolute; top: 10px; right: 10px; display: flex; gap: 5px; z-index: 10; }
        .btn-action { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 1px solid; cursor: pointer; font-size: 0.85rem; transition: all 0.2s; }
        .btn-edit { border-color: #bfdbfe; background: white; color: #2563eb; }
        .btn-edit:hover { background: #eff6ff; }
        .btn-delete { border-color: #fecaca; background: white; color: #dc2626; }
        .btn-delete:hover { background: #fef2f2; }
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
            <button class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold border" style="border-radius: 12px; padding: 12px;">
                <i class="fa-solid fa-plus text-primary"></i> Nuevo
            </button>
        </div>

        <nav class="flex-grow-1">
            <a href="dashboard.php" class="nav-item">
                <i class="fa-solid fa-house"></i> Inicio
            </a>
            <a href="marcas.php" class="nav-item active">
                <i class="fa-regular fa-star"></i> Marcas
            </a>
            <a href="repuestos.php" class="nav-item">
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
        
        <header class="topbar px-4 d-flex align-items-center justify-content-between">
            <div class="flex-grow-1 d-flex justify-content-center">
                <!-- Espacio central si se requiere -->
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="search-container d-flex align-items-center me-3" style="width: 350px;">
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

        <div class="p-4 flex-grow-1">
            
            <div class="hero-banner rounded p-4 mb-4 d-flex align-items-center justify-content-between">
                <div class="ps-3 position-relative" style="z-index: 2;">
                    <h2 class="fw-bold mb-1">Marcas</h2>
                    <p class="mb-0 opacity-75">Todas las marcas disponibles.</p>
                </div>
                
                <button class="btn btn-light text-primary fw-bold px-4 py-2" data-bs-toggle="modal" data-bs-target="#marcaModal" onclick="openModal('add')" style="z-index: 2; border-radius: 8px;">
                    <i class="fa-solid fa-plus me-2"></i> Nueva Marca
                </button>
                
                <div class="hero-gears">
                    <i class="fa-solid fa-gear" style="font-size: 120px; right: -20px; top: -60px;"></i>
                    <i class="fa-solid fa-gear" style="font-size: 70px; right: 80px; top: -30px;"></i>
                </div>
            </div>

            <div class="row g-4">
                <?php if (empty($marcas)): ?>
                    <div class="col-12 text-center text-muted py-5">No hay marcas registradas.</div>
                <?php else: ?>
                    <?php foreach ($marcas as $marca): ?>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a href="repuestos.php?id_marca=<?php echo $marca['id']; ?>" class="brand-card bg-white d-flex align-items-center justify-content-center text-decoration-none">
                            <div class="action-btns">
                                <div class="btn-action btn-delete" title="Eliminar" onclick="event.preventDefault(); event.stopPropagation(); deleteMarca(<?php echo $marca['id']; ?>, '<?php echo addslashes(htmlspecialchars($marca['nombre'])); ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </div>
                                <div class="btn-action btn-edit" title="Editar" onclick="event.preventDefault(); event.stopPropagation(); openModal('edit', <?php echo $marca['id']; ?>, '<?php echo addslashes(htmlspecialchars($marca['nombre'])); ?>')">
                                    <i class="fa-solid fa-pen"></i>
                                </div>
                            </div>
                            <span class="brand-name"><?php echo htmlspecialchars(strtoupper($marca['nombre'])); ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- Modal Marca -->
    <div class="modal fade" id="marcaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="modal-title fw-bold" id="modalTitle">Nueva Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="marcaForm">
                    <div class="modal-body p-4">
                        <input type="hidden" id="marca_id" name="id">
                        <input type="hidden" id="action" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold text-secondary">Nombre de la Marca <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required style="border-radius: 10px; padding: 12px 15px;">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4 px-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSave" style="border-radius: 10px; font-weight: 600; background-color: #004ba8; border: none;">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const marcaModal = new bootstrap.Modal(document.getElementById('marcaModal'));
        
        function openModal(action, id = '', nombre = '') {
            document.getElementById('action').value = action;
            document.getElementById('marca_id').value = id;
            document.getElementById('nombre').value = nombre;
            document.getElementById('modalTitle').innerText = action === 'add' ? 'Nueva Marca' : 'Editar Marca';
            if(action === 'add') marcaModal.show();
            if(action === 'edit') marcaModal.show();
        }

        document.getElementById('marcaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSave = document.getElementById('btnSave');
            btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            btnSave.disabled = true;

            try {
                const response = await fetch('../../public/marcas_action.php', { method: 'POST', body: new FormData(this) });
                const data = await response.json();
                
                if (data.status === 'success') {
                    marcaModal.hide();
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, confirmButtonColor: '#004ba8' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#004ba8' });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de red.', confirmButtonColor: '#004ba8' });
            } finally {
                btnSave.innerHTML = 'Guardar';
                btnSave.disabled = false;
            }
        });

        function deleteMarca(id, nombre) {
            Swal.fire({
                title: '¿Estás seguro?', text: `Vas a eliminar la marca "${nombre}".`, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
                    try {
                        const response = await fetch('../../public/marcas_action.php', { method: 'POST', body: fd });
                        const data = await response.json();
                        if (data.status === 'success') {
                            Swal.fire({ icon: 'success', title: '¡Eliminada!', text: data.message, confirmButtonColor: '#004ba8' }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'No se puede eliminar', text: data.message, confirmButtonColor: '#004ba8' });
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
