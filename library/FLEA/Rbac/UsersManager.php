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
 * 定义 FLEA_Rbac_UsersManager 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id$
 */

// {{{ constants
/**
 * 密码的加密方式
 */
define('PWD_MD5',       1);
define('PWD_CRYPT',     2);
define('PWD_CLEARTEXT', 3);
define('PWD_SHA1',      4);
define('PWD_SHA2',      5);
// }}}

// {{{ includes
require_once 'FLEA/Db/TableDataGateway.php';
// }}}

/**
 * UsersManager 派生自 FLEA_Db_TableDataGateway，用于访问保存用户信息的数据表
 *
 * 如果数据表的名字不同，应该从 FLEA_Rbac_UsersManager 派生类并使用自定义的数据表名字、主键字段名等。
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Rbac_UsersManager extends FLEA_Db_TableDataGateway
{
    /**
     * 主键字段名
     *
     * @var string
     */
    public $primaryKey = 'user_id';

    /**
     * 数据表名字
     *
     * @var string
     */
    public $tableName = 'users';

    /**
     * 用户名字段的名字
     *
     * @var string
     */
    public $usernameField = 'username';

    /**
     * 电子邮件字段的名字
     *
     * @var string
     */
    public $emailField = 'email';

    /**
     * 密码字段的名字
     *
     * @var string
     */
    public $passwordField = 'password';

    /**
     * 角色字段的名字
     *
     * @var string
     */
    public $rolesField = 'roles';

    /**
     * 密码加密方式
     *
     * @var int
     */
    public $encodeMethod = PWD_MD5;

    /**
     * 对数据进行自动验证
     *
     * @var boolean
     */
    public $autoValidating = true;

    /**
     * 指定其他具有特殊意义的字段
     *
     * @var array
     */
    public $functionFields = array(
        'registerIpField' => null,
        'lastLoginField' => null,
        'lastLoginIpField' => null,
        'loginCountField' => null,
        'isLockedField' => null,
    );

    /**
     * 构造函数
     */
    public function __construct($params = null)
    {
        parent::__construct($params);
        $mn = strtoupper($this->emailField);
        if (isset($this->meta[$mn])) {
            $this->meta[$mn]['complexType'] = 'EMAIL';
        }
    }

    /**
     * 返回指定 ID 的用户
     *
     * @param mixed $id
     * @param mixed $fields
     *
     * @return array
     */
    public function findByUserId($id, $fields = '*')
    {
        return $this->findByField($this->primaryKey, $id, null, $fields);
    }

    /**
     * 返回指定用户名的用户
     *
     * @param string $username
     * @param mixed $fields
     *
     * @return array
     */
    public function findByUsername($username, $fields = '*')
    {
        return $this->findByField($this->usernameField, $username, null, $fields);
    }

    /**
     * 返回指定电子邮件的用户
     *
     * @param string $email
     * @param mixed $fields
     *
     * @return array
     */
    public function findByEmail($email, $fields = '*')
    {
        return $this->findByField($this->emailField, $email, null, $fields);
    }

    /**
     * 检查指定的用户ID是否已经存在
     *
     * @param mixed $id
     *
     * @return boolean
     */
    public function existsUserId($id)
    {
        return $this->findCount(array($this->primaryKey => $id)) > 0;
    }

    /**
     * 检查指定的用户名是否已经存在
     *
     * @param string $username
     *
     * @return boolean
     */
    public function existsUsername($username)
    {
        return $this->findCount(array($this->usernameField => $username)) > 0;
    }

    /**
     * 检查指定的电子邮件地址是否已经存在
     *
     * @param string $email
     *
     * @return boolean
     */
    public function existsEmail($email)
    {
        return $this->findCount(array($this->emailField => $email)) > 0;
    }

    /**
     * 创建用户记录，返回新建用户记录的主键值
     *
     * @param array $row
     *
     * @return mixed
     */
    public function create(& $row)
    {
        if (isset($this->functionFields['registerIpField']) && $this->functionFields['registerIpField'] != '') {
            $row[$this->functionFields['registerIpField']] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        }
        return parent::create($row);
    }

    /**
     * 验证指定的用户名和密码是否正确，验证成功则更新用户的登录信息
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param boolean $returnUserdata 指示验证通过后是否返回用户数据
     *
     * @return boolean|array
     *
     * @access public
     */
    public function validateUser($username, $password, $returnUserdata = false)
    {
        if ($returnUserdata) {
            $user = $this->findByField($this->usernameField, $username);
        } else {
            $fields = array($this->primaryKey, $this->passwordField);
            if (isset($this->functionFields['loginCountField']) && $this->functionFields['loginCountField'] != '') {
                $fields[] = $this->functionFields['loginCountField'];
            }
            if (isset($this->functionFields['isLockedField']) && $this->functionFields['isLockedField'] != '') {
                $fields[] = $this->functionFields['isLockedField'];
            }
            $user = $this->findByField($this->usernameField, $username, null, $fields);
        }
        if (!$user) { return false; }
        if (isset($this->functionFields['isLockedField']) && $this->functionFields['isLockedField'] != '') {
            if ($user[$this->functionFields['isLockedField']]) { return false; }
        }
        if (!$this->checkPassword($password, $user[$this->passwordField])) {
            return false;
        }

        $update = array();

        if (isset($this->functionFields['lastLoginField']) && $this->functionFields['lastLoginField'] != '') {
            $update[$this->functionFields['lastLoginField']] = time();
        }

        if (isset($this->functionFields['lastLoginIpField']) && $this->functionFields['lastLoginIpField'] != '') {
            $update[$this->functionFields['lastLoginIpField']] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        }

        if (isset($this->functionFields['loginCountField']) && $this->functionFields['loginCountField'] != '') {
            $update[$this->functionFields['loginCountField']] = $user[$this->functionFields['loginCountField']] + 1;
        }

        if (!empty($update)) {
            $update[$this->primaryKey] = $user[$this->primaryKey];
            $this->update($update);
        }

        if ($returnUserdata) { return $user; }
        return true;
    }

    /**
     * 更新指定用户的密码
     *
     * @param string $username 用户名
     * @param string $oldPassword 现在使用的密码
     * @param string $newPassword 新密码
     *
     * @return boolean
     *
     * @access public
     */
    public function changePassword($username, $oldPassword, $newPassword)
    {
        $user = $this->findByField($this->usernameField, $username, null, array($this->primaryKey, $this->passwordField));
        if (!$user) { return false; }
        if (!$this->checkPassword($oldPassword, $user[$this->passwordField])) {
            return false;
        }
        $user[$this->passwordField] = $newPassword;
        return parent::update($user);
    }

    /**
     * 直接更新密码
     *
     * @param string $username
     * @param string $newPassword
     *
     * @return boolean
     */
    public function updatePassword($username, $newPassword)
    {
        $user = $this->findByField($this->usernameField, $username, null, $this->primaryKey);
        if (!$user) { return false; }
        $user[$this->passwordField] = $newPassword;
        return parent::update($user);
    }

    /**
     * 直接更新密码
     *
     * @param mixed $userId
     * @param string $newPassword
     *
     * @return boolean
     */
    public function updatePasswordById($userid, $newPassword)
    {
        $user = $this->findByField($this->primaryKey, $userid, null, $this->primaryKey);
        if (!$user) { return false; }

        $user[$this->passwordField] = $newPassword;
        return parent::update($user);
    }

    /**
     * 检查密码的明文和密文是否符合
     *
     * @param string $cleartext 密码的明文
     * @param string $cryptograph 密文
     *
     * @return boolean
     *
     * @access public
     */
    public function checkPassword($cleartext, $cryptograph)
    {
        switch ($this->encodeMethod) {
        case PWD_MD5:
            return (md5($cleartext) == rtrim($cryptograph));
        case PWD_CRYPT:
            return (crypt($cleartext, $cryptograph) == rtrim($cryptograph));
        case PWD_CLEARTEXT:
            return ($cleartext == rtrim($cryptograph));
        case PWD_SHA1:
            return (sha1($cleartext) == rtrim($cryptograph));
        case PWD_SHA2:
            return (hash('sha512', $cleartext) == rtrim($cryptograph));

        default:
            return false;
        }
    }

    /**
     * 将密码明文转换为密文
     *
     * @param string $cleartext 要加密的明文
     *
     * @return string
     *
     * @access public
     */
    public function encodePassword($cleartext)
    {
        switch ($this->encodeMethod) {
        case PWD_MD5:
            return md5($cleartext);
        case PWD_CRYPT:
            return crypt($cleartext);
        case PWD_CLEARTEXT:
            return $cleartext;
        case PWD_SHA1:
            return sha1($cleartext);
        case PWD_SHA2:
            return hash('sha512', $cleartext);

        default:
            return false;
        }
    }

    /**
     * 返回指定用户的角色名数组
     *
     * @param array $user
     * @param string $rolenameField
     *
     * @return array
     */
    public function fetchRoles(& $user, $rolenameField = 'rolename')
    {
        if (!isset($user[$this->rolesField]) || !is_array($user[$this->rolesField])) {
            return array();
        }
        $roles = array();
        foreach ($user[$this->rolesField] as $role) {
            if (!is_array($role)) {
                return array($user[$this->rolesField][$rolenameField]);
            }
            $roles[] = $role[$rolenameField];
        }
        return $roles;
    }

    /**
     * 更新用户信息时，禁止更新密码字段
     *
     * @param array $row
     *
     * @return boolean
     */
    public function update(& $row)
    {
        unset($row[$this->passwordField]);
        return parent::update($row);
    }

    /**
     * 在更新到数据库之前加密密码
     */
    protected function _beforeUpdateDb(& $row)
    {
        $this->_encodeRecordPassword($row);
        return true;
    }

    /**
     * 在更新到数据库之前加密密码
     */
    protected function _beforeCreateDb(& $row)
    {
        $this->_encodeRecordPassword($row);
        return true;
    }

    /**
     * 将记录里面的密码字段值从明文转为加密后的密文
     *
     * @param array $row
     */
    protected function _encodeRecordPassword(& $row)
    {
        if (isset($row[$this->passwordField])) {
            $row[$this->passwordField] =
                $this->encodePassword($row[$this->passwordField]);
        }
    }
}
