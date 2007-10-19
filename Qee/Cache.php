<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * 缓存操作控制类
 * 
 * @author      liut(liutao@it168.com)
 * @version     $Id$
 * @lastupdate $Date$
 */


/**
 * Cache
 *
 *
 * Example: 
 * <code>
 * // config
 * 	'cache_default' => array(
 * 		'debug' => false,
 * 		'enabled' => true,
 * 		'engine' => 'Memcache',
 * 		'group' => 'default',
 * 		'servers' => array(
 * 			'127.0.0.1', // localhost, default port
 * 			//'10.0.0.1:11211', // port 11211
 * 		),
 * 		'compress' => true, // compress data in Memcache (slower, but uses less memory)
 * 		'persistent' => FALSE
 * 	),
 * // use
 * 	$cache = Cache::getSolution('cache_default');
 * 	$ret = $cache->set($key, $data, 60);	// set
 * 	$c_data = $cache->get($key);		// get
 * </code>
 */
abstract class Cache implements Cache_Interface
{
	private static $_solutions = array();
	
	public static function & getSolution($name = 'cache_default')
	{
		if (!isset($_solutions[$name]) || !$_solutions[$name]) {
			$params = Qee::getAppInf($name);
			if(empty($params) || empty($params['engine'])) 
			{
				throw new Exception('Cache config not found: '. $name);
			}
			$className = 'Cache_'.$params['engine'];
			$_solutions[$name] =& new $className();
			$_solutions[$name]->init($options);
		}
		return $_solutions[$name];
	}

	abstract public function init($params = NULL);
	abstract public function get($key);
	abstract public function set($key, $value, $expire);
	abstract public function delete($key, $expire = NULL);
	abstract public function clean();
}