<?php
session_start();
// Si el usuario ya está autenticado, redirigir a su panel correspondiente
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
    <title>Crear Cuenta - Motoverify</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(circle at 50% 15%, #184294 0%, #0c1c42 55%, #050b1a 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            color: #333;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient background watermarks matching index.php */
        .bg-decorations {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 1;
        }
        .gear-bg-1 {
            position: absolute;
            top: 5%;
            left: -60px;
            font-size: 240px;
            color: rgba(255, 255, 255, 0.025);
            transform: rotate(20deg);
        }
        .gear-bg-2 {
            position: absolute;
            bottom: 5%;
            right: -60px;
            font-size: 260px;
            color: rgba(255, 255, 255, 0.025);
            transform: rotate(-15deg);
        }
        .moto-bg-1 {
            position: absolute;
            top: 45%;
            right: 4%;
            font-size: 160px;
            color: rgba(255, 255, 255, 0.02);
            transform: rotate(5deg);
        }

        /* Branding Logo Wrapper */
        .brand-header {
            position: relative;
            z-index: 10;
            margin-bottom: 20px;
            text-align: center;
        }
        .brand-header img {
            height: 75px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.35));
            transition: transform 0.2s ease;
        }
        .brand-header img:hover {
            transform: scale(1.03);
        }

        /* Main Card Container */
        .register-container {
            width: 100%;
            max-width: 660px;
            position: relative;
            z-index: 10;
            margin: 0 auto;
        }

        .modal-login-card {
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            padding: 40px 45px 35px 45px;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 576px) {
            body {
                padding: 15px 10px;
            }
            .modal-login-card {
                padding: 30px 20px 25px 20px;
                border-radius: 18px;
            }
            .brand-header img {
                height: 60px;
            }
        }

        /* Curved Top Background with Watermark Gears */
        .modal-login-bg-top {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 120px;
            background-color: #0640aa;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 50% 100%, 0 90%);
            border-top-left-radius: 22px;
            border-top-right-radius: 22px;
            z-index: 1;
        }

        /* Bottom Background with Subtle Watermarks */
        .modal-login-bg-bottom {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 150px;
            background-color: #f8f9fc;
            border-bottom-left-radius: 22px;
            border-bottom-right-radius: 22px;
            z-index: 0;
        }

        .login-content {
            position: relative;
            z-index: 10;
        }

        /* Top-Right Close Button */
        .btn-close-card {
            position: absolute;
            top: -10px; right: -10px;
            width: 36px; height: 36px;
            background-color: #0d2859;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #ff3333; font-size: 1.15rem;
            text-decoration: none;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            transition: transform 0.2s, background-color 0.2s, color 0.2s;
            z-index: 20;
            cursor: pointer;
        }
        .btn-close-card:hover {
            transform: scale(1.1);
            color: #ff0000;
            background-color: #07193b;
        }

        /* Avatar Container & Circle */
        .avatar-container {
            text-align: center;
            margin-bottom: 12px;
            margin-top: 10px;
        }
        .avatar-circle {
            width: 82px; height: 82px;
            background-color: #d8eaff;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            color: #0066ff;
            font-size: 2.1rem;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 102, 255, 0.25);
            border: 3px solid #ffffff;
        }
        .avatar-badge {
            position: absolute;
            top: -2px; right: -4px;
            background-color: #0066ff;
            color: white;
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.82rem;
            border: 2px solid #ffffff;
        }

        /* Title with Underline Pill */
        .login-title {
            text-align: center;
            font-weight: 800;
            color: #0b2253;
            font-size: 1.65rem;
            margin-bottom: 26px;
            position: relative;
            letter-spacing: -0.5px;
        }
        .login-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 42px;
            height: 3px;
            background-color: #0066ff;
            border-radius: 2px;
        }

        /* Form Labels matching Login Modal */
        .form-label {
            font-weight: 700;
            color: #0b2253;
            font-size: 0.92rem;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .form-label .label-left {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .form-label .icon-tag {
            color: #0066ff;
            font-size: 0.9rem;
        }

        /* Form Control Custom matching Login Modal */
        .form-control-custom {
            width: 100%;
            padding: 11px 15px;
            border: 2px solid #3b82f6; /* Bright distinct blue border */
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1e293b;
            outline: none;
            background-color: #fcfcfd;
            transition: box-shadow 0.2s, border-color 0.2s, background-color 0.2s;
        }
        .form-control-custom:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.18);
            border-color: #1d4ed8;
            background-color: #ffffff;
        }
        .form-control-custom::placeholder {
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Password toggle visibility button */
        .toggle-password-btn {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 4px 6px;
            font-size: 0.95rem;
            transition: color 0.15s;
        }
        .toggle-password-btn:hover {
            color: #0066ff;
        }

        /* Submit Button matching Login Modal */
        .btn-submit {
            background-color: #062b80;
            color: #ffffff;
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.08rem;
            letter-spacing: 0.2px;
            transition: background-color 0.2s, transform 0.1s, box-shadow 0.2s;
            box-shadow: 0 6px 18px rgba(6, 43, 128, 0.28);
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: #041c5c;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(6, 43, 128, 0.38);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .btn-submit:disabled {
            background-color: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Alert error banner matching login modal */
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #f87171;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Small subtitle pill */
        .info-pill {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 30px;
            padding: 5px 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>

    <!-- Ambient background watermarks -->
    <div class="bg-decorations" aria-hidden="true">
        <i class="fa-solid fa-gear gear-bg-1"></i>
        <i class="fa-solid fa-gear gear-bg-2"></i>
        <i class="fa-solid fa-motorcycle moto-bg-1"></i>
    </div>

    <!-- Motoverify Branding -->
    <div class="brand-header">
        <a href="index.php" title="Volver a la tienda principal">
            <img src="assets/images/logo_motoverify.png?v=<?= time() ?>" alt="Motoverify">
        </a>
    </div>

    <!-- Main Register Container -->
    <div class="register-container">
        <div class="modal-login-card">
            
            <!-- Curved Top with Gears Watermark -->
            <div class="modal-login-bg-top">
                <i class="fa-solid fa-gear position-absolute" style="color: rgba(255,255,255,0.06); font-size: 80px; top: -10px; left: -10px;"></i>
                <i class="fa-solid fa-gear position-absolute" style="color: rgba(255,255,255,0.06); font-size: 105px; top: -20px; right: -20px;"></i>
            </div>
            
            <!-- Bottom Background with Subtle Watermarks -->
            <div class="modal-login-bg-bottom">
                <i class="fa-solid fa-gear position-absolute" style="color: rgba(0,0,0,0.03); font-size: 150px; bottom: -30px; left: -30px;"></i>
                <i class="fa-solid fa-motorcycle position-absolute" style="color: rgba(0,0,0,0.03); font-size: 105px; bottom: 20px; right: 10px;"></i>
            </div>

            <div class="login-content">
                <!-- Close Button in Top-Right corner -->
                <a href="index.php" class="btn-close-card" title="Volver a la Tienda" aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </a>

                <!-- Centered Avatar with badge -->
                <div class="avatar-container">
                    <div class="avatar-circle">
                        <i class="fa-solid fa-user-plus"></i>
                        <div class="avatar-badge"><i class="fa-solid fa-gear"></i></div>
                    </div>
                </div>

                <!-- Title & Underline Pill -->
                <h2 class="login-title">Crear cuenta</h2>

                <!-- Subtle badge indicator -->
                <div class="text-center">
                    <div class="info-pill">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Registro exclusivo para Clientes de Motoverify</span>
                    </div>
                </div>

                <!-- Error Message Banner (JS) -->
                <div class="alert-error d-none" id="registerErrorMsg">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <span id="registerErrorText">Error</span>
                </div>

                <!-- Registration Form -->
                <form id="registerForm" novalidate>
                    <input type="hidden" name="action" value="register">

                    <div class="row g-3">
                        <!-- Nombre Completo -->
                        <div class="col-12">
                            <label for="regNombre" class="form-label">
                                <span class="label-left">
                                    Nombre Completo <span class="text-danger">*</span>
                                </span>
                                <i class="fa-solid fa-user icon-tag"></i>
                            </label>
                            <input type="text" id="regNombre" name="nombre" class="form-control-custom" placeholder="Ej: Carlos Ramírez" required autocomplete="name">
                        </div>

                        <!-- Correo Electrónico -->
                        <div class="col-12 col-md-6">
                            <label for="regEmail" class="form-label">
                                <span class="label-left">
                                    Usuario o Email <span class="text-danger">*</span>
                                </span>
                                <i class="fa-solid fa-envelope icon-tag"></i>
                            </label>
                            <input type="email" id="regEmail" name="correo" class="form-control-custom" placeholder="cliente@correo.com" required autocomplete="email">
                        </div>

                        <!-- Cédula / Documento -->
                        <div class="col-12 col-md-6">
                            <label for="regDocumento" class="form-label">
                                <span class="label-left">Cédula / Documento</span>
                                <i class="fa-solid fa-id-card icon-tag"></i>
                            </label>
                            <input type="text" id="regDocumento" name="documento" class="form-control-custom" placeholder="Ej: 1083456789">
                        </div>

                        <!-- Contraseña -->
                        <div class="col-12 col-md-6">
                            <label for="regPassword" class="form-label">
                                <span class="label-left">
                                    Contraseña <span class="text-danger">*</span>
                                </span>
                                <i class="fa-solid fa-lock icon-tag"></i>
                            </label>
                            <div class="position-relative">
                                <input type="password" id="regPassword" name="password" class="form-control-custom pe-5" placeholder="••••••••••••" minlength="6" required autocomplete="new-password">
                                <button type="button" class="toggle-password-btn" data-target="regPassword" title="Mostrar/ocultar contraseña" tabindex="-1">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div class="col-12 col-md-6">
                            <label for="regPasswordConfirm" class="form-label">
                                <span class="label-left">
                                    Confirmar Contraseña <span class="text-danger">*</span>
                                </span>
                                <i class="fa-solid fa-shield-halved icon-tag"></i>
                            </label>
                            <div class="position-relative">
                                <input type="password" id="regPasswordConfirm" name="password_confirm" class="form-control-custom pe-5" placeholder="••••••••••••" minlength="6" required autocomplete="new-password">
                                <button type="button" class="toggle-password-btn" data-target="regPasswordConfirm" title="Mostrar/ocultar contraseña" tabindex="-1">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Teléfono / Celular -->
                        <div class="col-12 col-md-6">
                            <label for="regTelefono" class="form-label">
                                <span class="label-left">Teléfono / Celular</span>
                                <i class="fa-solid fa-phone icon-tag"></i>
                            </label>
                            <input type="tel" id="regTelefono" name="telefono" class="form-control-custom" placeholder="Ej: 3101234567" autocomplete="tel">
                        </div>

                        <!-- Ciudad / Municipio -->
                        <div class="col-12 col-md-6">
                            <label for="regCiudad" class="form-label">
                                <span class="label-left">Ciudad / Municipio</span>
                                <i class="fa-solid fa-city icon-tag"></i>
                            </label>
                            <input type="text" id="regCiudad" name="ciudad" class="form-control-custom" placeholder="Ej: Neiva, Huila">
                        </div>

                        <!-- Dirección de Entrega -->
                        <div class="col-12">
                            <label for="regDireccion" class="form-label">
                                <span class="label-left">Dirección de Entrega</span>
                                <i class="fa-solid fa-map-location-dot icon-tag"></i>
                            </label>
                            <input type="text" id="regDireccion" name="direccion" class="form-control-custom" placeholder="Calle, Carrera, Número, Barrio o Conjunto" autocomplete="street-address">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center mt-4 pt-2">
                        <button type="submit" class="btn-submit" id="btnSubmitRegister">
                            <span id="btnText">Crear Mi Cuenta</span>
                            <span class="spinner-border spinner-border-sm d-none ms-2" id="btnSpinner" role="status" aria-hidden="true"></span>
                        </button>
                    </div>

                    <!-- Footer Options matching Login Modal -->
                    <div class="text-center mt-4 pt-3 border-top" style="border-color: #e2e8f0 !important;">
                        <span class="text-muted small">¿Ya tienes una cuenta registrada?</span><br>
                        <a href="index.php?login=1" class="fw-bold text-primary small text-decoration-none d-inline-flex align-items-center gap-1 mt-1">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Iniciar sesión en Motoverify
                        </a>
                    </div>

                    <div class="text-center mt-2">
                        <a href="index.php" class="text-secondary small text-decoration-none d-inline-flex align-items-center gap-1">
                            <i class="fa-solid fa-house"></i> Volver a la Tienda Pública
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        const btnSubmit = document.getElementById('btnSubmitRegister');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const errorBox = document.getElementById('registerErrorMsg');
        const errorText = document.getElementById('registerErrorText');

        // Toggle password visibility
        document.querySelectorAll('.toggle-password-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        function showError(msg) {
            errorText.textContent = msg;
            errorBox.classList.remove('d-none');
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideError() {
            errorBox.classList.add('d-none');
        }

        // Form Submission
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            hideError();

            const nombre = document.getElementById('regNombre').value.trim();
            const correo = document.getElementById('regEmail').value.trim();
            const pass = document.getElementById('regPassword').value;
            const passConf = document.getElementById('regPasswordConfirm').value;

            if (!nombre || !correo || !pass) {
                showError('Por favor completa los campos obligatorios (*).');
                return;
            }

            if (pass.length < 6) {
                showError('La contraseña debe contener al menos 6 caracteres.');
                return;
            }

            if (pass !== passConf) {
                showError('La confirmación de la contraseña no coincide.');
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
                        title: '¡Bienvenido a Motoverify!',
                        text: data.message,
                        confirmButtonColor: '#062b80',
                        confirmButtonText: 'Ir a mi Panel de Cliente',
                        allowOutsideClick: false
                    }).then(() => {
                        window.location.href = data.redirect || '../views/cliente/dashboard.php';
                    });
                } else {
                    showError(data.message || 'No se pudo completar el registro.');
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnText.textContent = 'Crear Mi Cuenta';
                btnSpinner.classList.add('d-none');
                showError('Ocurrió un error al conectar con el servidor. Inténtalo de nuevo.');
            });
        });
    });
    </script>
</body>
</html>
