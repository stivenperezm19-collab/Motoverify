<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';

class AuthController
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../views/auth/login.php');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $_SESSION['alert'] = [
                'icon' => 'warning',
                'title' => 'Campos incompletos',
                'text' => 'Debe ingresar correo y contraseña'
            ];
            header('Location: ../../views/auth/login.php');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Correo inválido',
                'text' => 'Ingrese un correo electrónico válido'
            ];
            header('Location: ../../views/auth/login.php');
            exit;
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorEmail($email);

        if (!$usuario) {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Usuario no encontrado',
                'text' => 'El correo no está registrado o está inactivo'
            ];
            header('Location: ../../views/auth/login.php');
            exit;
        }

        if (!password_verify($password, $usuario['password'])) {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Contraseña incorrecta',
                'text' => 'Verifique sus credenciales'
            ];
            header('Location: ../../views/auth/login.php');
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_usuario'],
            'usuario' => $usuario['usuario'],
            'email' => $usuario['email'],
            'id_rol' => $usuario['id_rol']
        ];
        switch ($usuario['id_rol']) {
            case '1':
                header('Location: ../../views/admin/dashboard.php');
                exit;

            case '2':
                header('Location: ../../views/vendedor/dashboard.php');
                exit;

            case '3':
                header('Location: ../../views/cliente/dashboard.php');
                exit;

            default:
                $_SESSION['alert'] = [
                    'icon' => 'error',
                    'title' => 'Rol no válido',
                    'text' => 'No se pudo determinar el acceso del usuario'
                ];
                header('Location: ../../views/auth/login.php');
                exit;
        }
    }

    public function logout()
    {  // cerrar sesion
        session_unset();
        session_destroy();
        header('Location: ../../views/auth/login.php');
        exit;
    }
}

$controller = new AuthController();

$accion = $_GET['accion'] ?? 'login';

if ($accion === 'logout') {
    $controller->logout();
} else {
    $controller->login();
}
?>