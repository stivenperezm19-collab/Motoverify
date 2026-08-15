<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $codigo = trim($_POST['codigo'] ?? '');
        $id_marca = intval($_POST['id_marca'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $imagen_url = trim($_POST['imagen_url'] ?? '');
        
        // --- Validación de datos ingresados (Flujo Alternativo 4a) ---
        if (empty($codigo) || $id_marca <= 0 || empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'Los campos Código, Marca y Nombre son obligatorios. Por favor corrige los datos.']);
            exit;
        }

        if ($action === 'add') {
            $query = "INSERT INTO repuestos (codigo, id_marca, nombre, modelo, imagen_url) VALUES (?, ?, ?, ?, ?)";
            try {
                if ($stmt = $conn->prepare($query)) {
                    $stmt->bind_param("sisss", $codigo, $id_marca, $nombre, $modelo, $imagen_url);
                    if($stmt->execute()){
                        echo json_encode(['status' => 'success', 'message' => 'Repuesto creado exitosamente.']);
                    } else {
                        throw new Exception("Error al ejecutar la inserción");
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Error preparando la consulta (¿Existen las columnas en la base de datos?)");
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            }
        } else { // Edit
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }
            
            $query = "UPDATE repuestos SET codigo=?, id_marca=?, nombre=?, modelo=?, imagen_url=? WHERE id=?";
            try {
                if ($stmt = $conn->prepare($query)) {
                    $stmt->bind_param("sisssi", $codigo, $id_marca, $nombre, $modelo, $imagen_url, $id);
                    $stmt->execute();
                    $stmt->close();
                    echo json_encode(['status' => 'success', 'message' => 'Repuesto actualizado exitosamente.']);
                } else {
                    throw new Exception("Error preparando la consulta");
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            }
        }
        
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de repuesto no válido.']);
            exit;
        }
        
        $queryDelete = "DELETE FROM repuestos WHERE id = ?";
        try {
            if ($stmtDelete = $conn->prepare($queryDelete)) {
                $stmtDelete->bind_param("i", $id);
                $stmtDelete->execute();
                $stmtDelete->close();
                echo json_encode(['status' => 'success', 'message' => 'Repuesto eliminado exitosamente.']);
            } else {
                throw new Exception("Error preparando la consulta");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
