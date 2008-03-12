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
 * 实现 Express 模式
 *
 * @package core
 * @version $Id$
 */

// {{{ includes
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'q.php';
// }}}

// {{{ init
/**
 * 初始化 QeePHP 框架
 */
define('Q_EXPRESS', true);

require Q_DIR . DS . 'qdebug.php';

if (!defined('Q_RUN_MODE')) {
    define('Q_RUN_MODE', 'debug');
}

Q::setIni(Q::loadFile('default_config.php', false, Q_DIR . DS . '_config'));
error_reporting(E_ALL | E_STRICT);
set_exception_handler(array('QExpress', 'exceptionHandler'));

// 允许 QeePHP 自动载入需要的类
Q::import(Q_DIR);
if (function_exists('spl_autoload_register')) {
    spl_autoload_register(array('Q', 'loadClass'));
} elseif (!function_exists('__autoload')) {
    function __autoload($class_name)
    {
        Q::loadClass($class_name);
    }
}
// }}}

/**
 * QExpress 类提供了一系列便利方法，以及一些预定义的辅助方法
 *
 * @package core
 */
class QExpress
{
    /**
     * QeePHP 应用程序 MVC 模式入口
     *
     * 如果应用程序需要使用 QeePHP 提供的 MVC 模式，应该调用 Q::runMVC() 启动应用程序。
     */
    static function runMVC()
    {
        self::init();

        // 如果是 pathinfo 和 rewrite 模式，则启用路由器
        if (Q::getIni('url_mode') != 'standard') {
            $router = Q::getSingleton('QRouter');
            /* @var $router QRouter */
            $router->import((array)Q::getIni('routes'));
            $router->parse();
        }

        // 载入调度器并转发请求到控制器
        $dispatcher_class = Q::getIni('dispatcher_class');
        $request_class = Q::getIni('requiest_class');
        $request = call_user_func(array($request_class, 'instance'));
        Q::register($request, 'current_request');
        $dispatcher = new $dispatcher_class($request);
        /* @var $dispatcher QDispatcher */
        Q::register($dispatcher, 'current_dispatcher');
        Q::register($dispatcher, $dispatcher_class);

        return $dispatcher->dispatching();
    }

    /**
     * 准备运行环境
     */
    static function init()
    {
        static $first_time = true;

        // 避免重复调用 Q::getInit()
        if (!$first_time) { return; }
        $first_time = false;

        date_default_timezone_set(Q::getIni('default_timezone'));

        /**
         * 载入日志服务提供程序
         */
        if (Q::getIni('log_enabled') && Q::getIni('log_provider')) {
            Q::loadClass(Q::getIni('log_provider'));
        }

        /**
         * 如果没有指定缓存目录，则使用默认的缓存目录
         */
        $cache_dir = Q::getIni('internal_cache_dir');
        if (empty($cache_dir)) {
            Q::setIni('internal_cache_dir', Q_DIR . DS . '_cache');
        }

        // 禁用 magic quotes runtime
        set_magic_quotes_runtime(0);

        if (Q::getIni('session_provider')) {
            Q::loadClass(Q::getIni('session_provider'));
        }
        if (Q::getIni('auto_session')) {
            session_start();
        }

        // 检查是否启用多语言支持
        if (Q::getIni('multi_language_support')) {
            Q::loadClass(Q::getIni('multi_language_support_provider'));
        }
    }

    /**
     * 默认的 on_access_denied 事件处理函数
     */
    static function onAccessDenied($controller_name, $action_name)
    {
        throw new QException("Access denied for \"{$controller_name}::{$action_name}\" action.");
    }

    /**
     * 默认的 on_action_not_found 事件处理函数
     */
    static function onActionNotFound($controller_name, $action_name)
    {
        throw new QException("Request to action {$controller_name}::{$action_name} not found");
    }

    /**
     * 默认的异常处理
     */
    static function exceptionHandler($ex)
    {
        QException::dump($ex);
    }
}
