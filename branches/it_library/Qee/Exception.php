<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 Qee_Exception 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

/**
 * Qee_Exception 是 QeePHP 的异常基础类
 *
 * @copyright Copyright (c) 2005 - 2006 FleaPHP.org (www.fleaphp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */
class Qee_Exception extends Exception
{
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * 将错误信息翻译为当前设置的语言
     *
     * @package Core
     *
     * @param string $msg
     *
     * @return string
     */
    static public function t($msg)
    {
        static $message = array();

        if (class_exists('Qee')) {
            $language = preg_replace('/[^a-z0-9\-_]+/i', '', Qee::getAppInf('defaultLanguage'));
            if (!isset($message[$language])) {
                //$message[$language] = include "/_Errors/{$language}/ErrorMessage.php";
            }
        } else {
            $language = 0;
        }

        $args = func_get_args();
        array_shift($args);
        if (isset($message[$language][$msg])) {
            $msg = $message[$language][$msg];
        }

        array_unshift($args, $msg);
        return call_user_func_array('sprintf', $args);
    }

    /**
     * 打印异常的详细信息
     *
     * @param Exception $ex
     * @param boolean $return 为 true 时返回输出信息，而不是直接显示
     */
    static public function printEx(Exception $ex, $return = false)
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

        if ($return) { return $out; }

        if (ini_get('html_errors')) {
            echo nl2br(htmlspecialchars($out));
        } else {
            echo $out;
        }

        return '';
    }
}
