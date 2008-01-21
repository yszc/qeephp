<?php

require dirname(__FILE__) . '/../library/qexpress.php';

Q::setIni('internal_cache_dir', dirname(__FILE__) . '/../tmp');

$dsn = array(
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'login'     => 'test',
    'database'  => 'test',
    'prefix'    => 'rx_',
);
Q::setIni('dsn', $dsn);

Q::import(dirname(__FILE__));

echo str_repeat('=', 60);
echo "\n\n";

