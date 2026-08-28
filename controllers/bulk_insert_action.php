<?php
// Incluimos las conexiones y los modelos que necesitamos para hablar con la base de datos
require_once '../config/database.php';
require_once '../models/RepuestoModel.php';
require_once '../models/MarcaModel.php';
require_once '../models/CategoriaModel.php';

// Indicamos que vamos a responder en formato JSON
header('Content-Type: application/json');

// Recibimos los datos (repuestos) que el navegador nos acaba de enviar
$input = json_decode(file_get_contents('php://input'), true);

// Verificamos que los datos que llegaron sean correctos y tengan la forma de una lista de repuestos
if (!$input || !isset($input['repuestos']) || !is_array($input['repuestos'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

$repuestos = $input['repuestos'];

// Si la lista está vacía, no hay nada que guardar
if (count($repuestos) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No hay repuestos para guardar.']);
    exit;
}

// Preparamos nuestro modelo para poder guardar repuestos
$repuestoModel = new RepuestoModel($conn);

$count = 0; // Aquí llevaremos la cuenta de cuántos repuestos guardamos con éxito

// Iniciamos una "transacción". Esto significa que si hay un error a la mitad, 
// podemos deshacer todo para que la base de datos no quede a medias.
$conn->begin_transaction();

try {
    // Revisamos cada uno de los repuestos de la lista, uno por uno
    foreach ($repuestos as $rep) {
        // Limpiamos los códigos por si traen espacios extra
        $codigo_barras = !empty($rep['codigo_barras']) ? trim($rep['codigo_barras']) : null;
        $codigo = !empty($rep['codigo']) ? trim($rep['codigo']) : ''; // Fallback (respaldo)
        
        // Si falta uno de los códigos, tratamos de usar el otro para rellenar
        if (empty($codigo) && !empty($codigo_barras)) $codigo = $codigo_barras;
        if (empty($codigo_barras) && !empty($codigo)) $codigo_barras = $codigo;

        // Si de plano no hay ningún código, generamos uno automáticamente 
        // para que la base de datos no nos marque un error de duplicado.
        if (empty($codigo)) {
            // Creamos un código tipo: "IA-A1B2C3-5"
            $codigo = 'IA-' . strtoupper(substr(uniqid(), -6)) . '-' . $count;
        }
        
        // Aseguramos que el código de barras quede como "nulo" si sigue vacío
        if (empty($codigo_barras)) {
            $codigo_barras = null;
        }

        // Extraemos el resto de los datos básicos
        $nombre = !empty($rep['nombre']) ? $rep['nombre'] : 'Repuesto Desconocido';
        $id_marca = !empty($rep['id_marca']) ? intval($rep['id_marca']) : null;
        $id_categoria = !empty($rep['id_categoria']) ? intval($rep['id_categoria']) : null;
        $modelo = !empty($rep['modelo']) ? $rep['modelo'] : '';

        // Si la IA nos dio el NOMBRE de una marca pero no tenemos su ID numérico...
        if (!$id_marca && !empty($rep['marca_texto'])) {
            $marca_texto = trim($rep['marca_texto']);
            
            // Buscamos en nuestra base de datos si esa marca ya existe
            $stmt = $conn->prepare("SELECT id_marca FROM MARCA WHERE nombre LIKE ? LIMIT 1");
            $searchTerm = "%" . $marca_texto . "%";
            $stmt->bind_param("s", $searchTerm);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                // Si existe, tomamos su ID
                $id_marca = intval($row['id_marca']);
            } else {
                // Si NO existe, la creamos en ese momento y tomamos su nuevo ID
                $marcaModel = new MarcaModel($conn);
                $id_marca = $marcaModel->create(['nombre' => $marca_texto, 'descripcion' => 'Creada automáticamente por IA']);
            }
            $stmt->close();
        }

        // Hacemos exactamente lo mismo para la Categoría
        if (!$id_categoria && !empty($rep['categoria_texto'])) {
            $categoria_texto = trim($rep['categoria_texto']);
            
            // Buscamos si ya existe
            $stmt = $conn->prepare("SELECT id_categoria FROM CATEGORIA WHERE nombre LIKE ? LIMIT 1");
            $searchTerm = "%" . $categoria_texto . "%";
            $stmt->bind_param("s", $searchTerm);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $id_categoria = intval($row['id_categoria']);
            } else {
                // Si no existe, creamos la nueva categoría
                $categoriaModel = new CategoriaModel($conn);
                $id_categoria = $categoriaModel->create(['nombre' => $categoria_texto, 'descripcion' => 'Creada automáticamente por IA']);
            }
            $stmt->close();
        }

        // Si después de todo esto seguimos sin tener una marca válida, 
        // saltamos este repuesto (la marca es obligatoria)
        if (!$id_marca) {
            continue;
        }

        // Preparamos todo el paquete de datos para guardar el repuesto
        $data = [
            'codigo_interno' => $codigo, 
            'codigo_barras' => $codigo_barras,
            'nombre' => $nombre,
            'descripcion' => '',
            'referencia_fabricante' => '',
            'id_marca' => $id_marca,
            'id_categoria' => $id_categoria, // Puede ser nulo si la IA no supo qué poner
            'caracteristicas' => '',
            'compatibilidades' => $modelo,
            'observaciones' => 'Importado con IA',
            'estado' => 'Activo'
        ];

        // Intentamos guardar el repuesto en la base de datos
        if ($repuestoModel->create($data)) {
            // Si funciona, sumamos 1 al contador
            $count++;
        }
    }

    // Si llegamos hasta aquí sin problemas, confirmamos todos los guardados ("commit")
    $conn->commit();

    // Le decimos al navegador que todo salió bien y cuántos guardamos
    echo json_encode(['status' => 'success', 'message' => "Se guardaron $count repuestos correctamente."]);

} catch (Exception $e) {
    // Si ocurrió algún error grave (Exception), deshacemos todo lo que intentamos guardar ("rollback")
    $conn->rollback();
    // Y devolvemos el mensaje de error
    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
