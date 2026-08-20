<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$config = require '../config/api.php';
$openai_api_key = $config['openai_api_key'] ?? '';

if (empty($openai_api_key) || strpos($openai_api_key, 'aqui-tu-clave') !== false) {
    echo JSON_encode(['status' => 'error', 'message' => 'Falta configurar la API Key de OpenAI en config/api.php']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo JSON_encode(['status' => 'error', 'message' => 'No se recibió ningún archivo válido.']);
    exit;
}

$file = $_FILES['file'];
$mime = mime_content_type($file['tmp_name']);
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$content = '';
$isImage = false;

if (in_array($ext, ['jpg', 'jpeg', 'png']) || strpos($mime, 'image/') === 0) {
    $isImage = true;
    $imageData = base64_encode(file_get_contents($file['tmp_name']));
    $content = "data:" . $mime . ";base64," . $imageData;
} elseif (in_array($ext, ['csv', 'txt'])) {
    $content = file_get_contents($file['tmp_name']);
    if (strlen($content) > 50000) {
        $content = substr($content, 0, 50000); // Limitar tamaño
    }
} else {
    echo JSON_encode(['status' => 'error', 'message' => 'Formato no soportado actualmente. Por favor sube CSV, TXT o Imágenes (JPG/PNG).']);
    exit;
}

// Construir la petición a OpenAI
$systemPrompt = "Eres un asistente experto en catálogos de repuestos de motocicletas. 
El usuario te enviará el contenido de un archivo (texto, csv o imagen de factura/lista).
Tu tarea es extraer todos los repuestos mencionados y devolver UNICAMENTE un JSON Array válido.
El JSON debe tener la siguiente estructura estricta para cada objeto:
[
  {
    \"codigo_barras\": \"string o null si no se encuentra\",
    \"nombre\": \"string (nombre del repuesto, claro y conciso)\",
    \"id_marca\": integer o null (ID de la base de datos, inferido de la marca),
    \"modelo\": \"string (modelo de moto compatible, o null)\",
    \"confianza\": integer (0 a 100, donde 100 es que estás seguro de los datos, 50 si hay dudas)
  }
]
Nota: Si detectas la marca, debes intentar devolver su ID, pero como no conoces la BD exacta, devuelve null en id_marca y el nombre de la marca detectada en el campo 'marca_texto' (agregar este campo temporalmente) para que el backend lo mapee.
SIEMPRE devuelve SOLO el array JSON, sin formato markdown, sin backticks.";

$messages = [
    [
        "role" => "system",
        "content" => $systemPrompt
    ]
];

if ($isImage) {
    $messages[] = [
        "role" => "user",
        "content" => [
            [
                "type" => "text",
                "text" => "Extrae los repuestos de esta imagen y devuélvelos en formato JSON estricto."
            ],
            [
                "type" => "image_url",
                "image_url" => [
                    "url" => $content
                ]
            ]
        ]
    ];
} else {
    $messages[] = [
        "role" => "user",
        "content" => "Extrae los repuestos del siguiente texto/CSV y devuélvelos en formato JSON estricto:\n\n" . $content
    ];
}

$ch = curl_init('https://api.openai.com/v1/chat/completions');
$payload = [
    "model" => "gpt-4o",
    "messages" => $messages,
    "temperature" => 0.1,
    "response_format" => ["type" => "json_object"] // Wait, json_object expects a single object, not array.
];
// Ajuste para json_object: El prompt debe pedir un objeto que contenga un array
$payload['messages'][0]['content'] = str_replace('UN JSON Array válido', 'un objeto JSON con la propiedad "repuestos" que contenga el array', $payload['messages'][0]['content']);
$payload['messages'][0]['content'] = str_replace("[\n  {", "{\n  \"repuestos\": [\n    {", $payload['messages'][0]['content']);
$payload['messages'][0]['content'] = str_replace("  }\n]", "    }\n  ]\n}", $payload['messages'][0]['content']);


curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $openai_api_key
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || $httpCode !== 200) {
    echo json_encode(['status' => 'error', 'message' => 'Error al comunicarse con la IA de OpenAI.', 'debug' => $response]);
    exit;
}

$responseData = json_decode($response, true);
$aiContent = $responseData['choices'][0]['message']['content'] ?? '';

$parsed = json_decode($aiContent, true);

if (!$parsed || !isset($parsed['repuestos'])) {
    echo json_encode(['status' => 'error', 'message' => 'La IA no devolvió un formato JSON válido.', 'raw' => $aiContent]);
    exit;
}

$repuestos = $parsed['repuestos'];

// Intentar mapear marcas detectadas a IDs reales
try {
    $stmt = $pdo->query("SELECT id, nombre FROM marcas");
    $marcasDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($repuestos as &$rep) {
        if (isset($rep['marca_texto']) && !empty($rep['marca_texto']) && empty($rep['id_marca'])) {
            $marcaTexto = strtolower(trim($rep['marca_texto']));
            foreach ($marcasDb as $mdb) {
                if (strpos(strtolower($mdb['nombre']), $marcaTexto) !== false || strpos($marcaTexto, strtolower($mdb['nombre'])) !== false) {
                    $rep['id_marca'] = $mdb['id'];
                    break;
                }
            }
        }
    }
} catch (Exception $e) {}

echo json_encode(['status' => 'success', 'data' => $repuestos]);
