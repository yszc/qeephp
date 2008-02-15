<?php
/////////////////////////////////////////////////////////////////////////////
// FleaPHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.fleaphp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 Generator_Controller 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package Tool
 * @version $Id$
 */

// {{{ includes
require_once dirname(__FILE__) . '/abstract.php';
// }}}

/**
 * Generator_Controller 创建控制器代码文件
 *
 * @package Tool
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.0
 */
class Generator_Controller extends Generator_Abstract
{
    function execute($module, array $opts)
    {
        dump($opts);
    }

}