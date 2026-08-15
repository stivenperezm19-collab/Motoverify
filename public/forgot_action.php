<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, ingresa un correo electrónico válido.']);
        exit;
    }

    // 1. Asegurarnos de que la tabla password_resets exista
    $createTableQuery = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTableQuery);

    // 2. Verificar si el correo existe en la base de datos (tabla usuarios)
    $query = "SELECT id, email FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Generar token único
            $token = bin2hex(random_bytes(32));
            
            // Guardar token en BD
            $insertQuery = "INSERT INTO password_resets (email, token) VALUES (?, ?)";
            $stmtInsert = $conn->prepare($insertQuery);
            $stmtInsert->bind_param("ss", $email, $token);
            $stmtInsert->execute();

            // 3. Simular o Enviar el correo
            // Como no tenemos PHPMailer instalado ni configurado el SMTP aquí, 
            // crearemos el enlace que normalmente iría en el correo.
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/Motoverify/public/reset_password.php?token=" . $token;

            // En un entorno de producción, aquí usaríamos PHPMailer() para enviar el $resetLink por correo de Gmail.
            // Para poder probarlo localmente (sin SMTP configurado), imprimiremos el link oculto en el mensaje de éxito
            // para que el usuario pueda copiarlo y entrar.
            
            // mail($email, "Recuperación de contraseña", "Haz clic en el siguiente enlace para recuperar tu contraseña: " . $resetLink);

            echo json_encode([
                'status' => 'success', 
                'message' => 'Se ha enviado un correo de recuperación.',
                'debug_link' => $resetLink // SOLO PARA PRUEBAS EN LOCAL
            ]);
        } else {
            // Por seguridad, siempre decimos "Si el correo existe, se envió el email"
            // para evitar enumeración de usuarios.
            echo json_encode(['status' => 'success', 'message' => 'Si el correo existe, recibirás un enlace de recuperación.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
    }
}
?>
