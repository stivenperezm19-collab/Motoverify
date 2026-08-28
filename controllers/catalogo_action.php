<?php
// Iniciamos la "sesión" para saber quién está conectado
session_start();
// Le decimos al navegador que la respuesta será en formato JSON
header('Content-Type: application/json');
// Incluimos la conexión a la base de datos y los modelos de Categoría e Imagen
require_once '../config/database.php';
require_once '../models/CategoriaModel.php';
require_once '../models/ImagenModel.php';

// Preparamos las herramientas (modelos) para usarlas más adelante
$categoriaModel = new CategoriaModel($conn);
$imagenModel = new ImagenModel($conn);

// Verificamos si nos están enviando datos mediante el método POST (como cuando se envía un formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Revisamos qué "acción" quieren hacer (agregar, editar, borrar, etc.)
    $action = $_POST['action'] ?? '';
    
    // Si la acción es agregar una nueva categoría o editar una existente...
    if ($action === 'add' || $action === 'edit') {
        // Limpiamos los datos que nos enviaron para quitar espacios extra
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        // El nombre es obligatorio. Si está vacío, devolvemos un error.
        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la categoría es obligatorio.']);
            exit;
        }

        // Agrupamos el nombre y la descripción
        $data = ['nombre' => $nombre, 'descripcion' => $descripcion];

        // --- SI LA ACCIÓN ES AGREGAR (ADD) ---
        if ($action === 'add') {
            try {
                // Intentamos crear la categoría en la base de datos
                $id_categoria = $categoriaModel->create($data);
                // Si se creó con éxito...
                if ($id_categoria) {
                    // Verificamos si también nos enviaron una imagen y si subió bien
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        // Creamos la carpeta donde guardaremos la foto si no existe
                        $uploadDir = '../uploads/categorias/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        
                        // Le damos un nombre único a la imagen para que no se sobreescriba con otra
                        $fileInfo = pathinfo($_FILES['imagen']['name']);
                        $ext = strtolower($fileInfo['extension']); // La extensión (ej: jpg)
                        $newName = "cat_" . $id_categoria . "_" . time() . "." . $ext;
                        $targetPath = $uploadDir . $newName;
                        
                        // Movemos la foto temporal a su carpeta final
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetPath)) {
                            // Si se movió bien, la guardamos también en la base de datos vinculándola a esta categoría
                            $ruta_archivo = 'uploads/categorias/' . $newName;
                            $imagenModel->saveImageAndLinkToCategoria($id_categoria, $ruta_archivo, $_FILES['imagen']['name'], $_FILES['imagen']['type'], $_FILES['imagen']['size']);
                        }
                    }
                    // Avisamos que todo fue un éxito
                    echo json_encode(['status' => 'success', 'message' => 'Categoría creada exitosamente.']);
                } else {
                    // Si no se pudo crear, lanzamos un error
                    throw new Exception("Error al insertar.");
                }
            } catch (Exception $e) {
                // Si algo falló en el proceso, avisamos del error de base de datos
                echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            }
        
        // --- SI LA ACCIÓN ES EDITAR (EDIT) ---
        } else {
            // Buscamos el ID de la categoría que queremos editar
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }
            
            try {
                // Intentamos actualizar la información de la categoría
                if ($categoriaModel->update($id, $data)) {
                    // Si también nos enviaron una nueva imagen...
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/categorias/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        
                        // Le damos un nombre único y la guardamos
                        $fileInfo = pathinfo($_FILES['imagen']['name']);
                        $ext = strtolower($fileInfo['extension']);
                        $newName = "cat_" . $id . "_" . time() . "." . $ext;
                        $targetPath = $uploadDir . $newName;
                        
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetPath)) {
                            // Vinculamos la nueva imagen a la categoría en la base de datos
                            $ruta_archivo = 'uploads/categorias/' . $newName;
                            $imagenModel->saveImageAndLinkToCategoria($id, $ruta_archivo, $_FILES['imagen']['name'], $_FILES['imagen']['type'], $_FILES['imagen']['size']);
                        }
                    }
                    echo json_encode(['status' => 'success', 'message' => 'Categoría actualizada.']);
                } else {
                    throw new Exception("Error al actualizar.");
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            }
        }
        
    // --- SI LA ACCIÓN ES REVISAR ANTES DE BORRAR (CHECK_DELETE) ---
    // (Útil para saber si hay repuestos huérfanos si borramos esta categoría)
    } elseif ($action === 'check_delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID no válido.']);
            exit;
        }
        
        try {
            // Primero revisamos si tiene "sub-categorías" hijas. Si es así, no dejamos borrarla directamente.
            if ($categoriaModel->hasSubcategorias($id)) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Esta es una categoría principal con subcategorías activas. No puede ser eliminada directamente.'
                ]);
                exit;
            }
            
            // Luego revisamos si tiene repuestos. Si tiene, habrá que reasignarlos a otra categoría.
            $needsReassign = $categoriaModel->hasRepuestos($id);
            
            echo json_encode([
                'status' => 'success',
                'needs_reassign' => $needsReassign
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al verificar dependencias.']);
        }
        
    // --- SI LA ACCIÓN ES BORRAR DEFINITIVAMENTE (DELETE) ---
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $id_nueva_categoria = intval($_POST['id_nueva_categoria'] ?? 0); // Por si queremos reasignar repuestos a otra
        
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID no válido.']);
            exit;
        }
        
        // Iniciamos una transacción segura
        $conn->begin_transaction();
        try {
            // Si nos dijeron a qué otra categoría mandar los repuestos, los movemos primero
            if ($id_nueva_categoria > 0) {
                $categoriaModel->reassignRepuestos($id, $id_nueva_categoria);
            }
            
            // Ahora sí, borramos la categoría actual
            if ($categoriaModel->delete($id)) {
                $conn->commit(); // Confirmamos los cambios
                echo json_encode(['status' => 'success', 'message' => 'Categoría eliminada exitosamente.']);
            } else {
                throw new Exception("No se pudo eliminar.");
            }
        } catch (Exception $e) {
            $conn->rollback(); // Si algo falló, deshacemos todo para evitar errores
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    } else {
        // Si nos piden una acción que no conocemos
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    }
} else {
    // Si intentan acceder al archivo de forma incorrecta (sin mandar formulario)
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
