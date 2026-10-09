<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Vendedor') {
    header('Location: ../../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/AdministradorModel.php';

$userId = $_SESSION['user_id'];
$user = ['nombre' => '', 'email' => ''];

try {
    $adminModel = new AdministradorModel($conn);
    $adminData = $adminModel->getById($userId);
    if ($adminData) {
        $user = $adminData;
    }
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fbfbfd; color: #333; overflow-x: hidden; }
        .topbar-global { height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 90px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.05rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); padding: 30px; display: flex; flex-direction: column; background-color: #fbfbfd; }
        
        .profile-card { background: #ffffff; border-radius: 16px; padding: 40px; border: 1px solid #eaeaea; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); max-width: 600px; margin: 0 auto; }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eaeaea; }
        .profile-header-avatar { width: 80px; height: 80px; background-color: #004ba8; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; }
        .profile-title { font-size: 1.5rem; font-weight: 700; color: #111; margin: 0; }
        .profile-subtitle { color: #6b7280; margin: 0; }
        .form-label { font-weight: 600; color: #4b5563; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #d1d5db; }
        .form-control:focus { border-color: #004ba8; box-shadow: 0 0 0 0.25rem rgba(0, 75, 168, 0.1); }
        .btn-save { background-color: #004ba8; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; transition: background-color 0.2s; width: 100%; }
        .btn-save:hover { background-color: #00367a; }
    </style>
</head>
<body>

    <header class="topbar-global px-4 d-flex align-items-center justify-content-between">
        <a href="dashboard.php" class="topbar-logo text-decoration-none">
            <img src="../../public/assets/images/logo_motoverify.png" alt="Motoverify">
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="perfil.php" class="d-flex align-items-center me-3 bg-light rounded-pill px-3 py-1 border border-secondary-subtle text-decoration-none">
                <i class="fa-solid fa-circle-user text-primary fs-4 me-2"></i>
                <span class="text-dark fw-bold" style="font-size: 0.95rem;"><?= ucfirst(explode('@', $_SESSION['email'])[0]) ?></span>
            </a>
            <a href="../../controllers/logout.php" class="btn btn-outline-danger btn-sm fw-bold"><i class="fa fa-sign-out me-2"></i>Salir</a>
        </div>
    </header>

    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
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
                    <label for="nombre" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>

                <hr class="my-4" style="border-color: #eaeaea;">
                <h5 class="mb-3" style="font-weight: 700; color: #111;">Seguridad</h5>

                <input type="hidden" id="current_password" name="current_password">

                <div class="mb-4">
                    <label for="new_password" class="form-label">Nueva Contraseña</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Deja en blanco para mantener actual">
                    <div class="form-text">Si no deseas cambiar tu contraseña, deja este campo vacío.</div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="btn btn-light w-50 fw-semibold py-2" style="border-radius: 10px; color: #4b5563; border: 1px solid #d1d5db;" onclick="toggleEditMode(false)">Cancelar</button>
                    <button type="submit" class="btn-save w-50 m-0">Guardar</button>
                </div>
            </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleEditMode(edit) {
            const viewMode = document.getElementById('view-mode-section');
            const editMode = document.getElementById('perfilForm');
            const alertContainer = document.getElementById('alert-container');
            
            alertContainer.innerHTML = '';
            
            if(edit) {
                viewMode.style.display = 'none';
                editMode.style.display = 'block';
            } else {
                editMode.reset();
                editMode.style.display = 'none';
                viewMode.style.display = 'block';
            }
        }

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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
                    modal.hide();
                    
                    document.getElementById('current_password').value = password;
                    document.getElementById('auth_password').value = '';
                    
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
                    
                    document.querySelector('#view-mode-section .fw-bold.text-dark:nth-of-type(1)').innerText = document.getElementById('nombre').value;
                    document.querySelectorAll('#view-mode-section .fw-bold.text-dark')[1].innerText = document.getElementById('email').value;

                    // Actualizar también el nombre de la barra superior sin recargar la página
                    const newEmail = document.getElementById('email').value;
                    const newName = newEmail.split('@')[0];
                    const capitalized = newName.charAt(0).toUpperCase() + newName.slice(1);
                    document.querySelector('.topbar-global .d-flex .text-dark.fw-bold').innerText = capitalized;

                    if (data.clearPassword) {
                        document.getElementById('current_password').value = '';
                        document.getElementById('new_password').value = '';
                    } else {
                        document.getElementById('current_password').value = '';
                    }
                    
                    setTimeout(() => {
                        toggleEditMode(false);
                        alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`; 
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
</body>
</html>
