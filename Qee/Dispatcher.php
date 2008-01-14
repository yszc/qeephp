<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2005 - 2007 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 Qee_Dispatcher类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

/**
 * Qee_Dispatcher 分析 HTTP 请求，并转发到合适的 Controller 对象处理
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class Qee_Dispatcher
{
	// Base URL
	protected $_base = null;
	// 访问参数数组(含_GET内容)
	protected $_params = array();
	// 提供给控制器的参数
	protected $_vars = array();
	
	// 提供用户认证服务的接口实现类(Auth_interface)
	protected $_auth;
	// string
	protected $_controller;
	// string
	protected $_action;

	/**
	 * 构造函数
	 *
	 *
	 * @return Dispatcher
	 */
	public function __construct()
	{
		//$this->_auth = & AuthFactory::getAuth();
		$this->_base = Qee::getAppInf('urlBase');
		$url = $_SERVER['REQUEST_URI'];
		$urlMode = Qee::getAppInf('urlMode');
		$data = $this->parseMode($url, $urlMode);

		$this->_controller = $data['controller'];
		$this->_action = $data['action'];
		$this->_params = &$data['params'];
		$this->_vars = &$data['vars'];
	}

	/**
	 * function description
	 * 
	 * @param
	 * @return void
	 */
	public function forward($controller, $action = null, $args = array())
	{
		$this->_controller = $controller;
		$this->_action = $action;
		$this->_vars = $args;
		return $this->dispatch();
	}
	
	/*
	 * 分派请求 
	 *
	 */
	public function dispatch()
	{
		$data = '';
		$cache_pages_items = Qee::getAppInf('cached_pages_items');
		if(isset($cache_pages_items[$this->_controller.'.'.$this->_action]))
		{
			$lifetime = $cache_pages_items[$this->_controller.'.'.$this->_action];
			$cached_key = sprintf("%s.%s_%s", $this->_controller, $this->_action, @implode("_", $this->_vars ));
			$cache_selotion = Cache::getSolution('cached_pages_options');
			if(($data = $cache_selotion->get($cached_key)) === FALSE )
			{
				$data = $this->execute();
				$cache_selotion->set($cached_key, $data, $lifetime);
			}
		}
		else
		{
			$data = $this->execute();
		}
		echo $data;
		

	}

	/**
	 * function description
	 * 
	 * @param
	 * @return void
	 */
	protected function &execute()
	{
		$controller = ucfirst($this->_controller);
		$action = ucfirst($this->_action);
		
		//加上控制器前辍
		$controller = Qee::getAppInf("controllerPrefix") . $controller;
		//$file = Qee::getAppInf("controllerPath") . DS . $controller . ".php";

		Qee::loadClass($controller);
		if(!class_exists($controller)) {
			throw new Exception("请求的控制器 <span class='err'>$file::$controller</span> 不存在。");
		}

		$ctl = new $controller($controller);
		$ctl->setDispatcher($this);
		//$ctl->setParams($this->_params);

		$actionPrefix = Qee::getAppInf('actionMethodPrefix');
        if ($actionPrefix != '') { $actionMethod = $actionPrefix . ucfirst($action); }
		else $actionMethod = $action;

		//执行action
		if(method_exists($ctl, $actionPrefix . $action) )
		{
		}
		elseif(method_exists($ctl, '__call'))
		{
			$actionMethod = $action;
		}
		else
		{
			throw new Exception("请求的控制器 <span class='err'>$file::$controller</span> 不存在动作 <span class='err'>$actionMethod</span>。");
			
		}

        ob_start();
        ob_implicit_flush(false);

		$out = call_user_func_array(array($ctl, $actionMethod), $this->_vars);
		
        $data = ob_get_contents();
        ob_end_clean();
		return $data;
	}
	
	/**
	 * function description
	 * 
	 * @param
	 * @return void
	 */
	public function &parseUrl($url, $base = '')
	{
		$parts = parse_url($url);
		
		if (!$parts || empty($parts['path']))
		{
			return FALSE;
		}
		if (!empty($_SERVER['PATH_INFO']) )
		{
			$parts['pathinfo'] = $_SERVER['PATH_INFO'];
			if ($_SERVER['PATH_INFO'] == '/')
			{
				$base = $parts['path'];
			}
			else
			{
				$pos = strpos($url, $_SERVER['PATH_INFO']);
				if ($pos !== false)
				{
					$base = substr($parts['path'], 0, $pos);
				}
				elseif(isset($_SERVER['SCRIPT_NAME'])) $base = $_SERVER['SCRIPT_NAME'];
			}
		}
		else
		{
			$parts['pathinfo'] = empty($base)?$parts['path']:substr($parts['path'], strlen($base)-1);
		}

		parse_str($parts['query'], $params);
		$parts['params'] = array_merge($_GET, $params);

		$parts['base'] = $base;
		return $parts;
	}

	/*
	 * 分析URL模式
	 */
	public function &parseMode($url, $urlMode = null)
	{
		$request = $this->parseUrl($url, $this->_base);
		extract($request);
		$this->_base = $base;
		$defaultController = Qee::getAppInf("defaultController");
		$defaultAction = Qee::getAppInf("defaultAction");
		$data = array('controller' => $defaultController, 'action' => $defaultAction);
		switch($urlMode)
		{
			//pathInfo模式
			case URL_PATHINFO:
				if(empty($_SERVER['PATH_INFO'])) {
					throw new Exception("_SERVER['PATH_INFO'] not found");
				}
			case URL_REWRITE:
			case URL_ROUTER:
				$parts = explode('/', trim($pathinfo, '/'));
				isset($parts[0]) && !empty($parts[0]) && $data['controller'] =  $parts[0];
				isset($parts[1]) && !empty($parts[1]) && $data['action']	 =  $parts[1];
				$data['vars'] = array_slice($parts, 2);
				break;
			case URL_STANDARD:
			default:
				$ctl = Qee::getAppInf('controllerAccessor');
				$act = Qee::getAppInf('actionAccessor');
				$data['controller'] = !empty($_REQUEST[$ctl]) ? $_REQUEST[$ctl] : $defaultController;
				$data['action']	 = !empty($_REQUEST[$act]) ? $_REQUEST[$act] : $defaultAction;
				if(isset($_GET['_pi'])) $data['vars'] = explode('/', trim($_GET['_pi'], '/'));
				break;
		}
		
		$data['params'] = & $params;

		return $data;
	}

	/*
	 * 指派控制器
	 */
	public function setController($controller)
	{
		$this->_controller = $controller;
	}

	/*
	 * 指派动作
	 */
	public function setAction($action)
	{
		$this->_action = $action;
	}

	/*
	 * 取得控制器的名称 
	 */
	public function getController()
	{
		return $this->_controller;

	}

	/*
	 * 取得动作的名称 
	 */
	public function getAction()
	{
		return $this->_action;
	}

	/**
	 * 返回当前使用的验证服务对象
	 *
	 * @return Auth_interface
	 */
    function & getAuth()
    {
        return $this->_auth;
    }

	/**
	 * 设置要使用的验证服务对象
	 *
	 * @param Auth_interface $auth
	 */
    function setAuth($auth)
    {
        $this->_auth =& $auth;
    }

	/**
	 * 根据控制器等信息生成URL
	 * 未完善（目前只支持 Pathinfo  格式和 Rewrite，需要添加针对Router的处理）
	 * 
	 * @param
	 * @return void
	 */
	public function url($controllerName = null, $actionName = null, $params = null, $anchor = null)
	{
		$out = $this->_base . '/' . $controllerName . '/' . $actionName;
		!empty($params) && $out .= '/' . (is_array($params)? implode('/', $params) : $params);
		!empty($anchor) && $out .= '#' . $anchor;
		return $out;
	}
}
