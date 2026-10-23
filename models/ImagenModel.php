<?php
// Esta clase se encarga de guardar las imágenes y conectarlas con repuestos, marcas o categorías
class ImagenModel {
    // Variable para guardar la conexión a la base de datos
    private $conn;

    // Al crear este modelo, le pasamos la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Esta función guarda los datos de una imagen nueva y la vincula a un REPUESTO específico
    public function saveImageAndLinkToRepuesto($id_repuesto, $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes, $imagen_principal = true) {
        // 1. Primero, guardamos los datos de la imagen en la tabla general de imágenes
        $queryImagen = "INSERT INTO IMAGEN (ruta_archivo, nombre_archivo, tipo_archivo, tamano_bytes, estado) VALUES (?, ?, ?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($queryImagen);
        if (!$stmt) return false;

        // Colocamos los datos reales (ruta, nombre, tipo y peso de la imagen)
        $stmt->bind_param("sssi", $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes);
        if ($stmt->execute()) {
            // Obtenemos el ID que la base de datos le asignó a esta nueva imagen
            $id_imagen = $stmt->insert_id;
            $stmt->close();

            // 2. Segundo, creamos un "puente" que une esta imagen con el repuesto correspondiente
            $queryPivote = "INSERT INTO REPUESTO_IMAGEN (id_repuesto, id_imagen, imagen_principal, orden) VALUES (?, ?, ?, 1)";
            $stmtPivote = $this->conn->prepare($queryPivote);
            if ($stmtPivote) {
                // Convertimos el "verdadero/falso" de imagen principal a "1/0" para la base de datos
                $principal_int = $imagen_principal ? 1 : 0;
                $stmtPivote->bind_param("iii", $id_repuesto, $id_imagen, $principal_int);
                $stmtPivote->execute();
                $stmtPivote->close();
                // Devolvemos verdadero si todo el proceso fue exitoso
                return true;
            }
        }
        return false;
    }
    
    // Esta función guarda los datos de una imagen nueva y la vincula a una MARCA específica
    public function saveImageAndLinkToMarca($id_marca, $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes, $imagen_principal = true) {
        // Guardamos los datos en la tabla principal de imágenes
        $queryImagen = "INSERT INTO IMAGEN (ruta_archivo, nombre_archivo, tipo_archivo, tamano_bytes, estado) VALUES (?, ?, ?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($queryImagen);
        if (!$stmt) return false;

        $stmt->bind_param("sssi", $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes);
        if ($stmt->execute()) {
            // Obtenemos el ID de la imagen guardada
            $id_imagen = $stmt->insert_id;
            $stmt->close();

            // Creamos la unión entre la imagen y la MARCA indicada
            $queryPivote = "INSERT INTO MARCA_IMAGEN (id_marca, id_imagen, imagen_principal, orden) VALUES (?, ?, ?, 1)";
            $stmtPivote = $this->conn->prepare($queryPivote);
            if ($stmtPivote) {
                $principal_int = $imagen_principal ? 1 : 0;
                $stmtPivote->bind_param("iii", $id_marca, $id_imagen, $principal_int);
                $stmtPivote->execute();
                $stmtPivote->close();
                return true;
            }
        }
        return false;
    }

    // Esta función guarda los datos de una imagen nueva y la vincula a una CATEGORÍA específica
    public function saveImageAndLinkToCategoria($id_categoria, $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes, $imagen_principal = true) {
        // Al igual que las anteriores, primero guardamos la imagen general
        $queryImagen = "INSERT INTO IMAGEN (ruta_archivo, nombre_archivo, tipo_archivo, tamano_bytes, estado) VALUES (?, ?, ?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($queryImagen);
        if (!$stmt) return false;

        $stmt->bind_param("sssi", $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes);
        if ($stmt->execute()) {
            $id_imagen = $stmt->insert_id;
            $stmt->close();

            // Creamos la conexión entre la imagen y la CATEGORÍA
            $queryPivote = "INSERT INTO CATEGORIA_IMAGEN (id_categoria, id_imagen, imagen_principal, orden) VALUES (?, ?, ?, 1)";
            $stmtPivote = $this->conn->prepare($queryPivote);
            if ($stmtPivote) {
                $principal_int = $imagen_principal ? 1 : 0;
                $stmtPivote->bind_param("iii", $id_categoria, $id_imagen, $principal_int);
                $stmtPivote->execute();
                $stmtPivote->close();
                return true;
            }
        }
        return false;
    }
}
?>
