<?php

class CategoriaModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO CATEGORIA (nombre, descripcion, estado) VALUES (?, ?, 'ACTIVO')";
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
        $query = "UPDATE CATEGORIA SET nombre = ?, descripcion = ? WHERE id_categoria = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("ssi", $data['nombre'], $data['descripcion'], $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function delete($id) {
        $queryRel = "DELETE FROM CATEGORIA_IMAGEN WHERE id_categoria = ?";
        $stmtRel = $this->conn->prepare($queryRel);
        if($stmtRel) {
            $stmtRel->bind_param("i", $id);
            $stmtRel->execute();
            $stmtRel->close();
        }

        $query = "DELETE FROM CATEGORIA WHERE id_categoria = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function hasSubcategorias($id) {
        $query = "SELECT COUNT(*) as total FROM CATEGORIA WHERE id_categoria_padre = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['total'] > 0;
    }

    public function hasRepuestos($id) {
        $query = "SELECT COUNT(*) as total FROM REPUESTO WHERE id_categoria = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['total'] > 0;
    }

    public function reassignRepuestos($id_antigua, $id_nueva) {
        $query = "UPDATE REPUESTO SET id_categoria = ? WHERE id_categoria = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("ii", $id_nueva, $id_antigua);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
