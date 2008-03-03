<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QView_Adapter_Smarty 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

// {{{ includes

do {
    if (class_exists('Smarty', false)) { break; }

    $view_config = Q::getIni('view_config');
    if (!isset($view_config['smarty_dir']) && !defined('SMARTY_DIR')) {
        throw new QView_Exception('Application settings "view_config[\'smarty_dir\']" and constant SMARTY_DIR must be defined for QView_Adapter_Smarty.');
    }

    if (!isset($view_config['smarty_dir'])) {
        $view_config['smarty_dir'] = SMARTY_DIR;
    }

    Q::load_file('Smarty.class.php', true, $view_config['smarty_dir']);
} while (false);

// }}}

/**
 * QView_Adapter_Smarty 提供了对 Smarty 模板引擎的支持
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 */
class QView_Adapter_Smarty extends Smarty
{
    /**
     * 构造函数
     *
     * @return QView_Adapter_Smarty
     */
    function __construct()
    {
        parent::Smarty();

        $view_config = Q::getIni('view_config');
        foreach ($view_config as $key => $value) {
            if (isset($this->{$key})) {
                $this->{$key} = $value;
            }
        }

        QView_Adapter_SmartyHelper::bind($this);
    }
}
