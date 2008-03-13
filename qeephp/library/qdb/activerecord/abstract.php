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
 * 定义 QDB_ActiveRecord_Abstract 抽象类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Abstract 类实现了 Active Record 模式
 *
 * @package database
 */
abstract class QDB_ActiveRecord_Abstract implements QDB_ActiveRecord_Events, QDB_ActiveRecord_Interface
{
    /**
     * 当前对象的类名
     *
     * @var string
     */
    private $__class;

    /**
     * 对象不允许直接访问的属性
     *
     * @var array
     */
    private $__props;

    /**
     * 对象属性的读写方法
     *
     * @var array
     */
    private $__props_events;

    /**
     * 对象所有属性的引用
     *
     * @var array
     */
    private $__all_props;

    /**
     * 事件钩子
     *
     * @var array
     */
    private static $__callbacks = array();

    /**
     * 扩展的方法
     *
     * @var array
     */
    private static $__methods = array();

    /**
     * 行为插件对象的实例
     *
     * @var array
     */
    private static $__behaviors = array();

    /**
     * 所有用到的 ActiveRecord 对象的定义
     *
     * @var array
     */
    private static $__defines = array();

    /**
     * 构造函数
     *
     * @param array $data
     */
    function __construct(array $data = null)
    {
        $this->__class = get_class($this);
        self::__init($this->__class);

        if (is_array($data)) {
            $this->attach($data);
        } else {
            $this->attach(array());
        }
        $this->__doCallbacks(self::after_initialize);
    }

    /**
     * 保存对象到数据库
     *
     * @param boolean $force_create 是否强制创建新记录
     */
    function save($force_create = false)
    {
        $this->__doCallbacks(self::before_save);
        $id = $this->id();
        if (empty($id) || $force_create) {
            $this->create();
        } else {
            $this->update();
        }
        $this->__doCallbacks(self::after_save);
    }

    /**
     * 验证对象属性
     *
     * @param string $mode
     */
    function validate($mode = 'general')
    {
        $this->__doCallbacks(self::before_validation);
        if ($mode == 'create') {
            $this->__doCallbacks(self::before_validation_on_create);
            $this->__doCallbacks(self::after_validation_on_create);
        } elseif ($mode == 'update') {
            $this->__doCallbacks(self::before_validation_on_update);
            $this->__doCallbacks(self::after_validation_on_update);
        }
        $this->__doCallbacks(self::after_validation);
    }

    /**
     * 销毁对象对应的数据库记录
     */
    function destroy()
    {
        $table = self::$__defines[$this->__class]['table'];
        /* @var $table QDB_Table */
        $this->__doCallbacks(self::before_destroy);
        $table->remove($this->id());
        $this->__doCallbacks(self::after_destroy);
    }

    /**
     * 获得对象ID（对象在数据库中的主键值）
     *
     * @return mixed
     */
    function id()
    {
        $pk = self::$__defines[$this->__class]['pk'];
        return $this->{$pk};
    }

    /**
     * 获得对象的ID属性名（对象在数据库中的主键字段名）
     *
     * @return string
     */
    function idname()
    {
        return self::$__defines[$this->__class]['pk'];
    }

    /**
     * 获得包含对象所有属性的数组
     *
     * @return array
     */
    function toArray()
    {
        $row = array();
        foreach (self::$__defines[$this->__class]['attribs'] as $define) {
            $field = $define['alias'];
            if ($define['assoc']) {
                if (is_array($this->{$field})) {
                    $row[$field] = array();
                    foreach ($this->{$field} as $obj) {
                        $row[$field][] = $obj->toArray();
                    }
                } else {
                    $row[$field] = $this->{$field}->toArray();
                }
            } else {
                $row[$field] = $this->{$field};
            }
        }
        return $row;
    }

