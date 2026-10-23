<?php
require_once __DIR__ . '/../../config/database.php';
$res = $conn->query("SHOW COLUMNS FROM REPUESTO");
while($row = $res->fetch_assoc()){
    print_r($row);
}
?>
