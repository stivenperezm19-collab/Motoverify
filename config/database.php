<?php
/*
 * ARCHIVO DE CONEXIÓN A LA BASE DE DATOS
 * --------------------------------------
 * Este archivo actúa como la llave principal del sistema. 
 * Su única función es abrir una comunicación directa entre nuestra página web 
 * y la base de datos (donde se guarda toda la información de repuestos y usuarios).
 */

// 1. Configuramos las credenciales para entrar a la base de datos
$host = '127.0.0.1';         // Dirección del servidor (127.0.0.1 significa "esta misma computadora")
$user = 'root';              // Nombre del usuario principal de la base de datos
$password = '';              // Contraseña (está vacía porque es un entorno local de pruebas)
$bd = 'consulta_repuestos';  // El nombre exacto de la base de datos a la que queremos entrar
$port = 3306;                // El puerto (o "puerta") estándar por el que escucha la base de datos MySQL

// 2. Intentamos abrir la conexión (usamos 'try' que significa intentar)
try {
    // Aquí creamos una nueva conexión directa usando las credenciales de arriba
    $conn = new mysqli($host, $user, $password, $bd, $port);
} catch (mysqli_connect_error $e) {
    // Si ocurre un problema (por ejemplo, la base de datos está apagada o la contraseña está mal),
    // el sistema "atrapa" el error (catch) y muestra un mensaje en pantalla, deteniendo todo.
    die('Error de Conexion: ' . $e->getMessage());
}
?>