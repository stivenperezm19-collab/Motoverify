<?php
// Esta clase maneja todas las acciones relacionadas con las Categorías de repuestos
class CategoriaModel {
    // Aquí guardamos la conexión a la base de datos
    private $conn;

    // Al crear este modelo, recibimos la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Esta función permite crear (guardar) una categoría nueva
    public function create($data) {
        // Preparamos la orden para guardar el nombre, la descripción y ponerla como "ACTIVO" por defecto
        $query = "INSERT INTO CATEGORIA (nombre, descripcion, estado) VALUES (?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($query);
        // Si hay error en la base de datos, nos detenemos aquí
        if (!$stmt) return false;
        
        // Colocamos los datos reales en los signos de interrogación (?)
        $stmt->bind_param("ss", $data['nombre'], $data['descripcion']);
        // Si logramos guardarla con éxito...
        if ($stmt->execute()) {
            // Obtenemos el número de ID que se le asignó a esta nueva categoría
            $insert_id = $stmt->insert_id;
            $stmt->close();
            // Devolvemos ese número de ID
            return $insert_id;
        }
        $stmt->close();
        return false; // Retornamos falso si algo salió mal
    }

    // Esta función actualiza (modifica) los datos de una categoría que ya existe
    public function update($id, $data) {
        // Preparamos la orden para cambiar el nombre y la descripción de la categoría indicada
        $query = "UPDATE CATEGORIA SET nombre = ?, descripcion = ? WHERE id_categoria = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        // Pasamos los nuevos datos y el ID de la categoría al final
        $stmt->bind_param("ssi", $data['nombre'], $data['descripcion'], $id);
        // Ejecutamos la actualización y guardamos el resultado
        $result = $stmt->execute();
        $stmt->close();
        // Devolvemos verdadero si funcionó o falso si falló
        return $result;
    }

    // Esta función sirve para borrar una categoría del sistema
    public function delete($id) {
        // Primero, borramos cualquier imagen que esté asociada a esta categoría
        $queryRel = "DELETE FROM CATEGORIA_IMAGEN WHERE id_categoria = ?";
        $stmtRel = $this->conn->prepare($queryRel);
        if($stmtRel) {
            $stmtRel->bind_param("i", $id); // Le damos el ID
            $stmtRel->execute(); // Ejecutamos el borrado de imágenes
            $stmtRel->close();
        }

        // Una vez sin imágenes, procedemos a borrar la categoría en sí
        $query = "DELETE FROM CATEGORIA WHERE id_categoria = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Esta función revisa si esta categoría tiene otras "sub-categorías" dentro de ella
    public function hasSubcategorias($id) {
        // Contamos cuántas categorías tienen a esta como "padre"
        $query = "SELECT COUNT(*) as total FROM CATEGORIA WHERE id_categoria_padre = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        // Si el total es mayor a 0, significa que sí tiene sub-categorías
        return $row['total'] > 0;
    }

    // Esta función revisa si esta categoría tiene repuestos asignados a ella
    public function hasRepuestos($id) {
        // Contamos cuántos repuestos están usando este ID de categoría
        $query = "SELECT COUNT(*) as total FROM REPUESTO WHERE id_categoria = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        // Si el total es mayor a 0, significa que sí hay repuestos en esta categoría
        return $row['total'] > 0;
    }

    // Esta función mueve todos los repuestos de una categoría vieja a una nueva
    // (Es útil si vamos a borrar una categoría pero no queremos perder sus repuestos)
    public function reassignRepuestos($id_antigua, $id_nueva) {
        // Actualizamos los repuestos: "Ponle la nueva categoría a los que tengan la antigua"
        $query = "UPDATE REPUESTO SET id_categoria = ? WHERE id_categoria = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        // Pasamos primero la categoría NUEVA y luego la ANTIGUA (el orden importa por los ?)
        $stmt->bind_param("ii", $id_nueva, $id_antigua);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