    /**
     * 将对象附着到一个包含对象属性的数组，等同于将数组的属性值复制到对象
     *
     * @param array $row
     */
    function attach(array $row)
    {
        foreach (self::$__defines[$this->__class]['attribs'] as $field => $define) {
            if (!isset($row[$field]) && $define['assoc'] == false) {
                $row[$field] = self::$__defines[$this->__class]['attribs'][$field]['default'];
            }

            if ($define['readonly'] || !$define['public'] || $define['assoc']) {
                if ($define['assoc']) {
                    if (!is_array($row[$field])) {
                        // LC_MSG: Property "%s" type mismatch. expected is "%s", actual is "%s".
                        $msg = 'Property "%s" type mismatch. expected is "%s", actual is "%s".';
                        throw new QDB_ActiveRecord_Exception(__($msg, "\$row[{$field}]", 'array', gettype($row[$field])));
                    } else {
                        $this->__props[$field] = new QColl($define['class']);
                        foreach ($row[$field] as $assoc_row) {
                            $this->__props[$field][] = new $define['class']($assoc_row);
                        }
                    }
                } else {
                    $this->__props[$field] = $row[$field];
                }
                $this->__all_props[$field] =& $this->__props[$field];
            } else {
                $this->{$field} = $row[$field];
                $this->__all_props[$field] =& $this->{$field};
            }
        }
    }

    /**
     * 返回该对象使用的表数据入口对象
     *
     * @return QDB_Table
     */
    function getTable()
    {
        return self::$__defines[$this->__class]['table'];
    }

    /**
     * 魔法方法，实现对只读属性和属性方法的支持
     *
     * @param string $varname
     *
     * @return mixed
     */
    function __get($varname)
    {
        if (!isset(self::$__defines[$this->__class]['attribs'][$varname])) {
            // LC_MSG: Property "%s" not defined.
            throw new QDB_ActiveRecord_Exception(__('Property "%s" not defined.', $varname));
        }
        $attr = self::$__defines[$this->__class]['attribs'][$varname];
        if (isset($attr['getter'])) {
            return call_user_func($attr['getter'], $varname);
        }
        return $this->__props[$varname];
    }

    /**
     * 魔法方法，实现只读属性的支持
     *
     * @param string $varname
     * @param mixed $value
     */
    function __set($varname, $value)
    {
        if (!isset(self::$__defines[$this->__class]['attribs'][$varname])) {
            $this->{$varname} = $value;
            return;
        }
        $attr = self::$__defines[$this->__class]['attribs'][$varname];
        if ($attr['readonly']) {
            // LC_MSG: Property "%s" is readonly.
            throw new QException(__('Property "%s" is readonly.', $varname));
        }
        if (isset($attr['setter'])) {
            $this->__all_props[$varname] = call_user_func($attr['setter'], $value);
            return;
        }
        if (!$attr['assoc']) {
            if (isset($this->__props[$varname])) {
                $this->__props[$varname] = $value;
            } else {
                $this->{$varname} = $value;
            }
            return;
        }

        if ($attr['assoc'] == 'has_many' || $attr['assoc'] == 'many_to_many') {
            // 聚合的对象，要求 $value 必须是一个包含特定类型对象的数组
            if (!is_array($value)) {
                // LC_MSG: Property "%s" type mismatch. expected is "%s", actual is "%s".
                $msg = 'Property "%s" type mismatch. expected is "%s", actual is "%s".';
                throw new QDB_ActiveRecord_Exception(__($msg, $varname, 'array', gettype($value)));
            }
            foreach (array_keys($value) as $key) {
                if (!is_object($value[$key]) || !($value[$key] instanceof $attr['class'])) {
                    // LC_MSG: Property "%s[]" type mismatch. expected is "%s", actual is "%s".
                    $msg = 'Property "%s[]" type mismatch. expected is "%s", actual is "%s".';
                    throw new QDB_ActiveRecord_Exception(__($msg, $varname, $attr['class'], gettype($value[$key])));
                }
            }
            $this->__props[$varname] = $value;
        } else {
            if (!is_object($value) || !($value instanceof $attr['class'])) {
                // LC_MSG: Property "%s" type mismatch. expected is "%s", actual is "%s".
                $msg = 'Property "%s" type mismatch. expected is "%s", actual is "%s".';
                throw new QActiveRecord_Exception(__($msg, $varname, $attr['class'], gettype($value)));
            }
            $this->__props[$varname] = $value;
        }
    }

