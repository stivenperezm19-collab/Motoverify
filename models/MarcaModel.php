<?php

class MarcaModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO MARCA (nombre, descripcion, estado) VALUES (?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("ss", $data['nombre'], $data['descripcion']);
        if ($stmt->execute()) {
            $insert_id = $stmt->insert_id;
            $stmt->close();
            return $insert_id;
        }
        $stmt->close();
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE MARCA SET nombre = ?, descripcion = ? WHERE id_marca = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("ssi", $data['nombre'], $data['descripcion'], $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function delete($id) {
        // Primero eliminar cualquier posible relación en MARCA_IMAGEN
        $queryRel = "DELETE FROM MARCA_IMAGEN WHERE id_marca = ?";
        $stmtRel = $this->conn->prepare($queryRel);
        if($stmtRel) {
            $stmtRel->bind_param("i", $id);
            $stmtRel->execute();
            $stmtRel->close();
        }

        $query = "DELETE FROM MARCA WHERE id_marca = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function hasRepuestos($id) {
        $queryCheck = "SELECT COUNT(*) as total FROM REPUESTO WHERE id_marca = ?";
        $stmtCheck = $this->conn->prepare($queryCheck);
        if (!$stmtCheck) return false;
        
        $stmtCheck->bind_param("i", $id);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();
        $row = $result->fetch_assoc();
        $stmtCheck->close();
        
        return $row['total'] > 0;
    }
}
?>
