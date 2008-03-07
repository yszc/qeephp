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
 * 应用程序的启动文件
 */

/**
 * 将应用程序发布到生产服务器时，请将 RUN_MODE 修改为“deploy”
 *
 * 可用的 RUN_MODE 值如下：
 * deploy   - 部署模式
 * test     - 测试模式
 * devel    - 开发模式
 */
define('RUN_MODE', 'devel');

/**
 * 载入 QeePHP 框架
 *
 * 如果改动了 QeePHP 框架文件所在位置，需要修改下面的常量定义。
 */
define('QEEPHP_INST_DIR', '%QEEPHP_INST_DIR%');

/**
 * 定义缓存配置文件要使用的缓存服务
 *
 * 默认使用 QCache_File 来缓存配置文件。
 */
define('CONFIG_CACHE_BACKEND', 'QCache_File');

/**
 * 载入 QeePHP 框架
 *
 * 默认使用 Express 模式，因此载入了 qexpress.php 文件。
 * 如果不使用 Express 模式，修改下列代码，改为载入 q.php 文件。
 */
require QEEPHP_INST_DIR . '/library/qexpress.php';

// 定义应用程序根目录
define('ROOT_DIR', dirname(dirname(__FILE__)));

// 导入应用程序目录，以便 Q::loadClass() 能够加载类定义文件
Q::import(ROOT_DIR . DS . 'app');

/**
 * load_boot_config() 函数用于载入应用程序的配置文件
 *
 * @param boolean $reload
 *
 * @return array
 */
function load_boot_config($reload = false)
{
    Q::setIni('internal_cache_dir', ROOT_DIR . DS . 'tmp' . DS . 'internal');

    switch (RUN_MODE) {
    case 'deploy':
        // 在部署模式下，配置文件的缓存每 24 小时更新一次
        $life_time = 86400;
        break;
    case 'test':
        // 测试模式下，配置文件每 5 分钟更新一次
        $life_time = 300;
        break;
    case 'devel':
        // 开发模式每次访问都更新缓存，确保修改配置文件后能立即生效
        $life_time = 0;
    }
    $cacheid = 'app.config.' . RUN_MODE;

    $policy = array('life_time' => $life_time, 'serialize' => true);
    if (!$reload) {
        // 尝试从缓存载入配置
        $config = Q::getCache($cacheid, $policy, CONFIG_CACHE_BACKEND);
        if (is_array($config)) { return $config; }
    }

    // 载入配置文件，并替换配置文件中的宏
    $replace = array('%ROOT_DIR%' => ROOT_DIR);
    $files = array(
        ROOT_DIR . '/config/environment.yaml.php'                   => 'global',
        ROOT_DIR . '/config/database.yaml.php'                      => 'dsn_pool',
        ROOT_DIR . '/config/routes.yaml.php'                        => 'routes',
        ROOT_DIR . '/config/acl.yaml.php'                           => 'global_act',
        ROOT_DIR . '/config/environments/' . RUN_MODE . '.yaml.php' => 'global',
    );

    $config = array();
    foreach ($files as $filename => $namespace) {
        if (!file_exists($filename)) { continue; }
        $contents = Q::loadYAML($filename, $replace, false);
        if ($namespace == 'global') {
            $config = array_merge_recursive($config, $contents);
        } else {
            if (!isset($config[$namespace])) {
                $config[$namespace] = array();
            }
            $config[$namespace] = array_merge_recursive($config[$namespace], $contents);
        }
    }
    $config['dsn'] = $config['dsn_pool'][RUN_MODE];

    // 写入缓存
    Q::setCache($cacheid, $config, $policy, CONFIG_CACHE_BACKEND);
    return $config;
}

// 载入配置
Q::setIni(load_boot_config());
