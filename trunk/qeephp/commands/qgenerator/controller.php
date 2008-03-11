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
 * 定义 QGenerator_Controller 类
 *
 * @package generator
 * @version $Id$
 */

/**
 * QGenerator_Controller 创建控制器代码
 *
 * @package generator
 */
class QGenerator_Controller extends QGenerator_Abstract
{
    /**
     * 执行代码生成器
     *
     * @param array $opts
     *
     * @return mixed
     */
    function execute(array $opts)
    {
        $controller_name = reset($opts);
        if (empty($controller_name)) {
            return false;
        }

        $arr = explode('::', $controller_name);
        if (count($arr) == 2) {
            list($namespace, $controller_name) = $arr;
            $full_name = "{$namespace}::{$controller_name}";
        } else {
            $namespace = null;
            $full_name = $controller_name;
        }

        $class_name = 'Controller_' . ucfirst(strtolower($controller_name));
        if (($filename = $this->existsClassFile($class_name, $namespace))) {
            echo "Class '{$full_name}' declare file '{$filename}' exists.\n";
            return 0;
        }

        $content = $this->getCode($class_name, $namespace);
        if ($content !== -1 && !empty($content)) {
            return $this->createClassFile($class_name, $content, $namespace);
        } else {
            return false;
        }
    }

    /**
     * 生成代码
     *
     * @param string $class_name
     *
     * @return string
     */
    function getCode($class_name)
    {
        $viewdata = array('class_name' => $class_name);
        return $this->parseTemplate('controller', $viewdata);
    }
}
