<?php
// Iniciamos la sesión para poder guardar quién acaba de entrar al sistema
session_start();
// Avisamos que la respuesta será en formato JSON
header('Content-Type: application/json');
// Traemos la conexión a la base de datos
require_once '../config/database.php';
// Traemos las herramientas (modelos) para buscar a los administradores y clientes
require_once '../models/AdministradorModel.php';
require_once '../models/ClienteModel.php';

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
        // 1. Buscamos primero en el modelo de administradores y vendedores
        $adminModel = new AdministradorModel($conn);
        $user = $adminModel->getByUserOrEmail($username);

        if ($user) {
            // Verificamos si la cuenta está inactiva
            if (isset($user['estado']) && $user['estado'] === 'Inactivo') {
                echo json_encode(['status' => 'error', 'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.']);
                exit;
            }

            // Comparamos la contraseña (hash o texto directo de respaldo)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'] ?? $user['usuario'] ?? 'Usuario';
                $_SESSION['email'] = $user['email'];
                $_SESSION['rol'] = $user['rol'] ?? 'Administrador';
                
                $redirect = '../views/admin/dashboard.php';
                if ($_SESSION['rol'] === 'Vendedor') {
                    $redirect = '../views/vendedor/dashboard.php';
                }
                
                echo json_encode(['status' => 'success', 'redirect' => $redirect]);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'La contraseña es incorrecta.']);
                exit;
            }
        }

        // 2. Si no es administrador ni vendedor, buscamos en el modelo de Clientes
        $clienteModel = new ClienteModel($conn);
        $cliente = $clienteModel->getByEmail($username);

        if ($cliente) {
            if (isset($cliente['estado']) && $cliente['estado'] === 'Inactivo') {
                echo json_encode(['status' => 'error', 'message' => 'Tu cuenta de cliente está inactiva. Contacta a soporte.']);
                exit;
            }

            if (password_verify($password, $cliente['clave_hash']) || $password === $cliente['clave_hash']) {
                $_SESSION['user_id'] = $cliente['id_cliente'];
                $_SESSION['nombre'] = $cliente['nombre'];
                $_SESSION['email'] = $cliente['correo'];
                $_SESSION['rol'] = 'Cliente';
                $_SESSION['is_cliente'] = true;

                $redirect = '../views/cliente/dashboard.php';
                echo json_encode(['status' => 'success', 'redirect' => $redirect]);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'La contraseña es incorrecta.']);
                exit;
            }
        }

        // 3. Si no encontramos a nadie con ese correo/usuario
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
        exit;

    } catch (Exception $e) {
        // Si hay algún problema técnico con la base de datos
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
        exit;
    }
}
?>
