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

        /* --- Layout --- */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .sidebar-brand .logo-icon { position: relative; width: 40px; height: 35px; }
        .sidebar-brand .logo-icon .fa-gear { font-size: 28px; color: #666; position: absolute; left: 0; top: 0; }
        .sidebar-brand .logo-icon .fa-wrench { font-size: 18px; color: #111; position: absolute; left: 10px; top: 5px; transform: rotate(45deg); }
        .sidebar-brand .logo-icon .fa-circle { font-size: 10px; color: #ff0000; position: absolute; left: 22px; top: 12px; }
        .sidebar-brand .logo-text { font-size: 1.8rem; font-weight: 800; font-style: italic; letter-spacing: -1px; line-height: 1; }
        .sidebar-brand .text-moto { color: #111; }
        .sidebar-brand .text-verify { color: #004ba8; }

        .btn-new {
            margin: 10px 24px 20px;
            border: 1px solid #e0e0e0;
            background: #ffffff;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #111;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-new:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-new i { color: #004ba8; font-size: 1.2rem; }

        .sidebar-nav {
            flex: 1;
            padding: 0 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            color: #4b5563;
            font-weight: 600;
            font-size: 1.05rem;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        
        .nav-item.active {
            background-color: #f0f7ff;
            color: #004ba8;
            position: relative;
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: -16px; top: 10%;
            height: 80%; width: 4px;
            background-color: #004ba8;
            border-radius: 0 4px 4px 0;
        }

        .sidebar-bottom {
            padding: 20px 16px;
            border-top: 1px solid #eaeaea;
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #fbfbfd;
        }

        /* --- Topbar --- */
        .topbar {
            height: 80px;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            background-color: #ffffff;
            border-bottom: 1px solid #eaeaea;
        }
        
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-avatar {
            width: 45px; height: 45px;
            background-color: #e5e7eb;
            color: #6b7280;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            text-decoration: none;
        }

        /* --- Profile Body --- */
        .dashboard-body {
            padding: 30px 40px;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            border: 1px solid #eaeaea;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
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
            width: 80px; height: 80px;
            background-color: #004ba8;
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
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

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="dashboard.php" class="sidebar-brand">
                <div class="logo-icon">
                    <i class="fa-solid fa-gear"></i>
                    <i class="fa-solid fa-wrench"></i>
                    <i class="fa-solid fa-circle"></i>
                </div>
                <div class="logo-text">
                    <span class="text-moto">moto</span><span class="text-verify">verify</span>
                </div>
            </a>

            <div class="btn-new">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo</span>
            </div>

            <nav class="sidebar-nav">
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

            <div class="sidebar-bottom sidebar-nav">
                <a href="#" class="nav-item">
                    <i class="fa-regular fa-circle-question"></i> Ayuda
                </a>
                <a href="#" class="nav-item active">
                    <i class="fa-solid fa-gear"></i> Configuración
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-actions">
                    <a href="perfil.php" class="user-avatar" title="Perfil">
                        <i class="fa-regular fa-user"></i>
                    </a>
                </div>
            </header>

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
                            <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <hr class="my-4" style="border-color: #eaeaea;">
                        <h5 class="mb-3" style="font-weight: 700; color: #111;">Seguridad</h5>

                        <div class="mb-4">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Deja en blanco para mantener actual">
                            <div class="form-text">Si no deseas cambiar tu contraseña, deja este campo vacío.</div>
                        </div>

                        <button type="submit" class="btn-save">Guardar Cambios</button>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script>
        document.getElementById('perfilForm').addEventListener('submit', async function(e) {
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
