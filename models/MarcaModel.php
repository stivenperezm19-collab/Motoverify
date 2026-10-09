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
            public function getDashboardCards() {
        $marcas_cards = [];
        $query_marcas = "SELECT m.id_marca, m.nombre, COUNT(r.id_repuesto) as total_repuestos, i.ruta_archivo as logo
                         FROM MARCA m
                         LEFT JOIN REPUESTO r ON m.id_marca = r.id_marca
                         LEFT JOIN MARCA_IMAGEN mi ON m.id_marca = mi.id_marca AND mi.imagen_principal = 1
                         LEFT JOIN IMAGEN i ON mi.id_imagen = i.id_imagen
                         GROUP BY m.id_marca, m.nombre, i.ruta_archivo
                         ORDER BY total_repuestos DESC LIMIT 4";
        $res = $this->conn->query($query_marcas);
        if ($res) {
            while ($row = $res->fetch_assoc()) { $marcas_cards[] = $row; }
        }
        return $marcas_cards;
    }

    public function getAll() {
        $marcas = [];
        $query = "SELECT id_marca as id, nombre FROM MARCA ORDER BY nombre";
        $res = $this->conn->query($query);
        if ($res) {
            while ($row = $res->fetch_assoc()) { $marcas[] = $row; }
        }
        return $marcas;
    }

    public function getAllWithImages() {
        $marcas = [];
        $query = "SELECT m.*, i.ruta_archivo as imagen_url 
                  FROM MARCA m 
                  LEFT JOIN MARCA_IMAGEN mi ON m.id_marca = mi.id_marca AND mi.imagen_principal = 1
                  LEFT JOIN IMAGEN i ON mi.id_imagen = i.id_imagen 
                  ORDER BY m.nombre ASC";
        $res = $this->conn->query($query);
        if ($res) {
            while ($row = $res->fetch_assoc()) { $marcas[] = $row; }
        }
        return $marcas;
    }

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
