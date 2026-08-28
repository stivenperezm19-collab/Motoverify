<?php
// Cargamos la conexión a la base de datos
require_once '../config/database.php';

// Avisamos que la respuesta será en formato JSON (datos estructurados)
header('Content-Type: application/json');

// Obtenemos el texto que fue escaneado por el lector QR y le quitamos los espacios en blanco extra
$qr_text = $_POST['qr_text'] ?? '';
$qr_text = trim($qr_text);

// Si el lector QR mandó un texto vacío, mostramos un error
if (empty($qr_text)) {
    echo json_encode(['status' => 'error', 'message' => 'Código QR vacío']);
    exit;
}

// Preparamos la búsqueda en la base de datos:
// Vamos a buscar un repuesto cuyo "código de barras" O "código interno" coincida exactamente con lo que leyó el QR
$query = "SELECT id_repuesto, nombre FROM REPUESTO 
          WHERE codigo_barras = ? OR codigo_interno = ? LIMIT 1"; // Solo queremos 1 resultado

$stmt = $conn->prepare($query);
// Reemplazamos los signos '?' por el texto del QR
$stmt->bind_param('ss', $qr_text, $qr_text);
// Ejecutamos la búsqueda
$stmt->execute(); 
// Obtenemos los resultados
$result = $stmt->get_result();

// Si encontramos un repuesto que coincida...
if ($result && $result->num_rows > 0) {
    $repuesto = $result->fetch_assoc();
    // Respondemos con éxito y mandamos el ID y el nombre del repuesto encontrado
    // (Esto sirve para que la pantalla se vaya automáticamente a ver ese repuesto)
    echo json_encode([
        'status' => 'success',
        'repuesto_id' => $repuesto['id_repuesto'],
        'repuesto_nombre' => $repuesto['nombre']
    ]);
} else {
    // Si no encontramos nada con ese código...
    echo json_encode([
        'status' => 'not_found',
        'message' => 'No se encontró ningún repuesto con ese código.'
    ]);
}
?>
