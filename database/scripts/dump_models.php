<?php
// Este es un script de prueba para conectar con la Inteligencia Artificial (Google Gemini)

// Cargamos nuestra llave secreta (contraseña) para poder usar la IA
$config = require 'C:/laragon/www/Motoverify/config/api.php';
$key = $config['gemini_api_key'];

// Preparamos la dirección web (URL) a la que le vamos a preguntar qué "modelos" o cerebros de IA están disponibles
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $key;

// Iniciamos la herramienta (cURL) que hace la llamada a esa página web de forma invisible
$ch = curl_init($url);
// Le decimos que queremos que nos devuelva la respuesta en texto, en lugar de solo imprimirla
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Apagamos la verificación de seguridad SSL (solo porque es un entorno de pruebas local)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Ejecutamos la llamada y guardamos lo que nos respondió la IA en una variable
$res = curl_exec($ch);
// Cerramos la herramienta
curl_close($ch);

// Tomamos esa respuesta y la guardamos en un archivo de texto en nuestra computadora
file_put_contents('C:/laragon/www/Motoverify/models_list.json', $res);

// Mostramos un mensaje diciendo que todo salió bien
echo "OK";
?>
