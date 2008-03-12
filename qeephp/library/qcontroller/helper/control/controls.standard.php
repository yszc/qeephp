<?php

/**
* 构造一个单行文本框
*
* @param string $name
* @param array $attribs
*
* @return string
*/
function ctl_textbox($name, $attribs)
{
    return ctl_common_basic($name, $attribs, 'text');
}

/**
 * 构造一个密码输入框
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_password($name, $attribs)
{
    return ctl_common_basic($name, $attribs, 'password');
}

/**
 * 构造一个多行文本框
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_memo($name, $attribs)
{
    extract(QWebControls::extract_attribs($attribs, array('id', 'value', 'disabled')));
    if (empty($id)) { $id = $name; }

    $out = '<textarea ';
    if ($name) {
        $out .= 'name="' . h($name) . '" ';
        $out .= 'id="' . h($id) . '" ';
    }
    $out .= QWebControls::attribs_to_string($attribs);
    if ($disabled) {
        $out .= 'disabled="disabled" ';
    }
    $out .= '>';
    $out .= h($value);
    $out .= '</textarea>';
    return $out;
}

/**
 * 构造一个多选框
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_checkbox($name, $attribs)
{
    return ctl_common_check($name, $attribs, 'checkbox');
}

/**
 * 构造一个多选框组
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_checkboxgroup($name, $attribs)
{
    return ctl_common_checkgroup($name, $attribs, 'checkbox', '[]');
}

/**
 * 构造一个单选按钮
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_radio($name, $attribs)
{
    return ctl_common_check($name, $attribs, 'radio');
}

/**
 * 构造一组单选按钮
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_radiogroup($name, $attribs)
{
    return ctl_common_checkgroup($name, $attribs, 'radio', '');
}

/**
 * 构造一个列表框
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_listbox($name, $attribs)
{
    extract(QWebControls::extract_attribs($attribs, array('id', 'size', 'items', 'selected', 'multiple', 'disabled', 'key', 'caption')));
    if (empty($id)) { $id = $name; }

    if (!is_array($selected) && substr($selected, 0, 1) == ':') {
        $selected = intval(substr($selected, 1));
        $selectedByIndex = true;
    } else {
        $selectedByIndex = false;
    }
    $out = '<select ';
    if ($name) {
        $out .= 'name="' . h($name) . '" ';
        $out .= 'id="' . h($id) . '" ';
    }
    if ($size <= 0) {
        $size = 4;
    }
    $out .= 'size="' . $size . '" ';
    if ($multiple) {
        $out .= 'multiple="multiple" ';
    }
    if ($disabled) {
        $out .= 'disabled="disabled" ';
    }
    $out .= QWebControls::attribs_to_string($attribs);
    $out .= ">\n";

    $items = (array)$items;

    if ($key) {
        if (!split_multi_dim_array($items, $key, $caption)) {
            return 'INVALID ITEMS';
        }
    }

    $ix = 0;
    foreach ($items as $caption => $value) {
        $out .= '<option value="' . h($value) . '" ';
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
            $out .= 'selected="selected" ';
        }
        $out .= '>';
        $out .= h($caption);
        $out .= "</option>\n";
        $ix++;
    }
    $out .= "</select>\n";
    return $out;
}

/**
 * 构造一个下拉列表框
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_dropdownList($name, $attribs)
{
    extract(QWebControls::extract_attribs($attribs, array('id', 'items', 'selected', 'disabled', 'key', 'caption')));
    if (empty($id)) { $id = $name; }

    if (substr($selected, 0, 1) == ':') {
        $selected = intval(substr($selected, 1));
        $selectedByIndex = true;
    } else {
        $selectedByIndex = false;
    }
    $out = '<select ';
    if ($name) {
        $out .= 'name="' . h($name) . '" ';
        $out .= 'id="' . h($id) . '" ';
    }
    if ($disabled) {
        $out .= 'disabled="disabled" ';
    }
    $out .= QWebControls::attribs_to_string($attribs);
    $out .= ">\n";

    $items = (array)$items;

    if ($key) {
        if (!split_multi_dim_array($items, $key, $caption)) {
            return 'INVALID ITEMS';
        }
    }

    $ix = 0;
    foreach ($items as $caption => $value) {
        $out .= '<option value="' . h($value) . '" ';
        if ($selectedByIndex) {
            if ($ix == $selected) {
                $out .= 'selected="selected" ';
            }
        } else {
            if ($value == $selected) {
                $out .= 'selected="selected" ';
            }
        }
        $out .= '>';
        $out .= h($caption);
        $out .= "</option>\n";
        $ix++;
    }
    $out .= "</select>\n";
    return $out;
}

/**
 * 构造一个上传文件选择框
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_fileupload($name, $attribs)
{
    return ctl_common_basic($name, $attribs, 'file');
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
function ctl_button($name, $attribs, $buttonType = 'button')
{
    extract(QWebControls::extract_attribs($attribs, array('caption')));
    if ($caption != '') { $attribs['value'] = $caption; }
    return ctl_common_basic($name, $attribs, $buttonType);
}

/**
 * 构造一个表单提交按钮
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_submit($name, $attribs)
{
    return ctl_button($name, $attribs, 'submit');
}

/**
 * 构造一个表单重置按钮
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_reset($name, $attribs)
{
    return ctl_button($name, $attribs, 'reset');
}

/**
 * 构造一个标签控件
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_label($name, $attribs)
{
    extract(QWebControls::extract_attribs($attribs, array('id', 'caption')));
    if (empty($id)) { $id = $name; }

    $out = '<label ';
    if ($name) {
        $out .= 'name="' . h($name) . '" ';
        $out .= 'id="' . h($id) . '" ';
    }
    $out .= QWebControls::attribs_to_string($attribs);
    $out .= '>';
    $out .= h($caption);
    $out .= '</label>';
    return $out;
}


/**
 * 构造一个静态控件
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_static($name, $attribs)
{
    extract(QWebControls::extract_attribs($attribs, array('id', 'value')));
    if (empty($id)) { $id = $name; }

    $out = '<div ';
    if ($name) {
        $out .= 'name="' . h($name) . '" ';
        $out .= 'id="' . h($id) . '" ';
    }
    $out .= QWebControls::attribs_to_string($attribs);
    $out .= '>';
    $out .= h($value);
    $out .= '</div>';
    return $out;
}

/**
 * 构造一个隐藏表单控件
 *
 * @param string $name
 * @param array $attribs
 *
 * @return string
 */
