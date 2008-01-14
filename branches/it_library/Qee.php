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


/**
 * 载入公共函数库
 */
//require_once 'FLEA/Functions.php';

//include_once 'Qee/Container.php';

/**
 * 定义一些有用的常量
 */

// 定义 QeePHP 版本号常量和 QeePHP 所在路径
define('QEE_VERSION', '2.0.1 alpha');

// 简写的 DIRECTORY_SEPARATOR
define('DS', DIRECTORY_SEPARATOR);

// 标准 URL 模式
define('URL_STANDARD',  1);

// PATHINFO 模式
define('URL_PATHINFO',  2);

// URL 重写模式
define('URL_REWRITE',   3);

// URL 路由模式， 待实现
define('URL_ROUTER',    4);


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
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    static function getAppInf($key, $default = null)
    {
		if(is_null($key)) return self::$APP_INF;
		if(isset(self::$APP_INF[$key])) return self::$APP_INF[$key];
		$arr = explode(".", $key);
		//用引用方式查找关联数组
		$pt  = &self::$APP_INF;
		while($arr)
		{
			if(!is_array($pt)) return $default;
			$key = array_shift($arr);
			$pt = &$pt[$key];
		}
		if (null === $pt) return $default;
		return $pt;
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

        if ( !(class_exists($className, false) || interface_exists($className, false)) ) {
            //require_once 'Qee/Exception/ExpectedClass.php';
            throw new Qee_Exception_ExpectedClass($className, $filename);
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
	 * Container::register(new MyObject(), 'my_obejct');
	 * .....
	 * // 稍后取出对象
	 * $obj = Qee::registry('my_object');
	 * </code>
	 *
	 * Container 提供一个对象注册表，开发者可以将一个对象以特定名称注册到其中。
	 *
	 * 当没有提供 $name 参数时，则以对象的类名称作为注册名。
	 *
	 * 当 $persistent 参数为 true 时，对象将被放入持久存储区。在下一次执行脚本时，
	 * 可以通过 Container::registry() 取出放入持久存储区的对象，无需重新构造对象。
	 * 利用这个特性，开发者可以将一些需要大量构造时间的对象放入持久存储区，
	 * 从而避免每一次执行脚本都去做对象构造操作。
	 *
	 * 使用哪一种持久化存储区来保存对象，由应用程序设置 objectPersistentProvier 决定。
	 * 该设置指定一个提供持久化服务的对象名。
	 *
	 * example:
	 * <code>
	 * if (!Container::isRegister('ApplicationObject')) {
	 * 		Container::loadClass('Application');
	 * 		Container::register(new Application(), 'ApplicationObject', true);
	 * }
	 * $app = Container::registry('ApplicationObject');
	 * </code>
	 *
	 * @param object $obj 要注册的对象
	 * @param string $name 注册的名字
	 * @param boolean $persistent 是否将对象放入持久化存储区
	 */
    static function register($obj, $name = null, $persistent = false)
    {
        if (is_null($name)) {
            $name = get_class($obj);
        }
        if (!isset(self::$OBJECTS[$name])) {
            self::$OBJECTS[$name] = $obj;
			return self::$OBJECTS[$name];
        }

        //require_once 'Qee/Exception/DuplicateEntry.php';
        throw new Exception_DuplicateEntry('Qee_Container::register($name)', $name);
    }

	/**
	 * 取得指定名字的对象实例，如果指定名字的对象不存在则抛出异常
	 *
	 * 使用示例参考 Container::register()。
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

        //require_once 'Qee/Exception/NonExistentEntry.php';
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
     * QeePHP 应用程序 MVC 模式入口
     */
    static function runMVC()
    {
        self::init();
		$dispatcherName = self::getAppInf('dispatcher');
		$dispatcher = self::getSingleton($dispatcherName);
		//var_dump($dispatcher);
		
		return $dispatcher->dispatch();
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
		 * 自动加载对象
		 */
		spl_autoload_register(array('Qee', 'loadClass'));
		/**
		 * 设置异常处理例程
		 */
		set_exception_handler(array('Qee', 'printError'));
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
        //if (self::getAppInf('urlMode') != URL_STANDARD) {
        //    require 'Qee/Filter/Uri.php';
        //}

        // 处理 requestFilters
        foreach ((array)self::getAppInf('requestFilters') as $file) {
            self::loadFile($file);
        }

        // 处理 autoLoad
        foreach ((array)self::getAppInf('autoLoad') as $file) {
            self::loadFile($file);
        }

        // 载入指定的 session 服务提供程序
        //if (self::getAppInf('sessionProvider')) {
        //    self::getSingleton(self::getAppInf('sessionProvider'));
        //}
        // 自动起用 session 会话
        //if (self::getAppInf('autoSessionStart')) {
        //    session_start();
        //}

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
	function printError(Exception $ex)
	{
	    if (!self::getAppInf('displayErrors')) { exit; }
	    if (self::getAppInf('friendlyErrorsMessage')) {
	        $language = self::getAppInf('defaultLanguage');
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

}




// 加入搜索路径
Qee::import(dirname(__FILE__));
Qee::import(dirname(__FILE__).'/Qee');


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
 * $url = url('Login', 'checkUser', array('name' => 'test'));
 * // $url 现在为 ?controller=Login&action=checkUser&name=test
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

	
	$dispatcherName = Qee::getAppInf('dispatcher');
	$dispatcher = Qee::getSingleton($dispatcherName);
	return $dispatcher->url($controllerName, $actionName, $params, $anchor);
}
