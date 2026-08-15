<?php
session_start();
require_once __DIR__ . '/../../models/Usuario.php';

// Agregamos una base HTML mínima para cargar SweetAlert2 correctamente
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesando Registro...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { font-family: "Inter", sans-serif; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); min-height: 100vh; margin: 0; }</style>
</head>
<body>';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_str = $_POST['usuario'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $terminos = $_POST['terminos'] ?? '';

    if (empty($usuario_str) || empty($email) || empty($password)) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Campos incompletos',
                text: 'Todos los campos son obligatorios.',
                confirmButtonColor: '#198754'
            }).then(() => {
                window.location.href = '../../views/auth/register.php';
            });
        </script></body></html>";
        exit();
    }

    $usuarioModel = new Usuario();

    if ($usuarioModel->emailExiste($email)) {
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Email registrado',
                text: 'El email ya está registrado en nuestro sistema.',
                confirmButtonColor: '#198754'
            }).then(() => {
                window.location.href = '../../views/auth/register.php';
            });
        </script></body></html>";
        exit();
    }

    // 3 = Cliente
    $registrado = $usuarioModel->registrar($usuario_str, $email, $password, 3);

    if ($registrado) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: '¡Registro Exitoso!',
                text: 'Tu cuenta de cliente ha sido creada correctamente. Ahora puedes iniciar sesión.',
                confirmButtonColor: '#198754'
            }).then(() => {
                window.location.href = '../../views/auth/login.php';
            });
        </script></body></html>";
        exit();
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error interno',
                text: 'Hubo un error al crear la cuenta. Por favor, intenta de nuevo.',
                confirmButtonColor: '#198754'
            }).then(() => {
                window.location.href = '../../views/auth/register.php';
            });
        </script></body></html>";
        exit();
    }
} else {
    header('Location: ../../views/auth/register.php');
    exit();
}
?>
