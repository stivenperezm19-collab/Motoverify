<?php
// Iniciamos la sesión para poder identificar al usuario (administrador)
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}

// Conectamos a la base de datos
require_once '../../config/database.php';
// Cargamos las herramientas para manejar la información de los administradores
require_once '../../models/AdministradorModel.php';

// Obtenemos el ID del usuario actual de la sesión. 
// Si no hay sesión (porque estamos probando), usamos el número 1 por defecto.
$userId = $_SESSION['user_id'] ?? 1;

// Preparamos un espacio vacío para guardar la información del usuario
$user = ['nombre' => '', 'email' => ''];
try {
    // Usamos el "AdministradorModel" para buscar los datos del usuario en la base de datos
    $adminModel = new AdministradorModel($conn);
    $adminData = $adminModel->getById($userId); // Buscamos por su ID
    if ($adminData) {
        $user = $adminData; // Si lo encontramos, guardamos sus datos aquí
    }
} catch (Exception $e) {
    // Si ocurre un error al buscar, simplemente lo ignoramos por ahora
}

// --- DATOS PARA FILTROS DE BÚSQUEDA ---
$marcas_filter = [];
$cat_filter = [];
$mod_filter = [];
try {
    $rM = $conn->query("SELECT id_marca as id, nombre FROM MARCA ORDER BY nombre");
    if($rM) while($row = $rM->fetch_assoc()) $marcas_filter[] = $row;
    
    $rC = $conn->query("SELECT id_categoria as id, nombre FROM CATEGORIA ORDER BY nombre");
    if($rC) while($row = $rC->fetch_assoc()) $cat_filter[] = $row;
    
    $rMo = $conn->query("SELECT DISTINCT compatibilidades as modelo_moto FROM REPUESTO WHERE compatibilidades != '' ORDER BY compatibilidades");
    if($rMo) while($row = $rMo->fetch_assoc()) $mod_filter[] = $row;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Mi Perfil</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            overflow-x: hidden;
        }

        /* ===== TOPBAR GLOBAL ===== */
        .topbar-global { height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
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

        /* --- Profile Body --- */
        .dashboard-body {
            padding: 30px 40px;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            border: 1px solid #eaeaea;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            max-width: 600px;
            margin: 0 auto;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eaeaea;
        }

        .profile-header-avatar {
            width: 80px;
            height: 80px;
            background-color: #004ba8;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }

        .profile-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111;
            margin: 0;
        }

        .profile-subtitle {
            color: #6b7280;
            margin: 0;
        }

        .form-label {
            font-weight: 600;
            color: #4b5563;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
        }

        .form-control:focus {
            border-color: #004ba8;
            box-shadow: 0 0 0 0.25rem rgba(0, 75, 168, 0.1);
        }

        .btn-save {
            background-color: #004ba8;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: background-color 0.2s;
            width: 100%;
        }

        .btn-save:hover {
            background-color: #00367a;
        }
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

            <!-- Profile Body -->
            <div class="dashboard-body">

                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-header-avatar">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div>
                            <h2 class="profile-title">Mi Perfil</h2>
                            <p class="profile-subtitle">Gestiona tu información personal y contraseña</p>
                        </div>
                    </div>

                    <div id="alert-container"></div>

                    <!-- VIEW MODE -->
                    <div id="view-mode-section">
                        <div class="mb-4">
                            <label class="form-label text-muted mb-1">Nombre de Usuario</label>
                            <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($user['nombre'] ?? ''); ?></div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted mb-1">Correo Electrónico</label>
                            <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                        </div>
                        <div class="mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-primary w-100 fw-semibold py-2 mb-3" style="border-radius: 10px; border-width: 2px;" data-bs-toggle="modal" data-bs-target="#authModal">
                                <i class="fa-solid fa-pen-to-square me-2"></i> Hacer Cambios
                            </button>
                            <a href="../../controllers/logout.php" class="btn btn-outline-danger w-100 fw-semibold py-2" style="border-radius: 10px; border-width: 2px;">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>

                    <!-- EDIT MODE -->
                    <form id="perfilForm" style="display: none;">
                        <div class="mb-4">
                            <label for="nombre" class="form-label">Nombre de Usuario <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label">Correo Electrónico <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <hr class="my-4" style="border-color: #eaeaea;">
                        <h5 class="mb-3" style="font-weight: 700; color: #111;">Seguridad</h5>

                        <!-- Hidden field for current password, verified via modal -->
                        <input type="hidden" id="current_password" name="current_password">

                        <div class="mb-4">
                            <label for="new_password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="new_password" name="new_password"
                                placeholder="Deja en blanco para mantener actual">
                            <div class="form-text">Si no deseas cambiar tu contraseña, deja este campo vacío.</div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-light w-50 fw-semibold py-2" style="border-radius: 10px; color: #4b5563; border: 1px solid #d1d5db;" onclick="toggleEditMode(false)">Cancelar</button>
                            <button type="submit" class="btn-save w-50 m-0">Guardar</button>
                        </div>
                    </form>
                </div>

            </div>
        </main>

    <!-- Auth Modal -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Verificar Identidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted" style="font-size: 0.9rem;">Por seguridad, ingresa tu contraseña para poder editar tu perfil.</p>
                    <div id="authAlert" class="alert alert-danger d-none py-2 px-3" style="font-size: 0.85rem;"></div>
                    <form id="authForm">
                        <input type="password" id="auth_password" class="form-control mb-3" placeholder="Contraseña actual" required autofocus>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold" style="border-radius: 10px;" id="btnAuthVerify">Verificar y Continuar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleEditMode(edit) {
            const viewMode = document.getElementById('view-mode-section');
            const editMode = document.getElementById('perfilForm');
            const alertContainer = document.getElementById('alert-container');
            
            alertContainer.innerHTML = ''; // Limpiar alertas al cambiar de modo
            
            if(edit) {
                viewMode.style.display = 'none';
                editMode.style.display = 'block';
            } else {
                // Restaurar y ocultar form
                editMode.reset();
                editMode.style.display = 'none';
                viewMode.style.display = 'block';
            }
        }

        // Handle Modal Authentication
        document.getElementById('authForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnVerify = document.getElementById('btnAuthVerify');
            const authAlert = document.getElementById('authAlert');
            const password = document.getElementById('auth_password').value;
            
            btnVerify.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            btnVerify.disabled = true;
            authAlert.classList.add('d-none');

            try {
                const formData = new FormData();
                formData.append('password', password);
                
                const response = await fetch('../../controllers/verify_password.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    // Cierra el modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
                    modal.hide();
                    
                    // Pasa la contraseña al formulario oculto y muestra el modo edición
                    document.getElementById('current_password').value = password;
                    document.getElementById('auth_password').value = ''; // Limpiar
                    
                    toggleEditMode(true);
                } else {
                    authAlert.innerText = data.message;
                    authAlert.classList.remove('d-none');
                }
            } catch (error) {
                authAlert.innerText = 'Error de conexión.';
                authAlert.classList.remove('d-none');
            } finally {
                btnVerify.innerHTML = 'Verificar y Continuar';
                btnVerify.disabled = false;
            }
        });

        document.getElementById('perfilForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const btnSave = document.querySelector('.btn-save');
            const originalText = btnSave.innerHTML;
            btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            btnSave.disabled = true;

            const formData = new FormData(this);
            const alertContainer = document.getElementById('alert-container');
            alertContainer.innerHTML = '';

            try {
                const response = await fetch('../../controllers/perfil_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    
                    // Update view mode display
                    document.querySelector('#view-mode-section .fw-bold.text-dark:nth-of-type(1)').innerText = document.getElementById('nombre').value;
                    document.querySelectorAll('#view-mode-section .fw-bold.text-dark')[1].innerText = document.getElementById('email').value;

                    if (data.clearPassword) {
                        document.getElementById('current_password').value = '';
                        document.getElementById('new_password').value = '';
                    } else {
                        document.getElementById('current_password').value = '';
                    }
                    
                    // Volver a modo vista después de 1 segundo
                    setTimeout(() => {
                        toggleEditMode(false);
                        alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`; // Mantener alerta visible un momento más
                    }, 1500);

                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            } catch (error) {
                alertContainer.innerHTML = `<div class="alert alert-danger">Ocurrió un error al procesar la solicitud.</div>`;
            } finally {
                btnSave.innerHTML = originalText;
                btnSave.disabled = false;
            }
        });
    </script>

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
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Categoría</label>
                    <select name="categoria" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach($cat_filter as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Modelo de Moto</label>
                    <select name="modelo" class="form-select">
                        <option value="">Todos los modelos</option>
                        <?php foreach($mod_filter as $mod): ?>
                            <option value="<?= htmlspecialchars($mod['modelo_moto']) ?>"><?= htmlspecialchars($mod['modelo_moto']) ?></option>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sincronizar input de barra superior con modal de filtros
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
    </script>
</body>

</html>
