<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
* 缓存操作籿 使用Memcache
* 
* @author      liut(liutao@it168.com)
* @copyright   It168
* @package     Core
* @version     $Id$
* @lastupdate $Date$
*/

// 使用Pear/Cache/Lite
require_once 'Cache/Lite.php';

/**
 * Cache_Memcache
 *
 *
 * Example: 
 * <code>
 * // config
 * 	'cache_default' => array(
 * 		'debug' => false,
 * 		'enabled' => true,
 * 		'engine' => 'CacheLite',
 * 		'group' => 'default',
 * 		'cachedir' => '/www/cache',
 * 		'lifetime' => 60*60,
 *		'fileNameProtection' => FALSE,
 *		'hashedDirectoryLevel' => 2,
 * 	),
 * // use
 * 	$cache = Cache::getSolution('cache_default');
 * 	$ret = $cache->set($key, $data, 60);	// set
 * 	$c_data = $cache->get($key);		// get
 * </code>
 */
class Cache_CacheLite extends Cache_Lite implements Cache_Interface
{
	public $_group     = 'default';
	public function init($params = NULL)
	{
		static $inited = FALSE;
		if ($inited) return true;
		$inited = TRUE;
		$enabled = TRUE;
		$group = 'default';
		$cachedir = '/www/cache';
		$debug = FALSE;
		$lifetime = 60 * 60;
		$fileNameProtection = FALSE;
		$hashedDirectoryLevel = 1;
		if(is_array($params)) extract($params);
		$this->_group = $group;
		$cachedir .= '/'. $group . '/';
		if(!is_dir($cachedir)) @mkdir($cachedir);
		$options = array(
			'caching' => $enabled, 
			'cacheDir' => $cachedir,
			'fileNameProtection' => $fileNameProtection,
			'hashedDirectoryLevel' => $hashedDirectoryLevel,
			'lifeTime' => $lifetime
		);
		parent::Cache_Lite($options);
	}

	public function get($key)
	{
		return parent::get($key, $this->_group);
	}

	public function set($key, $value, $expire)
	{
		$this->setOption('lifeTime', $expire);
		$this->save($value, $key, $this->_group);
	}
	
	public function delete($key, $expire = NULL)
	{
		$this->remove($key, $this->_group);
	}
	
	
	public function clean()
	{
		$this->clean($this->_group);
	}

}