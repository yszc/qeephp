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
 * 定义 Table_Authors 类
 *
 * @package test_fixture
 * @version $Id$
 */

/**
 * Table_Authors 封装了对 authors 表的操作
 *
 * @package test_fixture
 */
class Table_Authors extends QTable_Base
{
    public $table_name = 'authors';
    public $pk = 'author_id';

    protected $has_many = array(
        /**
         * 每个作者拥有多个内容
         */
        array(
            'table_class' => 'Table_Contents',
            'mapping_mame' => 'contents',
            'link_field' => 'author_id',

            /**
             * 指示在读取作者记录时，是否读取关联的内容记录
             *
             * all/find/true* - 读取所有关联记录
             * skip/false     - 跳过，不读取内容记录
             * 整数           - 仅读取指定个数的内容记录，例如 on_find = 5 表示仅读取每个作者的 5 个内容记录
             * 数组           - 包含读取起始位置和要读取的个数，例如 array($offset, $nums)
             *
             * 应该尽量避免读取 has many 关联记录
             * 否则可能因为关联记录太多而影响应用程序性能
             */
            'on_find' => 5,

            /**
             * 指示按照什么排序规则查询关联的内容记录
             */
            'on_find_order' => '[created] ASC',

            /**
             * 指示在删除作者记录时，如何处理关联的内容记录
             *
             * delete/true  - 删除所有的关联记录
             * fill         - 将关联记录的外键字段设置为指定的值
             * skip/false   - 不处理关联记录
             *
             * 对于 has one、has many，删除关联记录是 on_delete 的默认行为
             */
            'on_delete' => 'delete',

            /**
             * 指示在保存作者记录时，是否保存关联的内容记录
             *
             * save/true    - 根据关联记录是否具有主键值来决定是创建记录还是更新现有记录
             * create       - 强制创建新记录
             * update       - 强制更新记录
             * replace      - 使用数据库的 replace 操作来尝试替换记录
             * skip/false   - 不处理关联记录
             * only_create  - 仅仅保存需要创建的记录（根据是否具备主键值判断）
             * only_update  - 仅仅保存需要更新的记录（根据是否具备主键值判断）
             */
            'on_save' => 'skip',
        ),

        /**
         * 每个作者拥有多个评论
         */
        array(
            'table_class' => 'Table_Comments',
            'mapping_name' => 'comments',
            'link_field' => 'author_id',

            /**
             * 指示在删除作者记录时，
             *
             * on_delete 为 fill 时，表示不删除该作者的评论记录，
             * 而是在评论记录的 author_id（由 foreign_key 属性决定）字段
             * 填充特定值（由 on_delete_fill 属性决定）
             *
             *
             */
            'on_delete'      => 'fill',

            /**
             * 要填充的值
             */
            'on_delete_fill' => 0,
        ),
    );
}
