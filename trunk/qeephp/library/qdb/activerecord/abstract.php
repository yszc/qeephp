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
    private static $__ref = array();

    /**
     * 构造函数
     *
     * @param array $data
     */
    function __construct(array $data = null)
    {
        $this->__class = get_class($this);
        self::reflection($this->__class);

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
     * 从数据库重新读取对象
     *
     * @param int $recursion
     */
    function reload($recursion = 0)
    {
        $arr = $this->getTable()->find(array($this->idname() => $this->id()))->recursion($recursion)->asArray()->query();
        $this->attach($arr);
    }

    /**
     * 验证对象属性
     *
     * @param string $mode
     */
    function doValidate($mode = 'general')
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
        $table = self::$__ref[$this->__class]['table'];
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
        $pk = self::$__ref[$this->__class]['pk'];
        return $this->{$pk};
    }

    /**
     * 获得对象的ID属性名（对象在数据库中的主键字段名）
     *
     * @return string
     */
    function idname()
    {
        return self::$__ref[$this->__class]['pk'];
    }

    /**
     * 获得包含对象所有属性的数组
     *
     * @return array
     */
    function toArray()
    {
        $row = array();
        $attribs = self::$__ref[$this->__class]['attribs'];
        // ralias 是 别名 => 实际字段名
        $ralias = self::$__ref[$this->__class]['ralias'];

        foreach (array_keys($this->__all_props) as $a) {
            $f = $ralias[$a];
            if ($attribs[$f]['assoc']) {
                if (is_array($this->__props[$a]) || $this->__props[$a] instanceof Iterator) {
                    $row[$f] = array();
                    foreach ($this->__props[$a] as $obj) {
                        $row[$f][] = $obj->toArray();
                    }
                } else {
                    $row[$f] = $this->__props[$a]->toArray();
                }
            } else {
                $row[$f] = isset($this->__props[$a]) ? $this->__props[$a] : $this->{$a};
            }
        }
        return $row;
    }

    /**
     * 返回对象所有属性的 JSON 字符串
     *
     * @return string
     */
    function toJSON()
    {
        return json_encode($this->toArray());
    }

    /**
     * 返回该对象使用的表数据入口对象
     *
     * @return QDB_Table
     */
    function getTable()
    {
        return self::$__ref[$this->__class]['table'];
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
        if (!isset(self::$__ref[$this->__class]['attribs'][$varname])) {
            // LC_MSG: Property "%s" not defined.
            throw new QDB_ActiveRecord_Exception(__('Property "%s" not defined.', $varname));
        }
        $attr = self::$__ref[$this->__class]['attribs'][$varname];
        if (isset($attr['getter'])) {
            if (!is_array($attr['getter'])) {
                $attr['getter'] = array($this, $attr['getter']);
            }
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
        if (!isset(self::$__ref[$this->__class]['attribs'][$varname])) {
            $this->{$varname} = $value;
            return;
        }
        $attr = self::$__ref[$this->__class]['attribs'][$varname];
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
     * 为一个 ActiveRecord 类定义一个关联
     *
     * @param string $class
     * @param string $field
     * @param array $options
     */
    static function link($class, $field, array $options)
    {
        self::reflection($class);

        $define = array('public' => false);
        $define['readonly'] = isset($options['readonly']) ? $options['readonly'] : false;
        $define['alias']    = isset($options['alias'])    ? $options['alias']    : $field;
        $define['getter']   = isset($options['getter'])   ? $options['getter']   : null;
        $define['setter']   = isset($options['setter'])   ? $options['setter']   : null;
        $define['default']  = null;

        if (!empty($options['has_one'])) {
            $define['assoc'] = 'has_one';
            $define['class'] = $options['has_one'];
        }
        if (!empty($options['has_many'])) {
            $define['assoc'] = 'has_many';
            $define['class'] = $options['has_many'];
        }
        if (!empty($options['belongs_to'])) {
            $define['assoc'] = 'belongs_to';
            $define['class'] = $options['belongs_to'];
        }
        if (!empty($options['many_to_many'])) {
            $define['assoc'] = 'many_to_many';
            $define['class'] = $options['many_to_many'];
        }

        unset($options['readonly']);
        unset($options['alias']);
        unset($options['setter']);
        unset($options['getter']);
        $define['assoc_options'] = $options;

        self::$__ref[$class]['attribs'][$field] = $define;
        self::$__ref[$class]['alias'][$field] = $define['alias'];
        self::$__ref[$class]['ralias'][$define['alias']] = $field;
        self::$__ref[$class]['links'][$field] = $define;
    }

    /**
     * 获得一个模型类的反射信息
     *
     * @param string $class
     *
     * @return array
     */
    static function reflection($class)
    {
        if (isset(self::$__ref[$class])) {
            return self::$__ref[$class];
        }

        log_message('reflection of class: '. $class, 'debug');
        Q::loadClass($class);
        $ref = call_user_func(array($class, '__define'));
        if (empty($ref['fields']) || !is_array($ref['fields'])) {
            $ref['fields'] = array();
        }
        if (empty($ref['validation']) || !is_array($ref['validation'])) {
            $ref['validation'] = array();
        }
        if (!empty($ref['create_reject'])) {
            $ref['create_reject'] = Q::normalize($ref['create_reject']);
        } else {
            $ref['create_reject'] = array();
        }
        if (!empty($ref['update_reject'])) {
            $ref['update_reject'] = Q::normalize($ref['update_reject']);
        } else {
            $ref['update_reject'] = array();
        }

        // 构造表数据入口
        if (!empty($ref['table_name'])) {
            // 通过 table_name 指定数据表
            $obj_id = 'activerecord_table_' . strtolower($class);
            if (Q::isRegistered($obj_id)) {
                $ref['table'] = Q::registry($obj_id);
            } else {
                Q::loadClass('QDB_Table');
                $params = array('table_name' => $ref['table_name']);
                foreach ($ref as $key => $value) {
                    if (substr($key, 0, 6) == 'table_' && $key != 'table_name') {
                        $params[substr($key, 6)] = $value;
                    }
                }
                $ref['table'] = new QDB_Table($params, true);
                Q::register($ref['table'], $obj_id);
            }
        } elseif (!empty($ref['table_class'])) {
            $ref['table'] = Q::getSingleton($ref['table_class']);
            $ref['table']->connect();
        }
        $ref['pk'] = $ref['table']->pk;

        // 绑定行为插件
        self::$__callbacks[$class] = array();
        self::$__methods[$class] = array();

        $behaviors = isset($ref['behaviors']) ? Q::normalize($ref['behaviors']) : array();
        foreach ($behaviors as $behavior) {
            $behavior = strtolower($behavior);
            $behavior_class = 'Behavior_' . ucfirst($behavior);

            if (!class_exists($behavior_class, false)) {
                $dir = Q_DIR . '/qdb/activerecord/behavior';
                $filename = $behavior . '_behavior.php';
                Q::loadFile($filename, true, array($dir));
            }
            if (!empty($ref['behaviors_settings'][$behavior])) {
                $settings = $ref['behaviors_settings'][$behavior];
            } else {
                $settings = array();
            }
            $behavior_obj = new $behavior_class($class, $settings);
            /* @var $behavior_obj QDB_ActiveRecord_Behavior_Interface */
            $callbacks = $behavior_obj->__callbacks($class);
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
                    $ref['fields'][$method] = array('setter' => array($behavior_obj, 'set' . ucfirst($method)));
                    break;
                case self::getter:
                    $ref['fields'][$method] = array('getter' => array($behavior_obj, 'get' . ucfirst($method)));
                    break;
                default:
                    self::$__callbacks[$class][$type][] = $method;
                }
            }
        }

        // 根据字段定义确定字段属性
        $meta = $ref['table']->columns();
        $ref['links'] = array();
        $ref['alias'] = array();
        if (isset($ref['fields']) && is_array($ref['fields'])) {
            foreach ($ref['fields'] as $field => $options) {
                $define = array('public' => true, 'readonly' => false, 'assoc' => false);

                if (!is_array($options)) {
                    // 假定为别名
                    $define['alias'] = $options;
                } else {
                    $define['readonly'] = isset($options['readonly']) ? (bool)$options['readonly'] : false;
                    if (!empty($options['setter'])) {
                        $define['public'] = false;
                        $define['setter'] = $options['setter'];
                    }
                    if (!empty($options['getter'])) {
                        $define['public'] = false;
                        $define['getter'] = $options['getter'];
                    }
                    if (!empty($options['alias'])) {
                        $ref['alias'][$field] = $options['alias'];
                    } else {
                        $ref['alias'][$field] = $field;
                    }
                    $define['alias'] = $ref['alias'][$field];

                    if (!empty($options['has_one'])) {
                        $define['assoc'] = 'has_one';
                        $define['class'] = $options['has_one'];
                    }
                    if (!empty($options['has_many'])) {
                        $define['assoc'] = 'has_many';
                        $define['class'] = $options['has_many'];
                    }
                    if (!empty($options['belongs_to'])) {
                        $define['assoc'] = 'belongs_to';
                        $define['class'] = $options['belongs_to'];
                    }
                    if (!empty($options['many_to_many'])) {
                        $define['assoc'] = 'many_to_many';
                        $define['class'] = $options['many_to_many'];
                    }
                    if ($define['assoc']) {
                        unset($options['readonly']);
                        unset($options['alias']);
                        unset($options['setter']);
                        unset($options['getter']);
                        $define['assoc_options'] = $options;
                        $ref['links'][$field] = $define;
                    }
                }

                // 根据 META 确定属性的默认值
                if (!empty($meta[$field]) && $meta[$field]['has_default']) {
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
                $ref['alias'][$key] = $key;
                $attribs[$key] = array(
                    'public'    => true,
                    'readonly'  => false,
                    'assoc'     => false,
                    'alias'     => $key,
                    'default'   => ($field['has_default']) ? $field['default'] : null,
                );
            }
        }
        $ref['attribs'] = $attribs;
        $ref['ralias'] = array_flip($ref['alias']);
        unset($ref['fields']);

        self::$__ref[$class] = $ref;
        return $ref;
    }

    /**
     * 将对象附着到一个包含对象属性的数组，等同于将数组的属性值复制到对象
     *
     * @param array $row
     */
    private function attach(array $row)
    {
        $alias = self::$__ref[$this->__class]['alias'];
        foreach (self::$__ref[$this->__class]['attribs'] as $field => $define) {
            if (!isset($row[$field]) && $define['assoc'] == false) {
                $row[$field] = self::$__ref[$this->__class]['attribs'][$field]['default'];
            }

            $a = isset($alias[$field]) ? $alias[$field] : $field;

            if ($define['readonly'] || !$define['public'] || $define['assoc']) {
                if ($define['assoc']) {
                    if ($define['assoc'] == 'has_many' ||  $define['assoc'] == 'many_to_many') {
                        $this->__props[$a] = new QColl($define['class']);
                    }
                    if (!isset($row[$field])) {
                        $this->__props[$a] = null;
                        continue;
                    } elseif (!is_array($row[$field])) {
                        // LC_MSG: Property "%s" type mismatch. expected is "%s", actual is "%s".
                        $msg = 'Property "%s" type mismatch. expected is "%s", actual is "%s".';
                        throw new QDB_ActiveRecord_Exception(__($msg, "\$row[{$field}]", 'array', gettype($row[$field])));
                    } else {
                        if ($define['assoc'] == 'has_one' ||  $define['assoc'] == 'belongs_to') {
                            $this->__props[$a] = new $define['class']($row[$field]);
                        } else {
                            foreach ($row[$field] as $assoc_row) {
                                $this->__props[$a][] = new $define['class']($assoc_row);
                            }
                        }
                    }
                    $this->__all_props[$a] =& $this->__props[$a];
                } else {
                    $this->__props[$a] = $row[$field];
                    $this->__all_props[$a] =& $this->__props[$a];
                }
            } else {
                $this->{$a} = $row[$field];
                $this->__all_props[$a] =& $this->{$a};
            }
        }
    }

    /**
     * 在数据库中创建对象
     */
    protected function create()
    {
        $table = self::$__ref[$this->__class]['table'];
        /* @var $table QDB_Table */
        $this->doValidate('create');
        $this->__doCallbacks(self::before_create);
        $row = $this->toArray();
        foreach (self::$__ref[$this->__class]['create_reject'] as $f) {
            unset($row[$f]);
        }
        $id = $table->create($row);
        $this->__all_props[$this->idname()] = $id;
        $this->__doCallbacks(self::after_create);
    }

    /**
     * 更新对象到数据库
     */
    protected function update()
    {
        $table = self::$__ref[$this->__class]['table'];
        /* @var $table QDB_Table */
        $this->doValidate('update');
        $this->__doCallbacks(self::before_update);
        $row = $this->toArray();
        foreach (self::$__ref[$this->__class]['update_reject'] as $f) {
            unset($row[$f]);
        }
        $table->update($row);
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
        self::reflection($class);
        $select = new QDB_ActiveRecord_Select(
            $class,
            self::$__ref[$class]['table'],
            self::$__ref[$class]['attribs'],
            self::$__ref[$class]['links']
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
     * 对数据进行验证，返回所有未通过验证数据的名称错误信息
     *
     * @param string $class
     * @param array $data
     * @param array|string $fields
     *
     * @return array
     */
    protected static function __validate($class, array $data, $fields = null)
    {
        $ref = self::reflection($class);
        $v = new QValidate();
        return $v->groupCheck($data, $ref['validation'], $fields);
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
}
