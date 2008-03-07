<?php echo '<?php'; ?>


class <?php echo $class_name; ?> extends QDB_ActiveRecord_Abstract
{
    /**
     * 开启一个查询，查找符合条件的对象或对象集合
     *
     * @static
     *
     * @return QDB_ActiveRecord_Select
     */
    static function find()
    {
        $args = func_get_args();
        return parent::__find(__CLASS__, $args);
    }

    /**
     * 删除符合条件的对象，返回成功删除的对象的数量
     *
     * @static
     *
     * @param mixed $where
     *
     * @return int
     */
    static function deleteWhere($where)
    {
        $args = func_get_args();
        array_shift($args);
        return parent::__deleteWhere(__CLASS__, $where, $args);
    }

    /**
     * 实例化所有符合条件的对象，并调用这些对象的 destroy() 方法，返回成功删除的对象的数量
     *
     * @static
     *
     * @param mixed $where
     *
     * @return int
     */
    static function destroyWhere()
    {
        $args = func_get_args();
        array_shift($args);
        return parent::__destroyWhere(__CLASS__, $where, $args);
    }

    /**
     * 返回对象的定义
     *
     * @static
     *
     * @return array
     */
    static function __define()
    {
        return array(
            // 指定该 ActiveRecord 要使用的行为插件
            'behaviors' => '',

<?php if (empty($table_class)): ?>
            // 用什么数据表保存对象的数据
            'table_name' => '<?php echo $table_name; ?>',
<?php else: ?>
            // 用什么表数据入口处理对象的持久化
            'table_class' => '<?php echo $table_class; ?>',
<?php endif; ?>

            // 指定数据表记录字段与对象属性之间的映射关系
            // 没有在此处指定的字段，QeePHP 会自动设置将字段映射为对象的可读写属性
            'fields' => array(
                // 主键应该是只读，确保领域对象的“不变量”
<?php foreach ($pk as $p): ?>
                '<?php echo $p; ?>' => array('readonly' => true),
<?php endforeach; ?>
<?php if (isset($meta['created'])): ?>
                // 对象创建时间应该是只读
                'created' => array('readonly' => true),
<?php endif; ?>
<?php if (isset($meta['updated'])): ?>
                // 对象最后更新时间应该是只读
                'updated' => array('readonly' => true),
<?php endif; ?>
            ),
        );
    }
}
