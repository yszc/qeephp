<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Cache_Interface
 *
 * 缓存引擎类接口
 *
 * @author     liut
 * @version    $Id$
 * @lastupdate $Date$
 */



/**
 * Cache_Interface
 *
 */
interface Cache_Interface
{
	public function init($params = NULL);
	public function get($key);
	public function set($key, $value, $expire);
	public function delete($key, $expire = NULL);
	public function clean();
}