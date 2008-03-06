<?php

global $g_boot_time;
$g_boot_time = microtime(true);

require dirname(__FILE__) . '/../app/boot.php';
QExpress::runMVC();
