<?php
require_once __DIR__ . '/../../config/database.php';

$sales = file_get_contents('update_sales.sql');
$usuarios = file_get_contents('update_usuarios_module.sql');

if ($conn->multi_query($sales)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Sales update OK.\n";
} else {
    echo "Sales error: " . $conn->error . "\n";
}

if ($conn->multi_query($usuarios)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Usuarios update OK.\n";
} else {
    echo "Usuarios error: " . $conn->error . "\n";
}
?>
