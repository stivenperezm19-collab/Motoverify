<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Verificación de sesión o uso de fallback para pruebas
$userId = $_SESSION['user_id'] ?? 1; // Ajustar a solo sesión cuando login esté completo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validar datos obligatorios
    if (empty($nombre) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completa los campos obligatorios (Nombre y Correo).']);
        exit;
    }

    try {
        if (!empty($password)) {
            // Actualizar con contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET nombre = ?, email = ?, password = ? WHERE id = ?";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("sssi", $nombre, $email, $hashedPassword, $userId);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['status' => 'success', 'message' => 'Perfil y contraseña actualizados exitosamente.', 'clearPassword' => true]);
            } else {
                throw new Exception("Error preparando la consulta");
            }
        } else {
            // Actualizar sin contraseña
            $query = "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("ssi", $nombre, $email, $userId);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['status' => 'success', 'message' => 'Perfil actualizado exitosamente.']);
            } else {
                throw new Exception("Error preparando la consulta");
            }
        }
        
        // Actualizar sesión si corresponde
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['email'] = $email;
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos al actualizar el perfil.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
