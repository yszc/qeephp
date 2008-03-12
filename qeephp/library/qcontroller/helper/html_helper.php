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
 * 定义 Helper_Html 类
 *
 * @package helper
 * @version $Id$
 */

/**
 * Helper_Html 类提供生成 HTML 常用代码的功能
 *
 * @package helper
 */
class Helper_Html
{
    /**
     * 生成一个下拉列表框
     *
     * @param string $name
     * @param array $arr
     * @param mixed $selected
     * @param string $extra
     */
    function dropdown_list($name, $arr, $selected = null, $extra = null) {
        echo "<select name=\"{$name}\" {$extra} >\n";
        foreach ($arr as $value => $title) {
            echo '<option value="' . htmlspecialchars($value) . '"';
            if ($selected == $value) { echo ' selected'; }
            echo '>' . htmlspecialchars($title) . "&nbsp;&nbsp;</option>\n";
        }
        echo "</select>\n";
    }

    /**
     * 生成一组单选框
     *
     * @param string $name
     * @param array $arr
     * @param mixed $checked
     * @param string $separator
     * @param string $extra
     */
    function radio_group($name, $arr, $checked = null, $separator = '', $extra = null) {
        $ix = 0;
        foreach ($arr as $value => $title) {
            $value_h = htmlspecialchars($value);
            $title = t($title);
            echo "<input name=\"{$name}\" type=\"radio\" id=\"{$name}_{$ix}\" value=\"{$value_h}\" ";
            if ($value == $checked) {
                echo "checked=\"checked\"";
            }
            echo " {$extra} />";
            echo "<label for=\"{$name}_{$ix}\">{$title}</label>";
            echo $separator;
            $ix++;
            echo "\n";
        }
    }

    /**
     * 生成一组多选框
     *
     * @param string $name
     * @param array $arr
     * @param array $selected
     * @param string $separator
     * @param string $extra
     */
    function checkbox_group($name, $arr, $selected = array(), $separator = '', $extra = null) {
        $ix = 0;
        if (!is_array($selected)) {
            $selected = array($selected);
        }
        foreach ($arr as $value => $title) {
            $value_h = htmlspecialchars($value);
            $title = t($title);
            echo "<input name=\"{$name}[]\" type=\"checkbox\" id=\"{$name}_{$ix}\" value=\"{$value_h}\" ";
            if (in_array($value, $selected)) {
                echo "checked=\"checked\"";
            }
            echo " {$extra} />";
            echo "<label for=\"{$name}_{$ix}\">{$title}</label>";
            echo $separator;
            $ix++;
            echo "\n";
        }
    }

    /**
     * 生成一个多选框
     *
     * @param string $name
     * @param int $value
     * @param boolean $checked
     * @param string $label
     * @param string $extra
     */
    function checkbox($name, $value = 1, $checked = false, $label = '', $extra = null) {
        echo "<input name=\"{$name}\" type=\"checkbox\" id=\"{$name}_1\" value=\"{$value}\"";
        if ($checked) { echo " checked"; }
        echo " {$extra} />\n";
        if ($label) {
            echo "<label for=\"{$name}_1\">" . htmlspecialchars($label) . "</label>\n";
        }
    }

    /**
     * 生成一个文本输入框
     *
     * @param string $name
     * @param string $value
     * @param int $width
     * @param int $maxLength
     * @param string $extra
     */
    function textbox($name, $value = '', $width = null, $maxLength = null, $extra = null) {
        echo "<input name=\"{$name}\" type=\"text\" value=\"" . htmlspecialchars($value) . "\" ";
        if ($width) {
            echo "size=\"{$width}\" ";
        }
        if ($maxLength) {
            echo "maxlength=\"{$maxLength}\" ";
        }
        echo " {$extra} />\n";
    }

    /**
     * 生成一个密码输入框
     *
     * @param string $name
     * @param string $value
     * @param int $width
     * @param int $maxLength
     * @param string $extra
     */
    function password($name, $value = '', $width = null, $maxLength = null, $extra = null) {
        echo "<input name=\"{$name}\" type=\"password\" value=\"" . htmlspecialchars($value) . "\" ";
        if ($width) {
            echo "size=\"{$width}\" ";
        }
        if ($maxLength) {
            echo "maxlength=\"{$maxLength}\" ";
        }
        echo " {$extra} />\n";
    }

    /**
     * 生成一个多行文本输入框
     *
     * @param string $name
     * @param string $value
     * @param int $width
     * @param int $height
     * @param string $extra
     */
    function textarea($name, $value = '', $width = null, $height = null, $extra = null) {
        echo "<textarea name=\"{$name}\"";
        if ($width) { echo "cols=\"{$width}\" "; }
        if ($height) { echo "rows=\"{$height}\" "; }
        echo " {$extra} >";
        echo htmlspecialchars($value);
        echo "</textarea>\n";
    }

    /**
     * 生成一个隐藏域
     *
     * @param string $name
     * @param string $value
     * @param string $extra
     */
    function hidden($name, $value = '', $extra = null) {
        echo "<input name=\"{$name}\" type=\"hidden\" value=\"";
        echo htmlspecialchars($value);
        echo "\" {$extra} />\n";
    }

    /**
     * 生成一个文件上传域
     *
     * @param string $name
     * @param int $width
     * @param string $extra
     */
    function filefield($name, $width = null, $extra = null) {
        echo "<input name=\"{$name}\" type=\"file\"";
        if ($width) {
            echo " size=\"{$width}\"";
        }
        echo " {$extra} />\n";
    }

    /**
     * 生成 form 标记
     *
     * @param string $name
     * @param string $action
     * @param string $method
     * @param string $onsubmit
     * @param string $extra
     */
    function form($name, $action, $method='post', $onsubmit='', $extra = null) {
        echo "<form name=\"{$name}\" action=\"{$action}\" method=\"{$method}\" ";
        if ($onsubmit) {
            echo "onsubmit=\"{$onsubmit}\"";
        }
        echo " {$extra} >\n";
    }

    /**
     * 关闭 form 标记
     */
    function form_close() {
        echo "</form>\n";
    }
}
