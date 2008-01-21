<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QLog 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * 追加日志记录
 *
 * @param string $msg
 * @param string $level
 */
function log_message($msg, $level = 'log', $title = '')
{
    static $instance = null;

    if (is_null($instance)) {
        $instance = Q::getSingleton('QLog');
    }

    return $instance->append($msg, $level, $title);
}

/**
 * Log 类提供基本的日志服务
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.0
 */
class QLog
{
    /**
     * 保存运行期间的日志，在教本结束时将日志内容写入到文件
     *
     * @var string
     */
    protected $_log = '';

    /**
     * 日期格式
     *
     * @var string
     */
    protected $_dateFormat = 'Y-m-d H:i:s';

    /**
     * 保存日志文件的目录
     *
     * @var string
     */
    protected $_filesDir;

    /**
     * 保存日志的文件名
     *
     * @var string
     */
    protected $_filename;

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
        $dir = Q::getIni('_filesDir');
        if (empty($dir)) {
            // 如果没有指定日志存放目录，则保存到内部缓存目录中
            $dir = Q::getIni('internal_cache_dir');
        }
        $dir = rtrim(realpath($dir), '\\/');
        $this->_filesDir = $dir;
        $this->_filename = $this->_filesDir . DIRECTORY_SEPARATOR . Q::getIni('_filename');

        $this->level = array_flip(normalize(strtolower(Q::getIni('log_level'))));

        global $___qee_loaded_time;
        list($usec, $sec) = explode(' ', $___qee_loaded_time);
        $this->_log = sprintf("[%s %s] ======= QeePHP Loaded =======\n", date($this->_dateFormat, $sec), $usec);

        if (isset($_SERVER['REQUEST_URI'])) {
            $this->_log .= sprintf("[%s] REQUEST_URI: %s\n", date($this->_dateFormat), $_SERVER['REQUEST_URI']);
        }

        // 注册脚本结束时要运行的方法，将缓存的日志内容写入文件
        register_shutdown_function(array($this, '__write'));

        // 检查文件是否已经超过指定大小
        if (file_exists($this->_filename)) {
            $filesize = filesize($this->_filename);
        } else {
            $filesize = 0;
        }
        $maxsize = (int)Q::getIni('log_file_maxsize');
        if ($maxsize <= 0) { $maxsize = 512 * 1024; /* 512KB */ }
        if ($maxsize >= 512) {
            $maxsize = $maxsize * 1024;
            if ($filesize >= $maxsize) {
                // 使用新的日志文件名
                $pi = pathinfo($this->_filename);
                $new_filename = $pi['dirname'] . DIRECTORY_SEPARATOR . basename($pi['basename'], '.' . $pi['extension']);
                $new_filename .= date('-Ymd-His') . '.' . $pi['extension'];
                rename($this->_filename, $new_filename);
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
        $level = strtolower($level);
        if (!isset($this->level[$level])) { return; }

        if ($title != '') {
            $format = "[%s] [%s] [%s]: %s\n";
        } else {
            $format = "[%s] [%s]: %s\n";
        }
        $output = str_replace("\n", "\\n", print_r($msg, true));
        $msg = sprintf($format, date($this->_dateFormat), $level, $title, $output);
        $this->_log .= $msg;
    }

    /**
     * 将日志信息写入缓存
     */
    function __write()
    {
        $fp = fopen($this->_filename, 'a');
        if (!$fp) { return; }
        flock($fp, LOCK_EX);
        fwrite($fp, str_replace("\r", '', $this->_log));
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
