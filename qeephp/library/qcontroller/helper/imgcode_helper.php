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
 * 定义 Helper_Imgcode
 *
 * @package helper
 * @version $Id$
 */

/**
 * Helper_Imgcode 用于生成图像验证码
 *
 * @package helper
 */
class Helper_Imgcode
{
    /**
     * 生成的验证码
     *
     * @var string
     */
    public $code;

    /**
     * 验证码过期时间
     *
     * @var string
     */
    public $expired;

    /**
     * 验证码图片的类型（默认为 jpeg）
     *
     * @var string
     */
    public $imagetype = 'jpeg';

    /**
     * 指示是否在生成验证码图片时保留已有的验证码
     *
     * 保留已有的验证码可以让用户在各个不同的页面都看到一致的验证码。
     * 只有这个验证码使用后，已有的验证码才会失效。
     *
     * @var boolean
     */
    public $keep_code = false;

    /**
     * 构造函数
     */
    function __construct()
    {
        $this->code = isset($_SESSION['imgcode']) ? $_SESSION['imgcode'] : '';
        $this->expired = isset($_SESSION['imgcode_expired']) ? $_SESSION['imgcode_expired'] : 0;
    }

    /**
     * 检查图像验证码是否有效
     *
     * @param string $code
     *
     * @return boolean
     */
    function check($code)
    {
        $time = time();
        if ($time >= $this->expired || strtoupper($code) != strtoupper($this->code)) {
            return false;
        }
        return true;
    }

    /**
     * 检查图像验证码是否有效（区分大小写）
     *
     * @param string $code
     *
     * @return boolean
     */
    function checkCaseSensitive($code)
    {
        $time = time();
        if ($time >= $this->expired || $code != $this->code) {
            return false;
        }
        return true;
    }

    /**
     * 清除 session 中的 imgcode 相关信息
     */
    function clear()
    {
        unset($_SESSION['imgcode']);
        unset($_SESSION['imgcode_expired']);
    }

    /**
     * 利用 GD 库产生验证码图像
     *
     * 目前 $options 参数支持下列选项：
     * -  padding_left, padding_right, padding_top, padding_bottom
     * -  border, border_color
     * -  font, color, bgcolor
     *
     * 如果 font 为 0-5，则使用 GD 库内置的字体。
     * 如果要指定字体文件，则 font 选项必须为字体文件的绝对路径，例如：
     * <code>
     * $options = array('font' => '/var/www/example/myfont.gdf');
     * image($type, $length, $lefttime, $options);
     * </code>
     *
     * @param int $type 验证码包含的字符类型，0 - 数字、1 - 字母、其他值 - 数字和字母
     * @param int $length 验证码长度
     * @param int $leftime 验证码有效时间（秒）
     * @param array $options 附加选项，可以指定字体、宽度和高度等参数
     */
    function make($type = 0, $length = 4, $lefttime = 900, $options = null)
    {
        if ($this->keep_code && $this->code != '') {
            $code = $this->code;
        } else {
            // 生成验证码
            switch ($type) {
            case 0:
                $seed = '0123456789';
                break;
            case 1:
                $seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                break;
            default:
                $seed = '346789ABCDEFGHJKLMNPQRTUVWXYabcdefghjklmnpqrtuvwxy';
            }
            if ($length <= 0) { $length = 4; }
            $code = '';
            list($usec, $sec) = explode(" ", microtime());
            srand($sec + $usec * 100000);
            $len = strlen($seed) - 1;
            for ($i = 0; $i < $length; $i++) {
                $code .= substr($seed, rand(0, $len), 1);
            }
            $_SESSION['imgcode'] = $code;
        }
        $_SESSION['imgcode_expired'] = time() + $lefttime;

        // 设置选项
        $padding_left = isset($options['padding_left']) ? (int)$options['padding_left'] : 3;
        $padding_right = isset($options['padding_right']) ? (int)$options['padding_right'] : 3;
        $padding_top = isset($options['padding_top']) ? (int)$options['padding_top'] : 2;
        $padding_bottom = isset($options['padding_bottom']) ? (int)$options['padding_bottom'] : 2;
        $color = isset($options['color']) ? $options['color'] : '0xffffff';
        $bgcolor = isset($options['bgcolor']) ? $options['bgcolor'] : '0x666666';
        $border = isset($options['border']) ? (int)$options['border'] : 1;
        $bd_color = isset($options['border_color']) ? $options['border_color'] : '0x000000';

        // 确定要使用的字体
        if (!isset($options['font'])) {
            $font = 5;
        } else if (is_int($options['font'])) {
            $font = (int)$options['font'];
            if ($font < 0 || $font > 5) { $font = 5; }
        } else {
            $font = imageloadfont($options['font']);
        }

        // 确定字体宽度和高度
        $font_width = imagefontwidth($font);
        $font_height = imagefontheight($font);

        // 确定图像的宽度和高度
        $width = $font_width * strlen($code) + $padding_left + $padding_right + $border * 2 + 1;
        $height = $font_height + $padding_top + $padding_bottom + $border * 2 + 1;

        // 创建图像
        $img = imagecreate($width, $height);

        // 绘制边框
        if ($border) {
            list($r, $g, $b) = $this->hex2rgb($bd_color);
            $border_color = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, 0, 0, $width, $height, $border_color);
        }

        // 绘制背景
        list($r, $g, $b) = $this->hex2rgb($bgcolor);
        $background_color = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, $border, $border, $width - $border - 1, $height - $border - 1, $background_color);

        // 绘制文字
        list($r, $g, $b) = $this->hex2rgb($color);
        $text_color = imagecolorallocate($img, $r, $g, $b);
        imagestring($img, $font, $padding_left + $border, $padding_top + $border, $code, $text_color);

        // 输出图像
        switch (strtolower($this->imagetype)) {
        case 'png':
            header("Content-type: " . image_type_to_mime_type(IMAGETYPE_PNG));
            imagepng($img);
            break;
        case 'gif':
            header("Content-type: " . image_type_to_mime_type(IMAGETYPE_GIF));
            imagegif($img);
            break;
        case 'jpg':
        default:
            header("Content-type: " . image_type_to_mime_type(IMAGETYPE_JPEG));
            imagejpeg($img);
        }

        imagedestroy($img);
        unset($img);
    }

    /**
     * 将 16 进制颜色值转换为 rgb 值
     *
     * @param string $hex
     *
     * @return array
     */
    protected function hex2rgb($color, $defualt = 'ffffff')
    {
        $color = strtolower($color);
        if (substr($color, 0, 2) == '0x') {
            $color = substr($color, 2);
        } elseif (substr($color, 0, 1) == '#') {
            $color = substr($color, 1);
        }
        $l = strlen($color);
        if ($l == 3) {
            $r = hexdec(substr($color, 0, 1));
            $g = hexdec(substr($color, 1, 1));
            $b = hexdec(substr($color, 2, 1));
            return array($r, $g, $b);
        } elseif ($l != 6) {
            $color = $defualt;
        }

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        return array($r, $g, $b);
    }
}