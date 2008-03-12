<?php echo '<?php'; ?>

<?php if (!empty($namespace)): ?>

// namespace: <?php echo $namespace; ?>

<?php endif; ?>

/**
 * <?php echo $class_name; ?> 是用于处理 __ 的控制器
 *
 * @package app
 */
class <?php echo $class_name; ?> extends QController_Abstract
{
    /**
     * 默认动作
     */
    function actionIndex()
    {
        /**
         * 要传递到视图的数据，可以直接赋值给 $this->view。
         *
         * 为了便于在视图中使用这些数据，$this->view 应该是一个数组，键名对应视图中的变量名。
         */
        $this->view = array('text' => 'Hello!');
    }
}
