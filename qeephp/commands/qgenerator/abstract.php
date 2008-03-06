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
 * 定义 QGenerator_Abstract 类
 *
 * @package commands
 * @version $Id$
 */

/**
 * QGenerator_Abstract 是所有生成器的基础类
 *
 * @package commands
 */
abstract class QGenerator_Abstract
{
    /**
     * 应用程序的配置
     *
     * @var array
     */
    protected $config;

    /**
     * 构造函数
     */
    function __construct()
    {
        $this->config = load_boot_config($module);
    }

    /**
     * 继承类必须覆盖此方法
     *
     * @param array $opts
     */
    abstract function execute(array $opts);

    /**
     * 将以“_”下划线分割的字符串转换成骆驼表示法（除第一个单词外，每个单词的第一个字母大写）
     *
     * @param string $name
     *
     * @return string
     */
    protected function camelName($name)
    {
        $name = strtolower($name);
        while (($pos = strpos($name, '_')) !== false) {
            $name = substr($name, 0, $pos) . ucfirst(substr($name, $pos + 1));
        }
        return $name;
    }

    /**
     * 检查指定的类文件是否在应用程序目录中
     *
     * @param string $class
     *
     * @return string|boolean
     */
    protected function existsClassFile($class)
    {
        $dir = ROOT_DIR . '/app/' . $this->module;
        $filename = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        $path = $dir . DIRECTORY_SEPARATOR . strtolower($filename);
        if (file_exists($path)) {
            return $path;
        } else {
            return false;
        }
    }

    /**
     * 创建指定类的定义文件
     *
     * @param string $class
     * @param string $content
     */
    protected function createClassFile($class, $content)
    {
        $dir = ROOT_DIR . '/app/' . $this->module;
        $filename = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        $path = $dir . DIRECTORY_SEPARATOR . strtolower($filename);
        $dir = dirname($path);
        Q::load_ext('filedir');
        mkdirs($dir);
        if (file_put_contents($path, $content)) {
            echo "Create file '{$path}' successed.\n";
            return true;
        }
        return false;
    }

    /**
     * 载入模板，返回解析结果
     *
     * @param string $tpl
     * @param array $viewdata
     *
     * @return string
     */
    protected function parseTemplate($__template, $viewdata)
    {
        ob_start();
        call_user_func_array(array(__CLASS__, 'parseTemplateStatic'), array($__template, $viewdata));
        return ob_get_clean();
    }

    /**
     * 载入模板，返回解析结果（静态方法）
     *
     * @param string $tpl
     * @param array $viewdata
     *
     * @static
     */
    protected static function parseTemplateStatic($__template, $viewdata)
    {
        $__template = dirname(__FILE__) . '/templates/template_' . $__template . '.php';
        extract($viewdata);
        return include($__template);
    }
}
