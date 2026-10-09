<?php
// Esta clase representa las operaciones que podemos hacer con los repuestos en la base de datos
class RepuestoModel {
    // Aquí guardamos la conexión a la base de datos para usarla en todas las funciones
    private $conn;

    // Cuando creamos un RepuestoModel, necesitamos darle la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Esta función revisa si un código de barras ya existe en la base de datos
    // El parámetro $exclude_id sirve para ignorar un repuesto específico (útil cuando estamos editando)
    public function checkCodigoBarras($codigo_barras, $exclude_id = 0) {
        // Preparamos la pregunta para la base de datos buscando el repuesto por código
        $query = "SELECT id_repuesto FROM REPUESTO WHERE codigo_barras = ?";
        // Si nos dicen que ignoremos un ID, lo agregamos a la pregunta
        if ($exclude_id > 0) {
            $query .= " AND id_repuesto != ?";
        }
        $stmt = $this->conn->prepare($query);
        // Colocamos los valores reales en lugar de los signos de interrogación (?)
        if ($exclude_id > 0) {
            // Pasamos el código de barras y el ID a excluir
            $stmt->bind_param("si", $codigo_barras, $exclude_id);
        } else {
            // Solo pasamos el código de barras
            $stmt->bind_param("s", $codigo_barras);
        }
        // Ejecutamos la búsqueda
        $stmt->execute();
        $stmt->store_result();
        // Contamos cuántos repuestos encontramos
        $count = $stmt->num_rows;
        // Cerramos la búsqueda para liberar memoria
        $stmt->close();
        // Devuelve verdadero (true) si encontró más de 0 (es decir, el código ya existe)
        return $count > 0;
    }

    // Esta función revisa si un código interno ya existe, funciona igual que la anterior
    public function checkCodigoInterno($codigo_interno, $exclude_id = 0) {
        // Preparamos la búsqueda del código interno
        $query = "SELECT id_repuesto FROM REPUESTO WHERE codigo_interno = ?";
        if ($exclude_id > 0) {
            $query .= " AND id_repuesto != ?";
        }
        $stmt = $this->conn->prepare($query);
        // Ponemos los valores en la consulta
        if ($exclude_id > 0) {
            $stmt->bind_param("si", $codigo_interno, $exclude_id);
        } else {
            $stmt->bind_param("s", $codigo_interno);
        }
        $stmt->execute();
        $stmt->store_result();
        // Contamos cuántas veces aparece
        $count = $stmt->num_rows;
        $stmt->close();
        // Si es mayor a 0, significa que ya está registrado
        return $count > 0;
    }

    // Esta función sirve para guardar un repuesto nuevo en la base de datos
    public function create($data) {
        // Preparamos la orden para insertar (guardar) todos los datos del repuesto
        $query = "INSERT INTO REPUESTO (codigo_interno, codigo_barras, nombre, descripcion, referencia_fabricante, id_marca, id_categoria, caracteristicas, compatibilidades, observaciones, estado, precio, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        // Si hay un error al preparar, nos detenemos aquí y devolvemos falso
        if (!$stmt) return false;
        
        // Emparejamos los datos que nos enviaron con cada signo de interrogación (?) de la consulta
        $stmt->bind_param("sssssiissssdi", 
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
            $data['precio'],
            $data['stock']
        );
        
        // Si logramos ejecutar la orden de guardado correctamente...
        if ($stmt->execute()) {
            // Obtenemos el ID o número único que la base de datos le asignó a este nuevo repuesto
            $insert_id = $stmt->insert_id;
            $stmt->close();
            // Devolvemos ese número único para usarlo si es necesario
            return $insert_id;
        }
        // Si algo falló, cerramos la operación y devolvemos falso
        $stmt->close();
        return false;
    }

    // Esta función permite actualizar la información de un repuesto que ya existe
    public function update($id, $data) {
        // Preparamos la orden para modificar los datos del repuesto que tenga el ID indicado
        $query = "UPDATE REPUESTO SET codigo_interno=?, codigo_barras=?, nombre=?, descripcion=?, referencia_fabricante=?, id_marca=?, id_categoria=?, caracteristicas=?, compatibilidades=?, observaciones=?, estado=?, precio=?, stock=? WHERE id_repuesto=?";
        $stmt = $this->conn->prepare($query);
        // Si no se pudo preparar, devolvemos falso
        if (!$stmt) return false;
        
        // Colocamos los nuevos datos en su lugar correspondiente, incluyendo el ID al final
        $stmt->bind_param("sssssiissssdii", 
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
            $data['precio'],
            $data['stock'],
            $id
        );
        
        // Ejecutamos la orden y guardamos el resultado (verdadero si funcionó, falso si falló)
        $result = $stmt->execute();
        $stmt->close();
        // Devolvemos el resultado de la operación
        return $result;
    }

    // Esta función sirve para eliminar un repuesto del sistema usando su ID
    public function delete($id) {
        // Importante: Antes de eliminar el repuesto, debemos eliminar las imágenes asociadas a él
        // Preparamos la orden para borrar la relación entre este repuesto y sus imágenes
        $queryRel = "DELETE FROM REPUESTO_IMAGEN WHERE id_repuesto = ?";
        $stmtRel = $this->conn->prepare($queryRel);
        if($stmtRel) {
            // Pasamos el ID del repuesto para borrar sus imágenes
            $stmtRel->bind_param("i", $id);
            $stmtRel->execute();
            $stmtRel->close();
        }

        // Ahora sí, preparamos la orden para borrar el repuesto principal
        $query = "DELETE FROM REPUESTO WHERE id_repuesto = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        // Pasamos el ID del repuesto que queremos borrar
        $stmt->bind_param("i", $id);
        // Ejecutamos la acción
        $result = $stmt->execute();
        $stmt->close();
        // Devolvemos el resultado (verdadero si se borró, falso si no)
        return $result;
    }

    // Esta función obtiene todos los modelos de moto disponibles en las compatibilidades
    public function getModelos() {
        $query = "SELECT DISTINCT compatibilidades as modelo_moto FROM REPUESTO WHERE compatibilidades IS NOT NULL AND compatibilidades != '' ORDER BY compatibilidades ASC";
        $result = $this->conn->query($query);
        $modelos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $modelos[] = $row;
            }
        }
        return $modelos;
    }

    // Obtener repuestos con formato especial para el Punto de Venta (POS)
    public function getForPos() {
        $query = "SELECT r.id_repuesto, r.codigo_interno, r.nombre, r.precio, r.stock, m.nombre as marca 
                  FROM REPUESTO r 
                  LEFT JOIN MARCA m ON r.id_marca = m.id_marca 
                  ORDER BY r.nombre ASC";
        $result = $this->conn->query($query);
        $repuestos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $repuestos[] = $row;
            }
        }
        return $repuestos;
    }
}
?>
