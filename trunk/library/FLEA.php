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
 * 定义 FLEA 类和基础函数，并初始化 QeePHP 运行环境
 *
 * 对于大部分 QeePHP 的组件，都要求预先初始化 QeePHP 环境。
 * 在应用程序中只需要通过 require('FLEA.php') 载入该文件，
 * 即可完成 QeePHP 运行环境的初始化工作。
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id: FLEA.php 887 2007-07-05 10:05:15Z dualface $
 */

/**
 * 记录文件载入的时间
 */
global $___fleaphp_loaded_time;
$___fleaphp_loaded_time = microtime();

/**
 * 载入公共函数库
 */
require_once 'FLEA/Functions.php';

/**
 * 定义一些有用的常量
 */

// 定义 QeePHP 版本号常量和 QeePHP 所在路径
define('FLEA_VERSION', '1.0.70.885');

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
 * @author 廖宇雷 dualface@gmail.com
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
     * 对象注册表
     *
     * @var array
     */
    private static $OBJECTS = array();

    /**
     * 类搜索路径
     *
     * @var array
     */
    private static $CLASS_PATH = array();

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
     * 导入文件搜索路径
     *
     * @param string $dir
     */
    static function import($dir)
    {
        if (!array_search($dir, self::$CLASS_PATH[$dir])) {
            self::$CLASS_PATH[] = $dir;
        }
    }

    /**
     * 载入指定的文件
     *
     * @param string $filename
     * @param boolean $once 指示同一个文件是否只载入一次
     * @param array $dirs
     *
     * @return mixed
     */
    static function loadFile($filename, $once = false, $dirs = null)
    {
        if (preg_match('/[^a-z0-9\-_.]/i', $filename)) {
            throw new FLEA_Exception(FLEA_Exception::t('Security check: Illegal character in filename: %s.', $filename));
        }

        if (is_null($dirs)) {
            $dirs = self::$CLASS_PATH;
        } else {
            if (is_string($dirs)) {
                $dirs = explode(PATH_SEPARATOR, $dirs);
            }
            $dirs = array_merge($dirs, self::$CLASS_PATH);
        }

        foreach ($dirs as $dir) {
            $path = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $filename;
            if (@is_readable($path)) {
                return $once ? include_once $path : include $path;
            }
        }

        // include 会在 include_path 中寻找文件
        return $once ? include_once $filename : include $filename;
    }

    /**
     * 载入指定类的定义文件
     *
     * @param string $className
     */
    static function loadClass($className)
    {
        if (class_exists($className, false) || interface_exists($className, false)) {
            return;
        }

        $filename = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        self::loadFile($filename, true, '.' . PATH_SEPARATOR . dirname($filename));

        if (!class_exists($className, false)) {
            require_once 'FLEA/Exception/ExpectedClass.php';
            throw new FLEA_Exception_ExpectedClass($className, $filename);
        }
    }

    /**
     * 返回指定对象的唯一实例
     *
     * @param string $className
     *
     * @return object
     */
    static function getSingleton($className)
    {
        if (isset(self::$OBJECTS[$className])) {
            return self::registry($className);
        }
        self::loadClass($className);
        return self::register(new $className(), $className);
    }

    /**
     * 注册一个对象实例
     *
     * @param object $obj
     * @param string $name
     *
     * @return object
     */
    static function register($obj, $name = null)
    {
        if (is_null($name)) {
            $name = get_class($obj);
        }
        if (isset(self::$OBJECTS[$name])) {
            return self::$OBJECTS[$name];
        }
        self::$OBJECTS[$name] = $obj;
        return $obj;
    }

    /**
     * 从对象实例容其中取出指定名字的对象实例
     *
     * @param string $name
     *
     * @return object
     */
    static function registry($name)
    {
        return isset(self::$OBJECTS[$name]) ? self::$OBJECTS[$name] : null;
    }

    /**
     * 检查指定名字的对象是否已经注册
     *
     * @param string $name
     *
     * @return boolean
     */
    static function isRegistered($name)
    {
        return isset(self::$OBJECTS[$name]);
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
     * 返回数据库访问对象实例
     *
     * @param array|string|int $dsn
     *
     * @return FLEA_Db_Driver_Prototype
     */
    static function getDBO($dsn = 0)
    {
        if ($dsn == 0) {
            $dbo = self::registry('_DBO_DEFAULT');
            if ($dbo) { return $dbo; }
            $dsn = self::getAppInf('dbDSN');
            $default = true;
        } else {
            $default = false;
        }
        $dsn = self::parseDSN($dsn);

        if (!is_array($dsn) || !isset($dsn['driver'])) {
            require_once 'FLEA/Db/Exception/InvalidDSN.php';
            throw new FLEA_Db_Exception_InvalidDSN($dsn);
        }

        $dsnid = '_DBO_' . $dsn['id'];
        if (self::isRegistered($dsnid)) {
            return self::registry($dsnid);
        }

        $driver = ucfirst(strtolower($dsn['driver']));
        $className = 'FLEA_Db_Driver_' . $driver;
        if ($driver == 'Mysql' || $driver == 'Mysqlt') {
            require_once 'FLEA/Db/Driver/Mysql.php';
        } else {
            self::loadClass($className);
        }
        $dbo = new $className($dsn);
        /* @var $dbo FLEA_Db_Driver_Prototype */
        $dbo->connect();

        self::register($dbo, $dsnid);
        if ($default) {
            self::register($dbo, '_DBO_DEFAULT');
        }
        return $dbo;
    }

    /**
     * 分析 DSN 字符串或数组，返回包含 DSN 连接信息的数组，失败返回 false
     *
     * @param string|array $dsn
     *
     * @return array
     */
    static function parseDSN($dsn)
    {
        if (is_array($dsn)) {
            $dsn['host'] = isset($dsn['host']) ? $dsn['host'] : '';
            $dsn['port'] = isset($dsn['port']) ? $dsn['port'] : '';
            $dsn['login'] = isset($dsn['login']) ? $dsn['login'] : '';
            $dsn['password'] = isset($dsn['password']) ? $dsn['password'] : '';
            $dsn['database'] = isset($dsn['database']) ? $dsn['database'] : '';
            $dsn['options'] = isset($dsn['options']) ? serialize($dsn['options']) : '';
            $dsn['dbTablePrefix'] = isset($dsn['dbTablePrefix']) ? $dsn['dbTablePrefix'] : self::getAppInf('dbTablePrefix');
        } else {
            $dsn = str_replace('@/', '@localhost/', $dsn);
            $parse = parse_url($dsn);
            if (empty($parse['scheme'])) { return false; }

            $dsn = array();
            $dsn['host']     = isset($parse['host']) ? $parse['host'] : 'localhost';
            $dsn['port']     = isset($parse['port']) ? $parse['port'] : '';
            $dsn['login']    = isset($parse['user']) ? $parse['user'] : '';
            $dsn['password'] = isset($parse['pass']) ? $parse['pass'] : '';
            $dsn['driver']   = isset($parse['scheme']) ? strtolower($parse['scheme']) : '';
            $dsn['database'] = isset($parse['path']) ? substr($parse['path'], 1) : '';
            $dsn['options']  = isset($parse['query']) ? $parse['query'] : '';
            $dsn['dbTablePrefix'] = self::getAppInf('dbTablePrefix');
        }
        $dsnid = "{$dsn['driver']}://{$dsn['login']}:{$dsn['password']}@{$dsn['host']}_{$dsn['dbTablePrefix']}/{$dsn['database']}/{$dsn['options']}";
        $dsn['id'] = $dsnid;
        return $dsn;
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
