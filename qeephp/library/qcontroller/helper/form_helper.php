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
 * 定义 Helper_Form 类
 *
 * @package helper
 * @version $Id: form_helper.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * Helper_Form 为控制器提供处理表单的辅助方法
 *
 * @package helper
 */
class Helper_Form
{
    /**
     * QController_Abstract 实例
     *
     * @var QController_Abstract
     */
    protected $controller;

    /**
     * 构造函数
     *
     * @param QController_Abstract $controller
     */
    function __construct(QController_Abstract $controller = null)
    {
        $this->controller = $controller;
    }

    /**
     * 根据特定 Model 类名，返回包含该 Model 所有属性名的数组
     *
     * @param string $model_class
     * @param QRequest $request
     *
     * @return array
     */
    function prepare($model_class, QRequest $request = null)
    {
        $ref = QDB_ActiveRecord_Abstract::reflection($model_class);
        $form = array();
        $pk = Q::normalize($ref['pk']);
        if ($request) {
            foreach ($ref['alias'] as $a) {
                if (in_array($a, $pk)) { continue; }
                $form[$a] = $request->getPost($a);
            }
        } else {
            foreach ($ref['alias'] as $a) {
                if (in_array($a, $pk)) { continue; }
                $form[$a] = null;
            }
        }
        $form['_pk'] = $pk;
        return $form;
    }
}
