<?php

ini_set('include_path', '.;d:\www\workspace\qeephp\library');
require_once 'FLEA/Db/Driver/Mysqli.php';

$dsn = array(
    'login' => 'root',
    'database' => 'test',
);

$dbo = new FLEA_Db_Driver_Mysqli($dsn);
$dbo->connect();

$sql = "SELECT * FROM roles WHERE `role_id` > ?";
$rowset = $dbo->getAll($sql, array(0), 'name');
// var_dump($rowset);

$sql = "SELECT * FROM roles WHERE `role_id` > ?";
$fieldValues = $reference = null;
$rowset = $dbo->getAllWithFieldRefs($sql, 'name', $fieldValues, $reference, array(0));
print_r($rowset);
print_r($reference);

$sql = "SELECT * FROM roles WHERE `role_id` = ?";
print_r($dbo->getRow($sql, array(1)));

print_r($dbo->metaColumns('permissions'));
print_r(get_included_files());
