<?php

/**
 * QException 是 QeePHP 的异常基础类
 *
 * @copyright Copyright (c) 2005 - 2006 FleaPHP.org (www.qee.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package core
 * @version $Id$
 */
class QException extends Exception
{
    function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    static function dump(Exception $ex)
    {
        $out = "exception '" . get_class($ex) . "'";
        if ($ex->getMessage() != '') {
            $out .= " with message '" . $ex->getMessage() . "'";
        }
        if (defined('DEPLOY_MODE') && DEPLOY_MODE != false) {
            $out .= ' in ' . basename($ex->getFile()) . ':' . $ex->getLine() . "\n\n";
        } else {
            $out .= ' in ' . $ex->getFile() . ':' . $ex->getLine() . "\n\n";
            $out .= $ex->getTraceAsString();
        }

        if (ini_get('html_errors')) {
            echo nl2br(htmlspecialchars($out));
        } else {
            echo $out;
        }
    }
}

