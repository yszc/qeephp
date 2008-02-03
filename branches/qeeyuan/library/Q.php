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
 * 定义 QeePHP 核心服务类 Q
 *
 * @package Core
 * @version $Id$
 */

if (defined('QEE_VERSION')) { return; }

// {{{ constants
// 定义 QeePHP 版本号常量和 QeePHP 所在路径
define('QEE_VERSION', '2.0');
define('QEE_DIR', dirname(__FILE__));

// DIRECTORY_SEPARATOR 的简写
define('DS', DIRECTORY_SEPARATOR);

// 定义可用的运行模式
define('QEEPHP_MODE_DEBUG',     'debug');
define('QEEPHP_MODE_DEPLOY',    'deploy');
define('QEEPHP_MODE_TEST',      'test');
// }}}

// {{{ includes
require QEE_DIR . DS . 'QException.php';
// }}}

/**
 * Q 类提供了 QeePHP 框架的基本服务
 *
 * @package core
 */
abstract class Q
{
    // {{{ -- private member var
    /**
     * 应用程序设置
     *
     * @var array
     */
    private static $config = array();

    /**
     * 对象注册表
     *
     * @var array
     */
    private static $objects = array();

    /**
     * 类搜索路径
     *
     * @var array
     */
    private static $class_path = array();
    // }}}

    // {{{ getIni()
    /**
     * 获取指定的应用程序设置内容
     *
     * @param string $option
     * @param mixed $default
     *
     * @return mixed
     */
    static function getIni($option, $default = null)
    {
        if (strpos($option, '/') === false) {
            return isset(self::$config[$option]) ? self::$config[$option] : $default;
        }
        $parts = explode('/', $option);
        $pos =& self::$config;
        foreach ($parts as $part) {
            if (!isset($pos[$part])) { return $default; }
            $pos =& $pos[$part];
        }
        return $pos;
    }
    // }}}

    // {{{ setIni()
    /**
     * 修改指定应用程序设置的内容
     *
     * @param string $option
     * @param mixed $data
     */
    static function setIni($option, $data = null)
    {
        if (is_array($option)) {
            self::$config = array_merge(self::$config, $option);
        } elseif (strpos($option, '/') === false) {
            self::$config[$option] = $data;
        } else {
            $parts = explode('/', $option);
            $max = count($parts) - 1;
            $pos =& self::$config;
            for ($i = 0; $i <= $max; $i++) {
                $part = $parts[$i];
                if ($i < $max) {
                    if (!isset($pos[$part])) {
                        $pos[$part] = array();
                    }
                    $pos =& $pos[$part];
                } else {
                    $pos[$part] = $data;
                }
            }
        }
    }
    // }}}

    // {{{ getCache()
    /**
     * 读取指定缓存的内容，如果缓存内容不存在或已经失效，则返回 false
     *
     * @param string $id
     * @param array $policy
     * @param string $backend
     *
     * @return mixed
     */
    static function getCache($id, array $policy = null, $backend = null)
    {
        static $obj = null;

        if (is_null($backend)) {
            if (is_null($obj)) {
                $obj = self::getSingleton(self::getIni('default_cache_backend'));
            }
            return $obj->get($id, $policy);
        } else {
            $cache = self::getSingleton($backend);
            return $cache->get($id, $policy);
        }
    }
    // }}}

    // {{{ setCache()
    /**
     * 将变量内容写入缓存
     *
     * @param string $id
     * @param mixed $data
     * @param array $policy
     * @param string $backend
     */
    static function setCache($id, $data, array $policy = null, $backend = null)
    {
        static $obj = null;

        if (is_null($backend)) {
            if (is_null($obj)) {
                $obj = self::getSingleton(self::getIni('default_cache_backend'));
            }
            $obj->set($id, $data, $policy);
        } else {
            $cache = self::getSingleton($backend);
            $cache->set($id, $data, $policy);
        }
    }
    // }}}

    // {{{ removeCache()
    /**
     * 删除指定的缓存内容
     *
     * @param string $id
     * @param array $policy
     * @param string $backend
     */
    static function removeCache($id, array $policy = null, $backend = null)
    {
        if (is_null($backend)) {
            $cache = self::getSingleton(self::getIni('default_cache_backend'));
        } else {
            $cache = self::getSingleton($backend);
        }
        $cache->remove($id, $policy);
    }
    // }}}

