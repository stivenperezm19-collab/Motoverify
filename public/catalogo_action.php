<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $icono = trim($_POST['icono'] ?? '');
        
        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la categoría es obligatorio.']);
            exit;
        }

        if ($action === 'add') {
            $query = "INSERT INTO categorias (nombre, descripcion, icono) VALUES (?, ?, ?)";
            try {
                if ($stmt = $conn->prepare($query)) {
                    $stmt->bind_param("sss", $nombre, $descripcion, $icono);
                    if($stmt->execute()){
                        echo json_encode(['status' => 'success', 'message' => 'Categoría creada exitosamente.']);
                    } else {
                        throw new Exception("Error al insertar.");
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Error preparando consulta. Verifica la BD.");
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            }
        } else {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }
            
            $query = "UPDATE categorias SET nombre=?, descripcion=?, icono=? WHERE id=?";
            try {
                if ($stmt = $conn->prepare($query)) {
                    $stmt->bind_param("sssi", $nombre, $descripcion, $icono, $id);
                    $stmt->execute();
                    $stmt->close();
                    echo json_encode(['status' => 'success', 'message' => 'Categoría actualizada.']);
                } else {
                    throw new Exception("Error preparando consulta.");
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            }
        }
        
    } elseif ($action === 'check_delete') {
        // Verifica si la categoría tiene repuestos o subcategorías
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID no válido.']);
            exit;
        }
        
        try {
            // Verificar dependencias (subcategorías) - Flujo Alternativo 2a
            // Asumimos campo id_padre en categorias. Si falla la consulta, ignoramos subcategorías.
            $hasSubcats = false;
            $resPadre = $conn->query("SELECT id FROM categorias WHERE id_padre = $id LIMIT 1");
            if ($resPadre && $resPadre->num_rows > 0) {
                $hasSubcats = true;
            }
            
            if ($hasSubcats) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Esta es una categoría principal con subcategorías activas. No puede ser eliminada directamente.' // Flujo Alternativo 2a
                ]);
                exit;
            }
            
            // Verificar repuestos asignados
            $needsReassign = false;
            $resReps = $conn->query("SELECT id FROM repuestos WHERE id_categoria = $id LIMIT 1");
            if ($resReps && $resReps->num_rows > 0) {
                $needsReassign = true;
            }
            
            echo json_encode([
                'status' => 'success',
                'needs_reassign' => $needsReassign
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al verificar dependencias.']);
        }
        
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $id_nueva_categoria = intval($_POST['id_nueva_categoria'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID no válido.']);
            exit;
        }
        
        $conn->begin_transaction();
        try {
            // Si hay id_nueva_categoria, reasignar repuestos primero
            if ($id_nueva_categoria > 0) {
                $queryUpdate = "UPDATE repuestos SET id_categoria = ? WHERE id_categoria = ?";
                if ($stmtUpd = $conn->prepare($queryUpdate)) {
                    $stmtUpd->bind_param("ii", $id_nueva_categoria, $id);
                    $stmtUpd->execute();
                    $stmtUpd->close();
                }
            }
            
            // Eliminar categoría
            $queryDel = "DELETE FROM categorias WHERE id = ?";
            if ($stmtDel = $conn->prepare($queryDel)) {
                $stmtDel->bind_param("i", $id);
                $stmtDel->execute();
                $stmtDel->close();
            }
            
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Categoría eliminada exitosamente.']);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
