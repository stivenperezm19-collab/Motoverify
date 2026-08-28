<?php
// Incluimos la configuración de la base de datos
require_once '../config/database.php';
// Indicamos que nuestra respuesta será en formato JSON
header('Content-Type: application/json');

// Cargamos las claves secretas para usar la Inteligencia Artificial (IA)
$config = require '../config/api.php';
$gemini_api_key = $config['gemini_api_key'] ?? '';
$openrouter_api_key = $config['openrouter_api_key'] ?? '';

// Por ahora, forzamos el uso de Gemini original a petición del usuario
$useOpenRouter = false;
$useGemini = (!empty($gemini_api_key) && $gemini_api_key !== 'TU_API_KEY_AQUI');

// Comprobamos si hay al menos una clave configurada
if (!$useOpenRouter && !$useGemini) {
    echo json_encode(['status' => 'error', 'message' => 'Falta configurar una API Key en config/api.php']);
    exit;
}

// Verificamos si se envió un archivo y si llegó correctamente sin errores
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió ningún archivo válido.']);
    exit;
}

// Guardamos la información del archivo que nos enviaron
$file = $_FILES['file'];
$mime = mime_content_type($file['tmp_name']); // El tipo de archivo real (ej: image/jpeg)
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // La extensión (ej: jpg)

// Preparamos variables para guardar el contenido del archivo
$content = '';
$isImage = false;
$isPdf = false;

// Si el archivo es una imagen...
if (in_array($ext, ['jpg', 'jpeg', 'png']) || strpos($mime, 'image/') === 0) {
    $mime_type = $mime;
    // Convertimos la imagen a texto (Base64) para poder enviarla por internet
    $content = base64_encode(file_get_contents($file['tmp_name']));
// Si el archivo es un documento PDF...
} elseif ($ext === 'pdf' || $mime === 'application/pdf') {
    $mime_type = 'application/pdf';
    $content = base64_encode(file_get_contents($file['tmp_name']));
// Si el archivo es un documento de texto o tabla (CSV)...
} elseif (in_array($ext, ['csv', 'txt'])) {
    $mime_type = 'text/plain';
    $text_content = file_get_contents($file['tmp_name']);
    // Si el texto es demasiado largo, lo cortamos para que no colapse el sistema
    if (strlen($text_content) > 50000) {
        $text_content = substr($text_content, 0, 50000); 
    }
    $content = base64_encode($text_content);
// Si es cualquier otro tipo de archivo no permitido...
} else {
    echo json_encode(['status' => 'error', 'message' => 'Formato no soportado actualmente. Por favor sube CSV, TXT, PDF o Imágenes (JPG/PNG).']);
    exit;
}

// Aquí definimos las "instrucciones" (Prompt) para decirle a la IA qué debe hacer exactamente
$systemPrompt = "Eres un asistente experto en catálogos de repuestos de motocicletas. 
El usuario te enviará el contenido de un archivo (texto, csv, pdf o imagen de factura/lista).
Tu tarea es extraer todos los repuestos mencionados y devolver UNICAMENTE un objeto JSON válido con la propiedad \"repuestos\" que contenga un array.
El JSON debe tener la siguiente estructura estricta para cada objeto en el array:
{
  \"repuestos\": [
    {
      \"codigo_barras\": \"string o null si no se encuentra\",
      \"nombre\": \"string (nombre del repuesto, claro y conciso)\",
      \"id_marca\": integer o null (ID de la base de datos, inferido de la marca),
      \"modelo\": \"string (modelo de moto compatible, o null)\",
      \"confianza\": integer (0 a 100, donde 100 es que estás seguro de los datos, 50 si hay dudas),
      \"marca_texto\": \"string (nombre de la marca detectada)\",
      \"categoria_texto\": \"string (nombre de la categoría detectada, ej: Frenos, Motor, Transmisión, Eléctrico, etc.)\",
      \"id_categoria\": null
    }
  ]
}
Nota: Si detectas la marca o categoría, devuelve su nombre en 'marca_texto' y 'categoria_texto' para que el backend lo mapee.
SIEMPRE devuelve SOLO el objeto JSON, sin formato markdown, sin backticks.";

if ($useOpenRouter) {
    $url = 'https://openrouter.ai/api/v1/chat/completions';
    
    // Preparar el contenido dependiendo del tipo de archivo (OpenRouter / OpenAI format)
    if ($mime_type === 'text/plain' || $mime_type === 'application/pdf') {
        // Nota: OpenAI/OpenRouter gratuito a veces no soporta PDF directo como archivo binario.
        // Asumimos que $content para text/plain está en base64 según el código arriba, así que lo decodificamos si es texto puro
        $textToSend = ($mime_type === 'text/plain') ? base64_decode($content) : "Archivo PDF en Base64: " . $content;
        $messageContent = $systemPrompt . "\n\nExtrae los repuestos de este archivo. RESPONDE ÚNICAMENTE CON EL JSON, SIN NADA DE TEXTO ALREDEDOR:\n" . $textToSend;
        
        $payload = [
            "model" => "google/gemini-2.0-pro-exp-02-05:free",
            "messages" => [
                [
                    "role" => "user",
                    "content" => $messageContent
                ]
            ],
            "response_format" => ["type" => "json_object"]
        ];
    } else {
        // Es una imagen
        $payload = [
            "model" => "google/gemini-2.0-pro-exp-02-05:free",
            "messages" => [
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => $systemPrompt . "\n\nExtrae los repuestos de este archivo. RESPONDE ÚNICAMENTE CON EL JSON, SIN NADA DE TEXTO ALREDEDOR:"
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => "data:" . $mime_type . ";base64," . $content
                            ]
                        ]
                    ]
                ]
            ],
            "response_format" => ["type" => "json_object"]
        ];
    }
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openrouter_api_key,
        'HTTP-Referer: http://localhost',
        'X-Title: Motoverify'
    ];
} else {
    // Lógica original de Gemini
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent?key=' . $gemini_api_key;
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemPrompt . "\n\nExtrae los repuestos de este archivo:"],
                    [
                        "inline_data" => [
                            "mime_type" => $mime_type,
                            "data" => $content
                        ]
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "responseMimeType" => "application/json"
        ]
    ];
    $headers = ['Content-Type: application/json'];
}

