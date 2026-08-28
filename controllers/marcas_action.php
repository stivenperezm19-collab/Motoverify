<?php
// Iniciamos la sesión para poder recordar quién está usando el sistema
session_start();
// Le indicamos al navegador que nuestra respuesta estará en formato JSON
header('Content-Type: application/json');
// Cargamos la conexión a la base de datos y los modelos necesarios
require_once '../config/database.php';
require_once '../models/MarcaModel.php';
require_once '../models/ImagenModel.php';

// Preparamos nuestras herramientas para interactuar con Marcas e Imágenes
$marcaModel = new MarcaModel($conn);
$imagenModel = new ImagenModel($conn);

// (Esta parte está comentada, pero sirve para asegurar que solo usuarios registrados puedan hacer cambios)
/*
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autenticado.']);
    exit;
}
*/

// Verificamos si nos están enviando un formulario (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Revisamos qué "acción" quieren hacer (agregar, editar o borrar)
    $action = $_POST['action'] ?? '';
    
    // --- SI LA ACCIÓN ES AGREGAR UNA NUEVA MARCA (ADD) ---
    if ($action === 'add') {
        // Limpiamos los datos quitando espacios en blanco al inicio o al final
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        // Si no pusieron nombre, marcamos un error
        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca es obligatorio.']);
            exit;
        }
        
        // Juntamos la información
        $data = ['nombre' => $nombre, 'descripcion' => $descripcion];
        try {
            // Intentamos guardar la nueva marca en la base de datos
            $id_marca = $marcaModel->create($data);
            if ($id_marca) {
                // Si también subieron una imagen o logo para la marca...
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    // Preparamos la carpeta donde se guardará (la creamos si no existe)
                    $uploadDir = '../uploads/marcas/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    
                    // Armamos un nombre único para la imagen
                    $fileInfo = pathinfo($_FILES['imagen']['name']);
                    $ext = strtolower($fileInfo['extension']);
                    $newName = "marca_" . $id_marca . "_" . time() . "." . $ext;
                    $targetPath = $uploadDir . $newName;
                    
                    // Movemos la imagen a su carpeta final
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetPath)) {
                        // Guardamos el registro de la imagen en la base de datos
                        $ruta_archivo = 'uploads/marcas/' . $newName;
                        $imagenModel->saveImageAndLinkToMarca($id_marca, $ruta_archivo, $_FILES['imagen']['name'], $_FILES['imagen']['type'], $_FILES['imagen']['size']);
                    }
                }
                echo json_encode(['status' => 'success', 'message' => 'Marca agregada exitosamente.']);
            } else {
                throw new Exception("Error insertando marca");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
        }
        
    // --- SI LA ACCIÓN ES EDITAR UNA MARCA EXISTENTE (EDIT) ---
    } elseif ($action === 'edit') {
        // Tomamos el ID de la marca y limpiamos los nuevos datos
        $id = intval($_POST['id_marca'] ?? $_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        // Verificamos que el ID y el nombre sean correctos
        if ($id <= 0 || empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
            exit;
        }
        
        $data = ['nombre' => $nombre, 'descripcion' => $descripcion];
        try {
            // Intentamos actualizar la marca con los nuevos datos
            if ($marcaModel->update($id, $data)) {
                // Si cambiaron la imagen...
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/marcas/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    
                    $fileInfo = pathinfo($_FILES['imagen']['name']);
                    $ext = strtolower($fileInfo['extension']);
                    $newName = "marca_" . $id . "_" . time() . "." . $ext;
                    $targetPath = $uploadDir . $newName;
                    
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetPath)) {
                        $ruta_archivo = 'uploads/marcas/' . $newName;
                        // Vinculamos la nueva imagen a esta marca
                        $imagenModel->saveImageAndLinkToMarca($id, $ruta_archivo, $_FILES['imagen']['name'], $_FILES['imagen']['type'], $_FILES['imagen']['size']);
                    }
                }
                echo json_encode(['status' => 'success', 'message' => 'Marca actualizada exitosamente.']);
            } else {
                throw new Exception("Error actualizando marca");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
        }
        
    // --- SI LA ACCIÓN ES BORRAR (DELETE) ---
    } elseif ($action === 'delete') {
        $id = intval($_POST['id_marca'] ?? $_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de marca no válido.']);
            exit;
        }
        
        try {
            // Primero, nos aseguramos de que esta marca no tenga repuestos asignados
            if ($marcaModel->hasRepuestos($id)) {
                // Si tiene, bloqueamos el borrado
                echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar la marca porque tiene repuestos asociados a ella.']);
                exit;
            }
            
            // Si está libre de repuestos, procedemos a borrarla
            if ($marcaModel->delete($id)) {
                echo json_encode(['status' => 'success', 'message' => 'Marca eliminada exitosamente.']);
            } else {
                throw new Exception("Error eliminando marca");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
        }
    } else {
        // Si la acción no existe
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    }
} else {
    // Si no enviaron el formulario correctamente
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
