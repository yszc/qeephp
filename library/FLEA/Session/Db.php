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
 * 定义 FLEA_Session_Db 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id$
 */

/**
 * FLEA_Session_Db 类提供将 session 保存到数据库的能力
 *
 * 要使用 FLEA_Session_Db，必须完成下列准备工作：
 *
 * - 创建需要的数据表
 *
 *     字段名       类型             用途
 *     sess_id     varchar(64)     存储 session id
 *     sess_data   text            存储 session 数据
 *     activity    int(11)         该 session 最后一次读取/写入时间
 *
 * - 修改应用程序设置 sessionProvider 为 FLEA_Session_Db
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Session_Db
{
    /**
     * 数据库访问对象
     *
     * @var FLEA_Db_Driver_Prototype
     */
    public $dbo = null;

    /**
     * 保存 session 的数据表名称，由应用程序设置 sessionDbTableName 指定
     *
     * @var string
     */
    public $tableName = null;

    /**
     * 保存 session id 的字段名，由应用程序设置 sessionDbFieldId 指定
     *
     * @var string
     */
    public $fieldId = null;

    /**
     * 保存 session 数据的字段名，由应用程序设置 sessionDbFieldData 指定
     *
     * @var string
     */
    public $fieldData = null;

    /**
     * 保存 session 过期时间的字段名，由应用程序设置 sessionDbFieldActivity 指定
     *
     * @var string
     */
    public $fieldActivity = null;

    /**
     * 指示 session 的有效期
     *
     * 0 表示由 PHP 运行环境决定，其他数值为超过最后一次活动时间多少秒后失效
     *
     * @var int
     */
    public $lifeTime = 0;

    /**
     * 构造函数
     *
     * @return FLEA_Session_Db
     */
    public function __construct()
    {
        $this->tableName     = FLEA::getAppInf('sessionDbTableName');
        $this->fieldId       = FLEA::getAppInf('sessionDbFieldId');
        $this->fieldData     = FLEA::getAppInf('sessionDbFieldData');
        $this->fieldActivity = FLEA::getAppInf('sessionDbFieldActivity');
        $this->lifeTime      = (int)FLEA::getAppInf('sessionDbLifeTime');

        session_set_save_handler(
            array(& $this, 'sessionOpen'),
            array(& $this, 'sessionClose'),
            array(& $this, 'sessionRead'),
            array(& $this, 'sessionWrite'),
            array(& $this, 'sessionDestroy'),
            array(& $this, 'sessionGc')
        );
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        session_write_close();
    }

    /**
     * 打开 session
     *
     * @param string $savePath
     * @param string $sessionName
     *
     * @return boolean
     */
    public function sessionOpen($savePath, $sessionName)
    {
        $dsnName = FLEA::getAppInf('sessionDbDSN');
        if ($dsnName != 'dbDSN') {
            $dsn = FLEA::getAppInf($dsnName);
        } else {
            $dsn = 0;
        }
        $this->dbo = FLEA::getDBO($dsn);

        $this->tableName     = $this->dbo->dsn['dbTablePrefix'] . $this->tableName;
        $this->tableName     = $this->dbo->qtable($this->tableName);
        $this->fieldId       = $this->dbo->qfield($this->fieldId);
        $this->fieldData     = $this->dbo->qfield($this->fieldData);
        $this->fieldActivity = $this->dbo->qfield($this->fieldActivity);

        $this->sessionGc(FLEA::getAppInf('sessionDbLifeTime'));

        return true;
    }

    /**
     * 关闭 session
     *
     * @return boolean
     */
    public function sessionClose()
    {
        return true;
    }

    /**
     * 读取指定 id 的 session 数据
     *
     * @param string $sessid
     *
     * @return string
     */
    public function sessionRead($sessid)
    {
        $sessid = $this->dbo->qstr($sessid);
        $sql = "SELECT {$this->fieldData} FROM {$this->tableName} WHERE {$this->fieldId} = {$sessid}";
        if ($this->lifeTime > 0) {
            $time = time() - $this->lifeTime;
            $sql .= " AND {$this->fieldActivity} >= {$time}";
        }
        return $this->dbo->getOne($sql);
    }

    /**
     * 写入指定 id 的 session 数据
     *
     * @param string $sessid
     * @param string $data
     *
     * @return boolean
     */
    public function sessionWrite($sessid, $data)
    {
        $sessid = $this->dbo->qstr($sessid);
        $sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE {$this->fieldId} = {$sessid}";
        $data = $this->dbo->qstr($data);
        $activity = time();

        $fields = (array)$this->_beforeWrite($sessid);
        if ((int)$this->dbo->getOne($sql) > 0) {
            $sql = "UPDATE {$this->tableName} SET {$this->fieldData} = {$data}, {$this->fieldActivity} = {$activity}";
            if (!empty($fields)) {
                $arr = array();
                foreach ($fields as $field => $value) {
                    $arr[] = $this->dbo->qfield($field) . ' = ' . $this->dbo->qstr($value);
                }
                $sql .= ', ' . implode(', ', $arr);
            }
            $sql .= " WHERE {$this->fieldId} = {$sessid}";
        } else {
            $extraFields = '';
            $extraValues = '';
            if (!empty($fields)) {
                foreach ($fields as $field => $value) {
                    $extraFields .= ', ' . $this->dbo->qfield($field);
                    $extraValues .= ', ' . $this->dbo->qstr($value);
                }
            }

            $sql = "INSERT INTO {$this->tableName} ({$this->fieldId}, {$this->fieldData}, {$this->fieldActivity}{$extraFields}) VALUES ({$sessid}, {$data}, {$activity}{$extraValues})";
        }

        return $this->dbo->execute($sql);
    }

    /**
     * 销毁指定 id 的 session
     *
     * @param string $sessid
     *
     * @return boolean
     */
    public function sessionDestroy($sessid)
    {
        $sessid = $this->dbo->qstr($sessid);
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->fieldId} = {$sessid}";
        return $this->dbo->execute($sql);
    }

    /**
     * 清理过期的 session 数据
     *
     * @param int $maxlifetime
     *
     * @return boolean
     */
    public function sessionGc($maxlifetime)
    {
        if ($this->lifeTime > 0) {
            $maxlifetime = $this->lifeTime;
        }
        $time = time() - $maxlifetime;
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->fieldActivity} < {$time}";
        $this->dbo->execute($sql);
        return true;
    }

    /**
     * 获取未过期的 session 总数
     *
     * @return int
     */
    public function getOnlineCount($lifetime = -1)
    {
        if ($this->lifeTime > 0) {
            $lifetime = $this->lifeTime;
        } else if ($lifetime <= 0) {
            $lifetime = (int)ini_get('session.gc_maxlifetime');
            if ($lifetime <= 0) {
                $lifetime = 1440;
            }
        }
        $sql = "SELECT COUNT(*) FROM {$this->tableName}";
        if ($this->lifeTime > 0) {
            $time = time() - $lifetime;
            $sql .= " WHERE {$this->fieldActivity} >= {$time}";
        }
        return (int)$this->dbo->getOne($sql);
    }

    /**
     * 返回要写入 session 的额外内容，开发者应该在继承类中覆盖此方法
     *
     * 例如返回：
     * return array(
     *      'username' => $username
     * );
     *
     * 数据表中要增加相应的 username 字段。
     *
     * @param string $sessid
     *
     * @return array
     */
    protected function _beforeWrite($sessid)
    {
        return array();
    }
}
