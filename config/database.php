<?php
$host = '127.0.0.1';
$user = 'root';
$password = '';
$bd = 'gestion_repuestos';
$port = 3306;

try {
    $conn = new mysqli($host, $user, $password, $bd, $port);
} catch (mysqli_connect_error $e) {
    die('Error de Conexion: ' . $e->getMessage());
}
?>