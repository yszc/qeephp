<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 Helper_Pager 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * Helper_Pager 类提供数据查询分页功能
 *
 * Helper_Pager 使用很简单，只需要构造时传入 Db_Table 实例以及查询条件即可。
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.0
 */
class Db_Pager
{
    /**
     * 如果 $this->source 是一个 Db_Table 对象，则调用
     * $this->source->findAll() 来获取记录集。
     *
     * 否则通过 $this->dbo->select_limit() 来获取记录集。
     *
     * @var Table
     */
    protected $source;

    /**
     * 数据库访问对象，当 $this->source 参数为 SQL 语句时，必须调用
     * $this->set_dbo() 设置查询时要使用的数据库访问对象。
     *
     * @var SDBO
     */
    protected $dbo;

    /**
     * 查询条件
     *
     * @var mixed
     */
    protected $_conditions;

    /**
     * 排序
     *
     * @var string
     */
    protected $_sortby;

    /**
     * 计算实际页码时的基数
     *
     * @var int
     */
    protected $_basePageIndex = 0;

    /**
     * 每页记录数
     *
     * @var int
     */
    protected $pageSize = -1;

    /**
     * 数据表中符合查询条件的记录总数
     *
     * @var int
     */
    protected $totalCount = -1;

    /**
     * 数据表中符合查询条件的记录总数
     *
     * @var int
     */
    protected $count = -1;

    /**
     * 符合条件的记录页数
     *
     * @var int
     */
    protected $pageCount = -1;

    /**
     * 第一页的索引，从 0 开始
     *
     * @var int
     */
    protected $firstPage = -1;

    /**
     * 第一页的页码
     *
     * @var int
     */
    protected $firstPageNumber = -1;

    /**
     * 最后一页的索引，从 0 开始
     *
     * @var int
     */
    protected $lastPage = -1;

    /**
     * 最后一页的页码
     *
     * @var int
     */
    protected $lastPageNumber = -1;

    /**
     * 上一页的索引
     *
     * @var int
     */
    protected $prevPage = -1;

    /**
     * 上一页的页码
     *
     * @var int
     */
    protected $prevPageNumber = -1;

    /**
     * 下一页的索引
     *
     * @var int
     */
    protected $nextPage = -1;

    /**
     * 下一页的页码
     *
     * @var int
     */
    protected $nextPageNumber = -1;

    /**
     * 当前页的索引
     *
     * @var int
     */
    protected $currentPage = -1;

    /**
     * 构造函数中提供的当前页索引，用于 setBasePageIndex() 后重新计算页码
     *
     * @var int
     */
    protected $_currentPage = -1;

    /**
     * 当前页的页码
     *
     * @var int
     */
    protected $currentPageNumber = -1;

    /**
     * 构造函数
     *
     * 如果 $source 参数是一个 Table 对象，则 Db_Pager 会调用
     * 该 TDG 对象的 findCount() 和 findAll() 来确定记录总数并返回记录集。
     *
     * 如果 $source 参数是一个字符串，则假定为 SQL 语句。这时，Db_Pager
     * 不会自动调用计算各项分页参数。必须通过 setCount() 方法来设置作为分页计算
     * 基础的记录总数。
     *
     * 同时，如果 $source 参数为一个字符串，则不需要 $conditions 和 $sortby 参数。
     * 而且可以通过 set_dbo() 方法设置要使用的数据库访问对象。否则 Db_Pager
     * 将尝试获取一个默认的数据库访问对象。
     *
     * @param Table|string $source
     * @param int $currentPage
     * @param int $pageSize
     * @param mixed $conditions
     * @param string $sortby
     * @param int $basePageIndex
     *
     * @return Db_Pager
     */
    function __construct($source, $currentPage, $pageSize = 20, $conditions = null, $sortby = null, $basePageIndex = 0)
    {
        $this->basePageIndex = $basePageIndex;
        $this->currentPage = $this->currentPage = $currentPage;
        $this->pageSize = $pageSize;

        if (is_object($source)) {
            $this->source = $source;
            $this->conditions = $conditions;
            $this->sortby = $sortby;
            $this->totalCount = $this->count = (int)$this->source->find($conditions)->count()->query();
            $this->computingPage();
        } elseif (!empty($source)) {
            $this->source = $source;
            $sql = "SELECT COUNT(*) FROM ( $source ) as _count_table";
            $this->dbo = QDBO_Abstract::get_dbo();
            $this->totalCount = $this->count = (int)$this->dbo->get_one($sql);
            $this->computingPage();
        }
    }

    /**
     * 设置分页索引第一页的基数
     *
     * @param int $index
     */
    function setBasePageIndex($index)
    {
        $this->basePageIndex = $index;
        $this->currentPage = $this->currentPage;
        $this->computingPage();
    }

    /**
     * 设置当前页码，以便用 findAll() 获得其他页的数据
     *
     * @param int $page
     */
    function setPage($page)
    {
        $this->currentPage = $page;
        $this->currentPage = $page;
        $this->computingPage();
    }

