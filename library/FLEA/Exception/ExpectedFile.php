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
 * 定义 FLEA_Exception_ExpectedFile 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Exception
 * @version $Id: ExpectedFile.php 861 2007-06-01 16:37:41Z dualface $
 */

/**
 * FLEA_Exception_ExpectedFile 异常指示需要的文件没有找到
 *
 * @package Exception
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Exception_ExpectedFile extends FLEA_Exception
{
    /**
     * 要载入的语言文件名
     *
     * @var string
     */
    public $filename;

    function __construct($filename)
    {
        $this->filename = $filename;
        parent::__construct(self::t('Required file "%s" is missing.', $filename));
    }
}
