<?php
// Iniciamos sesión y configuramos la respuesta para que el navegador la entienda (formato JSON)
session_start();
header('Content-Type: application/json');

// Incluimos las conexiones y modelos necesarios
require_once '../config/database.php';
require_once '../models/RepuestoModel.php';
require_once '../models/ImagenModel.php';

// Preparamos nuestras herramientas principales
$repuestoModel = new RepuestoModel($conn);
$imagenModel = new ImagenModel($conn);

// Verificamos si se envió un formulario de datos (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtenemos qué acción se quiere realizar (agregar, editar, borrar)
    $action = $_POST['action'] ?? '';
    
    // --- SI QUEREMOS AGREGAR O EDITAR UN REPUESTO ---
    if ($action === 'add' || $action === 'edit') {
        // Recogemos todos los datos del formulario y les quitamos los espacios en blanco sobrantes
        $codigo_interno = trim($_POST['codigo_interno'] ?? trim($_POST['codigo'] ?? ''));
        $codigo_barras = trim($_POST['codigo_barras'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $referencia_fabricante = trim($_POST['referencia_fabricante'] ?? '');
        // Los IDs siempre deben ser números enteros
        $id_marca = intval($_POST['id_marca'] ?? $_POST['marca_id'] ?? 0);
        $id_categoria = intval($_POST['id_categoria'] ?? $_POST['categoria_id'] ?? 0);
        $caracteristicas = trim($_POST['caracteristicas'] ?? '');
        $compatibilidades = trim($_POST['compatibilidades'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $estado = trim($_POST['estado'] ?? 'ACTIVO'); // Si no dicen el estado, por defecto es ACTIVO
        $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0.00;
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        $id = intval($_POST['id'] ?? intval($_POST['id_repuesto'] ?? 0)); // El ID del repuesto (solo para editar)
        
        // --- 1. Validación de datos obligatorios ---
        if (empty($codigo_interno) || $id_marca <= 0 || empty($nombre) || $id_categoria <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Los campos Código Interno, Marca, Categoría y Nombre son obligatorios.']);
            exit;
        }

        // --- 2. Verificación de Código de Barras repetido ---
        // (Un código de barras no puede estar en dos repuestos diferentes)
        if (!empty($codigo_barras)) {
            if ($repuestoModel->checkCodigoBarras($codigo_barras, $action === 'edit' ? $id : 0)) {
                echo json_encode(['status' => 'error', 'message' => 'El código de barras ya está registrado en otro repuesto.']);
                exit;
            }
        } else {
            $codigo_barras = null; // Si está vacío, lo guardamos como 'nada'
        }

        // --- 3. Verificación de Código Interno repetido ---
        // (El código interno es único para cada pieza en nuestro inventario)
        if ($repuestoModel->checkCodigoInterno($codigo_interno, $action === 'edit' ? $id : 0)) {
            echo json_encode(['status' => 'error', 'message' => 'El código interno ya está registrado en otro repuesto.']);
            exit;
        }

        // Agrupamos toda la información en un solo paquete para guardarlo fácilmente
        $data = [
            'codigo_interno' => $codigo_interno,
            'codigo_barras' => $codigo_barras,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'referencia_fabricante' => $referencia_fabricante,
            'id_marca' => $id_marca,
            'id_categoria' => $id_categoria,
            'caracteristicas' => $caracteristicas,
            'compatibilidades' => $compatibilidades,
            'observaciones' => $observaciones,
            'estado' => $estado,
            'precio' => $precio,
            'stock' => $stock
        ];

        // --- SI ES UN REPUESTO NUEVO (ADD) ---
        if ($action === 'add') {
            try {
                // Intentamos guardar los datos del repuesto en la base de datos
                $insert_id = $repuestoModel->create($data);
                
                // Si se guardó correctamente...
                if ($insert_id) {
                    
                    // Si el usuario también subió una foto del repuesto...
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        // Preparamos la carpeta (si no existe, la creamos)
                        $uploadDir = '../uploads/repuestos/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        
                        // Generamos un nombre único y aleatorio para la foto (para evitar sobreescribir)
                        $nombre_original = basename($_FILES['imagen']['name']);
                        $ext = pathinfo($nombre_original, PATHINFO_EXTENSION);
                        $nombre_archivo = uniqid() . '.' . $ext;
                        $ruta_destino = $uploadDir . $nombre_archivo; // Ruta en el servidor
                        $ruta_web = '/uploads/repuestos/' . $nombre_archivo; // Ruta para mostrar en la página
                        $tipo_archivo = $_FILES['imagen']['type'];
                        $tamano_bytes = $_FILES['imagen']['size'];
                        
                        // Guardamos físicamente la foto
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                            // Vinculamos la foto con el repuesto recién creado en la base de datos
                            $imagenModel->saveImageAndLinkToRepuesto($insert_id, $ruta_web, $nombre_original, $tipo_archivo, $tamano_bytes);
                        }
                    }

                    echo json_encode(['status' => 'success', 'message' => 'Repuesto creado exitosamente.']);
                } else {
                    throw new Exception("Error al ejecutar la inserción");
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            }
            
        // --- SI ESTAMOS EDITANDO UN REPUESTO (EDIT) ---
        } else { 
            // Si el ID no es válido, no podemos editar
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }
            
            try {
                // Actualizamos los datos del repuesto
                if ($repuestoModel->update($id, $data)) {
                    
                    // Si nos enviaron una foto nueva durante la edición...
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/repuestos/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        
                        // Creamos un nombre único
                        $nombre_original = basename($_FILES['imagen']['name']);
                        $ext = pathinfo($nombre_original, PATHINFO_EXTENSION);
                        $nombre_archivo = uniqid() . '.' . $ext;
                        $ruta_destino = $uploadDir . $nombre_archivo;
                        $ruta_web = '/uploads/repuestos/' . $nombre_archivo;
                        $tipo_archivo = $_FILES['imagen']['type'];
                        $tamano_bytes = $_FILES['imagen']['size'];
                        
                        // Guardamos y vinculamos la nueva foto a este repuesto existente
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                            $imagenModel->saveImageAndLinkToRepuesto($id, $ruta_web, $nombre_original, $tipo_archivo, $tamano_bytes);
                        }
                    }

                    echo json_encode(['status' => 'success', 'message' => 'Repuesto actualizado exitosamente.']);
                } else {
                    throw new Exception("Error preparando la consulta o el repuesto no existe.");
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            }
        }
        
    // --- SI QUEREMOS BORRAR UN REPUESTO (DELETE) ---
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? intval($_POST['id_repuesto'] ?? 0));
        
        // Si el ID es inválido
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de repuesto no válido.']);
            exit;
        }
        
        try {
            // Intentamos borrar el repuesto usando el modelo
            if ($repuestoModel->delete($id)) {
                echo json_encode(['status' => 'success', 'message' => 'Repuesto eliminado exitosamente.']);
            } else {
                throw new Exception("Error eliminando el repuesto");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
        }
    } else {
        // Acción desconocida
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    }
} else {
    // Método de envío incorrecto
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
