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
 * 针对表数据入口的单元测试（多表关联的 CRUD 操作）
 *
 * @package test
 * @version $Id$
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../init.php';

class Test_QTable_Links extends PHPUnit_Framework_TestCase
{
    function test_FindBelongsTo()
    {
        $tableAuthors = Q::getSingleton('Table_Authors');
        /* @var $tableAuthors Table_Authors */
        $tableAuthors->getDBO()->startTrans();

        $authors = $this->insertAuthors();

        $tableContents = Q::getSingleton('Table_Contents');
        /* @var $tableContents Table_Contents */

        $tableContents->disableLinks('comments, marks, tags');
        $content = array(
            'title' => '测试标题',
            'author_id' => $authors['liaoyulei'],
        );
        $id = $tableContents->create($content);
        $find = $tableContents->find($id)->query();
        $tableContents->getDBO()->completeTrans(false);

        QDebug::dump($find);

        $this->assertEquals($content['title'], $find['title'], "\$find['title'] == \$content['title']");
        $this->assertTrue(!empty($find['author']), "!empty(\$find['author'])");
        $this->assertType('array', $find['author'], "type of \$find['author'] == array");
        $this->assertTrue(!empty($find['author']['author_id']), "!empty(\$find['author']['author_id'])");
        $this->assertEquals($authors['liaoyulei'], $find['author']['author_id'], "\$find['author']['author_id'] == \$authors['liaoyulei']");
        $this->assertEquals('liaoyulei', $find['author']['name'], "\$find['author']['name'] == 'liaoyulei'");
    }

    function test_FindHasMany()
    {
        $tableAuthors = Q::getSingleton('Table_Authors');
        /* @var $tableAuthors Table_Authors */
        $tableAuthors->getDBO()->startTrans();
        $tableAuthors->disableLinks('books');

        $authors = $this->insertAuthors();
        $contents = $this->insertContents($authors);
        $this->insertComments($authors, $contents);

        $author = $tableAuthors->find($authors['dali'])->query();
        QDebug::dump($author);

        $tableAuthors->getDBO()->completeTrans(false);
    }

    /**
     * @return array
     */
    protected function insertAuthors()
    {
        $tableAuthors = Q::getSingleton('Table_Authors');
        /* @var $tableAuthors Table_Authors */

        $authors = array(
            'liaoyulei' => $tableAuthors->create(array('name' => 'liaoyulei')),
            'dali'      => $tableAuthors->create(array('name' => 'dali')),
            'xiecong'   => $tableAuthors->create(array('name' => 'xiecong')),
        );

        return $authors;
    }

    protected function insertContents(array $authors, $nums = 20)
    {
        $tableContents = Q::getSingleton('Table_Contents');
        /* @var $tableContents Table_Contents */
        $authors = array_values($authors);
        $authors_count = count($authors);

        $contents = array();
        for ($i = 0; $i < $nums; $i++) {
            $content = array(
                'title' => 'TITLE ' . mt_rand(),
                'author_id' => $authors[$i % $authors_count],
            );
            $contents[] = $tableContents->create($content);
        }

        return $contents;
    }

    protected function insertComments(array $authors, array $contents, $nums = 80)
    {
        $tableComments = Q::getSingleton('Table_Comments');
        /* @var $tableComments Table_Comments */
        $authors = array_values($authors);
        $authors_count = count($authors);
        $contents = array_values($contents);
        $contents_count = count($contents);

        $comments = array();
        for ($i = 0; $i < $nums; $i++) {
            $comment = array(
                'author_id' => $authors[$i % $authors_count],
                'content_id' => $contents[$i % $contents_count],
                'body' => 'BODY ' . mt_rand(),
            );
            $comments[] = $tableComments->create($comment);
        }

        return $comments;
    }
}
