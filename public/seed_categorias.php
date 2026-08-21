<?php
require_once '../config/database.php';
require_once '../models/CategoriaModel.php';

$categoriaModel = new CategoriaModel($conn);

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

foreach ($defaultCats as $cat) {
    // Check if it exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM CATEGORIA WHERE nombre = ?");
    $stmt->bind_param("s", $cat['nombre']);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count == 0) {
        $categoriaModel->create(['nombre' => $cat['nombre'], 'descripcion' => $cat['desc']]);
        echo "Creada categoría: " . $cat['nombre'] . "\n";
    }
}
echo "Completado.";
?>
