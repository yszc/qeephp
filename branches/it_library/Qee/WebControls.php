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
 * 定义 Qee_WebControls 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

/**
 * Qee_WebControls 类提供一组支持 WebControls 的静态方法
 *
 * 开发者不应该自行加载该文件，而是调用 init_webcontrols() 来进行 WebControls 的初始化。
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class Qee_WebControls
{
    /**
     * 扩展的控件
     *
     * @var array
     */
    protected $_extends = array();

    /**
     * 保存扩展控件的目录
     *
     * @var array
     */
    protected $_extendsDir = array();

    /**
     * 构造函数
     */
    public function __construct()
    {
        $extendsDir = Qee::getAppInf('webControlsExtendsDir');
        if (!is_array($extendsDir)) {
            $extendsDir = explode(PATH_SEPARATOR, $extendsDir);
        }
        $this->_extendsDir = array_merge($this->_extendsDir, $extendsDir);
    }

    /**
     * 构造一个控件的 HTML 代码
     *
     * @param string $type
     * @param string $name
     * @param array $attribs
     * @param boolean $return
     *
     * @return string
     */
    public function control($type, $name, $attribs = null, $return = false)
    {
        $type = strtolower($type);
        $render = '_ctl' . ucfirst($type);
        $attribs = (array)$attribs;

        $__ctl_out = false;
        if (method_exists($this, $render)) {
            $__ctl_out = $this->{$render}($name, $attribs);
        } else {
            $extfilename = ucfirst($type) . '.php';
            if (!isset($this->_extends[$type])) {
                foreach ($this->_extendsDir as $dir) {
                    if (file_exists($dir . DS . $extfilename)) {
                        require $dir . DS . $extfilename;
                        $this->_extends[$type] = true;
                        break;
                    }
                }
            }

            if (isset($this->_extends[$type])) {
                $__ctl_out = call_user_func_array($render, array('name' => $name, 'attribs' => & $attribs));
            }
        }

        if ($__ctl_out === false) {
            $__ctl_out = "INVALID CONTROL TYPE \"{$type}\"";
        }

        if ($return) { return $__ctl_out; }
        echo $__ctl_out;
        return '';
    }

    /**
     * 构造控件的属性字符串
     *
     * @param array $attribs
     *
     * @return string
     */
    static public function attribsToString($attribs)
    {
        $__ctl_out = '';
        foreach ($attribs as $attrib => $value) {
            $__ctl_out .= $attrib . '="' . str_replace('"', '\'', $value) . '" ';
        }
        return $__ctl_out;
    }

    /**
     * 从属性数组中导出需要的属性
     *
     * @param array $attribs
     * @param array $req
     *
     * @return array
     */
    static public function extractAttribs(& $attribs, $req)
    {
        $extract = array();
        foreach ($req as $attrib) {
            if (array_key_exists($attrib, $attribs)) {
                $extract[$attrib] = $attribs[$attrib];
                unset($attribs[$attrib]);
            } else {
                $extract[$attrib] = null;
            }
        }
        return $extract;
    }

    /**
     * 从属性数组中合并嵌套的数组，但消除嵌套数组中的数组
     *
     * @param array $attribs
     */
    static public function mergeAttribs(& $attribs)
    {
        $args = array();
        foreach ($attribs as $key => $arg) {
            if (is_array($arg)) {
                $args = array_merge($args, $arg);
            } else if (!is_null($arg)) {
                $args[$key] = $arg;
            }
        }
        $attribs = $args;
    }

    /**
     * 返回视图对象
     *
     * @return object
     */
    protected function _getView()
    {
        $viewClass = Qee::getAppInf('view');
        if ($viewClass != 'PHP') {
            return Qee::getSingleton($viewClass);
        } else {
            return false;
        }
    }

    /**
     * 构造一个单行文本框
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlTextbox($name, $attribs)
    {
        return $this->__baseCtlInput($name, $attribs, 'text');
    }

    /**
     * 构造一个密码输入框
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlPassword($name, $attribs)
    {
        return $this->__baseCtlInput($name, $attribs, 'password');
    }

    /**
     * 构造一个多行文本框
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlMemo($name, $attribs)
    {
        extract(self::extractAttribs($attribs, array('id', 'value', 'disabled')));
        if (empty($id)) { $id = $name; }

        $__ctl_out = '<textarea ';
        if ($name) {
            $__ctl_out .= 'name="' . h($name) . '" ';
            $__ctl_out .= 'id="' . h($id) . '" ';
        }
        $__ctl_out .= self::attribsToString($attribs);
        if ($disabled) {
            $__ctl_out .= 'disabled="disabled" ';
        }
        $__ctl_out .= '>';
        $__ctl_out .= h($value);
        $__ctl_out .= '</textarea>';
        return $__ctl_out;
    }

    /**
     * 构造一个多选框
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlCheckbox($name, $attribs)
    {
        return $this->__baseCtlCheckboxOrRadio($name, $attribs, 'checkbox');
    }

    /**
     * 构造一个多选框组
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlCheckBoxGroup($name, $attribs)
    {
        return $this->__baseCtlCheckboxOrRadioGroup($name, $attribs, 'checkbox', '[]');
    }

    /**
     * 构造一个单选按钮
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlRadio($name, $attribs)
    {
        return $this->__baseCtlCheckboxOrRadio($name, $attribs, 'radio');
    }

    /**
     * 构造一组单选按钮
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlRadioGroup($name, $attribs)
    {
        return $this->__baseCtlCheckboxOrRadioGroup($name, $attribs, 'radio', '');
    }

    /**
     * 构造一个列表框
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlListBox($name, $attribs)
    {
        extract(self::extractAttribs($attribs, array('id', 'size', 'items', 'selected', 'multiple', 'disabled', 'key', 'caption')));
        if (empty($id)) { $id = $name; }

        if (!is_array($selected) && substr($selected, 0, 1) == ':') {
            $selected = intval(substr($selected, 1));
            $selectedByIndex = true;
        } else {
            $selectedByIndex = false;
        }
        $__ctl_out = '<select ';
        if ($name) {
            $__ctl_out .= 'name="' . h($name) . '" ';
            $__ctl_out .= 'id="' . h($id) . '" ';
        }
        if ($size <= 0) {
            $size = 4;
        }
        $__ctl_out .= 'size="' . $size . '" ';
        if ($multiple) {
            $__ctl_out .= 'multiple="multiple" ';
        }
        if ($disabled) {
            $__ctl_out .= 'disabled="disabled" ';
        }
        $__ctl_out .= self::attribsToString($attribs);
        $__ctl_out .= ">\n";

        $items = (array)$items;

        if ($key) {
            if (!self::__processMultiDimArray($items, $key, $caption)) {
                return 'INVALID ITEMS';
            }
        }

        $ix = 0;
        foreach ($items as $caption => $value) {
            $__ctl_out .= '<option value="' . h($value) . '" ';
            $checked = false;
            if ($selectedByIndex) {
                if (is_array($selected)) {
                    if (in_array($ix, $selected)) {
                        $checked = true;
                    }
                } else if ($ix == $selected) {
                    $checked = true;
                }
            } else {
                if (is_array($selected)) {
                    if (in_array($value, $selected)) {
                        $checked = true;
                    }
                } else if ($value == $selected) {
                    $checked = true;
                }
            }
            if ($checked) {
                $__ctl_out .= 'selected="selected" ';
            }
            $__ctl_out .= '>';
            $__ctl_out .= h($caption);
            $__ctl_out .= "</option>\n";
            $ix++;
        }
        $__ctl_out .= "</select>\n";
        return $__ctl_out;
    }

    /**
     * 构造一个下拉列表框
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlDropdownList($name, $attribs)
    {
        extract(self::extractAttribs($attribs,
                array('id', 'items', 'selected', 'disabled', 'key', 'caption')));
        if (empty($id)) { $id = $name; }

        if (substr($selected, 0, 1) == ':') {
            $selected = intval(substr($selected, 1));
            $selectedByIndex = true;
        } else {
            $selectedByIndex = false;
        }
        $__ctl_out = '<select ';
        if ($name) {
            $__ctl_out .= 'name="' . h($name) . '" ';
            $__ctl_out .= 'id="' . h($id) . '" ';
        }
        if ($disabled) {
            $__ctl_out .= 'disabled="disabled" ';
        }
        $__ctl_out .= self::attribsToString($attribs);
        $__ctl_out .= ">\n";

        $items = (array)$items;

        if ($key) {
            if (!self::__processMultiDimArray($items, $key, $caption)) {
                return 'INVALID ITEMS';
            }
        }

        $ix = 0;
        foreach ($items as $caption => $value) {
            $__ctl_out .= '<option value="' . h($value) . '" ';
            if ($selectedByIndex) {
                if ($ix == $selected) {
                    $__ctl_out .= 'selected="selected" ';
                }
            } else {
                if ($value == $selected) {
                    $__ctl_out .= 'selected="selected" ';
                }
            }
            $__ctl_out .= '>';
            $__ctl_out .= h($caption);
            $__ctl_out .= "</option>\n";
            $ix++;
        }
        $__ctl_out .= "</select>\n";
        return $__ctl_out;
    }

    /**
     * 构造一个上传文件选择框
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlFileUpload($name, $attribs)
    {
        return $this->__baseCtlInput($name, $attribs, 'file');
    }

    /**
     * 构造一个按钮
     *
     * @param string $name
     * @param array $attribs
     * @param string $buttonType
     *
     * @return string
     */
    protected function _ctlButton($name, $attribs, $buttonType = 'button')
    {
        extract(self::extractAttribs($attribs, array('caption')));
        if ($caption != '') { $attribs['value'] = $caption; }
        return $this->__baseCtlInput($name, $attribs, $buttonType);
    }

    /**
     * 构造一个表单提交按钮
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlSubmit($name, $attribs)
    {
        return $this->_ctlButton($name, $attribs, 'submit');
    }

    /**
     * 构造一个表单重置按钮
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlReset($name, $attribs)
    {
        return $this->_ctlButton($name, $attribs, 'reset');
    }

    /**
     * 构造一个标签控件
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlLabel($name, $attribs)
    {
        extract(self::extractAttribs($attribs, array('id', 'caption')));
        if (empty($id)) { $id = $name; }

        $__ctl_out = '<label ';
        if ($name) {
            $__ctl_out .= 'name="' . h($name) . '" ';
            $__ctl_out .= 'id="' . h($id) . '" ';
        }
        $__ctl_out .= self::attribsToString($attribs);
        $__ctl_out .= '>';
        $__ctl_out .= h($caption);
        $__ctl_out .= '</label>';
        return $__ctl_out;
    }


    /**
     * 构造一个静态控件
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlStatic($name, $attribs)
    {
        extract(self::extractAttribs($attribs, array('id', 'value')));
        if (empty($id)) { $id = $name; }

        $__ctl_out = '<div ';
        if ($name) {
            $__ctl_out .= 'name="' . h($name) . '" ';
            $__ctl_out .= 'id="' . h($id) . '" ';
        }
        $__ctl_out .= self::attribsToString($attribs);
        $__ctl_out .= '>';
        $__ctl_out .= h($value);
        $__ctl_out .= '</div>';
        return $__ctl_out;
    }

    /**
     * 构造一个隐藏表单控件
     *
     * @param string $name
     * @param array $attribs
     *
     * @return string
     */
    protected function _ctlHidden($name, $attribs)
    {
        return $this->__baseCtlInput($name, $attribs, 'hidden');
    }

    /**
     * 构造一个一般的 INPUT 控件
     *
     * @param string $name
     * @param array $attribs
     * @param string $type
     *
     * @return string
     */
    protected function __baseCtlInput($name, $attribs, $type)
    {
        extract(self::extractAttribs($attribs, array('id', 'value', 'disabled')));
        if (empty($id)) { $id = $name; }

        $__ctl_out = "<input type=\"{$type}\" ";
        if ($name) {
            $__ctl_out .= 'name="' . h($name) . '" ';
            $__ctl_out .= 'id="' . h($id) . '" ';
        }
        $__ctl_out .= 'value="' . h($value) . '" ';
        $__ctl_out .= self::attribsToString($attribs);
        if ($disabled) {
            $__ctl_out .= 'disabled="disabled" ';
        }
        $__ctl_out .= '/>';
        return $__ctl_out;
    }

    /**
     * 构造一个多选框或单选按钮
     *
     * @param string $name
     * @param array $attribs
     * @param string $type
     *
     * @return string
     */
    protected function __baseCtlCheckboxOrRadio($name, $attribs, $type)
    {
        extract(self::extractAttribs($attribs,
                array('id', 'value', 'checked', 'disabled', 'caption')));
        if (empty($id)) { $id = $name; }

        $__ctl_out = "<input type=\"{$type}\" ";
        if ($name) {
            $__ctl_out .= 'name="' . h($name) . '" ';
            $__ctl_out .= 'id="' . h($id) . '" ';
        }
        if (empty($value)) { $value = 1; }
        $__ctl_out .= 'value="' . h($value) . '" ';
        $__ctl_out .= self::attribsToString($attribs);
        if ($checked) {
            $__ctl_out .= 'checked="checked" ';
        }
        if ($disabled) {
            $__ctl_out .= 'disabled="disabled" ';
        }
        $__ctl_out .= '/>';
        if (strlen($caption)) {
            $__ctl_out .= $this->_ctlLabel(null, array('for' => $id, 'caption' => $caption));
        }
        return $__ctl_out;
    }

    /**
     * 构造一个多选框或单选按钮组
     *
     * @param string $name
     * @param array $attribs
     * @param string $type
     * @param string $suffix
     *
     * @return string
     */
    protected function __baseCtlCheckboxOrRadioGroup($name, $attribs, $type, $suffix)
    {
        static $idSuffix = 1;

        extract(self::extractAttribs($attribs, array('items', 'selected', 'disabled',
                'multirow', 'cols', 'key', 'caption', 'table', 'border', 'cellspacing',
                'cellpadding', 'key2caption')));

        if (!is_array($selected) && substr($selected, 0, 1) == ':') {
            $selected = intval(substr($selected, 1));
            $selectedByIndex = true;
        } else {
            $selectedByIndex = false;
        }

        $__ctl_out = '';
        $items = (array)$items;
        $max = count($items);
        if ($max <= 0) { return ''; }

        if ($key) {
            if (!self::__processMultiDimArray($items, $key, $caption, $key2caption)) {
                return 'INVALID ITEMS';
            }
        } else if ($key2caption) {
            $tmp = array();
            foreach ($items as $caption => $key) {
                $tmp[$key] = $caption;
            }
            $items = $tmp;
        }

        $ix = 0;
        $col = 0;
        if ($table) {
            $border = $border === null ? 0 : $border;
            $cellspacing = $cellspacing === null ? 0 : $cellspacing;
            $cellpadding = $cellpadding === null ? 0 : $cellpadding;
            $__ctl_out .= "<table border=\"{$border}\" cellspacing=\"{$cellspacing}\" cellpadding=\"{$cellpadding}\">\n";
            if ($multirow) { $__ctl_out .= "<tr>\n"; }
        }
        foreach ($items as $caption => $value) {
            if ($table) { $__ctl_out .= "<td>"; }
            $checked = false;
            if ($selectedByIndex) {
                if (is_array($selected)) {
                    if (in_array($ix, $selected)) { $checked = true; }
                } else if ($ix == $selected) {
                    $checked = true;
                }
            } else {
                if (is_array($selected)) {
                    if (in_array($value, $selected)) { $checked = true; }
                } else if ($value == $selected) {
                    $checked = true;
                }
            }

            $__ctl_out .= "<input type=\"{$type}\" ";
            if ($name) {
                $__ctl_out .= 'name="' . h($name) . $suffix . '" ';
                $idSuffix++;
                $__ctl_out .= 'id="' . h($name) . "_{$idSuffix}\" ";
            }
            if (strlen($value) == 0) { $value = 1; }
            $__ctl_out .= 'value="' . h($value) . '" ';
            $__ctl_out .= self::attribsToString($attribs);
            if ($checked) {
                $__ctl_out .= 'checked="checked" ';
            }
            if ($disabled) {
                $__ctl_out .= 'disabled="disabled" ';
            }
            $__ctl_out .= '/>';
            if ($caption) {
                $__ctl_out .= $this->_ctlLabel(null, array(
                    'for' => "{$name}_{$idSuffix}", 'caption' => $caption
                ));
            }

            if ($ix < $max) {
                if ($multirow) {
                    if ($cols) {
                        $col++;
                        if ($col >= $cols) {
                            if ($table) { $__ctl_out .= "</td>\n</tr>\n<tr>\n"; }
                            else { $__ctl_out .= "<br />\n"; }
                            $col = 0;
                        } else {
                            if ($table) { $__ctl_out .= "</td>\n"; }
                            else { $__ctl_out .= "&nbsp;&nbsp;\n"; }
                        }
                    } else {
                        if ($table) { $__ctl_out .= "</td>\n</tr>\n<tr>\n"; }
                        else { $__ctl_out .= "<br />\n"; }
                    }
                } else {
                    if ($table) { $__ctl_out .= "</td>\n"; }
                    else { $__ctl_out .= "&nbsp;&nbsp;\n"; }
                }
            }

            $ix++;
        }

        if ($table) {
            if ($cols && $ix % $cols > 0) {
               $__ctl_out .= str_repeat("<td>&nbsp;</td>\n", $cols - $ix % $cols);
            }
            $__ctl_out .= "</tr>\n</table>\n";
        }
        return $__ctl_out;
    }

    /**
     * 将多维数组转换为一维数组
     *
     * @param array $items
     * @param string $key
     * @param string $caption
     * @param boolean $key2caption
     *
     * @return boolean
     */
    static protected function __processMultiDimArray(& $items, & $key, & $caption, $key2caption = false)
    {
        if ($caption == '') {
            $first = reset($items);
            if (!is_array($first)) { return false; }
            next($first);
            $caption = key($first);
        }

        // 传入的 items 是一个多维数组
        $new = array();
        if ($key2caption) {
            foreach ($items as $item) {
                $new[$item[$key]] = $item[$caption];
            }
        } else {
            foreach ($items as $item) {
                $new[$item[$caption]] = $item[$key];
            }
        }
        $items = $new;
        return true;
    }
}
