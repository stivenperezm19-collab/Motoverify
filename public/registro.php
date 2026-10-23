<?php
session_start();
// Si el usuario ya está autenticado, redirigir a su panel correspondiente
if (isset($_SESSION['user_id']) && isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'Cliente') {
        header('Location: ../views/cliente/dashboard.php');
        exit;
    } elseif ($_SESSION['rol'] === 'Administrador') {
        header('Location: ../views/admin/dashboard.php');
        exit;
    } elseif ($_SESSION['rol'] === 'Vendedor') {
        header('Location: ../views/vendedor/dashboard.php');
        exit;
    }
}

// Redirigir a la tienda pública en index.php abriendo automáticamente el modal de registro
header('Location: index.php?register=1');
exit;