    // {{{ loadClass()
    /**
     * 载入指定类的定义文件，如果载入失败抛出异常
     *
     * example:
     * <code>
     * Q::loadClass('Table_Posts');
     * </code>
     *
     * 在查找类定义文件时，类名称中的“_”会被替换为目录分隔符，
     * 从而确定类名称和类定义文件的映射关系 (例如：Table_Posts 的定义文件为 Table/Posts.php)
     *
     * loadClass() 会首先尝试从开发者指定的搜索路径中查找类的定义文件。
     * 搜索路径可以用 Q::import() 添加。
     *
     * 如果 $suffix 参数不为空，则会在搜索类定义文件路径中添加 $suffix 指定的目录名。
     *
     * @param string $className 要载入的类名字
     * @param string|array $dirs
     * @param string $suffix
     */
    static function loadClass($class_name, $dirs = null, $suffix = null)
    {
        if (class_exists($class_name, false) || interface_exists($class_name, false)) {
            return;
        }

        $filename = str_replace('_', DS, $class_name);
        if (!is_null($suffix)) {
            $suffix = trim($suffix, '\\/');
            $suffix = DS . $suffix;
        }
        
        if ($filename != $class_name) {
            $dirname = dirname($filename);
            if (is_null($dirs)) {
                $dirs = array();
                foreach (self::$class_path as $dir) {
                    if ($dir == '.') {
                        $dirs[] = $dirname . $suffix;
                    } else {
                        $dir = rtrim($dir, '\\/');
                        $dirs[] = $dir . DS . $dirname . $suffix;
                    }
                }
            } else {
                if (is_string($dirs)) {
                    $dirs = explode(PATH_SEPARATOR, $dirs);
                }
                foreach ($dirs as $offset => $dir) {
                    $dirs[$offset] = $dir . DS . $dirname . $suffix;
                }
            }
            $filename = basename($filename) . '.php';
        } else {
            $dirs = self::$class_path;
            $filename .= '.php';
        }
        
        QDebug::dump($filename, '$filename');
        QDebug::dump($dirs, '$dirs');

        self::loadFile($filename, true, $dirs);
    }
    // }}}

    // {{{ loadFile()
    /**
     * 载入指定的文件
     *
     * $filename 参数必须是一个包含扩展名的完整文件名。
     * loadFile() 会首先从 $dirs 参数指定的路径中查找文件，
     * 找不到时再从 PHP 的 include_path 搜索路径中查找文件。
     *
     * $once 参数指示同一个文件是否只载入一次。
     *
     * @param string $filename 要载入的文件名
     * @param boolean $once 指示对于同一个文件是否只载入一次
     * @param array $dirs 搜索目录
     * @param boolean $throw 在找不到文件时是否抛出异常
     *
     * @return mixed
     */
    static function loadFile($filename, $once = false, $dirs = null, $throw = true)
    {
        static $loaded = array();

        $id = $filename . implode(':', (array)$dirs);
        
        QDebug::dump($id, '$id');
        QDebug::dump($loaded, '$loaded');
        
        if ($once && isset($loaded[$id])) { return null; }
        if (preg_match('/[^a-z0-9\-_.]/i', $filename)) {
            // LC_MSG: Security check: Illegal character in filename "%s".
            throw new QException(__('Security check: Illegal character in filename "%s".', $filename));
        }

        if (is_null($dirs)) {
            $dirs = array();
        } elseif (is_string($dirs)) {
            $dirs = explode(PATH_SEPARATOR, $dirs);
        }
        foreach ($dirs as $dir) {
            $path = rtrim($dir, '\\/') . DS . $filename;
            if (self::isReadable($path)) {
                if ($once) { 
                    $loaded[$id] = true;
                    return include_once $path; 
                } else {
                	return include $path;
                }
            }
        }

        // include 会在 include_path 中寻找文件
        if (self::isReadable($filename)) {
            if ($once) {
            	$loaded[$id] = true;
            	return include $filename;
            } else {
                return include $filename;
            }
        }

        if ($throw) {
            // LC_MSG: File "%s" not found.
            throw new QException(__('File "%s" not found.', $filename));
        }

        return false;
    }
    // }}}

    // {{{ getSingleton()
    /**
     * 返回指定对象的唯一实例，如果指定类无法载入或不存在，则抛出异常
     *
     * @param string $class_name 要获取的对象的类名字
     *
     * @return object
     */
    static function getSingleton($class_name)
    {
        if (isset(self::$objects[$class_name])) {
            return self::$objects[$class_name];
        }
        self::loadClass($class_name);
        return self::register(new $class_name(), $class_name);
    }
    // }}}

