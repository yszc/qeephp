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
 * 定义 QView_Adapter_Smarty 类
 *
 * @package mvc
 * @version $Id$
 */

// {{{ includes

do {
    if (class_exists('Smarty', false)) { break; }

    $view_config = (array)Q::getIni('view_config');
    if (empty($view_config['smarty_dir']) && !defined('SMARTY_DIR')) {
        throw new QView_Exception(__('Application settings "view_config[\'smarty_dir\']" ' .
                                     'and constant SMARTY_DIR must be defined for QView_Adapter_Smarty.'));
    }

    if (empty($view_config['smarty_dir'])) {
        $view_config['smarty_dir'] = SMARTY_DIR;
    }

    Q::loadFile('Smarty.class.php', true, $view_config['smarty_dir']);
} while (false);

// }}}

/**
 * QView_Adapter_Smarty 提供了对 Smarty 模板引擎的支持
 *
 * @package mvc
 */
class QView_Adapter_Smarty extends Smarty implements QView_Adapter_Interface
{
    /**
     * 构造函数
     *
     * @return QView_Adapter_Smarty
     */
    function __construct()
    {
        parent::Smarty();

        $view_config = (array)Q::getIni('view_config');
        foreach ($view_config as $key => $value) {
            if (isset($this->{$key})) {
                $this->{$key} = $value;
            }
        }

        QView_Adapter_Smarty_Helper::bind($this);
    }

    /**
     * 清除已经设置的所有数据
     */
    function clear()
    {
        $this->clear_all_assign();
    }
}
