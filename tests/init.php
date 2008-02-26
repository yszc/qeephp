<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 单元测试公用初始化文件
 *
 * @package test
 * @version $Id$
 */

if (defined('TEST_INIT')) { return; }
define('TEST_INIT', true);

date_default_timezone_set('Asia/ShangHai');

require dirname(__FILE__) . '/../library/qexpress.php';

Q::setIni('internal_cache_dir', dirname(__FILE__) . '/../tmp');

define('FIXTURE_DIR', dirname(__FILE__) . DS . 'fixture');

Q::import(FIXTURE_DIR);

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

