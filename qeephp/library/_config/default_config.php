<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * QeePHP 默认设置
 *
 * @package Config
 * @version $Id$
 */

return array(
    // {{{ 核心配置

    /**
     * 指示控制器的 url 参数名和默认控制器名
     *
     * 控制器名字只能是a-z字母和0-9数字，以及“_”下划线。
     */
    'controller_accessor'       => 'controller',
    'default_controller'        => 'default',

    /**
     * 指示 动作方法的 url 参数名和默认 动作方法名
     */
    'action_accessor'           => 'action',
    'default_action'            => 'index',

    /**
     * 指示命名空间的 url 参数名和默认命名空间
     */
    'namespace_accessor'        => 'namespace',
    'default_namespace'         => null,

    /**
     * 指示模块的 url 参数名和默认模块名
     */
    'module_accessor'           => 'module',
    'default_module'            => null,

    /**
     * 当无权访问请求的动作时要调用的处理例程
     */
    'on_access_denied'          => null,

    /**
     * 当请求的动作不存在时要调用的处理例程
     */
    'on_action_not_found'       => null,

    /**
     * 指示要使用的请求对象
     */
    'request_class'             => 'QRequest',

    /**
     * 是否启动session_start，对负载有影响，建议关闭。如果需要验证等功能，请使用cookie代替
     */
    'auto_session'              => false,

    /**
     * 处理请求时要使用的验证服务提供程序
     */
    'request_acl_class'         => 'QACL',

    /**
     * 指示当没有为控制器提供 ACT 时，要使用的默认 ACT
     */
    'default_act'               => array('allow' => 'acl_everyone'),

    /**
     * 全局 ACT，当没有指定 ACT 时则从全局 ACT 中查找指定控制器的 ACT
     */
    'global_act'                => null,

    /**
     * 指示 ACL 组件用什么键名在 session 中保存用户数据
     *
     * 如果在一个域名下同时运行多个应用程序，
     * 请务必为每一个应用程序使用自己独一无二的键名
     */
    'acl_session_key'           => 'acl_userdata',

    /**
     * 指示 ACL 组件用什么键名在 session 中保存用户角色信息
     */
    'acl_roles_key'             => 'acl_roles',

    /**
     * url 参数的传递模式，可以是标准、PATHINFO、URL 重写等模式
     */
    'url_mode'                  => 'standard',

    /**
     * 指示默认的应用程序入口文件名
     */
    'url_bootstrap'             => 'index.php',

    /**
     * QeePHP 内部及 cache 系列函数使用的缓存目录
     * 应用程序必须设置该选项才能使用 cache 功能。
     */
    'internal_cache_dir'        => null,

    /**
     * 默认使用的缓存服务
     */
    'default_cache_backend'     => 'QCache_File',

    /**
     * 默认的时区设置
     */
    'default_timezone'          => 'Asia/Shanghai',

    /**
     * 第三方库的保存目录
     */
    'vendor_ext_dir'       => array(Q_DIR . DS . '_vendor'),

    // }}}

    // {{{ 数据库相关

    /**
     * 数据库连接设置
     */
    'dsn'                       => null,

    /**
     * 数据表元数据缓存时间（秒），如果 db_meta_cached 设置为 false，则不会缓存数据表元数据
     * 通常开发时，该设置为 10，以便修改数据库表结构后应用程序能够立刻刷新元数据
     */
    'db_meta_lifetime'          => 0,

    /**
     * 指示是否缓存数据表的元数据
     */
    'db_meta_cached'            => true,

    /**
     * 缓存元数据使用的缓存服务
     */
    'db_meta_cache_backend'     => 'QCache_File',

    // }}}

    // {{{ View 相关

    /**
     * 要使用的模板引擎，'PHP' 表示使用 PHP 语言本身作模板引擎
     */
    'view_adapter'               => 'QView_Adapter_Gingko',

    /**
     * 模板引擎要使用的配置信息
     */
    'view_config'               => null,

    /**
     * QWebControls 扩展控件的保存目录
     */
    'webcontrols_ext_dir'       => array(Q_DIR . '/_webcontrols'),

    // }}}

    // {{{ I18N

    /**
     * 指示 QeePHP 应用程序内部处理数据和输出内容要使用的编码
     */
    'response_charset'          => 'utf-8',

    /**
     * 是否自动输出 Content-Type: text/html; charset=response_charset
     */
    'auto_response_header'      => true,

    /**
     * 指示是否启用多语言支持
     */
    'multi_languages'           => false,

    // }}}

    // {{{ 日志和错误处理
    /**
     * 指示是否启用日志服务
     */
    'log_enabled'               => true,

    /**
     * 指示日志服务的程序
     */
    'log_provider'              => 'QLog',

    /**
     * 指示用什么目录保存日志文件
     *
     * 如果没有指定日志存放目录，则保存到内部缓存目录中
     */
    'log_files_dir'             => null,

    /**
     * 指示用什么文件名保存日志
     */
    'log_filename'              => 'access.log',

    /**
     * 指示当日志文件超过多少 KB 时，自动创建新的日志文件，单位是 KB，不能小于 512KB
     */
    'log_file_maxsize'          => 4096,

    /**
     * 指示哪些级别的错误要保存到日志中
     */
    'log_level'                 => 'notice, debug, warning, error, exception, log',

    /**
     * 指示是否显示错误信息
     */
    'display_errors'            => true,

    /**
     * 指示是否显示友好的错误信息
     */
    'friendly_errors'           => true,

    /**
     * 指示是否在错误信息中显示出错位置的源代码
     */
    'display_source'            => true,

);
