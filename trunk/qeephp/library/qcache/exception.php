<?php

class QCache_Exception extends QException
{
    public $filename;

    function __construct($msg, $filename = null)
    {
        $this->filename = $filename;
        parent::__construct(__($msg, $filename));
    }
}
