<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 实现“Express”模式
 *
 * Express 模式是接近于 FleaPHP 的自动化模式。在这种模式下，框架自动完成多项工作，减少开发者的编码量。
 *
 * 载入 EXPRESS.php 文件时，会自动定义 FLEA 类，以及完成运行环境的初始化。
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Easy
 * @version $Id$
 */

/**
 * 载入公共函数库
 */
require_once 'FLEA/Functions.php';

/**
 * 定义一些有用的常量
 */

// 定义 QeePHP 版本号常量和 QeePHP 所在路径
define('FLEA_VERSION', '1.8');

// 定义指示 PHP4 或 PHP5 的常量
define('PHP5', true);
define('PHP4', false);

// 简写的 DIRECTORY_SEPARATOR
define('DS', DIRECTORY_SEPARATOR);

// 标准 URL 模式
define('URL_STANDARD',  1);

// PATHINFO 模式
define('URL_PATHINFO',  2);

// URL 重写模式
define('URL_REWRITE',   3);

/**#@+
 * 定义 RBAC 基本角色常量
 */
// RBAC_EVERYONE 表示任何用户（不管该用户是否具有角色信息）
define('RBAC_EVERYONE',     -1);

// RBAC_HAS_ROLE 表示具有任何角色的用户
define('RBAC_HAS_ROLE',     -2);

// RBAC_NO_ROLE 表示不具有任何角色的用户
define('RBAC_NO_ROLE',      -3);

// RBAC_NULL 表示该设置没有值
define('RBAC_NULL',         null);
/**#@-*/


/**
 * 初始化 QeePHP 框架
 */
if (DEBUG_MODE) {
    error_reporting(E_ALL & E_STRICT);
} else {
    error_reporting(0);
}

// 设置异常处理例程
set_exception_handler('__FLEA_EXCEPTION_HANDLER');

