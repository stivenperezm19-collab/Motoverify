<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Validación básica de sesión
/*
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autenticado.']);
    exit;
}
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca es obligatorio.']);
            exit;
        }
        
        $query = "INSERT INTO marcas (nombre) VALUES (?)";
        try {
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("s", $nombre);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['status' => 'success', 'message' => 'Marca agregada exitosamente.']);
            } else {
                throw new Exception("Error preparando la consulta");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
        }
        
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($id) || empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
            exit;
        }
        
        $query = "UPDATE marcas SET nombre = ? WHERE id = ?";
        try {
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("si", $nombre, $id);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['status' => 'success', 'message' => 'Marca actualizada exitosamente.']);
            } else {
                throw new Exception("Error preparando la consulta");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
        }
        
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID de marca no válido.']);
            exit;
        }
        
        // --- Validación: Flujo Alternativo 2a ---
        // Verificar si existen repuestos asociados a esta marca
        $queryCheck = "SELECT COUNT(*) as total FROM repuestos WHERE id_marca = ?";
        try {
            if ($stmtCheck = $conn->prepare($queryCheck)) {
                $stmtCheck->bind_param("i", $id);
                $stmtCheck->execute();
                $result = $stmtCheck->get_result();
                $row = $result->fetch_assoc();
                $stmtCheck->close();
                
                if ($row['total'] > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar la marca porque tiene repuestos asociados a ella.']);
                    exit;
                }
            }
            
            // Si no hay repuestos, se procede a eliminar
            $queryDelete = "DELETE FROM marcas WHERE id = ?";
            if ($stmtDelete = $conn->prepare($queryDelete)) {
                $stmtDelete->bind_param("i", $id);
                $stmtDelete->execute();
                $stmtDelete->close();
                echo json_encode(['status' => 'success', 'message' => 'Marca eliminada exitosamente.']);
            } else {
                throw new Exception("Error preparando la consulta");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
