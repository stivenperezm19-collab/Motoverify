<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completa todos los campos.']);
        exit;
    }

    $query = "SELECT id, email, password FROM usuarios WHERE email = ?";
    
    try {
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verificar contraseña (usando password_verify o comparación directa para pruebas)
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    
                    echo json_encode(['status' => 'success', 'redirect' => '../views/admin/dashboard.php']);
                    exit;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'La contraseña es incorrecta.']);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No existe una cuenta con este correo.']);
                exit;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de BD. Verifica que la tabla "usuarios" exista.']);
        exit;
    }
}
?>