/**
 * FLEA 类提供了 QeePHP 框架的基本服务
 *
 * 该类的所有方法都是静态方法。
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
abstract class FLEA
{
    /**
     * 应用程序设置
     *
     * @var array
     */
    private static $APP_INF = array();

    /**
     * 载入应用程序设置
     *
     * @param string $configFilename 配置文件名
     */
    static function loadAppInf($configFilename)
    {
        $config = self::loadFile($configFilename);
        self::setAppInf($config);
    }

    /**
     * 取出指定名字的设置值
     *
     * @param string $option
     * @param mixed $default
     *
     * @return mixed
     */
    static function getAppInf($option, $default = null)
    {
        return isset(self::$APP_INF[$option]) ? self::$APP_INF[$option] : $default;
    }

    /**
     * 获得指定名字的设置值中的项目，要求该设置必须是数组
     *
     * @param string $option
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    static function getAppInfValue($option, $key, $default = null)
    {
        return isset(self::$APP_INF[$option][$key]) ? self::$APP_INF[$option][$key] : $default;
    }

    /**
     * 修改设置值
     *
     * @param string $option
     * @param mixed $data
     */
    static function setAppInf($option, $data = null)
    {
        if (is_array($option)) {
            self::$APP_INF = array_merge(self::$APP_INF, $option);
        } else {
            self::$APP_INF[$option] = $data;
        }
    }

    /**
     * 读取指定缓存的内容，如果缓存内容不存在或失效，则返回 false
     *
     * @param string $cacheId 缓存ID，不同的缓存内容应该使用不同的ID
     * @param int $time 缓存过期时间或缓存生存周期
     * @param boolean $timeIsLifetime 指示 $time 参数的作用
     * @param boolean $idAsFilename 指示是否用 $cacheId 作为文件名
     *
     * @return mixed 返回缓存的内容，缓存不存在或失效则返回 false
     */
    static function getCache($cacheId, $time = 900, $timeIsLifetime = true, $idAsFilename = false)
    {
        $cacheDir = self::getAppInf('internalCacheDir');
        if ($cacheDir == null) {
            require_once 'FLEA/Exception/CacheDisabled';
            throw new FLEA_Exception_CacheDisabled($cacheDir);
        }

        if ($idAsFilename) {
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheId . '.php';
        } else {
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . md5($cacheId) . '.php';
        }
        if (!file_exists($cacheFile)) { return false; }

        if ($timeIsLifetime && $time == -1) {
            return include $cacheFile;
        }

        $filetime = filemtime($cacheFile);
        if ($timeIsLifetime) {
            if (time() >= $filetime + $time) { return false; }
        } else {
            if ($time >= $filetime) { return false; }
        }
        return include $cacheFile;
    }

    /**
     * 将变量内容写入缓存
     *
     * @param string $cacheId
     * @param mixed $data
     * @param boolean $idAsFilename 指示是否用 $cacheId 作为文件名
     *
     * @return boolean
     */
    static function writeCache($cacheId, $data, $idAsFilename = false)
    {
        $cacheDir = self::getAppInf('internalCacheDir');
        if ($cacheDir == null) {
            require_once 'FLEA/Exception/CacheDisabled';
            throw new FLEA_Exception_CacheDisabled($cacheDir);
        }

        if ($idAsFilename) {
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheId . '.php';
        } else {
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . md5($cacheId) . '.php';
        }
        $contents = '<?php return ' . var_export($data, true) . ";\n";
        if (!file_put_contents($cacheFile, $contents)) {
            require_once 'FLEA/Exception/CacheDisabled';
            throw new FLEA_Exception_CacheDisabled($cacheDir);
        } else {
            return true;
        }
    }

    /**
     * 删除指定的缓存内容
     *
     * @param string $cacheId
     * @param boolean $idAsFilename 指示是否用 $cacheId 作为文件名
     *
     * @return boolean
     */
    static function purgeCache($cacheId, $idAsFilename = false)
    {
        $cacheDir = self::getAppInf('internalCacheDir');
        if ($cacheDir == null) {
            require_once 'FLEA/Exception/CacheDisabled';
            throw new FLEA_Exception_CacheDisabled($cacheDir);
        }

        if ($idAsFilename) {
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheId . '.php';
        } else {
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . md5($cacheId) . '.php';
        }

        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        return true;
    }

    /**
     * 初始化 WebControls，返回 FLEA_WebControls 对象实例
     *
     * @return FLEA_WebControls
     */
    static function initWebControls()
    {
        return self::getSingleton(self::getAppInf('webControlsClassName'));
    }

    /**
     * 初始化 Ajax，返回 FLEA_Ajax 对象实例
     *
     * @return FLEA_Ajax
     */
    static function initAjax()
    {
        return self::getSingleton(self::getAppInf('ajaxClassName'));
    }

    /**
     * 载入一个助手，返回助手对象的一个实例
     *
     * @param string $helperName
     */
    static function loadHelper($helperName)
    {
        $settingName = 'helper.' . strtolower($helperName);
        $setting = self::getAppInf($settingName);
        if ($setting) {
            self::loadFile($setting, true);
        }
    }

    /**
     * QeePHP 应用程序 MVC 模式入口
     */
    static function runMVC()
    {
        self::init();
        $dispatcher = self::getSingleton(self::getAppInf('dispatcher'));
        return $dispatcher->dispatching();
    }

    /**
     * 准备运行环境
     */
    static function init()
    {
        static $firstTime = true;

        // 避免重复调用 self::init()
        if (!$firstTime) { return; }
        $firstTime = false;

        /**
         * 载入日志服务提供程序
         */
        if (self::getAppInf('logEnabled') && self::getAppInf('logProvider')) {
            self::loadClass(self::getAppInf('logProvider'));
        }
        if (!function_exists('log_message')) {
            // 如果没有指定日志服务提供程序，就定义一个空的 log_message() 函数
            eval('function log_message() {}');
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

        // 根据 URL 模式设置，决定是否要载入 URL 分析过滤器
        if (self::getAppInf('urlMode') != URL_STANDARD) {
            require 'FLEA/Filter/Uri.php';
        }

        // 处理 requestFilters
        foreach ((array)self::getAppInf('requestFilters') as $file) {
            self::loadFile($file);
        }

        // 处理 autoLoad
        foreach ((array)self::getAppInf('autoLoad') as $file) {
            self::loadFile($file);
        }

        // 载入指定的 session 服务提供程序
        if (self::getAppInf('sessionProvider')) {
            self::getSingleton(self::getAppInf('sessionProvider'));
        }
        // 自动起用 session 会话
        if (self::getAppInf('autoSessionStart')) {
            session_start();
        }

        // 定义 I18N 相关的常量
        define('RESPONSE_CHARSET', self::getAppInf('responseCharset'));
        define('DATABASE_CHARSET', self::getAppInf('databaseCharset'));

        // 检查是否启用多语言支持
        if (self::getAppInf('multiLanguageSupport')) {
            self::loadClass(self::getAppInf('languageSupportProvider'));
        }
        if (!function_exists('_T')) {
            eval('function _T() {}');
        }

        // 自动输出内容头信息
        if (self::getAppInf('autoResponseHeader')) {
            header('Content-Type: text/html; charset=' . self::getAppInf('responseCharset'));
        }
    }
}
