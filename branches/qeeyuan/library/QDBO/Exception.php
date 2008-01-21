<?php

class QDBO_Exception extends QException
{
    public $sql;

    function __construct($sql, $error, $errcode)
    {
        $this->sql = $sql;
        parent::__construct($error, $errcode);
    }
}
