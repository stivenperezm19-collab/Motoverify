<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/AdministradorModel.php';

// Verificación de seguridad estricta: Solo Administradores
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Solo administradores pueden gestionar usuarios.']);
    exit;
}

$adminModel = new AdministradorModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $id_rol = intval($_POST['id_rol'] ?? 0);

        if (empty($nombre) || empty($email) || empty($password) || $id_rol <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Verificar si el correo ya existe
        if ($adminModel->getByEmail($email)) {
            echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya está registrado.']);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        if ($adminModel->create($nombre, $email, $passwordHash, $id_rol)) {
            echo json_encode(['status' => 'success', 'message' => 'Usuario creado exitosamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el usuario.']);
        }
    } 
    elseif ($action === 'update') {
        $id = intval($_POST['id_administrador'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $id_rol = intval($_POST['id_rol'] ?? 0);
        $password = $_POST['password'] ?? ''; // Opcional

        if ($id <= 0 || empty($nombre) || empty($email) || $id_rol <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios para actualizar.']);
            exit;
        }

        // Verificar si intenta usar un correo de otro usuario
        $existente = $adminModel->getByEmail($email);
        if ($existente && $existente['id'] != $id) {
            echo json_encode(['status' => 'error', 'message' => 'El correo ya pertenece a otro usuario.']);
            exit;
        }

        $passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

        if ($adminModel->updateUserAdmin($id, $nombre, $email, $id_rol, $passwordHash)) {
            echo json_encode(['status' => 'success', 'message' => 'Usuario actualizado exitosamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el usuario.']);
        }
    }
    elseif ($action === 'toggle_status') {
        $id = intval($_POST['id_administrador'] ?? 0);
        $nuevo_estado = $_POST['estado'] === 'Activo' ? 'Activo' : 'Inactivo';

        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }

        // Protección: Si intenta desactivarse a sí mismo
        if ($id == $_SESSION['user_id'] && $nuevo_estado === 'Inactivo') {
            echo json_encode(['status' => 'error', 'message' => 'No puedes desactivar tu propia cuenta.']);
            exit;
        }

        // Protección: Evitar desactivar al último administrador
        if ($nuevo_estado === 'Inactivo') {
            $user_target = $adminModel->getById($id);
            if ($user_target && $user_target['rol'] === 'Administrador') {
                if ($adminModel->countActiveAdmins() <= 1) {
                    echo json_encode(['status' => 'error', 'message' => 'No puedes desactivar al último Administrador activo del sistema.']);
                    exit;
                }
            }
        }

        if ($adminModel->toggleStatus($id, $nuevo_estado)) {
            echo json_encode(['status' => 'success', 'message' => 'El estado del usuario ha sido cambiado a ' . $nuevo_estado . '.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo cambiar el estado.']);
        }
    }
}
?>
