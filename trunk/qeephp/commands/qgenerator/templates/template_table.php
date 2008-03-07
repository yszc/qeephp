<?php echo '<?php'; ?>


class <?php echo $class_name; ?> extends QDB_Table
{
    public $table_name = '<?php echo $table_name; ?>';
    public $pk = '<?php echo implode(', ', $pk); ?>';
}
