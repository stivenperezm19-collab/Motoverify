<?php
require_once __DIR__ . '/../../config/database.php';

$sales_fix = file_get_contents('update_sales_fix.sql');

if ($conn->multi_query($sales_fix)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Sales fix OK.\n";
} else {
    echo "Sales fix error: " . $conn->error . "\n";
}
?>
