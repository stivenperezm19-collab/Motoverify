<?php
// ai_analyze.php - Este archivo conecta nuestro sistema con la Inteligencia Artificial (IA) de Google (Gemini) para reconocer imágenes.
// Le decimos al navegador que vamos a enviarle información en formato JSON (un formato común para enviar datos)
header('Content-Type: application/json');

// Cargamos el archivo de configuración donde guardamos la clave secreta de la IA
$config = require_once '../config/api.php';
// Obtenemos las claves
$apiKeyGemini = $config['gemini_api_key'] ?? '';
$apiKeyOpenRouter = $config['openrouter_api_key'] ?? '';

// Por ahora, forzamos el uso de Gemini original a petición del usuario
$useOpenRouter = false;
$useGemini = (!empty($apiKeyGemini) && $apiKeyGemini !== 'TU_API_KEY_AQUI');

if (!$useOpenRouter && !$useGemini) {
    // Si no hay ninguna clave, avisamos con un error
    echo json_encode(['status' => 'error', 'message' => 'Ni la clave de Gemini ni la de OpenRouter están configuradas. Inserta una en config/api.php']);
    exit;
}

// Verificamos si nos enviaron una imagen y si la subida fue exitosa (sin errores)
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió ninguna imagen o hubo un error en la subida.']);
    exit;
}

// Guardamos la ruta temporal donde se guardó la imagen subida
$tmpFilePath = $_FILES['image']['tmp_name'];
// Obtenemos el tipo de archivo (por ejemplo, si es JPG, PNG, etc.)
$mimeType = $_FILES['image']['type'];

// La IA de Gemini solo entiende ciertos formatos de imagen. Aquí tenemos la lista de permitidos:
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
// Si la imagen enviada no está en la lista de formatos permitidos...
if (!in_array($mimeType, $allowedMimeTypes)) {
    // Mostramos un error y nos detenemos
    echo json_encode(['status' => 'error', 'message' => 'Formato de imagen no soportado. Usa JPG, PNG o WebP.']);
    exit;
}

// ===== PASO 1: Consultar la BD para obtener los nombres de repuestos existentes =====
// Conectamos a nuestra base de datos ANTES de llamar a la IA
require_once '../config/database.php';

// Obtenemos la lista de nombres únicos de repuestos en el inventario
$catalogQuery = "SELECT DISTINCT r.nombre, c.nombre as categoria_nombre 
                 FROM REPUESTO r 
                 LEFT JOIN CATEGORIA c ON r.id_categoria = c.id_categoria 
                 ORDER BY r.nombre";
$catalogResult = $conn->query($catalogQuery);
$existingParts = [];
$partNames = [];
if ($catalogResult && $catalogResult->num_rows > 0) {
    while ($row = $catalogResult->fetch_assoc()) {
        $existingParts[] = $row;
        $partNames[] = $row['nombre'];
    }
}

// También obtenemos las categorías únicas
$catQuery = "SELECT DISTINCT nombre FROM CATEGORIA ORDER BY nombre";
$catResult = $conn->query($catQuery);
$categoryNames = [];
if ($catResult && $catResult->num_rows > 0) {
    while ($row = $catResult->fetch_assoc()) {
        $categoryNames[] = $row['nombre'];
    }
}

// Construimos la lista de repuestos disponibles para incluir en el prompt
$partListText = implode(', ', $partNames);
$categoryListText = implode(', ', $categoryNames);

// Convertimos la imagen a un código de texto (Base64) para poder enviarla por internet a la IA
$imageData = base64_encode(file_get_contents($tmpFilePath));

// ===== PASO 2: Prompt mejorado que incluye los repuestos existentes en BD =====
$prompt_text = "Actúa como un experto mecánico de motocicletas. Analiza la imagen e identifica el repuesto.

INVENTARIO DISPONIBLE EN BASE DE DATOS (debes elegir de esta lista si es posible):
Repuestos: " . $partListText . "
Categorías: " . $categoryListText . "