    /**
     * 魔法方法，用于调用行为插件为对象添加的方法
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    function __call($method, $args)
    {
        if (isset(self::$__methods[$this->__class][$method])) {
            array_unshift($args, $this->__all_props);
            array_unshift($args, $this);
            return call_user_func_array(self::$__methods[$this->__class][$method], $args);
        } else {
            // LC_MSG: Call to undefined method "%s::%s()".
            throw new QDB_ActiveRecord_Exception(__('Call to undefined method "%s::%s()".', $this->__class, $method));
        }
    }

    /**
     * 在数据库中创建对象
     */
    protected function create()
    {
        $table = self::$__defines[$this->__class]['table'];
        /* @var $table QDB_Table */
        $this->validate('create');
        $this->__doCallbacks(self::before_create);
        $table->create($this->to_array());
        $this->__doCallbacks(self::after_create);
    }

    /**
     * 更新对象到数据库
     */
    protected function update()
    {
        $table = self::$__defines[$this->__class]['table'];
        /* @var $table QDB_Table */
        $this->validate('update');
        $this->__doCallbacks(self::before_update);
        $table->update($this->to_array());
        $this->__doCallbacks(self::after_update);
    }

    /**
     * 开启一个查询
     *
     * @param string $class
     * @param array $args
     *
     * @return QDB_ActiveRecord_Select
     */
    protected static function __find($class, array $args)
    {
        self::__init($class);
        $select = new QDB_ActiveRecord_Select(
            $class,
            self::$__defines[$class]['table'],
            self::$__defines[$class]['attribs'],
            self::$__defines[$class]['links']
        );
        if (!empty(self::$__callbacks[$class][self::after_find])) {
            $select->bindCallbacks(self::$__callbacks[$class][self::after_find]);
        }
        if (!empty($args)) {
            call_user_func_array(array($select, 'where'), $args);
        }
        return $select;
    }

    /**
     * 调用指定类型的 callback 方法
     */
    protected function __doCallbacks($type)
    {
        if (!isset(self::$__callbacks[$this->__class][$type])) { return; }
        foreach (self::$__callbacks[$this->__class][$type] as $callback) {
            call_user_func_array($callback, array($this, $this->__all_props));
        }
    }

