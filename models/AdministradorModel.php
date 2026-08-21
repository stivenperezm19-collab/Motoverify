<?php

class AdministradorModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getById($id) {
        $query = "SELECT id_administrador as id, usuario as nombre, email, clave_hash as password FROM ADMINISTRADOR WHERE id_administrador = ?";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            $stmt->close();
        }
        return null;
    }

    public function getByEmail($email) {
        $query = "SELECT id_administrador as id, email, clave_hash as password FROM ADMINISTRADOR WHERE email = ?";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }
            $stmt->close();
        }
        return null;
    }

    public function getByUserOrEmail($username) {
        $query = "SELECT id_administrador as id, email, usuario, clave_hash as password FROM ADMINISTRADOR WHERE email = ? OR usuario = ?";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }
            $stmt->close();
        }
        return null;
    }

    public function update($id, $nombre, $email, $passwordHash = null) {
        if ($passwordHash) {
            $query = "UPDATE ADMINISTRADOR SET usuario = ?, email = ?, clave_hash = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_administrador = ?";
            if ($stmt = $this->conn->prepare($query)) {
                $stmt->bind_param("sssi", $nombre, $email, $passwordHash, $id);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        } else {
            $query = "UPDATE ADMINISTRADOR SET usuario = ?, email = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_administrador = ?";
            if ($stmt = $this->conn->prepare($query)) {
                $stmt->bind_param("ssi", $nombre, $email, $id);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
        return false;
    }
}
?>
