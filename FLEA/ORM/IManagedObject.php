<?php

/**
 * 需要通过 ORM 管理的对象，都必须实现 FLEA_ORM_IManagedObject 接口
 *
 * @package System
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
interface FLEA_ORM_IManagedObject
{
    /**
     * 返回一个设置对象的 ORM 属性的数组
     *
     * 必须设置的属性是 tableClass 或者 tableName，两者只能设置其中一个。如果两个选项都提供，则只使用 tableClass。
     * tableClass 指示由哪一个表数据入口负责该对象的持久化；tableName 指示用哪一个数据表保存对象。
     *
     * 可选的属性：
     * - propertiesMapping 指示对象属性和数据表字段之间的映射关系，格式为“属性名 => 字段名”
     */
    public static function __setupORM();

    /**
     * 返回对象对应的数据库记录的主键值，如果是未保存的对象，应该返回 null
     *
     * @return mixed
     */
    public function __getID();

    /**
     * 设置对象对应的数据库记录的主键值，如果是未保存的对象，$id 参数为 null
     *
     * @param mixed $id
     */
    public function __setID($id);
}
