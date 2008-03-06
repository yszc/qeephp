<?php

/**
 * Default controller
 *
 * @package app
 */
class Controller_Default extends QController_Abstract
{
    /**
     * default action
     *
     * @return QResponse_Interface
     */
    function actionIndex()
    {
        return new QResponse_Render();
    }
}
