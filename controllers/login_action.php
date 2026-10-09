<?php
// Iniciamos la sesión para poder guardar quién acaba de entrar al sistema
session_start();
// Avisamos que la respuesta será en formato JSON
header('Content-Type: application/json');
// Traemos la conexión a la base de datos
require_once '../config/database.php';
// Traemos las herramientas (modelo) para buscar a los administradores
require_once '../models/AdministradorModel.php';

// Verificamos si alguien intentó enviar el formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tomamos el correo/usuario y la contraseña que escribieron
    $username = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Si alguno de los campos está vacío, no los dejamos pasar
    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completa todos los campos.']);
        exit;
    }

    try {
        // Preparamos nuestro buscador de administradores
        $adminModel = new AdministradorModel($conn);
        // Buscamos si existe alguien con ese correo o ese nombre de usuario
        $user = $adminModel->getByUserOrEmail($username);

        // Si encontramos a la persona...
        if ($user) {
            // Comparamos la contraseña que escribieron con la que tenemos guardada (encriptada)
            // (La segunda parte "===" es solo un respaldo por si la contraseña no estaba encriptada)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // Si la contraseña es correcta, le damos su "gafete" virtual guardando sus datos en la sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['rol'] = $user['rol'] ?? 'Administrador';
                
                $redirect = '../views/admin/dashboard.php';
                if ($_SESSION['rol'] === 'Vendedor') {
                    $redirect = '../views/admin/ventas.php';
                }
                
                // Le decimos a la página que todo está bien y que lo envíe a su panel correspondiente
                echo json_encode(['status' => 'success', 'redirect' => $redirect]);
                exit;
            } else {
                // Si la persona existe pero la contraseña está mal
                echo json_encode(['status' => 'error', 'message' => 'La contraseña es incorrecta.']);
                exit;
            }
        } else {
            // Si no encontramos a nadie con ese correo/usuario
            echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
            exit;
        }
    } catch (Exception $e) {
        // Si hay algún problema técnico con la base de datos
        echo json_encode(['status' => 'error', 'message' => 'Error de BD. Verifica que la tabla exista.']);
        exit;
    }
}
?>
