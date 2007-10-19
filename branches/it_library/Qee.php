<?PHP
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QEE 类和基础函数，并初始化 QeePHP 运行环境
 *
 * 对于大部分 QeePHP 的组件，都要求预先初始化 QeePHP 环境。
 * 在应用程序中只需要通过 require('QEE.php') 载入该文件，
 * 即可完成 QeePHP 运行环境的初始化工作。
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */


//include_once 'Qee/Container.php';

/**
 * 定义一些有用的常量
 */

// 定义 QeePHP 版本号常量和 QeePHP 所在路径
define('QEE_VERSION', '1.8');

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
    //error_reporting(E_ALL & E_STRICT);
} else {
    //error_reporting(0);
}

// 设置异常处理例程
set_exception_handler('__QEE_EXCEPTION_HANDLER');

/**
 * Qee 类提供了 QeePHP 框架的基本服务
 *
 * 该类的所有方法都是静态方法。
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
abstract class Qee
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
     * 载入指定类的定义文件，如果载入失败抛出异常
     *
     * example:
     * <code>
     * Qee_Container::loadClass('Qee_Db_TableDataGateway');
     * </code>
     *
     * 在查找类定义文件时，类名称中的“_”会被替换为目录分隔符，
     * 从而确定类名称和类定义文件的映射关系（例如：Qee_Db_TableDataGateway 的定义文件为
     * Qee/Db/TableDataGateway.php）。
     *
     * loadClass() 会首先尝试从开发者指定的搜索路径中查找类的定义文件。
     * 搜索路径可以用 Qee_Container::import() 添加，或者通过 $dirs 参数提供。
     *
     * 如果没有指定 $dirs 参数和搜索路径，那么 loadClass() 会通过 PHP 的
     * include_path 设置来查找文件。
     *
     * @param string $className 要载入的类名字
     * @param string|array $dirs 可选的搜索路径
     */
    static function loadClass($className, $dirs = null)
    {
        if (class_exists($className, false) || interface_exists($className, false)) {
            return;
        }

        if (null === $dirs) {
            $dirs = self::$CLASS_PATH;
        } else {
            if (!is_array($dirs)) {
                $dirs = explode(PATH_SEPARATOR, $dirs);
            }
            $dirs = array_merge($dirs, self::$CLASS_PATH);
        }

        $filename = str_replace('_', DIRECTORY_SEPARATOR, $className);
        if ($filename != $className) {
            $dirname = dirname($filename);
            foreach ($dirs as $offset => $dir) {
                if ($dir == '.') {
                    $dirs[$offset] = $dirname;
                } else {
                    $dir = rtrim($dir, '\\/');
                    $dirs[$offset] = $dir . DIRECTORY_SEPARATOR . $dirname;
                }
            }
            $filename = basename($filename) . '.php';
        } else {
            $filename .= '.php';
        }

        self::loadFile($filename, true, $dirs);

        if (!class_exists($classname, false)) {
            require_once 'Qee/Exception/ExpectedClass.php';
            throw new Qee_Exception_ExpectedClass($classname, $filename);
        }
    }

    /**
     * 返回指定对象的唯一实例，如果指定类无法载入或不存在，则抛出异常
     *
     * example:
     * <code>
     * $service = Qee_Container::getSingleton('Service_Products');
     * </code>
     *
     * @param string $classname 要获取的对象的类名字
     *
     * @return object
     */
    static function getSingleton($classname)
    {
        if (isset(self::$OBJECTS[$classname])) {
            return self::registry($classname);
        }
        self::loadClass($classname);
        return self::register(new $classname(), $classname);
    }

    /**
     * 以特定名字注册一个对象，以便稍后用 registry() 方法取回该对象。
     * 如果指定名字已经被使用，则抛出异常
     *
     * example:
     * <code>
     * // 注册一个对象
     * Qee_Container::register(new MyObject(), 'my_obejct');
     * .....
     * // 稍后取出对象
     * $obj = Qee::registry('my_object');
     * </code>
     *
     * Qee_Container 提供一个对象注册表，开发者可以将一个对象以特定名称注册到其中。
     *
     * 当没有提供 $name 参数时，则以对象的类名称作为注册名。
     *
     * 当 $persistent 参数为 true 时，对象将被放入持久存储区。在下一次执行脚本时，
     * 可以通过 Qee_Container::registry() 取出放入持久存储区的对象，无需重新构造对象。
     * 利用这个特性，开发者可以将一些需要大量构造时间的对象放入持久存储区，
     * 从而避免每一次执行脚本都去做对象构造操作。
     *
     * 使用哪一种持久化存储区来保存对象，由应用程序设置 objectPersistentProvier 决定。
     * 该设置指定一个提供持久化服务的对象名。
     *
     * example:
     * <code>
     * if (!Qee_Container::isRegister('ApplicationObject')) {
     * 		Qee_Container::loadClass('Application');
     * 		Qee_Container::register(new Application(), 'ApplicationObject', true);
     * }
     * $app = Qee_Container::registry('ApplicationObject');
     * </code>
     *
     * @param object $obj 要注册的对象
     * @param string $name 注册的名字
     * @param boolean $persistent 是否将对象放入持久化存储区
     */
    static function register($obj, $name = null, $persistent = false)
    {
        // TODO: 实现对 $persistent 参数的支持

        if (is_null($name)) {
            $name = get_class($obj);
        }
        if (!isset(self::$OBJECTS[$name])) {
            self::$OBJECTS[$name] = $obj;
            return;
        }

        require_once 'Qee/Exception/DuplicateEntry.php';
        throw new Exception_DuplicateEntry('Qee_Container::register($name)', $name);
    }

    /**
     * 取得指定名字的对象实例，如果指定名字的对象不存在则抛出异常
     *
     * 使用示例参考 Qee_Container::register()。
     *
     * @param string $name 注册名
     *
     * @return object
     */
    static function registry($name)
    {
        if (isset(self::$OBJECTS[$name])) {
            return self::$OBJECTS[$name];
        }

        require_once 'Qee/Exception/NonExistentEntry.php';
        throw new Exception_NonExistentEntry('Qee_Container::registry($name)', $name);
    }

    /**
     * 检查指定名字的对象是否已经注册
     *
     * 使用示例参考 Qee_Container::register()。
     *
     * @param string $name 注册名
     *
     * @return boolean
     */
    static function isRegistered($name)
    {
        return isset(self::$OBJECTS[$name]);
    }


    /**
     * 载入指定的文件
     *
     * $filename 参数必须是一个包含扩展名的完整文件名。
     * loadFile() 会首先从 $dirs 参数指定的路径中查找文件，
     * 找不到时再从 PHP 的 include_path 搜索路径中查找文件。
     *
     * $once 参数指示同一个文件是否只载入一次。
     *
     * example:
     * <code>
     * Qee_Container::loadFile('Table/Products.php');
     * </code>
     *
     * @param string $filename 要载入的文件名
     * @param boolean $once 同一个文件是否只载入一次
     * @param array $dirs 搜索目录
     *
     * @return mixed
     */
    static function loadFile($filename, $once = false, $dirs = null)
    {
        //if (preg_match('/[^a-z0-9\-_.]/i', $filename)) {
        //    throw new Qee_Exception(Qee_Exception::t('Security check: Illegal character in filename: %s.', $filename));
       // }

        if (is_null($dirs)) {
            $dirs = array();
        } else if (is_string($dirs)) {
            $dirs = explode(PATH_SEPARATOR, $dirs);
        }

        foreach ($dirs as $dir) {
            $path = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $filename;
            if (@self::isReadable($path)) {
                return $once ? include_once $path : include $path;
            }
        }

        // include 会在 include_path 中寻找文件
        if (@self::isReadable($filename)) {
            return $once ? include_once $filename : include $filename;
        }
    }

    /**
     * 导入文件搜索路径
     *
     * 在使用 loadClass() 时，会通过 import() 指定的搜索路径查找类定义文件。
     *
     * 当 loadClass('Service_Products') 时，由于类名称映射出来的类定义文件已经包含了目录名
     * （Service_Products 映射为 Service/Products.php）。
     * 所以只能将 Service 子目录所在目录添加到搜索路径，而不是直接将 Service 目录添加到搜索路径。
     *
     * example:
     * <code>
     * // 假设要载入的文件完整路径为 /www/app/Service/Products.php
     * Qee_Container::import('/www/app');
     * Qee::loadClass('Service_Products');
     * </code>
     *
     * @param string $dir
     */
    static function import($dir)
    {
        if (!array_search($dir, self::$CLASS_PATH)) {
            self::$CLASS_PATH[] = $dir;
        }
    }

    /**
     * 检查指定文件是否可读
     *
     * 这个方法会在 PHP 的搜索路径中查找文件。
     *
     * 该方法来自 Zend Framework 中的 Zend_Loader::isReadable()。
     *
     * @param string $filename
     *
     * @return boolean
     */
    public static function isReadable($filename)
    {
        if (@is_readable($filename)) { return true; }

        $path = get_include_path();
        $dirs = explode(PATH_SEPARATOR, $path);

        foreach ($dirs as $dir) {
            if ('.' == $dir) { continue; }
            if (@is_readable($dir . DIRECTORY_SEPARATOR . $filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 初始化 WebControls，返回 Qee_WebControls 对象实例
     *
     * @return Qee_WebControls
     */
    static function initWebControls()
    {
        return self::getSingleton(self::getAppInf('webControlsClassName'));
    }

    /**
     * 初始化 Ajax，返回 Qee_Ajax 对象实例
     *
     * @return Qee_Ajax
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
            Qee_Container::loadFile($setting, true);
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
            require 'Qee/Filter/Uri.php';
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



 function __autoload($class)
{
	Qee::loadCalss($class);
}

// 加入搜索路径
Qee::import(dirname(__FILE__));
Qee::import(dirname(__FILE__).'/Qee');

/**
 * 重定向浏览器到指定的 URL
 *
 * @param string $url 要重定向的 url
 * @param int $delay 等待多少秒以后跳转
 * @param bool $js 指示是否返回用于跳转的 JavaScript 代码
 * @param bool $jsWrapped 指示返回 JavaScript 代码时是否使用 <script> 标签进行包装
 * @param bool $return 指示是否返回生成的 JavaScript 代码
 */
function redirect($url, $delay = 0, $js = false, $jsWrapped = true, $return = false)
{
    $delay = (int)$delay;
    if (!$js) {
        if (headers_sent() || $delay > 0) {
            echo <<<EOT
<html>
<head>
<meta http-equiv="refresh" content="{$delay};URL={$url}" />
</head>
</html>
EOT;
            exit;
        } else {
            header("Location: {$url}");
            exit;
        }
    }

    $out = '';
    if ($jsWrapped) {
        $out .= '<script language="JavaScript" type="text/javascript">';
    }
    if ($delay > 0) {
        $out .= "window.setTimeout(function () { document.location='{$url}'; }, {$delay});";
    } else {
        $out .= "document.location='{$url}';";
    }
    if ($jsWrapped) {
        $out .= '</script>';
    }

    if ($return) {
        return $out;
    }

    echo $out;
    exit;
}

/**
 * 构造 url
 *
 * 构造 url 需要提供两个参数：控制器名称和控制器动作名。如果省略这两个参数或者其中一个。
 * 则 url() 函数会使用应用程序设置中的确定的默认控制名称和默认控制器动作名。
 *
 * url() 会根据应用程序设置 urlMode 生成不同的 URL 地址：
 * - URL_STANDARD - 标准模式（默认），例如 index.php?url=Login&action=Reject&id=1
 * - URL_PATHINFO - PATHINFO 模式，例如 index.php/Login/Reject/id/1
 * - URL_REWRITE  - URL 重写模式，例如 /Login/Reject/id/1
 *
 * 生成的 url 地址，还要受下列应用程序设置的影响：
 *   - controllerAccessor
 *   - defaultController
 *   - actionAccessor
 *   - defaultAction
 *   - urlMode
 *   - urlLowerChar
 *
 * 用法：
 * <code>
 * $url = url('Login', 'checkUser');
 * // $url 现在为 ?controller=Login&action=checkUser
 *
 * $url = url('Login', 'checkUser', array('username' => 'dualface'));
 * // $url 现在为 ?controller=Login&action=checkUser&username=dualface
 *
 * $url = url('Article', 'View', array('id' => 1'), '#details');
 * // $url 现在为 ?controller=Article&action=View&id=1#details
 * </code>
 *
 * @param string $controllerName
 * @param string $actionName
 * @param array $params
 * @param string $anchor
 * @param array $options
 *
 * @return string
 */
function url($controllerName = null, $actionName = null, $params = null, $anchor = null, $options = null)
{
    static $baseurl = null, $currentBootstrap = null;

    // 确定当前的 URL 基础地址和入口文件名
    if ($baseurl == null) {
        $baseurl = detect_uri_base();
        $p = strrpos($baseurl, '/');
        $currentBootstrap = substr($baseurl, $p + 1);
        $baseurl = substr($baseurl, 0, $p);
    }

    // 确定生成 url 要使用的 bootstrap
    $options = (array)$options;
    if (isset($options['bootstrap'])) {
        $bootstrap = $options['bootstrap'];
    } else if ($currentBootstrap == '') {
        $bootstrap = Qee::getAppInf('urlBootstrap');
    } else {
        $bootstrap = $currentBootstrap;
    }

    // 确定控制器和动作的名字
    if ($bootstrap != $currentBootstrap && $currentBootstrap != '') {
        $controllerName = !empty($controllerName) ? $controllerName : null;
        $actionName = !empty($actionName) ? $actionName : null;
    } else {
        $controllerName = !empty($controllerName) ? $controllerName : Qee::getAppInf('defaultController');
        $actionName = !empty($actionName) ? $actionName : Qee::getAppInf('defaultAction');
    }
    $lowerChar = isset($options['lowerChar']) ? $options['lowerChar'] : Qee::getAppInf('urlLowerChar');
    if ($lowerChar) {
        $controllerName = strtolower($controllerName);
        $actionName = strtolower($actionName);
    }

    $url = '';
    $mode = isset($options['mode']) ? $options['mode'] : Qee::getAppInf('urlMode');

    // PATHINFO 和 REWRITE 模式
    if ($mode == URL_PATHINFO || $mode == URL_REWRITE) {
        $url = $baseurl;
        if ($mode == URL_PATHINFO) {
            $url .= '/' . $bootstrap;
        }
        if ($controllerName != '' && $actionName != '') {
            $pps = isset($options['parameterPairStyle']) ? $options['parameterPairStyle'] : Qee::getAppInf('urlParameterPairStyle');
            $url .= '/' . rawurlencode($controllerName) . '/' . rawurlencode($actionName);
            if (is_array($params) && !empty($params)) {
                $url .= '/' . encode_url_args($params, $mode, $pps);
            }
        }
        if ($anchor) { $url .= '#' . $anchor; }
        return $url;
    }

    // 标准模式
    $alwaysUseBootstrap = isset($options['alwaysUseBootstrap']) ? $options['alwaysUseBootstrap'] : Qee::getAppInf('urlAlwaysUseBootstrap');
    $url = $baseurl . '/';

    if ($alwaysUseBootstrap || $bootstrap != Qee::getAppInf('urlBootstrap')) {
        $url .= $bootstrap;
    }

    $parajoin = '?';
    if ($controllerName != '') {
        $url .= $parajoin . Qee::getAppInf('controllerAccessor'). '=' . rawurlencode($controllerName);
        $parajoin = '&';
    }
    if ($actionName != '') {
        $url .= $parajoin . Qee::getAppInf('actionAccessor') . '=' . rawurlencode($actionName);
        $parajoin = '&';
    }

    if (is_array($params) && !empty($params)) {
        $url .= $parajoin . encode_url_args($params, $mode);
    }
    if ($anchor) { $url .= '#' . $anchor; }

    return $url;
}

/**
 * 获得当前请求的 URL 地址
 *
 * 感谢 tsingson 提供该函数，用于修正 QeePHP 原有 url() 函数不能适应 CGI 模式的问题。
 *
 * @param boolean $queryMode 是否将 URL 查询参数附加在返回结果中
 *
 * @return string
 */
function detect_uri_base($queryMode = false)
{
    $aURL = array();

    // Try to get the request URL
    if (!empty($_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = str_replace(array('"',"'",'<','>'), array('%22','%27','%3C','%3E'), $_SERVER['REQUEST_URI']);
        $p = strpos($_SERVER['REQUEST_URI'], ':');
        if ($p > 0 && substr($_SERVER['REQUEST_URI'], $p + 1, 2) != '//') {
            $aURL = array('path' => $_SERVER['REQUEST_URI']);
        } else {
            $aURL = parse_url($_SERVER['REQUEST_URI']);
        }
        if (isset($aURL['path']) && isset($_SERVER['PATH_INFO'])) {
            $aURL['path'] = substr($aURL['path'], 0, - strlen($_SERVER['PATH_INFO']));
        }
    }

    // Fill in the empty values
    if (empty($aURL['scheme'])) {
        if (!empty($_SERVER['HTTP_SCHEME'])) {
            $aURL['scheme'] = $_SERVER['HTTP_SCHEME'];
        } else {
            $aURL['scheme'] = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https' : 'http';
        }
    }

    if (empty($aURL['host'])) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $p = strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ':');
            if ($p > 0) {
                $aURL['host'] = substr($_SERVER['HTTP_X_FORWARDED_HOST'], 0, $p);
                $aURL['port'] = substr($_SERVER['HTTP_X_FORWARDED_HOST'], $p + 1);
            } else {
                $aURL['host'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
            }
        } else if (!empty($_SERVER['HTTP_HOST'])) {
            $p = strpos($_SERVER['HTTP_HOST'], ':');
            if ($p > 0) {
                $aURL['host'] = substr($_SERVER['HTTP_HOST'], 0, $p);
                $aURL['port'] = substr($_SERVER['HTTP_HOST'], $p + 1);
            } else {
                $aURL['host'] = $_SERVER['HTTP_HOST'];
            }
        } else if (!empty($_SERVER['SERVER_NAME'])) {
            $aURL['host'] = $_SERVER['SERVER_NAME'];
        }
    }

    if (empty($aURL['port']) && !empty($_SERVER['SERVER_PORT'])) {
        $aURL['port'] = $_SERVER['SERVER_PORT'];
    }

    if (empty($aURL['path'])) {
        if (!empty($_SERVER['PATH_INFO'])) {
            $sPath = parse_url($_SERVER['PATH_INFO']);
        } else {
            $sPath = parse_url($_SERVER['PHP_SELF']);
        }
        $aURL['path'] = str_replace(array('"',"'",'<','>'), array('%22','%27','%3C','%3E'), $sPath['path']);
        unset($sPath);
    }

    // Build the URL: Start with scheme, user and pass
    $sURL = $aURL['scheme'].'://';
    if (!empty($aURL['user'])) {
        $sURL .= $aURL['user'];
        if (!empty($aURL['pass'])) {
            $sURL .= ':'.$aURL['pass'];
        }
        $sURL .= '@';
    }

    // Add the host
    $sURL .= $aURL['host'];

    // Add the port if needed
    if (!empty($aURL['port']) && (($aURL['scheme'] == 'http' && $aURL['port'] != 80) || ($aURL['scheme'] == 'https' && $aURL['port'] != 443))) {
        $sURL .= ':'.$aURL['port'];
    }

    $sURL .= $aURL['path'];

    // Add the path and the query string
    if ($queryMode && isset($aURL['query'])) {
        $sURL .= $aURL['query'];
    }

    unset($aURL);
    return $sURL;
}

/**
 * 将数组转换为可通过 url 传递的字符串连接
 *
 * 用法：
 * <code>
 * $string = encode_url_args(array('username' => 'dualface', 'mode' => 'md5'));
 * // $string 现在为 username=dualface&mode=md5
 * </code>
 *
 * @param array $args
 * @param enum $urlMode
 * @param string $parameterPairStyle
 *
 * @return string
 */
function encode_url_args($args, $urlMode = URL_STANDARD, $parameterPairStyle = null)
{
    $str = '';
    switch ($urlMode) {
    case URL_STANDARD:
        if (is_null($parameterPairStyle)) {
            $parameterPairStyle = '=';
        }
        $sc = '&';
        break;
    case URL_PATHINFO:
    case URL_REWRITE:
        if (is_null($parameterPairStyle)) {
            $parameterPairStyle = Qee::getAppInf('urlParameterPairStyle');
        }
        $sc = '/';
        break;
    }

    foreach ($args as $key => $value) {
        if (is_array($value)) {
            $append = encode_url_args($value, $urlMode);
        } else {
            $append = rawurlencode($key) . $parameterPairStyle . rawurlencode($value);
        }
        if (substr($str, -1) != $sc) {
            $str .= $sc;
        }
        $str .= $append;
    }
    return substr($str, 1);
}

/**
 * 转换 HTML 特殊字符，等同于 htmlspecialchars()
 *
 * @param string $text
 *
 * @return string
 */
function h($text)
{
    return htmlspecialchars($text);
}

/**
 * 转换 HTML 特殊字符以及空格和换行符
 *
 * 空格替换为 &nbsp; ，换行符替换为 <br />。
 *
 * @param string $text
 *
 * @return string
 */
function t($text)
{
    return nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($text)));
}

/**
 * 通过 JavaScript 脚本显示提示对话框，并关闭窗口或者重定向浏览器
 *
 * 用法：
 * <code>
 * js_alert('Dialog message', '', $url);
 * // 或者
 * js_alert('Dialog message', 'window.close();');
 * </code>
 *
 * @param string $message 要显示的消息
 * @param string $after_action 显示消息后要执行的动作
 * @param string $url 重定向位置
 */
function js_alert($message = '', $after_action = '', $url = '')
{
    $out = "<script language=\"javascript\" type=\"text/javascript\">\n";
    if (!empty($message)) {
        $out .= "alert(\"";
        $out .= str_replace("\\\\n", "\\n", t2js(addslashes($message)));
        $out .= "\");\n";
    }
    if (!empty($after_action)) {
        $out .= $after_action . "\n";
    }
    if (!empty($url)) {
        $out .= "document.location.href=\"";
        $out .= $url;
        $out .= "\";\n";
    }
    $out .= "</script>";
    echo $out;
    exit;
}

/**
 * 将任意字符串转换为 JavaScript 字符串（不包括首尾的"）
 *
 * @param string $content
 *
 * @return string
 */
function t2js($content)
{
    return str_replace(array("\r", "\n"), array('', '\n'), addslashes($content));
}

/**
 * 调试和错误处理相关的全局函数
 */

/**
 * QeePHP 默认的异常处理例程
 *
 * @package Core
 *
 * @param Exception $ex
 */
function __QEE_EXCEPTION_HANDLER(Exception $ex)
{
   // if (!Qee::getAppInf('displayErrors')) { exit; }
    if (Qee::getAppInf('friendlyErrorsMessage')) {
        $language = Qee::getAppInf('defaultLanguage');
        $language = preg_replace('/[^a-z0-9\-_]+/i', '', $language);

        $exclass = strtoupper(get_class($ex));
        $template = "Qee/_Errors/{$language}/{$exclass}.php";
        if (!file_exists($template)) {
            $template = "Qee/_Errors/{$language}/QEE_EXCEPTION.php";
            if (!file_exists($template)) {
                $template = "Qee/_Errors/default/QEE_EXCEPTION.php";
            }
        }
        include $template;
    } else {
        Qee_Exception::printEx($ex);
    }
    exit;
}

/**
 * 输出变量的内容，通常用于调试
 *
 * @package Core
 *
 * @param mixed $vars 要输出的变量
 * @param string $label
 * @param boolean $return
 */
function dump($vars, $label = '', $return = false)
{
    if (ini_get('html_errors')) {
        $content = "<pre>\n";
        if ($label != '') {
            $content .= "<strong>{$label} :</strong>\n";
        }
        $content .= htmlspecialchars(print_r($vars, true));
        $content .= "\n</pre>\n";
    } else {
        $content = $label . " :\n" . print_r($vars, true);
    }
    if ($return) { return $content; }
    echo $content;
    return null;
}

/**
 * 显示应用程序执行路径，通常用于调试
 *
 * @package Core
 *
 * @return string
 */
function dump_trace()
{
    $debug = debug_backtrace();
    $lines = '';
    $index = 0;
    for ($i = 0; $i < count($debug); $i++) {
        $file = $debug[$i];
        if ($file['file'] == '') { continue; }
        $line = "#{$index} {$file['file']}({$file['line']}): ";
        if (isset($file['class'])) {
            $line .= "{$file['class']}{$file['type']}";
        }
        $line .= "{$file['function']}(";
        if (isset($file['args']) && count($file['args'])) {
            foreach ($file['args'] as $arg) {
                $line .= gettype($arg) . ', ';
            }
            $line = substr($line, 0, -2);
        }
        $line .= ')';
        $lines .= $line . "\n";
        $index++;
    } // for
    $lines .= "#{$index} main\n";

    if (ini_get('html_errors')) {
        echo nl2br(str_replace(' ', '&nbsp;', $lines));
    } else {
        echo $lines;
    }
}