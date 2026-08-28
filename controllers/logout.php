<?php
// Conectamos con la sesión actual del usuario (su "gafete" virtual)
session_start();

// Borramos toda la información temporal que habíamos guardado sobre el usuario
// (Como su nombre, ID, permisos, etc.)
$_SESSION = array();

// Para asegurarnos por completo, si el navegador guardó una "cookie" de sesión (un archivo temporal de registro)
// le decimos que la elimine poniéndole una fecha de vencimiento en el pasado
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruimos el sistema de sesión por completo para este usuario
session_destroy();

// Lo redirigimos de vuelta a la página principal pública para que ya no vea el panel de administración
header("Location: ../public/index.php");
exit;
?>
