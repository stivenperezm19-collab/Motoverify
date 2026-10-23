<?php
// Iniciamos la sesión para saber quién está tratando de cambiar su perfil
session_start();
// Avisamos que la respuesta será en formato JSON
header('Content-Type: application/json');
// Cargamos la conexión y el modelo de administradores
require_once '../config/database.php';
require_once '../models/AdministradorModel.php';

// Obtenemos el ID del usuario desde la sesión. Si no hay, asumimos que es el número 1 (para pruebas)
$userId = $_SESSION['user_id'] ?? 1;

// Verificamos si se están enviando datos desde el formulario de perfil (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tomamos los datos enviados y les quitamos los espacios en blanco
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Si falta alguno de los datos obligatorios, mostramos un error
    if (empty($nombre) || empty($email) || empty($current_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completa los campos obligatorios (Nombre, Correo y Contraseña Actual).']);
        exit;
    }

    try {
        $adminModel = new AdministradorModel($conn);

        // 1. Buscamos al usuario en la base de datos usando su ID para verificar su identidad
        $admin = $adminModel->getById($userId);
        
        // Si el usuario existe...
        if ($admin) {
            // Revisamos si la "contraseña actual" que escribió es la correcta
            // (Comprobamos tanto en versión encriptada como normal, por si acaso)
            if (!password_verify($current_password, $admin['password']) && $current_password !== $admin['password']) {
                echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta.']);
                exit; // Nos detenemos si se equivocó
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Administrador no encontrado.']);
            exit;
        }

        // 2. Si la contraseña actual fue correcta, procedemos a guardar los cambios
        
        // Si el usuario escribió una contraseña NUEVA...
        if (!empty($new_password)) {
            // Encriptamos la nueva contraseña para que sea segura
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            // Guardamos todos los datos, incluyendo la nueva clave
            if ($adminModel->update($userId, $nombre, $email, $hashedPassword)) {
                echo json_encode(['status' => 'success', 'message' => 'Perfil y contraseña actualizados exitosamente.', 'clearPassword' => true]);
            } else {
                throw new Exception("Error al actualizar administrador");
            }
        } else {
            // Si NO escribió una contraseña nueva, solo guardamos el nombre y el correo
            if ($adminModel->update($userId, $nombre, $email)) {
                echo json_encode(['status' => 'success', 'message' => 'Perfil actualizado exitosamente.']);
            } else {
                throw new Exception("Error al actualizar administrador");
            }
        }
        
        // Si cambió su correo electrónico, actualizamos ese dato también en la sesión temporal
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['email'] = $email;
        }

    } catch (Exception $e) {
        // Si la base de datos falló en algún punto
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos al actualizar el perfil.']);
    }
} else {
    // Si intentan entrar sin enviar un formulario
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
