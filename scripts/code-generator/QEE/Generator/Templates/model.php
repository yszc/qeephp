<?php echo '<?php'; ?>

require_once SYSTEM_DIR . '/IActiveRecord.php';

class <?php echo $className; ?> implements IActiveRecord
{
<?php
foreach ($propertiesMapping as $prop => $field):
?>
    /**
     * <?php echo isset($field['description']) ? $field['description'] : 'Table field: ' . $field['name']; echo "\n"; ?>
     *
     * @var <?php echo $field['phpType']; echo "\n"; ?>
     */
    public $<?php echo $prop; ?> = null;

<?php endforeach; ?>

    public static function __setupORM()
    {
        return array(
<?php if ($tableClass != 'FLEA_Db_TableDataGateway'): ?>
            'tableClass' => '<?php echo $tableClass; ?>',
<?php endif; ?>
            'propertiesMapping' => array(
<?php
foreach ($propertiesMapping as $prop => $field):
$spc = str_repeat(' ', $len - strlen($prop));
?>
                '<?php echo $prop; ?>'<?php echo $spc; ?> => '<?php echo $field['name']; ?>',
<?php endforeach; ?>
            ),
        );
    }

    public function __getID()
    {
        return $this-><?php echo $propPrimaryKey; ?>;
    }

    public function __setID($id)
    {
        $this-><?php echo $propPrimaryKey; ?> = $id;
    }


}
