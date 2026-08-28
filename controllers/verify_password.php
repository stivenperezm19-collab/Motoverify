<?php
// Iniciamos la sesión para saber de qué usuario estamos hablando
session_start();
// Le decimos al navegador que le responderemos en formato JSON
header('Content-Type: application/json');
// Incluimos la conexión a la base de datos y el modelo del Administrador
require_once '../config/database.php';
require_once '../models/AdministradorModel.php';

// Tomamos el ID del usuario que está conectado (o usamos el 1 por defecto para pruebas)
$userId = $_SESSION['user_id'] ?? 1;

// Revisamos si nos enviaron la contraseña a través de un formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtenemos la contraseña que el usuario escribió
    $password = $_POST['password'] ?? '';

    // Si la dejó en blanco, le pedimos que la ingrese
    if (empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Ingresa tu contraseña.']);
        exit;
    }

    // Preparamos la herramienta para buscar en la tabla de administradores
    $adminModel = new AdministradorModel($conn);
    // Buscamos los datos de este usuario en específico
    $admin = $adminModel->getById($userId);
    
    // Si encontramos al usuario en la base de datos...
    if ($admin) {
        // Comparamos la contraseña que escribió con la que tenemos guardada
        // (Revisamos tanto si está encriptada con password_verify como si está normal)
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            // Si coincide, le decimos que todo está correcto (success)
            echo json_encode(['status' => 'success']);
        } else {
            // Si no coincide, marcamos error
            echo json_encode(['status' => 'error', 'message' => 'Contraseña incorrecta.']);
        }
    } else {
        // Si por alguna razón el usuario no existe en la base de datos
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado.']);
    }
}
?>
