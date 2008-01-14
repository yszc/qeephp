<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * 缓存操作类, 使用Memcache
 * 
 * @author      liut(liutao@it168.com)
 * @version     $Id$
 * @lastupdate $Date$
 */



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
class Cache_Memcache implements Cache_Interface
{
	private $_memcache  = FALSE;
	private $_compress  = 0;
	private $_group     = '';
	private $_connected = FALSE;
	private $_debug     = FALSE;
	public $_logs      = NULL;

	/**
	 * Returns a singleton instance
	 *
	 * @return Cache_Memcache
	 * @access public
	 */
	public function init($params = NULL)
	{
		static $inited = FALSE;
		if ($inited) return $this->_connected;
		$inited = TRUE;
		//if (!extension_loaded('memcache')) {
		//	throw new Exception ('extension memcache is not loaded');
		//	return false;
		//}
		if(!class_exists('Memcache', false)) {
			return FALSE;
		}

		$servers = array('127.0.0.1');
		$compress = FALSE;
		$persistent = FALSE;
		$group = 'default';
		$debug = FALSE;

		//if (NULL == $params && isset($GLOBALS['g_memcache_options'])) $params = $GLOBALS['g_memcache_options'];
		if(is_array($params)) extract($params);

		if($compress) {
			$this->_compress = MEMCACHE_COMPRESSED;
		} else {
			$this->_compress = 0;
		}
		$this->_group = preg_replace('/[^a-z0-9_:]+/i', '', $group);
		$this->_debug = $debug ? TRUE : FALSE;

		if(!is_array($servers)) {
			$servers = array($servers);
		}
		$this->_memcache =& new Memcache();
		$this->_connected = FALSE;

		foreach($servers as $server) {
			$parts = explode(':', $server);
			$host = $parts[0];
			$port = isset($parts[1]) ? $parts[1] : 11211;

			if($this->_memcache->addServer($host, $port, $persistent)) {
				$this->_connected = true;
				if($this->_debug)
				{
					$message = sprintf("addServer %s:%d(%s) OK!", $host, $port, $persistent);
					$this->log($message);
				}
			}
		}
		return $this->_connected;
	}
	
	public function get($key)
	{
		$ret = FALSE;
		if ($this->init()) 
		{
			$ret = $this->_memcache->get($this->_group.$key);
			if ($this->_debug)
			{
				$message = ($ret ? 'hit' : 'miss') . ' ' . $key;
				$this->log($message);
			}
		}
		return $ret;
	}

	public function set($key, $value, $expire)
	{
		if ($this->init()) return $this->_memcache->set($this->_group.$key, $value, $this->_compress, $expire);
		return FALSE;
	}

	public function delete($key, $expire = NULL)
	{
		if ($this->init()) return $this->_memcache->delete($this->_group.$key, $expire);
		return FALSE;
	}

	public function getExtendedStats()
	{
		if ($this->init()) return $this->_memcache->getExtendedStats();
		return FALSE;
	}	
	
	public function log($message)
	{
		if (is_null($this->_logs))
		{
			$this->_logs = array();
		}
		$this->_logs[] = $message;
	}

	public function clean()
	{
		throw new Exception("Not Supported");
	}


}