function ctl_hidden($name, $attribs)
{
    return ctl_common_basic($name, $attribs, 'hidden');
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
function ctl_common_basic($name, $attribs, $type)
{
    extract(QWebControls::extract_attribs($attribs, array('id', 'value', 'disabled')));
    if (empty($id)) { $id = $name; }

    $out = "<input type=\"{$type}\" ";
    if ($name) {
        $out .= 'name="' . h($name) . '" ';
        $out .= 'id="' . h($id) . '" ';
    }
    $out .= 'value="' . h($value) . '" ';
    $out .= QWebControls::attribs_to_string($attribs);
    if ($disabled) {
        $out .= 'disabled="disabled" ';
    }
    $out .= '/>';
    return $out;
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
function ctl_common_check($name, $attribs, $type)
{
    extract(QWebControls::extract_attribs($attribs,
            array('id', 'value', 'checked', 'disabled', 'caption')));
    if (empty($id)) { $id = $name; }

    $out = "<input type=\"{$type}\" ";
    if ($name) {
        $out .= 'name="' . h($name) . '" ';
        $out .= 'id="' . h($id) . '" ';
    }
    if (empty($value)) { $value = 1; }
    $out .= 'value="' . h($value) . '" ';
    $out .= QWebControls::attribs_to_string($attribs);
    if ($checked) {
        $out .= 'checked="checked" ';
    }
    if ($disabled) {
        $out .= 'disabled="disabled" ';
    }
    $out .= '/>';
    if (strlen($caption)) {
        $out .= ctl_Label(null, array('for' => $id, 'caption' => $caption));
    }
    return $out;
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
function ctl_common_checkgroup($name, $attribs, $type, $suffix)
{
    static $idSuffix = 1;

    extract(QWebControls::extract_attribs($attribs, array('items', 'selected', 'disabled',
            'multirow', 'cols', 'key', 'caption', 'table', 'border', 'cellspacing',
            'cellpadding', 'key2caption')));

    if (!is_array($selected) && substr($selected, 0, 1) == ':') {
        $selected = intval(substr($selected, 1));
        $selectedByIndex = true;
    } else {
        $selectedByIndex = false;
    }

    $out = '';
    $items = (array)$items;
    $max = count($items);
    if ($max <= 0) { return ''; }

    if ($key) {
        if (!split_multi_dim_array($items, $key, $caption, $key2caption)) {
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
        $border = is_null($border) ? 0 : $border;
        $cellspacing = is_null($cellspacing) ? 0 : $cellspacing;
        $cellpadding = is_null($cellpadding) ? 0 : $cellpadding;
        $out .= "<table border=\"{$border}\" cellspacing=\"{$cellspacing}\" cellpadding=\"{$cellpadding}\">\n";
        if ($multirow) { $out .= "<tr>\n"; }
    }
    foreach ($items as $caption => $value) {
        if ($table) { $out .= "<td>"; }
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

        $out .= "<input type=\"{$type}\" ";
        if ($name) {
            $out .= 'name="' . h($name) . $suffix . '" ';
            $idSuffix++;
            $out .= 'id="' . h($name) . "_{$idSuffix}\" ";
        }
        if (strlen($value) == 0) { $value = 1; }
        $out .= 'value="' . h($value) . '" ';
        $out .= QWebControls::attribs_to_string($attribs);
        if ($checked) {
            $out .= 'checked="checked" ';
        }
        if ($disabled) {
            $out .= 'disabled="disabled" ';
        }
        $out .= '/>';
        if ($caption) {
            $out .= ctl_Label(null, array(
                'for' => "{$name}_{$idSuffix}", 'caption' => $caption
            ));
        }

        if ($ix < $max) {
            if ($multirow) {
                if ($cols) {
                    $col++;
                    if ($col >= $cols) {
                        if ($table) { $out .= "</td>\n</tr>\n<tr>\n"; }
                        else { $out .= "<br />\n"; }
                        $col = 0;
                    } else {
                        if ($table) { $out .= "</td>\n"; }
                        else { $out .= "&nbsp;&nbsp;\n"; }
                    }
                } else {
                    if ($table) { $out .= "</td>\n</tr>\n<tr>\n"; }
                    else { $out .= "<br />\n"; }
                }
            } else {
                if ($table) { $out .= "</td>\n"; }
                else { $out .= "&nbsp;&nbsp;\n"; }
            }
        }

        $ix++;
    }

    if ($table) {
        if ($cols && $ix % $cols > 0) {
           $out .= str_repeat("<td>&nbsp;</td>\n", $cols - $ix % $cols);
        }
        $out .= "</tr>\n</table>\n";
    }
    return $out;
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
function split_multi_dim_array($items, $key, $caption, $key2caption = false)
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
