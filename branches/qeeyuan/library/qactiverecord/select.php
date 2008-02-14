<?php


class QActiveRecord_Select
{
    /**
     * @var string
     */
    protected $class;

    /**
     * ActiveRecord 对象的属性和字段影射
     *
     * @var array
     */
    protected $attribs;

    /**
     * 查询参数
     *
     * @var array
     */
    protected $params;

    /**
     * @var QTable_Base
     */
    protected $table;

    /**
     * 构造函数
     *
     * @param string $class
     * @param QTable_Base $table
     * @param array $attribs
     */
    function __construct($class, QTable_Base $table, array $attribs)
    {
        $this->class = $class;
        $this->table = $table;
        $this->attribs = $attribs;
        $this->reset();
    }

    /**
     * 添加查询条件
     *
     * @param mixed $where
     *
     * @return QActiveRecord_Select
     */
    function where($where)
    {
        if (func_num_args() > 1) {
            $vals = func_get_arg(1);
            $where = $this->table->get_dbo()->qinto($where, $vals);
        }
        if (!empty($this->params['where'])) {
            $this->params['where'][] = "AND ({$where})";
        } else {
            $this->params['where'][] = "({$where})";
        }
        return $this;
    }

    /**
     * 设置查询的排序方式
     *
     * @param mixed $order
     *
     * @return QActiveRecord_Select
     */
    function order($order)
    {
        $this->params['order'] = $order;
        return $this;
    }

    /**
     * 查询所有符合条件的记录
     *
     * @return QActiveRecord_Select
     */
    function all()
    {
        $this->params['limit'] = null;
        return $this;
    }

    /**
     * 限制查询结果总数
     *
     * @param int $count
     * @param int $offset
     *
     * @return QActiveRecord_Select
     */
    function limit($count, $offset = 0)
    {
        $this->params['limit'] = array($count, $offset);
        return $this;
    }

    /**
     * 统计符合条件的记录数
     *
     * @return QActiveRecord_Select
     */
    function count()
    {
        $this->params['count'] = true;
        return $this;
    }

    /**
     * 执行查询
     *
     * @return mixed
     */
    function query()
    {
        $params = $this->params;
        $where = implode(' ', $this->params['where']);
        unset($params['where']);

        $data = $this->table->find($where, $params)->query();
        if (is_array($data)) {
            if ($this->params['limit'] == 1) {
                return new $this->class($data);
            } else {
                $objects = array();
                foreach ($data as $row) {
                    $objects[] = new $this->class($row);
                }
                return $objects;
            }
        } else {
            return $data;
        }
    }

    /**
     * 重置所有查询选项
     *
     * @return QActiveRecord_Select
     */
    function reset()
    {
        $this->params = array(
            'limit' => 1,
            'count' => false,
            'order' => null,
            'where' => array(),
        );
        return $this;
    }
}
