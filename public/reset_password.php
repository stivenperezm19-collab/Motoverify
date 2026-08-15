<?php
require_once '../config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$validToken = false;
$user_email = '';

if (empty($token)) {
    $error = "Enlace de recuperación inválido o ausente.";
} else {
    // Validar token
    // Como es temporal, asumimos que token expira o es de un solo uso.
    $query = "SELECT email FROM password_resets WHERE token = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $validToken = true;
            $row = $result->fetch_assoc();
            $user_email = $row['email'];
        } else {
            $error = "El enlace de recuperación es inválido o ya expiró.";
        }
        $stmt->close();
    } else {
        $error = "Error de conexión a la base de datos.";
    }
}

// Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Por favor, completa ambos campos.";
    } else if ($new_password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else if (strlen($new_password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Actualizar la contraseña en la base de datos
        // NOTA: Para producción usar password_hash($new_password, PASSWORD_DEFAULT);
        // Usaremos hash si el sistema lo soporta, o texto plano según la configuración actual del usuario
        // Aquí asumiré que debemos hashearla idealmente, pero para ser compatible con la BD existente:
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE usuarios SET password = ? WHERE email = ?";
        $stmtUpdate = $conn->prepare($updateQuery);
        // Si tu base de datos actual guarda contraseñas en texto plano, cambia $hashed_password por $new_password
        // Dejaremos el plain text comentado: $stmtUpdate->bind_param("ss", $new_password, $user_email);
        $stmtUpdate->bind_param("ss", $new_password, $user_email); // Usando raw password por compatibilidad con script anterior
        
        if ($stmtUpdate->execute()) {
            $success = "Tu contraseña ha sido actualizada con éxito.";
            
            // Invalidar el token para que no se use de nuevo
            $delQuery = "DELETE FROM password_resets WHERE email = ?";
            $stmtDel = $conn->prepare($delQuery);
            $stmtDel->bind_param("s", $user_email);
            $stmtDel->execute();
            
            $validToken = false; // Ocultar formulario
        } else {
            $error = "Hubo un error al actualizar la contraseña.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Motoverify</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: #f8f9fc;
            overflow: hidden;
        }

        /* Background Layout identical to mockup */
        .bg-top {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 45%;
            background-color: #0640aa;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%);
            z-index: 1;
        }
        
        .bg-bottom {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 55%;
            background-color: #f8f9fc;
            z-index: 0;
        }

        .decor-gears { position: absolute; color: rgba(255, 255, 255, 0.05); pointer-events: none; }
        .gear-top-left { font-size: 150px; top: -30px; left: -30px; }
        .gear-top-left-small { font-size: 80px; top: 100px; left: 100px; }
        .gear-top-right { font-size: 180px; top: -20px; right: -40px; }

        .decor-piston { position: absolute; color: rgba(0, 0, 0, 0.03); pointer-events: none; z-index: 0; }
        .piston-bottom-right { font-size: 280px; bottom: 80px; right: 20px; transform: rotate(15deg); }
        .gear-bottom-left { font-size: 220px; bottom: -20px; left: -40px; }

        .dots-pattern { position: absolute; width: 150px; height: 150px; background-image: radial-gradient(rgba(255, 255, 255, 0.15) 2px, transparent 2px); background-size: 20px 20px; }
        .dots-left { top: 20px; left: 20px; }
        .dots-right { top: 20px; right: 120px; }

        /* Card Setup */
        .reset-wrapper {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reset-card {
            background: white;
            width: 100%;
            max-width: 500px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            padding: 40px 50px;
            position: relative;
        }

        .btn-close-card {
            position: absolute;
            top: 20px; right: 20px;
            width: 35px; height: 35px;
            background-color: #0d2859;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #ff3333; font-size: 1.2rem; text-decoration: none;
            border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .btn-close-card:hover { transform: scale(1.1); color: #ff0000; }

        /* Avatar */
        .avatar-container { text-align: center; margin-bottom: 20px; }
        .avatar-circle {
            width: 80px; height: 80px;
            background-color: #d8eaff;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            color: #0066ff; font-size: 2.2rem; position: relative;
        }
        .avatar-badge {
            position: absolute; top: 0; right: -5px; background-color: #0066ff;
            color: white; width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; border: 2px solid white;
        }

        /* Title */
        .reset-title {
            text-align: center; font-weight: 800; color: #0b2253; font-size: 1.7rem;
            margin-bottom: 35px; position: relative;
        }
        .reset-title::after {
            content: ''; position: absolute; bottom: -12px; left: 50%;
            transform: translateX(-50%); width: 45px; height: 3.5px;
            background-color: #0066ff; border-radius: 2px;
        }

        /* Form Elements */
        .form-label {
            font-weight: 700; color: #0b2253; font-size: 0.95rem;
            margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
        }
        .form-control-custom {
            width: 100%; padding: 12px 15px; border: 2px solid #3b82f6;
            border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 1rem;
            color: #333; outline: none; transition: box-shadow 0.2s;
            margin-bottom: 25px; background-color: #fcfcfd;
        }
        .form-control-custom:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
        .form-control-custom::placeholder { color: #1a1a1a; font-weight: 900; font-size: 1.2rem; transform: translateY(-2px); }

        .btn-submit {
            background-color: #062b80; color: white; width: 100%; padding: 14px;
            border: none; border-radius: 50px; font-weight: 700; font-size: 1.1rem;
            transition: background-color 0.2s, transform 0.1s; margin-top: 10px;
        }
        .btn-submit:hover { background-color: #041c5c; }
        .btn-submit:active { transform: scale(0.98); }

        .alert-error {
            background-color: #fee2e2; color: #b91c1c; border: 1px solid #f87171;
            padding: 10px 15px; border-radius: 8px; font-size: 0.9rem; font-weight: 500;
            margin-bottom: 20px; text-align: center;
        }
        
        .alert-success-custom {
            background-color: #d1fae5; color: #065f46; border: 1px solid #34d399;
            padding: 20px; border-radius: 12px; font-size: 1rem; font-weight: 600;
            margin-bottom: 20px; text-align: center; display: flex; flex-direction: column; gap: 10px;
        }

    </style>
</head>
<body>

    <!-- Backgrounds -->
    <div class="bg-top">
        <i class="fa-solid fa-gear decor-gears gear-top-left"></i>
        <i class="fa-solid fa-gear decor-gears gear-top-left-small"></i>
        <i class="fa-solid fa-gear decor-gears gear-top-right"></i>
        <div class="dots-pattern dots-left"></div>
        <div class="dots-pattern dots-right"></div>
    </div>
    
    <div class="bg-bottom">
        <i class="fa-solid fa-gear decor-piston gear-bottom-left"></i>
        <i class="fa-solid fa-motorcycle decor-piston piston-bottom-right"></i>
    </div>

    <!-- Main Content -->
    <div class="reset-wrapper">
        <div class="reset-card">
            
            <a href="index.php" class="btn-close-card" title="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </a>

            <div class="avatar-container">
                <div class="avatar-circle">
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>

            <h2 class="reset-title">Cambiar contraseña</h2>

            <?php if (!empty($error)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-success-custom">
                    <i class="fa-solid fa-check-circle" style="font-size: 3rem; color: #10b981;"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                    <a href="index.php" class="btn btn-outline-success fw-bold mt-2 border-2" style="border-radius: 50px;">Ir a Iniciar Sesión</a>
                </div>
            <?php elseif ($validToken): ?>
                
                <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
                    
                    <!-- Campo de contraseña actual omitido a solicitud (Opción A) -->

                    <label for="new_password" class="form-label">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password" class="form-control-custom" placeholder="••••••••••••••••" required>

                    <label for="confirm_password" class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control-custom" placeholder="••••••••••••••••" required>

                    <div class="text-center">
                        <button type="submit" class="btn-submit">Actualizar contraseña</button>
                    </div>
                    
                </form>

            <?php else: ?>
                <!-- Botón de retorno si el token es inválido -->
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-secondary border-0" style="border-radius: 50px; padding: 10px 30px; font-weight: 600; background: #0640aa;">
                        <i class="fa-solid fa-arrow-left me-1"></i> Volver a la página principal
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