REGLAS ESTRICTAS:
1. Si la pieza de la imagen coincide con algún repuesto del INVENTARIO DISPONIBLE, responde EXACTAMENTE con ese nombre tal cual aparece en la lista.
2. Si ningún nombre del inventario coincide exactamente, responde con el nombre genérico de la pieza en español usando máximo 2-3 palabras clave.
3. NO incluyas marcas, colores, lados (izquierdo/derecho), posiciones (delantero/trasero) ni adjetivos.
4. Si la imagen NO es una pieza de motocicleta (ej. una persona, paisaje, moto completa), responde exactamente: NO_ES_REPUESTO.
5. No des explicaciones, no uses saltos de línea ni puntuación.
6. Responde SOLO con el nombre, nada más.";

if ($useOpenRouter) {
    $url = "https://openrouter.ai/api/v1/chat/completions";
    $payload = [
        "model" => "google/gemini-2.0-pro-exp-02-05:free", // Modelo gratuito de OpenRouter
        "messages" => [
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "text",
                        "text" => $prompt_text
                    ],
                    [
                        "type" => "image_url",
                        "image_url" => [
                            "url" => "data:" . $mimeType . ";base64," . $imageData
                        ]
                    ]
                ]
            ]
        ],
        "temperature" => 0.1,
        "max_tokens" => 150
    ];
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKeyOpenRouter,
        'HTTP-Referer: http://localhost', // Requerido por OpenRouter
        'X-Title: Motoverify' // Requerido por OpenRouter
    ];
} else {
    // Lógica para Gemini original
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent";
    $payload = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => $prompt_text
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
    $headers = [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKeyGemini
    ];
}

// Abrimos una conexión segura de internet (CURL)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
    echo json_encode(['status' => 'error', 'message' => 'Error de IA (HTTP ' . $httpCode . '): ' . $errorDetail]);
    exit;
}

$responseData = json_decode($response, true);

if ($useOpenRouter) {
    $aiResponse = $responseData['choices'][0]['message']['content'] ?? '';
} else {
    $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

// Limpiar el texto de Markdown (```json ... ```) si es necesario
$aiResponse = trim($aiResponse);
if (strpos($aiResponse, '```json') !== false) {
    $aiResponse = str_replace('```json', '', $aiResponse);
    $aiResponse = str_replace('```', '', $aiResponse);
} elseif (strpos($aiResponse, '```') !== false) {
    $aiResponse = str_replace('```', '', $aiResponse);
}
$aiText = trim($aiResponse);

if (empty($aiText)) {
    echo json_encode(['status' => 'error', 'message' => 'La IA no pudo identificar la imagen con claridad.', 'debug' => $response]);
    exit;
}

// Limpiamos el texto que dio la IA (le quitamos puntos y lo ponemos en minúsculas)
$aiTextClean = strtolower(rtrim($aiText, '.'));

// Verificamos si la IA detectó que la foto no es un repuesto, basándonos en las reglas que le dimos
if (strpos($aiTextClean, 'no_es_repuesto') !== false || strpos($aiTextClean, 'no es repuesto') !== false || strpos($aiTextClean, 'no_') === 0) {
    echo json_encode(['status' => 'error', 'message' => 'La imagen subida no parece ser un repuesto o pieza de motocicleta. Por favor, sube una fotografía válida.']);
    exit;
}

// ===== PASO 3: Búsqueda mejorada con sistema de puntuación =====

// Primero intentamos una coincidencia EXACTA con el nombre del repuesto
$exactQuery = "SELECT r.id_repuesto as id, r.nombre 
               FROM REPUESTO r 
               WHERE LOWER(r.nombre) = ?
               LIMIT 1";
$exactStmt = $conn->prepare($exactQuery);
$exactStmt->bind_param('s', $aiTextClean);
$exactStmt->execute();
$exactResult = $exactStmt->get_result();

if ($exactResult && $exactResult->num_rows > 0) {
    // ¡Coincidencia exacta encontrada! Este es el mejor caso.
    $repuesto = $exactResult->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'repuesto_id' => $repuesto['id'],
        'repuesto_nombre' => $repuesto['nombre'],
        'ai_detected' => $aiText
    ]);
    exit;
}

// Si no hay coincidencia exacta, buscamos con un sistema de puntuación (scoring)
// Cada palabra que coincida suma puntos, con mayor peso para coincidencias en el nombre
$keywords = array_filter(explode(' ', $aiTextClean), function($val) {
    return strlen($val) > 2; // Reducimos a >2 para capturar más palabras útiles como "kit", "eje", etc.
});

if (empty($keywords)) {
    $keywords = [$aiTextClean]; // Si no se pudo separar, usamos el texto completo
}

