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
 * 定义 FLEA_Rbac_RolesManager 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Db/TableDataGateway.php';
// }}}

/**
 * FLEA_Rbac_RolesManager 派生自 FLEA_Db_TableDataGateway，
 * 用于访问保存角色信息的数据表
 *
 * 如果数据表的名字不同，应该从 FLEA_Rbac_RolesManager
 * 派生类并使用自定义的数据表名字、主键字段名等。
 *
 * @package Core
 */
class FLEA_Rbac_RolesManager extends FLEA_Db_TableDataGateway
{
    /**
     * 主键字段名
     *
     * @var string
     */
    public $primaryKey = 'role_id';

    /**
     * 数据表名字
     *
     * @var string
     */
    public $tableName = 'roles';

    /**
     * 角色名字段
     *
     * @var string
     */
    public $rolesNameField = 'rolename';
}
