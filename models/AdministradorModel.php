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
        $query = "SELECT a.id_administrador as id, a.usuario as nombre, a.email, a.clave_hash as password, r.nombre_rol as rol, a.estado, a.id_rol FROM ADMINISTRADOR a LEFT JOIN ROL r ON a.id_rol = r.id_rol WHERE a.id_administrador = ?";
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
    public function getByEmail($email) {
        // Preparamos la consulta buscando por email
        $query = "SELECT a.id_administrador as id, a.email, a.clave_hash as password, r.nombre_rol as rol, a.estado FROM ADMINISTRADOR a LEFT JOIN ROL r ON a.id_rol = r.id_rol WHERE a.email = ?";
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
        $query = "SELECT a.id_administrador as id, a.email, a.usuario, a.clave_hash as password, r.nombre_rol as rol, a.estado FROM ADMINISTRADOR a LEFT JOIN ROL r ON a.id_rol = r.id_rol WHERE a.email = ? OR a.usuario = ?";
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

    // Listar todos los usuarios
    public function getAll() {
        $query = "SELECT a.id_administrador as id, a.usuario as nombre, a.email, r.nombre_rol as rol, a.estado, a.id_rol 
                  FROM ADMINISTRADOR a 
                  LEFT JOIN ROL r ON a.id_rol = r.id_rol 
                  ORDER BY a.id_administrador DESC";
        $result = $this->conn->query($query);
        $usuarios = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
        }
        return $usuarios;
    }

    // Crear un usuario nuevo
        public function getRoles() {
        $roles = [];
        $query = "SELECT id_rol, nombre_rol FROM ROL ORDER BY nombre_rol";
        $res = $this->conn->query($query);
        if ($res) {
            while ($row = $res->fetch_assoc()) { $roles[] = $row; }
        }
        return $roles;
    }

    public function create($nombre, $email, $passwordHash, $id_rol) {
        $query = "INSERT INTO ADMINISTRADOR (usuario, email, clave_hash, id_rol, estado) VALUES (?, ?, ?, ?, 'Activo')";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("sssi", $nombre, $email, $passwordHash, $id_rol);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }

    // Actualizar usuario desde el panel de Gestión de Usuarios
    public function updateUserAdmin($id, $nombre, $email, $id_rol, $passwordHash = null) {
        if ($passwordHash) {
            $query = "UPDATE ADMINISTRADOR SET usuario = ?, email = ?, id_rol = ?, clave_hash = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_administrador = ?";
            if ($stmt = $this->conn->prepare($query)) {
                $stmt->bind_param("ssisi", $nombre, $email, $id_rol, $passwordHash, $id);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        } else {
            $query = "UPDATE ADMINISTRADOR SET usuario = ?, email = ?, id_rol = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_administrador = ?";
            if ($stmt = $this->conn->prepare($query)) {
                $stmt->bind_param("ssii", $nombre, $email, $id_rol, $id);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
        return false;
    }

    // Cambiar estado (Activar/Desactivar)
    public function toggleStatus($id, $estado) {
        $query = "UPDATE ADMINISTRADOR SET estado = ? WHERE id_administrador = ?";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("si", $estado, $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }

    // Contar cuantos administradores activos quedan (para evitar dejar el sistema sin admins)
    public function countActiveAdmins() {
        $query = "SELECT COUNT(*) as total FROM ADMINISTRADOR a LEFT JOIN ROL r ON a.id_rol = r.id_rol WHERE r.nombre_rol = 'Administrador' AND a.estado = 'Activo'";
        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return $row['total'];
        }
        return 0;
    }
}
?>
