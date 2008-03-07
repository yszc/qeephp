<?php

/**
 * Default controller
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
    protected $helpers = '';

    /**
     * default action
     */
    function actionIndex()
    {
        return array(
            'text' => '<Hello, PHPer!>',
        );
    }
}
