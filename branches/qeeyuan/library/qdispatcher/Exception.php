<?php

class QDispatcher_Exception extends QException
{
    public $controller_name;
    public $action_name;

    function __construct($msg, $controller_name, $action_name)
    {
        $this->controller_name = $controller_name;
        $this->action_name = $action_name;
        parent::__construct(__(func_get_args()));
    }
}
