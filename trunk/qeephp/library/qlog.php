<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QLog 类
 *
 * @package core
 * @version $Id$
 */

// {{{ functions

/**
 * 记录一条日志的全局函数
 *
 * @param string $msg
 * @param string $level
 * @param string $title
 */
function log_message($msg, $level = 'log', $title = '')
{
    static $log;
    if (is_null($log)) {
        $log = Q::getSingleton('QLog');
    }
    /* @var $log QLog */
    $log->append($msg, $level, $title);
}

// }}}

/**
 * QLog 类提供基本的日志服务
 *
 * @package core
 */
class QLog
{
    /**
     * 保存运行期间的日志，在教本结束时将日志内容写入到文件
     *
     * @var string
     */
    protected $log = '';

    /**
     * 指示是否需要写入日志文件
     *
     * @var boolean
     */
    protected $need_write = false;

    /**
     * 日期格式
     *
     * @var string
     */
    protected $date_format = 'Y-m-d H:i:s';

    /**
     * 保存日志文件的目录
     *
     * @var string
     */
    protected $files_dir;

    /**
     * 保存日志的文件名
     *
     * @var string
     */
    protected $filename;

    /**
     * 要写入日志文件的错误级别
     *
     * @var array
     */
    protected $level;

    /**
     * 构造函数
     *
     * @return Log
     */
    function __construct()
    {
        $dir = Q::getIni('log_files_dir');
        if (empty($dir)) {
            // 如果没有指定日志存放目录，则保存到内部缓存目录中
            $dir = Q::getIni('internal_cache_dir');
        }
        $dir = rtrim(realpath($dir), '\\/');
        $this->files_dir = $dir;
        $filename = Q::getIni('log_filename');
        if(empty($filename)) $filename = 'qee.log';
        $this->filename = $this->files_dir . DS . $filename;

        $this->level = array_flip(Q::normalize(strtolower(Q::getIni('log_level'))));

        if (isset($_SERVER['REQUEST_URI'])) {
            $this->log .= sprintf("[%s] REQUEST_URI: %s\n", date($this->date_format), $_SERVER['REQUEST_URI']);
        }

        // 注册脚本结束时要运行的方法，将缓存的日志内容写入文件
        register_shutdown_function(array($this, '__write'));

        // 检查文件是否已经超过指定大小
        if (file_exists($this->filename)) {
            $filesize = filesize($this->filename);
        } else {
            $filesize = 0;
        }
        $maxsize = (int)Q::getIni('log_file_maxsize');
        if ($maxsize <= 0) { $maxsize = 512 * 1024; /* 512KB */ }
        if ($maxsize >= 512) {
            $maxsize = $maxsize * 1024;
            if ($filesize >= $maxsize) {
                // 使用新的日志文件名
                $pi = pathinfo($this->filename);
                $new_filename = $pi['dirname'] . DS . basename($pi['basename'], '.' . $pi['extension']);
                $new_filename .= date('-Ymd-His') . '.' . $pi['extension'];
                rename($this->filename, $new_filename);
            }
        }
    }

    /**
     * 追加日志信息
     *
     * @param string $msg
     * @param string $level
     */
    function append($msg, $level = 'log', $title = '')
    {
        if (!isset($this->level[$level])) { return; }

        $output = str_replace("\n", "\\n", print_r($msg, true));
        if ($title != '') {
            $format = "[%s] [%s] [%s]: %s\n";
            $msg = sprintf($format, date($this->date_format), $level, $title, $output);
        } else {
            $format = "[%s] [%s]: %s\n";
            $msg = sprintf($format, date($this->date_format), $level, $output);
        }
        $this->log .= $msg;
        $this->need_write = true;
    }

    /**
     * 将日志信息写入缓存
     */
    function __write()
    {
        if (!$this->need_write) { return; }
        $fp = fopen($this->filename, 'a');
        if (!$fp) { return; }
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $this->log);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}
