<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Auth_Interface
 *
 * 用户认证类接口
 *
 * @author     liut(liutao@it168.com)
 * @version    $Id$
 */



/**
 * Auth_Interface
 *
 */
interface Auth_Interface
{
	/**
	 * 返回用户数字编号
	 * @return int
	 */
	public function getUid();
	/**
	 * 返回用户名
	 * @return string
	 */
	public function getUsername();
	/**
	 * 返回用户Email
	 * @return string
	 */
	public function getEmail();
	/**
	 * 返回是否是已经登记的且非匿名用户
	 * @return bool
	 */
	public function isAuthed();
	/**
	 * 验证登录
	 * @return bool
	 */
	public function login($username, $password);
	/**
	 * 验证登录并重定向到登录地址
	 * @return bool
	 */
	public function verifyLogin($url, $redirect = true);
	/**
	 * 退出（清除相关信息，如Cookie、Session等）
	 * @return bool
	 */
	public function logout();
}