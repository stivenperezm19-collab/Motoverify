<?php
// Le indicamos al navegador que nuestra respuesta será en formato JSON
header('Content-Type: application/json');
// Incluimos la conexión a la base de datos
require_once '../config/database.php';

// Verificamos si nos están enviando datos mediante un formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtenemos el correo electrónico, quitándole los espacios en blanco
    $email = trim($_POST['email'] ?? '');

    // Si el correo está vacío o no tiene forma de correo válido (ej: texto@correo.com)...
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, ingresa un correo electrónico válido.']);
        exit;
    }

    // 1. Nos aseguramos de que exista una tabla en la base de datos para guardar las solicitudes de recuperación
    // Si la tabla no existe, la creamos automáticamente
    $createTableQuery = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTableQuery);

    // 2. Revisamos si el correo que ingresó el usuario realmente existe en nuestra tabla de administradores
    $query = "SELECT id_administrador, email FROM ADMINISTRADOR WHERE email = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Si encontramos al menos un administrador con ese correo...
        if ($result->num_rows > 0) {
            // Generamos un "token" (un código de seguridad único y muy difícil de adivinar)
            $token = bin2hex(random_bytes(32));
            
            // Guardamos este token junto con el correo en la base de datos
            $insertQuery = "INSERT INTO password_resets (email, token) VALUES (?, ?)";
            $stmtInsert = $conn->prepare($insertQuery);
            $stmtInsert->bind_param("ss", $email, $token);
            $stmtInsert->execute();

            // 3. Preparamos el enlace mágico que el usuario clickeará para cambiar su clave
            // Usamos el código de seguridad (token) en el enlace
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/Motoverify/public/reset_password.php?token=" . $token;
            
            // Configuramos los detalles del correo electrónico
            $subject = "Recuperación de contraseña - Motoverify";
            $message = "Hola,\n\nHaz clic en el siguiente enlace para recuperar tu contraseña de Motoverify:\n\n" . $resetLink . "\n\nSi no solicitaste esto, puedes ignorar este mensaje.";
            
            // Estas "cabeceras" evitan que el correo se marque como spam y le dicen de quién viene
            $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "Reply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            // Enviamos el correo (requiere que el servidor tenga configurado el envío de emails)
            mail($email, $subject, $message, $headers);

            // Le decimos al usuario que todo salió bien
            echo json_encode([
                'status' => 'success', 
                'message' => 'Se ha enviado un correo de recuperación.'
            ]);
        } else {
            // Por seguridad, si el correo NO existe, mostramos el MISMO mensaje de éxito.
            // ¿Por qué? Para evitar que los hackers (o personas malintencionadas) prueben correos al azar
            // y descubran cuáles están registrados en el sistema.
            echo json_encode(['status' => 'success', 'message' => 'Si el correo existe, recibirás un enlace de recuperación.']);
        }
        $stmt->close();
    } else {
        // Si falló la conexión con la base de datos
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
    }
}
?>
