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
* 定义 Qee_View_SmartTemplate 类
*
* @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
* @author 小龙 xlonecn@msn.com
* @package Core
* @version $Id$
*/

// {{{ includes

do {
    if (class_exists('SmartTemplate', false)) { break; }

    $viewConfig = Qee::getAppInf('viewConfig');
    if (!isset($viewConfig['smartDir'])) {
        //require_once 'Qee/View/Exception/NotConfiguration.php';
        throw new Qee_View_Exception_NotConfiguration('SmartTemplate');
    }

    $filename = $viewConfig['smartDir'] . '/class.smarttemplate.php';
    if (!file_exists($filename)) {
        //require_once 'Qee/View/Exception/InitEngineFailed.php';
        throw new Qee_View_Exception_InitEngineFailed('SmartTemplate');
    }

    include $filename;
} while (false);

// }}}

/**
* Qee_View_SmartTemplate 提供了对 SmartTemplate 模板引擎的支持
*
* @author 小龙 xlonecn@msn.com
* @package Core
* @version 1.0
*/
class Qee_View_SmartTemplate extends SmartTemplate
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::SmartTemplate();

        $viewConfig = Qee::getAppInf('viewConfig');
        if (is_array($viewConfig)) {
            foreach ($viewConfig as $key => $value) {
                if (isset($this->{$key})) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    /**
     * 输出指定模版的内容
     *
     * @param string $tpl
     */
    function display($tpl)
    {
        $this->tpl_file = $tpl;
        $this->output();
    }
}
