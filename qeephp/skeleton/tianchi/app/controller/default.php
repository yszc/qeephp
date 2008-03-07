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
     */
    function actionIndex()
    {
        return $this->getView();
    }
}
