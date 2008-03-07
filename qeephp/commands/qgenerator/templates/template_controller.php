<?php echo '<?php'; ?>


class <?php echo $class_name; ?> extends QController_Abstract
{
    /**
     * 当前控制器要使用的助手
     *
     * @var array|string
     */
    protected $helper = '';

    /**
     * default action
     */
    function actionIndex()
    {
        return array(
            'text' => 'Hello!',
        );
    }
}
