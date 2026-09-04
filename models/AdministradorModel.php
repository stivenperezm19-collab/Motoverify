<?php
// Esta clase se encarga de manejar la información de los administradores en la base de datos
class AdministradorModel {
    // Aquí guardamos la conexión a la base de datos
    private $conn;

    // Al crear este modelo, le pasamos la conexión a la base de datos para que pueda usarla
    public function __construct($db) {
        $this->conn = $db;
    }

    // Esta función busca a un administrador usando su número de identificación (ID)
    public function getById($id) {
        // Preparamos la consulta para buscar los datos del administrador
        $query = "SELECT id_administrador as id, usuario as nombre, email, clave_hash as password FROM ADMINISTRADOR WHERE id_administrador = ?";
        if ($stmt = $this->conn->prepare($query)) {
            // Reemplazamos el '?' con el ID que estamos buscando
            $stmt->bind_param("i", $id);
            // Ejecutamos la búsqueda
            $stmt->execute();
            // Obtenemos los resultados
            $result = $stmt->get_result();
            // Si encontramos a la persona (más de 0 resultados)...
            if ($result->num_rows > 0) {
                // Devolvemos sus datos
                return $result->fetch_assoc();
            }
            // Cerramos la conexión de esta búsqueda
            $stmt->close();
        }
        // Si no lo encontramos, devolvemos 'null' (nada)
        return null;
    }

    // Esta función busca a un administrador usando su correo electrónico
    public function getByEmail($mail) {
        // Preparamos la consulta buscando por email
        $query = "SELECT id_administrador as id, email, clave_hash as password FROM ADMINISTRADOR WHERE email = ?";
        if ($stmt = $this->conn->prepare($query)) {
            // Reemplazamos el '?' con el correo
            $stmt->bind_param("s", $email);
            // Ejecutamos
            $stmt->execute();
            $result = $stmt->get_result();
            // Si encontramos exactamente a 1 persona...
            if ($result->num_rows === 1) {
                // Devolvemos sus datos
                return $result->fetch_assoc();
            }
            $stmt->close();
        }
        return null;
    }

    // Esta función permite buscar a alguien ya sea por su correo o por su nombre de usuario
    // (Muy útil para la pantalla de inicio de sesión)
    public function getByUserOrEmail($username) {
        // Buscamos donde el email o el usuario sean iguales a lo que el usuario escribió
        $query = "SELECT id_administrador as id, email, usuario, clave_hash as password FROM ADMINISTRADOR WHERE email = ? OR usuario = ?";
        if ($stmt = $this->conn->prepare($query)) {
            // Reemplazamos ambos '?' con el texto ingresado
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            // Si hay coincidencia, devolvemos los datos
            if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }
            $stmt->close();
        }
        return null;
    }

    // Esta función sirve para guardar cambios en el perfil de un administrador
    public function update($id, $nombre, $email, $passwordHash = null) {
        // Si nos envían una nueva contraseña, actualizamos todo (incluida la contraseña)
        if ($passwordHash) {
            $query = "UPDATE ADMINISTRADOR SET usuario = ?, email = ?, clave_hash = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_administrador = ?";
            if ($stmt = $this->conn->prepare($query)) {
                $stmt->bind_param("sssi", $nombre, $email, $passwordHash, $id);
                $result = $stmt->execute();
                $stmt->close();
                return $result; // Devolvemos si funcionó o no
            }
        } else {
            // Si NO nos enviaron contraseña, actualizamos solo el nombre y el correo
            $query = "UPDATE ADMINISTRADOR SET usuario = ?, email = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_administrador = ?";
            if ($stmt = $this->conn->prepare($query)) {
                $stmt->bind_param("ssi", $nombre, $email, $id);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
        // Si algo sale mal, devolvemos falso
        return false;
    }
}
?>
