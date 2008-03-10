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
 * 定义 QRouter 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QRouter 类实现自定义路由的解析
 *
 * @package mvc
 */
class QRouter
{
    /**
     * 所有的路由规则
     *
     * @var array
     */
    protected $rules = array();

    function __construct()
    {
    }

    /**
     * 添加一条路由规则
     *
     * @param string $rule
     * @param array $config
     */
    function add($rule, array $config = null)
    {

    }

    /**
     * 导入路由规则
     *
     * @param array $routes
     */
    function import(array $routes)
    {
        foreach ($routes as $rule => $config) {
            $this->add($rule, $config);
        }
    }

    /**
     * 分析路由
     */
    function parse()
    {
        foreach ($this->rules as $rule => $config) {
            $rule = $this->rule2regx();
        }
    }

    protected function rule2regx($rule)
    {
        $parts = explode('/', $rule);
        foreach ($parts as $part) {
        }

    }
}
