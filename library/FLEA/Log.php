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
 * 定义 FLEA_Log 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
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
    $obj = FLEA::getSingleton('FLEA_Log');
    /* @var $obj FLEA_Log */
    $obj->appendLog($msg, $level, $title);
}

/**
 * FLEA_Log 类提供基本的日志服务
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Log
{
    /**
     * 日期格式
     *
     * @var string
     */
    public $dateFormat = 'Y-m-d H:i:s';

    /**
     * 保存运行期间的日志，在教本结束时将日志内容写入到文件
     *
     * @var string
     */
    protected $_log = '';

    /**
     * 保存日志文件的目录
     *
     * @var string
     */
    protected $_logFileDir;

    /**
     * 保存日志的文件名
     *
     * @var string
     */
    protected $_logFilename;

    /**
     * 是否允许日志保存
     *
     * @var boolean
     */
    protected $_enabled = true;

    /**
     * 要写入日志文件的错误级别
     *
     * @var array
     */
    protected $_errorLevel;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $dir = FLEA::getAppInf('logFileDir');
        if ($dir == null || $dir == '') {
            // 如果没有指定日志存放目录，则保存到内部缓存目录中
            $dir = FLEA::getAppInf('internalCacheDir');
        }
        $dir = realpath($dir);
        if (!is_dir($dir) || !is_writable($dir)) {
            $this->_enabled = false;
        } else {
            $this->_logFileDir = $dir;
            $this->_logFilename = $this->_logFileDir . DS . FLEA::getAppInf('logFilename');
            $errorLevel = explode(',', strtolower(FLEA::getAppInf('logErrorLevel')));
            $errorLevel = array_map('trim', $errorLevel);
            $errorLevel = array_filter($errorLevel, 'trim');
            $this->_errorLevel = array();
            foreach ($errorLevel as $e) {
               $this->_errorLevel[$e] = true;
            }

            global $___fleaphp_loaded_time;
            list($usec, $sec) = explode(" ", $___fleaphp_loaded_time);
            $this->_log = sprintf("[%s %s] ======= QeePHP Loaded =======\n", date($this->dateFormat, $sec), $usec);

            if (isset($_SERVER['REQUEST_URI'])) {
                $this->_log .= sprintf("[%s] REQUEST_URI: %s\n", date($this->dateFormat), $_SERVER['REQUEST_URI']);
            }

            // 注册脚本结束时要运行的方法，将缓存的日志内容写入文件
            register_shutdown_function(array(& $this, '__writeLog'));

            // 检查文件是否已经超过指定大小
            if (file_exists($this->_logFilename)) {
                $filesize = filesize($this->_logFilename);
            } else {
                $filesize = 0;
            }
            $maxsize = (int)FLEA::getAppInf('logFileMaxSize');
            if ($maxsize >= 512) {
                $maxsize = $maxsize * 1024;
                if ($filesize >= $maxsize) {
                    // 使用新的日志文件名
                    $pathinfo = pathinfo($this->_logFilename);
                    $newFilename = $pathinfo['dirname'] . DS .
                        basename($pathinfo['basename'], '.' . $pathinfo['extension']) .
                        date('-Ymd-His') . '.' . $pathinfo['extension'];
                    rename($this->_logFilename, $newFilename);
                }
            }
        }
    }

    /**
     * 追加日志信息
     *
     * @param string $msg
     * @param string $level
     */
    public function appendLog($msg, $level = 'log', $title = '')
    {
        if (!$this->_enabled) { return; }
        $level = strtolower($level);
        if (!isset($this->_errorLevel[$level])) { return; }

        $msg = sprintf("[%s] [%s] %s:%s", date($this->dateFormat), $level, $title, print_r($msg, true));
        $this->_log .= $msg;
    }

    /**
     * 将日志信息写入缓存
     */
    public function __writeLog()
    {
        global $___fleaphp_loaded_time;

        // 计算应用程序执行时间（不包含入口文件）
        list($usec, $sec) = explode(" ", $___fleaphp_loaded_time);
        $beginTime = (float)$sec + (float)$usec;
        $endTime = microtime();
        list($usec, $sec) = explode(" ", $endTime);
        $endTime = (float)$sec + (float)$usec;
        $elapsedTime = $endTime - $beginTime;
        $this->_log .= sprintf("[%s %s] ======= QeePHP End (elapsed: %f seconds) =======\n\n", date($this->dateFormat, $sec), $usec, $elapsedTime);

        $fp = fopen($this->_logFilename, 'a');
        if (!$fp) { return; }
        flock($fp, LOCK_EX);
        fwrite($fp, str_replace("\r", '', $this->_log));
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
