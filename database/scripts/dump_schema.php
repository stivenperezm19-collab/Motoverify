<?php
// Este es un script de prueba (herramienta interna) para ver cómo están construidas nuestras tablas
// Incluimos la conexión a la base de datos
require_once '../config/database.php';

// Hacemos una lista de las tablas que queremos revisar
$tables = ['repuestos', 'marcas', 'catalogos', 'repuesto_compatibilidad', 'ubicaciones'];

// Revisamos cada tabla de la lista, una por una
foreach ($tables as $table) {
    // Imprimimos el nombre de la tabla como si fuera un título
    echo "=== $table ===\n";
    // Le pedimos a la base de datos que nos "describa" la estructura de esta tabla
    // (Por ejemplo, qué columnas tiene: ID, nombre, precio, etc.)
    $result = $conn->query("DESCRIBE $table");
    
    // Si la tabla existe y la base de datos nos responde bien...
    if ($result) {
        // Leemos cada columna (o "campo") de esa tabla y la mostramos en la pantalla
        while ($row = $result->fetch_assoc()) {
            echo "{$row['Field']} - {$row['Type']}\n"; // Ejemplo de salida: "nombre - varchar(255)"
        }
    } else {
        // Si no existe o hay un error
        echo "Error o la tabla no existe.\n";
    }
    // Agregamos un salto de línea (espacio en blanco) para separar una tabla de la siguiente
    echo "\n";
}
?>
