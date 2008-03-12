<?php

global $g_boot_time;
$g_boot_time = microtime(true);

require dirname(__FILE__) . '/../config/boot.php';
require ROOT_DIR . '/app/app.php';

$app = App::instanc();
$app->run();
