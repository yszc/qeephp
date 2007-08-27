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
 * 定义 FLEA_Helper_FileUploader 和 FLEA_Helper_UploadFile 对象
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

/**
 * FLEA_Helper_FileUploader 实现了一个简单的、可扩展的文件上传助手
 *
 * 使用方法：
 *
 * <code>
 * $allowExts = 'jpg,png,gif';
 * $maxSize = 150 * 1024; // 150KB
 * $uploadDir = dirname(__FILE__) . '/upload';
 *
 * FLEA::loadClass('FLEA_Helper_FileUploader');
 * $uploader = new FLEA_Helper_FileUploader();
 * $files = $uploader->getFiles();
 * foreach ($files as $file) {
 *     if (!$file->check($allowExts, $maxSize)) {
 *         // 上传的文件类型不符或者超过了大小限制。
 *         return false;
 *     }
 *     // 生成唯一的文件名（重复的可能性极小）
 *     $id = md5(time() . $file->getFilename() . $file->getSize() . $file->getTmpName());
 *     $filename = $id . '.' . strtolower($file->getExt());
 *     $file->move($uploadDir . '/' . $filename);
 * }
 * </code>
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Helper_FileUploader
{
    /**
     * 所有的 UploadFile 对象实例
     *
     * @var array
     */
    protected $_files = array();

    /**
     * 可用的上传文件对象数量
     *
     * @var int
     */
    protected $_count;

    /**
     * 构造函数
     */
    public function __construct()
    {
        if (is_array($_FILES)) {
            foreach ($_FILES as $field => $struct) {
                if (isset($struct['error']) && $struct['error'] != UPLOAD_ERR_NO_FILE) {
                    $this->_files[$field] = new FLEA_Helper_UploadFile($struct, $field);
                }
            }
        }
        $this->_count = count($this->_files);
    }

    /**
     * 可用的上传文件对象数量
     *
     * @return int
     */
    public function getCount()
    {
        return $this->_count;
    }

    /**
     * 返回所有的上传文件对象
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->_files;
    }

    /**
     * 检查指定名字的上传文件对象是否存在
     *
     * @param string $name
     *
     * @return boolean
     */
    public function existsFile($name)
    {
        return isset($this->_files[$name]);
    }

    /**
     * 返回指定名字的上传文件对象
     *
     * @param string $name
     *
     * @return FLEA_Helper_UploadFile
     */
    public function getFile($name)
    {
        if (!isset($this->_files[$name])) {
            require_once 'FLEA/Exception/ExpectedFile.php';
            throw new FLEA_Exception_ExpectedFile('$_FILES[' . $name . ']');
        }
        return $this->_files[$name];
    }

    /**
     * 检查指定的上传文件是否存在
     *
     * @param string $name
     *
     * @return boolean
     */
    public function isFileExist($name)
    {
        return isset($this->_files[$name]);
    }

    /**
     * 批量移动上传的文件到目标目录
     *
     * @param string $destDir
     */
    public function batchMove($destDir)
    {
        foreach ($this->_files as $file) {
            /* @var $file FLEA_Helper_UploadFile */
            $file->move($destDir . '/' . $file->getFilename());
        }
    }
}

/**
 * 封装一个上传的文件
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Helper_UploadFile
{
    /**
     * 上传文件信息
     *
     * @var array
     */
    protected $_file = array();

    /**
     * 上传文件对象的名字
     *
     * @var string
     */
    protected $_name;

    /**
     * 构造函数
     *
     * @param array $struct
     * @param string $name
     *
     * @return FLEA_Helper_UploadFile
     */
    public function __construct($struct, $name)
    {
        $this->_file = $struct;
        $this->_file['is_moved'] = false;
        $this->_name = $name;
    }

    /**
     * 设置自定义属性
     *
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute($name, $value)
    {
        $this->_file[$name] = $value;
    }

    /**
     * 获取自定义属性
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        return $this->_file[$name];
    }

    /**
     * 返回上传文件对象的名字
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * 指示上传是否成功
     *
     * @return boolean
     */
    public function isSuccessed()
    {
        return $this->_file['error'] == UPLOAD_ERR_OK;
    }

    /**
     * 返回上传错误代码
     *
     * @return int
     */
    public function getError()
    {
        return $this->_file['error'];
    }

    /**
     * 指示上传文件是否已经从临时目录移出
     *
     * @return boolean
     */
    public function isMoved()
    {
        return $this->_file['is_moved'];
    }

    /**
     * 返回上传文件的原名
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->_file['name'];
    }

    /**
     * 返回上传文件不带"."的扩展名
     *
     * @return string
     */
    public function getExt()
    {
        if ($this->isMoved()) {
            return pathinfo($this->getNewPath(), PATHINFO_EXTENSION);
        } else {
            return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
        }
    }

    /**
     * 返回上传文件的大小（字节数）
     *
     * @return int
     */
    public function getSize()
    {
        return $this->_file['size'];
    }

    /**
     * 返回上传文件的 MIME 类型（由浏览器提供，不可信）
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->_file['type'];
    }

    /**
     * 返回上传文件的临时文件名
     *
     * @return string
     */
    public function getTmpName()
    {
        return $this->_file['tmp_name'];
    }

    /**
     * 获得文件的新路径（通常是移动后的新路径，包括文件名）
     *
     * @return string
     */
    public function getNewPath()
    {
        return $this->_file['new_path'];
    }

    /**
     * 检查上传的文件是否成功上传，并符合检查条件（文件类型、最大尺寸）
     *
     * 文件类型以扩展名为准，多个扩展名以 , 分割，例如 .jpg,.jpeg,.png。
     *
     * @param string $allowExts 允许的扩展名
     * @param int $maxSize 允许的最大上传字节数
     *
     * @return boolean
     */
    public function check($allowExts = null, $maxSize = null)
    {
        if (!$this->isSuccessed()) { return false; }

        if ($allowExts) {
            if (strpos($allowExts, ',')) {
                $exts = explode(',', $allowExts);
            } elseif (strpos($allowExts, '/')) {
                $exts = explode('/', $allowExts);
            } elseif (strpos($allowExts, '|')) {
                $exts = explode('|', $allowExts);
            } else {
                $exts = array($allowExts);
            }

            $fileExt = strtolower($this->getExt());
            $passed = false;
            $exts = array_filter(array_map('trim', $exts), 'trim');
            foreach ($exts as $ext) {
                if (substr($ext, 0, 1) == '.') {
                    $ext = substr($ext, 1);
                }
                if ($fileExt == strtolower($ext)) {
                    $passed = true;
                    break;
                }
            }
            if (!$passed) {
                return false;
            }
        }

        if ($maxSize && $this->getSize() > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * 移动上传文件到指定位置和文件名
     *
     * @param string $destPath
     */
    public function move($destPath)
    {
        $this->_file['is_moved'] = true;
        $this->_file['new_path'] = $destPath;
        return move_uploaded_file($this->_file['tmp_name'], $destPath);
    }

    /**
     * 删除移动后的文件
     */
    public function removeMovedFile()
    {
        if ($this->_file['is_moved']) {
            unlink($this->_file['new_path']);
        }
    }
}
