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
 * 定义 QDBO_Result_Mysql 类
 *
 * @package DB
 * @version $Id$
 */

/**
 * QDBO_Result_Mysql 封装了一个 mysql 查询句柄，便于释放资源
 *
 * @package DB
 */
class QDBO_Result_Mysql extends QDBO_Result_Abstract 
{
    function free()
    {
        if ($this->handle) { mysql_free_result($this->handle); }
        $this->handle = null;
    }

    function fetchRow()
    {
        if ($this->fetch_mode == QDBO_Abstract::fetch_mode_assoc) {
            return mysql_fetch_assoc($this->handle);
        } else {
            return mysql_fetch_array($this->handle);
        }
    }
}
