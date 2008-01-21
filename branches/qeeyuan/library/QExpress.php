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
 * @package Core
 * @version $Id$
 */


// {{{ includes
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Q.php';
// }}}

// {{{ init
/**
 * 初始化 QeePHP 框架
 */
define('EXPRESS_MODE', true);

if (!defined('DEPLOY_MODE') || DEPLOY_MODE != true) {
    Q::setIni(Q::loadFile('DEBUG_MODE_CONFIG.php', false, QEE_DIR . DS . '_config'));
    if (!defined('DEPLOY_MODE')) { define('DEPLOY_MODE', false); }
} else {
    Q::setIni(Q::load_file('DEPLOY_MODE_CONFIG.php', false, QEE_DIR . DS . '_config'));
    if (!defined('DEPLOY_MODE')) { define('DEPLOY_MODE', true); }
}

//if (!DEPLOY_MODE) {
    error_reporting(E_ALL | E_STRICT);
//} else {
//    error_reporting(0);
//}

set_exception_handler(array('QExpress', 'exceptionHandler'));
// }}}

/**
 * QExpress 类提供了一系列便利方法，以及一些预定义的辅助方法
 *
 * @package Core
 * @author 起源科技 (www.qeeyuan.com)
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

        // 载入调度器并转发请求到控制器
        $dispatcherClass = Q::getIni('dispatcher');
        $dispatcher = new $dispatcherClass($_GET);
        Q::reg($dispatcher, 'current_dispatcher');
        Q::reg($dispatcher, $dispatcherClass);
        return $dispatcher->dispatching();
    }

    /**
     * 准备运行环境
     */
    static function init()
    {
        static $firstTime = true;

        // 避免重复调用 Q::getInit()
        if (!$firstTime) { return; }
        $firstTime = false;

        date_default_timezone_set(Q::getIni('default_timezone'));

        /**
         * 载入日志服务提供程序
         */
        if (Q::getIni('log_enabled') && Q::getIni('log_provider')) {
            Q::loadClass(Q::getIni('log_provider'));
        }
        if (!function_exists('log_message')) {
            // 如果没有指定日志服务提供程序，就定义一个空的 log_message() 函数
            function log_message() {};
        }

        /**
         * 如果没有指定缓存目录，则使用默认的缓存目录
         */
        $cacheDir = Q::getIni('internal_cache_dir');
        if (empty($cacheDir)) {
            Q::setIni('internal_cache_dir', QEE_DIR . DS . '_cache');
        }

        // 过滤 magic_quotes
        if (get_magic_quotes_gpc()) {
            $in = array(& $_GET, & $_POST, & $_COOKIE, & $_REQUEST);
            while (list($k,$v) = each($in)) {
                foreach ($v as $key => $val) {
                    if (!is_array($val)) {
                        $in[$k][$key] = stripslashes($val);
                        continue;
                    }
                    $in[] =& $in[$k][$key];
                }
            }
            unset($in);
        }
        set_magic_quotes_runtime(0);

        if (Q::getIni('session_provider')) {
            Q::getSingleton(Q::getIni('session_provider'));
        }
        if (Q::getIni('auto_session')) {
            session_start();
        }

        // 定义 I18N 相关的常量
        define('RESPONSE_CHARSET', Q::getIni('response_charset'));

        // 检查是否启用多语言支持
        if (Q::getIni('multi_language_support')) {
            Q::loadClass(Q::getIni('multi_language_support_provider'));
        }
    }

    /**
     * 载入 YAML 文件，返回分析结果
     *
     * loadYAML() 会自动使用缓存，只有当 YAML 文件被改变后，缓存才会更新。
     *
     * 关于 YAML 的详细信息,请参考 www.yaml.org 。
     *
     * 用法：
     * <code>
     * $data = QExpress::loadYAML('myData.yaml');
     * </code>
     *
     * 注意：为了安全起见，不要将 yaml 文件置于浏览器能够访问的目录中。
     * 或者将 YAML 文件的扩展名设置为 .yaml.php，并且在每一个 YAML 文件开头添加“exit()”。
     * 例如：
     * <code>
     * # <?php exit(); ?>
     *
     * invoice: 34843
     * date   : 2001-01-23
     * bill-to: &id001
     * ......
     * </code>
     *
     * 这样可以确保即便浏览器直接访问该 .yaml.php 文件，也无法看到内容。
     *
     * 当 $cache 为 true 时，将使用默认设置对载入的 YAML 内容进行缓存。
     * 如果希望自行指定缓存策略以及要使用的缓存服务，可以将 $cache 参数设置为
     * $cache = array($policy, $backend)。
     *
     * 当 $cache 为 false 时，将不会对载入的 YAML 内容进行缓存。
     *
     * @param string $filename
     * @param array $replace 对于 YAML 内容要进行自动替换的字符串对
     * @param boolean $cache 缓存设置
     *
     * @return array
     */
    static function loadYAML($filename, $replace = null, $cache = true)
    {
        static $callback;

        if (is_array($cache)) {
            if (isset($cache[0])) {
                $policy = $cache[0];
            } else {
                $policy = Q::getIni('default_yaml_cache_policy');
            }
            if (isset($cache[1])) {
                $backend = $cache[1];
            } else {
                $backend = null;
            }
            $cacheEnabled = true;
        } elseif ($cache) {
            $cacheEnabled = true;
            $policy = Q::getIni('default_yaml_cache_policy');
            $backend = null;
        } else {
            $cacheEnabled = false;
        }

        if ($cacheEnabled) {
            $yaml = Q::getCache('YAML-' . $filename, $policy);
            if ($yaml) { return $yaml; }
        }

        if (!Q::isReadable($filename)) {
            // LC_MSG: File "%s" not found.
            throw new QException(__('File "%s" not found.', $filename));
        }

        Q::loadFile(QEE_DIR . DS . '_vendor' . DS . 'spyc.php', true);
        $yaml = Spyc::YAMLLoad($filename);

        if (is_null($callback)) {
            $callback = create_function('& $v, $key, $replace', 'foreach ($replace as $search => $rep) { $v = str_replace($search, $rep, $v); }; return $v;');
        }
        array_walk_recursive($yaml, $callback, $replace);

        if ($cacheEnabled) {
            Q::setCache('YAML-' . $filename, $yaml, $policy, $backend);
        }
        return $yaml;
    }

    /**
     * 默认的 on_access_denied 事件处理函数
     */
    static function onAccessDenied($controllerName, $actionName)
    {
        throw new QException("Access denied for \"{$controllerName}::{$actionName}\" action.");
    }

    /**
     * 默认的 on_action_not_found 事件处理函数
     */
    static function onActionNotFound($controllerName, $actionName)
    {
        throw new QException("Request to action {$controllerName}::{$actionName} not found");
    }

    /**
     * 默认的异常处理
     */
    static function exceptionHandler($ex)
    {
        QException::dump($ex);
    }
}

