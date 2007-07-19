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
 * 定义 FLEA_Exception_ValidationFailed 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Exception
 * @version $Id: ValidationFailed.php 861 2007-06-01 16:37:41Z dualface $
 */

/**
 * FLEA_Exception_ValidationFailed 异常指示数据验证失败
 *
 * @package Exception
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Exception_ValidationFailed extends FLEA_Exception
{
    /**
     * 被验证的数据
     *
     * @var mixed
     */
    public $data;

    /**
     * 验证结果
     *
     * @var array
     */
    public $result;

    /**
     * 构造函数
     *
     * @param array $result
     * @param mixed $data
     */
    function __construct($result, $data = null)
    {
        parent::__construct(self::t('The following data is invalid: "%s".', implode(', ', array_keys((array)$result))));
        $this->result = $result;
        $this->data = $data;
    }
}
