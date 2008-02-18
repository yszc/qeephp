<?php

phpinfo();

date_default_timezone_set('Asia/ShangHai');

require dirname(__FILE__) . '/../library/qexpress.php';

Q::setIni('internal_cache_dir', dirname(__FILE__) . '/../tmp');

$dsn = array(
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'login'     => 'root',
    'password'  => '',
    'database'  => 'test',
    'prefix'    => 'q_',
);
Q::setIni('dsn', $dsn);

Q::import(dirname(__FILE__));