    /**
     * 设置记录总数，从而更新分页参数
     *
     * @param int $count
     */
    function setCount($count)
    {
        $this->count = $count;
        $this->computingPage();
    }

    /**
     * 设置数据库访问对象
     *
     * @param SDBO $dbo
     */
    function set_dbo(& $dbo)
    {
        $this->dbo =& $dbo;
    }

    /**
     * 返回当前页对应的记录集
     *
     * @param array $params
     *
     * @return array
     */
    function findAll(array $params = null)
    {
        if ($this->count == -1) {
            $this->count = 20;
        }

        $offset = ($this->currentPage - $this->basePageIndex) * $this->pageSize;
        if (is_object($this->source)) {
            $limit = array($this->pageSize, $offset);
            $rowset = $this->source->find($this->conditions)->order($this->sortby)->limit($limit)->query();
        } else {
            if (is_null($this->dbo)) {
                $this->dbo = QDBO_Abstract::get_dbo();
            }
            $rs = $this->dbo->select_limit($this->source, $this->pageSize, $offset);
            $rowset = $this->dbo->get_all($rs);
        }
        return $rowset;
    }

    /**
     * 返回分页信息，方便在模版中使用
     *
     * @param boolean $returnPageNumbers
     *
     * @return array
     */
    function getPagerData($returnPageNumbers = false)
    {
        $data = array(
            'pageSize' => $this->pageSize,
            'totalCount' => $this->totalCount,
            'count' => $this->count,
            'pageCount' => $this->pageCount,
            'firstPage' => $this->firstPage,
            'firstPageNumber' => $this->firstPageNumber,
            'lastPage' => $this->lastPage,
            'lastPageNumber' => $this->lastPageNumber,
            'prevPage' => $this->prevPage,
            'prevPageNumber' => $this->prevPageNumber,
            'nextPage' => $this->nextPage,
            'nextPageNumber' => $this->nextPageNumber,
            'currentPage' => $this->currentPage,
            'currentPageNumber' => $this->currentPageNumber,
        );

        if ($returnPageNumbers) {
            $data['pagesNumber'] = array();
            for ($i = 0; $i < $this->pageCount; $i++) {
                $data['pagesNumber'][$i] = $i + 1;
            }
        }

        return $data;
    }

    /**
     * 产生指定范围内的页面索引和页号
     *
     * @param int $currentPage
     * @param int $navbarLen
     *
     * @return array
     */
    function getNavbarIndexs($currentPage = 0, $navbarLen = 8)
    {
        $mid = intval($navbarLen / 2);
        if ($currentPage < $this->firstPage) {
            $currentPage = $this->firstPage;
        }
        if ($currentPage > $this->lastPage) {
            $currentPage = $this->lastPage;
        }

        $begin = $currentPage - $mid;
        if ($begin < $this->firstPage) { $begin = $this->firstPage; }
        $end = $begin + $navbarLen - 1;
        if ($end >= $this->lastPage) {
            $end = $this->lastPage;
            $begin = $end - $navbarLen + 1;
            if ($begin < $this->firstPage) { $begin = $this->firstPage; }
        }

        $data = array();
        for ($i = $begin; $i <= $end; $i++) {
            $data[] = array('index' => $i, 'number' => ($i + 1 - $this->basePageIndex));
        }
        return $data;
    }

    /**
     * 计算各项分页参数
     */
    function computingPage()
    {
        $this->pageCount = ceil($this->count / $this->pageSize);
        $this->firstPage = $this->basePageIndex;
        $this->lastPage = $this->pageCount + $this->basePageIndex - 1;
        if ($this->lastPage < $this->firstPage) { $this->lastPage = $this->firstPage; }

        if ($this->lastPage < $this->basePageIndex) {
            $this->lastPage = $this->basePageIndex;
        }

        if ($this->currentPage >= $this->pageCount + $this->basePageIndex) {
            $this->currentPage = $this->lastPage;
        }

        if ($this->currentPage < $this->basePageIndex) {
            $this->currentPage = $this->firstPage;
        }

        if ($this->currentPage < $this->lastPage - 1) {
            $this->nextPage = $this->currentPage + 1;
        } else {
            $this->nextPage = $this->lastPage;
        }

        if ($this->currentPage > $this->basePageIndex) {
            $this->prevPage = $this->currentPage - 1;
        } else {
            $this->prevPage = $this->basePageIndex;
        }

        $this->firstPageNumber = $this->firstPage + 1 - $this->basePageIndex;
        $this->lastPageNumber = $this->lastPage + 1 - $this->basePageIndex;
        $this->nextPageNumber = $this->nextPage + 1 - $this->basePageIndex;
        $this->prevPageNumber = $this->prevPage + 1 - $this->basePageIndex;
        $this->currentPageNumber = $this->currentPage + 1 - $this->basePageIndex;
    }
}
