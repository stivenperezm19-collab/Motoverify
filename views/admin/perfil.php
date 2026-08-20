<?php
session_start();
// Validación básica de sesión, descomentar cuando la autenticación esté lista si es necesario
/*
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/index.php');
    exit;
}
*/

require_once '../../config/database.php';

// Si no hay sesión (para pruebas mientras se termina el login), usamos un ID dummy
$userId = $_SESSION['user_id'] ?? 1;

$user = ['nombre' => '', 'email' => ''];
try {
    // Usamos id y no id_usuario basándonos en login_action.php
    $query = "SELECT id, nombre, email FROM usuarios WHERE id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Manejo silencioso o log
}
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
        .topbar-logo img { max-height: 85px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }

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
            <img src="assets/logo_motoverify.png?v=<?php echo time(); ?>" alt="Motoverify">
        </a>
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
            <a href="perfil.php" class="nav-item active">
                <i class="fa-solid fa-gear"></i> Configuración
            </a>
        </div>
    </aside>

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

                    <form id="perfilForm">
                        <div class="mb-4">
                            <label for="nombre" class="form-label">Nombre Completo <span
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

                        <div class="mb-4">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Deja en blanco para mantener actual">
                            <div class="form-text">Si no deseas cambiar tu contraseña, deja este campo vacío.</div>
                        </div>

                        <button type="submit" class="btn-save">Guardar Cambios</button>
                    </form>
                </div>

            </div>
        </main>

    <script>
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
                const response = await fetch('../../public/perfil_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    if (data.clearPassword) {
                        document.getElementById('password').value = '';
                    }
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
