<?php

require Q_DIR . '/qapplication/abstract.php';

/**
 * 应用程序对象
 *
 * @package app
 */
class App extends QApplication_Abstract
{
    /**
     * 获得应用程序对象的唯一实例
     *
     * @return QApplication_Abstract
     */
    static function instanc()
    {
        static $app;
        if (is_null($app)) {
            $app = new App();
        }
        return $app;
    }

    /**
     * 默认的 on_access_denied 事件处理函数
     */
    function onAccessDenied($controller_name, $action_name, $namespace = null, $module = null)
    {
        require ROOT_DIR . '/public/403.php';
    }

    /**
     * 默认的 on_action_not_found 事件处理函数
     */
    function onActionNotFound($controller_name, $action_name, $namespace = null, $module = null)
    {
        require ROOT_DIR . '/public/404.php';
    }

    /**
     * 默认的异常处理
     */
    function exceptionHandler(Exception $ex)
    {
        QException::dump($ex);
    }
}
