<?php

class RepuestoModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function checkCodigoBarras($codigo_barras, $exclude_id = 0) {
        $query = "SELECT id_repuesto FROM REPUESTO WHERE codigo_barras = ?";
        if ($exclude_id > 0) {
            $query .= " AND id_repuesto != ?";
        }
        $stmt = $this->conn->prepare($query);
        if ($exclude_id > 0) {
            $stmt->bind_param("si", $codigo_barras, $exclude_id);
        } else {
            $stmt->bind_param("s", $codigo_barras);
        }
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count > 0;
    }

    public function checkCodigoInterno($codigo_interno, $exclude_id = 0) {
        $query = "SELECT id_repuesto FROM REPUESTO WHERE codigo_interno = ?";
        if ($exclude_id > 0) {
            $query .= " AND id_repuesto != ?";
        }
        $stmt = $this->conn->prepare($query);
        if ($exclude_id > 0) {
            $stmt->bind_param("si", $codigo_interno, $exclude_id);
        } else {
            $stmt->bind_param("s", $codigo_interno);
        }
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count > 0;
    }

    public function create($data) {
        $query = "INSERT INTO REPUESTO (codigo_interno, codigo_barras, nombre, descripcion, referencia_fabricante, id_marca, id_categoria, caracteristicas, compatibilidades, observaciones, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("sssssiissss", 
            $data['codigo_interno'], 
            $data['codigo_barras'], 
            $data['nombre'], 
            $data['descripcion'], 
            $data['referencia_fabricante'], 
            $data['id_marca'], 
            $data['id_categoria'], 
            $data['caracteristicas'],
            $data['compatibilidades'],
            $data['observaciones'], 
            $data['estado']
        );
        
        if ($stmt->execute()) {
            $insert_id = $stmt->insert_id;
            $stmt->close();
            return $insert_id;
        }
        $stmt->close();
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE REPUESTO SET codigo_interno=?, codigo_barras=?, nombre=?, descripcion=?, referencia_fabricante=?, id_marca=?, id_categoria=?, caracteristicas=?, compatibilidades=?, observaciones=?, estado=? WHERE id_repuesto=?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("sssssiissssi", 
            $data['codigo_interno'], 
            $data['codigo_barras'], 
            $data['nombre'], 
            $data['descripcion'], 
            $data['referencia_fabricante'], 
            $data['id_marca'], 
            $data['id_categoria'], 
            $data['caracteristicas'],
            $data['compatibilidades'],
            $data['observaciones'], 
            $data['estado'],
            $id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function delete($id) {
        // Importante: Eliminar primero las relaciones de imágenes
        $queryRel = "DELETE FROM REPUESTO_IMAGEN WHERE id_repuesto = ?";
        $stmtRel = $this->conn->prepare($queryRel);
        if($stmtRel) {
            $stmtRel->bind_param("i", $id);
            $stmtRel->execute();
            $stmtRel->close();
        }

        $query = "DELETE FROM REPUESTO WHERE id_repuesto = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
