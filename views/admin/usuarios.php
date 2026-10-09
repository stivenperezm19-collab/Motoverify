<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../config/database.php';
require_once '../../models/AdministradorModel.php';

$adminModel = new AdministradorModel($conn);
$usuarios = $adminModel->getAll();

$roles = [];
$resR = $conn->query("SELECT id_rol, nombre_rol FROM ROL ORDER BY nombre_rol");
if ($resR) {
    while ($row = $resR->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }
        .topbar-global { height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        .topbar-logo { display: inline-flex; align-items: center; height: 150%; text-decoration: none; }
        .topbar-logo img { max-height: 90px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.05rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); padding: 30px; background-color: #f8f9fc; }
        .content-card { background: #fff; border-radius: 14px; padding: 25px; border: 1px solid #f0f1f3; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .status-activo { background-color: #d1fae5; color: #065f46; }
        .status-inactivo { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <header class="topbar-global px-4 d-flex align-items-center justify-content-between">
        <a href="dashboard.php" class="topbar-logo text-decoration-none">
            <img src="../../public/assets/images/logo_motoverify.png" alt="Motoverify">
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="perfil.php" class="d-flex align-items-center justify-content-center bg-light text-secondary rounded-circle text-decoration-none" style="width: 45px; height: 45px; font-size: 1.2rem; border: 1px solid #e0e0e0; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#e9ecef'" onmouseout="this.style.backgroundColor='#f8f9fa'">
                <i class="fa-regular fa-user"></i>
            </a>
        </div>
    </header>

    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0 text-dark">Gestión de Usuarios</h2>
            <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openModal()">
                <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
            </button>
        </div>

        <div class="content-card">
            <table id="usersTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $u): ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= $u['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($u['rol'] ?? 'Sin rol') ?></span></td>
                            <td>
                                <span class="status-badge <?= $u['estado'] === 'Activo' ? 'status-activo' : 'status-inactivo' ?>">
                                    <?= $u['estado'] ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" onclick='openModal(<?= json_encode($u) ?>)' title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <?php if($u['estado'] === 'Activo'): ?>
                                    <button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleStatus(<?= $u['id'] ?>, 'Inactivo')" title="Desactivar">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-success ms-1" onclick="toggleStatus(<?= $u['id'] ?>, 'Activo')" title="Activar">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Usuario -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" name="id_administrador" id="userId">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre Completo</label>
                            <input type="text" class="form-control" name="nombre" id="userNombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control" name="email" id="userEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rol</label>
                            <select class="form-select" name="id_rol" id="userRol" required>
                                <option value="">Seleccione un rol...</option>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <input type="password" class="form-control" name="password" id="userPassword">
                            <div class="form-text text-muted" id="passwordHelp"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Guardar Usuario</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });
        });

        const userModal = new bootstrap.Modal(document.getElementById('userModal'));

        function openModal(user = null) {
            document.getElementById('userForm').reset();
            if (user) {
                document.getElementById('modalTitle').innerText = 'Editar Usuario';
                document.getElementById('userId').value = user.id;
                document.getElementById('userNombre').value = user.nombre;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userRol').value = user.id_rol;
                document.getElementById('userPassword').required = false;
                document.getElementById('passwordHelp').innerText = 'Déjelo en blanco si no desea cambiar la contraseña.';
            } else {
                document.getElementById('modalTitle').innerText = 'Nuevo Usuario';
                document.getElementById('userId').value = '';
                document.getElementById('userPassword').required = true;
                document.getElementById('passwordHelp').innerText = 'La contraseña es obligatoria para nuevos usuarios.';
            }
            userModal.show();
        }

        function saveUser() {
            const form = document.getElementById('userForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const isEdit = formData.get('id_administrador') !== '';
            formData.append('action', isEdit ? 'update' : 'create');

            fetch('../../controllers/usuarios_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Éxito', data.message, 'success').then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Problema de conexión con el servidor', 'error');
            });
        }

        function toggleStatus(id, newStatus) {
            const title = newStatus === 'Inactivo' ? '¿Desactivar usuario?' : '¿Activar usuario?';
            const text = newStatus === 'Inactivo' ? 'El usuario no podrá acceder al sistema.' : 'El usuario recuperará el acceso al sistema.';
            const confirmBtn = newStatus === 'Inactivo' ? 'Sí, desactivar' : 'Sí, activar';
            const color = newStatus === 'Inactivo' ? '#d33' : '#28a745';

            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: color,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmBtn,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('action', 'toggle_status');
                    fd.append('id_administrador', id);
                    fd.append('estado', newStatus);

                    fetch('../../controllers/usuarios_action.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Completado', data.message, 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
