<?php
// ai_analyze.php - Integración con Gemini API (Reconocimiento Visual IA)
header('Content-Type: application/json');

// Cargar la API Key
$config = require_once '../config/api.php';
$apiKey = $config['gemini_api_key'] ?? '';

if (empty($apiKey) || $apiKey === 'TU_API_KEY_AQUI') {
    echo json_encode(['status' => 'error', 'message' => 'La API Key de Gemini no está configurada. Por favor, inserta tu clave en config/api.php']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió ninguna imagen o hubo un error en la subida.']);
    exit;
}

$tmpFilePath = $_FILES['image']['tmp_name'];
$mimeType = $_FILES['image']['type'];

// Gemini API soporta image/jpeg, image/png, image/webp, image/heic, image/heif
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
if (!in_array($mimeType, $allowedMimeTypes)) {
    echo json_encode(['status' => 'error', 'message' => 'Formato de imagen no soportado. Usa JPG, PNG o WebP.']);
    exit;
}

$imageData = base64_encode(file_get_contents($tmpFilePath));

// Endpoint de Gemini API - Alias que apunta al modelo flash más reciente disponible
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent";

$payload = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => "Eres un experto en repuestos de motocicletas. Identifica el repuesto o pieza de moto que aparece en esta imagen. Responde únicamente con el nombre genérico de la pieza (por ejemplo: 'pastilla de freno', 'bujia', 'carburador', 'llanta'). Si la imagen NO es un repuesto o pieza de motocicleta (por ejemplo, si es una persona, un animal, un paisaje, etc.), responde exactamente con la palabra: NO_ES_REPUESTO. No des explicaciones ni agregues puntuación al final."
                ],
                [
                    "inline_data" => [
                        "mime_type" => $mimeType,
                        "data" => $imageData
                    ]
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.1,
        "maxOutputTokens" => 150
    ]
];

// AUTENTICACIÓN: Usar el header x-goog-api-key (compatible con claves AQ. y AIzaSy)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $apiKey
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error de red: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    $errorDetail = '';
    $respJson = json_decode($response, true);
    if (isset($respJson['error']['message'])) {
        $errorDetail = $respJson['error']['message'];
    }
    echo json_encode(['status' => 'error', 'message' => 'Error de Gemini (HTTP ' . $httpCode . '): ' . $errorDetail]);
    exit;
}

$responseData = json_decode($response, true);
$aiText = '';

if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $aiText = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
}

if (empty($aiText)) {
    echo json_encode(['status' => 'error', 'message' => 'La IA no pudo identificar la imagen con claridad.']);
    exit;
}

// Conectar a BD para buscar la respuesta de la IA
require_once '../config/database.php';

// Limpiamos el texto (quitar puntos, a minúsculas)
$aiTextClean = strtolower(rtrim($aiText, '.'));

// Validar si la IA detectó que no es un repuesto
if (strpos($aiTextClean, 'no_es_repuesto') !== false || strpos($aiTextClean, 'no es repuesto') !== false || strpos($aiTextClean, 'no_') === 0) {
    echo json_encode(['status' => 'error', 'message' => 'La imagen subida no parece ser un repuesto o pieza de motocicleta. Por favor, sube una fotografía válida.']);
    exit;
}

// Buscamos palabras clave de más de 3 letras
$keywords = array_filter(explode(' ', $aiTextClean), function($val) {
    return strlen($val) > 3;
});

$likeConditions = [];
$params = [];
$types = '';

if (!empty($keywords)) {
    foreach ($keywords as $word) {
        $likeConditions[] = "nombre LIKE ?";
        $params[] = "%" . $word . "%";
        $types .= 's';
    }
} else {
    $likeConditions[] = "nombre LIKE ?";
    $params[] = "%" . $aiTextClean . "%";
    $types .= 's';
}

$query = "SELECT id, nombre FROM repuestos WHERE (" . implode(' OR ', $likeConditions) . ") LIMIT 1";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $repuesto = $result->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'repuesto_id' => $repuesto['id'],
        'repuesto_nombre' => $repuesto['nombre'],
        'ai_detected' => $aiText
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'La IA identificó la imagen como: "' . $aiText . '", pero no encontramos coincidencias exactas en tu inventario local.'
    ]);
}
?>
