<?php
// Esta clase maneja toda la información relacionada con las Marcas (Yamaha, Honda, etc.)
class MarcaModel {
    // Variable para guardar la conexión a la base de datos
    private $conn;

    // Al crear este modelo, recibimos la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Esta función permite registrar una marca nueva en el sistema
    public function create($data) {
        // Preparamos la orden para guardar el nombre, descripción y dejarla "ACTIVA"
        $query = "INSERT INTO MARCA (nombre, descripcion, estado) VALUES (?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        // Reemplazamos los signos de interrogación por los datos reales
        $stmt->bind_param("ss", $data['nombre'], $data['descripcion']);
        // Si se guardó correctamente...
        if ($stmt->execute()) {
            // Obtenemos el ID único que se le dio a esta marca
            $insert_id = $stmt->insert_id;
            $stmt->close();
            // Devolvemos ese ID
            return $insert_id;
        }
        $stmt->close();
        return false;
    }

    // Esta función actualiza (modifica) los datos de una marca que ya está registrada
    public function update($id, $data) {
        // Preparamos la orden de modificación
        $query = "UPDATE MARCA SET nombre = ?, descripcion = ? WHERE id_marca = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        // Colocamos los nuevos datos y el ID al final
        $stmt->bind_param("ssi", $data['nombre'], $data['descripcion'], $id);
        // Ejecutamos y guardamos el resultado
        $result = $stmt->execute();
        $stmt->close();
        // Devolvemos si funcionó o falló
        return $result;
    }

    // Esta función borra una marca usando su ID
    public function delete($id) {
        // Primero, debemos eliminar cualquier imagen que pertenezca a esta marca
        $queryRel = "DELETE FROM MARCA_IMAGEN WHERE id_marca = ?";
        $stmtRel = $this->conn->prepare($queryRel);
        if($stmtRel) {
            $stmtRel->bind_param("i", $id);
            $stmtRel->execute(); // Borramos las imágenes
            $stmtRel->close();
        }

        // Una vez limpia de imágenes, borramos la marca por completo
        $query = "DELETE FROM MARCA WHERE id_marca = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Esta función revisa si una marca ya tiene repuestos guardados con su nombre
    // (Útil para evitar borrar una marca si todavía hay repuestos que la usan)
    public function hasRepuestos($id) {
        // Contamos cuántos repuestos pertenecen a esta marca
        $queryCheck = "SELECT COUNT(*) as total FROM REPUESTO WHERE id_marca = ?";
        $stmtCheck = $this->conn->prepare($queryCheck);
        if (!$stmtCheck) return false;
        
        $stmtCheck->bind_param("i", $id);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();
        $row = $result->fetch_assoc();
        $stmtCheck->close();
        
        // Si el conteo es mayor que 0, significa que sí tiene repuestos
        return $row['total'] > 0;
    }
}
?>
