<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 FLEA_View_Lite 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

// {{{ includes

do {
    if (class_exists('Template_Lite', false)) { break; }
    $viewConfig = FLEA::getAppInf('viewConfig');
    if (!isset($viewConfig['liteDir'])) {
        require_once 'FLEA/View/Exception/NotConfiguration.php';
        throw new FLEA_View_Exception_NotConfiguration('Template Lite');
    }

    $filename = $viewConfig['liteDir'] . '/class.template.php';
    if (!file_exists($filename)) {
        require_once 'FLEA/View/Exception/InitEngineFailed.php';
        throw new FLEA_View_Exception_InitEngineFailed('Template Lite');
    }

    include $filename;
} while (false);

// }}}

/**
 * FLEA_View_Lite 提供了对 TemplateLite 模板引擎的支持
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_View_Lite extends Template_Lite
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::Template_Lite();

        $viewConfig = FLEA::getAppInf('viewConfig');
        if (is_array($viewConfig)) {
            foreach ($viewConfig as $key => $value) {
                if (isset($this->{$key})) {
                    $this->{$key} = $value;
                }
            }
        }

        require_once 'FLEA/View/martyHelper.php';
        new FLEA_View_SmartyHelper($this);
    }
}
