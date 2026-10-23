<?php
$c = new mysqli('127.0.0.1', 'root', '', 'consulta_repuestos');
if ($c->connect_error) die('DB Error');
$res = $c->query('SHOW TABLES');
while ($row = $res->fetch_row()) {
    $table = $row[0];
    echo "\n--- Table: $table ---\n";
    $desc = $c->query("DESCRIBE $table");
    while ($col = $desc->fetch_assoc()) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }
}
?>