// Construimos una consulta que calcule un puntaje de relevancia para cada repuesto
// El nombre del repuesto tiene mayor peso (3 puntos), la categoría peso medio (2), y compatibilidades/marca peso menor (1)
$scoreExpressions = [];
$params = [];
$types = '';

foreach ($keywords as $word) {
    $word = trim($word);
    if (empty($word)) continue;
    
    // +3 puntos si la palabra está en el nombre del repuesto (lo más importante)
    $scoreExpressions[] = "(CASE WHEN LOWER(r.nombre) LIKE ? THEN 3 ELSE 0 END)";
    $params[] = "%" . $word . "%";
    $types .= 's';
    
    // +2 puntos si la palabra está en la categoría
    $scoreExpressions[] = "(CASE WHEN LOWER(c.nombre) LIKE ? THEN 2 ELSE 0 END)";
    $params[] = "%" . $word . "%";
    $types .= 's';
    
    // +1 punto si la palabra está en las compatibilidades
    $scoreExpressions[] = "(CASE WHEN LOWER(r.compatibilidades) LIKE ? THEN 1 ELSE 0 END)";
    $params[] = "%" . $word . "%";
    $types .= 's';
    
    // +1 punto si la palabra está en la marca
    $scoreExpressions[] = "(CASE WHEN LOWER(m.nombre) LIKE ? THEN 1 ELSE 0 END)";
    $params[] = "%" . $word . "%";
    $types .= 's';
}

if (!empty($scoreExpressions)) {
    $scoreFormula = implode(' + ', $scoreExpressions);
    
    // Necesitamos una condición WHERE que filtre solo repuestos que tengan AL MENOS una coincidencia
    $whereConditions = [];
    foreach ($keywords as $word) {
        $word = trim($word);
        if (empty($word)) continue;
        $whereConditions[] = "(LOWER(r.nombre) LIKE ? OR LOWER(c.nombre) LIKE ? OR LOWER(r.compatibilidades) LIKE ? OR LOWER(m.nombre) LIKE ?)";
        $params[] = "%" . $word . "%";
        $params[] = "%" . $word . "%";
        $params[] = "%" . $word . "%";
        $params[] = "%" . $word . "%";
        $types .= 'ssss';
    }
    
    $whereClause = implode(' OR ', $whereConditions);
    
    $query = "SELECT r.id_repuesto as id, r.nombre, 
              (" . $scoreFormula . ") as relevance_score
              FROM REPUESTO r
              LEFT JOIN MARCA m ON r.id_marca = m.id_marca
              LEFT JOIN CATEGORIA c ON r.id_categoria = c.id_categoria
              WHERE (" . $whereClause . ")
              HAVING relevance_score > 0
              ORDER BY relevance_score DESC, 
                       (CASE WHEN LOWER(r.nombre) LIKE ? THEN 0 ELSE 1 END)
              LIMIT 5";
    
    // Parámetro extra para el ORDER BY (coincidencia exacta del nombre completo primero)
    $params[] = "%" . $aiTextClean . "%";
    $types .= 's';
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $bestMatch = $result->fetch_assoc();
            
            // Solo aceptamos el resultado si tiene un puntaje mínimo de relevancia
            // Si la palabra clave principal está en el nombre, el puntaje mínimo es 3
            if ($bestMatch['relevance_score'] >= 2) {
                // Recolectamos también alternativas para mostrar al usuario
                $alternatives = [];
                while ($alt = $result->fetch_assoc()) {
                    if ($alt['relevance_score'] >= 2) {
                        $alternatives[] = [
                            'id' => $alt['id'],
                            'nombre' => $alt['nombre']
                        ];
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'repuesto_id' => $bestMatch['id'],
                    'repuesto_nombre' => $bestMatch['nombre'],
                    'ai_detected' => $aiText,
                    'relevance_score' => $bestMatch['relevance_score'],
                    'alternatives' => array_slice($alternatives, 0, 3)
                ]);
                exit;
            }
        }
    }
}

// Si no encontramos nada con buen puntaje, informamos al usuario qué vio la IA
echo json_encode([
    'status' => 'error',
    'message' => 'La IA identificó la imagen como: "' . $aiText . '", pero no encontramos coincidencias en tu inventario. Intenta buscar manualmente o verifica que el repuesto esté registrado en el sistema.'
]);
?>
