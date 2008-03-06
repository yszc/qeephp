<?php echo '<?php'; ?>


class <?php echo $modelClass; ?> extends ActiveRecord
{

<?php foreach ($mapping as $prop => $field): ?>
    /**
     * <?php echo !empty($field['description']) ? $field['description'] : 'Table field: ' . $field['name']; echo "\n"; ?>
     *
     * @var <?php echo $field['phpType']; echo "\n"; ?>
     */
    public $<?php echo $prop; ?> = null;

<?php endforeach; ?>

    /**
     * 用于查找对象的静态方法
     *
     * @return <?php echo $modelClass; echo "\n"; ?>
     */
    static function find()
    {
        $args = func_get_args();
        return parent::__find(__CLASS__, $args);
    }

    /**
     * 返回对象对应的表数据入口，及对象聚合等信息
     *
     * @return array
     */
    static function define()
    {
        return array(
            'tableClass' => '<?php echo $tableClass; ?>',
            'idname' => '<?php echo $idname; ?>',
            'propertiesMapping' => array(
<?php
foreach ($mapping as $prop => $field):
$spc = str_repeat(' ', $len - strlen($prop));
?>
                '<?php echo $prop; ?>'<?php echo $spc; ?> => '<?php echo $field['name']; ?>',
<?php endforeach; ?>
            ),
        );
    }
}
