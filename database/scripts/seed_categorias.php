<?php
// Incluimos la configuración para poder conectarnos a la base de datos
require_once '../config/database.php';
// Incluimos el modelo de Categoría que contiene las funciones para guardar datos
require_once '../models/CategoriaModel.php';

// Creamos un nuevo objeto para manejar las categorías y le pasamos nuestra conexión a la base de datos
$categoriaModel = new CategoriaModel($conn);

// Esta es una lista con las categorías básicas que queremos guardar en el sistema
$defaultCats = [
    ['nombre' => 'Sistema de motor',         'desc' => 'Pistón, cilindro, culata, válvulas y más'],
    ['nombre' => 'Sistema de transmisión',   'desc' => 'Embrague, caja de cambios, transmisión'],
    ['nombre' => 'Sistema de combustible',   'desc' => 'Carburador, inyección, bomba de combustible'],
    ['nombre' => 'Sistema eléctrico',        'desc' => 'Batería, encendido, luces, sensores'],
    ['nombre' => 'Sistema de frenos',        'desc' => 'Freno delantero, freno trasero, ABS'],
    ['nombre' => 'Suspensión',               'desc' => 'Horquilla delantera, amortiguador trasero'],
    ['nombre' => 'Chasis y estructura',      'desc' => 'Cuadro, basculante, soportes'],
    ['nombre' => 'Ruedas y neumáticos',      'desc' => 'Rines, llantas, cámaras, válvulas'],
    ['nombre' => 'Carrocería y accesorios',  'desc' => 'Carenados, espejos, direccionales, accesorios'],
];

// Recorremos una por una todas las categorías de nuestra lista
foreach ($defaultCats as $cat) {
    // Primero, preparamos una pregunta para la base de datos: ¿Ya existe una categoría con este nombre?
    $stmt = $conn->prepare("SELECT COUNT(*) FROM CATEGORIA WHERE nombre = ?");
    // Le pasamos el nombre de la categoría actual para que haga la búsqueda
    $stmt->bind_param("s", $cat['nombre']);
    // Ejecutamos la búsqueda
    $stmt->execute();
    // Guardamos la cantidad de veces que se encontró la categoría en una variable llamada $count
    $stmt->bind_result($count);
    $stmt->fetch(); // Obtenemos el resultado
    $stmt->close(); // Cerramos la consulta para liberar espacio
    
    // Si la cantidad es 0, significa que la categoría no existe todavía
    if ($count == 0) {
        // Entonces, usamos nuestro modelo para guardar esta nueva categoría en la base de datos
        $categoriaModel->create(['nombre' => $cat['nombre'], 'descripcion' => $cat['desc']]);
        // Mostramos un mensaje en pantalla indicando que la categoría se creó exitosamente
        echo "Creada categoría: " . $cat['nombre'] . "\n";
    }
}
// Al terminar de revisar y crear todas las categorías, mostramos un mensaje final
echo "Completado.";
?>
