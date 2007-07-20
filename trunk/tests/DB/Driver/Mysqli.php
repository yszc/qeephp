<?php

require_once '../Mysqli.php';

$dsn = array(
    'login' => 'root',
    'database' => 'test',
);

$dbo = new FLEA_Db_Driver_Mysqli($dsn);
$dbo->connect();
print_r($dbo);
print_r(get_included_files());
