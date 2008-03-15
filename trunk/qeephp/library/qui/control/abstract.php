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
 * 定义 QUI_Control_Abstract 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QUI_Control_Abstract 是用户界面控件的基础类
 *
 * @package mvc
 */
abstract class QUI_Control_Abstract
{
    /**
     * 定义控件可以响应的事件
     */
    const onclick       = 'onclick';
    const ondblclick    = 'ondblclick';
    const onchange      = 'onchange';
    const onfocus       = 'onfocus';
    const onkeypress    = 'onkeypress';
    const onload        = 'onload';
    const onresize      = 'onresize';
    const onunload      = 'onunload';

    /**
     * 控件的 ID
     *
     * @var string
     */
    protected $id;

    /**
     * 控件所属名字空间
     *
     * @var string
     */
    public $namespace;

    /**
     * 控件所属模块
     *
     * @var string
     */
    public $module;

    /**
     * 控件的属性
     *
     * @var array
     */
    public $attribs;

    /**
     * 额外的视图数据，通常来自控制器
     *
     * @var array
     */
    public $viewdata = null;

    /**
     * 视图适配器
     *
     * @var QView_Adapter_Abstract
     */
    public $view_adapter;

    /**
     * 构造函数
     *
     * @param string $id
     * @param array $attribs
     */
    function __construct(QView_Adapter_Abstract $view_adapter, $id, array $attribs = null)
    {
        $this->view_adapter = $view_adapter;
        $this->id = $id;
        $this->attribs = (array)$attribs;
    }

    /**
     * 返回控件的 ID
     *
     * @return string
     */
    function id()
    {
        return $this->id;
    }

    /**
     * 渲染控件
     */
    abstract function render($return = false);

    /**
     * 如果控件需要处理事件，则必须覆盖此方法
     *
     * @param enum $event
     * @param array $params
     *
     * @return mixed
     */
    function events($event, array $params = null)
    {
        return null;
    }

    /**
     * 根据 ID 和 NAME 属性返回字符串
     *
     * @return string
     */
    protected function setIdName()
    {
        $out = '';
        $name = $this->attr('name');
        if (empty($name)) {
            $name = $this->id;
        }
        $out .= 'name="' . htmlspecialchars($name) . '" ';
        $out .= 'id="' . htmlspecialchars($this->id) . '" ';
        unset($this->attribs['id']);
        return $out;
    }

    /**
     * 根据 DISABLED 属性返回字符串
     *
     * @return string
     */
    protected function setDisabled()
    {
        $disabled = $this->attr('disabled');
        return ($disabled) ? 'disabled="disabled" ' : '';
    }

    /**
     * 根据 CHECKED 属性返回字符串
     *
     * @return string
     */
    protected function setChecked()
    {
        $checked = $this->attr('checked');
        if (empty($checked)) { return ''; }

        if (!empty($this->attribs['value'])) {
            if ($checked == $this->attribs['value']) {
                return 'checked="checked" ';
            } else {
                return '';
            }
        } else {
            return 'checked="checked" ';
        }
    }

    /**
     * 尝试获得特定属性的值
     *
     * @param string $attr
     *
     * @return mixed
     */
    protected function attr($attr)
    {
        $value = isset($this->attribs[$attr]) ? $this->attribs[$attr] : null;
        unset($this->attribs[$attr]);
        return $value;
    }

    /**
     * 构造控件的属性字符串
     *
     * @param array|string $exclude
     *
     * @return string
     */
    protected function attribsToString($exclude = array())
    {
        $exclude = Q::normalize($exclude);
        $exclude = array_flip($exclude);
        $out = '';
        foreach ($this->attribs as $attrib => $value) {
            if (isset($exclude[$attrib])) { continue; }
            $out .= $attrib .'="' . str_replace('"', '\'', $value) . '" ';
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
    protected function splitMultiDimArray(& $items, $key, $caption, $key2caption = false)
    {
        if ($caption == '') {
            $first = reset($items);
            if (!is_array($first)) {
                // LC_MSG: 无效的 items 属性.
                throw new QUI_Exception(__('无效的 items 属性.'));
            }
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
