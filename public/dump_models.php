<?php
$config = require 'C:/laragon/www/Motoverify/config/api.php';
$key = $config['gemini_api_key'];
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $key;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Disable SSL verify just in case
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
file_put_contents('C:/laragon/www/Motoverify/models_list.json', $res);
echo "OK";
