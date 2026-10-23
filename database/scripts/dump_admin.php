<?php
// Este es un pequeño script de prueba (herramienta interna)
// Conectamos con la base de datos
require 'C:/laragon/www/Motoverify/config/database.php';

// Le pedimos a la base de datos que nos muestre una lista de todas las "tablas" que tiene
// (Las tablas son como las hojas de cálculo donde guardamos la información)
$res = $conn->query("SHOW TABLES");

// Recorremos la lista y mostramos el nombre de cada tabla en la pantalla
while($row = $res->fetch_array()) print_r($row[0] . "\n");
?>
