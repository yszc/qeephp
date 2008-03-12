<?php

/**
 * 默认控制器
 *
 * @package app
 */
class Controller_Default extends QController_Abstract
{
    /**
     * 当前控制器要使用的助手
     *
     * @var array|string
     */
    public $helpers = 'url';

    /**
     * default action
     */
    function actionIndex()
    {
        /**
         * 要传递到视图的数据，可以直接赋值给 $this->view。
         *
         * 为了便于在视图中使用这些数据，$this->view 应该是一个数组，键名对应视图中的变量名。
         */
        $this->view = array('text' => '<Hello, PHPer!>');
    }
}