// Iniciamos la conexión segura de internet (CURL)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Damos hasta 60 segundos para que procese todo

// Ejecutamos la petición y obtenemos la respuesta
$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Si hubo algún error en la conexión o la respuesta no fue exitosa (200)
if ($err || $httpCode !== 200) {
    $apiError = 'Error desconocido de red.';
    // Intentamos leer el mensaje de error de Google
    if ($response) {
        $errorObj = json_decode($response, true);
        if (isset($errorObj['error']['message'])) {
            $apiError = $errorObj['error']['message'];
        } elseif (isset($errorObj['error'])) {
            $apiError = is_string($errorObj['error']) ? $errorObj['error'] : json_encode($errorObj['error']);
        }
    }
    
    // Si la clave no es válida o no tiene permisos, mostramos un mensaje amigable
    if (strpos($apiError, 'API key not valid') !== false) {
        $apiError = 'La clave de Gemini configurada no es válida. Por favor obtén una clave real en aistudio.google.com y ponla en config/api.php.';
    } elseif (strpos($apiError, 'is not found') !== false) {
        $apiError = 'Tu clave de Gemini es válida, pero tu cuenta de Google no tiene acceso habilitado a los modelos "Gemini 1.5". Para solucionarlo: Entra a Google AI Studio, crea un NUEVO proyecto y genera una nueva API Key.';
    }
    
    // Detenemos el proceso y mostramos el error
    echo json_encode(['status' => 'error', 'message' => 'Error IA: ' . $apiError, 'debug' => $response]);
    exit;
}

// Transformamos la respuesta de la IA en datos para PHP
$responseData = json_decode($response, true);

// Extraemos solo el texto generado dependiendo de la API usada
if ($useOpenRouter) {
    $aiContent = $responseData['choices'][0]['message']['content'] ?? '';
} else {
    $aiContent = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

// Limpiamos el texto por si la IA responde con formato Markdown (```json ... ```)
$aiContent = trim($aiContent);
if (strpos($aiContent, '```json') !== false) {
    $aiContent = str_replace('```json', '', $aiContent);
    $aiContent = str_replace('```', '', $aiContent);
} elseif (strpos($aiContent, '```') !== false) {
    $aiContent = str_replace('```', '', $aiContent);
}
$aiContent = trim($aiContent);

// Convertimos ese texto generado a una estructura de datos real (Array)
$parsed = json_decode($aiContent, true);

// Si la IA no respondió en el formato esperado o falló el json_decode
if (!$parsed || !isset($parsed['repuestos'])) {
    echo json_encode(['status' => 'error', 'message' => 'La IA no devolvió un formato JSON válido o lo devolvió vacío.', 'raw' => $aiContent]);
    exit;
}

// Guardamos la lista de repuestos encontrada
$repuestos = $parsed['repuestos'];

// Intentar emparejar (mapear) las marcas y categorías detectadas por la IA con las que ya tenemos en nuestra Base de Datos
try {
    // 1. Emparejamiento de Marcas
    $resultM = $conn->query("SELECT id_marca as id, nombre FROM MARCA");
    if ($resultM) {
        $marcasDb = $resultM->fetch_all(MYSQLI_ASSOC);
        
        // Recorremos cada repuesto detectado
        foreach ($repuestos as &$rep) {
            // Si la IA detectó un nombre de marca pero no tenemos su ID...
            if (isset($rep['marca_texto']) && !empty($rep['marca_texto']) && empty($rep['id_marca'])) {
                $marcaTexto = strtolower(trim($rep['marca_texto'])); // Pasamos a minúsculas para comparar
                // Buscamos si existe una marca similar en nuestra base de datos
                foreach ($marcasDb as $mdb) {
                    if (strpos(strtolower($mdb['nombre']), $marcaTexto) !== false || strpos($marcaTexto, strtolower($mdb['nombre'])) !== false) {
                        // Si la encontramos, le asignamos el ID correcto
                        $rep['id_marca'] = $mdb['id'];
                        break; // Dejamos de buscar
                    }
                }
            }
        }
    }

    // 2. Emparejamiento de Categorías
    $resultC = $conn->query("SELECT id_categoria as id, nombre FROM CATEGORIA");
    if ($resultC) {
        $categoriasDb = $resultC->fetch_all(MYSQLI_ASSOC);
        
        foreach ($repuestos as &$rep) {
            // Si la IA detectó un nombre de categoría pero no tenemos su ID...
            if (isset($rep['categoria_texto']) && !empty($rep['categoria_texto']) && empty($rep['id_categoria'])) {
                $catTexto = strtolower(trim($rep['categoria_texto'])); // Pasamos a minúsculas
                // Buscamos coincidencias en nuestra base de datos
                foreach ($categoriasDb as $cdb) {
                    if (strpos(strtolower($cdb['nombre']), $catTexto) !== false || strpos($catTexto, strtolower($cdb['nombre'])) !== false) {
                        // Si coincide, asignamos el ID
                        $rep['id_categoria'] = $cdb['id'];
                        break; // Dejamos de buscar
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    // Ignoramos errores menores en el emparejamiento
}

// Enviamos los repuestos ya procesados de vuelta al navegador
echo json_encode(['status' => 'success', 'data' => $repuestos]);
