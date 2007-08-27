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
 * 定义 FLEA_Exception_CacheDisabled 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Exception
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Exception.php';
// }}}

/**
 * FLEA_Exception_CacheDisabled 异常指示缓存功能被禁用
 *
 * @package Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Exception_CacheDisabled extends FLEA_Exception
{
    /**
     * 缓存目录
     */
    public $cacheDir;

    function __construct($cacheDir)
    {
        parent::__construct(self::t('Cache function is disabled now. This is usually because internalCacheDir option is not defined or internalCacheDir directory is unwritable', $cacheDir));
        $this->cacheDir = $cacheDir;
    }
}
