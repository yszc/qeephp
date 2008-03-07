<?php echo '<?php'; ?>


class <?php echo $class_name; ?> extends QController_Abstract
{
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
