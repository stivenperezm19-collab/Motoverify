<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

require_once '../models/AdministradorModel.php';

$userId = $_SESSION['user_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Ingresa tu contraseña.']);
        exit;
    }

    $adminModel = new AdministradorModel($conn);
    $admin = $adminModel->getById($userId);
    
    if ($admin) {
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Contraseña incorrecta.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado.']);
    }
}
?>
