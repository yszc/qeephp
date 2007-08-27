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
 * 定义 Qee_Container 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id$
 */

/**
 * Qee_Container 类提供了类的载入、对象注册等服务
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class Qee_Container
{
    /**
     * 对象注册表
     *
     * @var array
     */
    protected static $OBJECTS = array();

    /**
     * 类搜索路径
     *
     * @var array
     */
    protected static $CLASS_PATH = array();


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
        if (preg_match('/[^a-z0-9\-_.]/i', $filename)) {
            throw new Qee_Exception(Qee_Exception::t('Security check: Illegal character in filename: %s.', $filename));
        }

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
        if (!array_search($dir, self::$CLASS_PATH[$dir])) {
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
}