    /**
     * 初始化指定 QDB_ActiveRecord_Abstract 继承类的定义
     *
     * @param string $class
     */
    private static function __init($class)
    {
        if (isset(self::$__defines[$class])) { return; }
        $class_define = call_user_func(array($class, '__define'));
        if (!is_array($class_define['fields'])) {
            $class_define['fields'] = array();
        }

        // 构造表数据入口
        if (!empty($class_define['table_name'])) {
            // 通过 table_name 指定数据表
            $obj_id = 'activerecord_table_' . strtolower($class);
            if (Q::isRegistered($obj_id)) {
                $class_define['table'] = Q::registry($obj_id);
            } else {
                Q::loadClass('QDB_Table');
                $params = array('table_name' => $class_define['table_name']);
                foreach ($class_define as $key => $value) {
                    if (substr($key, 0, 6) == 'table_' && $key != 'table_name') {
                        $params[substr($key, 6)] = $value;
                    }
                }
                $class_define['table'] = new QDB_Table($params, true);
                $class_define['pk'] = $class_define['table']->pk;
                Q::register($class_define['table'], $obj_id);
            }
        } elseif (!empty($class_define['table_class'])) {
            $class_define['table'] = Q::getSingleton($class_define['table_class']);
            $class_define['table']->connect();
            $class_define['pk'] = $class_define['table']->pk;
        }

        // 绑定行为插件
        self::$__callbacks[$class] = array();
        self::$__methods[$class] = array();

        $behaviors = isset($class_define['behaviors']) ? Q::normalize($class_define['behaviors']) : array();
        foreach ($behaviors as $behavior) {
            $behavior_class = 'Behavior_' . ucfirst(strtolower($behavior));
            $dirs = array(Q_DIR . DS . 'qdb' . DS . 'activerecord');
            if (!Q::isRegistered($behavior_class)) {
                Q::loadClass($behavior_class, $dirs);
                $behavior_obj = new $behavior_class($class);
                Q::register($behavior_obj, $behavior_class);
            } else {
                $behavior_obj = Q::registry($behavior_class);
            }
            /* @var $behavior_obj QDB_ActiveRecord_Behavior_Interface */
            $callbacks = $behavior_obj->__callbacks();
            self::$__behaviors[$behavior] = $behavior_obj;

            foreach ($callbacks as $call) {
                list($type, $method) = $call;
                switch ($type) {
                case self::custom_callback:
                    if (is_array($method)) {
                        // array($obj/$class, $method) 形式的 callback
                        self::$__methods[$class][$method[1]] = $method;
                    } else {
                        self::$__methods[$class][$method] = $method;
                    }
                    break;
                case self::setter:
                    $class_define['fields'][$method] = array('setter' => array($behavior_obj, 'set' . ucfirst($method)));
                    break;
                case self::getter:
                    $class_define['fields'][$method] = array('getter' => array($behavior_obj, 'get' . ucfirst($method)));
                    break;
                default:
                    self::$__callbacks[$class][$type][] = $method;
                }
            }
        }

        // 根据字段定义确定字段属性
        $meta = $class_define['table']->columns();
        $class_define['links'] = array();
        if (isset($class_define['fields']) && is_array($class_define['fields'])) {
            foreach ($class_define['fields'] as $field => $options) {
                $define = array('public' => true, 'readonly' => false, 'assoc' => false);

                if (!is_array($options)) {
                    // 假定为别名
                    $define['alias'] = $options;
                } else {
                    $define['readonly'] = isset($options['readonly']) ? (bool)$options['readonly'] : false;
                    if (isset($options['setter'])) {
                        $define['public'] = false;
                        $define['setter'] = $options['setter'];
                    }
                    if (isset($options['getter'])) {
                        $define['public'] = false;
                        $define['getter'] = $options['getter'];
                    }
                    if (isset($options['alias'])) {
                        $define['alias'] = $options['alias'];
                    } else {
                        $define['alias'] = $field;
                    }

                    if (isset($options['has_one'])) {
                        $define['assoc'] = 'has_one';
                        $define['class'] = $options['has_one'];
                    }
                    if (isset($options['has_many'])) {
                        $define['assoc'] = 'has_many';
                        $define['class'] = $options['has_many'];
                    }
                    if (isset($options['belongs_to'])) {
                        $define['assoc'] = 'belongs_to';
                        $define['class'] = $options['belongs_to'];
                    }
                    if (isset($options['many_to_many'])) {
                        $define['assoc'] = 'many_to_many';
                        $define['class'] = $options['many_to_many'];
                    }
                    if ($define['assoc']) {
                        unset($options['readonly']);
                        unset($options['alias']);
                        unset($options['setter']);
                        unset($options['getter']);
                        $define['assoc_options'] = $options;
                        $class_define['links'][$field] = $define;
                    }
                }

                // 根据 META 确定属性的默认值
                if (isset($meta[$field]) && $meta[$field]['has_default']) {
                    $define['default'] = $meta[$field]['default'];
                } else {
                    $define['default'] = null;
                }
                $attribs[$field] = $define;
            }
        }

        // 将没有指定的字段也设置为对象属性
        foreach ($meta as $key => $field) {
            if (!isset($attribs[$key])) {
                $attribs[$key] = array(
                    'public'    => true,
                    'readonly'  => false,
                    'alias'     => $key,
                    'assoc'     => false,
                    'default'   => ($field['has_default']) ? $field['default'] : null,
                );
            }
        }
        $class_define['attribs'] = $attribs;
        unset($class_define['fields']);

        self::$__defines[$class] = $class_define;
    }
}
