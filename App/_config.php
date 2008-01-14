<?PHP

//默认应用程序各文件夹路径设置
if(!defined('APP_ROOT')) define('APP_ROOT', dirname(__FILE__));

return array(

	// cache
	'cached_pages_items'  			=> array
	(
		// 格式: Controller.Action
		'home.index' => 60*45, // 45 分钟
		'test.list' => 60*25, // 25 分钟
		'test.view' => 60*15, // 15 分钟
	),
	'cached_pages_options'  		=> array(
		'debug' => false,
		'enabled' => true,
		'engine' => 'CacheLite',
		'group' => 'mytest',
		'cachedir' => '/www/cache',
		'lifetime' => 60*60,
		'fileNameProtection' => FALSE,
		'hashedDirectoryLevel' => 2,
	),

	// displayErrors
	'displayErrors' 		=> TRUE,
	// 提供用户认证的程序
	//'authProvider' 				=> 'My_Auth',
	//控制器的名称
    'controllerAccessor'        => 'ctl',
	//动作的名称
    'actionAccessor'            => 'act',
	//默认的控制器
	'defaultController'			=> 'home',
	//默认的动作
	'defaultAction'				=> 'index',	
	//定义控制器前缀
	'controllerPrefix'			=> 'C_',
	//动作前辍
	'actionMethodPrefix'		=> 'action',
	// 设置URL Mode
    'urlMode'                   => URL_PATHINFO,
	/**
	 * 应用程序要使用的 url 调度器
	 */
    'dispatcher'                => 'Qee_Dispatcher',

    'viewEngine' => 'View_Smarty',
    'viewConfig' => array(
        'root'         => APP_ROOT . '/',
        'tplrefresh'         => 1,
        'template_dir'      => APP_ROOT . '/templates',
        'compile_dir'       => APP_ROOT . '/templates_c',
    ),

	//数据表前缀
	'tablePrefix'		=> 'bbt_',
	
	//数据库连接DSN设置
	'forums'				=> array(
		'dsn_posts' 	=> 'mysqli://bbsur:PA7SKCVzmQwSR7Ku@cbkdb/bbst?debug=1',
		'dsn_forums' 	=> 'mysqli://bbsur:PA7SKCVzmQwSR7Ku@cbkdb/bbst?debug=1'
	)
	

);