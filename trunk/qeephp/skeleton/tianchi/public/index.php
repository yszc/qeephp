<?php

global $g_boot_time;
$g_boot_time = microtime(true);

require dirname(__FILE__) . '/../config/boot.php';
require ROOT_DIR . '/app/myapp.php';

MyApp::instanc()->run();
