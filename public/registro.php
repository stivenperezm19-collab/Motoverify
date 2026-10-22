<?php
session_start();
// Si el usuario ya está autenticado, redirigir a su panel
if (isset($_SESSION['user_id']) && isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'Cliente') {
        header('Location: ../views/cliente/dashboard.php');
        exit;
    } elseif ($_SESSION['rol'] === 'Administrador') {
        header('Location: ../views/admin/dashboard.php');
        exit;
    } elseif ($_SESSION['rol'] === 'Vendedor') {
        header('Location: ../views/vendedor/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta de Cliente - Motoverify</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #004ba8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            color: #333;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            max-width: 650px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .auth-header {
            background: linear-gradient(135deg, #004ba8 0%, #1e5abf 100%);
            padding: 35px 30px;
            color: white;
            text-align: center;
            position: relative;
        }

        .auth-header img {
            max-height: 70px;
            width: auto;
            object-fit: contain;
            margin-bottom: 12px;
            background: white;
            padding: 8px 16px;
            border-radius: 12px;
        }

        .auth-body {
            padding: 35px;
        }

        .form-control-custom {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control-custom:focus {
            border-color: #004ba8;
            box-shadow: 0 0 0 3px rgba(0, 75, 168, 0.12);
        }

        .btn-register-submit {
            background: linear-gradient(135deg, #004ba8 0%, #1e5abf 100%);
            color: white;
            border: none;
            padding: 14px;
            font-weight: 700;
            font-size: 1.05rem;
            border-radius: 12px;
            width: 100%;
            transition: opacity 0.2s, transform 0.2s;
        }
        .btn-register-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            color: white;
        }

        .security-badge {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <!-- Encabezado con branding de Motoverify -->
        <div class="auth-header">
            <a href="index.php">
                <img src="assets/images/logo_motoverify.png" alt="Motoverify">
            </a>
            <h3 class="fw-bold mb-1">Crea tu Cuenta de Cliente</h3>
            <p class="mb-0 opacity-75 small">Accede a catálogo completo, realiza pedidos y haz seguimiento en tiempo real.</p>
        </div>

        <div class="auth-body">
            <div class="security-badge">
                <i class="fa-solid fa-shield-halved fs-5"></i>
                <span>Registro rápido y seguro para clientes de Motoverify. Rol asignado automáticamente.</span>
            </div>

            <form id="registerForm">
                <input type="hidden" name="action" value="register">

                <div class="row g-3">
                    <!-- Nombre Completo -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">Nombre Completo *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                            <input type="text" name="nombre" class="form-control form-control-custom border-start-0 ps-0" placeholder="Ej: Carlos Ramírez" required>
                        </div>
                    </div>

                    <!-- Correo Electrónico -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-muted">Correo Electrónico *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" id="regEmail" name="correo" class="form-control form-control-custom border-start-0 ps-0" placeholder="tu@correo.com" required>
                        </div>
                        <div id="emailFeedback" class="small mt-1 d-none"></div>
                    </div>

                    <!-- Documento -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-muted">Cédula / Documento</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-id-card text-muted"></i></span>
                            <input type="text" name="documento" class="form-control form-control-custom border-start-0 ps-0" placeholder="Ej: 1083456789">
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-muted">Contraseña *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" id="regPassword" name="password" class="form-control form-control-custom border-start-0 ps-0" placeholder="Mínimo 6 caracteres" minlength="6" required>
                        </div>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-muted">Confirmar Contraseña *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock-check text-muted"></i></span>
                            <input type="password" id="regPasswordConfirm" name="password_confirm" class="form-control form-control-custom border-start-0 ps-0" placeholder="Repite tu contraseña" minlength="6" required>
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-muted">Teléfono / Celular</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                            <input type="text" name="telefono" class="form-control form-control-custom border-start-0 ps-0" placeholder="Ej: 3101234567">
                        </div>
                    </div>

                    <!-- Ciudad -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-muted">Ciudad / Municipio</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-location-dot text-muted"></i></span>
                            <input type="text" name="ciudad" class="form-control form-control-custom border-start-0 ps-0" placeholder="Ej: Neiva, Huila">
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">Dirección de Entrega</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-map-pin text-muted"></i></span>
                            <input type="text" name="direccion" class="form-control form-control-custom border-start-0 ps-0" placeholder="Calle, Carrera, Número, Barrio o Conjunto">
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" id="btnSubmitRegister" class="btn-register-submit shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <span id="btnText">Crear Mi Cuenta</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <p class="text-muted small mb-2">¿Ya tienes una cuenta registrada?</p>
                <a href="index.php?login=1" class="fw-bold text-primary text-decoration-none">
                    <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Iniciar Sesión en Motoverify
                </a>
            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="text-secondary small text-decoration-none">
                    <i class="fa-solid fa-house me-1"></i> Volver a la Tienda Pública
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Envío asíncrono con validaciones
    const registerForm = document.getElementById('registerForm');
    const btnSubmit = document.getElementById('btnSubmitRegister');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const pass = document.getElementById('regPassword').value;
        const passConf = document.getElementById('regPasswordConfirm').value;

        if (pass.length < 6) {
            Swal.fire({
                icon: 'warning',
                title: 'Contraseña insegura',
                text: 'La contraseña debe contener al menos 6 caracteres.'
            });
            return;
        }

        if (pass !== passConf) {
            Swal.fire({
                icon: 'warning',
                title: 'Contraseñas diferentes',
                text: 'La contraseña y la confirmación no coinciden. Por favor verifícalas.'
            });
            return;
        }

        btnSubmit.disabled = true;
        btnText.textContent = 'Creando cuenta...';
        btnSpinner.classList.remove('d-none');

        const formData = new FormData(registerForm);

        fetch('../controllers/cliente_auth_action.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnText.textContent = 'Crear Mi Cuenta';
            btnSpinner.classList.add('d-none');

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Cuenta creada!',
                    text: data.message,
                    confirmButtonColor: '#004ba8',
                    confirmButtonText: 'Ir a mi panel'
                }).then(() => {
                    window.location.href = data.redirect;
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo crear la cuenta',
                    text: data.message
                });
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            btnText.textContent = 'Crear Mi Cuenta';
            btnSpinner.classList.add('d-none');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al conectar con el servidor.' });
        });
    });
    </script>
</body>
</html>
