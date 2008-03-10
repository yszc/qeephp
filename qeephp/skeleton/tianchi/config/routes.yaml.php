# <?php die(); ?>

#############################
# 路由设置
#############################

# 特定的路由规则
#
# 这个路由规则实际看起来类似 http://www.example.com/articles-live-12423.html
# 也就是访问名为 articles 的控制器，动作为 view。live 映射为 category 参数，而 12423 映射为 id 参数
# 最后的 html 映射为 format 参数。
#
# :string 代表字符串参数
# :int 代表证书参数
#
# $1 代表整个路由规则中的第一个参数，$2 以此类推
#
# articles-:string-:int.:string
#   controller:     articles
#   action:         view
#   category:       $1
#   id:             $2
#   format:         $3
#


# 某个模块的路由
#
# module 明确控制器所属模块
#
# :controller、:id、:format 和 :action 都是预定义变量，分别到表资源、ID、格式和动作。
#
#
#
# admin/:controller/:id.:format/:action
#    module: admin
#


# 默认的路由规则
#
# 如果 :id 省略，则假定访问 :controller 控制器的 index 方法
# 如果 :format 省略，则假定 :format 为 html
# 如果 :action 省略，则假定 :action 为 view
#
# :controller/:id.:format/:action
#


# 针对根目录的默认路由
#
# 根目录既  http://www.example.com/
#
# 如果没有指定根目录对应的控制器和动作，则假定访问 default 控制器的 index 方法
#
# root:
#   controller: welcome
#