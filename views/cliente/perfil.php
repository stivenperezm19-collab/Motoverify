<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/ClienteModel.php';

$clienteModel = new ClienteModel($conn);
$id_cliente = (int)$_SESSION['user_id'];
$cliente = $clienteModel->getById($id_cliente);
$stats = $clienteModel->getEstadisticas($id_cliente);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Motoverify</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }

        .topbar-global {
            height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050;
        }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 85px; width: auto; object-fit: contain; }
        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }

        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.02rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.15rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }

        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); padding: 30px; background-color: #f8f9fc; }

        .profile-card { background: #fff; border-radius: 14px; padding: 30px; border: 1px solid #f0f1f3; box-shadow: 0 4px 12px rgba(0,0,0,0.02); margin-bottom: 25px; }
        .form-control-custom { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; font-size: 0.95rem; }
        .form-control-custom:focus { border-color: #004ba8; box-shadow: 0 0 0 3px rgba(0, 75, 168, 0.1); }
    </style>
</head>
<body>

    <?php require_once 'topbar.php'; ?>
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="fw-bold text-dark mb-1">Mi Perfil de Cliente</h2>
        <p class="text-muted mb-4">Administra tu información personal, direcciones de entrega y credenciales de acceso.</p>

        <div class="row g-4">
            <!-- Columna Formulario Perfil -->
            <div class="col-12 col-lg-8">
                <div class="profile-card">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-id-card text-primary me-2"></i>Información Personal y de Entrega</h5>

                    <form id="profileForm">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Nombre Completo *</label>
                                <input type="text" name="nombre" class="form-control form-control-custom" value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Correo Electrónico</label>
                                <input type="email" class="form-control form-control-custom bg-light" value="<?= htmlspecialchars($cliente['correo'] ?? '') ?>" readonly title="El correo electrónico está vinculado a tu cuenta">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Cédula / Documento</label>
                                <input type="text" name="documento" class="form-control form-control-custom" placeholder="Ej: 1083456789" value="<?= htmlspecialchars($cliente['documento'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Teléfono / Celular de Contacto</label>
                                <input type="text" name="telefono" class="form-control form-control-custom" placeholder="Ej: 3101234567" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Ciudad / Municipio</label>
                                <input type="text" name="ciudad" class="form-control form-control-custom" placeholder="Ej: Neiva, Huila" value="<?= htmlspecialchars($cliente['ciudad'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-muted">Dirección de Entrega Predeterminada</label>
                                <input type="text" name="direccion" class="form-control form-control-custom" placeholder="Calle / Carrera / Número / Barrio" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="fw-bold text-dark mb-2"><i class="fa-solid fa-lock text-warning me-2"></i>Seguridad y Cambio de Contraseña</h5>
                        <p class="text-muted small mb-3">Si deseas cambiar tu contraseña, completa los siguientes campos. Si no deseas cambiarla, déjalos en blanco.</p>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-muted">Contraseña Actual</label>
                                <input type="password" name="current_password" class="form-control form-control-custom" placeholder="••••••••">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-muted">Nueva Contraseña</label>
                                <input type="password" name="new_password" class="form-control form-control-custom" placeholder="Mínimo 6 caracteres">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-muted">Confirmar Nueva Contraseña</label>
                                <input type="password" name="confirm_password" class="form-control form-control-custom" placeholder="Repite la nueva contraseña">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" id="btnSaveProfile" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Columna Resumen Cuenta -->
            <div class="col-12 col-lg-4">
                <div class="profile-card text-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold mx-auto mb-3" style="width: 80px; height: 80px; background: #004ba8; font-size: 2rem;">
                        <?= strtoupper(mb_substr($cliente['nombre'] ?? 'C', 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cliente['nombre'] ?? '') ?></h5>
                    <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-1 rounded-pill mb-3">Rol: Cliente</span>
                    <p class="text-muted small mb-4">
                        <i class="fa-regular fa-calendar-check me-1"></i> Miembro desde <?= date('d/m/Y', strtotime($cliente['fecha_registro'] ?? 'now')) ?>
                    </p>

                    <div class="bg-light rounded-3 p-3 text-start border">
                        <div class="d-flex justify-content-between text-muted small mb-2">
                            <span>Pedidos realizados:</span>
                            <span class="fw-bold text-dark"><?= $stats['total_pedidos'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mb-2">
                            <span>Pedidos en curso:</span>
                            <span class="fw-bold text-warning"><?= $stats['pedidos_pendientes'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Total invertido:</span>
                            <span class="fw-bold text-success">$<?= number_format($stats['total_gastado'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const btn = document.getElementById('btnSaveProfile');
        const origHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';

        const formData = new FormData(this);

        fetch('../../controllers/cliente_perfil_action.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = origHTML;

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Atención',
                    text: data.message
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = origHTML;
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
        });
    });
    </script>
</body>
</html>
