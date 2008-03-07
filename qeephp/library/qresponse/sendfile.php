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
 * 定义 QResponse_Sendfile 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QResponse_Sendfile 类输出一个浏览器能够识别的下载文件
 *
 * @package mvc
 */
class QResponse_Sendfile implements QResponse_Interface
{
    /**
     * 所有要输出的内容
     *
     * @var array
     */
    protected $output = array();

    /**
     * 输出文件名
     *
     * @var string
     */
    protected $output_filename;

    /**
     * 输出类型
     *
     * @var string
     */
    protected $mime_type;

    /**
     * 输出文件名的字符集
     *
     * @var string
     */
    protected $filename_charset;

    /**
     * 允许客户端缓存输出的文件
     *
     * @var boolean
     */
    protected $enabled_client_cache = true;

    /**
     * 构造函数
     *
     * @param string $output_filename
     * @param string $mime_type
     * @param string $filename_charset
     */
    function __construct($output_filename, $mime_type = 'application/octet-stream', $filename_charset = 'utf-8')
    {
        $this->output_filename = $output_filename;
        $this->mime_type = $mime_type;
        $this->filename_charset = $filename_charset;
    }

    /**
     * 添加一个要输出的文件
     *
     * @param string $filename
     *
     * @return QResponse_Sendfile
     */
    function addFile($filename)
    {
        $this->output[] = array('file', $filename);
        return $this;
    }

    /**
     * 追加要输出的数据
     *
     * @param string $content
     *
     * @return QResponse_Sendfile
     */
    function appendData($content)
    {
        $this->output[] = array('raw', $content);
        return $this;
    }

    /**
     * 设置输出文件名
     *
     * @param string $output_filename
     *
     * @return QResponse_Sendfile
     */
    function setOutputFilename($output_filename)
    {
        $this->output_filename = $output_filename;
        return $this;
    }

    /**
     * 设置输出文件名的编码
     *
     * @param string $charset
     *
     * @return QResponse_Sendfile
     */
    function setOutputFilenameCharset($charset)
    {
        $this->filename_charset = $charset;
        return $this;
    }

    /**
     * 设置是否允许客户端缓存输出的文件
     *
     * @param boolean $enabled
     *
     * @return QResponse_Sendfile
     */
    function enableClientCache($enabled = true)
    {
        $this->enabled_client_cache = $enabled;
        return $this;
    }

    /**
     * 设置输出类型
     *
     * @param string $mime_type
     *
     * @return QResponse_Sendfile
     */
    function setMimeType($mime_type)
    {
        $this->mime_type = $mime_type;
        return $this;
    }

    /**
     * 执行响应
     */
    function run()
    {
        header("Content-Type: {$this->mime_type}");
        $filename = '"' . htmlspecialchars($this->output_filename) . '"';

        $filesize = 0;
        foreach ($this->output as $output) {
            list($type, $data) = $output;
            if ($type == 'file') {
                $filesize += filesize($data);
            } else {
                $filesize += strlen($data);
            }
        }

        header("Content-Disposition: attachment; filename={$filename}; charset={$this->filename_charset}");
        if ($this->enabled_client_cache) {
            header('Pragma: cache');
        }
        header('Cache-Control: public, must-revalidate, max-age=0');
        header("Content-Length: {$filesize}");

        foreach ($this->output as $output) {
            list($type, $data) = $output;
            if ($type == 'file') {
                readfile($data);
            } else {
                echo $data;
            }
        }
        exit;
    }
}