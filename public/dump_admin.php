<?php
require 'C:/laragon/www/Motoverify/config/database.php';
$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_array()) print_r($row[0] . "\n");
