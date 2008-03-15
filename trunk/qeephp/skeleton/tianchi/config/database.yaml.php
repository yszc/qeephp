# <?php die(); ?>

#############################
# 数据库设置
#############################

# devel 模式
devel:
    driver:     mysql
    host:       localhost
    login:      username
    password:   password
    database:   %APP_NAME%_devel
    charset:    utf8

# test 模式
test:
    driver:     mysql
    host:       localhost
    login:      username
    password:   password
    database:   %APP_NAME%_test
    charset:    utf8

# deploy 模式
deploy:
    driver:     mysql
    host:       localhost
    login:      username
    password:   password
    database:   %APP_NAME%_db
    charset:    utf8
