<?php
/**
 * Controlador de Autenticación y Registro para Clientes de Motoverify
 */
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/ClienteModel.php';
require_once '../models/AdministradorModel.php';

$clienteModel = new ClienteModel($conn);
$adminModel = new AdministradorModel($conn);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // REGISTRO DE NUEVO CLIENTE
    // ==========================================
    if ($action === 'register') {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim(strtolower($_POST['correo'] ?? ''));
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $documento = trim($_POST['documento'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');

        // 1. Validaciones de obligatoriedad
        if (empty($nombre) || empty($correo) || empty($password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Los campos Nombre, Correo electrónico y Contraseña son obligatorios.'
            ]);
            exit;
        }

        // 2. Validación de nombre
        if (mb_strlen($nombre) < 3) {
            echo json_encode([
                'status' => 'error',
                'message' => 'El nombre completo debe tener al menos 3 caracteres.'
            ]);
            exit;
        }

        // 3. Validación de formato de correo
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'El formato del correo electrónico ingresado no es válido.'
            ]);
            exit;
        }

        // 4. Validación de longitud y seguridad de la contraseña
        if (strlen($password) < 6) {
            echo json_encode([
                'status' => 'error',
                'message' => 'La contraseña debe contener al menos 6 caracteres por seguridad.'
            ]);
            exit;
        }

        // 5. Validación de confirmación de contraseña (si se envía)
        if (!empty($password_confirm) && $password !== $password_confirm) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Las contraseñas no coinciden. Por favor verifícalas.'
            ]);
            exit;
        }

        // 6. Evitar cuentas duplicadas (verificar en CLIENTE y en ADMINISTRADOR)
        if ($clienteModel->emailExists($correo)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Ya existe una cuenta registrada con este correo electrónico.'
            ]);
            exit;
        }

        if ($adminModel->getByEmail($correo)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Este correo electrónico ya está en uso en el sistema.'
            ]);
            exit;
        }

        // 7. Encriptar contraseña de manera segura
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 8. Crear el cliente en la base de datos (con rol Cliente id_rol = 3 estrictamente asignado)
        $id_cliente = $clienteModel->create(
            $nombre,
            $correo,
            $passwordHash,
            !empty($documento) ? $documento : null,
            !empty($telefono) ? $telefono : null,
            !empty($direccion) ? $direccion : null,
            !empty($ciudad) ? $ciudad : null
        );

        if ($id_cliente) {
            // Iniciar sesión automáticamente
            $_SESSION['user_id'] = $id_cliente;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $correo;
            $_SESSION['rol'] = 'Cliente';
            $_SESSION['is_cliente'] = true;

            echo json_encode([
                'status' => 'success',
                'message' => '¡Tu cuenta ha sido creada exitosamente! Bienvenido a Motoverify.',
                'redirect' => '../views/cliente/dashboard.php'
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Ocurrió un error al crear la cuenta. Por favor intenta nuevamente.'
            ]);
            exit;
        }
    }

    // ==========================================
    // VERIFICAR CORREO EN TIEMPO REAL
    // ==========================================
    if ($action === 'check_email') {
        $correo = trim(strtolower($_POST['correo'] ?? ''));
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'invalid', 'available' => false]);
            exit;
        }
        $exists = $clienteModel->emailExists($correo) || ($adminModel->getByEmail($correo) !== null);
        echo json_encode(['status' => 'success', 'available' => !$exists]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Acción no permitida.']);
exit;
?>
