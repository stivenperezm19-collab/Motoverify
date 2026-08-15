<?php
require_once '../config/database.php';

$tables = ['repuestos', 'marcas', 'catalogos', 'repuesto_compatibilidad', 'ubicaciones'];

foreach ($tables as $table) {
    echo "=== $table ===\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "{$row['Field']} - {$row['Type']}\n";
        }
    } else {
        echo "Error or doesn't exist.\n";
    }
    echo "\n";
}
?>
