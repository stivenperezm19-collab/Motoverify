<?php
/**
 * Controlador de Perfil para Clientes de Motoverify
 */
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/ClienteModel.php';

// Validar que el usuario sea un Cliente autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cliente') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Se requiere iniciar sesión como Cliente.']);
    exit;
}

$clienteModel = new ClienteModel($conn);
$id_cliente = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($nombre)) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre completo es obligatorio.']);
        exit;
    }

    $clienteActual = $clienteModel->getById($id_cliente);
    if (!$clienteActual) {
        echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado.']);
        exit;
    }

    $passwordHash = null;

    // Si el cliente desea cambiar la contraseña
    if (!empty($new_password)) {
        if (empty($current_password)) {
            echo json_encode(['status' => 'error', 'message' => 'Para establecer una nueva contraseña, debes ingresar tu contraseña actual.']);
            exit;
        }

        // Obtener el hash actual
        $clienteCreds = $clienteModel->getByEmail($clienteActual['correo']);
        if (!password_verify($current_password, $clienteCreds['clave_hash']) && $current_password !== $clienteCreds['clave_hash']) {
            echo json_encode(['status' => 'error', 'message' => 'La contraseña actual no es correcta.']);
            exit;
        }

        if (strlen($new_password) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            exit;
        }

        if ($new_password !== $confirm_password) {
            echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña y su confirmación no coinciden.']);
            exit;
        }

        $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
    }

    $actualizado = $clienteModel->updateProfile(
        $id_cliente,
        $nombre,
        $telefono,
        $documento,
        $direccion,
        $ciudad,
        $passwordHash
    );

    if ($actualizado) {
        $_SESSION['nombre'] = $nombre;
        echo json_encode([
            'status' => 'success',
            'message' => 'Tu perfil ha sido actualizado exitosamente.',
            'nombre' => $nombre
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error al guardar los cambios en tu perfil.']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Método no soportado.']);
exit;
?>
