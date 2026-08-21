<?php

class ImagenModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function saveImageAndLinkToRepuesto($id_repuesto, $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes, $imagen_principal = true) {
        // 1. Insertar en tabla IMAGEN
        $queryImagen = "INSERT INTO IMAGEN (ruta_archivo, nombre_archivo, tipo_archivo, tamano_bytes, estado) VALUES (?, ?, ?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($queryImagen);
        if (!$stmt) return false;

        $stmt->bind_param("sssi", $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes);
        if ($stmt->execute()) {
            $id_imagen = $stmt->insert_id;
            $stmt->close();

            // 2. Insertar en tabla pivote REPUESTO_IMAGEN
            $queryPivote = "INSERT INTO REPUESTO_IMAGEN (id_repuesto, id_imagen, imagen_principal, orden) VALUES (?, ?, ?, 1)";
            $stmtPivote = $this->conn->prepare($queryPivote);
            if ($stmtPivote) {
                $principal_int = $imagen_principal ? 1 : 0;
                $stmtPivote->bind_param("iii", $id_repuesto, $id_imagen, $principal_int);
                $stmtPivote->execute();
                $stmtPivote->close();
                return true;
            }
        }
        return false;
    }
    
    public function saveImageAndLinkToMarca($id_marca, $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes, $imagen_principal = true) {
        $queryImagen = "INSERT INTO IMAGEN (ruta_archivo, nombre_archivo, tipo_archivo, tamano_bytes, estado) VALUES (?, ?, ?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($queryImagen);
        if (!$stmt) return false;

        $stmt->bind_param("sssi", $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes);
        if ($stmt->execute()) {
            $id_imagen = $stmt->insert_id;
            $stmt->close();

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

    public function saveImageAndLinkToCategoria($id_categoria, $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes, $imagen_principal = true) {
        $queryImagen = "INSERT INTO IMAGEN (ruta_archivo, nombre_archivo, tipo_archivo, tamano_bytes, estado) VALUES (?, ?, ?, ?, 'ACTIVO')";
        $stmt = $this->conn->prepare($queryImagen);
        if (!$stmt) return false;

        $stmt->bind_param("sssi", $ruta_archivo, $nombre_archivo, $tipo_archivo, $tamano_bytes);
        if ($stmt->execute()) {
            $id_imagen = $stmt->insert_id;
            $stmt->close();

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
