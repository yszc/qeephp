# <?php die(); ?>

#############################
# 核心设置
#############################

# 控制器的 url 参数名和默认控制器名
# 控制器名字只能是 a-z 字母和 0-9 数字，以及“_”下划线。
controller_accessor:    controller
default_controller:     default

# 动作方法的 url 参数名和默认动作方法名，动作方法名只能是 a-z 字母
action_accessor:        action
default_action:         index

# 要使用的 MVC 调度器
dispatcher:             QDispatcher

# 当用户没有权限访问特定 URL 时，要调用的错误处理函数
on_access_denied:       on_access_denied

# 当用户访问的 URL 不存在时，要调用的错误处理函数
on_action_not_found:    on_page_not_found

# url 参数的传递模式，可以是 standard, pathinfo, 和 rewrite 模式
# 只有在 url_mode 为 pathinfo 或 rewrite 时，routes.yaml 指定的路由才能生效
# 如果使用 IIS，有可能需要将 url_mode 改为 standard 才能正常运行
url_mode:               pathinfo

# 默认的应用程序入口文件名
url_bootstrap:          index.php

# 在生成 url 时，是否总是使用应用程序入口文件名，仅在 url_mode 为 'standard' 时生效
#
# 如果该设置为 false，则生成的 url 类似：
#
# http://www.example.com/?controller=xxx&action=yyy
url_always_use_bootstrap: true

# QeePHP 内部及 cache 系列函数使用的缓存目录
# 应用程序必须设置该选项才能使用 cache 功能。
internal_cache_dir:     %ROOT_DIR%/temp/internal

# 默认使用的缓存服务
default_cache_backend:  QCache_File

# 默认的时区设置
default_timezone:       Asia/Shanghai


#############################
# 视图相关设置
#############################

# 要使用的模板引擎，PHP 表示使用 PHP 语言本身作模板引擎
view_adapter:           QView_Adapter_Gingko

# 模板引擎所需的配置
view_config:
    template_dir:       %ROOT_DIR%/app/view

# QWebControls 扩展控件的保存目录
webcontrols_ext_dir:
    - %ROOT_DIR%/app/webcontrols

# 指示 QeePHP 应用程序内部处理数据和输出内容要使用的编码
response_charset:       utf-8

# 是否自动输出 Content-Type: text/html; charset=response_charset
auto_response_header:   true

# 指示是否启用多语言支持
multi_languages:        false

# 是否启动session_start，对负载有影响，建议关闭。如果需要验证等功能，请使用cookie代替
auto_session:           false


#############################
# 访问控制
#############################

# 调度器要使用的验证服务提供程序
dispatcher_acl_provider: QACL

# 指示当没有为控制器提供 ACT 时，要使用的默认 ACT
default_act:
    allow: acl_everyone

# 指示 ACL 组件用什么键名在 session 中保存用户数据
#
# 如果在一个域名下同时运行多个应用程序，
# 请务必为每一个应用程序使用自己独一无二的键名
acl_session_key:        acl_userdata

# 指示 ACL 组件用什么键名在 session 中保存用户角色信息
acl_roles_key:          acl_roles


#############################
# 应用程序设置
#############################

# 在这里添加应用程序需要的设置