    // {{{ register()
    /**
     * 以特定名字注册一个对象，以便稍后用 registry() 方法取回该对象。
     * 如果指定名字已经被使用，则抛出异常
     *
     * example:
     * <code>
     * // 注册一个对象
     * Q::register(new MyObject(), 'MyObejct');
     * .....
     * // 稍后取出对象
     * $obj = Q::regitry('MyObject');
     * </code>
     *
     * QEE 提供一个对象注册表，开发者可以将一个对象以特定名称注册到其中。
     *
     * 当没有提供 $name 参数时，则以对象的类名称作为注册名。
     *
     * 当 $persistent 参数为 true 时，对象将被放入持久存储区。在下一次执行脚本时，
     * 可以通过 Q::registry() 取出放入持久存储区的对象，无需重新构造对象。
     * 利用这个特性，开发者可以将一些需要大量构造时间的对象放入持久存储区，
     * 从而避免每一次执行脚本都去做对象构造操作。
     *
     * 使用哪一种持久化存储区来保存对象，由应用程序设置 object_persistent_provier 决定。
     * 该设置指定一个提供持久化服务的对象名。
     *
     * example:
     * <code>
     * if (!Q::isRegistered('ApplicationObject')) {
     * 		Q::loadClass('Application');
     * 		Q::registry(new Application(), 'ApplicationObject', true);
     * }
     * $app = Q::registry('ApplicationObject');
     * </code>
     *
     * @param object $obj 要注册的对象
     * @param string $name 注册的名字
     * @param boolean $persistent 是否将对象放入持久化存储区
     */
    static function register($obj, $name = null, $persistent = false)
    {
        if (!is_object($obj)) {
            // LC_MSG: Type mismatch. $obj expected is object, actual is "%s".
            throw new QException(__('Type mismatch. $obj expected is object, actual is "%s".', gettype($obj)));
        }

        // TODO: 实现对 $persistent 参数的支持
        if (is_null($name)) {
            $name = get_class($obj);
        }
        self::$objects[$name] = $obj;
        return $obj;
    }
    // }}}

    // {{{ registry()
    /**
     * 取得指定名字的对象实例，如果指定名字的对象不存在则抛出异常
     *
     * @param string $name
     *
     * @return object
     */
    static function registry($name)
    {
        if (isset(self::$objects[$name])) {
            return self::$objects[$name];
        }
        // LC_MSG: No object is registered of name "%s".
        throw new QException(__('No object is registered of name "%s".', $name));
    }
    // }}}

    // {{{ isRegistered()
    /**
     * 检查指定名字的对象是否已经注册
     *
     * @param string $name
     *
     * @return boolean
     */
    static function isRegistered($name)
    {
        return isset(self::$objects[$name]);
    }
    // }}}

    // {{{ import()
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
     * Q::import('/www/app');
     * Q::loadClass('Service_Products');
     * </code>
     *
     * @param string $dir
     */
    static function import($dir)
    {
    	if (!isset(self::$class_path[$dir]) || !is_dir($dir)) {
    		self::$class_path[$dir] = realpath($dir);
    	}
    }
    // }}}

    // {{{ isReadable()
    /**
     * 检查指定文件是否可读
     *
     * 这个方法会在 PHP 的搜索路径中查找文件。
     *
     * @param string $filename
     *
     * @return boolean
     */
    static function isReadable($filename)
    {
        if (is_readable($filename)) { return true; }

        $path = get_include_path();
        $dirs = explode(PATH_SEPARATOR, $path);

        foreach ($dirs as $dir) {
            if ('.' == $dir) { continue; }
            if (@is_readable($dir . DS . $filename)) {
                return true;
            }
        }

        return false;
    }
    // }}}

    // {{{ normalize()
    /**
     * 对输入的数组或字符串进行规格化
     *
     * @param array|string $input
     *
     * @return array
     */
    static function normalize($input)
    {
        if (!is_array($input)) {
            $input = explode(',', $input);
        }
        $input = array_map('trim', $input);
        return array_filter($input, 'strlen');
    }
    // }}}

    // {{{ loadYAML()
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
                $policy = self::getIni('default_yaml_cache_policy');
            }
            if (isset($cache[1])) {
                $backend = $cache[1];
            } else {
                $backend = null;
            }
            $cache_enabled = true;
        } elseif ($cache) {
            $cache_enabled = true;
            $policy = self::getIni('default_yaml_cache_policy');
            $backend = null;
        } else {
            $cache_enabled = false;
        }

        if ($cache_enabled) {
            $yaml = self::getCache('YAML-' . $filename, $policy);
            if ($yaml) { return $yaml; }
        }

        if (!Q::isReadable($filename)) {
            // LC_MSG: File "%s" not found.
            throw new QException(__('File "%s" not found.', $filename));
        }

        self::loadFile(QEE_DIR . DS . '_vendor' . DS . 'spyc.php', true);
        $yaml = Spyc::YAMLLoad($filename);

        if (is_null($callback)) {
            $callback = create_function('& $v, $key, $replace', 'foreach ($replace as $search => $rep) { $v = str_replace($search, $rep, $v); }; return $v;');
        }
        array_walk_recursive($yaml, $callback, $replace);

        if ($cache_enabled) {
            self::setCache('yaml:' . $filename, $yaml, $policy, $backend);
        }
        return $yaml;
    }
    // }}}
}

// {{{ __()
/**
 * QeePHP 内部使用的多语言翻译函数
 *
 * 应用程序应该使用 Translate 组件实现多语言界面。
 *
 * @return $msg
 */
function __()
{
    $args = func_get_args();
    $msg = array_shift($args);
    $language = strtolower(Q::getIni('error_msg_language'));
    $messages = Q::loadFile('LC_MESSAGES.php', false, QEE_DIR . '/_lang/' . $language, false);
    if (isset($messages[$msg])) {
        $msg = $messages[$msg];
    }
    array_unshift($args, $msg);
    return call_user_func_array('sprintf', $args);
}
// }}}

